# Immediate route leave verification

The navigation hook now starts leave before awaiting the route payload. Payload/template/critical preparation runs alongside leave; both must finish before the paint gate and router proceed. Committed critical display still gates entry.

## Automated evidence

- `node --test ./tests/route-transition.test.mjs`: 18 passing cases. Controlled promises cover slow payloads, slow critical preparation, fast preparation with unfinished leave, immediate and Promise animations, native/smooth scroll, hashes, superseded requests, cancellation, failures, route entry and first load.
- The timing regression test was also run against the original hook from Git HEAD and failed because leave had not started while the payload was pending. The temporary baseline files were removed.
- `node --test ./tests/rendering.test.mjs ./tests/animation-lifecycle.test.mjs ./tests/route-transition.test.mjs ./tests/template-assets-manifest.test.mjs`: 28 passing tests, including static generation and SSR rendering.
- The navigation tests are included in `npm run test:render`.
- `npm run build:themes` succeeded. After the cancellation follow-up changes, `npm run build:themes:js` and the 28-test suite passed again.
- Documentation was synchronized in `reactwp-website/src/docs/{page-transitions,routing-and-navigation,frontend-runtime}.md`; the documentation build from `reactwp-website/configs/` succeeded.
- Focused `git diff --check` passed.

## Browser evidence

Used the compiled theme, real React Router, Loader and GSAP in the Codex browser with a loopback HTTP fixture. Its route endpoint delays uncached responses by 1,800 ms. Measurements are milliseconds after the click and are observations of this local fixture, not production performance guarantees.

| Initial render | Leave starts | Leave finishes | Fetch response | Route changes | Enter starts | Enter finishes |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Client | 23 | 198 | 1818 | 1844 | 1864 | 2014 |
| Hydrated static | 22 | 201 | 1814 | 1842 | 1864 | 2014 |
| Hydrated server | 18 | 197 | 1815 | 1831 | 1863 | 2013 |

- Replacing a slow navigation with another route showed the latest heading, opacity 1 and released scrolling; the obsolete response did not change the destination.
- Returning to the current route during a slow fetch initially reproduced an invisible-page edge case. With the final correction, the same test restored opacity 1, retained the current heading and URL, and released scrolling. No console errors were recorded in the final hydrated fixture.
- Browser back and forward reached the expected cached routes with visible content and released scrolling.
- Initial static and server hydration completed without hydration warnings in the fixture.

## Scope and diagnostics

The browser endpoint uses fixture payloads, not a live WordPress database or a downstream project's custom animations. Reduced-motion completion and failure paths have controlled automated coverage; an OS-level reduced-motion browser pass was not performed.

Webpack reported a persistent-cache snapshot warning but compiled successfully. Docusaurus reported a Node localStorage experimental warning and generated the site successfully. An initial client fixture run recorded a ResizeObserver-loop notification; subsequent hydrated fixture runs had no recorded errors. These diagnostics were not treated as proof of a clean site-wide browser console.

Existing projects with customized leave factories must not assume the destination payload is already available through `Loader.route()` when leave starts. `blocker.location` remains available for the requested URL; the project-facing documentation describes this timing change.
