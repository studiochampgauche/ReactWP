---
name: reactwp-design-system
description: Guidance for creating and evolving a ReactWP visual system through semantic tokens, typography, color, spacing, grids, surfaces, media, motion, themes, and component contracts. Use when a task establishes or changes the product's visual foundations.
---

# Design System

## When to Use This Reference

Apply when a project needs a visual direction, reusable tokens, typography/color foundations, repeated layout rules, theming, or consistency across several templates. For one isolated component, use existing tokens and read `components-and-styles.md` instead of expanding the system unnecessarily.

The starter intentionally contains very few visual decisions. Build the smallest coherent system that expresses the project concept and supports real WordPress content.

## Start from Product Intent

Before selecting values, identify:

- audience, tone, and primary actions;
- content types and their relative importance;
- brand assets and licensed type/media;
- desired density and editorial rhythm;
- reference-site principles from `inspirations.md`;
- supported languages, contrast modes, motion preferences, and devices;
- constraints imposed by dynamic CMS content.

Do not create a generic collection of fashionable tokens. Every prominent choice should reinforce the page hierarchy and brand character.

For reference-led redesign of an existing interface, inventory the current page before changing the system. Record its first viewport, section sequence, primary carrier per region, media cadence, repeated device density, shell states, and end-of-page composition. Use the authoritative current-to-target delta ledger from `inspirations.md`; new tokens are not evidence of improvement unless the rendered architecture and reading journey change where required.

## Token Layers

Use three layers when they provide real leverage:

1. **Primitive values:** palette steps, type sizes, spacing ratios, durations.
2. **Semantic roles:** canvas, ink, accent, muted text, section gap, focus ring.
3. **Component roles:** navigation height, hero display size, media frame, or a surface role only when the product actually uses one.

Components should primarily consume semantic/component roles. Primitive values may change between themes without requiring component rewrites.

```scss
:root{
    --primitive-neutral-0: #ffffff;
    --primitive-neutral-950: #10100f;
    --primitive-orange-600: #d94e2b;

    --color-canvas: var(--primitive-neutral-0);
    --color-ink: var(--primitive-neutral-950);
    --color-accent: var(--primitive-orange-600);
    --color-focus: var(--color-accent);

    --space-page: clamp(1rem, 3vw, 3.5rem);
    --space-section: clamp(5rem, 10vw, 11rem);
    --measure-copy: 68ch;
}
```

These values illustrate architecture, not a default ReactWP aesthetic.

## CSS Custom Properties vs Sass

Use CSS custom properties for values that:

- change by theme, mode, section, viewport, or component context;
- need inheritance;
- may be animated;
- should be inspectable and adjustable at runtime.

Use Sass for:

- source asset paths already represented in `_variables.scss`;
- compile-time functions/mixins;
- repeated generation that does not need runtime variation.

Avoid duplicating the same value as unrelated Sass and CSS variables. Make one layer authoritative.

## Typography

Define roles rather than styling each heading ad hoc:

- display/hero;
- section title;
- body and long-form body;
- navigation/control labels and optional contextual labels that add information;
- metadata/caption;
- literal code, commands, identifiers, or numerical data when relevant.

For each role decide family, weight, size, line height, tracking, measure, and responsive behavior. Use fluid sizing only where intermediate interpolation improves the composition.

```scss
:root{
    --font-display: 'Project Display', sans-serif;
    --font-body: 'Project Sans', sans-serif;
    --type-body: clamp(1rem, 0.96rem + 0.2vw, 1.125rem);
    --type-display: clamp(3.25rem, 1.5rem + 8vw, 10rem);
    --leading-body: 1.55;
    --leading-display: 0.9;
}
```

