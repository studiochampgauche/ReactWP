# Changelog

This file tracks notable project-level changes for `reactwp`.

## 2026-04-27

### Changed

- Non-critical media groups now render progressively as soon as each individual group is ready, instead of waiting for the full deferred batch to finish.
- The loader now exposes group-level deferred promises through `window.loader.noCriticalDownloadGroups` and `window.loader.noCriticalDisplayGroups`.
- The README documentation links now point to the public `https://reactwp.com/docs/...` site with the new clean docs URLs instead of the GitHub wiki.
