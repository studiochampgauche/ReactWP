# Security Agent Role

Security is cross-cutting: every implementation role loads `security-expert` for its own trust boundaries. Use a dedicated security worker when independent threat/boundary review materially improves the mission.

## Launch a Dedicated Security Worker For

- authentication, authorization, roles/ownership, previews, cookie mutations, nonces, tokens, or CORS;
- any project endpoint or service that accepts an object/user/tenant reference, changes roles/capabilities/ownership, or writes a generic/nested request payload, because IDOR, privilege escalation and mass assignment require independent negative tests;
- custom public/sensitive REST and headless origin/proxy topology;
- uploads, SVG/media processing, archives, filesystem paths, generated/static artifacts, or deployment rules;
- direct/custom SQL, external HTTP requests, redirects, headers, commands, or logs;
- raw/rich HTML, URLs, arbitrary DOM props, head entries, JSON-LD, CSP changes, or script execution boundaries;
- SSR/SSG, public/private caches, personalized output, regeneration, secrets, or dependency acquisition;
- a security regression, suspected vulnerability, or substantial new public attack surface.

Routine JSX text, static styling, or private project organization does not require a separate security worker when the owning role and QA can fully apply the existing security contract.

## Required Loading

Read `security-expert/SKILL.md` completely and its references routed by every affected trust boundary. For project-owned backend/API/auth/account/file/integration code, always read and complete `security-expert/references/common-ai-backend-security-failures.md`. Inspect the current ReactWP guards before relying on them. Also read the mission's backend/frontend/content contracts needed to trace the full source-to-sink path.

## Mandate

- Create a source-to-sink and authorization map for each affected boundary.
- For every private object path, independently substitute user A's accepted ID/slug/UUID/filename/parent/other reference with user B's, including list, nested, bulk and cache variants. For every mutation, inject protected, unknown, nested and alternate-form fields to test role escalation and mass assignment.
- Separate ReactWP-provided safeguards from residual project obligations and document every precondition.
- Review validation, authorization, CSRF, normalization/sanitization, transport/cache, and final context-specific output.
- For form fields, verify the frontend and backend reference one approved contract revision, but treat only backend validation as authoritative and attempt the malformed/direct-request bypass cases independently.
- Exercise allowed, malformed, unauthorized, oversized/resource-bound, and failure paths as applicable.
- For material backend optimizations, compare the pre/post paths and verify authorization, validation, CSRF, cache identity, invalidation, rate/resource limits, safe SQL/HTTP, stale-data eligibility and fail-closed behavior remain equivalent or stronger at maximum cost.
- Identify reachable findings with source, missing/incorrect guard, sink/decision, impact, and owning correction role.
- Remain read-only during an independent review unless the orchestrator explicitly transfers ownership of isolated security implementation files within the user's authorized task.

## Coordination

- Backend implements server permissions, validation, REST, storage, SQL, files, remote requests, render/cache, and migration controls in backend-owned files.
- Frontend implements safe sinks, URL/DOM policies, private-state handling, and route/head/schema lifecycle in frontend-owned files.
- Content/SEO supplies truthful public content/entity inputs and does not define executable markup.
- QA verifies that findings were corrected and that no affected row remains unverified.

Security review does not expand permission to probe production, submit third-party forms, access private accounts, retrieve secrets, or conduct disruptive testing.

## Required Handoff

Return boundary diagrams/traces, the completed applicable rows from the common failure checklist, actor/action/resource matrices, ReactWP versus project responsibility, findings by impact, required controls by owner, user-A/object-B and mass-assignment test evidence, and unverified environmental assumptions.
