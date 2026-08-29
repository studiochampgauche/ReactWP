---
name: reactwp-common-ai-backend-security-failures
description: Mandatory ReactWP checklist for preventing common AI-generated backend and security failures involving authorization, IDOR, authentication, secrets, injection, sessions, uploads, abuse controls, dependencies, and unreviewed generated code.
---

# Common AI-Generated Backend Security Failures

## When to Use This Reference

Read this checklist before implementing or approving project-owned code that accepts input, selects a user or object, reads private data, mutates state, registers an endpoint, authenticates a caller, handles a token/session, stores a secret, queries SQL, renders untrusted content, uploads a file, calls an integration, or changes a dependency.

This reference does not replace the boundary-specific security references. It prevents a recurring failure mode: generated code looks complete on the happy path while omitting the adversarial authorization, validation, isolation, and operational cases.

## Non-Negotiable Review Matrix

For each applicable row, record the exact source, server-side guard, protected resource or sink, and passing evidence. `N/A` needs a concrete reason. A frontend restriction, hidden control, successful build, static scanner, or statement that a framework “handles security” is not evidence.

| # | Failure to prevent | Required ReactWP/project control | Minimum adversarial evidence |
| --- | --- | --- | --- |
| 1 | Role or privilege escalation | Never accept `role`, capabilities, user level, administrator flags, ownership, tenant, verification, or equivalent privileged state through a generic profile/update payload. Use a dedicated server-side operation, an exact writable-field allowlist, the narrow WordPress/project capability for the target user, and deliberate audit/revocation behavior. | A low-privilege user cannot promote self or another user, cannot add a privileged field under an alternate/nested name, and cannot invoke the dedicated operation. An authorized administrator can perform only the intended transition. |
| 2 | IDOR / broken object-level authorization | Treat every ID, slug, UUID, email, filename, order number, parent ID, or nested reference only as a locator. Resolve the canonical object, tenant/owner/visibility/state, then authorize this actor and action on that exact object. Scope collection queries by the same rule. | User A can read/change A's object, cannot read/change/delete B's object by replacing any reference, cannot infer it through list counts/cache/errors, and a mixed/bulk request cannot smuggle B's object. |
| 3 | API key exposed to the browser | Classify credentials as server-only unless the provider explicitly defines a constrained publishable identifier. Store server credentials in protected environment/secret configuration and call the provider from a trusted backend or backend-for-frontend. | Search authored and generated browser assets, bootstrap/route payloads, source maps, logs, examples and repository history/diff for the secret name/value; confirm the browser never receives the credential. |
| 4 | API route without authentication | Every non-public REST/AJAX/admin/webhook/function entry point has an explicit authentication decision and fail-closed behavior. A WordPress `permission_callback`, ReactWP REST allowlist entry, or webhook reachability is only one part of the boundary. | Anonymous, missing, expired, malformed and wrong-auth-mechanism requests fail; the intended authenticated caller succeeds. |
| 5 | Access control only in frontend code | Buttons, menus, routes, disabled fields and cached client identity are UX only. Repeat authorization in the backend service at every sensitive read/mutation and derive authoritative identity from the authenticated request. | A direct HTTP/admin/AJAX request that bypasses React is denied with no state change or private response. |
| 6 | SQL injection | Prefer WordPress query APIs. When custom SQL is necessary, prepare every value, allowlist identifiers/directions, escape `LIKE` input before a prepared placeholder, and bound result/cost. | Injection strings in every client-controlled value remain data; dynamic column/order/table input outside the allowlist is rejected; the result set stays bounded. |
| 7 | XSS | Validate content semantics, sanitize intentional rich HTML against a narrow server-side allowed-markup/protocol policy, and escape/encode at the exact final HTML/attribute/URL/JS/CSS sink. React text rendering is the default; raw HTML remains an explicit audited boundary. | Scriptable tags, event handlers, dangerous URL protocols, malformed markup and stored payloads cannot execute in client, static or server rendering. |
| 8 | Plaintext or weak password protection | Never implement project password storage or hashing when WordPress identity is used. Use WordPress account, sign-on, password-reset and session APIs so core owns hashing and upgrades; never normalize, log, cache, return or email plaintext passwords. | Stored values are WordPress hashes rather than plaintext/custom reversible data; login/reset work through core APIs; passwords are absent from responses, logs, analytics, fixtures and caches. |
| 9 | Misconfigured JWT | ReactWP's built-in headless authentication uses WordPress cookies; its preview token is not a generic user-authentication JWT. Do not introduce JWT accidentally. If an explicit architecture requires it, use a maintained verifier with a server-fixed algorithm/key policy and validate signature, issuer, audience, expiry/not-before, token purpose and subject; define rotation, revocation, replay and transport/storage rules. | Reject unsigned, wrong-algorithm, wrong-key, wrong-issuer/audience/purpose, expired, future, tampered and revoked tokens. A decoded-but-unverified payload never authorizes work. |
| 10 | Unsafe session/token lifetime | Define short risk-appropriate idle and absolute lifetimes, renewal rules, server-side revocation and logout/security-event behavior. Keep identity/token responses `no-store`; do not put bearer, preview or reset tokens in public caches, persistent browser storage or URLs without an explicitly reviewed requirement. | Expired/revoked/logged-out credentials fail, password or privilege changes revoke the required sessions, public caches cannot replay identity, and the `remember` choice matches the product risk. |
| 11 | Mass assignment | Define an exact writable-field map per operation and actor. Map request fields explicitly into domain commands/WordPress APIs; reject or safely ignore unknown fields according to the versioned contract. Derive owner, author, role, capability, tenant, status and other protected fields server-side or handle them in separate privileged operations. | Extra, nested, alternate-casing and duplicate protected fields do not alter state; generic request arrays are never passed into `wp_update_user`, `wp_insert_post`, ACF/meta updates or model hydration. |
| 12 | Unsafe file upload | Authenticate, authorize the destination/object, verify CSRF when applicable, and bound count/bytes/dimensions/expanded work. Validate server-observed content/MIME plus extension, generate filenames, confine storage, keep uploads non-executable, and define scan/access/deletion/response rules. Use ReactWP's SVG protection only for the flow it actually covers. | Spoofed MIME/extensions, polyglots as applicable, oversized/decompression-heavy files, traversal names, duplicate/collision cases, unauthorized upload/read/delete and executable serving are rejected or isolated. |
| 13 | Missing rate limiting or abuse bounds | Authorize and strictly bound work first, then add per-IP, per-account, per-object and/or global throttles appropriate to login, reset, search, form, upload, webhook and expensive API paths. `HeadlessApi::public_permission` protects only routes that use it; custom paths need their own policy. | Burst, distributed-account, repeated-object and maximum-cost cases are bounded; trusted proxy identity and multi-node counter behavior are verified; failure does not become an unbounded fallback. |
| 14 | Permissive CORS | Enable browser cross-origin access only when required, with normalized exact origins, correct credential behavior, a narrow method/header policy and `Vary: Origin`. Never combine credentials with `*`. CORS is not authentication or object authorization, and custom namespaces do not automatically inherit ReactWP CORS. | Allowed origin succeeds as designed; attacker origin, lookalike/subdomain/scheme/port variations and origin-less cross-site cases fail without weakening non-browser authorization. |
| 15 | Secrets in code or Git | Keep API keys, tokens, salts, licenses, private URLs and credentials in protected environment/secret configuration. Prevent them from entering source, examples, fixtures, generated `dist`, source maps, logs, errors, query strings or public payloads. Rotate any credential that was committed; deleting the current line is insufficient. | Secret scan of the change and generated/deployed artifacts is clean; configuration fails safely when missing; any previously exposed credential has documented rotation/revocation. |
| 16 | Sensitive error disclosure | Return stable visitor-safe codes/messages and only authorized existence detail. Keep SQL, stacks, paths, environment, tokens, headers, upstream bodies and personal data in protected redacted logs, never public responses. | Malformed, unauthorized, dependency-failure and unexpected-exception cases reveal no internals and do not enable account/object enumeration. |
| 17 | Vulnerable or obsolete dependencies | Prefer maintained, necessary packages; pin through the repository lock strategy; inspect release/security status and transitive impact; run ecosystem/repository security checks; patch or document a time-bounded mitigation. Remove unused packages. | Lockfile and build are reproducible, relevant vulnerability checks are reviewed for reachability, no known reachable unmitigated release blocker remains, and runtime compatibility tests pass after updates. |
| 18 | Insufficient server validation | Reject wrong type, shape, unknown field, length/count/depth/range/enum/grammar/cross-field/state violations before expensive work. Normalize only already permitted representations; do not silently sanitize invalid meaning into acceptance. | Direct requests cover missing/null/wrong type, boundary, oversized, malformed, duplicate/ambiguous, stale-state and cross-field cases independently of frontend validation. |
| 19 | Sensitive function callable without authorization | Inventory every route, hook, callback, CLI/admin action, AJAX action, cron/webhook trigger and reusable service method that can read private data or cause side effects. Entry points authenticate/authorize; domain services enforce invariant checks so an alternate caller cannot bypass them. | Each public or alternate invocation path is tested; calling the service through another registered hook/route cannot skip authorization, state or tenant rules. |
| 20 | Generated code accepted without security review/tests | Treat generated security-sensitive code as untrusted until an independent source-to-sink and actor/action/resource review plus focused negative tests pass. Review the diff and generated artifacts; scanners complement this review but do not replace it. | The evidence matrix names reviewer/check, allowed and denied cases, exact tests run, results and environmental limitations. No completion claim rests only on code appearance, an agent assertion or a successful build. |

