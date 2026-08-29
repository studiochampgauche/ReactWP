# Task Routing and Execution Waves

Use this reference to decide whether delegation helps, which roles are active, and which work can safely happen at the same time.

## Size the Mission

| Mission shape | Default execution |
| --- | --- |
| Read-only explanation or narrow review | Orchestrator/primary agent reads the applicable expert; no implementation workers |
| Small single-domain change | Work directly with that expert skill; focused verification |
| Two substantial domains with a stable interface | Delegate one bounded domain while the orchestrator handles or delegates the other |
| Three-domain feature or substantial modification | Backend, frontend, and content/SEO workers in parallel after a shared contract |
| Complete site, app, dashboard, portal, or management system | Product discovery and contract wave, parallel implementation waves, integration, security as needed, independent QA |
| High-risk focused feature | Owning implementation role plus dedicated security review; QA afterward |

Scope by behavior, not file count. One file can cross rendering, security, SEO, cache, and content boundaries; many mechanical files can still form one workstream.

## Role Router

### Backend worker

Activate for WordPress/ACF models, plugins, PHP, hooks, routes, public/headless APIs, submitted/persisted field validation and canonicalization, query/payload performance, rendering configuration, cache/invalidation, integrations/jobs, scalability, migrations, or data contracts.

### Frontend worker

Activate for React templates/components, SCSS, accessibility, responsive layout, media, form formatting/input/error behavior, client navigation, head synchronization, performance, loaders/transitions/scrollers, interaction, or motion.

### Content/SEO worker

Activate for product/page information architecture, copy, CMS editorial requirements, intent, internal links, metadata, robots, canonical/hreflang, social output, entities, schema, or content evidence.

### Security worker

Security expertise is always loaded by any role crossing a trust boundary. Launch a separate security worker when the mission includes authentication/authorization, custom public or sensitive REST, previews, uploads/files, SQL, external requests, raw HTML/head/JSON-LD, SSR/SSG boundaries, public/private caching, material performance/capacity changes to attacker-triggerable work, secrets, CORS, security headers, dependency acquisition, or deployment topology with material risk.

### QA worker

Activate after integration for every full product or explicitly requested compliance/release gate. For smaller implementation, use it when impact/risk spans domains, regression evidence is nontrivial, or the user asks for proof of complete expert compliance.

## Dependency-First Waves

Parallelize only after identifying dependencies:

```text
Wave 0: inspect -> mission brief -> shared contracts -> ownership
Wave 1: backend contract/implementation | frontend shell/design system | content/SEO brief
Wave 2: frontend data integration | backend derived endpoints/cache | content population/meta/schema
Wave 3: orchestrator integration -> spanning verification
Wave 4: dedicated security review when required + preliminary QA inspection when useful
Wave 5: security handoff -> owner corrections -> re-integration -> final/invalidated QA checks
```

These are dependency waves, not mandatory ceremonies. Collapse them for smaller tasks. If frontend cannot proceed without an exact payload, stabilize that payload first. Frontend can still work on semantic structure, states, tokens, and verified mock fixtures only when the fixture is explicitly the agreed contract.

When editorial decisions materially affect hierarchy, SEO, CMS variability, responsive measure, media, structured data, or layout, frontend and content/SEO are separate file owners but a paired editorial workstream. Schedule their composition-matrix sync before layout/content structures harden, their rendered-content review during implementation, and their joint responsive/locale sign-off before QA. They may communicate directly or through the orchestrator, but every shared decision must be recorded in the mission contract and relayed to backend when it changes fields or payloads. Isolated wording fixes that preserve meaning, metadata, and composition do not require this workstream.

Frontend and backend are separate file owners but a paired form-contract workstream for every submitted or persisted field. Stabilize the backend-custodied contract and shared fixtures before parallel implementation, synchronize after both implementations exercise those fixtures, and complete a browser-input plus direct-request canonical round trip before QA. Security reviews the trust boundary; it does not replace either role's implementation.

## Available Concurrency

- Never exceed the execution environment's available agent slots.
- Keep the primary orchestrator active to resolve decisions and integrate results.
- Prefer one backend, one frontend, and one content/SEO worker in the main parallel wave.
- Run QA in a later slot so it reviews stable integrated evidence independently.
- If dedicated security must run early, replace or delay the least-independent implementation stream rather than oversubscribing or combining conflicting ownership.
- Security and QA may inspect concurrently, but QA keeps affected rows `UNVERIFIED` and withholds its final verdict until the security handoff and all resulting corrections are integrated.
- Reuse an existing role for corrections instead of starting a second writer in the same domain.

## Full Product Discovery

For a complete site/app/system, settle at least:

- audiences, roles, permissions, public/private journeys, and success outcomes;
- integrated theme versus external headless topology;
- pages/screens, navigation, information architecture, and locale strategy;
- WordPress content types, taxonomies, ACF/editor workflow, operational data, and integrations;
- route/API/public payload shapes and error/empty/pagination behavior;
- rendering modes, cache scope, invalidation, preview, and deployment constraints;
- content evidence, conversion paths, metadata, social output, entities, and schema;
- visual direction, active inspiration references, accessibility, media, motion, and performance budgets;
- security boundaries, abuse/resource limits, secrets, and sensitive data;
- acceptance criteria, migrations, tests, runtime evidence, and QA gate.

## Inspiration Routing

When the user supplies reference sites, they are active unless the brief narrows them. When the user supplies no references and a visual direction is needed, the frontend worker may select a small relevant subset from the default pool in `frontend-expert/references/inspirations.md` based on the audience and product character.

Every selected live reference receives the proportional site-wide, page-level, or component-level reconnaissance defined there. A complete public inventory and homepage scroll/interaction pass are mandatory for site-wide work; narrower work inspects only the relevant page/component, persistent context, variants, and states. Do not silently average all default references or claim behavior from screenshots alone. Record which references were selected, the chosen scope, and why.

## Authority and External State

Delegation does not authorize production publication, WordPress content mutation outside the requested implementation, account creation, form submission, purchases, emailing, third-party configuration, destructive migration, or deployment. Keep workers within the user's stated systems and normal implementation steps; return for direction when a new material choice or authority is required.
