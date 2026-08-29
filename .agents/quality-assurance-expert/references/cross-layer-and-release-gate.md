# Cross-Layer and Release Gate

Use this reference after domain gates. Most material ReactWP regressions occur where one expert's output becomes another expert's input.

## Cross-Layer Traces

Trace every applicable journey end to end:

### WordPress/ACF -> route/API -> React

- Field meaning, return type, emptiness, language, HTML policy, and visibility remain identical across producer, payload, cache, and consumer.
- Integrated and headless modes use the correct private/public contract.
- Frontend empty/error/loading states match backend null/error/pagination behavior.
- Cache identity/invalidation changes when any rendered dependency changes.

### Editor/untrusted input -> public output

- Validation, authorization, sanitization, transport, rendering, URL/head/schema serialization, and final escaping each have an explicit owner.
- For every submitted/persisted field, frontend and backend reference one authoritative revision and shared fixture corpus; visible editing/formatting produces the documented transport value, backend bypass validation produces the documented canonical value or stable error, and a storage/read/render round trip does not drift.
- Public payload normalization is not mistaken for content authorization or HTML safety.
- Rich HTML and JSON-LD remain safe and current on direct, static/server, hydration, and client-navigation paths.

### Content/SEO -> WordPress -> rendered experience

- The content brief maps to actual ACF/WordPress fields and accessible frontend components.
- Frontend and content/SEO used one authoritative editorial composition matrix with a named custodian/revision and both approvals, then jointly signed off real short/long/translated/missing content, semantic hierarchy, typography/measure, text-media relationships, and responsive reading order in the rendered experience.
- Visible copy, metadata, social preview, internal links, robots, canonical/hreflang, and entity graph stay consistent across locales.
- SEO/plugin fallbacks do not hide missing required content or create duplicate/stale route head data.

### Navigation/motion/rendering -> accessibility/performance

- Loader/transition/scroller/motion lifecycles do not block content, lose focus, duplicate services, leak listeners, or leave stale metadata/schema.
- Reduced motion, keyboard/touch input, constrained viewports, slow/error media, and repeated route interruption are exercised.
- Static/server/client output and cache behavior do not diverge on content, security, or metadata.

### Backend performance -> security and operations

- The measured workload matches real consumers and the documented delivery/render/cache topology; query, payload, memory, remote, concurrency and job costs remain bounded as data and callers approach the supported maximum.
- Cold/warm/stale/invalidation/failure behavior preserves route/API correctness, public/private/auth/preview isolation, permissions, rate/resource limits, and deterministic fallback.
- External CDN/framework caches, queues, cron/schedulers and multi-node object caches have explicit ownership and consistency assumptions; ReactWP invalidation or built-in rate limiting is not assumed to control systems it does not own.

## Verification Selection

Run from `configs/` and select every command needed by the compliance rows:

```powershell
npm run build:themes
npm run build:plugins
npm run build:mu-plugins
npm run build
npm run test:firstload
npm run test:route-visibility
npm run test:rest-access
npm run test:public-payload
npm run test:render-cache
npm run test:headless-api-security
npm run test:preview-token
npm run test:svg-sanitizer
npm run test:server-security
npm run test:static-regenerator
npm run test:seo-route-language
npm run test:render
npm run test:security
npm run generate
npm run prod
```

Also syntax-check each changed PHP file with `php -l`. Use focused commands for focused changes; use the spanning build/test when several targets or the pipeline changed. Use `npm run prod` only when production optimization/artifacts/deployment behavior is in scope. Do not run `generate` without the runtime/content prerequisites it needs.

Record command, working directory, exit code, relevant output, and what requirement it proves. Do not list a command as passed when it was skipped, timed out, unavailable, or produced unreviewed warnings relevant to the change.

## Manual/Runtime Evidence

Automated commands must be complemented as applicable by:

- browser journey on direct load and internal navigation;
- representative responsive widths and input modes;
- affected form fields exercised through browser input paths and direct requests that bypass the frontend, including boundary/malformed values and canonical round trips;
- keyboard, focus, reduced motion, media/loading/error, and accessibility observations;
- authenticated/unauthorized/public/preview cases;
- stale/fresh cache and invalidation behavior;
- final head/robots/canonical/hreflang/social/schema inspection;
- integrated and external-headless outputs when both are supported;
- generated or deployed artifact inspection when production behavior changed.
- representative backend profiling/load evidence and repeated security/abuse checks when latency, capacity, caching, queries, integrations or jobs changed.

## Release Verdict

Use exactly one outcome:

- **100% compliant with applicable verified requirements:** all applicable matrix rows pass; no required evidence is missing.
- **Not compliant:** at least one applicable requirement fails.
- **Not fully verified:** no known failure may exist, but at least one applicable row lacks evidence or an essential check could not run.

Do not calculate an average percentage. One unresolved applicable requirement means the release is not 100% compliant.

## Final Report Skeleton

```text
Verdict:
Scope/baseline:

Findings (highest impact first):
- [severity] [owning skill] requirement, evidence, consequence, correction

Applicability:
- Frontend: applicable/not applicable — reason
- Backend: applicable/not applicable — reason
- Security: applicable/not applicable — reason
- Content/SEO: applicable/not applicable — reason

Compliance matrix:
| ID | Source | Requirement | Evidence | Status |

Checks executed:
- command/manual check -> result -> requirements covered

Unverified/limitations:
- missing evidence and exact next check
```

If there are no findings, retain the matrix, executed checks, limitations, and verdict; “no findings” alone is not a release gate.
