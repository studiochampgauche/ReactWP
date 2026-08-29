---
name: backend-expert-headless-api-consumers
description: ReactWP headless integration guidance for public endpoints, route consumption, CORS and deployment topology, previews, authentication, consumer routing, caching, and cross-framework data contracts.
---

# Headless API and Consumers

## Ownership in Headless Mode

In headless mode, WordPress/ReactWP owns content, route normalization, public API shapes, permissions, previews, and CMS-side invalidation. The external application owns its router, rendering framework, component mapping, frontend cache, document integration, and deployment.

Do not import the integrated theme runtime into an external app merely to reuse data fetching. Consume the documented HTTP contract and create a small adapter for the target framework.

## Built-in Endpoints

ReactWP exposes these routes under `/wp-json/reactwp/v1`:

| Endpoint | Purpose |
| --- | --- |
| `/bootstrap` | Public site/theme/system/assets/navigation/current route contract |
| `/route?view=/path/` | Resolve one public route |
| `/navigation` | Normalized menus by location |
| `/settings` | Explicit public settings from `rwp_headless_public_settings` |
| `/sitemap` | Bounded public route inventory |
| `/preview` | Token-authorized non-public route preview |
| `/auth/me` | Authenticated current-user contract and REST nonce |
| `/auth/login` | Cookie-authenticated login flow |
| `/auth/logout` | Authenticated logout flow |

Responses include `apiVersion` and `generatedAt`. Consumers should validate required fields and tolerate additive optional fields.

`bootstrap` accepts optional safe-local `view` and `lang`; without `view` it resolves the site front page. `route` requires `view`, returns HTTP 404 for the normalized 404 route, and does not switch language from a separate `lang` parameter—the localized path is the route input. Navigation accepts an optional 100-byte sanitized `location`; bootstrap, navigation, settings, sitemap, and preview accept `lang`. Preview accepts `postId` or `id` over GET/POST.

Public bootstrap exposes endpoint URLs through both `system.headless` (`routeEndpoint`, etc.) and the compatibility `system.endpoints` map (`route`, etc.). It never contains `currentUser` or a REST nonce.

## Route Fetching

Use the route endpoint for a normalized WordPress URL:

```js
export async function getRoute(cmsUrl, view, options = {}) {
    const endpoint = new URL('/wp-json/reactwp/v1/route', cmsUrl);
    endpoint.searchParams.set('view', view);

    const response = await fetch(endpoint, {
        headers: { Accept: 'application/json' },
        ...options,
    });

    const payload = await response.json();

    if(!response.ok && response.status !== 404){
        throw new Error(`ReactWP route failed with ${response.status}`);
    }

    return payload.route;
}
```

Pass a local path/search, not an arbitrary external URL. Use `route.path`, `route.search`, `route.url`, and `route.status` rather than reconstructing WordPress routing rules in the consumer.

Map `route.template` to the external framework's component/page loader. Provide an explicit fallback for unknown template keys and log contract mismatches in development.

## Public Data Design

Only expose fields the external application needs. Use:

- route ACF data for route-owned content;
- `/settings` for small, deliberately public site-wide values;
- `/navigation` for menus;
- a versioned custom endpoint for independently paginated/updated resources;
- authenticated endpoints for private account data.

Do not expose same-origin admin URLs, nonces, private options, raw WordPress objects, or internal plugin settings simply to simplify frontend code. `PublicPayload` normalizes shape but project filters still decide what becomes public.

Keep rich HTML semantics and its backend allowlist explicit. An external React consumer may use `dangerouslySetInnerHTML` when sanitized markup passes through unchanged; use `html-react-parser` only for node-level replacement or transformation. Vue/Svelte/Astro consumers need the same sanitized content contract and should add a parser only when their rendering also needs transformation.

Do not bypass the public projection limits when extending it: depth 20, 10,000 array entries, 2 MiB strings, 100 head strings of 65,536 bytes, 100 navigation locations, 500 items per sibling list, and 10 child levels. Project endpoints need their own explicit limits even if they call `PublicPayload::response()`.

## Origins and Topology

Allowed headless origins come from ReactWP's Headless API ACF settings and the `rwp_headless_allowed_origins` filter. Store origins as exact scheme/host/port values, not paths or wildcards.

Choose and document deployment topology:

- browser calls CMS directly across origins;
- frontend server/proxy calls CMS server-to-server;
- same parent domain with separate hosts;
- frontend build fetches public content at build time.

This choice affects CORS, cookies, HTTPS, preview tokens, rate limiting, cache headers, and secret placement. Server-to-server calls do not need browser CORS, but any credential used by the frontend server must remain server-only.

For cross-origin browser requests with WordPress cookies, credentials, exact origins, nonce transport, SameSite cookie behavior, HTTPS, and CSRF protection all need to align. Load `security-expert`; do not solve authentication by broadly allowing origins.

## Authentication

Public content should remain public and cacheable without login. For private features, choose an explicit strategy compatible with deployment:

- ReactWP's cookie login/current-user/logout endpoints for an approved credentialed origin;
- a same-origin backend-for-frontend proxy;
- a project-specific authentication mechanism reviewed as a separate trust boundary.

Do not infer authentication from a cached frontend boolean. Refresh identity from `/auth/me`, handle expiry, and keep authenticated responses out of public caches.

Mutations need authorization and CSRF protection in addition to authentication. Route registration and CORS do not grant business permissions.

## Preview

Preview tokens are created server-side for an authorized post/user and validated by ReactWP's preview endpoint. Prefer the `X-ReactWP-Preview-Token` header. Query-string preview tokens are disabled by default because URLs leak through logs, history, analytics, and referrers.

A headless preview flow should:

1. authorize token creation in WordPress;
2. send a short-lived token to the preview application without persisting it publicly;
3. fetch the preview route with no-store semantics;
4. render a visible preview state;
5. avoid CDN/static caching;
6. expire or discard the token after use.

Load `security-expert` for implementation or review.

## Consumer Cache Strategy

ReactWP public responses and the external framework/CDN may each cache. Define ownership:

- which endpoints are revalidated and for how long;
- whether routes are generated at build time or request time;
- how WordPress edits trigger consumer revalidation/deployment;
- which language/query dimensions form cache keys;
- how 404s and redirects expire;
- how preview/auth requests bypass public caches.

ReactWP render-cache invalidation does not automatically purge an external CDN or framework cache. Connect `rwp_render_cache_invalidated`, save hooks, or a project webhook to the external revalidation system when required. Bound retries and authenticate mutation webhooks.

## Error Contract

Handle:

- `404` with the normalized `NotFound` route when available;
- `400` for invalid local views/arguments;
- `401` for unauthenticated private requests;
- `403` for authenticated but forbidden requests or origin denial;
- `429` for rate limiting;
- `5xx` as transient backend/service failure.

Do not replace all failures with empty content or a 200 response. Preserve status for framework routing, monitoring, and caching.

## Headless Integration Checklist

- The external router owns navigation; WordPress remains the canonical content route source.
- The consumer uses public endpoints, not the private integrated bootstrap shape.
- Template keys and route data have explicit adapters/types.
- Allowed origins and authentication topology are documented per environment.
- Rich HTML has an enforced backend allowlist and an explicit pass-through or transformation rendering path.
- Preview and authenticated data bypass public caches.
- External cache revalidation is connected to WordPress changes.
- Status codes, missing templates, empty fields, and CMS downtime have defined behavior.
