# ReactWP Agent Instructions

## Orchestrated cross-layer work

For a complete site, application, dashboard, portal, management system, or substantial change spanning two or more expert domains, load and follow:

- `.agents/skills/reactwp-orchestrator/SKILL.md`

The user may describe only the desired outcome; they do not need to name roles or skills. The primary agent owns discovery, shared contracts, task decomposition, coordination, integration, verification, and the final unified result.

For substantial independent workstreams, the orchestrator must delegate bounded work to specialized sub-agents when available and useful. A typical first wave runs backend, frontend, and content/SEO work in parallel after shared contracts and exclusive file ownership are established. Run independent QA after integration; launch a dedicated security worker when the orchestrator's risk router requires it. Never give two agents concurrent write ownership of the same file, field, route key, component, or shared contract.

Frontend and content/SEO form a mandatory tandem for user-facing editorial experiences. They may work in parallel, but must share one authoritative editorial composition matrix and synchronize before typography/layout hardens, after real content is rendered, and before QA. The orchestrator is its default single write custodian; custody may be transferred in the ownership ledger but never duplicated. Content/SEO owns meaning, evidence, hierarchy, content variability, and media intent; frontend owns semantic implementation, type system, measure, composition, responsive behavior, media treatment, and accessibility. Both approve the same matrix revision. Neither may solve disagreement unilaterally by forcing copy into a fixed design or changing meaning to fit a component.

Do not use multi-agent ceremony for a small single-domain task. Delegation does not expand the user's scope or authorize deployment, publication, destructive migration, account changes, purchases, external messages, or unrelated cleanup. Each worker must follow the expert skill and references routed by its assigned behavior, preserve user-owned worktree changes, and return an evidence-backed handoff to the orchestrator.

## Frontend work

For any task that designs, implements, refactors, reviews, or optimizes the ReactWP frontend, load and follow:

- `.agents/skills/frontend-expert/SKILL.md`

This includes React components and templates, SCSS, responsive behavior, accessibility, frontend performance, GSAP, loaders, page transitions, smooth scrolling, media loading, and work based on visual references or Awwwards sites.

When visible editorial content is involved, work in tandem with `content-seo-expert` using `.agents/skills/content-seo-expert/references/editorial-composition-and-frontend-tandem.md`. Validate typography, line measure, hierarchy, content length/variance, text-media composition, responsive/translated cases, and the final rendered reading journey together.

Read only the supporting references routed by the skill for the task at hand. The GSAP files under `.agents/skills/frontend-expert/references/gsap/` are specialized references; do not load all of them when the task only needs one GSAP feature.

When a live website is used as inspiration, follow the complete reconnaissance protocol in `references/inspirations.md` before deriving a design direction. On every first or later use of that reference, inventory the whole public site, perform the mandatory top-to-bottom homepage and interaction pass, inspect distinct secondary-page templates, and record any route or interaction that could not be verified.

Active inspirations must materially affect section architecture, container/surface grammar, typography, media relationships, spatial rhythm, responsive transformation and interaction—not merely colors, radii or motion polish. Cards are never the default wrapper. Every repeated card/surface requires a content or interaction reason plus support from the project brief or observed references; otherwise prefer the appropriate open row, list, editorial grid, full-bleed media, table, timeline, accordion or other content-led structure. Do not replace generic cards with another generic system made of repeated micro-labels, decorative indices, pseudo-code, metadata rails, divider grids, boxed process steps, capsule CTA pairs, or technical-looking headers and footers. Those devices are optional and must add unique semantic or interaction value. For every reference-led page, the influence map must contain separate `Header` and `Footer` rows tied to directly observed reference evidence or a documented project-specific decision; neither may be treated as neutral shell chrome. Run the component-grammar and genericity tests in `references/inspirations.md` before declaring visual work complete.

For reference-led work, observed absence is design evidence. Maintain one interface-device ledger covering action treatment, contextual pretitle/uptitle/kicker text, visible labels, indices/numbers, badges/status/tags, standalone trailing emphasis/fact lines, literal code/data styling, panels/cards/surfaces, and divider/metadata systems. If a device family is absent from all active inspected references and no real content, state, order, or interaction requires it, omission is the default; do not invent it as polish. Any divergence needs a documented product reason and the smallest fitting treatment. Preserve necessary semantic form labels and real application controls—the rule governs unsupported presentation, not accessibility or correct HTML.

