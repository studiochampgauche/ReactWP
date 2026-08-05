![ReactWP](https://reactwp.com/github-image/banner-black.jpg)

# ReactWP

[![CI](https://github.com/studiochampgauche/ReactWP/actions/workflows/ci.yml/badge.svg)](https://github.com/studiochampgauche/ReactWP/actions/workflows/ci.yml)
[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL_v2_or_later-blue.svg)](LICENSE)
[![Latest tag](https://img.shields.io/github/v/tag/studiochampgauche/ReactWP?label=version)](https://github.com/studiochampgauche/ReactWP/tags)

ReactWP is a WordPress application framework for integrated React themes and headless frontends.

Keep the WordPress admin, content model, plugins, users, previews, and permalinks. Build the frontend with React inside WordPress, consume the public API from another framework, or use both from the same backend.

[Documentation](https://reactwp.com/docs/intro/) | [Installation](https://reactwp.com/docs/getting-started/) | [Rendering](https://reactwp.com/docs/hybrid-rendering/) | [Headless API](https://reactwp.com/docs/headless-api/) | [GitHub](https://github.com/studiochampgauche/ReactWP)

## Why ReactWP

- **WordPress stays WordPress.** Editors keep familiar pages, posts, menus, ACF fields, users, plugins, and project settings.
- **Choose the frontend architecture.** Run an integrated React theme or connect an external React, Vue, Svelte, Astro, or other frontend.
- **Choose rendering per template.** Keep client rendering, generate static HTML, or use an optional Node server for request-time rendering.
- **Adopt rendering progressively.** Existing client-rendered templates remain valid while individual routes move to static or server rendering.
- **Build the whole WordPress project together.** Themes, plugins, mu-plugins, PHP, React, SCSS, and media share one source and production pipeline.
- **Ship production assets by default.** ReactWP provides code splitting, extracted route CSS, image optimization, Brotli and gzip files, entrypoint manifests, bundle reports, and cache invalidation.

## Two Ways to Build

### Integrated React Theme

WordPress resolves every request and provides a normalized route payload. ReactWP renders the selected React template, manages internal navigation, prepares media, updates the document head, and coordinates the loader, scroll, and page transitions.

Each template can choose its initial render mode:

| Mode | Initial response | Node.js in production | Typical use |
| --- | --- | --- | --- |
| `client` | Browser-rendered React application | No | Existing projects and highly interactive screens |
| `static` | Build-time HTML followed by hydration | No | Marketing pages, services, articles, and public content |
| `server` | Request-time HTML followed by hydration | Yes | Accounts, carts, personalized routes, and live data |

`client` is the default, so projects can run without a Node.js production service. Static and server templates use the same React components as client-rendered templates.

### Headless WordPress Backend

Use ReactWP as a public content and administration API while another application owns rendering and navigation. The headless contract exposes normalized routes, navigation, public settings, SEO, previews, authentication, and sitemap data.

```js
const response = await fetch(
  'https://cms.example.com/wp-json/reactwp/v1/route?view=/about/'
);

const { route } = await response.json();
```

## Quick Start

### Requirements

- PHP `8.1+`
- Node.js `22.22.0+`
- npm
- Composer when installing ACF PRO through `get:core`
- MySQL or MariaDB
- a local web server with its document root set to `dist/`
- an ACF PRO license for ReactWP settings pages, repeaters, and other paid fields

Node.js is required to build ReactWP locally. It is only required in production when request-time SSR or runtime static regeneration is enabled.

### Create a Project

```bash
git clone https://github.com/studiochampgauche/ReactWP.git my-project
cd my-project/configs
npm install
npm run get:core
npm run build
```

`get:core` asks whether to install ACF Free, ACF PRO, or neither. ACF Free is the default and comes from WordPress.org. When PRO is selected, ReactWP asks for the license key with masked input and then the complete licensed site URL. ACF PRO is required for the built-in Site settings and Theme settings pages, repeaters, and other PRO field types.

For CI or another non-interactive build, select the edition explicitly. A PRO build uses:

```powershell
$env:REACTWP_ACF_EDITION = 'pro'
$env:REACTWP_ACF_LICENSE_KEY = 'your-acf-pro-license-key'
$env:REACTWP_ACF_SITE_URL = 'https://example.com'

npm run get:core
```

A free build uses:

```powershell
$env:REACTWP_ACF_EDITION = 'free'
npm run get:core
```

On macOS or Linux, use the equivalent exported variables:

```bash
export REACTWP_ACF_EDITION='pro'
export REACTWP_ACF_LICENSE_KEY='your-acf-pro-license-key'
export REACTWP_ACF_SITE_URL='https://example.com'

npm run get:core
```

`get:core` downloads WordPress from its official HTTPS source, inspects the ZIP before extraction, verifies every core file against the WordPress checksum API, and cleanly replaces the generated core while preserving `wp-config.php` and `wp-content`.

ACF Free is resolved through the official WordPress.org plugin API. ACF PRO is installed from its [official authenticated Composer repository](https://www.advancedcustomfields.com/resources/installing-acf-pro-with-composer/). ReactWP passes the license key as the Composer username and the site URL as its password without writing either value to the project. The latest available release is installed by default in both modes. Set `REACTWP_ACF_VERSION` only when the project needs an exact version. Use `REACTWP_ACF_EDITION=none` or the legacy `REACTWP_SKIP_ACF=1` to leave an existing ACF installation unchanged.

An advanced deployment can override Composer with a licensed private archive:

```powershell
$env:REACTWP_ACF_URL = 'https://private.example/acf-pro.zip?token=...'
$env:REACTWP_ACF_VERSION = '6.x.y'
$env:REACTWP_ACF_SHA256 = '<64-character-sha256>'
$env:REACTWP_DOWNLOAD_HOSTS = 'private.example'
npm run get:core
```

Never commit a private URL, token, ACF license, `auth.json`, `COMPOSER_AUTH`, or archive credentials. In a non-interactive environment without an explicit edition, `get:core` selects ACF Free and never guesses or requests PRO credentials.

Then:

1. Configure the database and environment values in `src/core/wp-config.php`.
2. Point the local domain document root to the generated `dist/` directory.
3. Complete the WordPress installation and activate the ReactWP theme.
4. Start the development watchers from `configs/` with `npm run watch`.

ReactWP keeps authored code in `src/` and generates the runnable WordPress installation in `dist/`.

The optional starter scaffold is disabled by default and can be enabled before or after WordPress installation. To create the initial home page, ACF language and theme-location rows, and primary menu without deleting existing content, temporarily add `define('RWP_FIRSTLOAD', true);` to `wp-config.php`, visit the WordPress admin as an administrator once, confirm the values were created, then remove the constant. Empty repeaters and missing ACF field references from an interrupted or older run are repaired automatically; populated rows are preserved.

## Create a React Template

Create `src/themes/reactwp/js/templates/About.jsx`:

```jsx
const About = ({ route }) => {
  return (
    <section id="about__intro" className="intro">
      <h1>{route.pageName}</h1>
      <div>{route.data.introduction}</div>
    </section>
  );
};

export default About;
```

Register it in `src/themes/reactwp/js/inc/config/configureTemplateRegistry.js`:

```js
import { registerTemplate } from '../TemplateRegistry';

export const configureTemplateRegistry = () => {
  registerTemplate('About', {
    loader: () => import('../../templates/About'),
    render: 'client'
  });
};
```

Enter `About` in the page's **React Template** field in WordPress. The route payload is then passed to the component through `route`, alongside the shared `site`, `theme`, `system`, and `navigation` props.

Change `render` to `static` or `server` when that route needs a different initial rendering strategy. See [Rendering](https://reactwp.com/docs/hybrid-rendering/) for generation, hydration, caching, revalidation, and SSR deployment.

The global **ReactWP > Cache** action rotates the browser cache generation and invalidates static and cached SSR HTML, including public shared entries and per-user private entries. In ReactWP, `public` and `private` describe the SSR cache scope; they do not describe two separate global invalidation actions.

## Project Structure

```text
configs/   Node dependencies, Webpack configuration, and project commands
src/       WordPress, PHP, React, SCSS, and media source files
dist/      generated WordPress installation and production assets
```

Edit `src/`. Build to `dist/`. Serve `dist/`.

## Commands

Run project commands from `configs/`.

| Command | Description |
| --- | --- |
| `npm run get:core` | Download and verify WordPress, then choose ACF Free, ACF PRO, or no ACF change |
| `npm run build` | Create a readable development build |
| `npm run watch` | Watch themes, plugins, mu-plugins, styles, and render assets |
| `npm run prod` | Create optimized production output and bundle reports |
| `npm run build:render` | Build the universal renderer in development mode |
| `npm run generate` | Generate static route fragments from WordPress |
| `npm run serve:ssr` | Start the optional production SSR service |
| `npm run test:render` | Test the renderer, static generation, and SSR service |
| `npm run test:security` | Run the PHP security regression suite |

The generated entrypoint manifest tells WordPress whether to load development or production assets. Enqueued filenames do not need to be changed by hand.

## Production

Run:

```bash
npm run prod
```

The production pipeline builds WordPress targets, minifies and splits theme assets, removes stale chunks, creates compressed siblings, writes manifests, validates bundle sizes, and builds the universal renderer. When `RWP_SITE_URL` is configured, it can also generate static routes.

Deploy `dist/` as the WordPress document root. A normal PHP WordPress server is enough for `client` and `static` templates. Start the optional render service only for templates configured with `render: 'server'` or runtime static regeneration.

The SSR service and WordPress must share an `RWP_SSR_SECRET` of at least 32 characters, including on loopback. Secretless loopback is available only in local/development environments when both `RWP_SSR_ALLOW_INSECURE_LOOPBACK=1` and `rwp_ssr_allow_insecure_loopback` are explicitly enabled. Remote SSR additionally requires HTTPS and `rwp_ssr_allow_remote_endpoint`. SSR responses with query parameters are cached only when every key is explicitly listed through `rwp_ssr_cache_query_keys`; the default list is empty.

Static generation keeps its output inside the ReactWP project by default. `RWP_SSG_ALLOW_EXTERNAL_OUTPUT=1` is an explicit escape hatch for a reviewed deployment path, not a normal requirement.

Apache and IIS protection files are generated around render artifacts. Nginx deployments must deny `/wp-content/themes/<theme>/assets/render/` and `/wp-content/uploads/reactwp/render/` as shown in [SECURITY.md](SECURITY.md). Preview tokens belong in `X-ReactWP-Preview-Token` or a Bearer authorization header, not in URLs.

Read [Deployment and Performance](https://reactwp.com/docs/deployment-and-performance/) before publishing a project.

## Documentation

- [Introduction](https://reactwp.com/docs/intro/)
- [Getting Started](https://reactwp.com/docs/getting-started/)
- [Build Your First Template](https://reactwp.com/docs/templates-and-pages/)
- [Client, Static, and Server Rendering](https://reactwp.com/docs/hybrid-rendering/)
- [Cache Tags](https://reactwp.com/docs/cache-tags/)
- [Configuration Reference](https://reactwp.com/docs/configuration-reference/)
- [Use ReactWP Headless](https://reactwp.com/docs/headless-quick-start/)
- [Headless API Reference](https://reactwp.com/docs/headless-api/)
- [Architecture](https://reactwp.com/docs/architecture/)
- [Troubleshooting](https://reactwp.com/docs/troubleshooting/)

## Security

Review the secure defaults and deployment checklist in [SECURITY.md](SECURITY.md). Report security issues privately to [security@reactwp.com](mailto:security@reactwp.com).

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request, and follow the project [Code of Conduct](CODE_OF_CONDUCT.md). Use GitHub issues for reproducible bugs and focused feature proposals; report vulnerabilities privately as described above.

## License

ReactWP is licensed under the [GNU General Public License v2.0 or later](LICENSE). Bundled third-party packages remain available under their respective licenses and copyright notices.
