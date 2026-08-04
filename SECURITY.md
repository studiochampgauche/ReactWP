# Security Policy

## Supported Versions

Security fixes are maintained on the latest ReactWP release and the repository's default branch. Projects should keep WordPress, ACF PRO, PHP, Node.js build tooling, and Composer dependencies updated.

## Report a Vulnerability

Send reports privately to [security@reactwp.com](mailto:security@reactwp.com). Do not open a public issue for an unpatched vulnerability.

Include the affected version or commit, the impacted endpoint or file, reproduction steps, expected impact, and any proof of concept that can be shared safely. Do not access data that is not yours, disrupt a production site, or publish the issue before a fix is available.

We will acknowledge a complete report, validate its scope, coordinate a fix, and credit the reporter when requested and appropriate.

## Secure Defaults

### REST And Headless Authentication

- REST access is denied to guests and non-administrators unless the exact normalized route is allowlisted. Text in unrelated query parameters never affects the route decision.
- Cookie-authenticated administrators retain normal same-origin REST access. An authenticated cross-origin frontend receives only exact headless routes or routes explicitly added through `rwp_authenticated_cross_origin_rest_routes`.
- Public ReactWP payloads exclude draft, private, scheduled, trashed, password-protected, and otherwise non-public content. The same policy applies recursively to post, author, term, and attachment references in ACF data.
- Public bootstrap responses never include the current user, email address, capabilities, or REST nonce. Identity is available only from the no-store `/auth/me` response.
- Preview tokens can be issued only by a user who can edit the requested post. Tokens are signed, post-bound, header-based by default, limited to one hour, and rejected when malformed, oversized, expired, or issued too far in the future.
- Headless authentication requires JSON, HTTPS outside explicit loopback development, an exact credentialed CORS origin, generic login failures, per-address and per-address-plus-username limits, and a valid REST nonce for authenticated logout.
- ReactWP trusts `REMOTE_ADDR` by default. A reverse proxy address must be returned through `rwp_headless_client_ip` only after the project validates its trusted proxy chain.

### Rendering And Caches

- `client` remains the fallback whenever static or server rendering is unavailable or invalid.
- Every SSR endpoint requires a secret of at least 32 characters by default. Remote endpoints additionally require HTTPS and explicit approval through `rwp_ssr_allow_remote_endpoint`.
- The Node renderer limits headers, body size, response size, render time, keep-alive time, and concurrency. It rejects unauthenticated, malformed, non-JSON, query-bearing, and oversized render requests.
- PHP uses safe remote requests, disables redirects, bounds responses, validates JSON, and opens a short circuit breaker after renderer failures. Remote response details are not exposed to visitors.
- Static generation accepts only same-origin JSON API responses, follows at most three same-origin redirects, bounds route and HTML counts/sizes, validates decoded route paths, and confines output to the project unless an external path is explicitly approved.
- Static manifests and fragments have path, depth, entry-count, and byte limits. Runtime fragments are written atomically under a protected uploads directory.
- Public SSR cache entries are shared only when the route is explicitly configured as public. Private entries require a non-empty identity and are isolated by user by default. Logged-in responses receive no-cache headers.
- Persistent browser payload and media caches default to disabled for private routes and refuse responses marked `private` or `no-store`.
- Cache invalidation is serialized. Lock contention establishes a fail-safe future watermark so an older static or SSR fragment cannot become fresh again.

### Browser Output And Uploads

- The provided rich-text renderer uses an element and attribute allowlist, URL protocol checks, safe `srcset` parsing, bounded input, and automatic `noopener noreferrer` protection.
- Route-managed `<head>` markup accepts only bounded `title`, safe `meta`, and selected HTTP(S) `link` elements. Scripts, styles, base elements, refresh directives, CSP injection, and arbitrary nodes are discarded.
- Shared components strip `dangerouslySetInnerHTML`, `srcDoc`, form-action overrides, invalid event properties, unsafe destinations, oversized attributes, and unsafe style values.
- Loader media URLs are restricted to HTTP(S) and generated blob URLs. Media properties, datasets, styles, selectors, and replacement counts are bounded, and structural document nodes cannot be replaced.
- SVG upload permission defaults to `manage_options`. Uploads and sideloads are byte-limited, parsed as XML with external entities disabled, required to have an SVG root, sanitized by `enshrined/svg-sanitize`, stripped of remote references, and written atomically.
- Custom project templates are trusted application code. A project that introduces its own raw HTML renderer, unsafe URL handling, upload endpoint, or relaxed CSP must secure and test that extension itself.

### Project And Supply Chain

- `wp-config.php`, `.env*`, `auth.json`, private keys, and certificate keys are ignored. Production startup rejects placeholder, short, or duplicate WordPress salts.
- XML-RPC, the WordPress file editor, production debug display, directory listing, executable uploads, and common browser capabilities are disabled or restricted by default.
- `npm run get:core` downloads over HTTPS, restricts hosts and redirects, bounds archives, rejects ZIP traversal, duplicate entries, symlinks, encryption, unsupported compression, ZIP64, local/central metadata mismatches, and oversized expansion.
- WordPress archives are verified against official checksums before and after a clean core replacement. `wp-content` and the real `wp-config.php` are preserved.
- ReactWP does not publish, embed, or persist ACF PRO credentials. Interactive license input is masked, and `get:core` supplies the license key and licensed site URL to the official Composer repository only through the child process environment. ACF Free comes from the WordPress.org plugin API. A private archive override still requires an explicit version, SHA-256 digest, and approved host.

## Deployment Checklist

