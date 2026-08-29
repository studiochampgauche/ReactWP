# Shared Contracts and Handoffs

Use this reference before parallel edits. A short explicit contract is cheaper than reconciling several correct but incompatible implementations.

## Mission Brief

Create and maintain a compact brief in the working plan/conversation unless the user explicitly requests a durable project document:

```text
Outcome and primary users:
Acceptance criteria:
Explicit non-goals:
Delivery mode and public origin:
Journeys/routes/screens/locales:
Content and evidence available/missing:
Editorial composition matrix and frontend/content sync status:
WordPress/ACF domain model:
Route/API/public payload contracts:
Frontend templates/components/states:
Form field contract revisions and frontend/backend approvals:
Metadata/social/entity/schema contract:
Trust boundaries and permissions:
Rendering, cache, invalidation, migration:
Backend performance/capacity budget and baseline:
Active inspiration references and inspection status:
Role/file/contract ownership:
Dependencies and execution waves:
Verification and QA evidence required:
External actions not authorized:
```

Do not invent missing business facts. Mark assumptions and request a decision only when different answers would materially alter the product or public behavior.

## Ownership Ledger

Assign ownership by mutable surface for the current wave:

| Surface | Owner | Consumers/reviewers | Status/dependency |
| --- | --- | --- | --- |
| ACF group and PHP registration | Backend | Content/SEO, frontend, security | Contract approved before consumer binding |
| Route/API object | Backend | Frontend, SEO, security | Version/empty/error/language shape fixed |
| Backend performance/capacity budget | Backend | Security, consumers, QA | Baseline/target/workload/cache topology/evidence recorded; security invariants fixed |
| Submitted/persisted form field contract | Backend by default; explicitly transferable | Frontend co-approves; security and QA review | One revision for display/editing, transport, canonical value, errors, sensitivity, and fixtures |
| React component and SCSS | Frontend | Content/SEO, QA | Depends on route contract |
| Visible copy/meta/entity graph | Content/SEO | Frontend, backend, security | Facts/evidence identified |
| Editorial composition matrix | Orchestrator by default; explicitly transferable | Frontend and content/SEO co-approve one revision | Custodian/revision/approval recorded; never concurrently edited |
| Raw HTML/JSON-LD boundary | Backend or frontend sink owner | Security, QA | Sanitization/serialization contract required |
| Compliance matrix | QA | Orchestrator and all owners | Read-only evidence |

Ownership is task-specific; do not rely on path stereotypes when a file mixes concerns. A worker may inspect any relevant file but edits only owned surfaces.

## Shared Contract Minimums

### Delivery and routing

- integrated theme, external headless, or both;
- public base/canonical origin and route ownership;
- route names, `react_template`, `route.lang`, query/search behavior, 404/preview/auth states;
- client/static/server rendering requirements and hydration/navigation behavior.

### Data and content

- field/key names, scalar/object/list types, required/optional/null/empty behavior;
- for every submitted/persisted field: visible/editing grammar, allowed characters/positions, native attributes, limits, locale assumptions, accepted transport, canonical value, stable errors, sensitivity, and shared accepted/rejected fixtures;
- language, HTML versus plain text, allowed markup, media shape, links, references, pagination, errors;
- editor workflow, visibility, `show_in_rest`, public/private exposure, versioning/migration;
- content source/evidence, page job, copy ownership, internal relationships, and enrichment modules.

### Frontend behavior

- semantic structure, states, responsive transformations, input modes, focus, reduced motion, media priority, and performance expectations;
- form formatting across typing/paste/autofill/mobile/IME/programmatic input, accessible instructions/errors, caret/editing behavior, and mapping of backend field error codes;
- joint editorial composition matrix: section purpose/priority, semantic level, real and edge copy, type role/measure, media relationship, desktop/mobile/locale behavior, and approval state;
- which elements own head/schema updates and which route values they consume;
- active inspiration principles and originality/accessibility constraints.

### Security and operations

- sources, validation, authorization, normalization/sanitization, transport/cache, final sinks;
- server-side revalidation of every form value independent of the client, plus bounds, canonicalization, CSRF/authorization, safe error handling, storage and output behavior;
- nonce/session/token/CORS topology, URL/host/path allowlists, resource bounds, public/private cache identity;
- representative volumes/concurrency; cold/warm/maximum-cost latency; query/time/memory/payload budgets; remote/job retry/fan-out limits; object-cache/CDN/queue/cron assumptions; before/after evidence;
- invalidation dependencies, migration/rollback, logs/errors, secrets, external systems, and deployment boundary.

### SEO and discovery

- visible title/H1/content promise, localized fields, description, OG/social image/type, canonical/hreflang/robots, internal links;
- entity identifiers, schema source properties, JSON-LD ownership, sitemap owner, direct/client navigation behavior;
- exact boundary between existing `reactwp-seo` behavior and project-owned extensions.

## Contract Change Protocol

