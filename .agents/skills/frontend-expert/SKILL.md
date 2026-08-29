---
name: frontend-expert
description: Design, implement, review, and optimize production frontend experiences in ReactWP using React, SCSS, responsive design, accessible forms, performance, and GSAP. Use for pages, templates, components, form formatting and errors, interactions, loaders, transitions, scroll experiences, or visual work inspired by reference websites. Do not use for backend-only WordPress, PHP, database, or infrastructure tasks with no frontend impact.
---

# Frontend Expert

Create distinctive, maintainable interfaces that fit ReactWP's runtime and the project's real visual language. Preserve the user's content, requirements, supplied references, and explicit design rationale while making deliberate decisions about hierarchy, composition, typography, motion, responsiveness, accessibility, and performance.

## Core Invariants

- Inspect the closest source, route payload, template, and configuration before choosing an implementation pattern. Existing ReactWP contracts outrank generic framework habits.
- Work in `src/`, treat `dist/` as generated, and run project commands from `configs/`.
- Prefer ReactWP's registry, loader, page-transition, scroller, media, routing, and render-mode extension points over parallel services or invasive runtime changes.
- Keep shared templates deterministic without browser globals. Put browser-only behavior behind effects or client lifecycle utilities, clean it up, and preserve client/static/server compatibility where supported.
- Make responsive behavior intentional across content, layout, type, media, input, and motion. Test width and usable height, including zoom, text enlargement, mobile browser chrome, long CMS copy, and translated content.
- Keep semantic structure, keyboard access, visible focus, touch targets, readable contrast, reduced motion, and accessible states in the implementation from the start.
- Treat display size and weight as hierarchy tools, not automatic spectacle. Do not default to viewport-filling, ultra-heavy titles when measure, spacing, color, or composition can create clearer emphasis.
- Treat fixed, sticky, and pinned behavior as a viewport-fit contract. If essential content cannot fit or become fully reachable, recompose or disable the behavior; do not conceal it with clipping, inaccessible nested scrolling, or indiscriminate type shrinking.
- Use CSS for simple state transitions and GSAP when sequencing, interruption, scroll linkage, or lifecycle control warrants it. Follow the routed GSAP reference rather than guessing cleanup or pin behavior.
- Treat first paint, critical fonts/media, loader completion, route readiness, failure, interruption, and reduced motion as one lifecycle. Never add a theatrical delay or a page-local loader to hide an incorrect asset graph.
- Render plain content as JSX text. Render unchanged, already trusted and sanitized HTML through the narrowest explicit boundary; reserve `html-react-parser` for required DOM-to-React transformation. Neither API sanitizes input.
- The project's real brand language, supplied assets, inspected references, content needs, accessibility, and explicit user rationale have the highest visual authority. Anti-generic guidance is a diagnostic default, not a ban on cards, labels, large type, surfaces, or familiar controls when evidence supports them.

## Collaboration Thresholds

- Load `content-seo-expert` and use the shared editorial tandem only when content decisions materially affect hierarchy, SEO, CMS variability, responsive measure, media, structured data, or layout. An isolated typo, label, or wording correction that preserves meaning, metadata, and composition can be handled directly with focused verification.
- For every submitted or persisted field, use the backend-custodied contract in `backend-expert/references/form-field-contracts.md`; frontend owns editing behavior, native attributes, accessible instructions/errors, and transport production, while backend remains authoritative.
- Load `security-expert` for raw HTML, URLs, head/JSON-LD, user input, authentication/private state, public payloads, files, or another trust boundary.
- Load `quality-assurance-expert` when the user requests final QA, release readiness, or evidence that expert requirements were met.
- Load `reactwp-orchestrator` only for a substantial mission with at least two independent expert workstreams. A localized frontend task does not need orchestration ceremony.

## Proportional Reference Work

When a user supplies a live site, screenshot, moodboard, or art direction, read [inspirations.md](references/inspirations.md) and select the smallest reconnaissance scope that can support the decision:

- **Site-wide:** inspect the complete public experience, distinct templates, persistent shell, homepage journey, representative responsive states, and relevant transitions/interactions.
- **Page-level:** inspect the relevant page from top to bottom, its persistent header/footer context, representative responsive states, and interactions or adjacent templates that materially affect that page.
- **Component-level:** inspect the component, its immediate layout context, meaningful variants, input states, and responsive/interaction behavior; do not inventory an unrelated site.

Record relevant `UNVERIFIED` states and refresh evidence when the reference or affected scope changed. Derive principles, not protected assets or a copied composition. For reference-led work, use only the influence-map rows, device ledger, baseline/delta evidence, media planning, and genericity checks applicable to the selected scope.

## Reference Router

Read only what the current task needs.

### Architecture

