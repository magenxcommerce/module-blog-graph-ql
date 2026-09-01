<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver;

use Magenx\Blog\Model\Config;
use Magenx\Blog\Model\TagRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class BlogTag implements ResolverInterface
{
    private TagRepository $tagRepository;
    private DataMapper $dataMapper;
    private Config $config;

    public function __construct(TagRepository $tagRepository, DataMapper $dataMapper, Config $config)
    {
        $this->tagRepository = $tagRepository;
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

        try {
            $tag = $this->tagRepository->getByUrlKey($urlKey);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $this->dataMapper->mapTag($tag);
    }
}
