---
name: reactwp-components-and-styles
description: ReactWP component composition and SCSS design-system guidance, including responsive layout, tokens, states, CMS resilience, and naming. Use when building or reorganizing page UI.
---

# Components and Styles

## When to Use This Reference

Apply when building a page, section, reusable component, design system, or responsive SCSS. This reference describes project-local conventions; it does not require a component library or a particular visual style.

## Compose by Responsibility

Use three practical levels:

1. **Template:** adapts `route.data` and composes the route's content hierarchy.
2. **Section/component:** owns a coherent content or interaction pattern and its states.
3. **Primitive:** owns a repeatable semantic behavior such as link, button, rich text, media frame, or layout container.

Keep ACF field names at the template boundary when possible:

```jsx
const Project = ({ route }) => {
    const hero = {
        title: route.data.hero_title || route.pageName,
        summary: route.data.hero_summary,
        media: route.data.hero_media
    };

    return <ProjectHero {...hero} />;
};
```

This makes the component easier to reuse and makes WordPress schema changes visible in one place. Do not create a wrapper component for every DOM node; a local semantic block is often clearer.

## Existing Primitives

Prefer the current component contracts before inventing equivalents:

- `AppLink`: internal React Router links, safe external links, hashes, and route prefetch.
- `Button`: action/link semantics with `before`, `text`, and `after` slots plus variants.
- `RichText`: transformed, allowlisted WordPress HTML rendering.
- `Contents`: common title/uptitle/subtitle/text/buttons composition.
- `Image`, `Video`, `Audio`: media targets populated by ReactWP's loader/media groups.
- `AppShell`, `Header`, `Footer`: shared application frame.

Extend or compose these when their semantic contract fits. Do not bypass their URL normalization, DOM-prop filtering, or explicit HTML rendering boundaries by spreading raw CMS objects directly to DOM nodes.

Classify HTML strings before rendering. Plain content stays JSX text. Already trusted and sanitized HTML that must remain unchanged should use a small explicit `dangerouslySetInnerHTML` component. Use `html-react-parser` only when the component needs `replace` or `transform`, such as converting anchors to `AppLink`, replacing media, removing nodes, changing attributes, or injecting React components. ReactWP `RichText` is such a transformed path: it filters tags and attributes, validates URL-bearing values and `srcset`, and adjusts link targets. Neither API sanitizes arbitrary WordPress, ACF, API, or editor content; enforce that policy upstream.

## Design Tokens

The starter's `_variables.scss` is intentionally minimal. For a real interface, define a compact system of roles rather than scattering literal values.

Use CSS custom properties for values that vary by theme, component context, viewport, or runtime state; use Sass variables/functions for compile-time paths and calculations.

```scss
:root{
    --color-canvas: #f5f2ea;
    --color-surface: #fffdf8;
    --color-ink: #121210;
    --color-muted: #66635c;
    --color-accent: #d84b2d;

    --font-display: 'Project Display', sans-serif;
    --font-body: 'Project Sans', sans-serif;

    --step--1: clamp(0.8rem, 0.76rem + 0.15vw, 0.9rem);
    --step-0: clamp(1rem, 0.95rem + 0.22vw, 1.125rem);
    --step-3: clamp(2rem, 1.35rem + 3vw, 4.5rem);
    --step-5: clamp(3.25rem, 1.7rem + 7vw, 8.5rem);

    --space-page: clamp(1rem, 3vw, 3.5rem);
    --space-section: clamp(4.5rem, 10vw, 10rem);
    --measure-copy: 68ch;
    --radius-control: 999px;
    --ease-standard: cubic-bezier(0.22, 1, 0.36, 1);
}
```

Values above demonstrate roles only; derive the actual aesthetic from the project brief and inspiration map. Token names should describe purpose (`--color-ink`) rather than a current literal (`--black`).

## Layout System

- Use a predictable page gutter token and a grid that sections can share.
- Let content determine height. Reserve fixed viewport sections for concepts that genuinely require a stage, and account for mobile browser UI with dynamic viewport units.
- Prefer Grid for two-dimensional editorial composition and Flexbox for linear distribution.
- Use `minmax(0, 1fr)` inside grids/flex items that contain long CMS content to prevent overflow.
- Use logical properties (`margin-inline`, `padding-block`, `inset-inline-start`) where they improve direction resilience.
- Keep readable copy measures independent from the full visual grid.
- Reserve media aspect ratios; choose intentional object position/crop behavior.
- Use container queries when a reusable component should react to its allocated space, and viewport queries when the whole page composition changes.

Example shared shell:

