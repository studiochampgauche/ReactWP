---
name: backend-expert-migrations-testing-debugging
description: ReactWP backend guidance for first-load setup, idempotent WordPress and ACF migrations, Local JSON synchronization, debugging data paths, PHP linting, builds, and focused test selection.
---

# Migrations, Testing, and Debugging

## Separate Setup from Migration

ReactWP's optional first-load scaffold creates a starter page, language rows, theme-location rows, primary menu, front-page setting, and permalink structure. It runs only when `RWP_FIRSTLOAD` is enabled, an administrator visits the admin, and the stored version/field references need work. It uses a lock and preserves existing populated rows.

This is project bootstrap, not a general place for every future data migration. Do not keep adding unrelated production mutations to `firstload.php`.

Use a project-owned versioned migration mechanism when an existing installation needs durable schema/data transformation.

## Migration Properties

A safe migration is:

- versioned;
- idempotent;
- scoped to exact records/fields;
- capability/CLI/deployment controlled;
- resumable or safely retryable;
- observable without leaking content/secrets;
- tested on representative old data;
- followed by cache invalidation;
- removable only after every environment has advanced.

Store a project schema version in one namespaced option. Advance it only after all steps succeed. For multi-step or large migrations, record progress and batch work to avoid request timeouts.

Do not infer success from the existence of one new field when several records need conversion. Do not delete old data before verifying the new shape and consumer compatibility.

## ACF Migration Pattern

For a renamed or reshaped field:

1. preserve stable field keys when the semantic field remains the same;
2. introduce the new schema in PHP/Local JSON;
3. make consumers accept old and new forms temporarily;
4. query affected object IDs in bounded batches;
5. read raw/canonical values deliberately;
6. transform and write through ACF/WordPress APIs;
7. verify ACF reference meta keys where applicable;
8. invalidate affected render/external caches;
9. remove compatibility in a later release.

For option repeaters, remember ACF stores row counts, subfield values, and underscore-prefixed field-key references across multiple options. ReactWP's first-load seeding code demonstrates this structure, but prefer `update_field()` when ACF is fully available and the migration context supports it.

## Local JSON Across Environments

The Local JSON artifact is part of the source contract. Before deployment:

- confirm new/changed group JSON is committed;
- confirm field/group keys match consumer expectations;
- verify the target theme resolves `datas/acf` correctly;
- check directory protective files exist in generated output;
- ensure production admin has no unexpected newer database-only group revision;
- decide whether synchronization is automatic or an explicit deployment/admin step.

Never resolve a conflict by casually changing keys or importing duplicate groups.

## Rewrite and Scheduled Work

Post-type/taxonomy rewrite changes need one deliberate flush after registration. Use activation/migration/admin tooling; never flush on every `init`.

For cron/queue changes:

- use namespaced event names;
- avoid duplicate scheduling;
- make callbacks idempotent;
- bound batch size and remote time;
- unschedule obsolete events during a controlled migration/deactivation;
- expose failure metrics/logs without secrets.

## Debug the Full Data Path

For a missing ACF value in React:

```text
stored post/term/user/option value
  -> matching active ACF group
  -> field name/key and return format
  -> show_in_rest group exposure
  -> rwp::field formatting
  -> RouteResolver reserved-field extraction
  -> rwp_route_payload filters
  -> Bootstrap or PublicPayload
  -> route.data consumer
  -> render/client cache
```

Inspect each boundary rather than adding fallback fetches in the frontend.

For a route that returns 404 unexpectedly:

- confirm rewrite/permalink and canonical path;
- confirm object type/status/password/public visibility;
- confirm taxonomy/author visibility rules;
- test `RouteResolver::from_path()` and the REST route endpoint;
- verify language prefix/translation mapping;
- verify the request path passes `RestAccess::is_safe_view()`;
- clear only relevant caches after the underlying cause is fixed.

For stale output:

- identify actual initial render source;
- inspect render mode/scope/TTL/tags after all precedence layers;
- compare current route key language/path/search;
- verify save hooks fired for the changed dependency;
- inspect external CDN/framework caches separately;
- avoid using a global cache bust as the only diagnosis.

## PHP Syntax and Focused Tests

Syntax-check each changed PHP file:

```powershell
php -l path/to/changed-file.php
```

Run project commands from `configs/`. Select tests by changed contract:

| Change | Focused verification |
| --- | --- |
| First-load/setup behavior | `npm run test:firstload` |
| Route visibility/path rules | `npm run test:route-visibility` |
| REST allowlisting | `npm run test:rest-access` |
| Public route/bootstrap shapes | `npm run test:public-payload` |
| Headless auth/origin/public boundaries | `npm run test:headless-api-security` |
| Preview behavior | `npm run test:preview-token` |
| Cache tags/invalidation | `npm run test:render-cache` |
| Static regeneration | `npm run test:static-regenerator` |
| SSR/server boundary | `npm run test:server-security` |
| Integrated render/hydration | `npm run test:render` |
| SEO route language resolution | `npm run test:seo-route-language` |

Use `npm run test:security` for cross-cutting security changes.

## Build Selection

Choose the smallest build that covers authored output:

```powershell
npm run build:mu-plugins
npm run build:plugins
npm run build:themes
npm run build:render
npm run build
```

- mu-plugin runtime PHP/JS: `build:mu-plugins`;
- normal plugin changes: `build:plugins`;
- integrated theme contract/assets: `build:themes`;
- server/static render bundle: `build:render` or render tests;
- changes spanning targets: `build`;
- production optimization/deployment artifacts: `prod`.

PHP source copied by the pipeline still needs linting; a Webpack success may not parse every PHP file.

## Test Meaningful Invariants

Prefer tests that prove behavior:

- unpublished/password-protected content is absent publicly;
- a matching exposed ACF group appears with the expected shape;
- an internal group remains absent;
- route template and render policy resolve with correct precedence;
- a custom endpoint rejects malformed/unauthorized/oversized input;
- an authenticated user cannot read, change, delete or download another user's object by replacing any accepted ID, slug, UUID, filename, parent reference or other locator, including nested, bulk and cached paths;
- a mutation cannot change role, capabilities, owner, tenant, protected status or another privileged field through unknown, nested, alternate or generic mass-assignment input;
- saving a dependency invalidates the intended tag;
- public/private cache identities do not collide;
- a migrated record can be processed twice without corruption;
- integrated and headless consumers agree on shared domain fields.

Avoid tests that merely search generated text or assert implementation-specific method calls without observing the contract.

## Completion Checklist

- Migration/setup is versioned, idempotent, bounded, and recoverable.
- ACF definitions and Local JSON are synchronized with stable keys.
- Every changed PHP file passes `php -l`.
- The focused test for each changed boundary passes.
- The smallest relevant build succeeds.
- Editor workflow has been exercised with real field/location rules.
- Anonymous/authenticated, integrated/headless, and cache states are covered as applicable.
- Every applicable row in the security expert's common AI-generated backend failure checklist has direct passing evidence; a build, scanner or generated-code review assertion is not sufficient.
- Generated `dist/` was not hand-edited.
- Remaining unverified deployment or external-cache behavior is stated explicitly.
