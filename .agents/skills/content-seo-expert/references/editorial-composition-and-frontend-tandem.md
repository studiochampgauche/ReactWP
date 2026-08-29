# Editorial Composition and Frontend Tandem

Use this reference whenever visible text and interface composition affect each other. Content/SEO and frontend work as a tandem from the brief through the rendered review; neither hands a supposedly finished artifact to the other at the end.

## Shared Outcome

The goal is not to make copy fit a predetermined box or to design empty containers before real content exists. The goal is one accessible, responsive editorial composition in which:

- the words answer the reader's task and preserve their meaning;
- semantic levels express the true document hierarchy;
- typography creates readable emphasis without falsifying that hierarchy;
- text length, line measure, whitespace, images, video, controls, and motion form one intentional rhythm;
- short, long, translated, missing, and editor-modified content remain usable;
- visible content, metadata, links, and structured data stay consistent.

## Joint Ownership

| Decision | Content/SEO leads | Frontend leads | Must be agreed together |
| --- | --- | --- | --- |
| Page job, audience, promise, proof, CTA | Yes | Advises on interaction/placement | Reading order and prominence |
| Heading meaning and hierarchy | Yes | Implements semantic/style system | Level, visual role, wrapping, responsive behavior |
| Copy length and variants | Drafts real content and meaningful shorter/longer variants | Defines resilient capacity and layout behavior | Where editing improves clarity versus where layout must adapt |
| Type scale, measure, rhythm | Supplies priority, density, tone, language variance | Defines font roles, sizes, line height, width, spacing | Readability and emphasis with real copy |
| Context labels, indices, metadata, code/data | Determines whether each item adds unique meaning and supplies truthful wording | Chooses semantic element, placement and visual treatment | Whether the device exists at all; repeated annotation density across the page |
| Images/media | Defines purpose, evidence, expected subject/source, supplied/pending/optional status, caption, alt/credit intent, textual relationship | Defines placement, ratio, scale, crop, focal behavior, placeholder, loading, interaction | Page-level media rhythm, reading order, large/full-bleed moments, overlay safety, responsive transformation and no-media fallback |
| Enriched modules | Defines why the module earns space and its content | Defines component behavior and states | Whether it should be a table, open row/list, card, FAQ, step, timeline or another structure; density and mobile behavior |
| SEO/head/schema | Defines truthful editorial specification | Implements visible/head/schema lifecycle where owned | Rendered content must match every representation |

Content/SEO does not prescribe arbitrary pixel sizes. Frontend does not rewrite claims or semantic meaning unilaterally. Either role may challenge the other's proposal with evidence from the real rendered page.

“Joint ownership” means joint decision and approval, not concurrent editing. The ownership ledger assigns one mutable custodian for the current wave. Under orchestration, the orchestrator is the default custodian; in a direct two-skill task, the primary agent is. Custody can be transferred explicitly, never duplicated. Frontend and content/SEO submit changes to that custodian and approve one revision identified by revision/date or another unambiguous version marker.

## Mandatory Collaboration Cycle

### 1. Content skeleton before layout hardens

Content/SEO supplies:

- page promise, primary action, section sequence, heading hierarchy, proof, links, and enrichment;
- real draft copy rather than lorem ipsum;
- expected content variability, locale expansion, editor flexibility, and missing-content behavior;
- media purpose, expected subject/source, supplied/pending/optional status, focal point, caption/credit/alt intent, and relation to nearby text; do not invent an asset simply to break up copy.

Frontend supplies an initial composition hypothesis: container/grid, typography roles, page-level media rhythm, media treatment, stable pending-asset slots, component states, responsive transformation, and accessibility/performance constraints.

### 2. Build the editorial composition matrix

Record the matrix custodian, current revision, and frontend/content approval status, then use one row per meaningful section or component:

```text
Custodian:
Revision/date:
Content/SEO approval:
Frontend approval:
```

| Page/module | Reader purpose and priority | Semantic element/level | Real copy and variability | Type role/measure | Media relationship | Desktop composition | Mobile/translated behavior | Constraints/status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Hero | Confirm value and primary action | One page `h1`, intro, CTA | Approved draft plus long-locale case | Display role, readable wrap, intro measure | Pending user image supports outcome; landscape source, focal point and full-bleed option recorded; stable placeholder until supplied | Text/media relationship | Reflow without hiding promise/CTA | Joint review pending |

“Variability” should describe observed or expected content, not impose an arbitrary SEO character limit. Include actual content, a realistic short case, a realistic long case, and the longest supported locale/editor case when material.

### 3. Render with real content early

Frontend implements a representative composition using the actual draft and edge variants. Content/SEO reviews the page in context rather than reviewing text only in a document.

Inspect together:

- first-screen comprehension and the complete reading journey;
- heading prominence versus true semantic level;
- font size, line height, line length, wrapping, orphans, density, and whitespace;
- relationship and reading order between copy, images, video, diagrams, controls, captions, and CTAs;
- page-level alternation between text-led, media-supported, media-led, large/full-bleed, and intentionally quiet passages; ensure neither a forgotten wall of text nor a mechanical image quota drives the result;
- whether each pending media placeholder reserves the agreed scale/crop across viewports, clearly maps to an asset request, and has an approved optional/no-media outcome;
- whether kickers, eyebrows, indices, badges, metadata, code-like text, divider rails, boxed steps, and shell utility copy add unique meaning or merely repeat and over-segment the content;
- whether action labels and hierarchy are specific to the reader's task instead of filling a conventional primary/secondary CTA pair;
- whether an image crop/overlay hides the subject or reduces contrast/readability;
- section, row/list and justified-card proportions with short/long/missing content, including whether an enclosure still earns its presence;
- mobile, intermediate, desktop, zoom, translated, and editor-modified cases;
- links, focus order, screen-reader meaning, reduced motion, loading/error media, and performance.