```scss
.page-grid{
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    column-gap: clamp(0.75rem, 1.5vw, 1.5rem);
    padding-inline: var(--space-page);
}

.page-grid__copy{
    grid-column: 2 / span 5;
    max-width: var(--measure-copy);
}
```

At narrow widths, explicitly redefine the content order and columns instead of relying on accidental wrapping.

## Responsive Design

Design at least these conditions, adjusting exact breakpoints to content:

- narrow mobile with long labels and a short viewport;
- large mobile/small tablet where single-column content can become over-wide;
- intermediate laptop width where desktop overlaps often fail;
- wide display where line lengths and image scaling need caps;
- coarse pointer/touch and keyboard-only navigation;
- reduced motion and increased zoom.

Use content breakpoints rather than framework defaults. Fluid `clamp()` values reduce breakpoint noise, but they do not replace structural layout changes.

Do not simply hide difficult content on mobile. Reorder, simplify motion, alter crop, or choose a more direct interaction while preserving information and actions.

## Naming and SCSS Organization

The existing code generally uses readable component classes and BEM-like elements/modifiers. Continue that direction:

```scss
.project-entry{}
.project-entry__media{}
.project-entry__title{}
.project-entry--featured{}
```

- Keep selectors shallow and component-scoped.
- Avoid styling by tag beneath a generic utility when CMS markup can vary.
- Avoid IDs for visual styling; ReactWP shell IDs are runtime hooks.
- Keep state classes explicit: `.is-active`, `.is-loading`, `.has-error`.
- Use data attributes for behavior/configuration when state needs to be visible to JavaScript; do not overload class names as an undocumented API.
- Import global foundations from `scss/default.scss`. Import template-specific SCSS from the owning template/component graph when route-level extraction is desired.

## Component States

Implement the states the component can actually enter:

- interaction: hover, focus-visible, active, current, disabled;
- data: loading, empty, error, partial, success;
- media: loading, loaded, missing, portrait/landscape, fallback;
- disclosure: open, closed, entering, leaving;
- environment: reduced motion, coarse pointer, narrow/short viewport.

Avoid styling only the ideal state represented in a design screenshot. WordPress content is dynamic, and empty optional fields should not leave unexplained gaps.

## Cards Are Not the Default Component

Component boundaries do not require visual cards. A React component may render an open editorial section, row, list item, media-caption pair, table row, genuinely ordered step, full-width band or overlapping composition without receiving a rounded background container.

Use a card surface only when it communicates a real boundary, interaction, repeated object, selection/state, or comparison that would become less clear without enclosure. For reference-led work, its geometry and behavior must come from the inspected container grammar rather than a remembered UI-kit recipe.

Avoid automatically combining rounded corners, subtle border, soft shadow, icon circle, eyebrow, title, paragraph, CTA and equal-height grid. Avoid wrapping an entire section in one card and then wrapping each child in another. If removing the surface leaves hierarchy and interaction unchanged, prefer the simpler open composition.

Repeated content does not automatically mean cards. Consider:

- full-width rows whose hierarchy comes from content, with indices only when order or reference identity matters;
- typographic lists with media revealed on hover/focus;
- a shared media stage controlled by adjacent titles;
- split text/media compositions or editorial columns;
- tables, timelines, accordions or definition lists when their semantics fit;
- one featured item plus quieter supporting entries rather than equal boxes.

Variation must be content-driven and systematic. Do not randomize offsets, radii, sizes or rotations merely to look handmade. Use real image proportions, title length, priority, chronology, taxonomy or editorial intent to create a more authored rhythm.

## Avoid Robotic Micro-Interface Grammar

Removing rounded cards is not enough. A page can still feel mechanically generated when every section is decomposed into the same apparatus: tiny uppercase or monospace pretitle, decorative number, short heading, explanatory copy, bordered metadata, and another horizontal rule. Class names do not identify the problem; repetition without semantic need does.

Treat these as warning signals when they recur across a page:

- every heading receives a kicker, eyebrow, category, or status line even when the heading is self-sufficient;
- items are numbered although their order, progress, chronology, or cross-reference does not matter;
- ordinary prose, implementation names, or marketing phrases are styled as code, terminal output, system status, tags, or pills;
- a hero ends with a bordered multi-column slogan or metadata rail that only repeats its promise;
- a simple relationship or process becomes equal bordered boxes, connector arrows, and labels although natural prose, media, or one continuous composition would explain it better;
- rules, boxes, labels, and aligned columns separate every thought, producing the visual cadence of a specification sheet regardless of the brand or content;
- the header uses an ornamental system-status line and the footer becomes a thin uppercase metadata bar without a real navigational, editorial, or brand reason;
- every primary/secondary action repeats the same capsule, outline, trailing-arrow, and horizontal pair regardless of importance, location, or reference behavior.

