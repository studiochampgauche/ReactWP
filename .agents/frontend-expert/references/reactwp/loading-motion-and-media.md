---
name: reactwp-loading-motion-and-media
description: Local guide for ReactWP Loader, page transitions, animation lifecycle, ScrollSmoother/Scroller, route media groups, and reduced-motion behavior. Use for loaders, transitions, scroll, preloading, or route animation work.
---

# ReactWP Loading, Motion, and Media

## When to Use This Reference

Apply before changing the loader, route transition, smooth scrolling, critical/deferred media, or global GSAP behavior. Then load the specific GSAP reference needed for the animation API.

ReactWP already provides one coordinated lifecycle. Customize its factories and services rather than starting a parallel animation/loading stack.

## Shell Hooks

The WordPress theme shell provides stable runtime elements:

```text
#loader
#app-header
#viewport
  #pageWrapper
    #pageContent
      #app
```

- `#app` is the React mount/hydration root.
- `#viewport` is the default leave/enter transition target.
- `#pageWrapper` and `#pageContent` are owned by `ScrollSmoother`.
- `#loader` is the visual loading layer and is `aria-hidden` by default.

Do not rename, reparent, or animate away these hooks from page components without changing and testing the runtime contract.

## First Paint, FOUC, and Render Modes

ReactWP's loader can prevent an incomplete client route from becoming the first visible frame, but it is not a substitute for delivering the correct styles and fonts.

The current theme emits `#loader` before the application markup and places its base geometry/background in early `wp_head` CSS. On client rendering, `Loader.prepareInitialLoad()` starts the critical template, font, and media requests; `Loader.display()` waits for those requests plus the committed route before setting `loaderState.isLoaded`. The configured animation may then remove the cover.

Static and server initial renders deliberately skip the visual loader by default through `rwp_prerender_skip_loader` because styled HTML and its template assets can be available immediately. Decide this per project instead of assuming the loader always appears. Force the loader for a prerendered route only when an exact branded reveal or another measured first-paint requirement justifies the extra blocking layer, then test hydration, failure, and no-JavaScript behavior. Otherwise prevent FOUC at the source with the enqueued template stylesheet, critical shell CSS, deterministic markup, stable dimensions, and an intentional font strategy.

Classify the defect before changing the loader:

- unstyled structure means the required stylesheet or template chunk is late or incorrectly registered;
- fallback-font replacement is FOUT and requires a font-loading/metrics decision;
- invisible text is FOIT and must not become an indefinite loading state;
- content popping after hydration may indicate a nondeterministic first render or a late client-only layout branch;
- a layout shift after the cover exits means the critical font/media/dimensions contract is incomplete.

Use the loader to cover the real critical interval while fixing the corresponding asset or render contract. Do not lengthen its animation merely to hide an unresolved race.

### Text inside the loader

Text styled with a project webfont must not be visible in the initial HTML frame. Its fallback face can flash before JavaScript has a chance to run, so hiding it only inside `configureLoader.js` is too late.

1. Put the text node's hidden initial state in the same server-emitted critical CSS as the loader shell. Reserve its layout when needed, but keep it visually hidden with `opacity: 0` and `visibility: hidden` before first paint.
2. Make the exact loader face/weight a member of the applicable ReactWP `criticalFonts` group. Entries are CSS font shorthands consumed by `document.fonts.load()`; include only the face required above the fold rather than every project weight.
3. In the configured loader animation, wait for `loaderState.criticalFonts`, request the exact shorthand with the actual loader text as the sample, and require `document.fonts.check()` to return `true` before revealing the node. Do not use `document.fonts.ready`, which can wait for unrelated faces.
4. Reveal ready copy fluidly with a restrained opacity plus small transform or mask transition that matches the project. Animate from already hidden CSS so there is no one-frame fallback flash or layout jump.
5. If the Font Loading API is unavailable, the request rejects, or the exact face is still unavailable, keep decorative loader text hidden and continue the non-text loader/route exit. Font failure must not trap navigation.
6. Under reduced motion, keep the same font gate and use an opacity-only transition or the immediate visible ready state according to the project's accessibility decision.

Example first-paint CSS for customized markup:

```css
#loader [data-loader-copy]{
    opacity: 0;
    visibility: hidden;
    transform: translateY(.5rem);
}
```

The animation may gate that node without changing ReactWP's loader completion contract:

```js
const copy = loader?.querySelector('[data-loader-copy]');
const font = '600 1.35rem "Project Sans"';
const sample = copy?.textContent?.trim() || 'Loading';

Promise.resolve(loaderState.criticalFonts)
    .then(async () => {
        if(!copy || !document.fonts){
            return false;
        }

        await document.fonts.load(font, sample);
        return document.fonts.check(font, sample);
    })
    .then((fontReady) => {
        if(!fontReady || !copy?.isConnected || loaderState.isLoaded){
            return;
        }

        return gsap.to(copy, {
            autoAlpha: 1,
            y: 0,
            duration: reducedMotion ? 0.12 : 0.4,
            ease: 'power2.out'
        });
    })
    .catch(() => null);
```

