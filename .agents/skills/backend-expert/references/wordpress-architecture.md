---
name: backend-expert-wordpress-architecture
description: ReactWP-specific WordPress backend architecture covering load order, source placement, hooks, plugins, mu-plugins, theme PHP, admin behavior, services, and safe extension boundaries.
---

# WordPress Architecture

## Use This Reference When

Use this file when deciding where PHP belongs, adding a project service or hook, changing WordPress lifecycle behavior, extending the admin, or determining whether a feature belongs in a plugin, mu-plugin, or the integrated theme.

## Authored and Generated Boundaries

ReactWP is a source-to-distribution project:

```text
src/core/                         WordPress configuration source
src/mu-plugins/plugins/reactwp/   ReactWP runtime source
src/plugins/                      Regular plugin source
src/themes/reactwp/               Integrated theme source
configs/                          Build, generation, and test tooling
dist/                             Generated runnable WordPress tree
```

Make durable changes in `src/`. A change made only in `dist/` is overwritten by the next build and cannot be reviewed as the authored implementation. Work from `configs/` when running npm scripts.

Do not edit WordPress core or bundled vendor code to add project behavior. Use hooks or a project-owned plugin. If ReactWP lacks a necessary extension point, change its authored runtime source and add a regression test rather than patching generated output.

## Placement Decision

Use the narrowest stable owner:

| Owner | Appropriate work |
| --- | --- |
| Project plugin | Domain rules, content types, integrations, endpoints, scheduled work, migrations, reusable backend services |
| Mu-plugin | Mandatory bootstrap or infrastructure that must load before optional plugins and must not be deactivated casually |
| Theme PHP | Theme-specific shell, asset hooks, presentation metadata, or integration required only by the bundled React theme |
| `configs/` | Build/generation/test tooling rather than request-time WordPress behavior |

Do not put a domain model in theme PHP simply because the current consumer is React. Content types and business rules should survive a theme change. Conversely, do not place a one-theme asset tweak in the core mu-plugin.

ReactWP's runtime is an mu-plugin because route resolution, payloads, REST restrictions, rendering, and caching are framework infrastructure. Prefer its filters and public facade over editing many runtime classes for one project.

## Bootstrap and Hook Timing

Register behavior on the earliest hook that guarantees its dependencies, not earlier:

- plugin file load: definitions, constants, autoloaders, and hook registration only;
- `plugins_loaded`: coordination with normal plugins when their files must already be loaded;
- `init`: content types, taxonomies, rewrite-aware behavior, and most public registrations;
- `acf/init`: ACF field groups and option pages;
- `rest_api_init`: REST route registration;
- `wp_enqueue_scripts`: public assets;
- `admin_init`, `admin_menu`, `admin_post_*`: admin configuration and actions;
- save/create/edit/delete hooks: persistence side effects and cache invalidation.

ReactWP defines locale code `CL` during `init`. Do not require it during plugin file evaluation. Prefer WordPress locale functions or evaluate language-sensitive values inside a hook.

Guard plugin entry files with:

```php
if(!defined('ABSPATH')){
    exit;
}
```

Namespace project classes/functions or use a consistent project prefix for global callbacks and option names. Avoid generic global names such as `get_data()` or `register_fields()`.

## Extend Through Hooks

Before changing a runtime class, search for a relevant filter or action. Important backend extension points include:

- `rwp_route_payload` for route-scoped derived data or metadata;
- `rwp_bootstrap` and `rwp_system` for truly global runtime data;
- `rwp_headless_public_settings` for explicit public configuration;
- `rwp_wp_head` for normalized head entries;
- `rwp_render_templates`, `rwp_render_config`, and `rwp_render_mode` for rendering policy;
- `rwp_allowed_rest_routes` for intentionally reachable custom REST routes, including non-administrator private routes whose own `permission_callback` remains authoritative;
- `rwp_authenticated_cross_origin_rest_routes` only for explicitly admitted administrator cross-origin routes in the current implementation, because the filter is evaluated inside the `manage_options` branch;
- `rwp_headless_allowed_origins` for deployment-specific headless origins;
- `rwp_render_cache_invalidated` and `rwp_client_cache_busted` for cache lifecycle integrations.

Keep filter callbacks deterministic and return the documented type. A filter that returns `null`, echoes output, mutates global query state, or performs an unbounded remote request can break initial HTML, REST navigation, SSG, and SSR simultaneously.

`RestAccess` compares normalized concrete paths by equality; it does not evaluate a WordPress registration regex. A dynamic endpoint must validate `rwp_requested_rest_route()` against one strict anchored/bounded project pattern and append only that concrete matched path to `rwp_allowed_rest_routes`. This admits the request to its `permission_callback`; it does not provide authentication, object authorization, CORS, CSRF, validation or rate limiting. Never add a whole namespace/prefix to simulate wildcard admission.

Example route extension:

```php
add_filter('rwp_route_payload', function(array $route, $object){
    if(!$object instanceof WP_Post || $object->post_type !== 'project'){
        return $route;
    }

    $route['data']['relatedCount'] = (int)get_post_meta(
        $object->ID,
        'related_count',
        true
    );

    return $route;
}, 10, 2);
```

Place domain values in `route.data`. Reserve top-level keys for the stable route contract. Return serializable values and keep the callback valid for browser requests, REST route fetches, generators, and server rendering.

## Dependency Boundaries

Check optional dependencies before calling them:

```php
if(function_exists('acf_add_local_field_group')){
    // Register ACF fields.
}
```

Apply the same principle to multilingual plugins, commerce plugins, external SDKs, or optional object caches. A missing optional plugin should produce an intentional reduced feature or admin notice, not a fatal error on public requests.

Do not silently redefine ReactWP helpers or classes. The public `rwp` alias already exposes field, query, menu, source, bootstrap, cache, preview, sanitization, and escaping helpers. Read the actual helper before relying on its behavior.

## Admin Behavior

`reactwp-backend` deliberately removes Gutenberg for post types, trims dashboard/menu items, and reorganizes access through the admin bar. When adding an editor workflow:

- verify the target menu or metabox has not been hidden by the backend plugin;
- gate admin pages and actions with WordPress capabilities;
- use `admin_post_*` or REST/AJAX routes with nonce and capability checks for mutations;
- keep site/theme options organized under the existing ACF option pages when they are content configuration;
- avoid making a hidden core screen the only way to operate a required feature.

If changing the admin experience affects permissions or submitted data, load `security-expert`.

## Side Effects and Idempotency

Request-time hooks run frequently. Registrations may run each request, but state-changing setup must be idempotent and versioned. Never insert starter posts, rewrite options, flush rewrites, or call remote provisioning unconditionally during `init`.

For one-time work:

- attach installation logic to an appropriate activation/setup path;
- record a schema version;
- acquire a short lock if concurrent requests are possible;
- preserve existing editor content;
- make retries safe;
- expose failures without leaking secrets.

ReactWP's `firstload.php` is an example of guarded starter provisioning, not a universal migration runner. Read the migrations reference before extending it.

## Architecture Review Checklist

- The code lives in authored source under the correct owner.
- Hook timing guarantees every dependency used by the callback.
- Global symbols, options, transients, cron events, and REST namespaces are collision-resistant.
- The callback preserves the required return type and does not echo unexpectedly.
- Optional plugins are checked before use.
- Request-time work is bounded; expensive reusable results have a cache strategy.
- State-changing setup is idempotent and versioned.
- The implementation works for the intended integrated/headless consumers.
- Security-sensitive boundaries are delegated to `security-expert`.
