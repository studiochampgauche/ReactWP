---
name: backend-expert-integrated-react-theme
description: Backend-to-frontend integration guidance for the bundled ReactWP theme, including bootstrap, route payloads, template selection, rendering modes, navigation, media, metadata, and rich ACF content.
---

# Integrated React Theme

## Integrated Request Lifecycle

In integrated mode, WordPress owns the initial URL and ReactWP owns the application shell:

```text
WordPress request
  -> Bootstrap::payload()
  -> InitialRender::resolve()
  -> TemplateAssets::enqueue()
  -> #reactwp-bootstrap JSON + optional initial HTML
  -> React runtime
  -> TemplateRegistry component
  -> RouteService for internal navigation
```

The same route contract must work for initial PHP output, client navigation, static rendering, and server rendering. Avoid backend code that depends on only one of those call paths.

## Connect a Content Model to a Template

For a page/project template:

1. create or reuse an ACF group with a stable schema and `show_in_rest => 1`;
2. ensure the content type is enabled in ReactWP's React Template settings;
3. set the entry's `react_template` value, or derive a stable default by post type;
4. register the exact template key in `configureTemplateRegistry.js`;
5. consume the resulting `route.data` contract;
6. choose `client`, `static`, or `server` and define cache dependencies;
7. build themes and exercise initial load plus internal navigation.

Backend field:

```text
react_template = ProjectDetail
```

Frontend registry:

```js
registerTemplate('ProjectDetail', {
    loader: () => import('../../templates/ProjectDetail'),
    render: 'static',
    cache: {
        tags: ['post-type:project']
    }
});
```

Names are case-sensitive and `PublicPayload` restricts them to a safe identifier pattern. A missing registration falls back according to the frontend registry behavior; treat that as a defect rather than relying on it silently.

## Template Props

ReactWP templates receive shared values such as:

```text
route, site, theme, system, navigation
```

The backend should keep page data in `route.data`. The template should not fetch the same page ACF data again on mount; that creates duplicate requests, inconsistent SSG/SSR output, and loading flashes.

Use a focused client request only for independently changing or user-triggered resources, such as filtering a large collection, submitting a form, or loading private account state. Give that resource its own endpoint, state lifecycle, and cache rules.

## Rich Content Contract

Classify every string field:

- plain text: render as JSX text;
- rich editor/WYSIWYG HTML: sanitize on the backend, then use an explicit unchanged-HTML boundary or `RichText` according to whether React-level transformation is required;
- URL: pass through URL-aware components such as `AppLink`/`Button`;
- structured content: render from typed arrays/objects, not encoded HTML.

For sanitized HTML that needs no React-level transformation, use a small explicit `dangerouslySetInnerHTML` boundary. Use `RichText`/`html-react-parser` when `replace` or `transform` is actually required. ReactWP's current `RichText` qualifies because it removes unsupported nodes, changes attributes, validates URL-bearing values, and rebuilds the React tree. Neither rendering mechanism replaces backend sanitization.

Keep HTML canonical in ACF/WordPress rather than pre-escaping it for JSON. Do not mix rich HTML and plain text in the same field depending on the entry.

## Initial Render Modes

Template registry configuration provides the default render mode. ReactWP then merges runtime filters, route configuration, and ACF per-object render fields according to `RenderStrategy`.

- `client`: no server-produced app HTML; simplest for interactive/private flows.
- `static`: generated HTML hydrated by React; appropriate for public deterministic content.
- `server`: request-time rendered HTML hydrated by React; appropriate when request context is required and a Node renderer is deployed.

All shared templates must render deterministically without browser globals. Browser-only work belongs in effects/runtime lifecycle. Backend filters used during rendering must avoid non-deterministic values, unbounded remote calls, and private data in public scope.

The mode requested by configuration may differ from the actual initial source if an artifact/service is unavailable. The PHP shell records both in `system.initialRender`; debug actual source before blaming hydration.

## Navigation

Initial navigation comes from `MenuBuilder::all()`. Internal links are intercepted by the integrated router, which requests `/reactwp/v1/route?view=...`, loads the required template chunk, coordinates media/loader/transitions, and commits the route.

Backend obligations:

- return canonical local `path`/`url` values;
- keep menu targets/external links explicit;
- ensure route payloads resolve consistently on direct request and REST navigation;
- preserve query parameters that are part of the contract;
- invalidate navigation/render output when custom navigation dependencies change.

Do not add a second PHP route endpoint for a normal page transition.

## Metadata and Head

Route SEO lives in `route.seo`; final head entries are collected through `rwp_wp_head` and applied for initial/client routes. Keep titles/descriptions deterministic and available from the route itself.

When adding structured data or head tags, produce a stable bounded contract and validate it in both direct load and navigation. Dynamic markup needs context-aware escaping and `security-expert` review.

## Media and Loading Hints

The backend may provide `media_groups` and critical media/font filters. These values participate in ReactWP's loader/media orchestration; they are not arbitrary CSS classes. Reuse the existing group contract and verify missing media, cached revisits, internal navigation, and reduced-data behavior when changing them.

ACF image fields should expose predictable attachment metadata needed by the `Image`, `Video`, or `Audio` component. Avoid sending original WordPress attachment objects with irrelevant private metadata.

## Adding Derived Backend Data

Use `rwp_route_payload` for small route-owned derived values. Keep the computation valid for:

- an initial WordPress request;
- the public route REST endpoint;
- build-time static generation;
- server rendering;
- authenticated preview when supported.

If a value depends on another post, term, menu, option, or remote record, attach matching cache tags and invalidate those tags when the dependency changes.

## Verification Matrix

For an integrated backend/template contract, verify:

- direct anonymous request;
- hard refresh on the route;
- internal navigation from and to the route;
- 404 and missing optional fields;
- logged-in preview if supported;
- intended render source (`client`, generated static, or SSR);
- hydration without warnings;
- stale-cache behavior after editing ACF content;
- rich content from the intended sanitized backend contract, using pass-through HTML or parser transformations as designed;
- theme build with `npm run build:themes` and relevant render tests.

Load `frontend-expert` for the React/SCSS implementation and `security-expert` for private data, HTML, URLs, previews, or mutations.
