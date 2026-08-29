# Audit Method and Traceability

Use this reference for every QA audit. It turns broad expert guidance into a complete, reviewable set of claims and evidence.

## 1. Establish the Baseline

Record:

- the latest user request and explicit acceptance criteria;
- the intended baseline or comparison range, if one exists;
- changed tracked files, untracked files, generated files, and configuration/content changes;
- unrelated pre-existing changes that must not be attributed to the audited task;
- execution environment, available services, browser/runtime access, and missing dependencies;
- whether the request is audit-only, audit-and-fix, or a release gate.

Do not audit only the diff when the change relies on surrounding contracts. Inspect the nearest callers, consumers, hooks, route fields, tests, and generated/runtime behavior needed to understand its effect. Do not broaden the review into unrelated historical code.

## 2. Build the Applicability Map

Evaluate all four domains before routing references:

| Domain | Typical trigger | Applicability evidence |
| --- | --- | --- |
| Frontend | React/SCSS, visual output, interaction, accessibility, media, motion, client navigation, rendering | Changed files plus affected rendered behavior |
| Backend | PHP/WordPress/ACF, data contract, route/public payload, REST, rendering/cache, migration | Producer/consumer flow and delivery mode |
| Security | Any differently trusted source, sensitive read/write, raw sink, URL/head, REST/auth, files, cache/privacy, external request | Source-to-sink or authorization boundary |
| Content/SEO | Visible copy/structure, metadata, robots, social preview, canonical/hreflang, entities/schema, locale | Content/page promise and final searchable/shareable output |

A domain can be `NOT APPLICABLE`, but only with a concrete boundary argument. For example, a SCSS-only spacing change may make backend and content/SEO non-applicable while frontend remains applicable; raw CMS HTML rendered by that component makes security applicable even if PHP did not change.

## 3. Create Atomic Requirements

Collect requirements from:

- the user request and acceptance criteria;
- repository/nested instruction files;
- each applicable expert `SKILL.md` operating rules, invariants, expected quality, workflows, verification rules, and `Do Not` section;
- every supporting reference routed by the affected behavior;
- current ReactWP source contracts and relevant external official specifications;
- regression obligations created by the change itself.

Split combined guidance into rows that can pass or fail independently. Preserve the source file and section for every row.

## 4. Evidence Rules

Choose evidence appropriate to the claim:

| Claim | Minimum useful evidence |
| --- | --- |
| Placement/contract | Source inspection of definition and consumer |
| Syntax/compilation | Lint, parser, type/static check, or focused build |
| Runtime behavior | Executed test or manual/browser reproduction with observed result |
| Visual/responsive behavior | Representative viewport inspection with real/edge content |
| Accessibility | Semantic/source review plus keyboard/focus/reduced-motion/manual or tooling checks as applicable |
| Security | Reachable source-to-sink trace, guard inspection, allowed/denied cases, and focused regression |
| Cache/invalidation | Identity/tag/invalidation inspection plus stale/fresh behavior test |
| Content truth | Supplied/primary evidence, source date, and rendered comparison |
| Metadata/schema | Final direct response/DOM and client-navigation result; validator when relevant |
| Deployment artifact | Production-mode generation plus inspection of the artifact/configuration |

Static source can prove some structural requirements but cannot prove dynamic behavior by itself. Automated tests can prove their assertions but not untested visual, editorial, authorization, or environment behavior.

## 5. Matrix Format

Use one row per atomic requirement:

| ID | Skill/source | Requirement | Applies because | Evidence | Status | Finding/action |
| --- | --- | --- | --- | --- | --- | --- |
| FE-01 | `frontend-expert` / section | Concrete rule | Changed behavior | file:line, command, or observation | PASS/FAIL/UNVERIFIED/NOT APPLICABLE | concise consequence or next check |

Use stable prefixes such as `REQ`, `FE`, `BE`, `SEC`, `SEO`, and `X` for user, domain, and cross-layer requirements.

## 6. Completeness Check

Before issuing a verdict, confirm:

- every changed or newly exposed behavior maps to at least one requirement row;
- every applicable `Do Not` rule has been checked, not merely omitted because no violation was noticed;
- every route/data/trust boundary has both producer and consumer evidence;
- tests cover the regression and meaningful failure/empty/unauthorized cases where applicable;
- every executed failure is resolved or remains a `FAIL`;
- every skipped or unavailable check remains `UNVERIFIED`;
- every `NOT APPLICABLE` row includes a defensible reason;
- findings, matrix statuses, and final verdict agree.

## Severity

Use severity for findings, never to erase compliance failures:

- **Blocker:** unsafe release, broken core journey, data exposure/loss, executable injection, or requirement whose failure invalidates the feature.
- **Major:** material functional, accessibility, rendering, cache, SEO, or maintainability failure.
- **Minor:** localized quality gap that still violates an applicable requirement.

Even a minor unresolved failure prevents a 100% compliance verdict.