This fragment only demonstrates the font gate. The owning animation must still return or complete through ReactWP's supported factory contract, cancel obsolete work, exit when `loaderState.isLoaded` becomes true, and call `done` at most once when it uses that callback.

## Loader Responsibilities

`Loader` coordinates, per normalized route key:

- lazy template module preload;
- critical font requests;
- critical media download and display;
- deferred media download and display by group;
- route readiness after React commits;
- visual loader completion;
- exposed `window.loader` promises/state used by configured animation.

Key state includes `isLoaded`, `criticalDisplay`, `noCriticalDisplay`, template/font/media promises, and per-group deferred promises.

The loader is not a fixed-duration splash screen. It may animate while resources are pending, but it must finish promptly when the route is ready.

Its visual contents are optional. Prefer a simple project-specific background, mark, shape, real progress response, or restrained motion when those can render correctly at first paint. Loader copy is decorative inside the current `aria-hidden` shell; do not place essential instructions or status there without designing a separate accessible announcement contract.

## Configure the Loader

Use `js/inc/config/configureLoader.js` and `Loader.setAnimation()`:

```js
Loader.setAnimation(({ gsap, ScrollTrigger, loader, labelNode, loaderState, done }) => {
    if(!loader){
        done();
        return null;
    }

    const timeline = gsap.timeline({
        onComplete: done
    });

    timeline
        .to({}, {
            duration: 0.1,
            repeat: -1,
            onRepeat(){
                if(loaderState.isLoaded){
                    this.repeat(0);
                }
            }
        })
        .to(loader, {
            autoAlpha: 0,
            pointerEvents: 'none',
            duration: 0.3,
            onStart: () => ScrollTrigger.refresh()
        });

    return timeline;
});
```

The repository's existing configuration demonstrates a restart loop instead of the repeat approach above; retain whichever pattern is present and tested unless the task calls for changing it.

Animation factories can complete in either way:

- return a GSAP animation whose `onComplete` can be wired automatically; or
- access/call `done` when completion depends on custom asynchronous logic.

Call `done` once. If the factory reads `done`, automatic completion wiring is intentionally disabled. A returned Promise may also define completion. Kill/release timelines after completion when they will not be reused.

Reduced motion uses the configured immediate factory when one exists, or the runtime default. It must wait only for required readiness and then place the loader in its final non-interactive state.

Use the project-facing `runAnimation()` helper for ordinary custom lifecycle work. `runAnimationLifecycle()` from `AnimationLifecycle.js` is the lower-level primitive used by `motion.js` and focused tests; it receives the reduced-motion decision explicitly instead of reading ReactWP's runtime snapshot.

## Configure Page Transitions

Use `js/inc/config/configurePageTransition.js`:

```js
PageTransitionAnimation
    .setLeave(({ gsap, viewport }) => {
        return gsap.to(viewport, {
            autoAlpha: 0,
            pointerEvents: 'none',
            duration: 0.25,
            ease: 'power2.inOut'
        });
    }, ({ gsap, viewport }) => {
        gsap.set(viewport, {
            autoAlpha: 0,
            pointerEvents: 'none'
        });
    })
    .setEnter(({ gsap, viewport }) => {
        return gsap.to(viewport, {
            autoAlpha: 1,
            pointerEvents: 'initial',
            duration: 0.35,
            ease: 'power2.out'
        });
    }, ({ gsap, viewport }) => {
        gsap.set(viewport, {
            autoAlpha: 1,
            pointerEvents: 'initial'
        });
    });
```

Factories receive GSAP, ScrollTrigger, loader/viewport elements, reduced-motion state, and route/navigation context passed by `useRouteTransition`.

Rules:

- leave must resolve so the blocker can proceed;
- enter must restore content visibility and pointer behavior;
- immediate factories must set the same final semantic/UI state without large motion;
- overwrite or kill obsolete animations when users navigate quickly;
- do not unmount the old route manually; React Router and `useRouteTransition` own the swap;
- avoid animating `#pageContent` transforms directly while ScrollSmoother owns them.

## Route Transition Sequence

For a different route key:

1. Scroller locks and remembers position.
2. Next route payload is fetched and normalized.
3. Loader prepares template and critical resources.
4. Leave animation runs.
5. Router proceeds after critical readiness and paint allowance.
6. React commits the next template and marks the route ready.
7. Critical display resolves.
8. Scroll goes to hash target or route top; Scroller unlocks.
9. Enter animation runs.
10. Deferred media is preloaded/displayed.

Keep the old page legible until leave begins and the new page hidden only for the minimum coherent interval. A transition should explain continuity, not impose latency.

## Component-level GSAP

