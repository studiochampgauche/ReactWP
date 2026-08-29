---
name: frontend-expert
description: Design, implement, review, and optimize production frontend experiences in ReactWP using React, SCSS, responsive design, accessible forms, performance, and GSAP. Use for pages, templates, components, form formatting and errors, interactions, loaders, transitions, scroll experiences, or visual work inspired by reference websites. Do not use for backend-only WordPress, PHP, database, or infrastructure tasks with no frontend impact.
---

# Frontend Expert

Create distinctive, maintainable interfaces that fit ReactWP's existing runtime instead of treating the repository as a generic React application. Preserve the user's content, functional requirements, and chosen references while making deliberate decisions about hierarchy, composition, typography, motion, responsiveness, accessibility, and performance.

## Operating Principles

- Inspect the relevant source before proposing or implementing a pattern. Existing runtime contracts outrank generic framework habits.
- Work in `src/`; treat `dist/` as generated output. Run project commands from `configs/`.
- Establish a clear visual idea before polishing details. A coherent concept is more valuable than a collection of fashionable effects.
- Treat display scale and weight as deliberate hierarchy tools, not automatic spectacle. Do not default heroes or section headings to viewport-filling sizes, ultra-tight leading, or heavy 800–900 weights; keep the page job, supporting copy, primary action, and surrounding composition perceptible in the same reading moment. Prefer moderate fluid caps and regular-to-semibold weights when whitespace, color, measure, contrast, or composition can provide the emphasis more gracefully. Validate short and long titles at ordinary laptop, narrow mobile, short-height, zoomed, and translated conditions before approving the role.
- Make responsive behavior intentional at content, layout, type, media, and motion levels; do not merely shrink the desktop composition.
- Keep semantic structure, keyboard access, visible focus, reduced motion, and content readability part of the implementation rather than a later audit.
- Prefer the project's extension points (`configureTemplateRegistry`, `configureLoader`, `configurePageTransition`) over invasive runtime changes.
- Treat first paint as an explicit loading contract. Use ReactWP's existing `#loader` and critical template/font/media promises when a client-rendered or deliberately covered prerendered route must not expose incomplete styling, fallback typography, or an uncommitted layout. Keep the loader shell and its initial visibility rules in first-paint critical CSS; do not create a page-level preloader or use a fixed theatrical delay to mask an incorrect asset graph.
- Never expose loader copy in a project webfont before that exact face and weight are available. Hide every such text node in the server-emitted critical CSS, include only the required face in the route's `criticalFonts`, wait for its specific `document.fonts.load()` request and verify it with `document.fonts.check()`, then reveal it with a deliberate opacity/transform/mask transition. A font-free background, shape, logo artwork, or progress treatment may appear immediately. If the font fails, omit the decorative loader text and let the loader finish; never flash a fallback face or trap the route. Respect reduced motion with an opacity-only or immediate ready state.
- Keep universal templates deterministic and safe to render without browser globals. Add browser-only work through effects or client lifecycle utilities.
- Render plain content as JSX text. For already trusted and sanitized HTML that needs no transformation, prefer a small explicit `dangerouslySetInnerHTML` boundary. Use `html-react-parser` only when `replace` or `transform` is needed to alter the rendered tree; neither approach sanitizes input.
- Use GSAP when motion needs sequencing, interruption, scroll linkage, or runtime control. Use CSS for simple state transitions where a timeline is unnecessary.
- Treat shake or drift on pinned scroll content as a pin-strategy and compositing defect to diagnose. Determine whether ScrollTrigger is using fixed or transform pinning before adding CSS: `will-change: transform` may stabilize the exact transform-driven pin or animated child, but it is not a universal pin rule and must not be placed on an ancestor of a fixed pin because that can break fixed positioning. Verify the actual scroller, ancestor transforms, `pinType`, refresh order, rapid scroll and target browsers.
- Treat viewport fit as a prerequisite for every fixed, pinned, or sticky composition. For each active state, measure the actual scrollport and subtract persistent header/UI offsets, safe areas, and intended padding; compare that space with the natural rendered content after fonts, media, real CMS copy, and controls settle. A large title may not push its supporting content below an immovable viewport. If any state does not fit at a supported width/height, zoom level, locale, or text-size condition, recompose, pin a smaller layer while content remains in normal flow, split the experience into fitting sequential states, or disable the pin/sticky behavior for that condition.
- Sequential pinned storytelling is acceptable only when every essential item is intentionally brought fully into view before the pin releases, the DOM and focus order remain logical, reverse scroll remains understandable, and reduced motion exposes the complete content without requiring scrub. Never hide an oversized pinned layout with clipping/`overflow: hidden`, make an undiscoverable nested scroller, shrink type below the approved readable system, or rely on users scrolling the page to reach content that cannot move because its container is fixed.
- Verify the result at representative viewport widths and exercise the actual navigation/loading flow when it is affected.
- Before using a live site as design inspiration, complete the whole-site reconnaissance in `inspirations.md`. Every first or later use requires a fresh homepage deep pass, interaction testing, and coverage of all distinct public page types; prior notes are context, not a substitute for reinspection.
- During every active-reference reconnaissance, analyze the preloader/first reveal and page-transition system as first-class design evidence, including explicit absence. Exercise cold/warm entry, reload, direct secondary-page entry, internal navigation between distinct templates, browser back/forward, rapid interruption, mobile/touch, keyboard/focus, and reduced motion when observable. Record the full visual sequence, resource/readiness relationship, scroll/header/media continuity, and anything `UNVERIFIED`; do not infer GSAP, Barba, shaders, or another implementation solely from appearance.
- When references are active, let their observed composition, container grammar, typography, media relationships, pacing and interactions materially shape the result. Do not acknowledge them only in an influence map and then fall back to a generic landing-page or dashboard template.
- A device is not authorized merely because it appears somewhere in the inspiration pool. Match the local role, content type, frequency/density, scale and adjacency, interaction/state, and responsive behavior of the driving evidence. A project card, ticket selector, taxonomy tag, festival utility bar, or one-off editorial label cannot be generalized into a page-wide component grammar without matching evidence.
- When improving an existing interface from references, capture the current rendered baseline before redesigning it and maintain a region-by-region delta ledger. Classify each major area as `keep`, `transform`, `remove`, or `replace with supplied/pending media`, name the driving evidence, and require a visible structural result. A new influence map, renamed selectors, or changed colors/type/motion without a changed reading journey is not progress.
- Treat the header and footer as two mandatory reference-led compositions, not reusable neutral chrome. Give each its own influence-map row naming the observed reference, transformed principle, project content/task, desktop/mobile behavior, interactions and originality boundary. They may draw from different active references. If the product deliberately omits either area, its row documents that decision and the resulting navigation/end-of-journey behavior. If no inspected reference fits, record a deliberate restrained project-specific decision instead of filling the gap with a familiar agency/SaaS shell.
- Treat every prominent custom graphic or illustration as first-class art direction. Before drawing it, establish whether it provides evidence, explanation, atmosphere, identity, or navigation; tie its motif, material, scale, placement, and behavior to real project content, brand assets, meaningful data, or a transformed principle observed in an active reference. If its only role is to occupy space or signal a vague technological mood, remove it and use the content, media, typography, or whitespace more deliberately.
- Plan a deliberate media rhythm across the complete page. A website is not automatically a sequence of text blocks: identify where real or planned photography, video, product/editorial imagery, artifacts, or other media strengthen evidence, comprehension, emotion, identity, or pacing, and where an intentionally quiet text-only section is stronger. Consider split text/media, image-led sequences, large crops, full-bleed and near-viewport media sections when the content and inspected references support them; do not add a quota of images or decorate every section.
- When the user or CMS will supply a media asset later, keep its intended place in the composition with a stable responsive placeholder `div` or media-slot component. Give it an approved aspect ratio or minimum block size, focal/crop intent, a restrained project-specific background token, and an explicit pending-asset record so it cannot be mistaken for final artwork. Do not generate or imply an AI image unless the user explicitly requests one. Once supplied, render meaningful media with the appropriate `<img>`, `<picture>`, or `<video>` semantics rather than leaving it as a CSS background or empty `div`.
- Treat cards as an evidence-based content/interaction pattern, never the default React component wrapper. Reject unsupported grids of equal rounded surfaces, icon circles, vague copy and decorative panels; prefer the open rows, lists, editorial grids, full-bleed media or other structures supported by the content and inspected references.
- Treat contextual pretitles/uptitles/kickers, visible labels, indices/item numbers, badges/status/tags, standalone trailing emphasis/fact lines, metadata rails, code/data styling, panels, divider grids, boxed process flows, action treatments, headers, and footers as optional devices—not automatic polish. Use them only for non-redundant context, a necessary control name, true order/status/taxonomy, literal code/data, a real content boundary, a clear interaction need, or a reference-supported brand decision. Do not prepend every section, number every repeated item, append a small bold/colored owner/category/technology/qualifier after every description, restate prose as a pill, wrap ordinary content in equal bordered panels, turn a simple narrative into boxes, default every CTA to a capsule-plus-arrow recipe, or frame the page like a system console merely to make it look designed or technical.
- Use `<strong>` to mark genuine strong importance within prose, not as a generic visual slot at the bottom of a repeated item. If a trailing fact is necessary, decide whether it belongs naturally in the sentence, a caption, a real tag/status, or a semantic name/value structure such as a description list; if none improves comprehension, omit it. Typography and color alone do not create a semantic role.
- When references are active, treat directly observed absence as evidence. Build an interface-device ledger for action treatment, contextual labels, numbering, badges/status, standalone emphasis/fact lines, code/data, panels/surfaces, dividers/rails, header, and footer. If no active inspected reference uses a device family and the product/content does not require its function, omit it. A project-specific divergence is allowed only with a documented reader or interaction benefit and the smallest fitting treatment; “it balances the layout,” “it adds detail,” and “it looks technical” are not benefits. Required semantic form labels and genuine application controls remain required even when references style them quietly.
- When visible editorial content, audience/search intent, page copy, enrichment, metadata, internal links, or entity/schema decisions are involved, also load `content-seo-expert` and follow the shared editorial composition tandem. Frontend owns typography, measure, semantic implementation, responsive composition, media treatment, and accessibility, but must validate them against content meaning, hierarchy, real length, and variability with content/SEO.
- For every submitted or persisted form field, work from the same authoritative field-contract revision as backend. Frontend owns the visible/editing format, native attributes, input-path behavior, accessible instructions/errors, and transport value; backend owns authoritative acceptance and canonicalization. Client restrictions improve usability but never replace server validation.
- When the user requests final QA, release readiness, or proof that all expert requirements were respected, also load `quality-assurance-expert`; frontend remains the source of frontend requirements while QA owns the evidence matrix and verdict.
- When a substantial mission spans multiple expert domains, also load `reactwp-orchestrator`; follow its mission brief, exclusive file/contract ownership, coordination, and handoff rules rather than resolving shared contracts unilaterally.

