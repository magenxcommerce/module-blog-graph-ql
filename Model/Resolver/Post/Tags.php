<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver\Post;

use Magenx\Blog\Model\PostRepository;
use Magenx\BlogGraphQl\Model\Resolver\DataMapper;
use Magenx\Blog\Model\TagRepository;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class Tags implements BatchResolverInterface
{
    private PostRepository $postRepository;
    private TagRepository $tagRepository;
    private DataMapper $dataMapper;

    public function __construct(PostRepository $postRepository, TagRepository $tagRepository, DataMapper $dataMapper)
    {
        $this->postRepository = $postRepository;
        $this->tagRepository = $tagRepository;
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

        $tagIdsByPost = $this->postRepository->getTagIdsForPosts($postIds);
        $allTagIds = array_values(array_unique(array_merge([], ...array_values($tagIdsByPost))));

        $tagMap = [];
        foreach ($this->tagRepository->getByIds($allTagIds) as $tag) {
            $tagMap[(int) $tag->getId()] = $tag;
        }

        foreach ($requests as $request) {
            $postId = $this->postIdOf($request);
            $items = [];
            foreach ($tagIdsByPost[$postId] ?? [] as $tagId) {
                if (isset($tagMap[$tagId])) {
                    $items[] = $this->dataMapper->mapTag($tagMap[$tagId]);
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
