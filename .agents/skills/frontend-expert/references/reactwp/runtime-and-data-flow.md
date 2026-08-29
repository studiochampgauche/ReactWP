---
name: reactwp-runtime-data-flow
description: Detailed ReactWP integrated frontend runtime guide covering bootstrap parsing, route normalization, template registry, route fetching/cache, browser mount/hydration, document metadata, and client navigation lifecycle.
---

# ReactWP Runtime and Data Flow

## When to Use This Reference

Apply when changing shared runtime behavior, route payload consumption, template registration/resolution, navigation, hydration, metadata, or debugging why a route/component receives particular data.

## Runtime Pipeline

```text
WordPress object/request
  -> RouteResolver
  -> RenderStrategy
  -> Bootstrap::payload
  -> Public/private initial render decision
  -> #reactwp-bootstrap JSON + #app HTML
  -> Runtime.js normalization
  -> App.jsx createRoot or hydrateRoot
  -> TemplateRegistry lazy entry
  -> route template props
  -> client navigation through RouteService/useRouteTransition
```

Each layer has one responsibility. Page templates should consume normalized props, not reproduce PHP route resolution or parse bootstrap DOM themselves.

## Bootstrap Payload

`Bootstrap::payload()` provides:

| Key | Contents |
| --- | --- |
| `site` | site name/description/language/locale/home/admin URLs |
| `theme` | active theme name/slug/version |
| `system` | base/admin/REST URLs, cache version, endpoints, nonce when appropriate |
| `assets` | critical fonts/media and deferred media maps |
| `navigation` | normalized WordPress menus |
| `route` | current route content, SEO, render/cache metadata |
| `currentUser` | authenticated marker and permitted identity in integrated context |
| `seoDefaults` | fallback title/description/image |

`header.php` serializes it with `Bootstrap::json()` into the data-only `#reactwp-bootstrap` script. Do not create a second bootstrap global or concatenate markup into that script.

## Route Normalization

`Runtime.js` parses the bootstrap once and normalizes:

- trailing-slash paths;
- query strings and query objects;
- route key as normalized path plus normalized search;
- template default;
- `seo`, `data`, `mediaGroups` defaults;
- render mode and cache defaults;
- boolean 404 state.

The route key includes query state. Code that caches or compares pathname alone can show the wrong query variant.

Use exported helpers when implementing route-aware behavior:

- `normalizePath()`;
- `normalizeSearch()`;
- `searchToQuery()` / `queryToSearch()`;
- `createRouteKey()`;
- `normalizeRoute()`.

Do not invent slightly different slash/query normalization in individual components.

## Runtime Singleton

The exported `runtime` is the initial bootstrap snapshot. Shared stable values such as `site`, `theme`, `system`, assets, and navigation are read from it. The current route after navigation is owned by `useRouteTransition`, not by mutating `runtime.route`.

Templates receive the active route explicitly. Prefer props/context over importing `runtime.route` inside page components, which would remain the initial route.

## Template Registry

`TemplateRegistry` wraps each loader with:

- lazy React component;
- deduplicated pending/resolved module load;
- explicit `preload()`/`load()`;
- render mode/cache metadata;
- asset key.

`initializeTemplateRegistry()` resets defaults and calls `configureTemplateRegistry()`. Browser and server renderer use the same configuration, which keeps render manifests consistent.

Unknown template names resolve to `Default`. `Default` and `NotFound` are always restored by registry reset.

Do not mutate `templateRegistry` directly. Use `registerTemplate()`/`registerTemplates()` from the configuration extension point.

## Initial Mount vs Hydration

`App.jsx` decides:

- `createRoot()` for client source or absent initial HTML;
- `hydrateRoot()` for static/server initial HTML;
- preload initial template before hydration;
- client-render fallback if template preload fails.

The `data-rwp-render` attribute on `#app` records the actual initial source, not merely the requested mode.

Hydrating templates must produce the same first markup in Node and browser. Browser-only conditions belong after commit.

## Route Service

The named `fetchRoute()` export from `RouteService.js`:

- accepts path/string or location-like input;
- normalizes path/search/view/key;
- sends credentials and `X-WP-Nonce` when available;
- calls the configured route endpoint;
- accepts wrapped `{ route }` or direct route data;
- normalizes the result;
- stores it in route memory when payload caching is enabled.

Do not create a second route fetch cache inside page links. `AppLink` and `Loader.prepareRoute()` already prefetch through this service.

When a route sets `render.cache.payload: false`, cached memory must not override a fresh fetch.

## Client Navigation Ownership

`useRouteTransition()` owns:

- current normalized route;
- React Router blocking/proceed lifecycle;
- next-route fetch and critical preload;
- loader/page leave/enter timing;
- scroll lock/top/hash behavior;
- route-ready signal after React commit;
- header/footer keys;
- deferred media start.

Templates should not call `setCurrentRoute`, proceed blockers, pause `window.gscroll`, or mutate `window.loader` for normal navigation.

Use `AppLink`/`Button` so internal navigation enters this lifecycle. `window.location` is reserved for deliberate full WordPress boundaries or failure fallback.

## Route Commit Signal

After the selected template commits, `RouteReadySignal` calls `handleRouteReady(route)`. Loader critical display waits for this signal plus resource readiness before declaring the route loaded.

Do not mark a route ready from a page component unless changing this contract deliberately. A component can manage its own deferred UI without blocking global route readiness.

## Document Metadata

`useDocumentMeta(currentRoute)` either:

- parses allowlisted `route.head` title/meta/link entries; or
- derives title, description, Open Graph, image, and canonical values from route SEO/defaults.

Keep route metadata in WordPress/PHP payloads. Avoid competing per-component `document.title` effects that can race after navigation.

## Shared Props vs Runtime Imports

Prefer template props when the value participates in rendering:

```jsx
const Template = ({ route, site, theme, system, navigation, currentUser }) => {
    // Render from explicit normalized inputs.
};
```

Import a runtime service when calling the service is the actual responsibility (for example `Scroller`, `Loader`, normalization helpers). Avoid importing internal mutable objects simply to shorten prop chains.

## Debugging Flow

When data appears wrong, inspect in order:

1. WordPress object and ACF field configuration.
2. `RouteResolver` output.
3. `RenderStrategy` render/cache metadata.
4. serialized bootstrap or REST `/route` response.
5. `normalizeRoute()` output/key.
6. registry template selection.
7. template adapter and component props.
8. cache state only after earlier values are correct.

This avoids compensating in React for a server payload/schema problem.

## Verification

For runtime changes run `npm run test:render` and the focused tests for the affected service. Exercise initial client mount, static/server hydration, internal navigation, query variants, hashes, back/forward, failure fallback, and rapid navigation.
