# Content and SEO Quality Gate

Use this gate when visible content, information architecture, internal links, metadata, robots, social previews, canonical/hreflang, entities, structured data, locales, or `reactwp-seo` behavior can be affected. Read the routed `content-seo-expert` references and check time-sensitive search guidance against current official sources.

## Content Purpose and Integrity

- Content/SEO and frontend reference the same authoritative editorial composition matrix revision with one named custodian and both approvals, then jointly review the real rendered page before QA; divergent copies or independent completion claims do not satisfy this requirement.
- Audience, situation, primary page job/search intent, unique value, and next action are explicit and coherent.
- The page answers the primary need early and uses useful examples, proof, comparisons, media, FAQs, tools, or other enrichment only when they improve the task.
- Claims, statistics, reviews, credentials, awards, prices, availability, case outcomes, quotes, authorship, and dates are supplied or verified; gaps are marked rather than invented.
- Current facts have primary/first-party sources and freshness dates where material.
- Content is original and does not copy distinctive phrasing, proprietary facts, testimonials, or unsupported claims from inspiration/competitor sites.
- Heading/link/media structure remains understandable and accessible in the rendered page and resilient to CMS variation.
- Semantic levels, real/edge copy lengths, typography/measure, whitespace, media purpose/placement, responsive/translated behavior, and reading order preserve the intended meaning together.

## On-Page and Metadata

- The visible promise, `<title>`, main heading, description, Open Graph/social preview, internal anchors, canonical decision, and structured data describe the same page.
- Titles/descriptions are unique, useful, and concise without being judged by a rigid character-count guarantee.
- Canonical, redirects, robots, crawlability, sitemap ownership, and internal links are consistent with the intended public URL.
- Locale content is transcreated for its audience; `route.lang`, visible language, URL, canonical, and reciprocal hreflang relationships agree.
- Images/media have rights, stable public URLs, useful context/alternatives, and appropriate social crop/direction.

## Structured Data

- Entity types and relationships match the main visible subject and use stable absolute identifiers where appropriate.
- JSON-LD contains only supported, current, visible, and maintainable properties; ratings, reviews, offers, people, dates, and credentials have explicit evidence.
- Schema.org vocabulary and current search-feature eligibility are distinguished; validation is not described as a guarantee of display, indexing, or ranking.
- JSON syntax/schema validation and applicable rich-result tests pass, and final direct/client-rendered output contains current page data without stale/duplicate entities.

## ReactWP SEO Contract

- Current plugin source was inspected before attributing a capability.
- Existing localized `seo.title_<lang>`, `description_<lang>`, `og_title_<lang>`, `og_description_<lang>`, `og_image`, `do_not_index`, favicon, robots, and article/profile output are used consistently with their actual fallbacks.
- `route.lang` remains canonical, with `route.language` only a compatibility fallback.
- The bundled plugin is not falsely credited with canonical links, Twitter Cards, JSON-LD, or XML sitemap generation unless source has changed.
- Integrated theme metadata is checked on direct response and React client navigation. An external headless consumer is checked at its public origin/framework output.
- Arbitrary JSON-LD scripts are not placed in `route.head`; a dedicated reviewed backend/frontend/security contract owns structured data across navigation and render modes.

## Required Evidence

- Compare the brief/source facts with final rendered content and every metadata/schema representation.
- Inspect final response/DOM after direct load and at least one route transition from a different page.
- Check relevant index/noindex, 404, search, fallback, empty field, locale, and social-image cases.
- Use current official validators/guidance where eligibility or syntax is time-sensitive, while recording that passing does not guarantee search presentation.

Missing source evidence or an unavailable rendered/locale/navigation check prevents a 100% content/SEO verdict.