## Reference Router

Read only what the current task needs:

### Architecture routing

- Repository boundaries, application layers, and deciding where code belongs: [architecture/overview.md](references/architecture/overview.md)
- Component composition, SCSS organization, CMS resilience, and naming: [architecture/components-and-styles.md](references/architecture/components-and-styles.md)
- Design tokens, typography, color, spacing, grids, graphic/illustration art direction, media language, and theming: [architecture/design-system.md](references/architecture/design-system.md)
- Fluid/structural responsive behavior, viewport and pinned/sticky fit, touch, pointer, keyboard, and device capabilities: [architecture/responsive-and-input.md](references/architecture/responsive-and-input.md)
- State ownership, async UI, forms, overlays, route-aware interactions, and cleanup: [architecture/state-and-interactions.md](references/architecture/state-and-interactions.md)
- Definition of done, visual QA, accessibility/performance checks, browser coverage, and test selection: [architecture/quality-and-testing.md](references/architecture/quality-and-testing.md)

### ReactWP routing

- Bootstrap payload, runtime normalization, registry, route service, and client navigation data flow: [reactwp/runtime-and-data-flow.md](references/reactwp/runtime-and-data-flow.md)
- New page templates, route data, internal links, metadata, and hydration-safe content: [reactwp/templates-and-routing.md](references/reactwp/templates-and-routing.md)
- Client/static/server modes, initial rendering, cache scope, tags, invalidation, and regeneration: [reactwp/rendering-and-caching.md](references/reactwp/rendering-and-caching.md)
- Loader, page transitions, smooth scrolling, media groups, and route motion lifecycle: [reactwp/loading-motion-and-media.md](references/reactwp/loading-motion-and-media.md)
- An external React/Vue/Svelte/Astro frontend consuming the ReactWP headless API: [reactwp/headless-frontend.md](references/reactwp/headless-frontend.md)
- Build commands, Webpack boundaries, SCSS/media processing, manifests, generated output, and deployment artifacts: [reactwp/build-assets-and-deployment.md](references/reactwp/build-assets-and-deployment.md)