Before adding one of these devices, ask:

1. What unique information or interaction clarity disappears if it is removed?
2. Is it real navigation, status, taxonomy, sequence, code, data, or an interaction affordance?
3. Is its form supported by the brief, brand, content model, or an observed reference?
4. Would a direct heading, sentence, caption, image relationship, whitespace shift, text link, or broader composition communicate more naturally?
5. Does the same device already appear elsewhere often enough that another instance becomes a visual tic?

Do not ban kickers, indices, code, tables, diagrams, labels, dividers, bordered steps, pills, arrows, status, or utility bars. Use them when their semantics earn them: literal commands may look like code; an ordered procedure may need numbers; a dashboard may need compact status; a technical diagram may need nodes; a brand may genuinely use capsule controls. Remove decorative duplicates before trying to restyle them. An authored, human result comes from specific language, real media, deliberate hierarchy, varied section relationships, purposeful actions, and editorial judgment—not random asymmetry or simulated imperfection.

## Action and Page-Shell Grammar

Buttons, navigation, the header, and the footer are major brand surfaces, not neutral primitives to drop around a finished page.

- For reference-led work, require separate authoritative `Header` and `Footer` influence-map rows before hardening shared shell components. Reuse of a component does not justify reuse of a generic visual recipe.
- Derive action hierarchy, label tone, shape, edge treatment, icon behavior, typography, spacing, placement, hover/focus feedback, and mobile transformation from the task and observed references.
- Use a button only for an action and a link for navigation. Visual restraint does not change semantic requirements, target size, focus visibility, or state coverage.
- Do not add a trailing arrow, icon capsule, magnetic hover, filled/outline pair, or rounded pill to every action by habit. Each must improve meaning, direction, feedback, or the approved visual language.
- Design the header around actual navigation, identity, context, and user tasks. Decorative availability/status copy or technical jargon does not make it more distinctive.
- Design the footer as the intentional end of the reading journey: useful destinations, contact/legal/context, a strong final brand or editorial gesture, or deliberate quiet. Avoid a generic three-cell utility strip simply because it balances the grid.
- Review header, page content, actions, and footer together. A human-feeling middle cannot compensate for generic chrome at the first and last impression.

## CMS Resilience

- Supply fallbacks only where the product has a meaningful fallback; do not mask missing required content with lorem ipsum.
- Treat titles, labels, and rich text as variable length and potentially multilingual.
- Use stable content identifiers as React keys. Use an array index only for static, non-reordered decorative items.
- Render a section only when its required content exists; keep conditions near the section boundary.
- Normalize repeated field data before passing it to reusable components.
- Keep raw HTML inside an explicit trusted/sanitized rendering boundary. Use `RichText` only when its React-level transformations are required; never interpolate HTML into attributes, scripts, or styles.
- Validate optional destination/media objects before rendering controls.

## Visual Quality Without Noise

- Choose one dominant contrast: scale, color, whitespace, typography, or motion.
- Build rhythm through repeated alignment and spacing relationships.
- Use decorative layers only when they reinforce the concept and have defined responsive behavior.
- Avoid filling every empty area. Whitespace is structural when it guides reading and pacing.
- Ensure every major section has a clear entry point and relationship to the next.
- Keep interaction feedback fast even when editorial transitions are slower.

## Browser and Runtime Boundaries

- Do not access browser globals during component render when the template can be static/server rendered.
- DOM measurements belong in layout effects or animation lifecycle code and must clean up.
- Do not create another `ScrollSmoother`; use ReactWP's `Scroller` contract.
- Do not animate global selectors from a reusable component without a scoped root.
- Avoid generating dynamic class names that the SCSS/build tooling cannot discover or maintain.

## Review Checklist

- Is the template adapting route data while components receive clear semantic props?
- Are existing primitives reused where their safety/navigation contracts apply?
- Do tokens express the chosen visual direction without unnecessary literal duplication?
- Is every card/surface semantically justified, reference-supported when references are active, and preferable to an open row/list/editorial structure?
- Does every compact label, index, badge, code treatment, metadata rail, divider grid, or boxed flow carry unique semantic or interaction value, rather than completing a remembered visual recipe?
- Do action controls, navigation, header, and footer visibly belong to this content and brand rather than to a reusable AI/SaaS shell?
- When references are active, can the header and footer each be traced to their own recorded observed principle and verified desktop/mobile transformation?
- Does the hierarchy survive long CMS content, missing media, and intermediate widths?
- Are styles scoped, states complete, and shell IDs left to the runtime?
- Is the first render deterministic for static/server modes?
- Do keyboard, touch, reduced-motion, and focus-visible presentations remain complete?