- Keep long-form line length readable independently of the visual grid.
- Define robust fallback stacks and font-display behavior.
- Test long French/English labels, uppercase accents, punctuation, and browser zoom.
- Do not use heading levels as type tokens; semantics and appearance remain separate.
- Do not make viewport-filling display type or 800–900 weight the default signature of a designed page. Start from the smallest size and lightest weight that still makes the hierarchy immediately clear, then use spacing, measure, color, alignment, and adjacent content relationships before escalating scale or weight. A hero should normally reveal enough of its promise, supporting context, and primary action to read as a complete entry rather than a title specimen.
- Cap fluid title roles by both width and usable height. Review ordinary laptop, narrow mobile, short landscape, 200–400% zoom, long/translated copy, and fallback-font states; reduce or recompose the approved role when the title crowds out essential context even if no text technically clips.
- Do not make tiny uppercase tracking or monospace text the automatic pretitle for every section. A contextual label is a content role, not required visual scaffolding.
- Reserve code styling for literal code, commands, identifiers, or data whose technical form helps the reader. Do not use it to make ordinary prose feel technological.

## Color and Contrast

Assign roles for canvas, surfaces, primary text, muted text, borders, accent, focus, success, warning, and error. Validate actual rendered combinations, including image overlays and translucent layers.

- Avoid a palette where the only accent cannot also function as accessible text/focus.
- State changes need more than hue alone.
- If sections invert colors, every inherited component state must remain legible.
- Treat blend modes and filters as progressive visual treatment, not the only route to readable content.
- A theme switch must update browser/system-facing colors and media where appropriate, not only body background.

## Spacing and Rhythm

Use a restrained scale for recurring relationships:

- inline control spacing;
- component internal spacing;
- group spacing;
- section spacing;
- page gutters.

Fluid section/page spacing can reduce breakpoint noise. Components may use a local token when their internal rhythm differs intentionally.

Avoid enforcing one mechanical gap everywhere. Editorial hierarchy often needs distinct relationships: title-to-intro, intro-to-action, repeated-item-to-item, and section-to-section.

## Grid and Containers

Define a shared grid contract only if multiple sections use it. A typical system may include:

- page gutters;
- maximum content width;
- 4/6/12 responsive columns;
- consistent column gap;
- readable content measure;
- full-bleed media escape.

Do not force every component onto a global 12-column abstraction. Components with self-contained layout can use their own Grid or container query.

## Surfaces, Shape, and Depth

Decide whether the interface language relies on:

- flat editorial planes;
- cards/surfaces;
- borders/dividers;
- soft or hard radii;
- shadow/elevation;
- texture/noise/gradient;
- overlap and masking.

Use a small vocabulary consistently. Avoid introducing a different radius, shadow, and border language for every component.

Decide explicitly whether repeated content needs a surface at all. Do not make rounded elevated cards the implicit system default. When active references use flat editorial planes, rules, rows, full-bleed media or open whitespace, preserve that structural language in the tokens and components. If cards are justified, define their role, density and interaction separately from controls, media frames and decorative panels instead of applying one radius/shadow recipe everywhere.

## Annotation, Separation, Action, and Shell Grammar

Define how the system uses contextual labels, indices, captions, status, code, dividers, process diagrams, actions, header, and footer; do not inherit one technical-looking treatment for all of them.

- A label must orient, classify, name a control, or add context that the heading cannot carry efficiently. Omit decorative restatements.
- An index must express order, progress, chronology, or stable reference identity. Do not number items solely to fill a grid column.
- A divider should clarify a meaningful boundary. Vary section transitions through space, media, overlap, background, pacing, or continuity when the content calls for it instead of ruling every row and band.
- A boxed flow should explain real nodes, states, dependencies, or interaction. A short linear story can remain prose, an open sequence, one diagram, or a media-led transition without putting every step in an equal cell.
- A hero footer or metadata rail must contain essential context or a useful next action; repeating several slogans below the hero is not a default compositional ending.
- Action variants must encode real hierarchy and behavior. Do not make capsule geometry, outline/fill pairs, arrows, or identical hover effects the unexamined default.
- Header and footer roles must come from actual navigation, brand, editorial, utility, legal, or product needs. Do not style both as interchangeable uppercase metadata bars to manufacture a technical tone.

### Reference absence and burden of invention

