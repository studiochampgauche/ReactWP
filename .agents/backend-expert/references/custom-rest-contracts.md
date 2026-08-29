---
name: backend-expert-custom-rest-contracts
description: ReactWP and WordPress guidance for custom REST endpoints, schemas, permission callbacks, route allowlisting, response envelopes, pagination, errors, mutations, and compatible contract evolution.
---

# Custom REST Contracts

## When a Custom Endpoint Is Appropriate

Use the built-in route payload for page-owned content. Create a custom endpoint when the resource has its own:

- pagination, filtering, or search;
- update frequency or cache policy;
- permission/ownership rules;
- mutation workflow;
- large data volume;
- non-route identity;
- integration lifecycle.

Do not create an endpoint merely to fetch an ACF field already present in `route.data`. Do not put a large collection in the bootstrap to avoid designing pagination.

## Register with WordPress and ReactWP

WordPress registration alone is insufficient because ReactWP restricts REST access. A deliberately public project route needs both registration and an exact allowlist entry:

```php
add_filter('rwp_allowed_rest_routes', function($routes){
    $routes[] = '/project/v1/projects';
    return $routes;
});

add_action('rest_api_init', function(){
    register_rest_route('project/v1', '/projects', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'project_rest_projects',
        'permission_callback' => [
            \ReactWP\Runtime\HeadlessApi::class,
            'public_permission',
        ],
        'args' => [
            'page' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 10000,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 12,
                'minimum' => 1,
                'maximum' => 50,
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
});
```

Use a project namespace with a version segment. ReactWP's built-in namespace remains framework-owned.

`HeadlessApi::public_permission` includes public rate limiting and administrator handling. It does not authorize mutations or private records. For authenticated cross-origin routes, the ReactWP access filter and the route's business permission callback are separate decisions.

### Dynamic WordPress route patterns

`RestAccess::is_allowed()` compares the normalized concrete requested route by exact equality. A WordPress registration pattern such as `/account/records/(?P<id>\d+)` is not itself an allowlist entry and will never equal `/project/v1/account/records/42`.

When a public or non-administrator private endpoint uses path parameters, admit only the current concrete path after it matches a strict anchored and bounded project pattern:

```php
add_filter('rwp_allowed_rest_routes', static function($routes){
    $routes = is_array($routes) ? $routes : [];
    $requested = function_exists('rwp_requested_rest_route')
        ? rwp_requested_rest_route()
        : '';

    if(
        is_string($requested)
        && preg_match(
            '#^/project/v1/account/records/[1-9][0-9]{0,9}$#D',
            $requested
        )
    ){
        $routes[] = $requested;
    }

    return array_values(array_unique($routes));
});
```

The regex is route admission, not authentication or IDOR protection. Keep it anchored to one method family's concrete path grammar, bound every variable segment, and then let the route's `permission_callback` authenticate and authorize the canonical object. Do not add a namespace prefix, wildcard, unanchored substring, raw request path, or WordPress regex pattern as though `RestAccess` understood patterns.

In the current ReactWP implementation, `rwp_authenticated_cross_origin_rest_routes` is evaluated only inside the `current_user_can('manage_options')` branch. It is an administrator cross-origin exception, not a general member/JWT route admission mechanism. A non-administrator private route still needs safe admission through `rwp_allowed_rest_routes`, followed by its real authentication/object authorization callback. An explicit JWT or other project mechanism must establish the principal before authorization, and a custom namespace needs its own exact-origin CORS behavior when a browser calls it cross-origin.

Load `security-expert` for any custom endpoint. It owns detailed authorization, nonce, authentication, validation, rate-limit, CORS, and exposure rules.

## Request Schema

For user-editable values, define and coordinate the shared [form field contract](form-field-contracts.md) before choosing endpoint callbacks. The REST schema, frontend formatter, transport grammar, canonical value, stable error code, and shared fixtures must describe the same revision.

Define each accepted argument rather than reading arbitrary request arrays. For each parameter specify:

- location and name;
- scalar/array/object type;
- required/default/null behavior;
- enum or numeric/string bounds;
- normalization/sanitization;
- business validation;
- effect on query and cache identity.

WordPress `sanitize_callback` normalizes a value. It does not prove that the current actor may request it or that it represents a valid project object. Use `validate_callback` and callback-level domain checks as appropriate.

For nested JSON mutation bodies, validate the complete schema and reject unknown/oversized structures where ambiguity is risky. Unslash WordPress request data once at the appropriate boundary; do not repeatedly slash/unslash until the value appears correct.

## Permission Callback

Every route requires a `permission_callback`. Choose according to the resource:

- public read: ReactWP public permission plus public-only query logic;
- authenticated read: require an authenticated identity and object-level capability/ownership;
- mutation: authenticate, verify CSRF for cookie auth, authorize the specific action/object, and validate input;
- webhook: authenticate the sender with a separate server-side mechanism and replay/size controls.

Never use `__return_true` for a private or state-changing route. A nonce is not a capability check, and CORS is not authentication.

## Object-Level Authorization and IDOR

An accepted identifier is not an access grant. This applies to numeric IDs and to slugs, UUIDs, usernames, emails, order numbers, filenames, media keys, parent IDs, composite keys and opaque tokens used only as locators.

For every private detail, download, update, delete or action endpoint:

