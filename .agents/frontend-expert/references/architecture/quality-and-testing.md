---
name: reactwp-frontend-quality-testing
description: Definition of done and verification strategy for ReactWP frontend changes, including build selection, visual QA, responsive/content stress, accessibility, performance, hydration, navigation, and regression testing.
---

# Frontend Quality and Testing

## When to Use This Reference

Apply before declaring a frontend implementation complete or when deciding which verification is proportional to a change. Tests should validate observable behavior and project invariants, not generated wording or implementation trivia.

## Definition of Done

A frontend change is complete when applicable behavior is correct across:

- initial request and client-side navigation;
- configured client/static/server rendering modes;
- content loading, empty, failure, and long-content states;
- responsive widths and short viewports;
- keyboard, touch, pointer, and reduced motion;
- accessible semantics, name/state, focus, and contrast;
- route Loader/Scroller/PageTransition lifecycle;
- production asset boundary and performance budget;
- cleanup after unmount, interruption, error, or rapid navigation.

A successful compile proves syntax/module integration, not visual or interaction quality.

## Select Verification by Changed Layer

| Changed layer | Minimum relevant verification |
| --- | --- |
| SCSS or presentational component | `npm run build:themes`; visual/responsive/content stress pass |
| Route template/registry | theme build; initial load + client navigation; relevant render test |
| Rich HTML/URL/media component | theme build; malicious/invalid input case; accessibility pass; security skill if trust boundary changes |
| Loader/transition/Scroller/GSAP lifecycle | animation lifecycle test; render test; rapid navigation/reduced motion/manual scroll checks |
| Server/static compatible shared component | render test; hydration comparison; browser-only guard review |
| Webpack/assets/build scripts | targeted build plus production build/report when manifests/optimization change |
| Headless consumer behavior | endpoint contract/error/auth/cache tests plus consumer routing states |

Run commands from `configs/`.

## Build and Test Commands

Common focused commands:

```powershell
npm run build:themes
npm run test:render
node --test ./tests/animation-lifecycle.test.mjs
npm run report:themes
```

Use `npm run build` for changes across theme/plugin/mu-plugin targets. Use `npm run prod` when production chunking, minification, compression, manifests, media optimization, SSR bundle, or deployment output matters.

Security-sensitive changes use the scripts routed by `security-expert`.

## Visual QA

Inspect the rendered result, not only source:

- compare hierarchy and composition to the brief/reference intent;
- check typography loading, line breaks, clipping, and fallback behavior;
- inspect the first paint before JavaScript, during critical loading, at font readiness, and after loader exit. No unstyled route or fallback project font may flash through the cover, and loader copy using a webfont must begin hidden in server-emitted critical CSS before entering fluidly after its exact face is verified;
- inspect media crop/aspect ratio/loading transitions;
- verify hover/focus/active/current/disabled/loading/error states;
- ensure overlays, sticky elements, and route transitions do not leave artifacts;
- check color inversion, transparency, blend/filter fallback, and focus rings;
- confirm the design remains coherent with real text and optional sections.
- inspect the complete journey from header through footer for repeated micro-labels, decorative indices, pseudo-code, metadata rails, divider grids, boxed flows, and habitual CTA treatments; remove devices that do not add unique meaning even when each isolated component appears polished.
- compare navigation, buttons, menu states, section transitions, and the footer to the reference influence map, not only the hero and content cards.
- for reference-led work, compare the implemented first paint/preloader and each applicable route transition with their dedicated influence-map rows and source sequence records. Verify cold/warm entry, direct secondary entry, leave/wait/swap/enter, back/forward, rapid interruption, persistent shell/media, scroll/focus/input, mobile, reduced motion and failure; a generic fade added without observed or product evidence does not pass;
- for an existing reference-led page, capture the current baseline before implementation and compare the same desktop/mobile first viewport, section sequence, media/carrier rhythm, interface-device density, header/menu states, footer transition and end state after implementation; a documentation, palette, radius, font or motion-only delta does not prove the redesign;
- verify that every adopted reference device matches the observed role/context, frequency/density, scale/adjacency, interaction/state and responsive behavior. One occurrence anywhere in the pool is not sufficient support for repeated local use;
- compare each major section with the primary-carrier map. Pending media must visibly reserve its approved geometry; an uninterrupted sequence of text plus interchangeable abstract filler fails when the plan calls for real media, artifacts, data, or content-specific interaction.