For reference-led work, do not record only what the active sites contain. Record what their composition consistently omits. Use one interface-device ledger:

| Device family | Directly observed in active references | Required project/content role | Decision and reason |
| --- | --- | --- | --- |
| Actions: button, text link, icon control | Site/page/state and exact treatment, or `absent` | Real action/destination and hierarchy | Transform observed grammar, use the smallest accessible control, or omit |
| Pretitle/uptitle/kicker/context label | Evidence or `absent` | Unique orientation/classification not repeated by the heading | Keep, merge into heading/prose, or omit |
| Index/item number | Evidence or `absent` | True order, chronology, progress, rank, or cross-reference | Keep or omit |
| Badge/status/tag | Evidence or `absent` | Real status, filter, taxonomy, or state | Keep or omit |
| Standalone trailing emphasis/fact | Evidence or `absent` | Necessary owner, category, technology, qualifier, result, or other fact not clear in the main copy | Integrate into prose/caption, model as real metadata, or omit |
| Code/data treatment | Evidence or `absent` | Literal code, command, identifier, or data | Use semantic code/data or plain language |
| Panel/card/surface | Evidence or `absent` | Discrete object, state, comparison, interaction, or boundary | Enclose or keep open |
| Divider/rail/metadata system | Evidence or `absent` | Meaningful separation or essential context | Use sparingly or omit |

When a family is absent from every active inspected reference and has no required content/interaction function, presume omission. A new family needs a documented reader benefit and must be the smallest project-specific divergence. Do not add it because a grid has an empty column, repeated items need “visual interest,” or a technical subject supposedly needs technical-looking chrome.

This rule does not remove correct semantics. Form controls still need accessible names/labels, application actions still need buttons, navigation still needs links, real status may need a badge, and literal source code may need `<code>`. Keep the semantic requirement while deriving its visible treatment from content, accessibility, and the observed reference grammar. A required form label can be visually quiet or visually hidden when the accessible naming contract permits; it cannot simply disappear.

Do not create a repeated terminal line of small bold, colored, uppercase, or monospace text merely to finish each item or occupy the bottom of a flex column. `<strong>` expresses strong importance in its surrounding prose; it is not a generic metadata container. For a necessary secondary fact, first try integrating it into the sentence or caption. Use a semantic list/name-value structure, tag, status, or data element only when that relationship is real and helpful. If the fact merely repeats the title/description or names an implementation detail the reader does not need, remove it.

Treat an equal repeated panel containing a small identifier, oversized title, short description, borders, and empty space as a warning pattern rather than a reusable default. If removing its enclosure and micro-label leaves the meaning intact, prefer an open editorial row, typographic sequence, media relationship, or simpler list. Changing the class name, radius, color, or animation does not make the unsupported pattern authored.

Judge these choices across the complete page. A device can be defensible once and still become generic through repetition. The design system should permit editorial variation while preserving recognizable type, grid, color, and motion relationships.

An active reference's use of a device is contextual evidence, not a reusable token request. Before adding a system role, verify that the local use matches the observed content/function, frequency and density, scale and adjacency, interaction states, responsive transformation, and whole-page proportion. Do not promote a one-off project tag, ticket control, editorial caption, utility bar, or compact mobile action into a universal component family.

## Media Language

Define reusable decisions for:

- aspect-ratio families;
- portrait/landscape behavior;
- object-fit and focal positioning;
- image treatments, masks, borders, captions;
- video poster/autoplay policy;
- fallback appearance and missing media;
- mobile art direction.

Media rules must account for WordPress-provided dimensions and variable source quality. Reserve layout space and keep semantic images in image elements rather than decorative backgrounds.

### Page-level media rhythm

Design the page as a relationship between words, media, and space—not as text with occasional decoration. With content/SEO, classify the important sections as text-led, media-supported, media-led, or intentionally quiet, then vary scale and cadence according to the story. Appropriate structures can include inline figures, split text/media, offset crops, image sequences, galleries, product captures, documentary evidence, and large full-bleed or near-viewport image/video moments. Do not impose a media count; a text-only section is valid when deliberate, but an entire text-heavy page must not result from forgetting to plan the assets.

