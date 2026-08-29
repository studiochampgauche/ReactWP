# Structured Data and Entities

Use this reference to model what a page is about and propose structured data that is truthful, stable, maintainable, and compatible with the rendered page.

## Model Entities Before Markup

List the real entities and relationships first:

- organization or person publishing the site;
- website and page;
- author, editor, service, product, location, event, article, creative work, or other primary subject;
- breadcrumbs and parent/child relationships;
- images, logos, dates, offers, ratings, and identifiers that are actually known.

Give reusable first-party entities stable absolute `@id` URLs, commonly canonical page URLs plus fragments such as `#organization`, `#website`, or `#person`. Refer to the same `@id` instead of duplicating slightly different versions of an entity on every page.

## Selection Rules

- Choose the most specific valid Schema.org type the content actually supports.
- Treat [Schema.org](https://schema.org/) as the vocabulary and current search-engine documentation as the source for feature eligibility and required/recommended properties.
- The main structured-data type should represent the page's main visible subject.
- Add page-specific types only when the corresponding information is visible, accurate, current, and maintained.
- Prefer a small complete graph over a large graph filled with guessed, empty, or irrelevant properties.

Common candidates include `WebSite`, `Organization` or `Person`, `WebPage`, `BreadcrumbList`, `Article`/`BlogPosting`, `Service`, `Product`, `LocalBusiness`, and `Event`. A candidate is not automatically eligible for a rich result.

## JSON-LD Shape

JSON-LD is generally the preferred implementation format. Build a structured object and serialize it as JSON; do not concatenate user-controlled script strings.

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "Verified organization name",
      "url": "https://example.com/"
    },
    {
      "@type": "WebPage",
      "@id": "https://example.com/example/#webpage",
      "url": "https://example.com/example/",
      "name": "Visible page subject",
      "isPartOf": { "@id": "https://example.com/#website" },
      "about": { "@id": "https://example.com/#organization" }
    }
  ]
}
```

This is a shape example, not a required graph. Add only supported relationships, and ensure every referenced `@id` is defined or intentionally external.

## High-Risk Properties

Require explicit evidence for:

- `review`, `aggregateRating`, rating counts, and testimonials;
- `offers`, price, currency, availability, condition, and validity dates;
- professional credentials, awards, affiliations, and legal names;
- event dates, attendance mode, location, status, and ticket state;
- medical/financial claims or regulated service details;
- authorship, publication date, and modification date.

Never invent missing values to satisfy a validator. Self-serving review markup and hidden/misleading structured data can be ignored or create policy problems.

## Page-Type Guidance

### Article or BlogPosting

Confirm the page is genuinely editorial content. Connect headline/name, author, dates, primary image, publisher, and canonical page. `dateModified` should change for meaningful content updates, not every deploy.

### Organization, Person, and LocalBusiness

Use authoritative identity data: official name, canonical URL, logo/image, contact/location details, and legitimate same-as profiles. Choose `LocalBusiness` only when a real local business entity/location is being described.

### Product, Offer, Service, and Review

Separate the thing offered from the commercial offer. Keep price/availability synchronized with the visible page and source of truth. Do not mark a generic service page as a retail product merely to reach a feature.

### FAQ

Questions and answers must be visible and equivalent to the markup. Verify current search-feature eligibility before proposing it; useful FAQ content can remain worthwhile even without a rich result.

## ReactWP Constraint

ReactWP's `useDocumentMeta` deliberately accepts title, bounded meta tags, and selected safe HTTP(S) links from `route.head`; it rejects scripts. Therefore, a JSON-LD `<script>` returned only through `rwp_wp_head` may appear in direct PHP output but will not remain synchronized automatically during client navigation.

Use a dedicated, reviewed structured-data contract for integrated React navigation, implemented with `backend-expert`, `frontend-expert`, and `security-expert`. The content skill should supply a structured object/schema specification, not arbitrary script HTML.

## Validation

1. Validate JSON syntax and the chosen Schema.org types/properties.
2. Use Google's Rich Results Test only for Google-supported features.
3. Compare every material property with visible page content and the source of truth.
4. Test direct load, static/server rendering, hydration, and internal client navigation.
5. Inspect the final DOM/source to ensure stale page schema is removed on route change.
6. Revalidate after content-model, URL, entity, template, or plugin changes.

Passing a validator does not guarantee indexing, a rich result, ranking, or display.

## Current Primary References

- [Google structured data introduction](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)
- [Google general structured data guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Google supported structured data features](https://developers.google.com/search/docs/appearance/structured-data/search-gallery)
- [Google Article structured data](https://developers.google.com/search/docs/appearance/structured-data/article)
- [Google Organization structured data](https://developers.google.com/search/docs/appearance/structured-data/organization)
- [Schema.org vocabulary](https://schema.org/)
