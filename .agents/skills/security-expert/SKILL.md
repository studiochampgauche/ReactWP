---
name: security-expert
description: Design, implement, and review security-sensitive ReactWP changes using the framework's existing sanitization, escaping, REST, rendering, cache, upload, and supply-chain protections while adding the controls ReactWP leaves to project code. Use for untrusted input/output, HTML, URLs, permissions, nonces, REST/auth, SQL, files, external requests, SSR/SSG, caches, secrets, headers, or deployment. Do not use for purely visual or editorial changes with no trust-boundary impact.
---

# Security Expert for ReactWP

Secure the complete data path without duplicating or misapplying ReactWP's safeguards. For every relevant change, trace:

```text
source -> parsing/unslashing -> validation -> authorization -> normalization/sanitization
       -> storage/transport/cache -> context-specific output sink
```

A guard at one arrow does not secure the others. Sanitization is not authorization, a nonce is not authorization, CORS is not authentication, escaping is not input validation, and React's text escaping does not make arbitrary HTML or URL protocols safe.

## Core Rules

- Inspect the current implementation of the relevant ReactWP guard before relying on it; repository code is the source of truth.
- Reuse `rwp::sanitize()`, `rwp::escape()`, `PublicPayload`, `RichText`, `AppLink`, `Button`, `sanitizeDomProps`, `Loader`, `RestAccess`, `HeadlessApi`, `PreviewToken`, and rendering/cache services only for the boundary each actually protects.
- Treat every raw-HTML path as a sink that requires an explicit trust and sanitization contract. `dangerouslySetInnerHTML` inserts HTML unchanged; `html-react-parser` transforms HTML but does not sanitize it. Tool selection belongs to the frontend design, while security review verifies the data is safe for the selected sink.
- Validate against the business contract before accepting data. Use sanitization to normalize an already permitted shape, not to turn arbitrary input into authorization.
- For submitted form fields, require the shared frontend/backend field contract. Treat masks, character filters, input types, patterns, and client validation only as UX; verify the backend independently enforces type, bounds, grammar, cross-field rules, canonicalization, authorization, and CSRF requirements.
- Authorize every state change and sensitive read with the narrowest capability or ownership rule. Verify CSRF separately for cookie-authenticated mutations.
- Treat every caller-supplied ID, slug, UUID, username, filename, order number, parent reference, or other object key only as a locator. Resolve the canonical object and its owner/tenant/visibility/state, then authorize this actor and action on that exact object; authentication, route access, an unguessable identifier, or ownership of a sibling object is never enough.
- Maintain an exact writable-field allowlist per mutation. Never pass a request object/array directly to a persistence API, and never let ordinary profile/content updates accept role, capabilities, owner, author, tenant, verification, status, price, or other privileged fields that must be server-derived or handled by a separate authorized operation.
- Escape at the final output sink for its exact context. Store canonical values, not pre-escaped presentation strings.
- Bound attacker-controlled length, depth, count, time, redirects, concurrency, and response size before expensive work.
- Treat backend performance work as a security change whenever it alters queries, caches, batching, concurrency, retries, remote calls, jobs, rate limits or maximum work. Load the backend performance reference and verify the optimized path cannot bypass authorization, leak across identities, poison/explode caches, restore stale permissions, or amplify resource exhaustion.
- Prefer allowlists, exact normalized matches, safe WordPress/browser APIs, and fail-closed behavior.
- Keep public, authenticated, preview, private-cache, build-time, and server-render contexts distinct. Data safe for one context may be sensitive in another.
- Do not weaken a ReactWP security filter to make a feature work until the feature's exact requirement, deployment impact, and narrower alternative have been evaluated.
- Add a focused regression test for a corrected vulnerability or a new non-obvious trust-boundary invariant.
- Treat AI-generated security-sensitive code as unreviewed until the applicable failure checklist, source-to-sink review, actor/action/resource authorization matrix, direct bypass tests, and generated-artifact/secret checks have passing evidence. A build, scanner, or agent assertion alone cannot approve it.
- When SEO/head/schema security depends on the truth and visibility of content, also load `content-seo-expert`; content owns the supported claims and entity graph while security owns validation, public exposure, serialization, and safe output.
- When the user requests final QA, release readiness, or proof that all expert requirements were respected, also load `quality-assurance-expert`; security remains the source of security requirements while QA owns completeness, evidence, and the verdict.
- When a substantial mission spans multiple expert domains, also load `reactwp-orchestrator`; security stays cross-cutting, and a dedicated security worker follows the orchestrator's risk, ownership, and independent-review rules.