Record that classification as the page's primary-carrier map. A section may instead be carried by a genuine product/editorial artifact, meaningful data, or interaction when those are the actual subject. Reject a repeated pattern in which prose is paired with interchangeable abstract decoration that could move to another product after relabelling. Preserve intentionally quiet sections and do not use the carrier map as a mechanical alternation recipe.

For reference-led editorial pages, record a `Media rhythm` influence-map row covering which observed reference principles drive media scale, crop, sequencing, transition, and responsive transformation. A full-screen treatment must have a compositional purpose rather than serving as spectacle. Account for mobile browser chrome and safe areas, avoid trapping scroll, keep essential controls/content reachable, and prefer suitable `svh`/`dvh`/minimum-size behavior over assuming `100vh` is stable everywhere.

### Pending user- or CMS-supplied assets

When an image or video is planned but not yet available, reserve it instead of collapsing the layout into text or inventing substitute artwork. A placeholder may be a `div` or media-slot component during design and implementation, provided that it:

- reserves stable responsive geometry through an approved aspect ratio, intrinsic-size contract, or intentional minimum block size;
- records purpose, expected subject, format, orientation, focal/crop intent, source owner, and status such as `pending user/CMS asset`;
- uses a restrained background color, tone, texture, or simple placeholder treatment derived from project tokens and active references;
- remains visually distinguishable to the project team as pending and does not pretend to be a finished photograph, illustration, testimonial, product capture, or proof;
- avoids unrequested AI-generated imagery and unauthorized media copied from an inspiration site;
- defines whether the final asset is required, optional, or allowed to collapse to an approved no-media composition.

The placeholder is not the final semantic implementation. When meaningful media arrives, replace it with `<img>`/`<picture>` or accessible `<video>`/embedded media, preserve dimensions to prevent layout shift, provide the agreed alt/caption/transcript/controls, and apply responsive sources, poster, priority, lazy loading, and compression according to the asset's position and value. Decorative media may use CSS backgrounds or `aria-hidden`; informative media must not remain an empty `div`. A production missing-media state must be intentional—approved fallback, alternate composition, or omission—not an unexplained blank rectangle.

## Graphic and Illustration Art Direction

A prominent custom graphic is not neutral decoration. Treat an illustration, diagram, SVG field, 3D object, canvas, collage, data visualization, or animated visual system as a content-bearing art-direction decision even when it is hidden from assistive technology.

Write a brief before building each prominent graphic family:

- **role:** evidence, explanation, atmosphere, identity, or navigation;
- **source:** the specific project story, brand asset, real artifact, meaningful data, subject matter, or directly observed reference principle that justifies it;
- **concept and material:** what the motif means and why photography, illustration, typography, collage, data, SVG, 3D, or another medium communicates it best;
- **composition:** relationship to nearby copy/media, scale, crop, depth, negative space, and how it changes across desktop and mobile;
- **behavior:** what hover, click, drag, scroll, or ambient motion communicates, plus the still/reduced-motion state;
- **semantics:** useful alternative or nearby explanation when it carries information; `aria-hidden` only when it is genuinely redundant decoration;
- **originality and provenance:** what principle is transformed, what must not be copied, and the rights/source status of any asset.

Do not invent a graphic only because a hero has an open side or a section feels unfinished. First consider real photography/video, archival or product artifacts, meaningful data, expressive typography, a commissioned or project-specific illustration, or intentional whitespace. Human specificity comes from a legible point of view and meaningful restraint—not random asymmetry, organic blobs, hand-drawn squiggles, noise, or fake imperfections applied as a style filter.

Treat abstract technology shorthand as a warning pattern when circles, orbit/radar rings, arbitrary paths, nodes, neon glows, particles, grids, browser fragments, and small technical labels are combined without project evidence. These forms remain valid for real topology, real data, an astronomical/orbital subject, an established brand system, or a clearly transformed inspected reference. In explanatory graphics, every node, label, direction, and relationship must map to something true; in atmospheric graphics, motif and motion must still arise from the project's identity or subject rather than pretending to explain a system.

