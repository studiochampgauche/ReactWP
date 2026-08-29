---
name: security-expert-responsibility-matrix
description: Matrix of ReactWP's built-in security guarantees, their preconditions, and controls that remain the responsibility of project code. Use before relying on a framework protection or extending a protected subsystem.
---

# ReactWP Security Responsibility Matrix

## How to Use This Reference

Find the affected boundary, inspect the named implementation, then confirm its preconditions still hold. The third column is not optional merely because ReactWP covers the first column.

## PHP Data and Output

| Boundary | ReactWP provides | Project must still provide |
| --- | --- | --- |
| `rwp::sanitize($type, $args)` | Thin dispatch to contextual WordPress sanitizers/`wp_kses`; returns `null` for unsupported types or invalid argument shape | Validate required shape/range/enum first; unslash request data; authorize the operation; choose the correct sanitizer; handle rejection; escape at output |
| `rwp::escape($type, $value, $args)` | Thin dispatch to contextual WordPress escaping functions | Choose the final sink context; validate business meaning; never use escaping as SQL/path/permission protection; avoid pre-escaped storage |
| `rwp::field($field, $id, $format, $escape)` | Delegates to ACF `get_field`; optional ACF HTML escaping only when explicitly requested and supported | The default `escape` argument is `false`; decide whether formatted HTML is intended; do not assume route ACF strings are HTML-safe; apply sink-specific handling |
| Project-owned form fields | WordPress/ACF/REST and React provide primitives for schemas, hooks, controls, sanitization, escaping, and errors; ReactWP does not define a project's field grammar or synchronize PHP and JavaScript validators | Maintain one frontend/backend-approved field contract; enforce type/bounds/grammar/business rules on the server; normalize deliberately; authorize and verify CSRF separately; test client bypass and shared fixtures |
| `Bootstrap::json()` | `wp_json_encode` with JSON hex flags, invalid UTF-8 substitution, and data-only script embedding | Keep payload data-only; do not concatenate raw strings into the script block; do not treat decoded values as safe HTML/URLs |
| `PublicPayload` | Bounds depth/count/string size; removes arbitrary objects; normalizes known public post/term/user/attachment references; excludes non-public references; normalizes known URLs/route shape | Keep secrets and privileged fields out of public filters/data; validate application schemas; sanitize rich HTML at its renderer; validate URLs at their actual sink; review scalar strings individually |
| WordPress/PHP templates | Existing templates use contextual `esc_*` for attributes, URLs, and text | Escape every new output at the final context; use `wp_kses` only for intentionally allowed HTML; validate redirects and headers; never echo raw request/ACF values |

## Browser and React Output

| Boundary | ReactWP provides | Project must still provide |
| --- | --- | --- |
| Normal React text/attributes | React encodes text and attribute markup characters during client/server render | Use JSX text for plain content; validate URL schemes, CSS values, element/tag choice, and sensitive data separately; maintain hydration-safe markup |
| `dangerouslySetInnerHTML` | React inserts the supplied HTML string into the DOM without encoding it as text | Use only at a small explicit boundary for HTML already trusted and sanitized against the intended markup policy; never pass arbitrary WordPress, ACF, API, editor, or user strings |
| `RichText` | Uses `html-react-parser` with `replace` to remove unsupported nodes, normalize attributes, validate URL-bearing values, and produce React elements | Use it when those transformations are required; do not treat the parser itself as sanitization; keep upstream sanitization and update the transformation allowlists narrowly |
| `sanitizeDomProps` | Blocks raw HTML/srcDoc/form-action overrides, non-function event values, oversized strings, and unsafe-looking style values | It is not a general URL sanitizer and does not authorize caller-controlled event functions; use only with trusted component code; validate semantic variants and destinations separately |
| `AppLink` and `Button` | Destination length/control checks, allowed external schemes, safe `_blank` rel, internal router handling | Use them for CMS/user-controlled destinations; decide whether a route is internal/full navigation; do not create direct `<a href={untrusted}>` sinks without equivalent scheme validation |
| `useDocumentMeta` | Route head allowlist for bounded title/meta/selected HTTP(S) links; discards scripts/styles/base/refresh/CSP/arbitrary nodes | Keep project head extensions within the allowlist or create a separately reviewed API; never accept arbitrary script/head HTML from CMS |
| `Loader` media | HTTP(S)/blob URL checks, media-property/style/dataset bounds, target limits, protected structural nodes | Provide trustworthy selectors, semantics/alt text, cache policy, and content schema; do not add executable/custom element replacement without review |

## REST, Authentication, and Data Exposure