1. derive the actor from the authenticated WordPress/project principal, not from a request `userId`/`ownerId`;
2. validate and resolve the canonical object;
3. resolve its stored owner, tenant, parent, visibility and lifecycle state;
4. apply the narrow object-level WordPress meta capability or centralized project policy for this actor, action and object;
5. verify the canonical parent/tenant relation for nested routes rather than trusting both client IDs independently;
6. apply CSRF/replay checks separately when required;
7. keep the response/cache private to the same authorization dimensions;
8. use a consistent `403` or concealed `404` policy without exposing the foreign object's existence or fields.

For private collections, scope the database query by the authorized identity/tenant before calculating results, totals, filters, facets or cursors. For bulk operations, authorize every item and define whether the operation is atomic or returns bounded per-item failures. Route-level, post-type-level, collection-level, or first-item authorization never covers an arbitrary item selected later.

Minimum endpoint fixtures include:

- anonymous request denied;
- user A can access A's object;
- user A cannot read, update, delete or download B's object after replacing each accepted reference;
- nested child with a forged foreign parent is denied;
- mixed A/B bulk payload cannot smuggle B's object;
- administrator/support override succeeds only through its exact capability;
- cold, warm and stale cache paths do not cross identities or restore revoked access.

Read and complete the security expert's [common AI-generated backend security failures](../../security-expert/references/common-ai-backend-security-failures.md) checklist for every custom endpoint.

## Response Shape

Return a stable view model rather than database objects:

```php
function project_rest_projects(WP_REST_Request $request){
    $page = max(1, (int)$request->get_param('page'));
    $per_page = min(50, max(1, (int)$request->get_param('per_page')));

    $query = new WP_Query([
        'post_type' => 'project',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'ignore_sticky_posts' => true,
    ]);

    $items = array_map(static function(WP_Post $post){
        return [
            'id' => (int)$post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'url' => get_permalink($post),
        ];
    }, $query->posts);

    $payload = \ReactWP\Runtime\PublicPayload::response([
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'perPage' => $per_page,
            'totalItems' => (int)$query->found_posts,
            'totalPages' => (int)$query->max_num_pages,
        ],
    ]);

    return new WP_REST_Response($payload, 200);
}
```

Use a consistent casing convention. ReactWP route responses use camelCase in public view models; match the surrounding contract unless a separately versioned API deliberately chooses otherwise.

Keep list items smaller than detail resources. Avoid embedding every relationship recursively. Use links/IDs or separate endpoints to prevent cycles and oversized responses.

## Errors and Status

Use `WP_Error` with a stable machine code, safe message, HTTP status, and optional bounded public details:

```php
return new WP_Error(
    'project_invalid_filter',
    __('The requested project filter is invalid.', 'project'),
    ['status' => 400]
);
```

Use status semantics consistently:

- `200` successful read/update;
- `201` created resource, ideally with location/self link;
- `204` successful no-content action;
- `400` malformed or invalid request;
- `401` authentication required/failed;
- `403` known actor lacks permission;
- `404` resource absent or intentionally concealed;
- `409` state conflict;
- `422` semantically invalid payload when the API uses that distinction;
- `429` rate limited;
- `5xx` unexpected/transient server error.

Do not leak SQL, filesystem, upstream credentials, tokens, stack traces, or private object details in messages.

## Pagination, Filtering, and Sorting

Bound `per_page` and cap expensive page depths when necessary. Prefer cursor/keyset pagination for very large or rapidly changing collections, but document the cursor contract. Allowlist sort fields and directions; never pass raw client values as SQL identifiers or `orderby` arrays.

Normalize filters to stable cache keys. Define whether empty filters are omitted or explicit. Include language, visibility, and user identity when they change results.

## Mutations and Side Effects

For create/update/delete endpoints:

1. authenticate and authorize the exact action;
2. validate the complete request contract;
3. normalize canonical values;
4. update through WordPress/ACF APIs;
5. handle partial failure intentionally;
6. invalidate render/client/external caches affected by the change;
7. return the new canonical public view model;
8. add a focused security/contract test.

Do not trust a client-provided owner, post status, capability, price, role, or cache tag without deriving/allowlisting it server-side.

Do not forward the complete request array into a WordPress/ACF/meta/user persistence API. Maintain an explicit field-to-domain map for this operation, reject or deliberately ignore unknown fields according to the versioned contract, and keep privileged fields in separate dedicated operations with their own target-user/object authorization. Test protected fields at the top level, nested level, alternate casing and duplicate-key/ambiguous-parser boundaries supported by the transport.

## Contract Evolution

Prefer additive optional fields. Avoid changing type/nullability or reinterpreting existing values inside the same version. If a breaking change is unavoidable:

- add a new namespace/version or response variant;
- support a bounded migration window;
- update consumers and contract tests;
- define cache separation;
- retire the old version deliberately.

## Verification Checklist

- Exact REST path is registered with WordPress and ReactWP access filters.
- Args define types, defaults, bounds, and allowed values.
- Permission callback matches public/private/mutation behavior.
- Every caller-supplied object reference is followed by canonical object/owner/tenant/parent authorization; user A/object B substitution and bulk/nested variants are denied.
- Mutation fields are explicitly mapped; protected and unknown mass-assignment fields cannot alter state.
- Query cannot expose drafts, password-protected posts, or unauthorized records.
- Response contains explicit bounded serializable values.
- Error codes/statuses are stable and do not leak internals.
- Pagination/filter/language/user dimensions are reflected in cache identity.
- Mutations invalidate every affected ReactWP and external cache.
- Allowed, malformed, unauthorized, oversized, empty, and failure cases are tested.