Reject or redesign a proposal when:

- it could move unchanged to an unrelated AI, crypto, agency, or SaaS page after swapping labels and colors;
- removing its labels reveals that the underlying image has no project-specific meaning;
- its purpose is only to fill empty space or make the page look innovative;
- its motion defaults to orbiting, pulsing, path drawing, or pointer following without expressing the concept;
- the active references changed only its palette, radius, or easing rather than its motif, medium, scale, crop, content relationship, rhythm, or behavior.

Review graphic repetition across the full journey. One supported visual device can still become mechanical when every section repeats it. Prefer a small authored visual language with deliberate variation and omission.

## Motion Tokens

Centralize common durations/eases only when motion shares a vocabulary:

```scss
:root{
    --duration-feedback: 160ms;
    --duration-enter: 520ms;
    --ease-standard: cubic-bezier(0.22, 1, 0.36, 1);
}
```

GSAP timelines may reference the same conceptual roles in JavaScript. Do not force every animation to the same duration; feedback, route transitions, and editorial reveals serve different purposes.

Every motion role needs a reduced-motion outcome that preserves final state and hierarchy.

## Component Contracts

A component belongs in the system when it has a stable meaning and reusable behavior, not merely similar markup. Document through code:

- semantic element/role;
- required and optional content;
- variants and sizes;
- interactive states;
- responsive behavior;
- accessibility name/state;
- supported media/content shapes.

Prefer bounded variants such as `variant="primary"` over arbitrary CMS-provided class/style strings.

## Evolving the System

When adding a token or variant:

1. Search for an existing semantic role.
2. Confirm the new value occurs or is expected in more than one meaningful place.
3. Name it by purpose, not current color/position.
4. Check every theme/inverted context and interaction state.
5. Replace true duplicates, not deliberate exceptions.

Do not refactor the entire visual system during a narrow component task unless the current architecture blocks the requested result.

## Review Checklist

- Do tokens encode a clear project concept rather than arbitrary literals?
- Are typography and copy measures resilient to real CMS content?
- Do semantic colors meet contrast in all states and inverted sections?
- Are grid, gutter, spacing, and media rules shared only where useful?
- Does the page have an intentional text/media/whitespace rhythm, including justified large or full-bleed moments, rather than becoming all text or forcing an image into every section?
- Does every pending media slot preserve the approved scale and responsive composition, identify a real asset request, avoid invented/unauthorized imagery, and define how semantic media or an approved no-media state will replace it?
- Does the surface/container system reflect the selected references and content, with every repeated card role justified rather than generated by default?
- Does every system device derive from reference evidence in the same role and context at comparable frequency, scale, interaction and responsive behavior, instead of a cherry-picked occurrence elsewhere in the pool?
- For an existing interface, do the current baseline and delta ledger correspond to visible structural differences in the rendered first viewport, section sequence, carrier/media rhythm, device density, shell and end state where change was required?
- Are compact labels, indices, code/data styles, metadata rails, dividers, process enclosures, action treatments, header, and footer distinct semantic roles used only where they improve comprehension and identity rather than as a page-wide technical aesthetic?
- Does the interface-device ledger include directly observed absences, and were unsupported panels, contextual pretitles/uptitles, visible labels, numbers, badges, standalone trailing emphasis/fact lines, code treatments, dividers, and button recipes omitted unless a real function justifies a minimal divergence?
- Does every prominent custom graphic have an explicit role, project/reference source, content relationship, responsive behavior, semantic treatment, and originality boundary—and would it remain specific if its labels and brand colors were removed?
- Can themes/modes change semantic roles without component rewrites?
- Are variants bounded and accessible?
- Does motion share an intentional vocabulary with a reduced path?
- Are new system decisions visible in the owning SCSS/component layer rather than duplicated inline?
