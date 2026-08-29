---
name: backend-expert
description: Design, implement, review, secure, and optimize ReactWP backend work across WordPress, ACF, PHP, REST, form validation, route payloads, queries, rendering/cache, performance, scalability, and integrated or headless consumers. Use for content models, hooks, plugins, types/taxonomies, field groups, endpoints, submitted data, bootstrap data, integrations, migrations, capacity, or backend-to-frontend contracts. Do not use for frontend-only visual or motion work.
---

# Backend Expert

Build WordPress backends that preserve ReactWP's route, rendering, cache, and public-data contracts. Treat WordPress as the content and domain system, ACF as a deliberate content schema, and ReactWP as the adapter that exposes that schema to either the integrated React theme or a separate headless application.

## Operating Principles

- Inspect the relevant source and existing hook before adding a parallel service. ReactWP's current code is the authoritative contract.
- Author PHP and WordPress source in `src/`; author build tooling in `configs/`; never hand-edit `dist/`.
- Choose the delivery mode before designing a data path: integrated ReactWP theme, external headless consumer, or intentionally both.
- Keep content models stable, named by meaning, and independent of a single visual layout. Define return shapes and empty states before wiring React.
- For every submitted or persisted form field, own one authoritative versioned field contract with frontend approval. Define the visible/editing grammar, accepted transport, canonical value, limits, locale assumptions, validation, normalization, stable errors, sensitivity, and shared fixtures before either side implements its rules.
- For every private read or mutation, define the actor/action/resource authorization contract before the endpoint or service. Caller-supplied IDs and other references locate candidates; only a server-side check against the canonical object, owner/tenant/parent, lifecycle state, and narrow capability authorizes access.
- For every mutation, define an exact writable-field map. Map accepted fields explicitly; derive privileged relationships server-side and keep role/capability/owner/tenant/status/security changes out of generic create/update/profile payloads.
- Prefer WordPress hooks, namespaced project services, registered content types, `WP_Query`, REST schemas, and ReactWP filters over direct core modification or ad hoc globals.
- Use stable ACF group/field keys, keep one source of truth for field definitions, and understand that only active groups with `show_in_rest` enter ReactWP route data automatically.
- Extend `route.data`, public settings, or a versioned custom endpoint according to ownership and lifetime; do not put every value into the global bootstrap.
- Return JSON-serializable primitives and arrays. Avoid leaking raw `WP_Post`, `WP_Term`, `WP_User`, ACF internals, or database-shaped records into a public contract.
- Define a measurable performance budget for material request, query, payload, cache, rendering, integration, or job changes. Measure representative cold/warm and maximum-cost paths before and after; do not infer scalability from a build or a small local happy path.
- Treat cache identity and invalidation as part of every data contract. A new dependency without an invalidation path produces stale static or SSR output.
- Treat resource bounds and abuse resistance as security requirements. Never improve latency or cache hit rate by weakening validation, authorization, CSRF, privacy partitioning, rate limits, safe queries/requests, output shaping, or failure behavior.
- When an optimization changes caller-triggerable cost, SQL/query behavior, cache identity/scope, concurrency, batching, retries, jobs, remote work, rendering or failure behavior, also load `security-expert` and repeat the affected security paths after measuring performance.
- For rich WordPress/ACF HTML, preserve canonical content, define the allowed markup, and sanitize the public value on the backend. A React consumer may use `dangerouslySetInnerHTML` for unchanged sanitized markup; reserve `html-react-parser` for consumers that actually replace or transform nodes.
- When input, permissions, authentication, private data, files, SQL, external requests, HTML, URLs, previews, CORS, or public/private caches are involved, also load `security-expert`.
- Treat any AI-generated backend at a trust boundary as incomplete until the security expert's common failure checklist and focused negative tests have direct evidence. In particular, every object endpoint needs a user-A/object-B substitution test, and every mutation needs protected/unknown-field mass-assignment tests.
- When the task includes React implementation, styling, accessibility, performance, media, or motion, also load `frontend-expert`.
- When the task includes editorial strategy, page copy, on-page SEO, metadata recommendations, internal links, or entity/schema modeling, also load `content-seo-expert`; backend owns the WordPress/ACF/plugin/payload implementation of that contract.
- When the user requests final QA, release readiness, or proof that all expert requirements were respected, also load `quality-assurance-expert`; backend remains the source of backend requirements while QA owns the evidence matrix and verdict.
- When a substantial mission spans multiple expert domains, also load `reactwp-orchestrator`; follow its mission brief, exclusive file/contract ownership, coordination, and handoff rules rather than changing shared payloads or contracts unilaterally.

## Delivery Mode Decision