| Boundary | ReactWP provides | Project must still provide |
| --- | --- | --- |
| Global REST gate (`RestAccess`) | Guests/non-admins denied except exact normalized allowlisted concrete routes; administrator same-origin access; separately filtered administrator cross-origin routes | Every custom route needs safe concrete admission plus its own `permission_callback`; dynamic WordPress route patterns require anchored/bounded matching that adds only the current concrete path; route admission does not authenticate/authorize an object or sanitize a response |
| Built-in public headless routes | Public permission rate limit, safe local view parsing, public payload shaping, non-public object exclusion | Project filters must expose only intentional public data; expensive custom work needs bounds/cache/rate decisions; do not assume custom routes inherit these callbacks |
| Project performance/capacity changes | ReactWP bounds its built-in public payloads, authentication/public rate paths, SSR transport, cache query dimensions, generation and selected file/network work | Measure project queries/payloads/jobs/integrations; bound maximum cost; preserve authorization and privacy on optimized/cache-hit paths; define custom rate/cache/queue behavior; test exhaustion and dependency failure |
| Built-in headless authentication | Credentialed exact-origin CORS, HTTPS outside local development, JSON login, generic failures, rate limiting, no-store identity responses, REST nonce on logout | Configure only trusted exact origins; secure proxy/client-IP filters; review cookie/SameSite/TLS topology; custom auth/account endpoints need their own complete design |
| Preview | Capability-gated issuance, signed post-bound expiry-limited token, constant-time comparison, no-store response, header/Bearer transport by default | Do not enable query tokens casually; never log/cache/share tokens; check preview data and downstream caches; use built-in token creation/validation rather than inventing a weaker token |
| CORS | Reflects only normalized allowed origins and allows credentials on built-in `/reactwp/v1` namespace responses | CORS is a browser transport control, not authentication; still authorize server-side; never use wildcard origins with credentials; custom namespaces need their own reviewed CORS behavior |
| Nonces | Built-in logout/admin actions verify appropriate nonce | Add nonces to every new cookie-authenticated mutation; still check capabilities/ownership; do not use nonce for bearer-token/API authentication |

## Rendering and Caches

| Boundary | ReactWP provides | Project must still provide |
| --- | --- | --- |
| PHP-to-Node SSR transport | Endpoint validation, strong secret by default, remote HTTPS opt-in, safe request APIs, no redirects, bounded response/time, JSON validation, circuit breaker | Keep renderer private; configure same strong secret; keep project template code safe; review any payload filter; do not expose SSR port or internal error details |
| Node render server | Secret authentication, method/content-type/query/body/header/response/time/concurrency limits | Run as unprivileged supervised service; terminate TLS/network privately; keep dependencies updated; avoid project code that performs unbounded work or unsafe remote access during render |
| Public/private SSR cache | Public cache only for explicit public scope and logged-out responses; private cache requires identity; query keys denied by default; cache tags normalized | Choose scope from data sensitivity, not performance preference; supply stable private identity for non-WordPress principals; allowlist only behavior-relevant query keys; never cache secrets/personalized HTML publicly |
| Browser payload/media cache | Private route persistence disabled by default; refuses `private`/`no-store` responses | Set response/cache metadata correctly; do not persist auth/preview/private payloads through custom storage; invalidate when business dependencies change |
| Static generation/runtime fragments | Same-origin/bounded fetches, route/path validation, output confinement, safe manifests, atomic protected writes | Generate only public deterministic routes; keep external-output override off unless reviewed; configure Nginx/IIS/Apache protection; do not include user-specific data |

## Files, Supply Chain, and Deployment

| Boundary | ReactWP provides | Project must still provide |
| --- | --- | --- |
| SVG plugin | Capability default, byte bounds, XML parsing without external entities, sanitizer library, remote reference removal, atomic replacement | Ensure plugin/dependency is active; do not relax capability/size/remotes without review; custom uploads and other formats need their own validation; serve uploads non-executable |
| `get:core` | HTTPS/host/redirect/archive bounds, ZIP traversal/symlink/encryption/structure checks, WordPress checksums, explicit custom checksums, protected credential flow | Trust only reviewed download hosts; protect environment credentials; review dependency updates; never commit private URLs, `auth.json`, license keys, or archives |
| Default headers/hardening | nosniff, SAMEORIGIN/frame-ancestors, referrer/permissions policy, baseline CSP, production HSTS, XML-RPC disabled, file editor/debug/listing restrictions in shipped configuration | Reconcile headers at CDN/proxy/web server; tighten CSP for project scripts; review every relaxation; set production environment/HTTPS/salts/permissions; deploy only `dist/` |
| Generated Apache/IIS protections | Deny render artifacts, sensitive metadata, and executable uploads in generated configuration | Nginx needs equivalent explicit rules; verify real document root and server behavior; do not deploy `src/`, `configs/`, source maps, credentials, logs, or private build inputs |

## Important Non-Guarantees

ReactWP does not automatically:

- define, format, validate, or synchronize project-owned form-field grammars across React, headless consumers, WordPress, ACF, REST, or admin handlers;
- know which project fields are secrets or personal data;
- authorize custom business actions or ownership rules;
- prevent IDOR in project routes: a caller-supplied ID/slug/UUID/filename/parent reference still requires authorization against the canonical object's owner, tenant, visibility and state, including list, bulk and cache paths;
- prevent mass assignment or privilege escalation when project code forwards generic request fields into user/post/meta/ACF/domain persistence;
- provide generic project JWT authentication, custom password storage, custom account recovery, or project session/revocation policy; the built-in headless auth uses WordPress cookies and the preview token has only its documented post-bound purpose;
- sanitize every ACF/scalar string as HTML;
- make HTML safe merely by switching between `dangerouslySetInnerHTML` and `html-react-parser`, or make direct URL sinks, CSS strings, shaders, or third-party embeds safe without their own contextual allowlists;
- prepare arbitrary SQL, validate dynamic identifiers, or make `sanitize_sql_orderby()` a general SQL defense;
- secure custom upload endpoints, archive extraction, local file paths, or shell commands;
- rate-limit or cache-isolate every custom endpoint;
- make menu hiding or frontend route hiding an access-control boundary;
- configure trusted proxies, WAF/CDN, TLS, cookies, backups, logging, monitoring, or Nginx on the project's behalf;
- keep dependencies patched after deployment.

When project code crosses one of these boundaries, the project owns the missing control and its regression tests.

Use [common-ai-backend-security-failures.md](common-ai-backend-security-failures.md) as the mandatory cross-boundary gate for project-owned security-sensitive backend code.
