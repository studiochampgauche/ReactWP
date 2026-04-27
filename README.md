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

1. [Getting Started](https://github.com/studiochampgauche/ReactWP/wiki/Getting-Started)
2. [Project Structure](https://github.com/studiochampgauche/ReactWP/wiki/Project-Structure)
3. [Build Tooling](https://github.com/studiochampgauche/ReactWP/wiki/Build-Tooling)
4. [Architecture](https://github.com/studiochampgauche/ReactWP/wiki/Architecture)
5. [PHP Runtime](https://github.com/studiochampgauche/ReactWP/wiki/PHP-Runtime)
6. [Bootstrap and Route Payloads](https://github.com/studiochampgauche/ReactWP/wiki/Bootstrap-and-Route-Payloads)
7. [Hooks and Filters](https://github.com/studiochampgauche/ReactWP/wiki/Hooks-and-Filters)
8. [Head and SEO](https://github.com/studiochampgauche/ReactWP/wiki/Head-and-SEO)
9. [Admin and Settings](https://github.com/studiochampgauche/ReactWP/wiki/Admin-and-Settings)
10. [Frontend Runtime](https://github.com/studiochampgauche/ReactWP/wiki/Frontend-Runtime)
11. [Components](https://github.com/studiochampgauche/ReactWP/wiki/Components)
12. [Routing and Navigation](https://github.com/studiochampgauche/ReactWP/wiki/Routing-and-Navigation)
13. [Theme Shell and Scroll](https://github.com/studiochampgauche/ReactWP/wiki/Theme-Shell-and-Scroll)
14. [Loader](https://github.com/studiochampgauche/ReactWP/wiki/Loader)
15. [Template Registry](https://github.com/studiochampgauche/ReactWP/wiki/Template-Registry)
16. [Page Transitions](https://github.com/studiochampgauche/ReactWP/wiki/Page-Transitions)
17. [Styling](https://github.com/studiochampgauche/ReactWP/wiki/Styling)
18. [Content and Menus](https://github.com/studiochampgauche/ReactWP/wiki/Content-and-Menus)

## Principles

- Keep WordPress reliable and content-focused
- Keep React expressive but understandable
- Prefer architectural clarity over tool churn
- Minimize dependencies unless they clearly buy back time or capability
