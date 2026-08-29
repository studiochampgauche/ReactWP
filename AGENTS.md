# ReactWP Agent Instructions

## Repository invariants

- Inspect the closest current implementation before proposing or changing a pattern.
- Author source changes in `src/` and build-tooling changes in `configs/`.
- Treat `dist/` as generated output; never hand-edit it.
- Run Node/npm commands from `configs/`.
- Preserve integrated, headless, client, static, and server compatibility wherever the touched contract supports those modes.
- Preserve ReactWP's registry, route, loader, transition, rendering, cache, and media contracts unless the task explicitly changes them.
- Preserve user-owned tracked and untracked work. Never overwrite unrelated changes.
- Never place secrets, licenses, tokens, private download URLs, or credentials in source or documentation.
- Use the smallest relevant verification and report anything that could not be exercised.

## Skill router

Read the applicable `SKILL.md` completely, then only the references it routes for the affected behavior.

- React, SCSS, accessibility, responsive behavior, frontend performance, media, loaders, transitions, scrolling, motion, or visual references: `.agents/skills/frontend-expert/SKILL.md`
- WordPress, PHP, ACF, hooks, routes, REST, data contracts, rendering, cache, integrations, migrations, or backend performance: `.agents/skills/backend-expert/SKILL.md`
- Audience, information architecture, copy, metadata, internal links, robots, social previews, entities, schema, or `reactwp-seo`: `.agents/skills/content-seo-expert/SKILL.md`
- Untrusted input/output, URLs, permissions, authentication, REST, SQL, files, external requests, caches, secrets, dependencies, or deployment boundaries: `.agents/skills/security-expert/SKILL.md`
- Final QA, release readiness, compliance, or regression evidence: `.agents/skills/quality-assurance-expert/SKILL.md`
- A complete product or substantial change with at least two independent expert workstreams: `.agents/skills/reactwp-orchestrator/SKILL.md`

Work directly with the relevant expert for a small localized change. Do not add orchestration or load unrelated expert references merely because they exist.

## Cross-skill triggers

- Use frontend and content/SEO together when editorial changes materially affect hierarchy, SEO, CMS variability, responsive measure, media, structured data, or layout. A typo, isolated label, or wording correction that preserves meaning, metadata, and composition does not require the tandem.
- For any submitted or persisted user-editable field, use frontend, backend, and security with one backend-custodied field contract from `.agents/skills/backend-expert/references/form-field-contracts.md`.
- For a backend trust boundary, project-owned endpoint, private object path, mutation, upload, integration, or dependency change, use backend and security. Follow `.agents/skills/security-expert/references/common-ai-backend-security-failures.md` where routed.
- QA first maps affected domains, then loads only applicable expert skills and routed references. Release-wide and cross-layer audits load all four product-domain skills.
- Never assign concurrent conflicting ownership of the same mutable file, field, route key, component, or shared contract, regardless of whether work happens in one workspace, separate branches, or worktrees.

## Documentation synchronization

- Every public API, component, runtime behavior, configuration, workflow, or developer-facing change must update the relevant source documentation in `../reactwp-website/src/docs/` during the same task.
- A changelog entry alone does not replace user-facing documentation.
- Run the documentation build from `../reactwp-website/configs/`. If that checkout or verification is unavailable, report it explicitly.

## Verification

Choose the smallest checks that prove the changed behavior. A successful build proves compilation only; runtime, visual, responsive, accessibility, security, rendering-mode, or content claims need their own applicable evidence.