## Object-Level Authorization and IDOR Contract

Authentication answers who the caller is. Object-level authorization answers whether that caller may perform this action on this exact object now. Never infer the second from the first.

For every protected read or mutation, record:

```text
Entry point and operation:
Authenticated actor source:
Object reference(s) accepted from caller:
Canonical resource and parent/tenant resolution:
Visibility, owner, tenant and lifecycle state:
Required object-level capability or project policy:
Administrator/support override and its exact capability:
CSRF/replay requirement:
List/query scope and bulk-item policy:
Public/private cache identity and invalidation:
Existence-concealment/error policy:
Allowed owner fixture:
Denied foreign-owner/foreign-tenant fixtures:
Denied missing/deleted/private/stale-state fixtures:
```

Apply these rules:

- Treat URL opacity, UUID randomness and unguessable slugs as enumeration resistance only, never authorization.
- Resolve enough canonical state to check the real owner, tenant, parent, visibility and action. Use WordPress object-level meta capabilities such as `read_post`, `edit_post`, or `delete_post` with the object ID when they accurately model the domain; otherwise implement one centralized project policy rather than scattered role-name comparisons.
- Derive the ordinary user's identity from the authenticated WordPress/principal context. A caller-supplied `userId`, `ownerId`, `author`, `tenantId` or parent reference cannot change the authorization subject or scope.
- Scope private collection queries before results, totals, pagination cursors or facets are computed. Filtering foreign records only after a broad query can leak existence and counts.
- For nested objects, authorize both the child and its canonical parent/tenant relationship; never trust a client-supplied parent ID without verifying the stored relationship.
- For bulk operations, authorize every item under one explicit atomic/partial-success policy. Authorizing the first ID or the collection route does not authorize the rest.
- Recheck mutable authorization/state close to a high-value write when time-of-check/time-of-use changes could matter.
- Partition private/authenticated caches by the real identity and authorization dimensions. A correct permission callback followed by a shared cached response is still an IDOR.
- Choose `403` versus deliberately concealed `404` consistently. Do not reveal a foreign object's title, owner, validation state, timing-dependent detail or different error body before authorization.