An agent discovering a contract problem must:

1. stop edits that depend on the disputed shape;
2. report the current contract, proposed change, reason, consumers, compatibility/migration/cache/security impact, and tests affected;
3. wait for orchestrator resolution when another active owner/consumer is affected;
4. update its work only after the orchestrator records the decision and notifies affected roles.

Minor private implementation details inside one owned surface do not need team approval. Public/shared keys, types, routes, fields, rendered semantics, metadata, security controls, and cache behavior do.

## Frontend and Content/SEO Sync Protocol

The two roles keep separate file ownership and jointly approve the editorial composition decisions. The orchestrator is the default single write custodian/source of truth for the matrix; another custodian can be named in the ownership ledger for a wave, but there is never more than one. Both role handoffs reference the same revision instead of embedding divergent copies.

1. content/SEO provides real copy, hierarchy, priority, variability, locale cases, proof, links, and media intent;
2. frontend proposes semantic implementation, type roles/measure, layout, media placement/crop, states, and responsive behavior;
3. the named custodian records a new matrix revision and both roles approve it before either hardens dependent structures;
4. frontend renders actual and edge content; both roles review the result and negotiate content/layout changes;
5. both provide rendered sign-off before QA, including unverified viewports/locales/states;
6. later content or layout changes invalidate affected sign-off rows and trigger a focused sync.

The roles may communicate directly when available, but every shared decision returns to the custodian and authoritative matrix. File ownership remains exclusive; co-approval does not create concurrent write ownership.

## Frontend and Backend Form Sync Protocol

For any submitted or persisted field, both roles follow `backend-expert/references/form-field-contracts.md` and approve one revision. Backend is the default single write custodian; frontend may propose changes but does not maintain a private field grammar.

1. backend records the domain meaning, required/empty behavior, bounds, locale assumptions, accepted transport, canonical representation, server validation/normalization, stable field error, sensitivity, and direct-request cases;
2. frontend records the native attributes, visible/editing format, all input-path behavior, accessible instructions/errors, transport production, and browser/device cases;
3. both agree on accepted/rejected fixtures before implementing separate PHP and JavaScript rules;
4. backend proves malformed and direct requests cannot bypass the contract; frontend proves typing, editing, paste, autofill, mobile, IME, and server-error mapping;
5. both test one canonical storage/use and read/render round trip and approve the same revision before QA;
6. a change to grammar, limits, locale, transport, canonicalization, sensitivity, or error codes invalidates both approvals and dependent evidence.

Security reviews trust boundaries without becoming the form-contract writer. File ownership remains exclusive, and approval never permits concurrent edits to frontend/backend files.

## Worker Task Packet

Every delegated task should include:

```text
Role and primary skill to load:
Mission outcome and current wave:
Exact owned files/contracts:
Read-only files/consumers to inspect:
Shared contract and assumptions:
Dependencies/blockers:
Required edge/failure cases:
Required verification:
Forbidden scope/external actions:
Handoff format and recipient:
```

Do not give multiple workers vague instructions such as “handle everything related to the feature.”

## Handoff Format

Each worker returns:

```text
Status: complete / partial / blocked
Outcome delivered:
Decisions and assumptions:
Files changed:
Shared contract consumed/changed:
Security/content/accessibility/cache implications:
Verification run and exact result:
Unverified areas:
Follow-up required from another owner:
```

The orchestrator reads the actual changes and evidence; a handoff is not an automatic acceptance.

## Dirty Worktree and Integration

- Inventory tracked and untracked changes before assigning ownership.
- Treat existing changes as user-owned unless their origin is known.
- Do not let agents reset, overwrite, move, or reformat unrelated work.
- If a required file already contains overlapping edits, give it one owner and have other agents provide a patch recommendation/contract rather than concurrent writes.
- Integrate producer/consumer changes together, then run focused and cross-layer verification.
- Generated `dist/` remains output; author fixes in `src/` or `configs/` and regenerate only when required.

## Integration Checklist

- Every worker stayed within ownership or reported approved transfers.
- Shared route/data/content/security/SEO contracts match every producer and consumer.
- Frontend and backend handoffs reference the same authoritative form-field contract revisions, shared fixtures, and approvals; direct-request and browser-input results converge on the same canonical values and stable errors.
- Every material backend performance change has a representative baseline/target/result, bounded maximum-cost path, correct cold/warm/stale/invalidation behavior, and repeated security/privacy/abuse/failure evidence.
- Frontend and content/SEO handoffs reference the same authoritative matrix revision, named custodian, and rendered sign-off.
- No duplicate router, loader, scroller, head manager, content source, cache, or field registration was introduced.
- Pre-existing user changes remain intact.
- Empty/error/unauthorized/translated/responsive/navigation/cache states compose correctly across layers.
- Focused worker tests and orchestrator spanning checks pass.
- QA receives the integrated workspace, mission brief, acceptance criteria, and raw evidence rather than conclusions alone.