## Reference Router

Read only what the task needs:

- To determine what ReactWP already protects and what remains project-owned, read [responsibility-matrix.md](references/responsibility-matrix.md).
- Before implementing or approving project-owned backend/API/auth/account/file/integration code, read the mandatory [common AI-generated backend security failures](references/common-ai-backend-security-failures.md) checklist. It owns the cross-cutting role-escalation, IDOR, mass-assignment, credential, authentication, session/JWT, injection, upload, abuse, CORS, dependency, error and review gates.
- For PHP/React input, `rwp::sanitize()`, `rwp::escape()`, ACF fields, rich HTML, URLs, DOM props, SQL, and output contexts, read [sanitization-escaping-and-sinks.md](references/sanitization-escaping-and-sinks.md).
- For formatted or constrained user-editable fields, also read the shared [form field contracts](../backend-expert/references/form-field-contracts.md).
- For backend latency, capacity, caching, query, integration, job, or scalability work, also read [backend performance and secure scalability](../backend-expert/references/performance-and-scalability.md).
- For REST allowlisting, permission callbacks, capabilities, nonces, headless CORS/authentication, rate limits, preview tokens, and public payload exposure, read [wordpress-rest-auth-and-data.md](references/wordpress-rest-auth-and-data.md).
- For SSR/static rendering, cache scope, external requests, files/uploads, security headers, secrets, supply chain, and deployment, read [rendering-files-and-deployment.md](references/rendering-files-and-deployment.md).

## Security Change Workflow

For a security-sensitive implementation or review:

1. Identify every untrusted or differently trusted source: request values, REST JSON, ACF/editor content, database values, remote responses, uploaded files, route payloads, cached objects, build inputs, or environment configuration.
2. Identify every sink: HTML/attribute/URL/JS/CSS, SQL, filesystem path, HTTP request, redirect, header, cache key/value, log, command, email, or authorization decision.
3. For each sensitive read/mutation, create the actor/action/resource matrix: caller identity source, accepted object references, canonical object and parent/tenant resolution, capability/ownership/state rule, protected fields, list/bulk scope, CSRF/replay rule, cache identity, and existence/error policy.
4. Read the relevant responsibility matrix row and inspect the named code. State which ReactWP invariant applies and its preconditions.
5. Complete every applicable row of the common AI-generated backend security failure checklist and list the residual obligations left to the project. Typical missing controls are schema validation, object-level capability/ownership checks, nonce verification, writable-field allowlisting, URL/path allowlisting, SQL preparation, output escaping, cache isolation, session/token policy, rate limits, or deployment policy.
6. Implement the narrowest control at the boundary closest to the risk. Avoid double escaping or lossy blanket sanitization that corrupts valid content.
7. Exercise the allowed case, anonymous and malformed cases, user A accessing/mutating user B's object by every accepted reference, mass-assignment/privilege payloads, an oversized/maximum-cost case when relevant, cross-identity cache paths, and the failure path. Confirm errors do not leak existence, secrets, personal data, or remote/internal details.

## Required Distinctions

### Validation, sanitization, and escaping

- **Validation** answers whether a value is acceptable for this operation.
- **Sanitization/normalization** produces a canonical value of an allowed type.
- **Authorization** answers whether this actor may perform the operation on this object.
- **Escaping/encoding** makes a value safe for one output context.

