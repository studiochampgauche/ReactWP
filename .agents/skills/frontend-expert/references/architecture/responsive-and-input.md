---
name: reactwp-responsive-and-input
description: Responsive and adaptive frontend guidance for ReactWP, covering fluid and structural layout changes, dynamic viewports, content stress, touch, pointer, keyboard, hover, reduced motion, and device capability queries.
---

# Responsive Layout and Input

## When to Use This Reference

Apply when a page/component changes layout across available space or behaves differently for touch, pointer, hover, keyboard, viewport height, orientation, or motion preferences.

Responsive design is a change in composition and interaction priorities, not a desktop screenshot scaled down.

## Design Conditions, Not Device Names

Test conditions such as:

- narrow width with long content;
- medium width where multi-column compositions become crowded;
- wide width where copy and media need maximums;
- short viewport with persistent navigation/overlays;
- coarse pointer without hover;
- fine pointer with hover;
- keyboard-only navigation;
- reduced motion;
- browser zoom and enlarged text;
- portrait/landscape changes.

Avoid assuming width tells you whether a user has touch, hover, or a mouse. Hybrid devices exist.

## Fluid vs Structural Change

Use fluid values for continuous relationships:

- type scale within safe minimum/maximum;
- page gutter;
- section spacing;
- grid gaps;
- selected media sizing.

Use breakpoints/container queries for structural changes:

- column count/order;
- navigation model;
- overlap removal;
- sticky/pinned behavior;
- alternate media crop;
- interaction mode.

```scss
.project-grid{
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: clamp(0.75rem, 1.5vw, 1.5rem);
}

@media (max-width: 48rem){
    .project-grid{
        grid-template-columns: 1fr;
    }
}
```

Set breakpoints where the content/system fails, not from a generic device list.

## Container Queries

Use a container query when a reusable component should respond to its allocated width regardless of viewport:

```scss
.entry-region{
    container-type: inline-size;
}

@container (min-width: 36rem){
    .project-entry{
        grid-template-columns: 1.2fr 0.8fr;
    }
}
```

Do not add container queries to every component. They are valuable when the same component appears in different layout contexts.

## Dynamic Viewports

Mobile browser UI makes `100vh` unreliable for critical full-height stages. Prefer a deliberate fallback/modern unit strategy:

```scss
.hero{
    min-height: 100vh;
    min-height: 100svh;
}
```

Use `dvh` when the layout should track browser chrome changes and `svh` when stability is preferable. Test short landscape viewports; a full-screen composition must still expose content and actions.

Account for safe areas only where full-bleed UI reaches device edges:

```scss
.site-header{
    padding-inline: max(var(--space-page), env(safe-area-inset-left));
}
```

## Fixed, Pinned, and Sticky Viewport Fit

Fixed positioning, CSS sticky, and ScrollTrigger pinning are conditional layout modes, not decoration. Before enabling one, calculate the usable block space from the actual scrollport—not an assumed desktop viewport:

```text
available block size
= scrollport/visual viewport block size
- persistent header or toolbar
- relevant safe-area insets
- required top/bottom padding and controls
```

Compare that result with the natural rendered block size of every state after fonts, media, CMS content, captions, actions, and errors settle. For a sequential pinned scene, apply the test to each state that is meant to be read at once. The current state must fit, and the scroll sequence must bring every essential later state fully into view before unpinning.

If the contract fails, choose a structural response rather than hiding overflow:

- return the section to normal document flow;
- keep only a bounded visual or navigation aid sticky while headings, copy, actions, and media scroll naturally;
- change columns to a vertical composition, reduce nonessential decoration, or adjust the approved fluid type/spacing range without harming readability;
- divide a dense sequence into multiple fitting stages with a clear continuous reading order;
- disable pin/sticky through `gsap.matchMedia()` or the relevant CSS condition for short heights, narrow layouts, enlarged text, or reduced motion;
- use an independently scrollable region only when the product genuinely requires it, its boundary and overflow are obvious, keyboard/touch/wheel access works, focus can enter/leave, and it does not trap page scrolling.

