---
name: reactwp-templates-and-routing
description: Local guide for ReactWP route payloads, template registration, client/static/server rendering, internal links, document metadata, and safe CMS content. Use for pages, route-aware components, navigation, or hydration work.
---

# ReactWP Templates and Routing

## When to Use This Reference

Apply when adding or editing a route template, consuming WordPress/ACF data, registering render behavior, building navigation, or diagnosing mount/hydration differences.

## Create a Template

Create the route-level component under `src/themes/reactwp/js/templates/` and import its route-specific styles from that dependency graph.

```jsx
import '../../scss/templates/project.scss';
import AppLink from '../components/AppLink';

const SanitizedHtml = ({ html = '', className = '' }) => (
    <div
        className={className}
        dangerouslySetInnerHTML={{ __html: html }}
    />
);

const Project = ({ route, site, navigation }) => {
    const { data = {} } = route;

    return (
        <article className="project">
            <header className="project__hero">
                <h1>{data.hero_title || route.pageName}</h1>
                <SanitizedHtml html={data.hero_intro} className="project__intro" />
            </header>

            <AppLink to="/work/">Back to work</AppLink>
        </article>
    );
};

export default Project;
```

The PHP route resolver passes ACF fields through `route.data` after reserving ReactWP fields such as `react_template`, `seo`, and `media_groups`. In this example, `hero_intro` is an HTML field sanitized by the backend and rendered unchanged. Use `RichText` instead only when React must transform its HTML tree.

## Register the Template

Add it in `js/inc/config/configureTemplateRegistry.js`:

```js
import { registerTemplate } from '../TemplateRegistry';

export const configureTemplateRegistry = () => {
    registerTemplate('Project', {
        loader: () => import('../../templates/Project'),
        render: 'static',
        cache: {
            tags: ['post-type:project']
        }
    });
};
```

WordPress's **React Template** field must contain the same registry name. `Default` and `NotFound` are registered automatically.

Registry requirements:

- names match `^[A-Za-z][A-Za-z0-9_.-]{0,127}$`;
- `loader` returns a dynamic import with a default React export;
- `render` is `client`, `static`, or `server`;
- `assetKey` is needed when registry name and template filename differ;
- cache tags use `namespace:value`-style identifiers accepted by the runtime.

Do not statically import every template into the registry; dynamic imports create route boundaries and feed template asset manifests.

## Template Props

Integrated browser and server renderers supply the same shared props:

| Prop | Purpose |
| --- | --- |
| `route` | normalized current route, render/cache metadata, SEO, and ACF `data` |
| `site` | site name, description, language/locale, home/admin URLs |
| `theme` | active theme name, slug, version |
| `system` | base/admin/REST URLs, cache version, endpoints, safe runtime configuration |
| `navigation` | normalized WordPress menus/navigation groups |
| `currentUser` | public unauthenticated marker or permitted current-user fields |

`App.jsx` currently also has access to runtime assets and SEO defaults, but templates should receive new shared contracts deliberately rather than importing mutable singleton state for ordinary content.

## Route Shape

A normalized route includes:

```js
{
    id,
    type,
    template,
    pageName,
    path,
    search,
    query,
    key,          // normalized path + normalized search
    url,
    lang,
    seo,
    head,
    mediaGroups,
    data,
    render: {
        mode,
        cache: {
            html,
            scope,
            ttl,
            payload,
            media,
            tags
        }
    },
    is404
}
```

Treat fields as optional at component boundaries because older content, previews, and filtered routes can be partial. `normalizeRoute()` guarantees the core object shapes used by the runtime.

Query parameters are part of the route key. Do not cache route content by pathname alone or discard `search` during navigation.

## Render Mode Selection

### Client

Use for browser-only, highly interactive, or migration-stage screens that do not need generated initial HTML. It requires no production Node service.

### Static

Use for stable public marketing/editorial routes. `npm run generate` can create initial HTML, followed by hydration. Plan cache tags/regeneration around content dependencies.

### Server

Use for request-time or personalized routes that benefit from initial HTML. It requires the optional production render service and appropriate cache scope/security configuration.

For all hydrating modes:

- render the same markup on the server and first browser pass;
- use deterministic keys and content;
- defer viewport, storage, pointer, and DOM-dependent decisions to client lifecycle;
- import browser-only modules inside guarded lifecycle code;
- keep essential content outside effects.

## Internal Navigation

Use `AppLink` for internal routes:

```jsx
<AppLink to={`/projects/${project.slug}/`}>
    {project.title}
</AppLink>
```

It preserves React Router navigation, prefetches through `Loader` on hover/focus, handles hashes, validates protocols, and preserves external/target behavior.

Use `data-router="false"` for a same-origin destination that must force a full WordPress navigation, such as `/wp-admin/`:

```jsx
<Button href="/wp-admin/" data-router="false">
    Open WordPress Admin
</Button>
```

Do not call `window.location` for normal internal page changes; it bypasses the route loader and transition lifecycle. Hard navigation is an error fallback or an explicit boundary.

Hash-only links are not new route payloads. Use `AppLink` so the existing Scroller resolves and scrolls to the target.

## Navigation Data

Navigation items are normalized with fields such as:

```js
{
    id,
    label,
    title,
    url,
    path,
    target,
    classes,
    children
}
```

Render nested data recursively with a depth appropriate to the component. Use stable IDs/paths as keys and set `aria-current="page"` after comparing normalized route paths. Do not assume every menu item is internal or has children.

## Rich Content and DOM Props

- For WordPress HTML already sanitized by the backend and requiring no React-level changes, render it through a small explicit `dangerouslySetInnerHTML` boundary.
- Use `RichText` or direct `html-react-parser` only when the component actually transforms the tree with `replace` or `transform`. The parser does not sanitize input; keep the backend HTML allowlist regardless of the rendering path.
- Use `sanitizeDomProps` in flexible primitives before spreading caller props onto a DOM element.
- Constrain heading tags with `normalizeHeadingTag`.
- Normalize URLs through existing link/button primitives rather than concatenating untrusted protocols.
- Keep raw CMS values out of inline CSS unless they are mapped to an explicit allowlist of design tokens/variants.

## Metadata

`useDocumentMeta(currentRoute)` updates document metadata from the route. Put page SEO in WordPress/route fields rather than calling `document.title` from visual components.

When a new route content field affects metadata, extend the route/metadata contract in one place and verify client navigation plus generated/server HTML.

## Error and Empty Behavior

- Unknown template names resolve to `Default`; WordPress 404 routes should use `NotFound`.
- Do not let a missing optional ACF group crash the whole route. Normalize arrays/objects and omit empty optional sections.
- Required content should fail visibly during development rather than silently substituting generic marketing copy in production.
- Link/media items with unsafe or incomplete destinations should render a non-link fallback or be omitted according to product semantics.
- A route fetch failure currently falls back to full browser navigation; preserve a recoverable path when changing transition logic.

## Template Checklist

- Template file has a default export and is dynamically registered.
- Registry name matches the WordPress field; `assetKey` matches the file when needed.
- Render mode matches content and deployment requirements.
- Route data is normalized and optional sections handle missing/long content.
- Initial markup is deterministic and valid for hydration.
- Internal links use `AppLink`/`Button`; full navigations are explicit.
- Rich HTML follows its backend sanitization contract and an explicit rendering boundary; flexible DOM props pass through `sanitizeDomProps`.
- Page-specific styles/assets stay in the template dependency boundary.
- The route works on initial request, client navigation, back/forward navigation, and relevant query/hash variants.
