# On-Page SEO and Metadata

Use this reference after the visible content and page purpose are clear. Metadata should summarize and distribute the page's real value, not compensate for weak content.

## Page-Level Specification

For each indexable page, consider:

- one descriptive `<title>` aligned with the visible page title and intent;
- one clear main heading that describes the page, without requiring an exact copy of the title;
- a unique meta description that gives a useful, accurate reason to visit;
- a stable, readable route and canonical decision;
- descriptive section headings and internal links;
- crawlable text for essential information;
- image filenames/context/alt text appropriate to their purpose;
- indexing, language, and duplication rules;
- Open Graph and optional platform-specific social metadata;
- structured data supported by visible content and known entities.

Search systems can derive title links from several signals, including the title element, visible headings/prominent text, Open Graph title, anchors, and site-level data. Keep those signals consistent without making them identical by formula.

## Titles and Descriptions

Write titles that are:

- specific to the page;
- recognizable without navigation context;
- naturally front-loaded with the differentiating subject;
- concise enough to avoid boilerplate dominating the useful part;
- consistent with the visible heading and site identity;
- unique across indexable routes.

Write meta descriptions as compact previews of the page's actual help, proof, audience, or outcome. Avoid keyword lists, generic brand slogans, and duplicating the same description across many pages.

Do not use a fixed character count as a pass/fail rule. Display truncation varies, and search engines may select page text instead. Review clarity at several viewport/device contexts and put the most distinguishing information early.

## Social Metadata

Specify separately when the social preview benefits from a different emphasis:

- `og:title`, `og:description`, `og:type`, `og:url`, `og:site_name`, and `og:image`;
- article publication/modification/author data when applicable;
- an image concept, crop/safe area, dimensions, format, alt/description support, and ownership rights;
- Twitter/X Card fields only if the project implements them.

The bundled `reactwp-seo` plugin already provides its documented Open Graph fields but does not provide Twitter Card metadata. Do not ask editors to fill duplicate fields unless the outputs genuinely need different copy.

## Canonical URLs

- Choose the preferred public URL for genuinely duplicate or near-duplicate variants.
- Keep internal links and sitemap URLs pointed at the canonical form.
- Use absolute HTTP(S) URLs and one consistent trailing-slash/query policy.
- Do not canonicalize distinct localized or substantively different pages to one language by default.
- Do not use canonical as a substitute for redirects when an old URL has permanently moved.

`useDocumentMeta` can synchronize a safe canonical `<link>` supplied through `route.head`, but `reactwp-seo` does not currently generate that entry. Canonical implementation therefore belongs to a project/backend contract and must be tested on direct and client navigation.

## Hreflang and Language

- Create alternate language/region links only for equivalent localized pages.
- Use valid language/region values and reciprocal relationships.
- Include the page itself in the alternate set when implementing a complete cluster.
- Decide whether `x-default` has a real selector/default destination.
- Keep localized canonicals self-referential unless a deliberate duplicate-content decision says otherwise.
- Align HTML/document language, `route.lang`, visible language, alternate links, and URL structure.

ReactWP's head allowlist accepts safe HTTP(S) `link[rel="alternate"]` entries and preserves bounded `hreflang`, but the project must generate the correct relationships.

## Robots and Indexing

Indexing is a product/content decision, not a generic cleanup toggle. Before changing it, consider whether the URL is useful, unique, linked, canonical, public, and intended to appear in search.

The bundled plugin already applies:

- search results: `noindex, follow`;
- 404, a disabled WordPress search-visibility setting, or content/user/term `do_not_index`: `noindex, nofollow`;
- normal eligible content: `index, follow`;
- all cases: `max-image-preview: large`.

Verify the actual WordPress response. Do not create a competing meta-robots mechanism without a single ownership decision. Remember that blocking crawling can prevent a crawler from seeing a `noindex` directive.

## Images and Media

- Use relevant original media where it adds evidence or understanding.
- Provide dimensions/aspect ratio so the frontend can avoid layout shift.
- Write alt text for purpose and context; do not append keywords unnaturally.
- Keep essential meaning in nearby crawlable text, captions, or transcripts.
- Verify image URLs are public, stable, and allowed to be indexed/shared.
- Choose an OG image that remains understandable when cropped or displayed small.

## Internal Links

- Link at the point where the destination resolves the reader's next question.
- Use concise descriptive anchors; vary naturally when the purpose differs.
- Prefer the canonical destination and avoid chains, broken links, and orphaned priority pages.
- Keep navigation, breadcrumbs, contextual links, related content, and footer links purposeful rather than mechanically exhaustive.

## Current Primary References

Recheck these official sources when the decision is time-sensitive:

- [Google title link guidance](https://developers.google.com/search/docs/appearance/title-link)
- [Google snippet and meta description guidance](https://developers.google.com/search/docs/appearance/snippet)
- [Google image SEO guidance](https://developers.google.com/search/docs/appearance/google-images)
- [Google canonical guidance](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Google robots meta guidance](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag)
