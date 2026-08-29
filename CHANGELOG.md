# Changelog

This file tracks notable project-level changes for `reactwp`.

## 2026-08-29

### Added

- `AppLink` and link-rendering `Button` instances can now set `updateHash={false}` to scroll to a same-page anchor without adding its hash to the URL.

### Changed

- Redesigned the shipped `Default` and `NotFound` screens as a compact black-and-white editorial experience with lighter typography, direct setup guidance, responsive accessible styling, and the supplied official ReactWP mark and wordmark instead of reconstructed brand graphics.
- Added repository-local expert workflows and tightened the frontend guidance so display titles stay proportionate to the available viewport instead of defaulting to extra-bold typography.
- Refreshed the pinned frontend and build toolchain after a full `npm-check-updates` review: CSS Loader 7.1.5, html-react-parser 6.1.7, React Router 8.3.1, Sass 1.103.1, Sharp 0.35.4, SVGO 4.1.0, Webpack 5.110.1, and Webpack CLI 7.2.3.

### Fixed

- Removed obsolete SVGO `removeViewBox` preset overrides after the SVGO 4 update; the default preset now preserves responsive SVG view boxes without warnings.
- Template asset discovery now traverses concatenated Webpack modules so registry aliases keep the correct scripts and styles.

## 2026-08-19

### Added

- Added exact documentation for all six bundled WordPress plugins, the callable PHP runtime surface, headless endpoint inputs/statuses/limits, current ACF settings fields, response headers, generated core hardening, and raw-HTML rendering boundaries.
- Added a focused regression proving that the SEO plugin resolves the canonical `route.lang` value while retaining its legacy `route.language` fallback.

### Changed

- Re-audited the authored PHP, React, SCSS, build scripts, configuration, tests, package manifests, documentation, and repository skills against the current source contracts.
- Clarified that `html-react-parser` is reserved for node transformation, while unchanged trusted and backend-sanitized HTML may use an explicit `dangerouslySetInnerHTML` boundary; neither rendering path sanitizes input.
- Corrected SSR development-exception wording: the explicit dual opt-in permits a missing or shorter loopback secret, not only an empty secret.

### Fixed

- The bundled SEO plugin now reads `route.lang`, the canonical normalized route language field, before the older `route.language` compatibility field.

## 2026-08-13

### Changed

- `AppLink` now forwards refs to its rendered anchor for internal routes, local anchors, and external links.

## 2026-08-12

### Changed

- `window.loader.init` now keeps one stable Promise for the complete initial loader lifecycle, and `Loader.whenInitialLoadDone()` exposes the same lifecycle without starting or replaying an animation.
- Loader animations that explicitly access `done` now control their own completion; returned GSAP timelines and Promises retain automatic completion only when `done` is not requested.

### Fixed

- Prevented template effects from observing the loader state's initial already-resolved placeholder before the real first-load Promise was assigned.

## 2026-08-04

### Added

- Added an explicit GPL-2.0-or-later project license, contribution guide, code of conduct, GitHub issue forms, pull request guidance, Dependabot configuration, and a cross-platform CI matrix.
- Added automated production builds, rendering tests, PHP security regressions, npm auditing, Composer validation, and Composer security auditing to pull requests and the default branch.

### Changed

- Added public package metadata and documented the supported Node.js and npm versions directly in `configs/package.json`.
- Updated React, React Router, Sass, Webpack, and vulnerable transitive packages; React Router 8 now provides the browser APIs directly without `react-router-dom`, and the minimum supported Node.js version is `22.22.0`.
- Aligned project licensing metadata with WordPress and the bundled GPL SVG sanitizer.
- Prepared the v3 release history for removal of legacy ACF PRO files and historical runtime configuration without changing the existing GitHub repository.

### Fixed

- Fixed the first rendering build on a clean checkout by materializing the emitted server bundle before generating its template manifest.
- Fixed static generation under Windows path aliases by deriving fragment paths from the canonical output directory.
- Updated the CI workflow to the current major releases of the official checkout and Node.js setup actions.

## 2026-07-20

### Changed

