# Changelog

This file tracks notable project-level changes for `reactwp`.

## 2026-04-29

### Added

- Added a secure headless API surface with public bootstrap, route, navigation, settings, sitemap, preview, and auth endpoints.
- Added signed, expiring preview tokens through `ReactWP\Runtime\PreviewToken::create()` and `rwp::preview_token()`.
- Added headless login/logout/current-user endpoints so authenticated REST requests can resolve `wp_get_current_user()`.
- Added a headless CORS allowlist backed by the new Site settings > Headless API origin repeater and the `rwp_headless_allowed_origins` filter.
- Added a JSON Schema in `contracts/` for external frontend integrations.

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
