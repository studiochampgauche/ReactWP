---
name: backend-expert-reactwp-runtime-payloads
description: ReactWP backend runtime guidance for the rwp facade, route resolution, bootstrap composition, public payload normalization, extension filters, and stable backend-to-frontend contracts.
---

# ReactWP Runtime and Payloads

## Runtime Data Flow

ReactWP's shared backend flow is:

```text
WordPress request/object
  -> RouteResolver
  -> ACF formatted fields + project route filters
  -> RenderStrategy + head data
  -> route contract
  -> Bootstrap (integrated theme)
     or PublicPayload + REST response (headless/client navigation)
```

Do not build a second resolver for ordinary posts, terms, authors, and 404s. Extend the route at its documented filter or create a separate resource only when it has a distinct identity, pagination, permission, or cache lifetime.

## The `rwp` Facade

The global `rwp` alias exposes selected ReactWP behavior:

| Method | Purpose |
| --- | --- |
| `rwp::field()` | Read an ACF value through ReactWP's field helper |
| `rwp::cpt()` | Create a `WP_Query` using project defaults |
| `rwp::menu()` | Read a theme-location menu using project defaults |
| `rwp::source()` | Resolve a theme source path or URL |
| `rwp::bootstrap()` | Build the current integrated bootstrap payload |
| `rwp::client_cache_version()` | Read the browser-cache generation |
| `rwp::bust_client_cache()` | Rotate client generation and trigger global render invalidation |
| `rwp::invalidate_render_cache()` | Invalidate render cache tags |
| `rwp::preview_token()` | Create an authorized preview token |
| `rwp::sanitize()` / `rwp::escape()` | Delegate to context-specific framework helpers |

Read the implementation before assuming defaults. For example, `rwp::cpt()` is a thin `WP_Query` wrapper, not a repository layer, and `rwp::field()` depends on ACF being active.

Do not recommend `rwp::button()` as a built-in helper. The current facade still declares it, but ReactWP does not ship the referenced `ReactWP\Utils\Button` implementation; it is unusable unless a project deliberately supplies a compatibility class.

## Route Resolution

`RouteResolver` resolves:

- current WordPress requests;
- a safe local path for client/headless navigation;
- a post ID;
- a supported WordPress object;
- a normalized 404 when no public route exists.

Public paths reject non-public/password-protected posts. Public authors require at least one public post. Terms require a public taxonomy. Current same-origin WordPress requests may render an object the logged-in user can read, which is not permission to publish it through a public endpoint.

The stable route shape is:

```text
id             integer, user_N, term_N, or null
type           post type, user, term, or 404
template       React template/domain view key
pageName       display title
path/search    normalized route location
query          bounded normalized query object
url            canonical/request URL
seo            route SEO data
mediaGroups    loader/media grouping hint
data           project/ACF content
lang           current language code
head           normalized head entries
render         render mode and cache policy
is404          boolean
```

Keep new domain data under `route.data`. Do not rename or repurpose top-level keys for one project.

## ACF-to-Route Mapping

For posts, users, and terms, ReactWP finds active matching ACF groups with `show_in_rest`, reads named fields, and then extracts reserved fields. It defaults `template` to `Default` and passes the remaining formatted values as `data`.

This mapping is convenient for page-shaped content. It is not ideal for every data set. Use a separate endpoint for large paginated collections, mutations, user-specific resources, frequently refreshed external data, or resources with distinct authorization/cache rules.

## Extending a Route

Use `rwp_route_payload` for route-scoped derived values:

```php
add_filter('rwp_route_payload', function($route, $object){
    if(!$object instanceof WP_Post || $object->post_type !== 'project'){
        return $route;
    }

    $route['data']['facts'] = [
        'year' => (int)get_post_meta($object->ID, 'project_year', true),
        'featured' => (bool)get_post_meta($object->ID, 'featured', true),
    ];

    return $route;
}, 10, 2);
```

Requirements for the callback:

- return an array even for unsupported objects;
- handle 404 calls where `$object` is `null`;
- emit only JSON-serializable values;
- bound queries and remote work;
- preserve route visibility;
- remain deterministic for static/server rendering;
- add cache dependencies/tags when derived data changes independently.

If the data is already stored in an exposed ACF group, do not copy it again through a filter.

## Bootstrap Ownership

`Bootstrap::payload()` combines site, theme, system/endpoints, critical assets, navigation, route, current user, and SEO defaults. The integrated theme embeds it as JSON in the page.

Use `rwp_bootstrap` sparingly. Global bootstrap data is paid on every initial request and can affect HTML caching/privacy. Suitable additions are small, stable, universally required runtime values. Route content belongs in the route; optional public global settings belong in the headless settings contract; large resources belong behind focused endpoints.

For guests, ReactWP normalizes route `data` and `seo` through `PublicPayload`. Logged-in same-origin bootstraps may contain a richer current-user context. Do not make an external headless consumer depend on this private bootstrap shape.

`Bootstrap::json()` uses WordPress JSON encoding with hex escaping to safely embed the JSON script. Do not replace it with manual concatenation or plain `json_encode()` inside HTML.

## PublicPayload

`PublicPayload` is the public response adapter. It:

- emits `apiVersion` and `generatedAt`;
- normalizes site/system/theme/route/navigation fields;
- bounds nesting, array counts, string sizes, head entries, and menu items;
- converts known post, term, user, and attachment values into limited references;
- normalizes URLs, template keys, query data, and render policy;
- deliberately omits the integrated current-user bootstrap from public bootstrap responses.

It does not determine business authorization, sanitize arbitrary HTML, or make a secret safe to expose. Project filters remain responsible for selecting public data. Load `security-expert` whenever data visibility is involved.

For a custom endpoint compatible with the API metadata shape:

```php
return new WP_REST_Response(
    \ReactWP\Runtime\PublicPayload::response([
        'items' => $items,
        'pagination' => $pagination,
    ]),
    200
);
```

Shape `$items` explicitly before passing them to the normalizer.

## Head Data and SEO

`RouteResolver` obtains head entries through `rwp_wp_head`, passing route/object context. Keep entries bounded and valid for both initial output and client navigation. Project SEO values generally belong in `route.seo`; the SEO plugin can derive final head tags.

Do not pass arbitrary editor HTML into the head. Use context-specific escaping and allowlisted tag/attribute construction. Load `security-expert` for any dynamic head markup.

## Contract Changes

Changing a route or endpoint contract affects:

- initial integrated bootstrap;
- client navigation endpoint;
- static generator and SSR;
- public headless consumers;
- template registry assumptions;
- caches and snapshots/tests.

Prefer additive optional fields. For a breaking change, introduce a new endpoint/version or compatibility window, update `PublicPayload::API_VERSION` only with deliberate consumer coordination, and add contract tests for old/new behavior.

## Debugging Order

When React receives missing or unexpected data:

1. verify the WordPress object and public visibility;
2. verify the ACF group location, active state, `show_in_rest`, field name, and stored value;
3. inspect `RouteResolver::payload_from_object()` before filters;
4. inspect `rwp_route_payload` callbacks;
5. compare integrated bootstrap with `/reactwp/v1/route?view=...`;
6. inspect `PublicPayload` normalization;
7. check language/query normalization and cache freshness;
8. verify the template/consumer reads the documented key.

This sequence locates the boundary where the shape changed instead of compensating in React.