Treat every prominent custom graphic or illustration as first-class art direction, never as filler for an empty hero or section. Its role, project/content evidence, transformed reference principle, relationship to adjacent copy/media, responsive behavior, motion, semantics, and originality boundary must be recorded in a dedicated influence-map row before implementation hardens. Reject interchangeable abstract technology imagery, ornamental diagrams, and motion that merely make a page look designed; prefer real media, project artifacts, meaningful data, typography, commissioned/custom illustration, or intentional whitespace when those tell the story more honestly.

Plan an explicit page-level media rhythm with content/SEO instead of allowing a site to become only stacked text and interface blocks. Assess where supplied or planned photography, video, editorial imagery, product captures, artifacts, or other media improve evidence, comprehension, emotion, or pacing; some sections may intentionally remain text-only. Reference-led editorial pages need a `Media rhythm` influence-map row, including any justified full-bleed or near-viewport media moment. When the user will provide an asset later, preserve its intended scale and composition with a stable responsive placeholder `div` or media-slot component using a restrained project token/background and a documented aspect ratio/focal intent. Do not generate an AI substitute unless explicitly requested. Replace the placeholder with semantic `<img>`, `<picture>`, or `<video>` markup when meaningful media arrives; a placeholder is a production asset state, not permanent alternative content.

Before fixing, pinning, or making content sticky, prove that every visible state fits inside the actual scrollport after persistent headers, safe areas, padding, and browser chrome are deducted. Test real and edge CMS content, font loading, short viewport heights, landscape, zoom/text enlargement, and mobile viewport changes—not width alone. Essential content may appear sequentially during a pin only when every item is brought fully into view before release and remains available in DOM/reading order and reduced motion. If the natural content or any state is taller than the available space, recompose it, pin only a smaller visual while text flows, split it into fitting stages, or disable pin/sticky for that condition; never conceal the failure with `overflow: hidden`, clipping, an inaccessible nested scroller, or indiscriminate type shrinking.

For React HTML strings, use JSX text for plain content. When already trusted and sanitized HTML must be rendered unchanged, prefer a small explicit `dangerouslySetInnerHTML` boundary. Use `html-react-parser` only when rendering requires DOM-to-React transformation through `replace` or `transform`, such as replacing links/images, removing nodes, changing attributes, or injecting components. Neither API sanitizes HTML; WordPress/ACF/API content must be sanitized against its allowed HTML policy before it reaches either sink.

## Backend work

For any task that designs, implements, refactors, reviews, or debugs the ReactWP backend or its data contracts, load and follow:

- `.agents/skills/backend-expert/SKILL.md`

This includes WordPress hooks and lifecycle, plugins and mu-plugins, custom post types and taxonomies, queries and menus, ACF field groups and Local JSON, ReactWP route/bootstrap payloads, REST endpoints, headless consumers, integrated React theme data, rendering strategy, caches, invalidation, migrations, performance, scalability, abuse resistance, and backend verification.

Choose the delivery mode explicitly: the integrated ReactWP theme and an external headless frontend share content concepts but do not have the same bootstrap, routing, authentication, or deployment ownership. Read only the backend references routed by the skill.

When backend work changes a trust boundary, also use `security-expert`. For latency, database/query cost, payload size, concurrency, cache efficiency, background jobs, integrations, capacity, or resource-exhaustion concerns, follow `.agents/skills/backend-expert/references/performance-and-scalability.md` and preserve every security invariant while optimizing. When backend work includes React component, styling, accessibility, frontend performance, or motion work, also use `frontend-expert`.

For any project-owned endpoint, account/authentication flow, private object/user/tenant access, mutation, upload, integration, or dependency change, both backend and security must read `.agents/skills/security-expert/references/common-ai-backend-security-failures.md`. Every private object contract must prove that changing an ID, slug, UUID, filename, parent reference, or other locator from user A's resource to user B's does not grant access through detail, list, nested, bulk, cache, download, update, or delete paths. Every mutation must use an explicit writable-field map and prove that protected/unknown fields cannot cause role escalation or mass assignment.

## Form fields and submissions

For any user-editable field that is submitted or persisted, load both `frontend-expert` and `backend-expert`, plus `security-expert` for the trust boundary, and follow `.agents/skills/backend-expert/references/form-field-contracts.md`. Maintain one authoritative versioned field contract covering visible/editing format, allowed characters and positions, required/empty behavior, limits, locale assumptions, transport grammar, canonical value, backend validation/normalization, accessible errors, sensitivity, and shared fixtures. Backend is the default custodian; frontend and backend approve the same revision.

Frontend must apply the approved field behavior to every input path, including typing, paste, autofill, mobile and assistive input, while preserving accessible editing and leaving values such as passwords untouched when their contract requires it. Backend must independently reject invalid direct requests and produce the documented canonical value. Client filtering, masks, native input types, patterns, and React validation are usability controls, never substitutes for server validation, authorization, CSRF protection, normalization, or output escaping.