Do not solve a failed fit with `overflow: hidden`, clipping, an arbitrary fixed height, or a title reduction that violates the type system. Width-only breakpoints are insufficient: include height/aspect conditions or a measured runtime eligibility check. Re-evaluate after `document.fonts.ready`, media/CMS/layout changes, resize/orientation, and mobile visual-viewport changes; rebuild or refresh the owned ScrollTrigger without creating continuous layout work.

## Content Stress

At each layout transition, test:

- title twice the expected length;
- empty optional fields;
- several CTA buttons with long labels;
- portrait and landscape media;
- missing media/fallback;
- one, two, and many repeated items;
- rich text with lists/tables/links;
- translated text and non-breaking strings.
- fixed/pinned/sticky regions with the tallest real state, the longest heading/supporting copy, enlarged text, and the shortest supported viewport.

Use `minmax(0, 1fr)`, `min-width: 0`, wrapping, and intentional overflow. Do not hide overflow merely to conceal a broken layout.

## Touch Targets and Gestures

- Primary targets should provide a comfortable touch area, generally around 44 CSS pixels or equivalent spacing.
- Keep destructive/adjacent controls sufficiently separated.
- Do not require hover to reveal essential actions.
- Do not make horizontal swipe the only access to content or navigation.
- Provide visible affordance and keyboard/button alternatives for drag interactions.
- Avoid scroll interception that fights native touch behavior.
- Test browser back/forward and same-page hash navigation with the smooth scroller active/inactive.

## Pointer and Hover Capability

Use capability queries for enhancements:

```scss
@media (hover: hover) and (pointer: fine){
    .project-entry:hover .project-entry__media{
        transform: scale(1.02);
    }
}
```

The default state must remain complete without hover. Pointer followers, magnetic controls, tilt, and custom cursors are progressive effects; they should not change semantics or block normal click targets.

## Keyboard

DOM order should match reading and focus order at every layout. CSS visual reordering must not create a different keyboard sequence.

- Keep focus-visible treatment legible on every responsive background.
- Mobile navigation must open/close through a real button, expose expanded state, manage focus, close with Escape, and restore focus.
- Off-canvas content must not remain tabbable while closed.
- Sticky/fixed UI must not cover the focused element or hash target.
- Scroll locking must be released on all close/unmount/error paths.

## Responsive Media

- Supply intrinsic dimensions/aspect ratio.
- Match `sizes` to the actual layout slots, not a generic `100vw`.
- Use art-directed sources when mobile requires a materially different crop.
- Avoid downloading desktop-only decorative video/images hidden by CSS.
- Keep captions and controls associated after visual reordering.
- Choose object position from content metadata or safe defaults rather than hardcoding every image center.

## Responsive Motion

Motion may simplify structurally:

- remove desktop parallax/pinning when the narrow flow is linear;
- shorten travel distances;
- reduce stagger counts;
- avoid large masked text reveals on small/slow devices;
- disable pointer-specific motion for coarse pointers;
- retain immediate final states under reduced motion.

Use `gsap.matchMedia()` for GSAP conditions and ensure each condition reverts its created animations/ScrollTriggers.

## Navigation and Scroll

ReactWP owns one Scroller/ScrollSmoother instance. Responsive components should not create another scroll engine.

- Account for header offsets at hash targets.
- Refresh after real layout/media/font changes, not continuously.
- Ensure native scrolling remains functional when smooth scrolling is disabled.
- Test back/forward restoration, route top, and hashes at narrow and wide layouts.
- Fixed overlays must integrate with the existing scroll-lock lifecycle rather than leaving `body` locked.

## Minimum Verification Matrix

Choose widths from the design, but include at least:

- about 320px narrow viewport;
- a large mobile/small tablet condition;
- the structural desktop breakpoint on both sides;
- an ordinary laptop;
- a wide display with capped measures;
- a short landscape viewport.

At each relevant condition, check mouse/touch assumptions, keyboard order, focus visibility, reduced motion, zoom/content stress, network/media loading, and complete visibility/reachability within every active fixed/pinned/sticky state. A responsive implementation is not complete when only screenshots at two widths look correct.