## ReactWP Authentication Boundaries

- ReactWP's global `RestAccess` gate decides whether a route is reachable in a context. It does not authorize the requested business object.
- Every custom WordPress REST route still requires its own `permission_callback`; private callbacks authenticate and authorize the precise object/action.
- ReactWP's built-in `/reactwp/v1/auth/*` flow delegates password verification and session cookies to WordPress through `wp_signon()`, rate-limits login, restricts credentialed origins, requires HTTPS outside local development, returns generic failures, and marks identity responses `no-store`.
- ReactWP does not provide a general JWT authentication system. Do not describe `PreviewToken` as one: it is a signed, post-bound, expiry-limited preview credential for the built-in preview flow.
- Custom account, password, reset, invitation, JWT, API-key, webhook or session mechanisms are complete project-owned trust boundaries even when another ReactWP route uses similar primitives.

## Mass-Assignment and Privilege-Change Contract

For every mutation, maintain an explicit input-to-domain map:

| Accepted request field | Actor(s) allowed | Validation/canonicalization | Domain field/API | Protected or server-derived relationship |
| --- | --- | --- | --- | --- |
| Example `displayName` | Owner or support capability | Shared field contract | Explicit user update | `role`, capabilities, owner and tenant are absent from this operation |

Never spread or forward `get_params()`, decoded JSON, `$_POST`, GraphQL input, ACF input, or a generic DTO directly into a persistence API. A client schema, TypeScript type, hidden input or omitted form control does not make extra fields impossible.

Privilege transitions require a dedicated operation because their actor, target, allowed transition, audit, notification, reauthentication and session-revocation needs differ from ordinary profile editing. Check the current actor against the target user/object; never authorize by comparing a client-supplied role string or by letting a user set their own capabilities.

## Verification Gate

Security-sensitive backend work is incomplete until all applicable rows have direct evidence. At minimum run:

1. the intended allowed request;
2. anonymous/missing/expired authentication;
3. authenticated user A requesting and mutating user B's resource by every accepted reference;
4. privilege and mass-assignment payloads with protected, nested, duplicate and unknown fields;
5. malformed, oversized and maximum-cost input;
6. cache hit/miss/stale paths across different identities;
7. safe error and dependency-failure behavior;
8. the focused ReactWP/project security tests routed by the affected subsystem.

Do not declare a row safe because no vulnerable string was found. Prove the behavior at the real entry point and service boundary.