### 4. Negotiate, do not force

When the composition fails, identify the cause:

- revise content when wording is repetitive, vague, structurally misplaced, or longer than the reader's task requires;
- revise layout when the copy is necessary and the container, measure, breakpoint, media ratio, or interaction is too rigid;
- revise both when hierarchy or module selection is wrong;
- revise the CMS model when one field is being forced to serve incompatible semantic/layout purposes.

Do not solve fit by indiscriminately shrinking type, reducing line height, hiding paragraphs, clipping essential copy, turning text into an image, or forcing every locale to match one visual line count. `line-clamp` is acceptable only for a genuinely optional preview/summary with a clear path to the complete content and joint approval.

### 5. Joint rendered sign-off

Before final QA, both roles confirm:

- the final visible copy and editorial sequence;
- heading levels and visual hierarchy;
- type roles, sizes/ranges, measures, wrapping, and responsive behavior;
- text/media purpose, order, crop, caption, alt intent, and interaction;
- media source/status, page-level rhythm, large/full-bleed treatment, pending placeholder geometry, loading priority, and approved replacement or no-media fallback;
- long/short/empty/translated resilience;
- metadata/schema/internal-link consistency with the rendered page;
- the necessity and semantic accuracy of every repeated contextual label, index, technical treatment, divider rail, boxed process, action label, and header/footer utility message;
- remaining assumptions or `UNVERIFIED` viewports/content states.

Any content or layout change after sign-off invalidates the affected joint checks and requires a focused re-review.

## Typography and Hierarchy Rules

- Semantic heading level follows document structure, not desired font size. Style roles may differ from element levels when the component API preserves meaning.
- Maintain one clear page-level heading unless the actual application/document architecture justifies another structure.
- Use a type scale with intentional contrast, but validate optical balance using real words, accents, numbers, punctuation, and locale-specific wrapping.
- Keep body and supporting text readable at supported viewports and zoom. Small text is not a solution for dense content.
- Set measures appropriate to the font and task; editorial paragraphs, labels, data, captions, and display headings need different widths.
- Avoid fixed-height text containers when content is CMS-controlled. Prefer intrinsic growth and composition that can absorb variance.

## Context Labels and Technical-Looking Content

- Do not invent a kicker, eyebrow, category, status, number, short “anchor,” or code-like phrase merely to complete a component composition. Content/SEO must be able to state the reader benefit or semantic role it adds.
- If a heading and nearby prose already communicate the same information, prefer removing the extra label. Frontend should not preserve redundant copy only because a grid column or style token expects it.
- Use indices for true order, chronology, progress, ranking, or cross-reference identity. Use code semantics and monospace/code treatment for literal code, commands, identifiers, or data—not ordinary marketing language.
- A process should be boxed or diagrammed only when nodes, states, branches, dependencies, comparison, or interaction benefit from enclosure. Otherwise consider a direct narrative, image relationship, open sequence, or fewer stronger moments.
- Button copy must describe the action or destination. Do not manufacture a second CTA, directional microcopy, header status, or footer slogan solely to balance a layout.
- Review repetition at page level. A contextual label may be useful in one section while the same label-heading-detail recipe on every section makes the reading journey mechanical.

## Text and Media Rules

- Every media element has a purpose: evidence, explanation, atmosphere, navigation, or decoration. If the purpose cannot be stated, question whether it earns its cost.
- Assess the whole reading journey for media opportunities. A site should not become only stacked text because files are pending, but media is not mandatory in every section; record deliberate text-led and quiet passages alongside media-supported and media-led moments.
- When the user or CMS will provide media later, frontend reserves the jointly agreed ratio/scale/focal treatment with a stable placeholder `div` or media-slot component and content/SEO records the expected subject, source owner, status, caption/alt/credit intent, and relation to the copy. Do not generate an AI substitute or borrow reference-site media unless the user explicitly authorizes and supplies the rights.
- Once an informative asset is supplied, replace the placeholder with semantic image/video markup and its agreed alternative. If an optional asset never arrives, use the approved no-media composition rather than shipping an unexplained blank block.
- Large full-bleed or near-viewport media sections are valid editorial moments when their scale, crop, pacing, and transition serve the content and active references; they are not automatically decorative spectacle.
- Decide whether text precedes, follows, overlays, labels, or is independent from the media based on comprehension and reading order.
- Preserve the subject/focal point across responsive crops and hover/scroll transformations.
- Overlay text requires stable contrast across every image/video frame and interaction state; provide a structural treatment when the asset cannot guarantee it.
- Alt text, caption, credit, transcript, and nearby copy have distinct jobs; do not duplicate them mechanically.
- Motion may reveal or relate text/media but must not delay access to essential content or make meaning depend on pointer/scroll behavior alone.

## Required Handoff Evidence

Both roles reference the same authoritative matrix revision and include their approval, rendered viewports/states reviewed, real and edge content used, media asset/status inventory, pending placeholder and replacement/no-media decisions, agreed changes, screenshots/observations when available, and remaining unverified cases in their handoffs. They do not create separate matrix copies. QA must receive the authoritative revision, not two unrelated completion claims.
