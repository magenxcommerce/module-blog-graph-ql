# Magenx_BlogGraphQl

The GraphQL half of the blog. `Magenx_Blog`
([magenxcommerce/module-blog](https://github.com/magenxcommerce/module-blog))
owns the entities, the admin CRUD and the configuration; this module owns
nothing but the storefront-facing schema and its resolvers — no tables, no
`system.xml`, no admin, no `view/frontend`.

Same split as `Magenx_Rma` / `Magenx_RmaGraphQl` and
`Magenx_Helpdesk` / `Magenx_HelpdeskGraphQl`.

## Schema

| Root field | Returns |
| --- | --- |
| `blogPosts(filter, pageSize, currentPage)` | `BlogPosts` — paginated, published posts for the current store, newest first |
| `blogPost(urlKey)` | `BlogPost`, or null when it does not exist, is disabled, is not assigned to the current store, or is future-dated |
| `blogTag(urlKey)` | `BlogTag` with its published posts, or null |
| `blogCategories` | `[BlogPostCategory!]!`, every active category by position |

`BlogPostFilterInput` supports `url_key`, `category_id`, `tag_id` and `sku` —
all resolved server-side by `Magenx\Blog\Model\ResourceModel\Post\Collection`,
so the storefront never scans a full list client-side. `sku` is the "articles
about this product" lookup.

See `etc/schema.graphqls` for the full, `@doc`'d surface.

## Resolvers

`BlogPost.categories`, `.tags`, `.products` and `.posts` are
`BatchResolverInterface` implementations — they ride a `blogPosts` listing, so
each does one relation-table query plus one entity load for the whole page
rather than per post. The four root queries and `BlogTag.posts` are plain
`ResolverInterface`: a query root, or a field only ever resolved on a single
object, has nothing to batch over.

`BlogPost.products` returns SKU/url_key references rather than
`ProductInterface`; resolve full catalog data through the storefront's own
product-by-SKU lookup when display data is needed.

## Configuration

Read from `Magenx_Blog` (Stores > Configuration > Magenx > Blog > General) via
`Magenx\Blog\Model\Config`. With **Enable Blog** set to No, every root query
resolves to an empty result for that store; no data is removed. **Posts Per
Page** is the default `pageSize`, **Maximum Page Size** the upper bound a query
may request.

## Install

```bash
composer require magenxcommerce/module-blog-graph-ql
bin/magento module:enable Magenx_BlogGraphQl
bin/magento setup:upgrade
```

`Magenx_Blog` must be installed and enabled — composer pulls it in.

## Caveats

- No live Magento install was available while this module was split out of
  `Magenx_Blog`; the schema still needs a `setup:upgrade` + a query against a
  running backend to be confirmed end to end.
- `post_content` and `short_description` are returned exactly as authored —
  nothing is sanitized server-side. The Next.js storefront runs them through
  `sanitizeCmsHtml` on read; any other consumer has to do its own.
- `image` is a media path rooted at the store's media base
  (`/media/blog/hero.jpg`), deliberately host-less: in a headless setup the
  storefront serves media from its own `/media` route.
- The storefront pins a verbatim copy of `etc/schema.graphqls` at
  `magento/schemas/Magenx_BlogGraphQl.graphqls` and fails its build on drift.
  A field rename here is a breaking change for it — Magento rejects the whole
  document, not one field.