| Concern | Integrated ReactWP theme | External headless frontend |
| --- | --- | --- |
| Request routing | WordPress resolves the initial request; `RouteService` resolves client transitions | External application/router owns requests and navigation |
| Initial data | `Bootstrap::payload()` embedded in `#reactwp-bootstrap` | `/reactwp/v1/bootstrap` and focused endpoints |
| Route data | Shared route shape from `RouteResolver` | Public normalized route shape from `PublicPayload` |
| React template | ReactWP `TemplateRegistry` | Consumer's own route/component mapping |
| Rendering | Per-template `client`, `static`, or `server` | Consumer framework decides rendering |
| Authentication | Same-origin WordPress session by default | Origin, cookies/token strategy, nonce, and proxy topology must be explicit |
| Cache ownership | ReactWP client/render/static caches | ReactWP API caches plus consumer/CDN/framework caches |

Support both modes only when the feature genuinely needs both. Keep their shared domain shape stable while adapting transport- and runtime-specific concerns at the boundary.

## Reference Router

Read only what the current task needs:

- WordPress load order, project placement, hooks, plugins, mu-plugins, admin behavior, and extension boundaries: [wordpress-architecture.md](references/wordpress-architecture.md)
- Custom post types, taxonomies, queries, archives, menus, options, and multilingual considerations: [content-types-queries-and-menus.md](references/content-types-queries-and-menus.md)
- ACF field design, stable keys, return formats, option pages, Local JSON, ReactWP exposure, and schema evolution: [acf-content-modeling.md](references/acf-content-modeling.md)
- `rwp` helpers, `RouteResolver`, `Bootstrap`, `PublicPayload`, filters, route shape, and backend-to-frontend payload ownership: [reactwp-runtime-and-payloads.md](references/reactwp-runtime-and-payloads.md)
- Connecting ACF/WordPress data to the bundled ReactWP theme, template registry, initial rendering, navigation, and rich content: [integrated-react-theme.md](references/integrated-react-theme.md)
- Connecting ReactWP to an external React, Vue, Svelte, Astro, Next, or other headless consumer: [headless-api-and-consumers.md](references/headless-api-and-consumers.md)
- Designing custom REST routes, request schemas, permissions, response envelopes, errors, pagination, and contract changes: [custom-rest-contracts.md](references/custom-rest-contracts.md)
- Preventing role escalation, IDOR, frontend-only access control, missing authentication, exposed credentials, SQL/XSS, weak password/JWT/session handling, mass assignment, unsafe uploads, missing rate limits, permissive CORS, secret/error leakage, vulnerable dependencies, insufficient server validation, unguarded sensitive functions, and unreviewed AI code: read the security expert's mandatory [common AI-generated backend security failures](../security-expert/references/common-ai-backend-security-failures.md) checklist for every affected backend trust boundary.
- Formatting and validating React, headless, WordPress, ACF, REST, or admin form fields in coordination with frontend: [form-field-contracts.md](references/form-field-contracts.md)
- Backend latency, query/storage efficiency, ACF/payload cost, secure caching, REST capacity, rendering, remote integrations, background work, observability, and scalability: [performance-and-scalability.md](references/performance-and-scalability.md)
- Rendering modes, render configuration precedence, cache scope/tags, regeneration, and invalidation dependencies: [rendering-cache-and-invalidation.md](references/rendering-cache-and-invalidation.md)
- First load, repeatable migrations, ACF synchronization, debugging, PHP linting, builds, and focused test selection: [migrations-testing-and-debugging.md](references/migrations-testing-and-debugging.md)

## Backend Workflow

1. Identify the domain object, editor workflow, consumers, visibility, expected/current volume, concurrency, latency/capacity expectations, trust levels, and delivery mode.
2. Inspect the closest WordPress registration, ACF group, ReactWP filter/service, payload, endpoint, and test before selecting an extension point.
3. Write the data contract first: field names, types, null/empty behavior, references, pagination, HTML semantics, language behavior, resource bounds, performance budget, and cache dependencies. For user input, also record the shared field-contract revision and obtain frontend approval before formatters or transport rules harden. For private data or mutations, add the actor/action/resource matrix and exact writable-field map, including canonical owner/tenant/parent checks, list/bulk scope, protected fields, CSRF/replay behavior, cache identity, and safe error policy.
4. Place durable domain data in WordPress/ACF; place derived request data in a route filter or service; place broadly reusable public configuration in the public settings contract.
5. Implement the smallest project-owned hook/service. Do not fork ReactWP runtime behavior when an existing filter or facade method expresses the same operation.
6. Connect the contract through the selected mode: registry/template props for the integrated theme, or public/versioned REST responses for headless.
7. Add invalidation for every post, term, menu, option, remote source, user context, or query dimension that affects cached output.
8. Exercise populated, empty, malformed, maximum-size/cost, unpublished/unauthorized, translated, paginated, cold/warm/stale-cache, dependency-failure, and concurrent/retry cases as applicable. For object access, prove user A cannot read/change/delete user B's resource by replacing an ID, slug, UUID, parent reference, filename or other accepted key; for mutations, prove protected, nested, duplicate and unknown fields cannot change privileged state.
9. Measure the representative path against its recorded budget and confirm the optimized path preserves security, privacy, invalidation, failure and output contracts.
10. Run PHP lint, focused PHP/Node tests, and the smallest build covering the changed artifact.