## Content and SEO work

For any task that plans, creates, enriches, optimizes, audits, or implements website content and SEO, load and follow:

- `.agents/skills/content-seo-expert/SKILL.md`

This includes audience and search intent, content strategy and briefs, page copy, enriched editorial modules, information architecture, internal links, titles and descriptions, Open Graph/social previews, canonical/hreflang/robots recommendations, entities, Schema.org/JSON-LD, content measurement, and work with the bundled `reactwp-seo` plugin.

Visible content must be designed in tandem with `frontend-expert` using `.agents/skills/content-seo-expert/references/editorial-composition-and-frontend-tandem.md`. Content is not complete until its hierarchy, real length and edge variants, typography, text-media relationship, responsive behavior, and rendered reading journey have been reviewed jointly.

Use `content-seo-expert` to define the editorial and SEO contract. Also use `backend-expert` for WordPress/ACF fields, plugin/PHP hooks, route or headless payloads, sitemaps, and migrations; use `frontend-expert` for rendered semantics, editorial components, responsive layouts, accessible media, and route-aware metadata/schema behavior; use `security-expert` for raw HTML, head/URL/JSON-LD output, public data, permissions, or other trust boundaries. Read only the references routed by each skill.

## Security-sensitive work

For any task that creates, changes, reviews, or debugs a trust boundary, load and follow:

- `.agents/skills/security-expert/SKILL.md`

This includes input handling, PHP or React output, raw/rich HTML, URLs, REST routes, permissions, nonces, authentication, previews, CORS, database queries, uploads, filesystem access, external requests, SSR/static rendering, public/private caches, security headers, secrets, dependencies, and deployment configuration.

When a frontend task is also security-sensitive, use both `frontend-expert` and `security-expert`. Read only the security references routed by the security skill. Reuse ReactWP's existing guards at the boundary they protect, and implement the residual controls the framework deliberately leaves to project code.

When a backend optimization changes caller-triggerable cost, queries/SQL, cache identity or scope, concurrency, batching, retries, jobs, rate limits, external calls, rendering or failure behavior, use both `backend-expert` and `security-expert`. Performance evidence is incomplete until the affected permission, privacy, cache-isolation, invalidation, abuse and failure paths are reverified.

Do not accept AI-generated security-sensitive backend code from appearance, a build, a scanner, or an agent assertion alone. Require the applicable common-failure-checklist rows, an actor/action/resource review, direct unauthorized and malformed requests, user-A/object-B substitution, protected-field mass-assignment cases, safe error evidence, and the focused ReactWP/project tests before approval.

## Quality assurance work

For any final QA, release-readiness check, compliance review, regression audit, or request to verify that the expert skills were fully respected, load and follow:

- `.agents/skills/quality-assurance-expert/SKILL.md`

The QA skill must evaluate `frontend-expert`, `backend-expert`, `security-expert`, and `content-seo-expert` for every audit, then read all supporting references routed by the affected behavior. It may declare `100% compliant with applicable verified requirements` only when every applicable documented requirement has direct passing evidence and no applicable area remains failed or unverified. A successful build, test suite, visual impression, or agent assertion alone is insufficient.

QA requests are audit-only unless the user explicitly asks to fix findings. When fixes are authorized, use the owning expert skill for each correction and repeat the affected QA gates afterward.

## Repository boundaries

- Author source changes in `src/` and build tooling changes in `configs/`.
- Treat `dist/` as generated output. Do not hand-edit it.
- Run Node/npm commands from `configs/`.
- Preserve ReactWP's template registry, route transition, loader, rendering, cache, and media contracts unless the task explicitly changes those contracts.
- Keep client, static, and server rendering compatible when touching shared templates or components.
- Never place secrets, licenses, tokens, private download URLs, or credentials in source or documentation.

## Documentation synchronization

- Every public API, component, runtime behavior, configuration, workflow, or developer-facing change in ReactWP must update the relevant source documentation in the sibling `../reactwp-website/src/docs/` checkout in the same task. If that checkout is unavailable, report the missing documentation update instead of encoding a machine-specific path.
- A changelog entry alone does not replace the user-facing documentation update.
- After changing the documentation, run its Docusaurus build from the sibling `../reactwp-website/configs/` directory and report any validation that could not be completed.

## Verification

Use the smallest relevant verification for the change. For frontend implementation this normally includes a theme build and focused tests; for runtime, rendering, animation-lifecycle, security, or build-pipeline changes, run the corresponding tests documented in the skill and repository scripts.
