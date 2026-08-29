---
name: reactwp-orchestrator
description: Orchestrate substantial cross-layer ReactWP work by turning an outcome into shared contracts, delegating non-overlapping backend, frontend, content/SEO, security, and QA workstreams, coordinating parallel execution, and integrating one verified result. Use for complete sites, apps, dashboards, management systems, or changes spanning two or more expert domains. Do not use for small single-domain tasks where delegation adds no useful parallelism.
---

# ReactWP Orchestrator

Own the complete outcome while specialized agents own bounded workstreams. The user should be able to describe the site, application, system, or modification they need without naming skills, files, or technical layers. Translate that request into one coherent ReactWP delivery rather than several disconnected specialist outputs.

## Core Responsibilities

- Preserve the user's goal, scope, constraints, content, references, and authorization boundaries.
- Inspect the current project and ReactWP contracts before decomposing work. Do not plan from generic WordPress/React assumptions when the source can answer the question.
- Choose the delivery mode explicitly: integrated ReactWP theme, external headless consumer, or intentionally both.
- Infer that delivery mode from product facts such as the public origin, hosting, routing, authentication, deployment, editor workflow, and consumer applications. Do not force the user to choose ReactWP jargon; ask in product terms only when those facts leave a material architectural ambiguity.
- Establish shared data, content, route, rendering, security, cache, metadata, and acceptance contracts before dependent agents implement against them.
- For every private object journey, include one actor/action/resource contract that treats IDs and other references only as locators, authorizes canonical owner/tenant/parent/state server-side, and defines list/bulk/cache/error behavior. For every mutation, include one exact writable-field map that excludes or separately authorizes privileged role/capability/owner/tenant/status fields.
- Require measurable backend performance/capacity budgets for material query, payload, rendering, cache, integration or job work, and make preservation of security invariants an acceptance criterion rather than a later trade-off.
- Pair frontend and backend on every submitted or persisted form field. Backend is the default custodian of one authoritative field-contract revision; frontend co-approves its visible/editing format, input behavior, transport value, accessibility, and fixtures while backend owns authoritative validation, canonicalization, storage/use, and stable errors.
- Pair frontend and content/SEO on every user-facing editorial experience. Keep the orchestrator as the default single write custodian of one authoritative composition matrix, require both roles to approve the same revision, and synchronize before layout hardens, after real content is rendered, and before QA.
- Delegate substantial independent workstreams to specialized agents when parallel work reduces latency without creating file or contract conflicts.
- Keep one owner for every mutable file and shared contract at a time. Agents share a workspace; concurrent edits to the same surface are forbidden unless ownership is deliberately transferred.
- Integrate the workstreams, resolve contract drift, run the appropriate builds/tests/runtime checks, and send the completed integration through independent QA.
- Return one outcome, one verification summary, and one set of remaining limitations to the user.

Delegation never expands authorization. Every worker inherits the exact scope and side-effect permissions of the user's request. External publication, production deployment, account changes, purchases, destructive actions, or unrelated cleanup still require their own authority.

## Required Expert Sources

Always read repository `AGENTS.md`. Load only the specialists that apply to the mission, but evaluate all four product domains during routing:

- `.agents/backend-expert/SKILL.md` for WordPress, ACF, PHP, plugins, REST, routes, queries, rendering, cache, performance/scalability, migrations, and integrated/headless data contracts;
- `.agents/frontend-expert/SKILL.md` for React, SCSS, rendering, accessibility, responsiveness, performance, media, interactions, motion, and visual references;
- `.agents/content-seo-expert/SKILL.md` for audience, content, information architecture, metadata, internal links, entities, schema, and `reactwp-seo`;
- `.agents/security-expert/SKILL.md` for every applicable trust boundary and as a dedicated specialist when risk warrants it;
- `.agents/quality-assurance-expert/SKILL.md` for the independent final evidence gate.

Each delegated agent must read its role profile, its primary expert `SKILL.md`, and only the supporting references routed by its assigned behavior. The orchestrator must itself understand every shared contract; do not delegate away architectural accountability.

## When to Delegate

Use specialized sub-agents when there are at least two substantial workstreams that can make meaningful progress independently. A typical first wave uses the orchestrator plus backend, frontend, and content/SEO agents. Run QA after integration, when an implementation slot is available and the evidence is stable.

Work directly with the relevant expert skill when the task is a small, localized, single-domain change. Do not create an agent ceremony for a copy edit, one SCSS correction, a narrow PHP fix, or a read-only question unless independent review is specifically requested.

Read [task-routing.md](references/task-routing.md) to size work, select agents, order dependency waves, and handle full products versus focused changes.

## Reference Router

- Mission brief, shared contracts, file ownership, change control, handoffs, dirty worktrees, and integration: [shared-contracts-and-handoffs.md](references/shared-contracts-and-handoffs.md)
- Backend worker mandate and handoff: [backend-agent.md](references/backend-agent.md)
- Frontend worker mandate, including complete inspiration reconnaissance: [frontend-agent.md](references/frontend-agent.md)
- Content and SEO worker mandate: [content-seo-agent.md](references/content-seo-agent.md)
- Independent QA wave, findings, correction loop, and final verdict: [quality-assurance-agent.md](references/quality-assurance-agent.md)
- Cross-cutting security ownership and when to launch a dedicated security worker: [security-agent.md](references/security-agent.md)

## Orchestration Workflow

