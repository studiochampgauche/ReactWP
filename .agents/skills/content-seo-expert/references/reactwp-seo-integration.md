# ReactWP SEO Integration

This reference records the current authored-source contract. Reinspect `src/plugins/reactwp-seo/` and `src/themes/reactwp/js/inc/useDocumentMeta.js` before changing implementation; source code overrides this summary if ReactWP evolves.

## What `reactwp-seo` Provides

Through `rwp_wp_head`, the bundled plugin currently emits supported entries for:

- charset, X-UA-Compatible, and viewport;
- document title and meta description;
- Open Graph type, URL, site name, title, description, and image;
- `profile:first_name`, `profile:last_name`, and `profile:username` for author/user contexts;
- `article:published_time`, `article:modified_time`, and `article:author` for post/article contexts;
- a 192 x 192 favicon link.

It also filters WordPress robots directives:

- searches receive `noindex, follow`;
- 404, WordPress search visibility disabled, or an applicable `do_not_index` value receive `noindex, nofollow`;
- otherwise content receives `index, follow`;
- `max-image-preview: large` is added.

## ACF Fields It Registers

The content SEO group is active and `show_in_rest = 1` for posts, pages, all users, all taxonomies, and globally selected additional public post types. It provides:

- `seo.do_not_index`;
- `seo.og_image` as a URL;
- for each configured language: `seo.title_<code>`, `seo.description_<code>`, `seo.og_title_<code>`, and `seo.og_description_<code>`.

The Site Settings SEO group is active and `show_in_rest = 1`. It provides:

- `seo.post_types` to opt additional public post types into content fields;
- `seo.favicon`, constrained to 192 x 192;
- `seo.og_image`;
- for each configured language: global `seo.description_<code>`, `seo.og_title_<code>`, and `seo.og_description_<code>`.

There is no global SEO title field; the site name and route/page fallbacks own that role.

## Route Resolution and Fallbacks

For route-aware output, `route.lang` is canonical and `route.language` is only a compatibility fallback.

| Output | Resolution order |
| --- | --- |
| Title | `route.seo.title_<lang>` -> `route.seo.title` -> `<route.pageName> - <site name>` |
| Description | `route.seo.description_<lang>` -> `route.seo.description` -> contextual `seo_description_<CL>` -> global `seo_description_<CL>` |
| OG title | `route.seo.og_title_<lang>` -> `route.seo.og_title` -> resolved title |
| OG description | `route.seo.og_description_<lang>` -> `route.seo.og_description` -> resolved description |
| OG image | `route.seo.og_image` -> contextual/global `seo_og_image` |
| OG URL | `route.url` -> current queried-object/request URL |
| OG type | `route.seo.og_type` -> post `article`, user `profile`, otherwise `website` |

Content recommendations should populate existing localized fields first. Add fields only when a real output cannot be expressed by the current contract.

`window.RWP_SEO` is localized only when the main ReactWP script is enqueued and represents global option SEO values. Route templates should prefer `route.seo` and runtime defaults rather than treating this browser global as a universal API.

## What It Does Not Provide

The current plugin does not generate:

- canonical link elements;
- Twitter/X Card metadata;
- JSON-LD or another structured-data script;
- an XML sitemap.

WordPress core or another installed component may separately provide some behavior in a deployed project. Verify the final response and active plugins instead of assuming ReactWP owns it.

## Integrated Theme vs External Headless

The editorial and entity contract can be shared, but delivery ownership changes:

| Concern | Integrated ReactWP theme | External headless consumer |
| --- | --- | --- |
| Page metadata | `rwp_wp_head` -> `route.head` -> `useDocumentMeta` | Consumer framework's metadata/head API |
| Route SEO content | `route.seo` and ReactWP runtime defaults | Public normalized ReactWP payload mapped by the consumer |
| Canonical/hreflang | Project `rwp_wp_head` extension compatible with the allowlist | Consumer/public-origin router owns final URLs and head links |
| JSON-LD | Dedicated ReactWP route object plus route-aware renderer | Consumer owns JSON-LD rendering from the public data contract |
| Sitemap | WordPress core, a project/plugin implementation, or deployment layer | Public-origin application/framework or another explicit owner |

Do not assume PHP head output from the WordPress origin configures a separate frontend origin. In headless mode, verify the final consumer response, public canonical host, rendering mode, and navigation behavior.

## Head and Client-Navigation Contract

`RouteResolver` collects head entries through `rwp_wp_head` into `route.head`. On client navigation, `useDocumentMeta` clears its managed route nodes and accepts at most 100 bounded entries containing only:

- `<title>`;
- bounded `<meta>` with a valid `name`, `property`, charset, or X-UA-Compatible form;
- safe HTTP(S) `<link>` with `rel` in `alternate`, `apple-touch-icon`, `canonical`, `icon`, or `manifest`.

It rejects scripts, styles, arbitrary elements, unsafe link schemes, credentials in URLs, and unsupported link relations. This means:

- canonical, hreflang, Twitter Card meta, and similar supported meta/link entries can be implemented through a carefully escaped `rwp_wp_head` extension and remain compatible with client navigation;
- JSON-LD cannot be implemented as an arbitrary string in `route.head` and expected to synchronize during route changes;
- any new head contract must work for the direct WordPress response and the route payload used by React.

## Structured-Data Implementation Pattern

When JSON-LD is required:

1. `content-seo-expert` defines the truthful entity graph, page mapping, source facts, locale behavior, and editorial acceptance criteria.
2. `backend-expert` defines a bounded structured object and its WordPress/ACF/route ownership. Use a deliberate property such as a project-owned `route.data.structuredData` or a reviewed new route key; do not smuggle script HTML through a text field.
3. `security-expert` validates allowed types/properties/data sources, public exposure, URL policy, bounds, and safe JSON serialization. Never concatenate raw CMS strings into executable script markup.
4. `frontend-expert` owns a single route-aware JSON-LD manager that replaces stale page data on navigation and remains compatible with client, static, and server rendering.
5. If direct PHP output also emits JSON-LD, both paths serialize the same normalized graph and must not produce divergent or duplicate entities.

The exact property name is a project contract, not a built-in ReactWP capability. Prefer a small explicit extension over weakening `useDocumentMeta` to accept arbitrary scripts.

## Responsibility Handoff

| Decision/change | Owning skill |
| --- | --- |
| Audience, intent, copy, proof, title/description recommendation, internal links | `content-seo-expert` |
| ACF groups, WordPress storage, `rwp_wp_head`, route shape, headless REST, sitemap/plugin code | `backend-expert` |
| Rendered semantics, heading/component behavior, route-aware head/schema manager, accessible media | `frontend-expert` |
| HTML/head/URL/JSON-LD trust boundary, public data, validation, serialization, permissions | `security-expert` |

## Verification Checklist

- Inspect the page on a direct request and after at least one internal navigation from a different page.
- Confirm old route-managed title/meta/link/schema nodes are removed.
- Confirm route locale uses `route.lang` and every localized field resolves as intended.
- Confirm fallback behavior with empty page fields and global defaults.
- Inspect title, description, OG type/URL/title/description/image, article/profile properties, favicon, canonical/hreflang additions, and robots output as applicable.
- Verify 404, search, `do_not_index`, and WordPress search-visibility cases before changing indexing behavior.
- Test client, static, and server rendering when shared head/schema code changes.
- Use focused ReactWP tests plus external validators; neither replaces inspecting the final response and DOM.
