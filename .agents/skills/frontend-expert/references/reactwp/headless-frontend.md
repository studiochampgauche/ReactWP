---
name: reactwp-headless-frontend
description: Guidance for building an external frontend against ReactWP's public/headless API, including bootstrap/route/navigation/settings/sitemap contracts, routing, previews, authentication, errors, caching, and framework integration.
---

# ReactWP Headless Frontend

## When to Use This Reference

Apply when ReactWP is the WordPress content/admin backend and an external React, Vue, Svelte, Astro, or other application owns rendering and navigation.

For GSAP in a non-React framework, also load `gsap/frameworks.md`. For authentication, CORS, preview tokens, or sensitive fields, load `security-expert`.

## Endpoint Map

ReactWP exposes the `reactwp/v1` contract:

| Endpoint | Purpose |
| --- | --- |
| `/bootstrap` | public site/theme/system/assets/navigation plus resolved route/defaults |
| `/route?view=/path/` | normalized public route for a local view |
| `/navigation` | normalized menu locations/items |
| `/settings` | explicitly filtered public settings |
| `/sitemap` | public route inventory |
| `/preview` | token-authorized draft/private preview route |
| `/auth/me` | no-store current authenticated identity/REST nonce |
| `/auth/login` | credentialed JSON login |
| `/auth/logout` | credentialed logout with REST nonce when logged in |

Endpoint URLs are available under both `system.headless` (`routeEndpoint`, etc.) and the compatibility `system.endpoints` map (`route`, etc.) in bootstrap data. Public bootstrap does not expose `currentUser` or a REST nonce.

`bootstrap` accepts optional `view`/`lang` and defaults to the front page. `route` requires a safe local `view`, returns HTTP 404 for a normalized 404 route, and uses the localized path rather than a separate `lang` switch. Navigation accepts optional `location`/`lang`; settings and sitemap accept `lang`; preview accepts `postId` or `id`, `lang`, and GET/POST.

## Start with Bootstrap or Route

For a full SPA shell, bootstrap can provide shared configuration/navigation plus the initial route. For a statically generated consumer, use sitemap plus individual routes. For a route-only integration:

```js
const endpoint = new URL('/wp-json/reactwp/v1/route', cmsOrigin);
endpoint.searchParams.set('view', '/about/');

const response = await fetch(endpoint, {
    headers: {
        Accept: 'application/json'
    }
});

if(!response.ok && response.status !== 404){
    throw new Error(`Route request failed: ${response.status}`);
}

const payload = await response.json();
const route = payload.route;
```

Treat 404 as a renderable route state when a normalized route payload is present.

## Route Contract

External consumers receive the same core route fields described in `templates-and-routing.md`: template, path/search/query, title/page name, SEO/head, media groups, ACF `data`, render/cache metadata, and 404 state.

Do not couple components directly to every raw ACF key across the application. Adapt each route/template payload into consumer component props at a route boundary.

## Template Mapping

ReactWP's `route.template` is a content-to-view identifier. The external frontend owns its component mapping:

```js
const templates = {
    Default: DefaultPage,
    Project: ProjectPage,
    NotFound: NotFoundPage
};

const Page = templates[route.template] || templates.Default;
```

Lazy-load route views where the consumer framework supports it. Keep a deterministic fallback for unknown/older template names.

The integrated ReactWP template registry/render mode does not automatically control an external application's SSR/SSG mode. The consumer chooses its own rendering architecture while respecting route cache/privacy semantics.

## External Routing

Normalize local paths and preserve search parameters when requesting ReactWP routes. The consumer router owns:

- link interception;
- history/back/forward;
- pending/error/404 states;
- scroll/hash restoration;
- route request cancellation/stale response protection;
- metadata update;
- optional prefetch/cache.

Do not fetch arbitrary absolute `view` URLs; ReactWP expects a safe local view path. Keep CMS origin separate from the public frontend origin.

## Navigation

Menu items include label/title/url/path/target/classes/children. Decide internal vs external by normalized origin/path rather than assuming every WordPress menu URL belongs to the frontend.

- Map CMS-local paths to consumer router links.
- Preserve external/target/download behavior.
- Render stable nested IDs/keys.
- Mark current page semantically.
- Handle menu URLs that intentionally lead to WordPress admin/login or another domain.

## Rich Content

React automatically encodes normal text. For an HTML field already sanitized by the backend and requiring no transformation, use a small explicit `dangerouslySetInnerHTML` boundary. Use `html-react-parser` only when `replace` or `transform` is required to map links, media, attributes, nodes, or components.

Neither path sanitizes the source. A non-React consumer needs an equivalent trusted/sanitized HTML contract and should introduce a DOM/AST parser only when its framework also needs node-level transformation.

## Metadata

Route SEO/head data can populate the consumer framework's supported metadata API. ReactWP's integrated `useDocumentMeta` allowlist does not run in an external frontend.

The consumer must:

- allow only supported title/meta/canonical/icon/alternate nodes;
- reject scripts/styles/base/refresh/arbitrary HTML;
- validate URL protocols;
- avoid duplicate tags across navigation/SSR;
- generate matching server/client metadata when hydrating.

## Public Caching

Public route/bootstrap/navigation/settings/sitemap responses can be cached according to response headers and product freshness. Respect:

- public vs authenticated/preview context;
- `no-store` on identity/preview responses;
- query variants;
- CMS invalidation/deployment strategy;
- ReactWP API version metadata.

Do not persist credentials, REST nonces, current-user, or preview payloads in shared/static/public browser caches.

## Authentication

Built-in headless auth uses WordPress cookies, credentialed exact-origin CORS, HTTPS, and JSON login.

Consumer requests use `credentials: 'include'` and the configured exact allowed origin. Obtain current identity through `/auth/me`; use its REST nonce for authenticated logout and applicable WordPress cookie-authenticated mutations.

Authentication UX must handle:

- generic login failure;
- throttling (`429`);
- expired session/nonce;
- unavailable HTTPS/origin configuration;
- no-store identity state;
- logout across tabs/routes.

Do not infer authentication from a cached boolean alone. For full security rules load `security-expert`.

## Preview

An authorized WordPress user issues a post-bound token. Send it in `X-ReactWP-Preview-Token` or Bearer authorization to `/preview`.

The consumer should isolate preview state:

- no public/static/shared cache;
- no token in URL/log/analytics;
- clear preview indicator;
- token expiry/error handling;
- no accidental navigation from preview into cached public data while preserving privileged state.

## Error Handling

Distinguish:

- `400` invalid local view/input;
- `401/403` authentication/origin/permission/preview failure;
- `404` public route not found;
- `429` public/auth rate limit;
- `5xx` backend/transient failure.

Avoid infinite retries. Preserve the current screen during a recoverable navigation error and offer deliberate retry/fallback.

## Consumer Architecture Checklist

- Is CMS origin/configuration separate from public frontend routing?
- Are route/template payloads adapted at a clear boundary?
- Are shared navigation/settings fetched once or invalidated deliberately?
- Are HTML/head/URL sinks independently allowlisted in the consumer?
- Are router cancellation, errors, 404, hashes, and metadata complete?
- Are public/auth/preview caches strictly separated?
- Does SSR/SSG avoid authenticated or preview data?
- Are CORS origins, credentials, cookies, TLS, and REST nonce behavior tested on the real topology?
