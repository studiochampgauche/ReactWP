# Content Strategy and Briefs

Use this reference before drafting a new page, planning a section, consolidating overlapping content, or creating a reusable editorial system.

## Start With the Page Job

Define the page in one sentence:

> For `[audience in a specific situation]`, this page helps them `[complete a task or make a decision]` by providing `[distinctive value/proof]`, leading naturally to `[next action]`.

If that sentence contains several unrelated jobs, split the experience or establish one dominant job with clearly secondary paths.

## Discovery Inputs

Collect only what changes the result:

- business objective and meaningful conversion;
- primary and secondary audiences, including their knowledge level;
- problem, trigger, objections, risks, constraints, and vocabulary;
- primary intent and adjacent questions;
- page type, route, locale, lifecycle, and owner;
- products/services/entities the page can truthfully describe;
- first-party proof: subject-matter expertise, process, examples, data, policies, outcomes, limitations, and authorship;
- user-supplied reference, inspiration, competitor, or exemplary URLs, with a note about what should be learned from each rather than copied;
- current competing/overlapping pages and intended internal-link relationships;
- legal, accessibility, brand, privacy, or regulated-content constraints.

Do not block a useful draft for harmless gaps. State reasonable assumptions. Pause for facts when guessing could create a false promise, inaccurate eligibility, regulated advice, fabricated proof, or the wrong conversion path.

## Intent and Journey Model

Classify intent only to guide content decisions, not to force a template:

| Intent | Reader needs | Useful page behavior |
| --- | --- | --- |
| Learn | A reliable explanation or method | Answer early, define terms, show examples and next questions |
| Compare | Tradeoffs and decision criteria | Use an honest comparison, constraints, fit/non-fit, and evidence |
| Evaluate | Proof, trust, implementation detail | Show process, outcomes, limitations, people, policies, and risk reduction |
| Act | A clear path to complete a task | Reduce uncertainty, expose requirements, make the next action obvious |
| Navigate | The correct entity or destination | Confirm identity and route quickly without padding |

Map the likely previous and next page. A strong page belongs to a journey, not just a keyword list.

## Topic and Internal-Link Architecture

Use a hub/supporting-page model when a subject genuinely needs several intents or depths:

- the hub owns the broad task and routes readers to deeper needs;
- supporting pages answer narrower, distinct questions completely;
- anchor text describes the destination naturally;
- reciprocal links exist only when useful;
- no two pages make the same promise to the same audience;
- canonicalization is not a substitute for fixing avoidable duplication.

Record proposed links as `source -> anchor purpose -> destination -> reader benefit`. Confirm the destination exists and is indexable before publishing.

## Evidence Ledger

Maintain a small source-of-truth table for claims:

| Claim | Status | Source/owner | Freshness | Allowed wording |
| --- | --- | --- | --- | --- |
| Verifiable fact | verified/pending | primary source URL or internal owner | date checked | exact supported claim |
| Customer outcome | approved/pending | case study or signed approval | period | scope and caveat |
| Product capability | current/pending | code/product owner | release/version | precise behavior |

Remove or qualify claims that cannot be supported. For current facts, prefer primary sources and attach the date verified.

Treat reference sites as inputs for pattern analysis: identify useful information order, proof types, depth, voice, and missing opportunities, then create an original page for this audience. Never reuse distinctive phrasing, proprietary data, testimonials, or unsupported claims from an inspiration source.

## Brief Template

Use this compact structure:

```text
Page/route:
Locale and market:
Owner and review date:
Audience and situation:
Primary page job/search intent:
Reader's key questions and objections:
Promise and unique angle:
Known entities and terminology:
Evidence and approved claims:
Primary action / secondary action:
Outline and answer order:
Enrichment modules that earn their space:
Internal links in / out:
Metadata direction:
Structured-data candidates and proof gaps:
Frontend/backend/security handoff:
Acceptance criteria:
```

## Multilingual and Local Content

- Use `route.lang` to identify the ReactWP route locale.
- Research the audience's terms, examples, laws, units, availability, and calls to action per market.
- Transcreate the promise and metadata; do not translate a keyword list mechanically.
- Ensure every localized route has the correct visible language, URL relationship, canonical, and reciprocal hreflang strategy when implemented.
- Do not publish a thin locale merely to create more indexable URLs.
