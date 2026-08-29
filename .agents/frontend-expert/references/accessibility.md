---
name: frontend-accessibility
description: ReactWP accessibility guidance for semantic structure, keyboard and focus behavior, navigation, motion, media, forms, color, and responsive interfaces. Use when creating or reviewing any user-facing page, component, transition, loader, or interaction.
---

# Frontend Accessibility

## When to Use This Reference

Apply while designing or reviewing any user-facing ReactWP interface. Give extra attention when the task adds navigation, a modal or menu, dynamic route changes, animated content, custom controls, forms, rich text, video/audio, or unusual pointer interactions.

Accessibility is a functional requirement. Do not preserve a reference site's inaccessible behavior merely because it is visually distinctive.

## Semantic Structure

- Use the native element that expresses the behavior: `<button>` for an action, `<a>` for navigation, `<nav>` for a navigation region, and form controls for user input.
- Keep exactly one useful page-level `<h1>` in a route and preserve a logical heading outline. Do not choose heading levels for appearance; style them with classes.
- Give repeated regions useful landmarks (`header`, `nav`, `main`, `aside`, `footer`) and label multiple instances of the same landmark.
- Use lists for collections whose order or grouping is meaningful.
- Keep WordPress-authored rich text semantic and sanitize its allowed markup before the React sink. Use `dangerouslySetInnerHTML` when sanitized HTML should pass through unchanged; use `RichText`/`html-react-parser` when `replace` or `transform` is needed to remove, replace, or modify nodes. Neither rendering API is a sanitizer.
- Use `normalizeHeadingTag()` when a WordPress field controls a heading tag. Constrain the value to `h1`-`h6` and provide a sensible fallback.

## Links and Buttons in ReactWP

Use the existing primitives:

- `AppLink` for internal route navigation and same-page hashes;
- `Button` when a visual control may act as a link or button depending on its destination;
- a native button when the action changes local state and does not navigate.

Preserve normal browser behaviors: opening in a new tab, copying a link, modified clicks, downloads, and external navigation. Do not attach navigation to a generic `<div>` or intercept every click globally inside a component.

An icon-only control needs an accessible name:

```jsx
<button type="button" className="menu-toggle" aria-label="Open menu">
    <MenuIcon aria-hidden="true" />
</button>
```

When a control expands content, communicate state and relationship:

```jsx
<button
    type="button"
    aria-expanded={open}
    aria-controls="primary-navigation"
    onClick={() => setOpen((value) => !value)}
>
    Menu
</button>
```

## Keyboard and Focus

- Every interaction must work with keyboard alone in a predictable order.
- Never remove focus outlines globally. Use `:focus-visible` to create a strong state that works on every background.
- Avoid positive `tabIndex`; fix DOM order instead.
- Do not make non-interactive decorative elements focusable.
- Escape should dismiss modal menus, dialogs, or overlays when dismissal is available.
- A modal interaction must contain focus while open, restore focus to its trigger on close, and prevent the background from being interactive. Prefer the native `<dialog>` element when it satisfies the design and browser requirements.
- Hover-only content must also be available through focus and touch. Do not require precise pointer movement to reach essential content.

A project focus token can be centralized:

```scss
:root{
    --focus-color: #1f6fff;
    --focus-offset: 0.2rem;
}

:where(a, button, input, textarea, select, summary):focus-visible{
    outline: 0.15rem solid var(--focus-color);
    outline-offset: var(--focus-offset);
}
```

Do not rely on color alone for focus, errors, selection, or status.

## Client-side Route Changes

ReactWP changes routes without a full document reload, so browser focus is not automatically reset.

- Keep page content inside the existing `#app` main region.
- After a completed route transition, focus should move to a meaningful route target when the product needs screen-reader announcement and keyboard orientation. Prefer a focusable page heading or the main region with `tabIndex={-1}`; do not move focus on same-page hash navigation when the target already supplies the expected context.
- Update the document title and route metadata through `useDocumentMeta`; do not manage competing title state in individual visual components.
- If route completion is announced through a live region, keep the message short and avoid announcing multiple loader/progress messages for the same navigation.
- Ensure page-transition overlays cannot retain focus or intercept pointer input after their visible phase.
- A visual loader marked `aria-hidden="true"` must not contain essential status text. If loading regularly takes long enough to need an announcement, add a separate polite status region.

Example route heading target:

```jsx
const PageTitle = ({ children }) => (
    <h1 id="page-title" tabIndex={-1}>
        {children}
    </h1>
);
```

Only programmatically focus it when the route lifecycle calls for it; avoid focus jumps during initial hydration.

## Motion and Vestibular Safety

Respect `prefers-reduced-motion` for GSAP, CSS, smooth scrolling, autoplay, parallax, pinned sections, cursor followers, and decorative video.

ReactWP already exposes `prefersReducedMotion` and immediate alternatives to loader/page-transition factories. Use those contracts rather than hiding content until an animation completes.

