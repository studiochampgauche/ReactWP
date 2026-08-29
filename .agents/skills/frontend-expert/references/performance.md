---
name: frontend-performance
description: ReactWP frontend performance guidance for rendering modes, Core Web Vitals, React and SCSS architecture, media, fonts, caching, loading, and motion. Use when creating or reviewing templates, assets, route loading, animation, or bundle behavior.
---

# Frontend Performance

## When to Use This Reference

Apply when a frontend task changes page structure, route templates, images/video/fonts, loading behavior, animation, third-party code, or rendering mode. For GSAP-specific optimization, also read `gsap/performance.md`; this reference covers the wider ReactWP system.

Optimize from measurements and architectural leverage. Do not trade away accessibility, content, or correctness for a synthetic score.

## Performance Model

Prioritize the experience represented by Core Web Vitals:

- **LCP:** make the primary above-the-fold content discoverable and renderable early.
- **INP:** keep event work small, avoid main-thread monopolies, and respond immediately to input.
- **CLS:** reserve space for media, fonts, embeds, loaders, and dynamic CMS content.

Also watch initial JavaScript, route chunk size, long tasks, memory retained across navigation, image bytes, and animation smoothness on representative mobile hardware.

## Choose the ReactWP Render Mode Deliberately

Each template registry entry selects `client`, `static`, or `server`:

| Mode | Prefer when | Main cost/risk |
| --- | --- | --- |
| `client` | highly interactive screens or projects that do not need generated initial HTML | slower meaningful first render and greater dependence on JavaScript |
| `static` | public marketing, editorial, service, and other build-time-known routes | regeneration discipline and stale content if invalidation is incomplete |
| `server` | personalized or request-time content that needs initial HTML | production Node service, request latency, and cache/security complexity |

Do not select `server` merely because it sounds faster. Prefer `static` for stable public routes and keep `client` when the operational cost of SSR offers no user benefit. A template shared by all modes must render deterministically on the server and in the browser.

## Template and Bundle Boundaries

- Register templates with dynamic imports in `configureTemplateRegistry.js`; this preserves route-level code splitting.
- Keep page-specific libraries and SCSS inside the page template or its lazy dependency graph rather than importing them into `App.jsx` or a global component.
- Keep the registry `assetKey` aligned with the template filename when they differ so template asset discovery remains correct.
- Avoid broad barrel imports that pull every template or heavy component into the main entry.
- Before adding a dependency, check whether the platform, ReactWP runtime, CSS, or GSAP already provides the behavior.
- Reuse runtime services. A second router, data cache, smooth-scroll library, media preloader, or animation engine increases cost and creates conflicting lifecycles.

Example route boundary:

```js
registerTemplate('CaseStudy', {
    loader: () => import('../../templates/CaseStudy'),
    render: 'static',
    cache: {
        tags: ['post-type:case-study']
    }
});
```

## React Runtime Cost

- Keep transient visual state local; do not place pointer position, scroll progress, or animation frames in React state.
- Avoid effects that derive values already computable during render.
- Attach global listeners once, use passive listeners where appropriate, and remove them on cleanup.
- Scope observers and animations to their component and disconnect/revert them on unmount.
- Do not add memoization everywhere. Use it for demonstrated expensive recalculation or referential stability that prevents meaningful child work.
- Virtualize genuinely large repeated lists, but avoid virtualization for short editorial grids where it harms semantics and find-in-page.
- Keep CMS payload transformations linear and outside hot render loops where possible.
- Avoid hydrating different markup than the server rendered. Browser-only conditions must not alter the first render tree.

## Route Loading and Caches

ReactWP already coordinates template, font, critical media, deferred media, route payload, and display promises through `Loader`.

- Use route `mediaGroups` and the existing critical/deferred asset maps instead of creating an unrelated page preloader.
- Keep the server-emitted loader shell and its initial hidden/visible states in critical first-paint CSS. The cover can prevent an incomplete client route from becoming visible, but it cannot repair a late/missing stylesheet, nondeterministic hydration, or unstable dimensions.
- Put only genuinely above-the-fold assets in critical groups. Excessive critical media delays every transition.
- Let deferred groups begin or display after the critical path. Do not wait for off-screen galleries before revealing the route.
- Respect `route.render.cache.payload` and `route.render.cache.media`; do not force persistence when the route contract disables it.
- Preserve route-key normalization (`path + normalized search`) so query variants do not collide.
- Prefetch on intentional user signals such as link hover/focus through `AppLink`; do not eagerly fetch every possible route on initial load.
- Keep loader animation duration subordinate to resource readiness. Never add a fixed delay whose only purpose is to make the loader noticeable.

## Images and Responsive Media

- Supply intrinsic dimensions or `aspect-ratio` to prevent CLS.
- Use the smallest source that is sharp at the rendered size. Provide responsive `srcset` and `sizes` for editorial images when WordPress data supports them.
- Prefer AVIF or WebP for photographic content when the pipeline/browser support is appropriate; keep SVG for suitable vector artwork.
- Do not ship a desktop hero image to narrow mobile layouts if an art-directed crop materially reduces bytes and improves composition.
- Use `loading="eager"` and high fetch priority only for the likely LCP image. Lazy-load below-the-fold media.
- Avoid CSS background images for meaningful content or likely LCP media because they are discovered later and lack responsive semantics.
- For autoplay decorative video, supply an image fallback/poster, omit audio, keep the file short, and avoid loading it on constrained layouts when it adds little value.
- The production theme style pipeline optimizes copied raster/SVG media, but it cannot choose the correct dimensions, crop, loading priority, or semantic element for the page.

