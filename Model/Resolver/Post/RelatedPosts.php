<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver\Post;

use Magenx\Blog\Model\PostRepository;
use Magenx\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magenx\BlogGraphQl\Model\Resolver\DataMapper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class RelatedPosts implements BatchResolverInterface
{
    private PostRepository $postRepository;
    private CollectionFactory $collectionFactory;
    private DataMapper $dataMapper;

    public function __construct(PostRepository $postRepository, CollectionFactory $collectionFactory, DataMapper $dataMapper)
    {
        $this->postRepository = $postRepository;
        $this->collectionFactory = $collectionFactory;
        $this->dataMapper = $dataMapper;
    }

    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $response = new BatchResponse();

        $postIds = [];
        foreach ($requests as $request) {
            $postId = $this->postIdOf($request);
            if ($postId) {
                $postIds[] = $postId;
            }
        }
        $postIds = array_values(array_unique($postIds));

        $positionsByPost = $this->postRepository->getRelatedPostPositionsForPosts($postIds);
        $allRelatedIds = array_values(array_unique(array_merge([], ...array_map('array_keys', array_values($positionsByPost)))));

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        $postMap = [];
        if ($allRelatedIds) {
            $collection = $this->collectionFactory->create();
            $collection->addPublishedFilter();
            $collection->addStoreFilter($storeId);
            $collection->addFieldToFilter('post_id', ['in' => $allRelatedIds]);
            foreach ($collection as $post) {
                $postMap[(int) $post->getId()] = $post;
            }
        }

        foreach ($requests as $request) {
            $postId = $this->postIdOf($request);
            $positions = $positionsByPost[$postId] ?? [];
            asort($positions);

            $items = [];
            foreach (array_keys($positions) as $relatedId) {
                if (isset($postMap[$relatedId])) {
                    $items[] = $this->dataMapper->mapPost($postMap[$relatedId]);
                }
            }
            $response->addResponse($request, ['total_count' => count($items), 'items' => $items]);
        }

        return $response;
    }

    private function postIdOf(BatchRequestItemInterface $request): int
    {
        return (int) ($request->getValue()['post_id'] ?? 0);
    }
}
