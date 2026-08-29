---
name: security-expert-wordpress-rest-auth-data
description: ReactWP-specific guidance for REST route allowlisting, permission callbacks, WordPress capabilities and nonces, headless CORS/auth, preview tokens, public payloads, rate limits, and sensitive data handling.
---

# WordPress REST, Authentication, and Data

## When to Use This Reference

Apply when adding/changing a REST route, AJAX/admin action, authenticated frontend, public headless field, preview, CORS origin, permission filter, rate limit, current-user payload, or cacheable API response.

## ReactWP's Two REST Gates

A request to a custom route may pass through two independent decisions:

1. The global `rest_authentication_errors` gate permits administrators on same-origin requests or an exact normalized route in the appropriate ReactWP filter.
2. WordPress calls the route's own `permission_callback`.

Both are required. Adding a route to `rwp_allowed_rest_routes` makes it reachable to the applicable unauthenticated request; it does not make its data safe or authorize an object.

Built-in routes use exact normalized matching through `RestAccess`, so unrelated query text cannot smuggle an allowed path. Preserve exact matching for project additions.

`RestAccess::is_allowed()` does not interpret WordPress route regexes. For a dynamic route such as `/project/v1/account/records/(?P<id>\d+)`, inspect the normalized concrete `rwp_requested_rest_route()` inside `rwp_allowed_rest_routes`, require a strictly anchored/bounded project regex such as `#^/project/v1/account/records/[1-9][0-9]{0,9}$#D`, and add only that concrete matched path. Never allow a namespace prefix, wildcard or unanchored substring. This step merely admits the request to WordPress's route `permission_callback`; the callback still authenticates and authorizes the resolved object.

## Registering a Public Project Route

A public route needs an explicit decision to be public, a permission/rate policy, strict arguments, bounded work, and a shaped response.

```php
add_filter('rwp_allowed_rest_routes', function($routes){
    $routes[] = '/project/v1/catalog';
    return $routes;
});

add_action('rest_api_init', function(){
    register_rest_route('project/v1', '/catalog', [
        'methods' => 'GET',
        'callback' => 'project_catalog_response',
        'permission_callback' => [
            \ReactWP\Runtime\HeadlessApi::class,
            'public_permission',
        ],
        'args' => [
            'page' => [
                'required' => false,
                'default' => 1,
                'validate_callback' => static function($value){
                    return is_numeric($value) && (int)$value >= 1 && (int)$value <= 100;
                },
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
});
```

`HeadlessApi::public_permission` supplies the built-in public rate limiter; it does not inspect the response or cap project query complexity. Apply a lower/narrower policy if the operation is expensive.

Return only explicit public fields. Do not serialize full post/user/ACF objects. `PublicPayload::sanitize_value()` helps bound and normalize public values but cannot identify project secrets in arbitrary keys.

## Authenticated and Administrative Routes

Every sensitive route needs the narrowest permission callback:

```php
'permission_callback' => static function(WP_REST_Request $request){
    $post_id = absint($request->get_param('id'));

    return $post_id > 0 && current_user_can('edit_post', $post_id);
},
```

Use object-level capabilities (`edit_post`, `read_post`, `delete_post`) where ownership/object mapping matters. `manage_options` is appropriate for site-wide configuration, not as a default for every feature.

ReactWP permits cookie-authenticated administrators to normal same-origin REST access, but the route callback still owns its permission rule. Hiding an admin menu, page, React route, or button is not authorization.

An object ID is not authorization either. Apply the same rule to slugs, UUIDs, usernames, emails, filenames, order numbers and nested parent IDs: resolve the canonical object and its stored owner/tenant/parent/state, then authorize the current actor and exact action. Scope private collection queries before calculating totals, authorize every bulk item, and partition caches by the same identity dimensions. Test user A with user B's locator rather than only testing anonymous access.

For a cookie-authenticated mutation, WordPress REST nonce validation normally participates in cookie authentication. If implementing a custom non-REST form/AJAX action, verify a dedicated nonce and capability explicitly.

## Nonces

Nonces mitigate CSRF for an action/session window. They do not:

- authenticate a bearer API client;
- prove the user can edit the target object;
- make replay impossible;
- sanitize data;
- protect a token placed in a public URL.

For admin form actions follow the existing cache action pattern:

```php
if(!current_user_can('manage_options')){
    wp_die(esc_html__('You are not allowed to perform this action.', 'project'));
}

check_admin_referer('project_action');
// Validate and perform the mutation.
```

Check capability before or alongside nonce validation so possession of a nonce never expands authority.

## Headless Authentication and CORS

Built-in `/reactwp/v1/auth/*` behavior includes:

- exact normalized credentialed origins;
- HTTPS outside explicit local development;
- JSON login by default;
- username/password bounds;
- generic failures;
- per-address and per-address-plus-username throttling;
- no-store current-user responses;
- `X-WP-Nonce` validation for authenticated logout.

The exact defaults are five failures per client-address + normalized username, 25 failures per client address, and a 600-second lock window. Public headless endpoints default to 240 requests per 60-second bucket; `manage_options` users bypass that public limit. Client identity starts from validated `REMOTE_ADDR`; changing `rwp_headless_client_ip` transfers trusted-proxy parsing responsibility to the project.