## Fonts

- Use the fewest families, weights, and styles the design actually needs.
- Prefer WOFF2 subsets appropriate to the site's languages.
- Define `font-display` intentionally; avoid invisible text during a slow font request.
- Reserve compatible metrics with fallback fonts when a type swap shifts layout.
- Add only above-the-fold faces to ReactWP critical font groups. Do not preload every weight.
- When loader copy uses a project webfont, hide it in server-emitted critical CSS before first paint, then reveal it only after the exact shorthand and sample text pass `document.fonts.load()` plus `document.fonts.check()`. Keep it hidden on failure while allowing the route to continue; never expose a fallback-font flash or wait on `document.fonts.ready` for unrelated faces.
- Typography can create more perceived performance than an elaborate loader: render readable content early and stabilize line breaks.

## CSS and Layout

- Keep base/reset and global tokens in the main SCSS entry; keep template-specific rules with the lazy template when the build can extract them.
- Prefer classes and custom properties over repeated inline style objects for static styling.
- Avoid selectors tied to deep DOM nesting; they make components brittle and increase style invalidation work.
- Use `content-visibility` cautiously for large below-the-fold regions and provide an intrinsic-size estimate when it prevents a layout jump.
- Avoid repeated synchronous layout reads after writes. Batch measurements, then mutations.
- Reserve `will-change` for exact elements about to animate and remove it when no longer useful. Permanent layer promotion wastes memory. For ScrollTrigger pins, determine fixed versus transform pinning first: a transform-driven pin may benefit from `will-change: transform` on its exact moving layer, while the same property on an ancestor can break a fixed pin.

## Motion and Scroll

- Animate transforms and opacity where possible; layout properties can trigger reflow and paint.
- Use a single GSAP ticker/timeline strategy rather than setting React state on every frame.
- `Scroller` owns the project's `ScrollSmoother`; do not create another instance in a page.
- Keep the number of pinned sections, ScrollTriggers, high-resolution canvases, filters, masks, and blend modes proportional to device capability.
- When a pin shakes, verify the scroller, fixed/transform `pinType`, ancestor containing blocks, update synchronization, refresh order, and layout stability before adding compositor hints. Test rapid as well as slow scrolling; use `will-change: transform` only when the exact transform-driven layer demonstrably benefits.
- Refresh ScrollTrigger only after meaningful layout changes, not continuously.
- Pause/kill off-screen work and revert component-scoped GSAP contexts on unmount.
- Use `gsap.quickTo()` or `quickSetter()` for high-frequency pointer followers.
- Disable non-essential smooth scroll and large motion under reduced motion; consider capability queries for coarse pointers and constrained widths.
- Test motion with CPU throttling or mid-range mobile hardware. A desktop compositor can conceal expensive design choices.

## Third-party Embeds

- Treat analytics, maps, video players, chat, and social embeds as performance budgets, not free markup.
- Load them after consent and/or interaction when product requirements allow.
- Use a lightweight placeholder with reserved dimensions.
- Do not add a second library for a behavior already covered by ReactWP, React, browser APIs, or GSAP.
- Isolate failures so an unavailable third party cannot prevent route readiness.

## Practical Budgets

Set page-specific budgets from measurements. In the absence of product budgets, use these as review prompts rather than hard universal limits:

- Is the route chunk materially larger than comparable templates?
- Is the LCP asset correctly sized and discoverable in initial HTML?
- Do critical assets contain anything below the fold?
- Does a navigation create long tasks or retain old animations/listeners?
- Does mobile load media or effects that are visually hidden?
- Does the page remain stable while fonts and CMS media arrive?

## Measurement Workflow

1. Build the relevant mode; development bundle timing is not production performance.
2. Measure an initial visit and a client-side route transition separately.
3. Test at least one narrow viewport with network and CPU throttling.
4. Identify the actual LCP element, long tasks, layout shifts, and largest transferred assets.
5. Fix the highest-impact cause and measure again.
6. Confirm the optimization did not break hydration, accessibility, caching, or visual quality.

Useful project checks from `configs/`:

```powershell
npm run prod:themes
npm run report:themes
npm run test:render
```

Use `npm run prod` when the change spans render assets, the SSR bundle, WordPress targets, or the production pipeline.

## Do Not

- Do not hand-optimize generated files in `dist/`.
- Do not preload every image, font, template, or route.
- Do not hide slow content behind a longer animation.
- Do not add a fixed minimum loader duration just to guarantee that branded copy or an animation gets seen; cached readiness may legitimately make the loader brief or absent.
- Do not choose SSR without accounting for its production service and cache behavior.
- Do not set `will-change`, `force3D`, or layer-promoting transforms on entire page trees.
- Do not use Lighthouse alone as proof of a good route transition or interaction experience.
