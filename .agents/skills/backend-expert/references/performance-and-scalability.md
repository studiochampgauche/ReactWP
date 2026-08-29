---
name: backend-expert-performance-scalability
description: ReactWP backend performance and scalability guidance for measurable request budgets, WordPress and ACF query discipline, payloads, REST, rendering, secure caching, integrations, background work, and abuse resistance.
---

# Backend Performance and Secure Scalability

## When to Use This Reference

Use this reference when a backend change can affect request latency, database work, payload size, memory, concurrency, cache behavior, rendering, remote integrations, scheduled work, or the cost an unauthenticated or low-privilege caller can trigger.

Performance and security are one contract. A fast path that bypasses authorization, leaks through a shared cache, accepts unbounded work, weakens validation, or serves stale permission-sensitive data is not an optimization. Reject an optimization unless it preserves or strengthens the complete security boundary.

## Establish a Measurable Budget

Before optimizing or introducing a potentially expensive path, record the workload and evidence required:

```text
Journey/route/endpoint/job:
Integrated theme, external headless, or both:
Anonymous/authenticated/preview roles and privacy scope:
Expected and maximum data volume:
Expected and maximum page size/filter depth/fan-out:
Expected concurrency and request/job frequency:
Latency target (cold/warm and percentile where meaningful):
Database query count/time target:
Response/payload byte target:
PHP/worker memory and execution-time target:
Remote dependency timeout/retry budget:
Cache owner, scope, key dimensions, TTL, tags, invalidation and freshness target:
Object cache/CDN/queue/cron/runtime assumptions:
Before/after measurement and regression evidence:
```

Do not invent numerical service-level objectives when the product or environment has not defined them. Establish a proportionate acceptance budget from the real traffic, hosting, data growth, privacy needs and user journey, then measure against it. Separate cold cache, warm cache, cache-disabled, anonymous, authenticated and preview results where their paths differ.

## Measure Before and After

- Inspect the actual producer, consumer and closest test before changing caching or queries. Measure the representative route or job rather than extrapolating from an isolated helper.
- Record elapsed time, database query count/time, response bytes, memory, remote-call count/time, cache hit/miss/stale behavior and generated work as applicable.
- Use production-like data volume and topology when evidence matters. A small local database with no persistent object cache cannot prove production capacity or multi-node consistency.
- Profile development or staging with temporary tools such as Query Monitor, application instrumentation or `SAVEQUERIES` only when appropriate. Remove diagnostic output and do not leave expensive query logging, stack traces, secrets or personal data enabled in production.
- Compare the same scenario before and after. A faster median does not compensate for worse tail latency, an authorization regression, cache leakage, stale content, failed invalidation or a new unbounded case.

## Keep Request-Time Work Bounded

- Register hooks and services cheaply. Do not perform migrations, rewrite flushes, starter provisioning, remote synchronization, bulk indexing or expensive cache warming on every request.
- Validate type, length, count, page size, filter depth and allowed operations before querying, rendering, uploading, fetching remotely or allocating large structures.
- Authenticate and authorize before expensive private work whenever the response distinction does not itself leak protected existence. Preserve intentionally generic authentication and enumeration failures.
- Make repeated initialization and side effects idempotent. Use short, failure-safe locks for one-time or concurrently triggered work; never hold a request lock around slow network work without a bounded failure strategy.
- Avoid doing the same derivation independently in route payload, REST response, SSR and static-generation paths. Place reusable domain work behind one deterministic service with an explicit cache and invalidation contract when reuse justifies it.

## WordPress Query and Storage Discipline