- Expanded the documentation for current-route language access, template props, focused build and test commands, SSG/SSR limits, security controls, and every public ReactWP filter currently exposed by the source.
- `npm run get:core` now asks whether to install ACF Free, ACF PRO, or preserve the existing installation. PRO credentials are requested only after PRO is selected, and license input is masked.
- Non-interactive builds can select `REACTWP_ACF_EDITION=free|pro|none`; without an explicit selection or PRO credentials, ACF Free is installed from WordPress.org.
- ACF PRO is resolved through its official authenticated Composer repository with `REACTWP_ACF_LICENSE_KEY` and `REACTWP_ACF_SITE_URL`. `REACTWP_ACF_VERSION` can optionally pin either edition, while URL, SHA-256, and host settings remain available for private PRO archive overrides.

### Fixed

- ACF Free now loads correctly as a must-use plugin. ReactWP hides unavailable Site settings and Theme settings links and displays a clear notice that those pages, repeaters, and other PRO fields require ACF PRO.
- The optional first-load scaffold now recognizes empty ACF repeater options, writes the required ACF field references, repairs previously seeded rows without replacing their content, and records completion only after the home page, languages, and theme locations are ready.

## 2026-07-19

### Added

- Added dedicated security regressions for exact headless origins, cross-site origin omission, preview-token authorization/signatures/expiry, encoded route bypasses, public route normalization, and cache-lock contention.
- Added a complete deployment security reference covering REST boundaries, browser rendering, SSR/SSG limits, private and public caches, ACF licensing, archive integrity, Nginx rules, and production operations.

### Changed

- Public contract version `1.4` now reserves response metadata, bounds nested values and navigation, validates template names, preserves typed user/term identifiers, and excludes private nested ACF relations from guest integrated bootstraps as well as headless payloads.
- Headless authentication now requires secure JSON requests by default, applies separate address and username limits, rejects credentialed or ambiguous origins, and restricts authenticated cross-origin administrators to explicitly approved REST routes.
- Preview tokens now require `edit_post`, include version and issue time, enforce bounded lifetimes and sizes, and reject malformed base64, future, mismatched, expired, or oversized payloads.
- SSR now requires a 32-character secret on loopback by default, while the Node service bounds body, response, headers, timeout, concurrency, and error disclosure. Remote PHP requests reject redirects, unsafe URLs, non-JSON responses, and oversized bodies.
- Static generation now confines output to the project by default, validates decoded paths and searches, restricts API redirects to the WordPress origin, and bounds routes, manifests, API bodies, and HTML fragments.
- `npm run get:core` now performs a clean verified WordPress core replacement without touching `wp-content` or `wp-config.php`, validates local and central ZIP metadata, and no longer embeds or advertises a public ACF PRO archive.
- Licensed private ACF archives now require an explicit URL, version, SHA-256 digest, and approved host; official authenticated Composer installation remains supported.
- SVG uploads now default to `manage_options` and require a valid XML SVG root before and after parser-based sanitation.
- Production configuration now rejects short or duplicate WordPress salts, logged-in frontend responses are explicitly non-cacheable, and public guest bootstraps no longer emit a REST nonce.

### Fixed

- Prevented encoded protocol-relative route values, lookalike REST namespaces, unrelated query parameters, and arbitrary cross-origin admin REST requests from bypassing route controls.
- Prevented private posts, authors, terms, attachments, and attachment parents from leaking through nested public or guest ACF values.
- Prevented stale HTML resurrection during concurrent invalidation by serializing writes and recording a future fail-safe watermark when the lock cannot be acquired.
- Prevented unsafe template registry property names, raw DOM property overrides, unsafe form actions and destinations, arbitrary route head nodes, oversized rich text, unsafe media attributes/styles, and replacement of structural document nodes.
- Prevented stale WordPress core files, ZIP path traversal, archive symlinks, duplicate paths, encrypted entries, metadata mismatches, and oversized extraction from surviving the core setup pipeline.

## 2026-07-18

### Added

