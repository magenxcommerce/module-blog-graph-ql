<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver\Post;

use Magenx\Blog\Model\CategoryRepository;
use Magenx\Blog\Model\PostRepository;
use Magenx\BlogGraphQl\Model\Resolver\DataMapper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Batch resolver for BlogPost.categories — one relation-table query plus one
 * category lookup for the whole blogPosts list, not one pair per post.
 */
class Categories implements BatchResolverInterface
{
    private PostRepository $postRepository;
    private CategoryRepository $categoryRepository;
    private DataMapper $dataMapper;

    public function __construct(PostRepository $postRepository, CategoryRepository $categoryRepository, DataMapper $dataMapper)
    {
        $this->postRepository = $postRepository;
        $this->categoryRepository = $categoryRepository;
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

        $categoryIdsByPost = $this->postRepository->getCategoryIdsForPosts($postIds);
        $allCategoryIds = array_values(array_unique(array_merge([], ...array_values($categoryIdsByPost))));

        $categoryMap = [];
        foreach ($this->categoryRepository->getByIds($allCategoryIds) as $category) {
            $categoryMap[(int) $category->getId()] = $category;
        }

        foreach ($requests as $request) {
            $postId = $this->postIdOf($request);
            $items = [];
            foreach ($categoryIdsByPost[$postId] ?? [] as $categoryId) {
                if (isset($categoryMap[$categoryId])) {
                    $items[] = $this->dataMapper->mapCategory($categoryMap[$categoryId]);
                }
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