- Prefer `WP_Query`, `get_posts`, `get_terms` and dedicated WordPress APIs. Allowlist request-driven query dimensions before mapping them to query arguments.
- Bound every collection. Paginate public lists; cap page size and expensive page depth. Use `no_found_rows => true` when total pages are not needed, `fields => 'ids'` when full objects are unnecessary, and request only the data the response exposes.
- Detect N+1 patterns across posts, terms, metadata, ACF relationships, media and nested repeaters. Batch resolution and deliberately prime relevant WordPress caches when it reduces total work; do not fetch every field merely because ACF can format it.
- Treat `meta_query`, wildcard searches, random ordering, large offsets and sorting/filtering on unindexed values as data-model decisions, not harmless syntax. Inspect real query plans and volume. For high-volume transactional or heavily queried structured data, consider a purpose-built indexed table only with an explicit schema, migration, capability, prepared-query, backup and rollback contract.
- Keep large or frequently changing values out of autoloaded options. When project code creates an option that need not load on every request, set autoload deliberately. Bound transient/cache entries and keys; WordPress options are not an unbounded queue or event log.
- Avoid repeated `update_option`, post/meta writes or cache invalidations when the canonical value did not change. Coalesce bulk work and preserve hooks/side effects required by the domain.
- Use direct SQL only when WordPress APIs cannot express the needed operation and measured evidence justifies it. Prepare values, allowlist identifiers, bound results and load `security-expert`.

## ACF and Payload Discipline

- Expose only deliberate active ACF groups through `show_in_rest`; editor availability is not a reason to serialize every field into every route.
- Choose stable return formats that avoid repeatedly hydrating large object graphs. Resolve relationship, image, repeater and flexible-content values into the smallest explicit view model needed by the consumer.
- Keep page-owned data in `route.data`, truly global public configuration in the public settings contract, and independently paginated resources in focused endpoints. Do not move a large collection into the bootstrap to save one request.
- Return bounded JSON-serializable primitives and arrays. `PublicPayload` provides framework limits and public normalization, but project code should produce a smaller domain-specific shape rather than relying on the maximum accepted shape.
- Measure serialized and compressed response size as applicable. A low PHP query count does not make a multi-megabyte bootstrap or deeply nested JSON performant.

## REST and Headless Cost Controls

- Define argument schemas, stable response envelopes, pagination, projection and cache dimensions before implementation. Reject unknown or oversized structures where ambiguity can multiply work.
- `HeadlessApi::public_permission` supplies the built-in public rate limiter only when a route actually uses that permission callback. It does not cap a project query, remote fan-out or response. Custom routes need their own cost analysis and possibly narrower per-IP, per-user, per-object or global limits.
- Keep rate limiting secondary to authorization and strict workload bounds. Validate trusted-proxy client identity before relying on forwarded addresses, and verify object-cache or transient behavior under the deployed multi-node topology.
- Cache only deterministic reads whose visibility and invalidation are understood. Mark authenticated, identity, preview, nonce-bearing and other sensitive responses `no-store` where appropriate; do not trade confidentiality for a higher hit rate.
- Use explicit projections for list endpoints and separate expensive detail resources. Avoid recursively embedding relationships, arbitrary sort/meta identifiers, unlimited search or client-selected includes.
- Where safe and useful, use HTTP/CDN validators and caching with correct `Vary`, identity, locale and invalidation behavior. ReactWP render invalidation does not automatically purge a CDN, external framework cache or search index.

## ReactWP Rendering and Cache Contracts

Read [rendering-cache-and-invalidation.md](rendering-cache-and-invalidation.md) before changing render mode or cache behavior.

- Select `client`, `static` or `server` from privacy, determinism, freshness, infrastructure and measured initial-render needs. SSR is not automatically faster, and static output must contain only deterministic public data.
- ReactWP SSR caching requires enabled HTML caching and a positive TTL. Public entries are not used for logged-in responses; private entries require an identity. Preserve that isolation and never substitute a shared fallback identity.
- Keep every behavior-changing language, query, filter, currency, experiment and authorization dimension in the correct cache identity. Allowlist and canonicalize query keys to avoid both cache poisoning and cardinality explosion.
- Attach semantic bounded tags for every dependency and invalidate those exact tags. Prefer targeted invalidation to frequent global busts, but never keep a narrow invalidation scheme that can serve stale authorization, privacy or correctness state.
- ReactWP's SSR transport has time/size bounds, a failure circuit breaker and client-render fallback. Do not remove them to improve a synthetic success benchmark. Project template work and remote dependencies must still be bounded.
- For custom expensive cache fills, consider stampede behavior, atomicity, lock expiry, stale-on-error eligibility and failure recovery. Never serve stale private, permission-sensitive, revoked, preview or security-policy data merely to reduce latency.
- Confirm invalidation across posts, terms, menus, options, ACF saves, relationships, users, integrations and external headless/CDN layers. ReactWP cannot infer every project dependency.