1. Set `WP_ENVIRONMENT_TYPE=production`, use unique WordPress salts, keep `wp-config.php` out of version control, and serve the site over HTTPS.
2. Keep the document root on `dist/`. Never deploy `src/`, `configs/`, development source maps, environment files, private archives, or build credentials.
3. Restrict ownership and permissions. PHP must not be able to modify core, themes, or plugins during normal requests; only uploads and intentional runtime render storage need web-server write access. Follow the [official WordPress hardening guidance](https://developer.wordpress.org/advanced-administration/security/hardening/).
4. For Nginx, add equivalent protection to ReactWP's generated Apache/IIS rules:

```nginx
location ~* ^/wp-content/themes/[^/]+/assets/render(?:/|$) { return 404; }
location ~* ^/wp-content/themes/[^/]+/datas/acf(?:/|$) { return 404; }
location ~* ^/wp-content/uploads/reactwp/render(?:/|$) { return 404; }
location ~* ^/wp-content/uploads/.*\.(?:php[0-9]?|phtml|phar)(?:/|$) { return 404; }
location ~* /(?:composer\.(?:json|lock)|package(?:-lock)?\.json)$ { return 404; }
location ~* /(?:\.env(?:\..*)?|auth\.json|.*\.(?:bak|dist|log|old|orig|sql|swp))$ { return 404; }
```

5. Keep the SSR service on loopback or a private network, set the same strong `RWP_SSR_SECRET` in Node and WordPress, run it under a dedicated unprivileged account, and supervise it with the operating system. Never expose its port directly.
6. Configure only trusted headless origins. Do not use wildcards with credentialed requests. Review cookies, SameSite policy, proxy TLS termination, and forwarded headers on the real production topology.
7. Keep WordPress, ACF, ReactWP, Node tooling, and Composer packages updated. Remove unused plugins and themes rather than merely deactivating them.
8. Review `rwp_content_security_policy` and `rwp_permissions_policy` when adding scripts, frames, payment providers, cameras, geolocation, or other capabilities. Prefer nonces or hashes when a project adopts a stricter script policy.
9. Back up the database and uploads, test restoration, centralize PHP/web/SSR logs outside the public document root, and monitor authentication failures and unexpected executable files.
10. Run `npm audit`, `composer audit`, `npm run test:security`, `npm run test:render`, and `npm run prod` before release.

Security defaults can be adjusted through narrowly scoped filters including `rwp_options_page_capability`, `rwp_svg_upload_capability`, `rwp_svg_max_bytes`, `rwp_headless_public_rate_limit`, `rwp_headless_public_rate_window`, `rwp_headless_client_ip`, `rwp_headless_require_json_auth`, `rwp_authenticated_cross_origin_rest_routes`, `rwp_preview_token_max_ttl`, `rwp_preview_token_authorized`, `rwp_allow_xmlrpc`, `rwp_content_security_policy`, `rwp_permissions_policy`, `rwp_hsts_header`, `rwp_ssr_allow_insecure_loopback`, and `rwp_ssr_allow_remote_endpoint`. Treat every relaxation as a deployment decision and keep it in project-owned PHP.

## Integrity of Core Downloads

`npm run get:core` verifies every WordPress file against official WordPress checksums and rejects unexpected archive content. A custom WordPress URL requires `REACTWP_WORDPRESS_SHA256`. ACF Free metadata and downloads are restricted to official WordPress.org hosts. ACF PRO is resolved from `connect.advancedcustomfields.com` using `REACTWP_ACF_LICENSE_KEY` and `REACTWP_ACF_SITE_URL`; the temporary Composer project and credentials are removed at the end of the command. A licensed private archive override requires `REACTWP_ACF_URL`, `REACTWP_ACF_VERSION`, `REACTWP_ACF_SHA256`, and an explicit host in `REACTWP_DOWNLOAD_HOSTS`.

Use `REACTWP_DOWNLOAD_HOSTS` only for download hosts you control and review. ReactWP refuses HTTP downloads, embedded URL credentials, excessive redirects, oversized or structurally unsafe archives, checksum mismatches, and unexpected ACF versions. ACF also documents an authenticated [official Composer installation](https://www.advancedcustomfields.com/resources/installing-acf-pro-with-composer/); keep `auth.json` or `COMPOSER_AUTH` outside version control.

## Important Environment Limits

| Setting | Purpose |
| --- | --- |
| `REACTWP_ACF_EDITION` | Optional non-interactive choice: `free`, `pro`, or `none` |
| `REACTWP_ACF_LICENSE_KEY` | ACF PRO license used only for the official Composer download |
| `REACTWP_ACF_SITE_URL` | Complete site URL associated with the ACF PRO license |
| `REACTWP_ACF_VERSION` | Optional exact ACF PRO version; latest is the default |
| `RWP_SSR_SECRET` | Shared renderer secret; minimum 32 characters |
| `RWP_SSR_BODY_LIMIT` | Maximum renderer request body |
| `RWP_SSR_RESPONSE_LIMIT` | Maximum renderer HTML response |
| `RWP_SSR_TIMEOUT` | Node render timeout |
| `RWP_SSR_CONCURRENCY` | Maximum simultaneous renders |
| `RWP_SSG_MAX_ROUTES` | Maximum routes generated in one run |
| `RWP_SSG_MAX_JSON_BYTES` | Maximum WordPress API response |
| `RWP_SSG_MAX_HTML_BYTES` | Maximum HTML fragment |
| `RWP_SSG_TIMEOUT` | Static-generation API timeout |
| `RWP_SSG_ALLOW_EXTERNAL_OUTPUT` | Explicit opt-in for an output path outside the project |

All numeric values are clamped to defensive minimums and maximums in code.