- Added hybrid initial rendering with backward-compatible `client`, build-time `static` (SSG), and optional runtime `server` (SSR) modes.
- Added automatic React hydration for valid static/server HTML, with a clean client render fallback when no fragment or render service is available.
- Added a Node render bundle, protected SSR service, static route generator, template render manifest, and per-template CSS asset manifest.
- Added `npm run build:render`, `watch:render`, `prod:render`, `generate`, `serve:ssr`, and `test:render` commands.
- Added targeted static-fragment invalidation and optional WP-Cron regeneration through the SSR service, with runtime fragments stored under protected WordPress uploads.
- Added separate HTML, payload, and media cache controls, dependency tags, public/private SSR cache scopes, and per-user private cache keys.
- Added the ACF **React Rendering** controls for route-level mode and SSR cache overrides.
- Added `currentUser` to the integrated template props and `rwp::invalidate_render_cache()` for project-level invalidation.
- Added regression coverage for REST allowlisting, public route visibility, nested payload relations, SVG sanitation, SSR endpoint policy, bounded invalidation state, and static-fragment removal.

### Changed

- Public contract version `1.2` no longer includes authenticated user data in bootstrap payloads; headless frontends now resolve identity exclusively through the no-store `/auth/me` endpoint.
- Replaced the legacy Imagemin binary chain with Sharp and SVGO, started tracking the tooling lockfile, and pinned the reviewed Sass watcher install script for reproducible dependency resolution.
- SVG uploads now use the maintained `enshrined/svg-sanitize` parser, remove remote references, and save only the sanitized document.
- Reduced the default PHP theme shell to its functional minimum by removing the redundant `404.php`, the empty WordPress loop, optional utility defaults, passive filters, and commented starter examples.
- The template registry now accepts loader configuration objects with `render`, `cache`, and `assetKey` metadata while preserving the existing loader-function signature.
- WordPress remains the front controller for every mode and now injects pre-rendered route fragments into `#app` before React hydrates them.
- Public payload API version `1.1` now exposes theme metadata, render strategy, cache settings, and the public runtime values needed for deterministic static rendering.
- Production builds generate static routes automatically when `RWP_SITE_URL` is set; projects can still run generation independently without rebuilding JavaScript.
- The global ReactWP cache action now invalidates all pre-rendered HTML, including public and private SSR entries, in addition to browser JSON, media, JavaScript, and CSS generations.
- Clarified the cache administration and documentation terminology so the global action is no longer mislabeled as public-only.
- Expanded the documentation with complete cache-tag and configuration references, including automatic invalidation events, custom dependencies, environment variables, defaults, limits, and precedence rules.
- Updated the bundled ACF PRO copy to `6.8.6` and pinned its archive to a reviewed SHA-256 digest.
- `npm run get:core` now requires HTTPS, limits redirects and archive sizes, verifies WordPress against official checksums, and rejects unverified or unexpected ACF archives.
- ACF options pages now require `manage_options` by default; the capability remains configurable through `rwp_options_page_capability`.
- The optional first-run scaffold is now explicit, administrator-only, idempotent, locked against concurrent runs, and never deletes existing posts or pages.
- Remote SSR now requires HTTPS, explicit endpoint approval, and a secret of at least 32 characters. Query-bearing SSR responses are uncached unless every query key is explicitly allowed.
- Private routes now disable persistent browser payload and media caches by default, and Cache Storage honors `private` and `no-store` response directives.
- SVG sanitation now covers normal uploads, sideloads, and direct file validation with a configurable size limit.

### Fixed

- Public route resolution now rejects draft, private, trashed, scheduled, password-protected, and otherwise non-public posts; signed previews remain explicitly authorized.
- REST allowlist checks now resolve and compare the actual REST route exactly, preventing unrelated query parameters from bypassing the global REST gate.
- The public bootstrap endpoint now resolves the site front page by default instead of treating the REST endpoint URL as the active route.
- Pre-rendered routes now enqueue their extracted template CSS before first paint and can skip the blocking initial loader without changing client-only routes.
- Static generation now adds trusted operating-system CA certificates when supported by Node, allowing local HTTPS sites with an installed Laragon or development root certificate without disabling TLS verification.
- Bounded render invalidation history now keeps a stale-before watermark so pruning old tags cannot resurrect obsolete static or SSR HTML.
- Routes that become private, unavailable, or non-static now have their generated fragments removed from runtime and build manifests.
- Public nested ACF values no longer expose private posts, non-public authors, or attachments owned by non-public content.
- Render bundles, static fragments, ACF JSON definitions, Composer manifests, and package manifests are denied direct HTTP access where the server supports generated access files.
- Public previews now use header-based tokens by default, public CORS responses reflect only exact allowed origins, and production responses receive baseline security headers.
- Public headless routes now receive a configurable per-address rate limit without trusting proxy forwarding headers by default.