## Contract Invariants

- `RouteResolver` owns the normalized route keys: `id`, `type`, `template`, `pageName`, `path`, `search`, `query`, `url`, `seo`, `mediaGroups`, `data`, `head`, `render`, `lang`, and `is404`.
- `route.lang` is the canonical current-route language key. Treat `route.language` only as a legacy compatibility input where existing code explicitly supports it.
- Project page data belongs under `route.data` unless it is route metadata, shared site state, or a separate resource with its own endpoint/lifecycle.
- A React template name must match the backend `react_template` value and the frontend registry key exactly.
- ACF group visibility in the editor is not the same as API exposure. ReactWP reads only active matching groups whose group has `show_in_rest` enabled.
- `PublicPayload` normalizes and bounds public response shapes; it is not business authorization and not an HTML sanitizer.
- Public and private render caches are different identities. Never put personalized output in a public entry.
- Structural WordPress changes may require a deliberate one-time rewrite flush or migration; never flush rewrite rules on every request.
- Custom public REST routes must be deliberately registered with ReactWP's REST access layer in addition to WordPress route registration.
- ReactWP route allowlisting, a successful login, an opaque identifier, a frontend ownership check, or a collection-level capability never authorizes an individual private object. Resolve and authorize every object and canonical parent/tenant server-side; scope lists and bulk work by the same rule before returning counts or data.
- Mutation contracts accept only explicitly mapped fields. Ordinary users cannot choose role, capabilities, owner, author, tenant, verification, protected status, pricing authority, cache/privacy scope, or equivalent privileged fields unless a separate dedicated operation explicitly authorizes that transition.
- Frontend formatting is never authoritative. Every submitted value is independently type/length/grammar/business validated, then deliberately normalized to the documented canonical representation before storage or use; invalid semantic input is rejected rather than silently repaired by sanitization.
- Every public or caller-controlled operation has bounded input, query/result count, response size, execution/fan-out and retry behavior proportionate to its cost. Performance caches preserve visibility/identity and cannot override authorization or freshness requirements.

## Verification Commands

Run commands from `configs/` and select the smallest relevant set:

```powershell
npm run test:firstload
npm run test:route-visibility
npm run test:rest-access
npm run test:public-payload
npm run test:render-cache
npm run test:headless-api-security
npm run test:seo-route-language
npm run test:render
npm run build:mu-plugins
npm run build:plugins
npm run build:themes
```

Use `npm run build` for changes spanning several WordPress targets, `npm run generate` for static-generation behavior, and `npm run prod` only for production artifacts or deployment behavior. Syntax-check every changed PHP file with `php -l`. Add representative profiling/load evidence when a performance budget is in scope; do not run disruptive load against production or third parties without explicit authorization. A successful build does not prove an ACF location rule, permission callback, payload/query bound, latency budget, cache isolation/invalidation path, or editor workflow.

## Do Not

- Do not edit WordPress core, generated `dist/`, or vendor files for project behavior.
- Do not register the same field group from PHP and Local JSON without an explicit source-of-truth strategy.
- Do not expose every ACF group or option merely because a frontend asks for it.
- Do not return raw WordPress objects or unbounded queries from public endpoints.
- Do not claim performance or scalability without representative before/after evidence, and do not optimize only the median while maximum-cost, tail-latency, failure, cache-miss, or abuse paths remain unbounded.
- Do not create a second route model for the integrated theme when `RouteResolver` and `rwp_route_payload` already own it.
- Do not make a headless consumer depend on the private/same-origin bootstrap shape.
- Do not use direct SQL where WordPress query APIs express the requirement; when SQL is necessary, also load `security-expert`.
- Do not trust a caller-supplied `user_id`, `owner_id`, `tenant_id`, post author, parent ID, order/customer reference, file key, slug or UUID as proof of access, and do not filter foreign records only after computing a private list or its totals.
- Do not pass `WP_REST_Request::get_params()`, decoded JSON, `$_POST`, ACF input, or a generic DTO directly into `wp_update_user`, `wp_insert_post`, meta/ACF updates, SQL, or model hydration.
- Do not implement project password hashing when WordPress identity applies, expose server API keys to browser code/payloads, or introduce JWT/session behavior without a separately reviewed authentication and revocation contract.
- Do not expose an HTML string without documenting and enforcing its allowed markup before it reaches a frontend raw-HTML sink.
- Do not add data to a cached response without defining identity, tags, invalidation, and privacy scope.
- Do not let PHP/WordPress accept a broader, narrower, or differently normalized field grammar than the frontend-approved contract, and do not trust a mask, `pattern`, input type, or client validator.
- Do not trade security, privacy, correctness, accessibility of errors, or deterministic invalidation for a faster benchmark.
