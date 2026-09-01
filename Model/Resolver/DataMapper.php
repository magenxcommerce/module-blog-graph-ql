<?php

declare(strict_types=1);

namespace Magenx\BlogGraphQl\Model\Resolver;

use Magenx\Blog\Model\Category;
use Magenx\Blog\Model\Post;
use Magenx\Blog\Model\Tag;

/**
 * Maps entity models to the plain arrays the GraphQL resolvers return.
 * Every array keeps the entity's id under its own key (post_id/category_id/
 * tag_id) so downstream batch resolvers can read it back off
 * $request->getValue() without a second load.
 */
class DataMapper
{
    public function mapPost(Post $post): array
    {
        return [
            'post_id' => (int) $post->getId(),
            'name' => (string) $post->getTitle(),
            'short_description' => $post->getShortDescription(),
            'post_content' => $post->getContent(),
            'image' => $post->getImage(),
            'url_key' => (string) $post->getUrlKey(),
            'publish_date' => $post->getPublishDate(),
            'author_name' => $post->getAuthorName(),
            'meta_title' => $post->getMetaTitle(),
            'meta_description' => $post->getMetaDescription(),
            'meta_keywords' => $post->getMetaKeywords(),
        ];
    }

    public function mapCategory(Category $category): array
    {
        return [
            'category_id' => (int) $category->getId(),
            'name' => (string) $category->getName(),
            'url_key' => (string) $category->getUrlKey(),
        ];
    }

    public function mapTag(Tag $tag): array
    {
        return [
            'tag_id' => (int) $tag->getId(),
            'name' => (string) $tag->getName(),
            'url_key' => (string) $tag->getUrlKey(),
            'description' => $tag->getDescription(),
            'meta_title' => $tag->getMetaTitle(),
            'meta_description' => $tag->getMetaDescription(),
        ];
    }
}