## 2026-07-17

### Added

- Added an entrypoint manifest so WordPress automatically loads the JavaScript files emitted by the current build mode in dependency order.
- Added an automatic production bundle report with raw, gzip, and Brotli sizes plus configurable bundle budgets.
- Added a nonce-protected **ReactWP > Cache** admin action and `rwp::bust_client_cache()` API for invalidating visitor browser caches on demand.

### Changed

- Development commands now emit `<theme>.js`, while `npm run prod` emits `<theme>.min.js`; the obsolete counterpart is removed automatically after a successful build.
- Split the initial theme bundle into stable framework, router, motion, vendor, and application assets for smaller individual files and better browser caching.
- Extracted template SCSS into standalone CSS chunks instead of injecting it through the JavaScript bundle.
- Production JavaScript and CSS chunks now use content-hashed `.min` filenames, while development chunks use stable readable filenames.
- Successful builds now remove stale JavaScript, source map, license, and extracted CSS chunks for themes, plugins, and mu-plugins.
- Production builds now emit precompressed Brotli and gzip variants of theme JavaScript and CSS; Apache serves the best supported variant even when dynamic compression modules are unavailable and sends long-lived cache headers for versioned assets.
- Browser Cache Storage names and initial asset versions now include the server-controlled cache generation; visitors automatically discard older ReactWP JSON and media cache generations on their next page load.

### Fixed

- Production Webpack mode is now passed explicitly to Babel so production bundles use `jsx-runtime` instead of the incompatible `jsx-dev-runtime`.

## 2026-07-10

### Changed

- Updated the Node tooling packages declared in `configs/package.json`.
- Confirmed the build tooling against Node.js `26.5.0`.
- Removed the obsolete `@babel/preset-env` `bugfixes` option now that Babel 8 always enables bugfix plugins.

## 2026-05-11

### Fixed

- Disabled Webpack filesystem cache for production builds to avoid stale asset metadata conflicts during optimized image emission.

## 2026-04-29

### Added

- Added a secure headless API surface with public bootstrap, route, navigation, settings, sitemap, preview, and auth endpoints.
- Added signed, expiring preview tokens through `ReactWP\Runtime\PreviewToken::create()` and `rwp::preview_token()`.
- Added headless login/logout/current-user endpoints so authenticated REST requests can resolve `wp_get_current_user()`.
- Added a headless CORS allowlist backed by the new Site settings > Headless API origin repeater and the `rwp_headless_allowed_origins` filter.

### Changed

- Standardized the headless route contract on `/wp-json/reactwp/v1/route?view=/example/` and removed duplicate `/headless/bootstrap` and `/headless/route` REST routes.
- The route endpoint now rejects requests without a `view` parameter instead of silently resolving the home page.
- The public settings endpoint now returns an empty settings object by default and only exposes values added through `rwp_headless_public_settings`.
- Reduced admin overhead by caching option-derived admin choices per request and only registering heavier ACF field groups on screens that can use them.
- Reduced post list and dashboard overhead by skipping SEO ACF field group construction on screens that cannot display those fields and cleaning dashboard widgets on the proper dashboard hook.
- Avoided option-derived menu location registration on admin screens that do not use menu locations, including dashboard and post list screens.
- Lazy-load the headless API runtime so normal admin page loads do not parse authentication, payload, and preview classes they do not use.

### Fixed

- Header and footer instances can now remount after route transitions complete so scroll-driven layout animations are recreated after page changes.
- Layout remount keys are now namespaced per component to avoid duplicate React keys when multiple persistent shell components remount on the same route.

## 2026-04-28

### Fixed

- Hash-only anchor links now scroll to their target without triggering route transitions or being reset to the top by `ScrollSmoother`.

## 2026-04-27

### Changed

- Non-critical media groups now render progressively as soon as each individual group is ready, instead of waiting for the full deferred batch to finish.
- The loader now exposes group-level deferred promises through `window.loader.noCriticalDownloadGroups` and `window.loader.noCriticalDisplayGroups`.
- The README documentation links now point to the public `https://reactwp.com/docs/...` site with the new clean docs URLs instead of the GitHub wiki.
