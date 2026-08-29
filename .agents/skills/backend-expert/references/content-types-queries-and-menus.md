---
name: backend-expert-content-types-queries-menus
description: WordPress and ReactWP conventions for custom post types, taxonomies, queries, archives, menus, options, references, pagination, and multilingual content behavior.
---

# Content Types, Queries, and Menus

## Model the Domain Before the Screen

Choose a post type when content has identity, editorial lifecycle, permissions, archives, revisions, relationships, or independent URLs. Choose a taxonomy when terms classify several objects and need reusable archives/filtering. Choose options for small site-wide configuration, not an unbounded collection of records.

Avoid creating a post type for every visual module. Layout sections generally belong in an ACF field structure attached to a durable domain object. Avoid storing queryable business state inside opaque serialized arrays when native post meta, taxonomy, or a purpose-built table is required.

Document for each model:

- singular/plural meaning and ownership;
- public/queryable/admin visibility;
- supported features and capability model;
- rewrite/archive behavior;
- REST visibility;
- language/translation behavior;
- relationships and deletion behavior;
- expected volume and query patterns.

## Registration

Register post types and taxonomies on `init` with stable slugs:

```php
add_action('init', function(){
    register_post_type('project', [
        'labels' => [
            'name' => __('Projects', 'project'),
            'singular_name' => __('Project', 'project'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'projects'],
        'supports' => ['title', 'thumbnail', 'revisions'],
    ]);

    register_taxonomy('project_type', ['project'], [
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'project-type'],
    ]);
});
```

Use project-specific capabilities when editors should manage one domain without broad `edit_posts` rights. Do not use UI hiding as authorization.

Changing rewrites is structural. Flush rules once during a controlled activation/migration or by saving permalinks during development; never call `flush_rewrite_rules()` on every request.

ReactWP `RouteResolver` can resolve public posts, public taxonomy terms, and public authors. A post must be publicly viewable and not password protected for public route endpoints. Private previews and authenticated reads use different permission paths.

## Route Templates for New Types

ReactWP selects a template from the ACF `react_template` field, defaulting to `Default`. For a content type:

1. include the type in the React Template global setting so the runtime ACF field group appears;
2. assign a template key to entries or derive one in `rwp_route_payload`;
3. register the exact same key in the integrated theme, or map it in the headless consumer;
4. define render/cache tags for queries affected by the type.

If every entry in a type uses one template, a route filter may set the default without forcing editors to repeat it. Preserve an explicit editor override only when it is a real content requirement.

## Query Discipline

Use `WP_Query`, `get_posts`, `get_terms`, or specific WordPress APIs before direct SQL. Define bounds and output shape:

```php
$page = max(1, (int)($route['query']['page'] ?? 1));

$query = new WP_Query([
    'post_type' => 'project',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $page,
    'no_found_rows' => false,
    'ignore_sticky_posts' => true,
]);

$items = array_map(static function(WP_Post $post){
    return [
        'id' => (int)$post->ID,
        'title' => get_the_title($post),
        'url' => get_permalink($post),
    ];
}, $query->posts);
```

For non-paginated supporting queries, use `no_found_rows => true`. Request only IDs when full objects are unnecessary. Prime metadata/term caches deliberately for lists, and avoid calling `get_field()` repeatedly inside nested loops without understanding query cost.

Do not mutate the global main query to assemble route payload data. If using template-loop functions that modify globals, restore them with `wp_reset_postdata()`. Prefer explicit local objects in services and filters.

Allowlist query parameters before mapping them to `WP_Query`. Never pass a route's full query array through. Search, ordering, meta keys, taxonomy names, post types, pagination, and per-page limits each need a defined contract. Load `security-expert` for request-driven queries or direct SQL.

## Serialization and References

Public contracts should contain stable references, not full database objects. A useful list item generally has:

```text
id, type, slug, title, url, image/reference fields, selected taxonomy terms
```

ReactWP `PublicPayload` converts recognized `WP_Post`, `WP_Term`, `WP_User`, and attachment values into bounded references for public endpoints. Do not depend on this as an excuse to return arbitrary objects: authenticated integrated bootstrap data and custom responses may follow different paths. Shape values explicitly so every consumer sees the same contract.

Decide whether an ACF relationship field returns IDs or objects. IDs are simpler for stable storage and batch resolution; objects are convenient in PHP but easier to overexpose or query inefficiently. Public payloads should still be explicit arrays.

## Menus

ReactWP's `MenuBuilder` reads registered theme locations and emits nested normalized items. `rwp::menu()` wraps the project menu helper, while the bootstrap and headless navigation endpoint use the runtime builder.

The built-in Site settings define theme locations through ACF options. `rwp_register_nav_menus_from_options()` registers them during `init`. Before adding a hard-coded location, verify whether the project expects it to be editor-configurable.

Menu consumers should receive stable fields such as `id`, `label`, `url`, `path`, `target`, `classes`, and `children`. Keep external URLs and targets distinguishable from internal routes. Menu updates automatically invalidate navigation/render caches through `wp_update_nav_menu`; custom navigation sources need equivalent invalidation.

## Options and Public Settings

ACF options are appropriate for settings with editorial ownership, such as contact information, global labels, allowed origins, theme locations, or feature copy. They are not automatically public.

Choose one exposure path:

- include route-specific derived values in `route.data`;
- include deliberately public global values through `rwp_headless_public_settings`;
- keep same-origin/private settings out of public responses;
- create a focused endpoint for a separately cached resource.

Do not expose operational credentials, private URLs, internal IDs, license data, or configuration simply because it exists on an options page.

## Multilingual Behavior

ReactWP recognizes current language through Polylang when available and otherwise uses `CL`/WordPress locale. Route keys include language. For requested headless languages and static regeneration, ReactWP calls Polylang's switch function when available and fires WPML's `wpml_switch_language` action.

Do not assume one multilingual plugin is always installed. Use feature checks and define:

- whether slugs are translated;
- how linked objects resolve in the current language;
- whether option values are global or per language;
- how fallback content behaves;
- whether cache tags/keys include language;
- how the external headless router represents locale prefixes.

Test at least one translated route and one missing translation when multilingual content is in scope.

## Review Checklist

- Content belongs in the selected native model rather than a visual workaround.
- Registration slugs, capabilities, REST visibility, and rewrites are intentional.
- Route visibility matches post status, password, author, and taxonomy rules.
- Query inputs are allowlisted and list size is bounded.
- Payloads contain explicit serializable references rather than raw objects.
- Menu and option changes have an invalidation path.
- Template keys agree across ACF, ReactWP, and the consumer.
- Language and fallback behavior are defined.