## External Services and Background Work

- Keep remote calls off latency-critical paths when the product can use asynchronously refreshed or previously validated data. When a synchronous call is required, cap connection/total time, redirects, response bytes, content type, fan-out and retry time.
- Apply exact host/scheme rules and SSRF protections before caching a remote response. Cache only validated data, separate tenant/user visibility, and define safe stale-on-error behavior; never cache an upstream error body or credential-bearing response as public content.
- Make jobs idempotent, bounded and resumable in batches. Record stable job identity, lock ownership/expiry, retry/backoff limit, failure visibility, and the cache/index state affected.
- WordPress cron depends on site traffic unless production invokes it through a real scheduler. State that topology explicitly for time-sensitive work; do not assume scheduling guarantees that the deployment does not provide.
- Prevent retry storms. A failed upstream, renderer, webhook or purge must not cause unbounded synchronous retries from every request.

## Security-Preserving Optimization Review

For every performance change, re-check:

- authorization and object ownership execute on cache hits and optimized paths where required;
- CSRF, nonce, CORS and authentication decisions were not removed or conflated;
- public/private/authenticated/preview/tenant identities cannot share protected data;
- inputs, query complexity, pagination, response bytes, concurrency, remote work and job retries remain bounded;
- cache keys cannot be poisoned or exploded by arbitrary input, and invalidation cannot be triggered with arbitrary tags;
- SQL values remain prepared and identifiers allowlisted;
- errors, traces, metrics and cache keys do not expose secrets or personal data;
- stale data cannot restore revoked permissions, expired tokens, removed content or an older security policy;
- failure remains fail-closed for permissions and safe for availability, with no hidden infinite retry or expensive fallback loop.

Do not remove validation, permission callbacks, nonce checks, exact origin rules, response shaping, rate limits, resource limits, cache partitioning, safe remote-request APIs or renderer safeguards to improve a benchmark. If security and the proposed optimization conflict, keep the security invariant and redesign the optimization.

## Verification

Use the smallest checks that prove both performance and correctness:

1. measure a representative baseline with production-like volume;
2. exercise cold, warm, stale and invalidated paths;
3. compare anonymous, authenticated, preview and relevant tenant/language/query identities;
4. run allowed, malformed, oversized, unauthorized and maximum-cost requests;
5. verify payload bytes, query count/time, memory, remote fan-out and cache hit/miss behavior against the recorded budget;
6. update each dependency and confirm only the correct cache/output becomes stale;
7. verify renderer, cache, remote service and job failure paths without weakening permissions or leaking errors;
8. repeat measurements after the complete security checks, not only after a happy-path microbenchmark.

From `configs/`, select relevant existing checks:

```powershell
npm run test:public-payload
npm run test:rest-access
npm run test:headless-api-security
npm run test:render-cache
npm run test:server-security
npm run test:static-regenerator
npm run test:render
```

Add focused project tests or approved local load/profiling evidence for the actual workload. Do not run disruptive load tests against production or third-party systems without explicit authorization. A successful build proves compilation, not a latency budget, query bound, cache isolation, invalidation path or abuse limit.

## Do Not

- Do not optimize from intuition alone or claim scalability from a single local happy-path timing.
- Do not cache private data publicly, omit a behavior-changing cache dimension, or ignore invalidation to improve hit rate.
- Do not return raw objects, unlimited collections or oversized payloads to avoid shaping/pagination work.
- Do not move heavy setup, remote synchronization, bulk work or migrations into a common request hook.
- Do not introduce broad database indexes, denormalized copies, object-cache dependencies, queues or CDN behavior without measured need and an operational/migration contract.
- Do not retry indefinitely, hold locks indefinitely, or let a failed dependency create synchronized retry storms.
- Do not expose sensitive metrics, queries, traces, payloads or cache identities in public output or logs.
- Do not call a backend “ultra secure” from design intent; demonstrate the applicable threat boundaries, secure defaults, abuse limits and regression evidence.
