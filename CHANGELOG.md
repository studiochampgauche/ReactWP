# Changelog

This file tracks notable project-level changes for `reactwp`.

## 2026-07-17

### Added

- Added an entrypoint manifest so WordPress automatically loads the JavaScript files emitted by the current build mode in dependency order.
- Added an automatic production bundle report with raw, gzip, and Brotli sizes plus configurable bundle budgets.
- Added a nonce-protected **ReactWP > Cache** admin action and `rwp::bust_client_cache()` API for invalidating public visitor caches on demand.

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
