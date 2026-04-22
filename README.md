![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

![Built](https://img.shields.io/badge/Bundler-Webpack-1C78C0)
![Backend](https://img.shields.io/badge/Backend%20WordPress-PHP%208.1+-4F5B93)
![Frontend](https://img.shields.io/badge/Frontend%20React-19-087ea4)
![Node](https://img.shields.io/badge/Node.js-20.12.2+-417e38)

# Introduction

***Build WordPress like it's 2030.***

SIKE. We're just in 2026.

![GIF](https://reactwp.com/github-image/oh.gif)

## Why ReactWP?

ReactWP is a custom WordPress starter built around one simple idea:

- WordPress manages content and administration
- ACF structures the content
- React renders the frontend
- Webpack publishes everything into a real WordPress install inside `dist/`

It gives you a practical setup for building themes, plugins, and mu-plugins in one repository.

## Project structure

```text
reactwp/
|-- configs/     # build tools and package.json
|-- src/         # source files you actually edit
|   |-- core/
|   |-- mu-plugins/
|   |-- plugins/
|   `-- themes/
|-- dist/        # generated WordPress output
|-- README.md
```

## Main idea

The workflow is straightforward:

1. edit files in `src/`
2. run the build from `configs/`
3. use the generated WordPress site in `dist/`

In other words:

> edit `src/`, not `dist/`

## Stack

- WordPress
- PHP 8.1+
- React 19
- React Router 7
- ACF Pro
- Webpack 5
- Babel
- Sass
- GSAP

## Quick start

Because `package.json` lives inside `configs/`, the cleanest command flow is:

```bash
git clone https://github.com/studiochampgauche/ReactWP.git
cd ReactWP/configs
npm i --legacy-peer-deps
npm run get:core
```

After that:

1. configure your database in `src/core/wp-config.php`
2. point your local server to `dist/`
3. start building with `npm run build`

If `dist/` already exists and you only want to rebuild assets:

```bash
cd configs
npm run build
```

## Shell compatibility

The regular build commands are simple `npm` scripts, but `get:core` is different.

It currently runs a Bash script:

```bash
bash ./scripts/get-core.sh
```

That means `get:core` is expected to work in:

- macOS terminal
- Linux terminal
- Git Bash on Windows
- WSL on Windows

If you are on Windows, do not assume `cmd.exe` or plain PowerShell will behave the same way.
For the safest setup, run `npm run get:core` from Git Bash.

## Build commands

ReactWP gives you three command families:

- `build` for one-shot development builds
- `watch` for development with file watching
- `prod` for production builds

Each family can run on the whole project or on one part only.

### Full project

Use these when you want to process themes, plugins, and mu-plugins together.

```bash
cd configs
npm run build
npm run watch
npm run prod
```

### Themes only

Use these when you are working only on the theme.

```bash
cd configs
npm run build:themes
npm run watch:themes
npm run prod:themes
```

### Plugins only

Use these when you are working only on custom plugins.

```bash
cd configs
npm run build:plugins
npm run watch:plugins
npm run prod:plugins
```

### Mu-plugins only

Use these when you are working only on mu-plugins.

```bash
cd configs
npm run build:mu-plugins
npm run watch:mu-plugins
npm run prod:mu-plugins
```

### Which one should you use?

- use `watch*` during active development
- use `build*` when you want a quick non-watching dev build
- use `prod*` when you want optimized production assets

## What `get:core` does

The `get:core` script prepares the WordPress distribution by:

- downloading WordPress
- creating or refreshing `dist/`
- removing default themes and some default plugins
- downloading ACF Pro
- installing/updating ACF Pro into `wp-content/mu-plugins/`

That makes `dist/` a real WordPress output, not just a frontend build folder.

> [!IMPORTANT]
> ReactWP ships with ACF PRO so the project can be installed and started quickly, but that does not mean the bundled version is always the latest one, even if it may sometimes be up to date.
>
> Keeping ACF PRO updated remains the responsibility of the person installing the project. In practice, each project owner should use their own valid ACF license if they want official updates and long-term version control.

## Theme

The main theme is `reactwp`.

Important entry points:

- `src/themes/reactwp/js/App.jsx`
- `src/themes/reactwp/scss/`
- `src/themes/reactwp/template/`

This theme works like a React SPA layered on top of WordPress.
WordPress injects the current page data into React through a global `CURRENT_ROUTE` object, and the frontend uses that to render the correct template.

## Plugins

ReactWP ships with several custom plugins. Here is the practical role of each one.

### `reactwp-frontend`

Frontend cleanup plugin.

It strips out a large part of WordPress frontend overhead so the theme keeps tighter control over rendering. In particular, it:

- hides the admin bar on the frontend
- disables automatic image `sizes` behavior added by newer WordPress versions
- disables WordPress speculation rules
- removes block library, global styles, and classic theme styles
- removes many default tags and links injected into `<head>` such as REST links, oEmbed discovery, feeds, emojis, and shortlinks

This helps keep the frontend lighter, cleaner, and more predictable for a React-driven theme.

### `reactwp-backend`

Backend cleanup plugin.

It reshapes the WordPress admin into a more controlled CMS-like interface by:

- disabling Gutenberg
- removing editor support from posts and pages
- cleaning the dashboard
- trimming admin menus
- rebuilding part of the admin toolbar with project-focused shortcuts
- simplifying the Customizer
- hiding some theme/settings UI

This makes WordPress feel more like a controlled project CMS than a generic default install.

### `reactwp-acf-local-json`

ACF versioning plugin.

It creates a `datas/acf` folder inside the theme, tells ACF to save JSON there, and loads ACF JSON from that location instead of the default one.

This is especially useful for team workflows and deployment consistency.

### `reactwp-seo`

SEO support plugin.

It handles SEO metadata at the WordPress level. It sets `robots` rules, manages `noindex` behavior, and generates the main `<head>` SEO tags, including title, description, Open Graph fields, and favicon output based on context and ACF fields.

### `reactwp-accept-svg`

SVG support plugin.

It enables SVG uploads, sanitizes SVG file names, validates the uploaded file as real SVG content, and blocks unsafe markup such as scripts, event handlers, external references, iframes, objects, embeds, and similar attack vectors.

### `reactwp-images`

Image utility plugin.

It disables WordPress intermediate image resizing and forces JPEG/editor image quality to `100`, so uploaded images keep their original sizing workflow and maximum quality.

### `reactwp` (mu-plugin)

Core ReactWP bootstrap plugin.

This must-use plugin is the shared core of the stack. It loads ReactWP utilities and admin helpers, enqueues the main frontend script, exposes `CL`, `SYSTEM`, and `ASSETS` data to JavaScript, applies ACF value replacements in PHP and REST output, and restricts the REST API except for explicitly allowed routes.

## ACF workflow

ACF is a central part of this setup.

In practice:

- WordPress stores the content
- ACF defines the content model
- React reads the page data passed from WordPress

This is one of the reasons the project feels structured and scalable despite staying inside WordPress.

## Recommended workflow

### Working on the frontend

- edit `src/themes/reactwp/js/`
- edit `src/themes/reactwp/scss/`
- run `npm run watch:themes`

### Working on the theme PHP files

- edit `src/themes/reactwp/template/`
- rebuild themes so the files are copied to `dist/`

### Working on plugins

- edit `src/plugins/<plugin>/`
- run `npm run watch:plugins`

### Working on mu-plugins

- edit `src/mu-plugins/`
- run `npm run watch:mu-plugins`

## Good to know

- `package.json` is inside `configs/`
- `dist/` may already contain uploads and local working content
- avoid editing generated files directly inside `dist/`
- `src/core/wp-config-sample.php` shows the expected config structure
- the default theme is set to `reactwp`

## In one sentence

ReactWP is a WordPress starter where WordPress stays in charge of content, and React takes care of the frontend.

# Useful links

- [Wiki](https://github.com/studiochampgauche/ReactWP/wiki)
- [Website](https://reactwp.com)

# Want to contribute?

Open issues, submit pull requests, or just fork it and build something wild.