When visual tooling/browser control is available, use screenshots at representative widths and interact with the local application. Do not use source inspection as a substitute for render verification.

## Responsive and Content Stress

At minimum verify:

- about 320px narrow width;
- a mobile/tablet transition;
- both sides of each structural breakpoint;
- a standard laptop width;
- a wide viewport with capped line lengths;
- a short landscape viewport;
- long titles/labels, empty optional fields, missing media, varied list counts, and rich text.

Check for unexpected horizontal scrolling, covered focus/anchors, fixed-height clipping, tap target crowding, and unnecessary hidden downloads.

## Navigation Matrix

For route-aware changes test:

- direct initial request;
- internal `AppLink` navigation;
- browser back and forward;
- same-route hash;
- next-route hash;
- query variants;
- rapid repeated navigation;
- route/media failure fallback;
- scroll top/restoration and lock release;
- header/footer remount behavior when affected.
- client/static/server initial render with the loader intentionally active or skipped, including cold/warm cache, throttled/failed critical font, loader-copy reveal, and font/JavaScript failure escape when affected.
- for a reference-led loader or transition, the exact source route pair/state that supplied the principle plus every unverified reference behavior; do not claim equivalence from a hero screenshot or ideal forward navigation alone.

Static/server templates also require hydration without recoverable mismatch warnings.

## Accessibility Pass

Perform a manual keyboard pass:

- logical focus order;
- visible focus in every visual context;
- operation of menus/disclosures/forms/dialogs;
- Escape/close/focus restoration;
- no focusable hidden content;
- route-change orientation/announcement where required.

Also inspect semantic headings/landmarks, accessible names/states, image alt behavior, form labels/errors, contrast, reduced motion, zoom/reflow, and touch target sizing. Automated scanners are useful baselines but cannot judge interaction meaning or focus quality.

## Motion QA

- Verify ordinary and reduced-motion paths reach the same semantic final state.
- Test quick reversal/re-entry and navigation interruption.
- Ensure component GSAP contexts/ScrollTriggers disappear after unmount.
- Confirm no invisible element blocks pointer/focus.
- Inspect on a constrained CPU/device when effects are significant.
- Avoid evaluating only the ideal first play; repeat the interaction and use back/forward.

## Performance Pass

Measure initial load and client navigation separately. Confirm:

- likely LCP content/media is early, sized, and stable;
- no below-fold asset blocks route readiness;
- route chunk has not absorbed unrelated templates/libraries;
- dynamic interactions avoid long main-thread tasks;
- old listeners/timelines/media do not accumulate across routes;
- hidden mobile media is not needlessly downloaded;
- layout shifts remain controlled as fonts/media arrive.
- the loader covers only the measured critical interval, has no fixed show-time, does not delay ready content for its own animation, and cannot trap the route when decorative loader text or its font fails;

Use production output for bundle/transfer conclusions.

## Hydration and Universal Rendering

For static/server-compatible code:

- compare server and browser first-render markup;
- remove time/random/viewport/storage branches from render;
- use stable keys;
- avoid invalid nesting;
- keep browser-only imports/effects guarded;
- verify raw HTML uses the same trusted-and-sanitized source in server and client rendering, and that parser-based components produce the same transformed tree in both environments;
- ensure effects enhance rather than create essential content.

## Regression Tests

Add a focused automated test when a change modifies a non-obvious reusable invariant, such as:

- route key/query normalization;
- loader completion/animation interruption;
- render-mode manifest behavior;
- sanitizer/URL allowlist behavior;
- cache scope/tag identity;
- security boundary;
- build manifest/output mapping.

Prefer testing observable input/output or lifecycle completion. Avoid tests that merely assert internal function order or duplicate implementation text.

## Handoff

Report:

- the user-visible result;
- important architecture/design decisions;
- for reference-led work, the authoritative `Preloader / first reveal` and `Page transition` influence rows plus cold/warm entry and route-pair evidence, intentional absences, transformed ReactWP behavior, and remaining `UNVERIFIED` states;
- for existing reference-led work, the authoritative baseline/delta ledger, primary-carrier map, and matched before/after observations;
- tests/builds/manual conditions actually verified;
- anything not verifiable locally;
- relevant files and safe next steps.

Do not claim browser, accessibility, performance, or production validation that was not performed.
