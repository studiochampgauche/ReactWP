![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

ReactWP is a WordPress starter designed for teams who want WordPress to stay in charge of content while React owns the frontend runtime.

Current starter version: `3.0.0`

Changelog:

- [CHANGELOG.md](./CHANGELOG.md)

## Goals

- Keep WordPress reliable, familiar, and editor-friendly
- Use ACF for structured content and site settings
- Treat routing, payloads, and frontend rendering as first-class concerns
- Keep the tooling practical and dependency-light
- Ship one repository that contains source code and generated `dist/`

## Core Ideas

- WordPress is the CMS and editorial back office
- React is the application shell and page rendering layer
- A shared payload shape powers both first render and client-side navigation
- The frontend reads a single bootstrap payload instead of scattered globals
- Theme CSS is compiled as a real stylesheet asset
- Headless consumers can use a stable public API contract without coupling to the PHP theme shell

## Project Structure

```text
reactwp-v3/
|-- configs/              # build tooling and installed node dependencies
|-- dist/                 # generated WordPress output
`-- src/
    |-- core/
    |-- mu-plugins/
    |-- plugins/
    `-- themes/
```

## Theme Structure

The default theme is split across PHP, runtime JavaScript, React templates, and SCSS:

- `src/themes/reactwp/template/` for the PHP shell and theme setup
- `src/themes/reactwp/js/inc/` for runtime code
- `src/themes/reactwp/js/components/` for reusable components
- `src/themes/reactwp/js/templates/` for route-level templates
- `src/themes/reactwp/scss/default.scss` for the global stylesheet entrypoint

ReactWP uses a shared payload between WordPress and React, including `site`, `theme`, `system`, `assets`, `navigation`, `route`, and `seoDefaults`.

SCSS can also be imported directly from JavaScript when a style should stay local to a template or component.


## Frontend Extension Points

The starter keeps a few frontend configuration points intentionally easy to find:

- `src/themes/reactwp/js/inc/config/configureLoader.js` for first-load behavior
- `src/themes/reactwp/js/inc/config/configurePageTransition.js` for route transition animation
- `src/themes/reactwp/js/inc/config/configureTemplateRegistry.js` for registering or overriding React templates

The runtime resets safe defaults internally before those configuration files run, so projects can focus on overrides instead of bootstrapping mechanics.

## Quick Start

1. Open a terminal in `configs/`
2. Install dependencies there if needed
3. Run `npm run get:core`
4. Configure `src/core/wp-config.php`
5. Point your local server to `dist/`
6. Run `npm run build` or `npm run watch`

## Commands

Run commands from `configs/`:

```bash
cd configs
npm run build
npm run build:themes
npm run watch
npm run prod
npm run get:core
```

## Starter Defaults

On first boot, ReactWP prepares a usable starter setup:

- a home page named `ReactWP 3`
- default language rows in site settings
- a starter menu location in the ReactWP settings
- a starter menu assigned to that location
- a static front page setup
- permalink structure configuration

## Documentation

1. [Getting Started](https://reactwp.com/docs/getting-started)
2. [Project Structure](https://reactwp.com/docs/project-structure)
3. [Build Tooling](https://reactwp.com/docs/build-tooling)
4. [Architecture](https://reactwp.com/docs/architecture)
5. [PHP Runtime](https://reactwp.com/docs/php-runtime)
6. [Bootstrap and Route Payloads](https://reactwp.com/docs/bootstrap-and-route-payloads)
7. [Hooks and Filters](https://reactwp.com/docs/hooks-and-filters)
8. [Head and SEO](https://reactwp.com/docs/head-and-seo)
9. [Admin and Settings](https://reactwp.com/docs/admin-and-settings)
10. [Frontend Runtime](https://reactwp.com/docs/frontend-runtime)
11. [Components](https://reactwp.com/docs/components)
12. [Routing and Navigation](https://reactwp.com/docs/routing-and-navigation)
13. [Theme Shell and Scroll](https://reactwp.com/docs/theme-shell-and-scroll)
14. [Loader](https://reactwp.com/docs/loader)
15. [Template Registry](https://reactwp.com/docs/template-registry)
16. [Page Transitions](https://reactwp.com/docs/page-transitions)
17. [Styling](https://reactwp.com/docs/styling)
18. [Content and Menus](https://reactwp.com/docs/content-and-menus)
19. [Headless API](https://reactwp.com/docs/headless-api)

## Principles

- Keep WordPress reliable and content-focused
- Keep React expressive but understandable
- Prefer architectural clarity over tool churn
- Minimize dependencies unless they clearly buy back time or capability
