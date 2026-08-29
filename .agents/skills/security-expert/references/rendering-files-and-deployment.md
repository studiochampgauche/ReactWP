---
name: security-expert-rendering-files-deployment
description: ReactWP security guidance for SSR/static rendering, public/private caches, external requests, files/uploads, SVG, headers, secrets, supply-chain downloads, and production deployment.
---

# Rendering, Files, and Deployment Security

## When to Use This Reference

Apply when changing render mode, renderer payload/service, static generation, cache scope/identity/tags/query keys, remote HTTP, uploads, filesystem work, headers/CSP, environment configuration, dependencies, build/download scripts, or production topology.

## SSR Trust Boundary

ReactWP's PHP `ServerRenderer` and Node render service already enforce a constrained transport:

- HTTP(S) endpoint shape without credentials/query/fragment;
- loopback by default; remote requires HTTPS, strong secret, and explicit filter opt-in;
- at least 32-character shared secret by default, including loopback production;
- safe remote requests for non-loopback, no redirects, timeout/response bounds, JSON content/type checks;
- Node method/content-type/query/header/body/response/time/keep-alive/concurrency limits;
- failure circuit breaker and client-render fallback;
- reduced payload fields and omission of REST nonce/theme directory;
- visitor-safe failure behavior.

These controls authenticate and bound the renderer transport. They do not sanitize unsafe project template code. React server rendering escapes ordinary text, but custom raw HTML, unsafe URLs, remote calls, secrets, or unbounded computation inside templates remain project responsibilities.

Project rules:

- keep the renderer on loopback/private networking and never expose its port directly;
- configure the same strong `RWP_SSR_SECRET` in PHP and Node through protected environment configuration;
- run as a dedicated unprivileged account under supervision;
- never log the render secret or full sensitive payload;
- keep the dual Node/PHP opt-in that permits a missing or shorter loopback secret development-only, and keep remote endpoint opt-in narrowly reviewed;
- preserve client fallback when SSR is unavailable or invalid;
- do not let route data select arbitrary renderer endpoints.

## Render Cache Scope and Identity

`public` and `private` are security contracts:

- public SSR entries are shared only for explicit public scope and are not used for logged-in responses;
- private entries require a non-empty identity and default to `user:<id>` for logged-in WordPress users;
- logged-in responses receive no-cache behavior;
- query-bearing SSR entries are not cached unless every normalized query key is explicitly allowlisted;
- cache keys include generation, canonical route key, and identity;
- tags are normalized and invalidation uses serialized/fail-safe watermarks.

Before setting `scope: 'public'`, prove output is identical and non-sensitive for every anonymous visitor. Do not use public scope for account state, preview, cart, location/personalization, A/B assignment, consent-dependent markup, per-request tokens, or data derived from cookies/headers unless those dimensions are intentionally excluded from rendered output.

For a project authentication principal outside WordPress, define `rwp_ssr_cache_identity` as a stable, opaque, non-secret partition. An empty private identity correctly disables persistent cache; do not replace it with a shared fallback.

Allowlist only query keys that genuinely change the rendered result and whose values are bounded/canonical. Otherwise cache cardinality and poisoning risks grow.

## Static Generation

Built-in generation and runtime regeneration constrain same-origin API fetches, redirects, route count, JSON/HTML sizes, decoded paths, manifests, directory depth, entry counts, and output roots. Runtime writes are atomic and protected.

Project responsibilities:

- generate only deterministic public content;
- do not include current-user, preview, nonce, cart, or other request-specific data;
- keep `RWP_SSG_ALLOW_EXTERNAL_OUTPUT` disabled unless the exact resolved destination is reviewed;
- preserve manifest/path validation and atomic writes when extending generation;
- protect render artifacts at the web server, especially on Nginx;
- invalidate static output when every content dependency changes;
- do not treat build-time network responses as trusted merely because generation runs in CI.

## External HTTP and SSRF

For project remote integrations:

- prefer `wp_safe_remote_get/post/request` for user-influenced URLs;
- require HTTPS outside explicit local/private cases;
- use an exact hostname/origin allowlist owned by project configuration;
- reject embedded credentials, fragments, unexpected ports, and attacker-controlled schemes;
- disable or tightly cap redirects and revalidate every redirect destination;
- set short timeouts, response byte limits, and expected content types;
- validate decoded JSON schema/depth/count before using it;
- do not reflect upstream bodies/headers/errors to visitors;
- protect credentials in headers/environment, not query strings;
- consider DNS rebinding and proxy behavior when a URL can be influenced after validation.

`esc_url_raw()` is storage normalization, not SSRF protection. A URL that is valid syntax may still reach localhost, cloud metadata, internal control planes, or an attacker host.

## Filesystem Operations

Before reading, writing, moving, or deleting:

- start from a fixed, intended root;
- validate a relative path segment-by-segment and reject traversal/control characters;
- verify the final resolved target/parent remains within that root;
- reject symlinks when following them would cross the boundary;
- bound size, count, nesting, and total expanded bytes;
- use exclusive/atomic temporary writes where partial data is unsafe;
- set least-privilege permissions and keep runtime writable roots narrow;
- never use a request value as a shell command or executable path;
- avoid exposing filesystem paths in public errors.

`sanitize_file_name()` does not provide root confinement. Extension checks do not validate file contents. MIME reported by a client is not authoritative.

## Uploads and SVG

The optional ReactWP SVG plugin protects its own WordPress upload/sideload filters with:

- `manage_options` capability by default through `rwp_svg_upload_capability`;
- filename normalization and extension checks;
- byte limits through `rwp_svg_max_bytes`;
- readable regular-file/non-symlink checks;
- XML parse with network access disabled and DOCTYPE/entity rejection;
- required SVG root/namespace;
- `enshrined/svg-sanitize`, remote reference removal, fragment-only href policy;
- atomic sanitized replacement and MIME/type correction.

This protection exists only when the plugin and sanitizer dependency are present. It does not secure a project-created upload endpoint or non-SVG formats.

For new uploads also decide:

- who may upload and to which object;
- CSRF/authentication for the transport;
- allowed count/size/type/content signature;
- filename generation and collision policy;
- storage outside executable/public roots when appropriate;
- antivirus/content scanning for the threat model;
- image decompression/resource limits;
- access control and deletion lifecycle;
- safe response headers (`Content-Type`, `Content-Disposition`, nosniff).

Do not relax SVG remote references or capability because an editor cannot upload a file; diagnose the actual content/workflow first.

## Archives and Dependency Downloads

`npm run get:core` is hardened for its supported WordPress/ACF flow: HTTPS, restricted hosts/redirects, archive and expansion bounds, ZIP traversal/duplicate/symlink/encryption/compression/ZIP64/metadata validation, official WordPress checksums, explicit SHA-256 for custom sources, and ephemeral ACF credentials.

This does not make every new build downloader or archive extractor safe. Reuse its patterns or a well-maintained library and enforce:

- approved source and HTTPS;
- pinned version plus digest/signature when not covered by an official checksum service;
- redirect/size/time limits;
- safe extraction paths and entry types;
- total expanded-size/compression-ratio limits;
- clean destination replacement without deleting protected project data;
- secret redaction and cleanup of temporary credentials/files.

Never commit or print ACF licenses, `auth.json`, `COMPOSER_AUTH`, tokens, private archive URLs, cookies, SSH/private keys, or renderer secrets.

## Security Headers and CSP

ReactWP sends a baseline on non-admin pages:

- `X-Content-Type-Options: nosniff`;
- `X-Frame-Options: SAMEORIGIN` and CSP `frame-ancestors 'self'`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- restrictive camera/geolocation/microphone Permissions Policy;
- baseline CSP for `base-uri`, `object-src`, `frame-ancestors`, and `form-action`;
- production HTTPS HSTS;
- no-cache/Vary behavior for logged-in users.

The baseline CSP is intentionally compatible and does not define a strict `script-src`/`style-src`. When the project adds third-party scripts, frames, payments, maps, media, cameras, or analytics:

- update policy from an inventory of required origins/capabilities;
- prefer nonces/hashes and exact sources over `unsafe-inline`, wildcards, or broad schemes;
- keep `object-src 'none'`, `base-uri`, `frame-ancestors`, and `form-action` unless a reviewed requirement changes them;
- test both integrated WordPress and headless/CDN response paths;
- reconcile duplicate/conflicting proxy/CDN/web-server headers;
- deploy CSP Report-Only during significant tightening before enforcement when appropriate.

A filter relaxation is project-owned deployment security and belongs in reviewed project PHP/configuration.

## Production Deployment

ReactWP cannot verify the final server topology. Before release:

- set `WP_ENVIRONMENT_TYPE=production`, HTTPS, unique salts, debug display off, and file editor disabled;
- serve `dist/` as document root; do not deploy `src/`, `configs/`, environment files, development source maps, credentials, logs, private archives, or build caches;
- make core/themes/plugins read-only to PHP during normal requests; keep only intended uploads/render storage writable;
- reproduce generated deny rules on Nginx for render artifacts, ACF data, executable uploads, dependency manifests, env/backups/logs;
- keep SSR private, secret-authenticated, unprivileged, and supervised;
- configure trusted exact CORS origins and real cookie/proxy TLS behavior;
- store logs outside public root and redact credentials/tokens;
- back up database/uploads and test restoration;
- patch WordPress, ACF, ReactWP, Node, Composer, plugins, themes, and OS/web server;
- remove unused plugins/themes rather than hiding or only deactivating them.

## Verification

Select focused checks from `configs/`:

```powershell
npm run test:server-security
npm run test:static-regenerator
npm run test:svg-sanitizer
npm run test:render
npm run test:security
npm run prod
```

Also validate the deployed response headers and deny rules against the actual web server. A local unit test cannot prove Nginx/CDN configuration, file ownership, network isolation, TLS, or secret handling in production.
