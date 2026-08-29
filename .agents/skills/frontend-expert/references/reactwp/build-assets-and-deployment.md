---
name: reactwp-build-assets-deployment
description: ReactWP frontend build and asset pipeline guide covering configs commands, Webpack entries/chunks/manifests, SCSS, media optimization, render assets, src/dist boundaries, production reports, and deployment output.
---

# ReactWP Build, Assets, and Deployment

## When to Use This Reference

Apply when adding dependencies/assets, changing imports, template chunks, SCSS/media processing, Webpack/config scripts, production optimization, generated manifests, or deployment contents.

## Source and Output Boundary

```text
configs/   authored Node tooling, dependencies, Webpack, scripts, tests
src/       authored PHP, React, SCSS, media, WordPress configuration
dist/      generated runnable WordPress document root and assets
```

Edit `src/` and `configs/`; regenerate `dist/`. A manual fix in `dist/` will be overwritten and leaves source/deploy builds inconsistent.

Run npm commands from `configs/`.

## Primary Commands

| Command | Purpose |
| --- | --- |
| `npm run get:core` | download/verify WordPress and choose ACF edition |
| `npm run build` | readable development build for all targets |
| `npm run watch` | watch theme, plugins, mu-plugins, styles, render assets |
| `npm run build:themes` | theme JS and CSS development build |
| `npm run build:themes:js` | Webpack theme client bundle only |
| `npm run build:themes:css` | Sass/theme media development build only |
| `npm run build:render` | universal renderer development build |
| `npm run watch:render` | watch the universal renderer development build |
| `npm run prod` | complete optimized production build/report/render pipeline |
| `npm run prod:themes` | optimized theme JS/CSS assets |
| `npm run generate` | static route generation from configured WordPress origin |
| `npm run serve:ssr` | optional built Node SSR service |
| `npm run report:themes` | bundle-size report |

Use the narrowest command that verifies the changed layer; use complete production build when manifests/optimization/deployment behavior changes.

## Theme JavaScript Entry

Webpack builds the ReactWP theme application from source and resolves `.js`, `.jsx`, `.scss`, and `.css`. Shared aliases currently include:

- `@theme` -> integrated theme JavaScript root;
- `@runtime` -> PHP runtime source path for build integrations that need it.

Prefer project-relative imports already used by the source unless an alias materially improves a repeated deep dependency. Do not introduce an alias for one import.

## Template Chunks

Dynamic imports in `configureTemplateRegistry.js` create route template boundaries:

```js
registerTemplate('Project', {
    loader: () => import('../../templates/Project'),
    render: 'static'
});
```

Webpack records scripts/styles associated with modules under `js/templates/` in `assets/render/template-assets.json`. `assetKey` connects a registry name to the template filename when they differ.

Consequences:

- keep page-specific libraries/styles inside the template dependency graph;
- avoid static imports of all templates in a central file;
- verify asset key/name when renaming/moving a template;
- shared imports can legitimately move into common chunks;
- server and client registry configuration must remain aligned.

## Entrypoint Manifest

The build emits theme entrypoint metadata used by PHP to enqueue the correct development/production script/style files. PHP validates manifest asset paths/extensions and falls back to conventional bundle names.

Do not hardcode hashed/chunk filenames in PHP templates. Change the manifest/build contract when a new asset category is genuinely required.

## SCSS Pipeline

`scss/default.scss` is the theme's main Sass input. It currently imports the foundation layer from `scss/inc/`.

- Put global reset, tokens, font-face, and shared foundations in `scss/inc/` and the default entry.
- Import page-specific SCSS from the owning lazy template/component graph when it should follow the route chunk.
- Keep URLs relative to generated asset locations and existing path variables.
- Avoid global selectors that accidentally style parsed CMS HTML or WordPress/admin markup outside the intended component.
- Build warnings/errors should be resolved in source, not hidden by editing compiled CSS.

Development output is expanded/readable; production CSS is compressed and precompressed.

## Theme Media Pipeline

Directories under `src/themes/reactwp/medias/` are copied to matching theme asset roots:

- `images`;
- `videos`;
- `audios`;
- `fonts`;
- `others`.

In production, supported raster images are re-encoded/optimized when the optimized output is smaller; SVG files are processed with SVGO while preserving `viewBox`. CSS/JS receive Brotli and gzip siblings.

The pipeline does not choose semantic element, responsive dimensions, crop, alt text, poster, preload priority, or WordPress attachment strategy. Those remain template/component responsibilities.

Avoid committing huge source media on the assumption production optimization will make every asset appropriate. Resize/export source to realistic maximums and use responsive media.

## Server Render Build

`build:render` creates the universal template renderer and server runtime artifacts. It must include the same template registry and shared render-compatible components as the browser.

Rebuild/render-test after:

- adding/renaming a template;
- changing registry render/cache metadata;
- changing universal shared components;
- changing parser/HTML behavior;
- modifying server runtime/config.

Browser-only modules must not enter server execution paths at module evaluation/render time.

## Development Watchers

`npm run watch` coordinates target watchers. Use the narrower watcher while focusing on one layer to reduce noise, but remember that a template can involve JS, SCSS, media, and render assets.

`watch:themes:css` copies theme media once when it starts and then watches Sass. Adding/changing media after the watcher has started requires rerunning the watcher/build; it is not a live media-directory watcher.

Watch success is not production verification: production mode changes minification, chunk names, compression, reports, media optimization, and source-map behavior.

## Production Output

`npm run prod`:

- builds WordPress targets;
- minifies/splits theme assets;
- removes stale chunks;
- writes manifests;
- produces gzip/Brotli siblings;
- optimizes theme media;
- reports/validates bundle sizes;
- builds universal renderer;
- can generate static routes when configured.

Inspect warnings and reports. A build that succeeds with a major route/shared chunk regression is not complete.

## Dependencies

Dependencies belong in `configs/package.json` and lockfile. Before adding one:

- confirm browser/platform/ReactWP/GSAP does not already cover the need;
- check whether it is browser-only or SSR-safe;
- import it only in the narrowest chunk;
- assess bundle/runtime cost;
- review maintenance/license/security;
- update/install through npm so lockfile remains consistent.

`html-react-parser` is available for components that actually use `replace` or `transform`; it is not the default requirement for every HTML string. An unchanged trusted-and-sanitized HTML field may use `dangerouslySetInnerHTML` without another parser dependency. Keep either raw-HTML contract identical across client, static, and server rendering.

## Deployment

Deploy `dist/` as WordPress document root. Do not deploy `src/`, `configs/`, development source maps, environment files, credentials, private archives, or local build caches.

Client/static routes need ordinary PHP WordPress hosting. Server-render templates additionally need the built SSR service with shared secret/private networking. Static generation/runtime fragments need correct web-server deny rules.

Generated Apache/IIS protections do not configure Nginx. Copy the required deny rules from `SECURITY.md` into the real Nginx configuration and verify them.

For deployment security, secrets, ownership, headers, and server topology, load `security-expert`.

## Verification Checklist

- Does the narrow development build pass?
- Do template dynamic imports still create intended chunks/assets?
- Does the template asset manifest contain renamed/new templates?
- Do global/template styles load on initial request and client navigation?
- Do fonts/media resolve from generated paths?
- Does server renderer build/hydrate when affected?
- Does production build complete without unexpected stale/large shared chunks?
- Are only intended generated files deployed?
- Are static/render/private artifacts denied by the actual server?
