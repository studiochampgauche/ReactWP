# Audits, Measurement, and Maintenance

Use this reference for site/page reviews, pre-publish QA, content refreshes, consolidation, and post-launch learning.

## Audit Layers

### 1. Purpose and usefulness

- Is the audience and page job clear?
- Does the page answer the primary need early?
- Is there original value, evidence, or a genuinely useful action?
- Are objections, constraints, and next steps addressed?
- Is the page materially distinct from other indexable pages?

### 2. Editorial integrity

- Are claims accurate, sourced, approved, and current?
- Are unsupported superlatives, vague promises, and fabricated specificity removed?
- Is the voice consistent and the reading level appropriate?
- Do headings, links, tables, media, and calls to action remain understandable out of ideal desktop context?

### 3. On-page and discovery

- Do title, main heading, description, social preview, and visible promise agree?
- Are canonical, indexing, locale, internal links, images, and redirects deliberate?
- Are important pages reachable through useful crawlable links?
- Does structured data describe the visible page and stable entities accurately?

### 4. ReactWP delivery

- Are the correct `seo.*_<lang>` fields populated?
- Does direct load match client navigation?
- Are route-managed head nodes updated and stale nodes removed?
- Does the implementation behave under client, static, and server rendering?
- Are missing capabilities correctly assigned to frontend/backend/security rather than attributed to `reactwp-seo`?

## Pre-Publish Checklist

```text
[ ] Page job, audience, locale, and owner are recorded
[ ] Visible content is final and fact-checked
[ ] Claims have evidence and time-sensitive claims have a date
[ ] Heading hierarchy and links work in the rendered page
[ ] Media has rights, dimensions, accessible alternatives, and useful context
[ ] SEO title and description are unique and consistent with the page
[ ] OG copy/image/type/url are correct
[ ] Canonical, hreflang, robots, and redirect decisions are explicit
[ ] Structured data matches visible facts and validates where relevant
[ ] Direct load and internal navigation yield the same current metadata/schema
[ ] Empty/fallback and relevant locale cases were checked
[ ] Analytics/search measurement respects consent and privacy requirements
[ ] Content owner and next review trigger are defined
```

## Measurement Plan

Choose metrics tied to the page job:

- discovery: valid impressions, indexed/eligible coverage, query/page mix;
- engagement: meaningful reading/navigation signals, not raw time alone;
- completion: form completion, contact, download, purchase, tool use, or another defined action;
- quality: assisted conversion, qualified lead rate, task success, support deflection, or return visits;
- integrity: broken links, stale claims, invalid schema, unpublished dependencies, or accessibility regressions.

Record a baseline, release date, material changes, and comparison window. Separate correlation from causation. Do not promise a ranking or attribute change to copy alone when design, links, seasonality, competition, indexing, deployment, or measurement also changed.

## Refresh Triggers

Refresh content when:

- prices, availability, people, policies, product behavior, statistics, laws, or eligibility changed;
- user/search intent shifted;
- evidence or examples became stale;
- a page receives impressions but fails to satisfy the intended task;
- several pages overlap and confuse users/search systems;
- templates, routes, language structure, or ReactWP metadata/schema contracts changed.

Do not update publication/modification dates solely to simulate freshness. Tie dates to meaningful changes and keep structured data consistent.

## Keep, Improve, Consolidate, Redirect, or Remove

Evaluate each URL using user value, business value, uniqueness, quality, traffic, links, conversions, dependencies, and legal/archival needs.

- **Keep:** page fulfills a distinct durable job.
- **Improve:** job is valid but usefulness, evidence, presentation, or discoverability is weak.
- **Consolidate:** multiple pages compete for the same job; merge the best content and redirect intentionally.
- **Redirect:** destination is the clear successor and preserves user intent.
- **Remove/noindex:** page should remain accessible but not discoverable, or has no successor; make this an explicit product decision.

Before any destructive content or URL action, inventory inbound internal links, external links, live campaigns, integrations, translations, cache/static artifacts, and redirects. Load `backend-expert` for route/migration/invalidation work and `security-expert` if private/public visibility changes.
