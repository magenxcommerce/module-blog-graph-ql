<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver\Tag;

use Magenx\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magenx\BlogGraphQl\Model\Resolver\DataMapper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Plain resolver: BlogTag is only ever resolved as a single object (root
 * Query.blogTag returns one tag), so there is no list to batch over.
 */
class Posts implements ResolverInterface
{
    private CollectionFactory $collectionFactory;
    private DataMapper $dataMapper;

    public function __construct(CollectionFactory $collectionFactory, DataMapper $dataMapper)
    {
        $this->collectionFactory = $collectionFactory;
        $this->dataMapper = $dataMapper;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $tagId = (int) ($value['tag_id'] ?? 0);
        if (!$tagId) {
            return ['total_count' => 0, 'items' => []];
        }

        /** @var ContextInterface $context */
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        $collection = $this->collectionFactory->create();
        $collection->addPublishedFilter();
        $collection->addStoreFilter($storeId);
        $collection->addTagFilter($tagId);
        $collection->setOrder('publish_date', 'DESC');

        $items = [];
        foreach ($collection as $post) {
            $items[] = $this->dataMapper->mapPost($post);
        }

        return ['total_count' => count($items), 'items' => $items];
    }
}
