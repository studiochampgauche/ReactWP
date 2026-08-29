# Editorial Writing and Enrichment

Use this reference to draft or improve visible page content and decide which enriched modules deserve frontend/backend implementation.

## What “Powerful” Content Means

Power comes from usefulness and specificity, not inflated language. Strong content usually has:

- an opening that confirms the reader is in the right place;
- a clear answer or value proposition before background detail;
- a recognizable point of view grounded in real expertise;
- precise nouns, verbs, constraints, examples, and outcomes;
- proof near the claim it supports;
- an honest description of fit, non-fit, tradeoffs, and next steps;
- a structure readers can scan without losing the argument;
- a close that makes the next action proportionate and clear.

Write in the brand's actual voice. Avoid generic “unlock,” “revolutionize,” “cutting-edge,” and unsupported superlatives unless they are deliberately part of that voice and can be substantiated.

## Drafting Sequence

1. Write the page's one-sentence promise and the first useful answer.
2. Order sections by reader need, not internal organization chart.
3. Add proof, examples, limitations, and decision support at the exact point of doubt.
4. Remove paragraphs that merely restate headings or metadata.
5. Tighten headings so they carry meaning out of context.
6. Finish metadata only after the visible page has stabilized.

## Enrichment Menu

Choose enrichment because it helps complete the page job:

| Need | Useful enrichment |
| --- | --- |
| Understand a process | Numbered steps, diagram, worked example, downloadable checklist |
| Compare choices | Honest comparison table, decision tree, fit/non-fit criteria |
| Trust a claim | Methodology, source, expert attribution, case evidence, limitations |
| See the outcome | Before/after with context, demo, gallery, annotated media |
| Complete a task | Calculator, template, form, code example, tool, requirements list |
| Resolve objections | Contextual FAQ, policy summary, timeline, cost drivers, constraints |
| Navigate a complex topic | Contents, glossary, related guides, progressive disclosure |

Do not add every module to every page. A component should either improve comprehension, confidence, completion, or discovery.

## FAQ Rules

- Use genuine recurring questions, support issues, sales objections, or necessary clarifications.
- Answer completely in the visible page.
- Avoid paraphrasing one query into several near-identical questions.
- Do not create FAQ solely to obtain a search treatment; eligibility and search features change.
- If FAQ structured data is proposed, verify current official eligibility and ensure markup matches visible questions and answers exactly.

## CMS and Component Fit

Content structure should be reusable but not layout-bound:

- model meaning such as `intro`, `steps`, `proof`, `comparison`, or `cta`, not `left_column_text`;
- define required/optional fields, length resilience, empty states, link behavior, and media alternatives;
- keep plain text plain; use rich text only when authors need controlled inline/block markup;
- preserve heading order at render time rather than letting arbitrary CMS fragments create multiple page-level headings;
- identify whether editors can reorder modules without breaking the narrative;
- specify which facts are shared entities and which are page-specific copy.

Load `backend-expert` when this requires ACF or route-contract changes, and `frontend-expert` when it requires new rendered components or editorial layouts.

## Accessibility and Inclusive Language

- Make link text meaningful without surrounding context.
- Write alternative text for the image's purpose in this context; use empty alt for truly decorative images.
- Provide captions/transcripts when media carries information not available in nearby text.
- Do not encode meaning only through color, position, animation, or visual metaphor.
- Expand uncommon abbreviations and explain necessary specialist language.
- Avoid manufactured urgency, hidden conditions, or calls to action that misstate what happens next.

## Factual Integrity Pass

Before delivery:

- tie each material claim to supplied evidence or a primary source;
- attach dates to time-sensitive numbers, prices, availability, rankings, policies, and product behavior;
- distinguish examples from typical results;
- verify names, roles, credentials, quotations, locations, and links;
- remove fabricated specificity instead of making prose sound more authoritative;
- flag review requirements for legal, medical, financial, safety, or regulated statements.