### Cross-cutting routing

- For any user-facing interface, read [accessibility.md](references/accessibility.md) when semantics, navigation, input, media, contrast, focus, or motion are involved.
- For bundle size, Core Web Vitals, asset strategy, rendering choice, runtime cost, or animation smoothness, read [performance.md](references/performance.md).
- When the user supplies websites, screenshots, art direction, or Awwwards links, read [inspirations.md](references/inspirations.md). The editable inspiration board in that file is also a persistent place for project references.
- When text hierarchy, length, typography, editorial modules, or text-media composition affects the interface, read the shared [editorial composition and frontend tandem](../content-seo-expert/references/editorial-composition-and-frontend-tandem.md).
- When a form field is formatted, constrained, validated, submitted, or persisted, read the shared [form field contracts](../backend-expert/references/form-field-contracts.md) and coordinate its revision with backend.

### GSAP routing

- Basic tweens, easing, stagger, `matchMedia()`, and reduced motion: [gsap/core.md](references/gsap/core.md)
- Multi-step choreography and timing: [gsap/timeline.md](references/gsap/timeline.md)
- Scroll-driven effects, scrub, pinning, and parallax: [gsap/scrolltrigger.md](references/gsap/scrolltrigger.md)
- React lifecycle, `useGSAP()`, scoping, and cleanup: [gsap/react.md](references/gsap/react.md)
- ScrollSmoother, SplitText, Flip, Observer, SVG, and other plugins: [gsap/plugins.md](references/gsap/plugins.md)
- Animation performance and frequent updates: [gsap/performance.md](references/gsap/performance.md)
- `gsap.utils` helpers: [gsap/utils.md](references/gsap/utils.md)
- Vue, Svelte, or another non-React frontend consuming ReactWP headlessly: [gsap/frameworks.md](references/gsap/frameworks.md)