- Reduced motion should preserve state and meaning while removing large movement, parallax, zoom, rapid flashing, and long scrubbing.
- An immediate path may set final visibility with `gsap.set()`; it must still resolve the loader or transition lifecycle.
- Avoid infinite motion near reading content. Pause decorative loops when off-screen or when the document is hidden.
- Never make scroll progress the only way to access essential content.
- Before pinning or fixing a section, verify that each readable state fits the usable scrollport after persistent UI and safe areas are deducted. If a scroll-linked sequence presents several states, every essential item must become fully visible and focusable before release; the reduced-motion path must expose the complete content in normal flow or another immediate, non-scrubbed composition.
- Do not allow an oversized display heading or decorative layer to push supporting copy, controls, or focused elements below an immovable pinned viewport. At zoom/enlarged text or short heights, reflow or remove the pin instead of clipping content or creating an unannounced nested scroll trap.
- Keep smooth scrolling disabled when reduced motion is requested; `Scroller` already follows this behavior.
- CSS must also provide a reduced-motion path:

```scss
@media (prefers-reduced-motion: reduce){
    *,
    *::before,
    *::after{
        scroll-behavior: auto !important;
    }

    .decorative-motion{
        animation: none;
        transition: none;
        transform: none;
    }
}
```

Do not use a universal `animation-duration: 0.01ms` rule if it breaks loaders, disclosure states, or third-party control semantics. Target the actual motion system.

## Color, Contrast, and Readability

- Normal text should target at least 4.5:1 contrast; large text and essential graphical controls should target at least 3:1.
- Validate the final rendered color pairs, including text over photographs, gradients, video, translucent overlays, disabled states, and focus rings.
- Do not encode information only by hue. Pair it with text, shape, iconography, position, or pattern.
- Keep body copy at a readable measure, generally around 45-80 characters per line depending on typeface and language.
- Allow text zoom and browser font-size preferences. Avoid fixed-height containers that clip translated or enlarged text.
- Ensure fluid display type still has a mobile minimum that remains readable and a maximum that does not dominate short viewports.

## Images, Video, and Audio

- Every meaningful image needs concise alternative text that communicates its purpose in context. Decorative images use `alt=""` and should not duplicate adjacent text.
- Provide `width` and `height` or an `aspect-ratio` so media does not shift the layout.
- Do not place essential copy only inside an image.
- Autoplay video must be muted, non-essential, and safe under reduced motion/data constraints. Provide controls for content video.
- Content video needs captions; audio-only content needs a transcript when speech carries information.
- Avoid autoplay audio.
- Ensure custom play/pause/mute controls expose names and state.
- Do not replace native media controls unless the custom controls are fully keyboard and assistive-technology operable.

## Forms and Validation

- For formatted or constrained inputs, follow the shared [form field contracts](../../backend-expert/references/form-field-contracts.md). Explain non-obvious allowed formats before entry and keep filtering compatible with paste, autofill, mobile keyboards, IME, selection, deletion, undo, and assistive technology.
- Every control needs a programmatically associated label. Placeholder text is supplemental, not a label.
- Group related controls with `fieldset` and `legend` where appropriate.
- Expose required state and instructions before submission.
- Associate inline errors with their control using `aria-describedby`; use `aria-invalid` after validation fails.
- On failed submission, preserve entered values, show a summary when several errors exist, and focus the summary or first invalid field according to the form's complexity.
- Use correct `type`, `name`, `autocomplete`, `inputMode`, and native constraints before adding custom validation.
- Do not rely on key suppression alone or trap focus/caret to enforce a format. When silent filtering could change meaning, preserve the input and expose a specific error instead.
- Disable submission only when necessary and communicate pending/success/failure states without relying on a spinner alone.

## Responsive and Touch Access

- Target sizes should be comfortable for touch; aim for at least 44 by 44 CSS pixels for primary interactive targets or provide equivalent spacing.
- Do not make horizontal drag the only way to discover or reach content.
- Test at 320 CSS pixels without two-dimensional page scrolling, except for genuinely tabular or spatial content.
- Support reflow at 400% zoom and landscape mobile heights.
- Re-test every fixed, sticky, or pinned region at enlarged text/zoom and short landscape height; all essential content and controls must remain visible or reachable without two-dimensional scrolling or scroll trapping.
- Avoid interactions that depend on device orientation, hover capability, or motion sensors without an alternative.
- Use media queries for capability (`hover`, `pointer`, reduced motion) rather than assuming device type from width.

## Accessible States Checklist

For each component, verify applicable states:

- default, hover, focus-visible, active, selected;
- disabled and read-only;
- loading, empty, error, success;
- open and closed;
- current route (`aria-current="page"`);
- reduced motion;
- high zoom, long labels, translated content, and missing images.

## Do Not

- Do not add ARIA to recreate behavior a native element already supplies.
- Do not use `outline: none` without an equally visible focus alternative.
- Do not trap scroll or focus after a loader/transition completes.
- Do not animate from `opacity: 0` without a no-JavaScript or reduced-motion path to visible content.
- Do not make scroll, hover, sound, or autoplay necessary to understand the page.
- Do not copy accessibility defects from an inspiration website.

## Focused Verification

At minimum, perform a keyboard pass in DOM order and inspect the narrowest supported layout. For significant changes, also test a screen reader/browser combination, reduced motion, 200-400% zoom, forced/high-contrast presentation where supported, and automated checks. Treat automated results as a baseline; they do not validate reading order, names, focus movement, or interaction quality.