This protection applies to the built-in endpoints, not to arbitrary project login/account routes.

Project obligations:

- configure exact trusted origins; never `*` with credentials;
- confirm cookie domain, Secure, HttpOnly, SameSite, TLS termination, and reverse-proxy topology;
- keep `rwp_headless_allow_insecure_auth` disabled outside truly isolated development;
- use `rwp_headless_client_ip` only after verifying that the immediate proxy is trusted and parsing forwarded addresses safely;
- do not treat CORS as a server-side authorization barrier—non-browser clients ignore it;
- avoid returning REST nonces or identity data in public/bootstrap/cacheable responses.

In the current ReactWP implementation, `rwp_authenticated_cross_origin_rest_routes` is consulted only after `current_user_can('manage_options')`. It permits an exact route through the administrator cross-origin branch; it is not a general member or JWT admission filter. A non-administrator private route must be safely admitted through `rwp_allowed_rest_routes`—using concrete-path validation for dynamic routes—then authenticate and authorize in its own `permission_callback`. An explicit JWT or other project mechanism must establish the principal before that callback. Authentication, capability/object policy, CSRF/token, response, cache and rate decisions still remain separate.

ReactWP's custom credentialed CORS response handling is scoped to the `/reactwp/v1` namespace. A project route under another namespace does not automatically inherit those response headers merely because it was added to a REST allowlist. Decide whether that route should be cross-origin at all; if so, implement an exact-origin, credential-aware policy without broadening unrelated WordPress REST routes.

## Preview Tokens

Use `rwp::preview_token($post_id, $ttl)` for issuance and the built-in preview endpoint for validation. It already requires edit capability, binds the post ID, signs with a WordPress-derived secret, caps lifetime, checks time structure, uses `hash_equals`, and marks the response no-store.

Transport tokens in `X-ReactWP-Preview-Token` or `Authorization: Bearer`. Query tokens are disabled by default because URLs leak through browser history, analytics, referrers, caches, screenshots, and logs.

Do not:

- create a reusable unbound preview token;
- extend TTL or query transport without a documented deployment need;
- put preview data into a public SSR/browser/CDN cache;
- log the Authorization/preview header;
- infer authorization from a post ID without validating the token against that ID.

## Public Payloads

`PublicPayload` provides a public projection, not blanket HTML sanitization. It:

- excludes non-public posts/authors/terms/attachments when encountered as WordPress objects;
- normalizes route, navigation, URLs, attachment metadata, and known user references;
- removes unsupported arbitrary objects;
- bounds recursion, array counts, head entries, and strings;
- omits `currentUser` and REST nonce from public bootstrap;
- returns current identity only through the no-store authenticated endpoint.

It does not know that `api_key`, `internal_notes`, `cost_price`, an email string, or a custom nested scalar is sensitive. Never add secrets/personal data to:

- `rwp_bootstrap` on public requests;
- `rwp_headless_public_settings`;
- public route ACF fields;
- navigation/sitemap filters;
- public render/cache payloads.

Review payloads by data classification, not only by XSS safety.

## Public vs Authenticated Route Resolution

`RouteResolver` prevents non-public, trash, scheduled/private, password-protected, and unauthorized objects from public resolution. Logged-in users with the correct capability can resolve more content in integrated contexts.

Therefore:

- do not cache logged-in/preview route payloads as public;
- do not use a privileged server-generated payload to answer an anonymous headless request;
- keep public cache scope only for deterministic anonymous content;
- review custom `rwp_route_payload` filters for differences between anonymous, authenticated, and preview contexts.

## Rate Limits and Resource Bounds

The built-in public permission callback and auth endpoints have rate limits. Project endpoints need their own cost analysis:

- maximum page/page-size and filters;
- query complexity and result count;
- response byte bounds;
- remote-call timeouts and fan-out;
- cache key cardinality;
- per-user/IP/global throttles as appropriate;
- object-cache availability and multi-node consistency.

Rate limiting is defense in depth, not permission. Avoid using untrusted forwarded headers as the client identity unless the trusted proxy chain has been validated.

## Errors and Enumeration

- Return generic authentication/reset/invitation failures where specificity enables account enumeration.
- Use `WP_Error` with appropriate HTTP status and stable machine code.
- Do not return SQL, stack traces, filesystem paths, internal upstream bodies, secrets, or existence details the caller is not authorized to know.
- Log operational detail outside the public document root with secret/header redaction.
- Keep preview and authenticated responses `no-store`.

## Route Review Checklist

- Is the exact route intentionally reachable through ReactWP's global gate?
- Does `permission_callback` authorize the actor and target object?
- Can user A replace every accepted object/parent locator with user B's and still receive only the documented denial, including list, nested, bulk, download and cache paths?
- Does each mutation map only approved writable fields, preventing protected/unknown/nested role, capability, owner, tenant, status or other mass-assignment changes?
- Is CSRF handled for cookie-authenticated mutations?
- Are input schema, size, enums, and cross-field rules validated before work?
- Is output an explicit projection with no secrets/non-public references?
- Does response caching match public/private/auth/preview status?
- Are CORS and credentials configured only when required?
- Are expensive work, remote calls, results, and errors bounded?
- Is there a focused unauthorized and malformed-request test?