## Implementation Workflow

1. Identify the page's primary job, audience, content hierarchy, technical constraints, and success state.
2. Inspect the route payload and the closest existing template/component/configuration before selecting the implementation layer. For a form, settle the shared field contracts with backend before implementing formatters or validators.
3. If references exist, convert them into an influence map as described in `inspirations.md`; trace every major section to observed structure or a deliberate project-specific decision, not only to colors or effects. For an existing interface, first add a rendered baseline, a primary-carrier map, and a current-to-target delta ledger. Always include distinct `Preloader / first reveal`, `Page transition`, `Header`, `Footer`, `Media rhythm`, and `Interface devices / intentional absence` rows before implementation begins, plus a `Graphic / illustration` row for each prominent custom graphic family or an explicit decision to omit decorative graphics.
4. Define the page's container, annotation, action, shell, and graphic grammar before building reusable visuals. Complete the interface-device ledger, including what the active references deliberately do not use and the exact role/context/frequency of devices they do use. Audit every proposed card/surface/panel, pretitle/uptitle/kicker, visible label, index/number, badge/status/tag, standalone bold/colored fact or qualifier, metadata rail, code treatment, divider, boxed flow, button recipe, header/footer device, and custom graphic. Remove anything redundant, contextually mismatched, over-repeated, or unsupported; replace unjustified wrappers, diagrams, or decorative visualizations with the content- and reference-appropriate editorial, media, list, table, timeline, narrative, typographic, or open-grid structure.
5. With content/SEO, create the editorial composition matrix and media plan before typography or layout hardens. Record each important media purpose, source/status (`supplied`, `pending user/CMS asset`, `optional`, or `not needed`), format, ratio/scale, focal intent, caption/alt/credit needs, text relationship, responsive transformation, and loading priority. Render real short/long/translated content plus pending-asset placeholders early.
6. Establish or reuse a compact set of design tokens before spreading literal values through SCSS. Do not create card radius/shadow/surface tokens unless the approved visual language actually needs them.
7. Build semantic React structure first, then responsive layout and visual treatment, then interaction and motion.
8. Integrate with ReactWP navigation, loading, scroll, and render-mode contracts. Define the first-paint plan for client/static/server modes: whether the shell loader is active or intentionally skipped, which CSS/font/media are critical, how loader text is font-gated and revealed, when the route becomes visible, and how font/JavaScript/resource failure plus reduced motion reach an accessible final state. Do not create a parallel router, loader, or smooth-scroll instance for a page feature.
9. Exercise keyboard navigation, focus behavior, reduced motion, touch input, cold/warm cache, throttled and failed font loading, and constrained width/height combinations. For every fixed, pinned, or sticky region, test start/mid/end and forward/reverse states with real plus longest content, loaded fonts/media, short landscape, mobile browser chrome changes, and text enlargement/zoom; verify that every essential item becomes fully visible and reachable before release. Test the complete header/menu state machine and the footer/end-of-page journey at desktop and mobile, including their transitions from and into page content.
10. Run the component-grammar audit, inspiration genericity test, full-page visual review, and focused tests proportional to the changed layer.

## Expected Quality

A finished frontend should have:

