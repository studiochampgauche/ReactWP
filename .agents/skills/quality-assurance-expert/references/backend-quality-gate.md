# Backend Quality Gate

Use this gate when WordPress, PHP, ACF, plugins/mu-plugins, content types, queries, route/public payloads, REST, integrated/headless consumption, rendering, cache, invalidation, or migrations can be affected. Read the specific references routed by `backend-expert` first.

## Ownership and Delivery Mode

- Authored code is in `src/`, build tooling in `configs/`, and generated `dist/` was not hand-edited.
- Integrated theme, external headless consumer, or deliberate dual support is stated and implemented without mixing private bootstrap and public API contracts.
- Existing WordPress/ReactWP hooks, services, facades, and filters are used before inventing parallel routing, rendering, cache, or global state.
- Project changes do not modify WordPress core or vendor code.

## Data and Content Model

- Field names/types, required/optional behavior, null/empty states, references, HTML semantics, languages, volume, pagination, and editor workflow are explicit.
- ACF keys are stable; PHP registration versus Local JSON has one deliberate source of truth.
- Only intended active field groups use `show_in_rest`; editor visibility is not mistaken for API exposure.
- Durable domain data, derived route data, public shared settings, and separate resources live in their appropriate contracts.
- Return shapes are bounded JSON-serializable values, not raw `WP_Post`, `WP_Term`, `WP_User`, ACF internals, or database rows.

## ReactWP Contracts

- `RouteResolver` remains the owner of normalized route keys and `route.data` owns page-specific project data unless another lifetime/owner is justified.
- `route.lang` is canonical; legacy `route.language` is used only by an existing compatibility path.
- Backend `react_template` and frontend registry keys match.
- `PublicPayload` is treated as shape normalization/bounding, not business authorization or HTML sanitization.
- Custom public REST routes are registered in WordPress and deliberately handled by ReactWP's REST-access layer.

## WordPress, REST, and Queries

- Every submitted/persisted field uses one backend-custodied contract revision approved by frontend, including required/empty behavior, type, bounds, allowed characters/positions, locale assumptions, transport grammar, canonical value, stable error code, sensitivity, and shared accepted/rejected fixtures.
- Direct REST/admin/programmatic requests are validated independently of frontend masks, native attributes, patterns, or JavaScript. Invalid semantic input is rejected rather than silently converted by sanitization, while documented benign display formatting normalizes to the agreed canonical value.
- WordPress REST args/validation or ACF/project hooks enforce the applicable local rules, and callback/service logic enforces cross-field, authorization, CSRF and current-state rules. `rwp::sanitize()` is not treated as business validation.
- Hooks run at the correct lifecycle and do not perform repeated expensive registration/migration/flush work.
- Queries use WordPress APIs, have bounded results/pagination, avoid N+1 behavior where material, and preserve publication/permission/language rules.
- REST request/response schemas, permission callbacks, error envelopes, status codes, pagination, and versioning match consumers.
- Populated, empty, malformed, translated, paginated, missing, unpublished, unauthorized, and failure cases are exercised as applicable.

## Rendering, Cache, and Migration

- Client/static/server mode is selected deliberately and shared templates remain compatible with every supported mode.
- Every new dependency has cache identity, tags/dependencies, privacy scope, and invalidation for post/term/menu/option/remote/user/query changes.
- Personalized data never enters a public render/cache entry.
- Structural changes have a repeatable migration/first-load strategy; rewrite flushing is deliberate and never runs on every request.
- Old and new data shapes have a deployment/rollback or compatibility decision when evolution is not atomic.

## Performance, Capacity, and Abuse Resistance

- Material request/query/payload/cache/render/integration/job work has a recorded workload, environment/topology assumptions, representative baseline, measurable target, maximum volume/concurrency/cost, and before/after result.
- Query count/time, N+1 behavior, result bounds, pagination, `no_found_rows`/ID-only/batching/cache priming choices, ACF relationship/repeater cost, payload bytes, memory and remote fan-out are inspected as applicable rather than inferred from code style.
- Cold, warm, stale, invalidated, cache-disabled, anonymous, authenticated, preview, locale/query and dependency-failure paths are measured when they differ. Object-cache/CDN/queue/cron/multi-node assumptions are explicit and not silently inferred from local development.
- Every caller-controlled path bounds input, query/result work, response size, execution time, concurrency/fan-out and retries. Expensive private work is authorized before execution where safe, and built-in ReactWP rate limits are not assumed to cover arbitrary custom endpoints.
- Cache keys contain every behavior/privacy dimension without unbounded cardinality; tags/invalidation cover every dependency; private/tenant/preview/auth data cannot enter public entries; stale data cannot restore revoked permission or security state.
- Performance changes preserve or strengthen validation, authorization, CSRF, output shaping, safe SQL/HTTP, resource/rate limits, error secrecy and fail-closed behavior. A faster insecure path is `FAIL`.
- Background jobs are bounded, idempotent, lock/retry safe and operationally scheduled; remote dependencies have time/size/redirect/fan-out limits and cannot create synchronous retry storms.

## Required Evidence

- Syntax-check every changed PHP file.
- Inspect producers, consumers, hooks, filters, public/private boundaries, cache invalidation, and editor exposure.
- Run the focused PHP/Node tests and target builds documented by `backend-expert` for the affected contracts.
- Verify the chosen delivery mode with an actual representative response/route, not just the registration code.
- Run the shared form fixtures against backend validation, include direct client-bypass, malformed, boundary, oversized and canonical storage/read-round-trip cases, and compare the result with frontend evidence.
- For performance scope, record the exact workload, data volume, cache state, identity, command/tool, repeated samples or percentile method, before/after metrics and security regression results. A local microbenchmark alone cannot prove production scalability.

A successful build cannot by itself pass ACF, REST permission, payload, cache, migration, or editor-workflow requirements.