1. **Understand the outcome.** Identify users, jobs, business goal, content, journeys, data, trust levels, locales, delivery mode, rendering needs, references, constraints, and acceptance criteria. Ask only for missing choices that materially change the product.
2. **Inspect the project.** Read the closest current implementation, instructions, contracts, tests, and dirty-worktree state. Separate user-owned pre-existing changes from the mission.
3. **Create the mission brief.** Define in writing the shared route/data/content/security/render/cache/SEO contracts, backend performance/capacity budgets where material, ownership ledger, dependency graph, agent deliverables, verification plan, and explicit non-goals.
4. **Open the first execution wave.** Delegate backend, frontend, and content/SEO work that has non-overlapping file ownership. Frontend and content/SEO work as a coordinated tandem on their shared editorial composition contract; frontend and backend coordinate every submitted/persisted field contract before either hardens its formatter or validator. Keep dependency-bound work pending until its input contract is stable.
5. **Coordinate contract changes.** Workers must report a proposed shared-contract change before editing dependent surfaces. Approve, reject, or revise it, notify every affected worker, and update ownership before work continues.
6. **Integrate.** Review every handoff and diff, reconcile producers with consumers, exercise cross-layer journeys, and run the smallest sufficient spanning checks. Agent self-reports are inputs, not proof.
7. **Run independent reviews.** Launch dedicated security review when the risk router requires it and QA after the integrated result is stable. They may inspect concurrently when useful, but no final QA verdict is valid until the security handoff and resulting corrections are integrated. QA remains read-only and reports findings to the orchestrator.
8. **Dispatch corrections.** Return each finding to the owning implementation role, preserve file ownership, re-integrate, and re-run every invalidated check. Continue until QA passes or a real blocker/new authority requirement prevents progress.
9. **Deliver one result.** Summarize what now works, important architecture/contracts, verification evidence, QA verdict, and any genuinely unverified limitation. Do not make the user assemble specialist reports.

## Coordination Rules

- Use parallelism for independent work, not for competing architectural decisions.
- Prefer short contract-first sequencing over letting frontend and backend invent incompatible payloads in parallel.
- Keep content/SEO involved before fields and layouts harden; it must not be a metadata afterthought.
- Schedule explicit frontend/backend form syncs before implementation, after shared-fixture tests, and after a real direct-request plus browser-input round trip. A changed grammar, canonical representation, limit, locale assumption, or error code invalidates both approvals.
- Schedule explicit frontend/content sync points for the composition matrix, the real rendered draft, and final responsive/locale sign-off. Relay decisions immediately or permit direct role communication while keeping the shared contract authoritative.
- Treat security as part of each design and data path, not a final scanner.
- Make backend optimization evidence include representative cold/warm/maximum-cost behavior and repeat security, privacy, cache isolation, invalidation, abuse and failure-path checks after the optimization.
- Keep QA independent from implementation. QA may recommend fixes but must not quietly edit product files during its audit.
- Reuse available agents for correction passes when practical. Do not spawn redundant workers for the same ownership area.
- Send concise progress updates while work continues, especially when contracts settle, parallel waves complete, QA finds blockers, or verification changes the plan.
- Stop correction loops when completion needs user choice, external coordination, new permissions, unavailable required evidence, or a persistent blocker that cannot be resolved safely within scope.

## Completion Standard

The mission is complete only when:

- the user's acceptance criteria are implemented in the final integrated workspace;
- backend producers and frontend/headless consumers share the same contract;
- every submitted or persisted field has one backend-custodied contract revision approved by frontend, with matching visible format, transport grammar, server validation, canonical value, accessible errors, and shared bypass/edge-case evidence;
- content, rendered semantics, metadata, social output, and structured data agree where applicable;
- frontend and content/SEO have jointly approved hierarchy, type roles/measure, real and edge copy lengths, text-media relationships, responsive/translated behavior, and the rendered reading journey;
- trust boundaries, permissions, raw sinks, privacy/cache scope, and failure paths are verified;
- applicable common AI-generated backend security failure rows pass, including user-A/object-B reference substitution for every private object path and protected/unknown-field mass-assignment attempts for every mutation;
- applicable backend latency, query count/time, memory, payload, concurrency, remote/job and cache behavior meet the recorded budget under representative cold, warm, maximum-cost and failure scenarios without weakening security;
- direct navigation, client navigation, relevant render modes, responsive/input states, locales, and cache invalidation are exercised as applicable;
- focused and spanning verification passes;
- QA reports `100% compliant with applicable verified requirements`, or the user receives a precise non-compliant/unverified handoff when a real blocker remains.

## Do Not

- Do not spawn every role for every request.
- Do not assign two agents concurrent write ownership of the same file, field group, route key, component, or shared contract.
- Do not ask frontend to guess backend payloads or ask backend to encode a visual layout into domain fields without a shared decision.
- Do not let frontend and backend maintain different form grammars, regexes, normalization rules, limits, or field error meanings, and do not treat client-side filtering as server validation.
- Do not let the content/SEO worker invent facts, or let implementation diverge from the approved visible content/entity contract.
- Do not let frontend force meaningful copy into fixed dimensions by shrinking, clipping, or hiding it, and do not let content/SEO ignore the spatial, responsive, media, or accessibility consequences of its copy.
- Do not treat security or QA as cosmetic final passes.
- Do not accept a faster backend path that weakens authorization, validation, CSRF, cache partitioning, workload bounds, rate limits, safe SQL/HTTP, invalidation, or fail-closed behavior.
- Do not merge agent output blindly, overwrite unrelated user changes, hand-edit `dist/`, or hide failed/unrun verification.
- Do not expand from implementation into deployment, publication, destructive migration, or external communication unless the user authorized it.
- Do not call the work complete because each role finished independently; completion belongs to the integrated and verified result.
