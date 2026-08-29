---
name: backend-expert-rendering-cache-invalidation
description: ReactWP backend guidance for client, static, and server rendering, render configuration precedence, public/private caches, tags, regeneration, and dependency-driven invalidation.
---

# Rendering, Cache, and Invalidation

## Rendering Is a Backend Contract

Although React produces the view, the backend route selects a rendering policy that affects privacy, infrastructure, invalidation, and payload availability. Treat `route.render` as part of the domain delivery contract.

ReactWP supports:

| Mode | Initial HTML | Production Node | Default HTML cache |
| --- | --- | --- | --- |
| `client` | Browser renders | No | Off |
| `static` | Generated artifact | No | On, public |
| `server` | Request-time renderer | Yes | Off unless configured |

All modes hydrate/use the same route and template contracts. A backend filter must work during WordPress requests, REST route calls, generation, and SSR.

## Configuration Precedence

`RenderStrategy::resolve()` merges sources in this order:

1. generated template manifest configuration;
2. `rwp_render_templates` filter configuration;
3. any existing `route.render` override;
4. per-object ACF fields (`react_render_mode`, cache scope, TTL);
5. `rwp_render_config` filter;
6. normalized `rwp_render_mode` filter.

Later values can override earlier ones. Before debugging an unexpected mode, inspect every source rather than changing the registry repeatedly.

Normalized modes are only `client`, `static`, and `server`. Cache scope is only `public` or `private`. TTL is bounded to one year. Template cache tags must match `type:value` using safe characters.

## Select a Mode

Use `client` when initial HTML is not valuable, the route is mostly private/interactive, or backend rendering dependencies are not deterministic.

Use `static` when content is public, deterministic, enumerable at generation time, and changes can trigger regeneration/invalidation. Marketing pages, projects, services, and articles commonly fit.

Use `server` when request context or frequently changing data must influence initial HTML and a maintained Node service is available. Personalized routes usually require private scope and often no persistent HTML cache.

Do not use server rendering as a substitute for an API design. Do not put private data into static output. When uncertain about privacy scope, load `security-expert`.

## Cache Shape

ReactWP normalizes cache configuration to:

```text
html     cache rendered HTML
scope    public or private
ttl      seconds; 0 follows non-expiring/version/tag semantics as implemented
payload  cache route payload when appropriate
media    cache media preparation when appropriate
tags     dependency tags
```

Public scope is shared among guests. Private scope includes user identity in the relevant render cache key. Public/private describe SSR cache identity, not two different global cache-bust buttons.

Never cache personalized, capability-dependent, preview, cart, account, or nonce-bearing HTML publicly. Do not assume removing one field after rendering prevents leakage if the template already received it.

## Route Keys and Query Dimensions

Render route keys include language, normalized path, and normalized search/query. If a project ignores some query parameters during rendering, define/allowlist that policy deliberately; arbitrary tracking parameters can fragment caches, while ignored content-changing parameters can serve the wrong output.

Custom endpoint and external caches must separately include every dimension that changes content: language, page, filters, authorization scope, experiment, currency, and relevant headers. ReactWP cannot infer every project dimension.

## Dependency Tags

Tags represent reasons output becomes stale. Examples:

```text
post:42
post-type:project
term:8
taxonomy:project_type
menu:all
settings:all
integration:inventory
```

`post:*`, `post-type:*`, `term:*`, `taxonomy:*`, `menu:all`, and `settings:all` are built-in tag families. `integration:inventory` illustrates a project-owned custom tag; custom tags are useful only when the project also invalidates the exact same normalized tag.

Attach tags at the template/filter/route configuration that knows the dependency. A project listing may need `post-type:project` plus relevant taxonomy/settings tags. A project detail needs its post tag and tags for any related/global content used.

Keep tags semantic and bounded. Do not build them directly from arbitrary request strings.

## Built-in Invalidation

`RenderCache` listens to:

- `save_post` and `deleted_post`;
- menu updates;
- term create/edit/delete;
- `acf/save_post`;
- client-cache busts.

The current built-in mapping is exact:

- post save/delete: `post:<id>` and `post-type:<slug>`;
- menu update: `menu:all`;
- term create/edit/delete: `term:<id>` and `taxonomy:<slug>`;
- ACF option-page save (`options` or `option`): `settings:all`;
- client-cache bust: `render:all`.

An ACF save for a post normally relies on WordPress's `save_post` hook. ACF user-field saves have no automatic user-tag invalidation. Custom option groups, external records, relationships, and user-derived output therefore need project-owned tags plus matching targeted invalidation when the built-in mapping cannot represent the dependency.

Use:

```php
rwp::invalidate_render_cache([
    'post-type:project',
    'integration:inventory',
]);
```

after a project-owned update whose built-in hooks cannot express the dependency. Prefer targeted tags to global invalidation for frequent changes. Use `rwp::bust_client_cache()` for a deliberate global generation rotation, not as the default after every edit.

## ACF and Options

ACF option-page saves trigger `settings:all`; ACF post changes normally receive the post/type invalidation through `save_post`. Derived relationships still require thought. If editing Project A changes a listing or Project B's related section, the affected caches need type/relationship tags, not only `post:A`.

For option pages, define which templates/routes consume each option group. Use the built-in `settings:all` dependency when broad invalidation is acceptable, or attach a project-owned tag such as `settings:commerce` and invalidate that exact tag after save when a narrower contract is required.

## Static Generation and Regeneration

`npm run generate` enumerates/builds static routes according to ReactWP tooling. A `static` registry declaration does not guarantee an artifact exists in the deployed environment. The PHP `InitialRender` service may fall back when the manifest/fragment is missing or stale.

When adding static content:

- ensure the route is discoverable by the generator/sitemap contract;
- keep backend data deterministic and publicly accessible at build time;
- deploy manifest and HTML fragments with matching assets;
- define regeneration after content changes;
- verify direct requests use `data-rwp-render="static"` rather than silently falling back.

Runtime static regeneration touches filesystem and external process boundaries; use the dedicated tests and `security-expert` when modifying it.

## External Headless Caches

ReactWP invalidation affects ReactWP-managed state. It does not automatically purge Next/Astro caches, a CDN, reverse proxy, browser service worker, or remote search index.

Connect WordPress changes to the consumer through an authenticated webhook, queue, or deployment integration. Define:

- event payload with affected IDs/tags/paths/languages;
- retry/backoff and idempotency;
- authentication and replay protection;
- timeout and failure observability;
- whether failures block editing or become eventual consistency.

Do not send secrets in URLs or logs. Load `security-expert` for webhook implementation.

## Verification

After a cache-affecting backend change:

1. request the route/resource twice and confirm intended reuse;
2. edit each dependency type;
3. confirm relevant entries become stale while unrelated entries remain valid;
4. test anonymous and authenticated identities;
5. test language/query variants;
6. verify preview bypasses public caches;
7. verify static/SSR fallback behavior when artifacts/services are unavailable;
8. run `npm run test:render-cache`, `npm run test:static-regenerator`, and `npm run test:render` as relevant.

For production output or server headers/configuration, use `npm run prod` and inspect deployed artifacts rather than relying only on unit tests.
