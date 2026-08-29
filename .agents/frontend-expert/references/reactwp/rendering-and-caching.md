---
name: reactwp-rendering-and-caching
description: ReactWP guide for client, static, and server rendering; initial render fallback; hydration; render configuration; public/private HTML caches; payload/media persistence; tags; invalidation; and static regeneration.
---

# ReactWP Rendering and Caching

## When to Use This Reference

Apply when choosing/changing template render mode, cache policy, tags, TTL, query behavior, generation, SSR deployment, or diagnosing stale/cross-context output. For security-sensitive scope/identity decisions, also load `security-expert`.

## Rendering Is Per Template

The template registry configures initial rendering independently for each route template:

```js
registerTemplate('Project', {
    loader: () => import('../../templates/Project'),
    render: 'static',
    cache: {
        tags: ['post-type:project']
    }
});
```

WordPress `RenderStrategy` combines registry metadata, route/object context, fields, and filters into the normalized route render contract.

## Modes

### Client

- Initial `#app` markup is browser-created.
- No production Node renderer is required.
- Appropriate for existing/highly interactive routes or when generated HTML provides little value.
- Still benefits from route-level lazy templates, loader/media strategy, and client payload cache rules.

### Static

- HTML is generated ahead of time and hydrated in the browser.
- No Node service is required during ordinary production requests once fragments exist.
- Strong fit for public editorial/marketing/service content.
- Requires generation/invalidation discipline.

### Server

- Optional Node renderer creates request-time HTML and browser hydrates it.
- Fit for request-time/personalized content that needs initial HTML.
- Requires renderer service, secret, network/service supervision, timeout/fallback, and cache-scope decisions.

`client` remains the fallback when static/server output is absent, invalid, unavailable, or unsafe to use.

## Requested Mode vs Actual Source

A route may request `static` or `server` but receive client output when a fragment/service is unavailable. `header.php` stores both requested mode and actual initial source; `#app[data-rwp-render]` drives hydration selection.

Components must not assume that a `static` registry setting guarantees pre-rendered HTML on every request. The route remains functional in client fallback.

## Universal Template Rules

Static/server templates share code with the browser. During render:

- no `window`, `document`, storage, viewport, media-query, pointer, random, or current-time branching;
- deterministic content/order/keys;
- valid HTML nesting;
- the same sanitized raw-HTML source and, when used, the same parser transformations;
- no effect-dependent essential content;
- browser-only modules loaded after commit or safely guarded.

Effects and GSAP do not run in server rendering. Their initial CSS/markup state must remain visible/usable without them.

## Render Cache Shape

Normalized route cache configuration contains:

```js
render: {
    mode: 'client' | 'static' | 'server',
    cache: {
        html: Boolean,
        scope: 'public' | 'private',
        ttl: Number,
        payload: Boolean,
        media: Boolean,
        tags: Array<String>
    }
}
```

Each field controls a different layer:

- `html`: persistent SSR HTML caching eligibility;
- `scope`: whether HTML can be shared anonymously or requires identity partition;
- `ttl`: HTML cache lifetime;
- `payload`: browser/route-memory payload persistence;
- `media`: browser media persistence;
- `tags`: invalidation dependencies.

Do not use one flag as shorthand for all caching.

## Public vs Private

`public` means the rendered HTML is safe and identical to share across anonymous visitors. Client and static routes default to `public`; server routes default to `private`, which requires a non-empty identity for persistent SSR entries.

Unless explicitly overridden, client routes use `html: false`, `payload: true`, and `media: true`; static routes use `html: true`, `payload: true`, and `media: true`; server routes use `html: false`, `payload: false`, and `media: false`. All three modes default to `ttl: 0`. The payload/media defaults follow the normalized public/private scope rather than the mode name independently.

Logged-in output is not served from public HTML cache and receives no-cache response behavior. Private browser payload/media persistence defaults off.

Choose scope from data behavior, not desired hit rate. Account, preview, cart, personalization, user roles, request tokens, consent state, and location/experiment content are not public by default.

## Cache Identity

For logged-in WordPress users, private SSR identity defaults to the user ID. An external/project principal needs a reviewed stable identity through the filter if persistent private SSR caching is desired.

An empty identity disables persistence. Do not replace it with a shared catch-all value merely to enable cache.

## Query Parameters

Route payload keys include normalized search, so client route variants remain distinct.

Persistent SSR HTML with query parameters is denied unless every query key is explicitly allowlisted through `rwp_ssr_cache_query_keys`. Values are canonicalized/bounded before key creation.

Allowlist only keys that:

- affect deterministic render output;
- have bounded values/cardinality;
- are safe within the chosen cache scope;
- cannot be omitted from the cache identity without changing content.

## Tags

The server renderer supplies default tags such as global render/menu/settings, template, and route post identifiers, then combines registry and route tags.

Use tags for content dependencies, for example:

```js
cache: {
    tags: [
        'post-type:project',
        'settings:theme'
    ]
}
```

Tags follow the normalized `namespace:value` pattern. Add only real dependencies; broad tags simplify correctness but invalidate more content.

If a project component fetches additional server data during render, its invalidation dependency must be represented or the cache may remain stale.

## Global Invalidation

The ReactWP cache action/version bump invalidates:

- public/private SSR fragments;
- static/runtime fragments through generation state;
- browser JSON/media caches;
- script/style asset versions used by visitors.

Global invalidation is a safe operational tool, not a substitute for accurate tags/TTL on frequently changing content.

## Static Generation

`npm run generate` obtains public bootstrap/sitemap/routes and creates static route fragments/manifests within configured bounds.

- Configure `RWP_SITE_URL` for the intended WordPress origin.
- Generate only public deterministic routes.
- Keep output inside the project by default.
- Regenerate/invalidate after relevant content/template/asset changes.
- Deploy generated protection files and configure equivalent Nginx denies.

Runtime static regeneration uses protected uploads storage and atomic writes. Preserve path/manifest limits when extending it.

## SSR Service

`npm run serve:ssr` starts the optional renderer from built assets. Production PHP and Node share `RWP_SSR_SECRET`.

- Prefer loopback/private network.
- Keep client fallback.
- Bound render work and avoid remote/unbounded code in templates.
- Monitor failures/circuit breaker without exposing upstream details.
- Build render assets whenever shared templates/registry change.

For deployment/security details load `security-expert`.

## Hydration Failures

Common causes:

- browser-only render branches;
- invalid nesting repaired differently by browser;
- random/time-generated values;
- different locale/data snapshot;
- unstable keys/order;
- parser/HTML policy differences;
- template module/asset mismatch;
- initial DOM changed before hydration.

ReactWP can recover/fallback, but a recoverable warning is still a defect to investigate rather than suppress.

## Mode Selection Checklist

- Is content public and stable enough for static?
- Does request-time/personalized initial HTML justify the SSR service?
- Is client fallback complete?
- Is first markup universal/deterministic?
- Are public/private scope and identity correct?
- Are payload/media persistence settings safe for the data?
- Are query dimensions and invalidation tags complete?
- Can deployment generate/serve/protect the required artifacts?

## Verification

Run from `configs/`:

```powershell
npm run build:render
npm run test:render
npm run generate
npm run serve:ssr
```

Use only the commands relevant to the configured environment; generation/SSR service can require a reachable local site/configuration. Verify initial source, hydration, client fallback, cache hit/miss, invalidation, logged-in/anonymous separation, and query variants.