- one readable hierarchy with an intentional focal point;
- display and section titles whose scale, weight, line height, and measure are proportionate to their semantic role and viewport context, without crowding out the supporting copy, navigation, actions, or next content merely to create impact;
- for reference-led work, a visibly traceable composition whose section architecture, container grammar, media, type rhythm and interaction could not have been produced by ignoring the selected references;
- for an existing reference-led interface, a verified current-to-target delta whose first viewport, section sequence, primary content/media carriers, device density, typography, actions, header/menu, and footer/end state changed wherever the audit identified genericity; documentation-only or decorative-only differences do not pass;
- reference decisions supported by evidence from the same role and context at comparable density, scale, interaction and responsive states; an isolated motif elsewhere in the pool never licenses repeated use;
- a preloader/first-reveal and page-transition direction grounded in directly observed reference sequences or documented intentional absence, translated into ReactWP's Loader/PageTransition lifecycle with equivalent mobile, keyboard, interruption, failure, and reduced-motion outcomes rather than copied motion or guessed technology;
- no unjustified generic card grid or repeated surface recipe; every card has a semantic/interaction reason and real content-driven behavior, while non-card structures remain available;
- no repeated layer of decorative kickers, eyebrows, indices, pseudo-status, code-like pills, metadata strips, divider rows or boxed steps; every compact label, marker and technical treatment conveys information that the nearby heading or prose does not already provide;
- no invented family of panels, pretitles/uptitles, labels, item numbers, badges, trailing bold/colored fact lines, code treatments, or stylized buttons that is absent from the active inspirations and unnecessary to the content/interaction; any documented divergence is sparse, purposeful, and visibly project-specific;
- buttons and page chrome whose hierarchy, wording, geometry, typography, icon use and interaction reflect the project and observed references rather than a reusable SaaS CTA/header/footer recipe;
- a header and footer that each have directly traceable inspiration evidence, content-specific structure, responsive transformation and tested interactions; neither can pass merely because it is minimal, technically correct or visually consistent with the middle of the page;
- prominent graphics and illustrations whose concept, visual material, relationship to content, responsive treatment, and motion are traceable to project evidence and transformed inspiration; no interchangeable orbit, network, node, glow, particle, grid, or floating-interface composition used merely as a shorthand for technology or to fill space;
- an intentional page-level media rhythm that uses images, video, product/editorial artifacts, large/full-bleed moments, or quiet text-only passages according to content and reference evidence instead of defaulting to uninterrupted text or forcing media everywhere;
- pending media slots that visibly reserve the approved composition with stable responsive geometry and a restrained placeholder treatment, remain traceable to an asset request, introduce no unrequested AI imagery, and become semantic optimized media when the real asset is supplied;
- no invented statistics, testimonials, badges, avatars, logos, decorative feature icons or claims added merely to make a component pattern look complete;
- consistent spacing, type, color, shape, and motion tokens;
- complete hover, focus-visible, active, loading, empty, error, and disabled states when applicable;
- form fields whose native semantics, formatting, allowed input, accessible errors, transport values, and edge-input behavior match the backend-approved contract for typing, editing, paste, autofill, mobile, IME, and direct submission cases;
- resilient layouts for real CMS content rather than idealized sample strings;
- a jointly approved editorial composition whose type scale, line measure, hierarchy, text length/variance, and text-media relationship remain coherent across supported viewports and locales;
- stable media dimensions, deliberate crops/focal points, appropriate full-bleed/viewport behavior, and predictable loading behavior;
- a stable first paint with no visible unstyled route, fallback-font flash, or premature content reveal in the supported render modes; the ReactWP loader covers only the required critical interval and never adds an arbitrary minimum duration;
- loader copy that is invisible in the server-emitted initial CSS, becomes visible only after its exact critical webfont is positively available, enters fluidly without layout shift, stays omitted on font failure, and has a reduced-motion and no-trap failure path;
- motion that supports orientation and hierarchy without delaying access to content;
- pinned and sticky content whose every active state fits the actual available scrollport or deliberately reveals all essential content before release; it remains reachable under short heights, long/translated copy, zoom/text enlargement, font/media changes and reduced motion, and stays visually stable during slow, rapid, forward and reverse scrolling using a verified pin mode and narrowly scoped compositor hints rather than blanket `will-change`;
- no avoidable hydration mismatch, unscoped animation, stale listener, or duplicated runtime service;
- raw HTML is rendered only through an explicit trusted-and-sanitized boundary, with `html-react-parser` reserved for components that actually transform the tree;
- a relevant build/test result or a clear explanation of what could not be verified.

## Verification Commands

Run from `configs/` and select the smallest relevant set:

```powershell
npm run build:themes
npm run test:render
node --test ./tests/animation-lifecycle.test.mjs
```

Use `npm run build` for changes spanning multiple WordPress targets, and `npm run prod` only when production optimization or generated production assets need verification. Do not assume a successful compile proves visual, responsive, or accessible behavior.
