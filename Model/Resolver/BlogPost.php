<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver;

use Magenx\Blog\Model\Config;
use Magenx\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Root Query.blogPost(urlKey). Fails soft: returns null (not an error) when
 * the post does not exist, is disabled, is not assigned to this store, or is
 * future-dated — the direct server-side lookup the previous integration's
 * PostsFilterInput could not reliably do.
 */
class BlogPost implements ResolverInterface
{
    private CollectionFactory $collectionFactory;
    private DataMapper $dataMapper;
    private Config $config;

    public function __construct(CollectionFactory $collectionFactory, DataMapper $dataMapper, Config $config)
    {
        $this->collectionFactory = $collectionFactory;
        $this->dataMapper = $dataMapper;
        $this->config = $config;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $urlKey = trim((string) ($args['urlKey'] ?? ''));
        if ($urlKey === '') {
            return null;
        }

        /** @var ContextInterface $context */
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        if (!$this->config->isEnabled($storeId)) {
            return null;
        }

        $collection = $this->collectionFactory->create();
        $collection->addPublishedFilter();
        $collection->addStoreFilter($storeId);
        $collection->addUrlKeyFilter($urlKey);
        $collection->setPageSize(1);

        $post = $collection->getFirstItem();
        if (!$post->getId()) {
            return null;
        }

        return $this->dataMapper->mapPost($post);
    }
}