- Placement and application layers: [architecture/overview.md](references/architecture/overview.md)
- Components, SCSS, CMS resilience, and naming: [architecture/components-and-styles.md](references/architecture/components-and-styles.md)
- Tokens, typography, color, spacing, grids, graphics, media language, and theming: [architecture/design-system.md](references/architecture/design-system.md)
- Responsive behavior, input modes, and viewport/pin fit: [architecture/responsive-and-input.md](references/architecture/responsive-and-input.md)
- State, async UI, forms, overlays, route interactions, and cleanup: [architecture/state-and-interactions.md](references/architecture/state-and-interactions.md)
- Definition of done, visual QA, browser coverage, and test selection: [architecture/quality-and-testing.md](references/architecture/quality-and-testing.md)

### ReactWP

- Bootstrap data, normalization, registry, and navigation flow: [reactwp/runtime-and-data-flow.md](references/reactwp/runtime-and-data-flow.md)
- Templates, route data, links, metadata, and hydration-safe content: [reactwp/templates-and-routing.md](references/reactwp/templates-and-routing.md)
- Client/static/server modes, caches, tags, invalidation, and regeneration: [reactwp/rendering-and-caching.md](references/reactwp/rendering-and-caching.md)
- Loader, transitions, smooth scrolling, media groups, and route motion lifecycle: [reactwp/loading-motion-and-media.md](references/reactwp/loading-motion-and-media.md)
- External React/Vue/Svelte/Astro consumers: [reactwp/headless-frontend.md](references/reactwp/headless-frontend.md)
- Webpack, SCSS/media processing, manifests, and generated artifacts: [reactwp/build-assets-and-deployment.md](references/reactwp/build-assets-and-deployment.md)

### Cross-cutting

- Semantics, navigation, input, media, contrast, focus, or motion: [accessibility.md](references/accessibility.md)
- Core Web Vitals, bundles, assets, rendering cost, and animation smoothness: [performance.md](references/performance.md)
- Live sites, screenshots, art direction, or Awwwards references: [inspirations.md](references/inspirations.md)
- Material text/layout/media coupling: [editorial composition and frontend tandem](../content-seo-expert/references/editorial-composition-and-frontend-tandem.md)
- Form formatting, validation, submission, or persistence: [form field contracts](../backend-expert/references/form-field-contracts.md)

### GSAP

- Tweens, easing, stagger, `matchMedia()`, and reduced motion: [gsap/core.md](references/gsap/core.md)
- Multi-step choreography: [gsap/timeline.md](references/gsap/timeline.md)
- Scroll, scrub, pinning, and parallax: [gsap/scrolltrigger.md](references/gsap/scrolltrigger.md)
- React lifecycle, scoping, and cleanup: [gsap/react.md](references/gsap/react.md)
- ScrollSmoother, SplitText, Flip, Observer, SVG, and plugins: [gsap/plugins.md](references/gsap/plugins.md)
- Frequent updates and animation performance: [gsap/performance.md](references/gsap/performance.md)
- `gsap.utils` helpers: [gsap/utils.md](references/gsap/utils.md)
- Non-React headless consumers: [gsap/frameworks.md](references/gsap/frameworks.md)

## Workflow

1. Define the page/component job, audience, content hierarchy, success state, delivery/rendering modes, and constraints.
2. Inspect the closest producer, payload, template, style, lifecycle, and tests; settle any shared field or editorial contract before implementation hardens.
3. When references are active, select and document the proportional reconnaissance scope, then convert relevant evidence into an influence map. For an existing interface, capture matching baseline and delta evidence.
4. Define a compact content, container, type, media, action, state, and motion grammar. Prefer structures justified by real content and project/reference evidence; use cards, labels, indices, panels, and graphics only where they earn their role.
5. Build semantic React first, responsive composition second, then interaction and motion. Integrate through existing ReactWP lifecycle contracts.
6. Exercise real, short, long, empty, translated, loading, error, focus, touch, reduced-motion, and constrained-height states proportional to the changed behavior.
7. Run the smallest sufficient build, tests, and runtime/visual checks. Revisit any evidence invalidated by the implementation.

## Verification

A finished frontend has direct evidence for the applicable claims: readable hierarchy, responsive and CMS-resilient layout, complete interactive states, accessible navigation/input/media, stable first paint and route lifecycle, scoped motion cleanup, safe HTML boundaries, and compatibility with supported render modes.

Run from `configs/` and select the smallest relevant set:

```powershell
npm run build:themes
npm run test:render
node --test ./tests/animation-lifecycle.test.mjs
```

Use `npm run build` for changes spanning multiple WordPress targets and `npm run prod` only when production optimization or artifacts need verification. A successful compile does not prove visual, responsive, accessibility, lifecycle, or runtime behavior.