Global route transition factories are not a substitute for component-scoped motion. Within a component:

- create animation after the DOM exists;
- scope selectors to a component root;
- use `@gsap/react`/`useGSAP()` or `gsap.context()` where available;
- revert animations and ScrollTriggers on unmount;
- prevent an initial hidden state from remaining hidden if JavaScript fails;
- coordinate entrances with route readiness only when the content truly depends on the global loader.

Read `gsap/react.md` for the exact lifecycle and `gsap/scrolltrigger.md` for scroll-driven work.

## Scroller Contract

`Scroller.js` registers one `ScrollSmoother` instance and exposes:

- `init()` / `kill()`;
- `refresh()`;
- `scrollTo()` / `jumpToTop()`;
- `getScrollTop()` / `setLockScrollTop()`;
- depth-aware `lock()` / `unlock()`.

It enables smooth scrolling for supported pointer conditions and disables it for reduced motion. It also toggles `has-smooth-scroll` and `is-scroll-locked` classes on the document.

Do not:

- call `ScrollSmoother.create()` from a template;
- directly replace `#pageWrapper`/`#pageContent` transforms;
- implement a competing scroll lock on `body` without integrating lock depth;
- leave `window.gscroll` paused after an error path;
- use native smooth scrolling as a second simultaneous engine.

After async media, font, disclosure, or layout changes, request a single relevant `scroller.refresh()`/`ScrollTrigger.refresh()`. Avoid refreshing on every frame.

## Media Groups

Routes expose a comma-separated `mediaGroups` value. The loader always includes the `all` group and combines it with route groups.

Bootstrap assets contain:

- `criticalFonts`;
- `criticalMedias`;
- `noCriticalMedias`.

Media entries can be strings or normalized objects with source/type/sources and safe attributes. Image/video/audio types may be inferred from known extensions. The loader validates URLs and allowed media properties before applying them.

Use critical groups for media required to present the route's initial viewport. Use deferred groups for content below the fold or non-blocking enhancements.

The placeholder primitives (`Image`, `Video`, `Audio`) expose nested targets such as `.img`, `.video`, and `.audio`. Media entries can target safe elements, while shell nodes and protected elements cannot be replaced.

Guidelines:

- assign stable, component-scoped selectors or element targets;
- reserve aspect ratio before the loader fills media;
- include useful alt text/semantics in the rendered component contract;
- avoid selectors that can match more than the intended component set;
- keep group names meaningful to route content rather than animation timing;
- do not make deferred media a prerequisite for route entry;
- respect route cache flags for media persistence.

## Page-level Media vs Loader Media

Use normal semantic React `<img>`, `<picture>`, `<video>`, or `<audio>` when the source is already part of route data and ordinary browser loading is sufficient. Use ReactWP media groups when coordinated preload/display, shared groups, caching, or loader readiness is a real requirement.

Do not route every small icon or below-the-fold image through the global loader. Conversely, do not lazy-load the likely LCP visual so late that it starts only after hydration when it could be critical/discoverable earlier.

## Reduced Motion

ReactWP's motion module captures the current reduced-motion preference for lifecycle factories, and Scroller checks the preference when initializing. Custom CSS and component animation must follow the same intent.

- Supply immediate final states for global loader/transition customizations.
- Replace parallax/scrub/large movement with stable composition.
- Keep route navigation and hash scrolling functional without smooth behavior.
- Avoid autoplay decorative motion where it is not essential.
- If the preference can change while the app is open and the feature needs to react live, use `gsap.matchMedia()` or a media-query listener with cleanup.

## Failure and Interruption Safety

- A route fetch/transition failure must release or bypass custom work and allow hard navigation fallback.
- Completion callbacks must be idempotent; the named `once()` export from `AnimationLifecycle.js` exists for this reason.
- Quick repeated navigation must not allow an obsolete enter animation to reveal the wrong route.
- Kill animations before discarding references; revert contexts on unmount.
- Ensure scroll locks balance even if media or animation promises reject.
- Avoid selectors that target nodes from both outgoing and incoming route trees unintentionally.

## Focused Verification

Run from `configs/`:

```powershell
node --test ./tests/animation-lifecycle.test.mjs
npm run test:render
npm run build:themes
```

Then manually exercise:

- initial client render and a hydrated static/server render;
- internal navigation in both directions plus browser back/forward;
- same-route and next-route hashes;
- rapid repeated navigation;
- reduced motion;
- keyboard focus through loader/transition;
- a route with slow or failed media;
- cold and warm cache with a throttled, cached, unavailable, and failed loader font;
- the first frame before JavaScript runs, verifying that branded loader copy is hidden by critical CSS and never flashes in a fallback face;
- the configured client/static/server initial-render behavior, including intentional loader skip/force, hydration, JavaScript failure/no-script escape, and content visibility after loader exit;
- narrow touch and fine-pointer layouts.
