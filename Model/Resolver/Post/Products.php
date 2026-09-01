<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver\Post;

use Magenx\Blog\Model\PostRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Batch resolver for BlogPost.products — the module's related-products
 * field. One relation-table query plus one product collection load for the
 * whole blogPosts list, delegating product data to the core catalog
 * collection rather than re-reading EAV attributes by hand.
 */
class Products implements BatchResolverInterface
{
    private PostRepository $postRepository;
    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(PostRepository $postRepository, ProductCollectionFactory $productCollectionFactory)
    {
        $this->postRepository = $postRepository;
        $this->productCollectionFactory = $productCollectionFactory;
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

        $positionsByPost = $this->postRepository->getProductPositionsForPosts($postIds);
        $allProductIds = array_values(array_unique(array_merge([], ...array_map('array_keys', array_values($positionsByPost)))));

        $productMap = [];
        if ($allProductIds) {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['url_key']);
            $collection->addFieldToFilter('entity_id', ['in' => $allProductIds]);
            foreach ($collection as $product) {
                $productMap[(int) $product->getId()] = $product;
            }
        }

        foreach ($requests as $request) {
            $postId = $this->postIdOf($request);
            $positions = $positionsByPost[$postId] ?? [];
            asort($positions);

            $items = [];
            foreach (array_keys($positions) as $productId) {
                $product = $productMap[$productId] ?? null;
                if (!$product) {
                    continue;
                }
                $items[] = [
                    'entity_id' => (int) $product->getId(),
                    'sku' => (string) $product->getSku(),
                    'url_key' => $product->getUrlKey(),
                ];
            }
            $response->addResponse($request, ['items' => $items]);
        }

        return $response;
    }

    private function postIdOf(BatchRequestItemInterface $request): int
    {
        return (int) ($request->getValue()['post_id'] ?? 0);
    }
}
