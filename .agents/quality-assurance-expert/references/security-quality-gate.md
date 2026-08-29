# Security Quality Gate

Use this gate whenever data crosses trust levels or reaches a sensitive sink. Read the relevant `security-expert` references and inspect the current ReactWP guard implementation before relying on it.

## Complete Boundary Trace

For every affected path, trace:

```text
source -> parsing/unslashing -> validation -> authorization -> normalization/sanitization
       -> storage/transport/cache -> context-specific sink
```

Record what ReactWP protects, the preconditions of that protection, and what remains project-owned. A missing arrow is `UNVERIFIED`, not assumed safe.

## Input and Authorization

- Each submitted/persisted form field has one frontend/backend-approved contract revision. Client filtering is treated only as UX; direct requests must fail the same malformed character, position, grouping, type, length, range, locale and cross-field cases on the backend.
- Normalization occurs only after the representation is accepted and cannot silently turn malformed semantic input into a different value. Canonical storage/use and stable errors do not leak sensitive input or internals.
- Business validation rejects wrong type, shape, range, state, ownership, and unsupported identifiers before expensive work.
- Capability/ownership authorization covers every sensitive read and write.
- Cookie-authenticated mutations verify CSRF/nonces separately from authorization.
- REST allowlisting and CORS are not treated as data permission or authentication.
- Length, depth, count, time, redirects, concurrency, query cost, and response size are bounded at attacker-controlled boundaries.
- Performance optimizations preserve or strengthen authorization, validation, CSRF, cache partitioning, invalidation, rate/resource bounds, prepared/allowlisted query behavior, safe remote requests and fail-closed errors on cache-hit, bypass, maximum-cost and dependency-failure paths.

## Sanitization and Output

- Canonical values are stored; escaping occurs at the final sink for HTML, attribute, URL, JS, CSS, JSON, SQL, header, path, or command context.
- `rwp::sanitize()` and `rwp::escape()` are used only for supported types/contexts and are not substituted for authorization.
- Raw WordPress/ACF/API/editor HTML has an explicit allowed-markup policy and backend sanitization before either React raw-HTML path.
- `dangerouslySetInnerHTML` inserts only already trusted/sanitized unchanged HTML; `html-react-parser` is not treated as a sanitizer.
- React escaping is not treated as a URL-protocol allowlist.
- Head/meta/link/JSON-LD output uses bounded structured data, safe URLs, and context-correct serialization; arbitrary script/head HTML is not accepted through `route.head`.

## Sensitive Systems

- SQL uses safe WordPress APIs or prepared values and allowlisted identifiers.
- Files/uploads/extraction prevent traversal, unsafe types, executable placement, archive abuse, and unbounded resource use.
- External requests control schemes, hosts, redirects, DNS/internal targets, response size/time, and returned content before use.
- Redirects, headers, logs, error messages, and commands do not accept unsafe concatenated input or leak sensitive details.
- Secrets, credentials, nonces, preview tokens, licenses, and private URLs are absent from source, public payloads, URLs, logs, artifacts, and public caches.

## Rendering, Cache, and Deployment

- Public, authenticated, preview, private-cache, build-time, static, and SSR contexts have distinct data/privacy rules.
- Cache identity prevents poisoning and cross-user leakage; query dimensions are bounded and canonical.
- SSR/static render services, regeneration, files, and deployment rules fail closed and do not expose source/secrets.
- Security headers/CSP and origin/auth topology are verified in the deployed or production-equivalent response when changed.
- Dependency/supply-chain changes preserve integrity, provenance, version/lock behavior, and secret-free configuration.

## Required Evidence

- Demonstrate allowed, malformed, unauthorized, oversized/resource-bound, and failure cases where applicable.
- For form work, include client-bypass requests and the shared accepted/rejected fixture corpus; frontend-only evidence cannot pass backend or security rows.
- For backend performance work, include maximum-cost and concurrent/retry/failure cases plus cache-identity/invalidation evidence; a faster happy path cannot pass while an abuse or privacy row is unverified.
- Run the focused security tests routed by `security-expert` and add a regression for a new non-obvious invariant or corrected vulnerability.
- Report a security finding only with a reachable source, missing/incorrect guard, affected sink/decision, and credible impact.

No passing aggregate test suite replaces this source-to-sink review.