Do not collapse these into one `sanitize_*` call.

### Framework code and project code

ReactWP secures its built-in routes, render transport, cache contracts, standard frontend sinks, SVG plugin, and core downloader. It cannot automatically secure project-owned custom endpoints, custom raw HTML, business permissions, database queries, upload handlers, remote integrations, cache identities, CSP relaxations, or deployment topology.

### Public shape safety and content safety

`PublicPayload` normalizes known structures, removes non-public WordPress object references, and enforces bounds. Arbitrary scalar strings can remain arbitrary strings. The final React/PHP/URL/HTML sink still needs its own safe rendering path, and project filters must never add secrets to a public payload.

## Review Priorities

Prioritize findings that cross a real trust boundary:

- broken authorization or public/private data exposure;
- role/capability escalation, IDOR/BOLA, mass assignment, or a sensitive alternate entry point that bypasses the primary route;
- executable output injection or unsafe raw HTML/URL/head handling;
- SSRF, path traversal, unsafe upload/extraction, or arbitrary file write/read;
- SQL injection or unsafe dynamic query identifiers;
- CSRF on cookie-authenticated state changes;
- cache poisoning or cross-user cache leakage;
- preview/auth token leakage, weak secret handling, or permissive credentialed CORS;
- resource exhaustion from unbounded input or expensive public work;
- production exposure caused by missing server rules or deployed source/secrets.

Do not report generic hardening preferences as exploitable defects without a reachable source, missing guard, affected sink, and credible impact.

## Verification

Run from `configs/` and select the smallest relevant set:

```powershell
npm run test:security
npm run test:headless-api-security
npm run test:rest-access
npm run test:public-payload
npm run test:preview-token
npm run test:svg-sanitizer
npm run test:server-security
npm run test:static-regenerator
npm run test:render
```

Use `npm run prod` when build output, security headers/configuration, render assets, deployment contents, or production-only behavior is affected. PHP syntax-check every changed PHP file with `php -l`. Tests complement, but do not replace, a source-to-sink review of authorization and output context.

## Do Not

- Do not pre-escape values before storage and then escape them again at output.
- Do not accept client-side character filtering or formatting as proof that a direct request is valid, and do not silently sanitize malformed semantic input into a different accepted value.
- Do not pass unsanitized WordPress, ACF, API, editor, or user HTML to either `dangerouslySetInnerHTML` or `html-react-parser`.
- Do not treat `PublicPayload::sanitize_value()` as an HTML sanitizer.
- Do not rely on React attribute escaping to reject `javascript:` or another unsafe URL scheme.
- Do not use a nonce as proof that a user has a capability.
- Do not assume a REST allowlist entry or CORS origin grants permission to the returned data.
- Do not authorize a collection, route, object type, or logged-in session and then fetch an arbitrary caller-selected object. Prove authorization for the resolved object and canonical parent/tenant on reads, writes, deletes, downloads, lists and bulk operations.
- Do not accept generic request arrays in user/post/meta/ACF persistence APIs or let a client choose protected role, capability, owner, author, tenant, verification, status, price, cache/privacy, or security-policy fields.
- Do not invent password hashing or claim ReactWP provides JWT authentication. Use WordPress identity/session APIs for the built-in model; treat any explicit JWT/account/session extension as a separate fully verified trust boundary.
- Do not concatenate request values into SQL, paths, commands, headers, redirects, or cache identities.
- Do not expose preview tokens, REST nonces, SSR secrets, credentials, or private URLs in query strings, logs, public bootstrap data, or persistent public caches.
- Do not approve a performance optimization that removes or weakens a security control; require an equivalent or stronger design and repeat the affected abuse, identity, cache and failure-path tests.
- Do not hand-edit or deploy security fixes only in `dist/`; fix authored source and regenerate output.
