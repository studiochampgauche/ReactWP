---
name: backend-expert-acf-content-modeling
description: ACF and ReactWP content-modeling guidance for field groups, stable keys, return formats, option pages, Local JSON, route exposure, editor experience, and safe schema evolution.
---

# ACF Content Modeling

## ACF's Role in ReactWP

ACF defines the editor-facing schema; ReactWP converts selected formatted values into route payloads. Model meaning, not markup. Prefer `hero_title`, `summary`, `services`, or `featured_project` over `left_column_text`, `blue_box`, or `desktop_image_2` unless the layout itself is the durable editorial concept.

For every field define:

- content purpose and editor instructions;
- type and return format;
- required, optional, or nullable behavior;
- validation and sensible bounds;
- whether it contains plain text, rich HTML, a URL, an attachment, or an object reference;
- visibility in integrated and headless consumers;
- migration/fallback behavior when renamed or removed.

## Registration and Stable Keys

Register PHP field groups on `acf/init` after confirming ACF is active:

```php
add_action('acf/init', function(){
    if(!function_exists('acf_add_local_field_group')){
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_project_content_v1',
        'title' => 'Project content',
        'fields' => [
            [
                'key' => 'field_project_summary_v1',
                'label' => 'Summary',
                'name' => 'summary',
                'type' => 'textarea',
                'required' => 0,
                'new_lines' => '',
            ],
        ],
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'project',
        ]]],
        'active' => true,
        'show_in_rest' => 1,
    ]);
}, 20);
```

Field and group keys are persistent identifiers, not decorative strings. Keep them stable across label/name edits and environments. Changing a field key can disconnect stored ACF references even if the field name is unchanged.

Use unique project-prefixed keys. Do not copy a group and leave colliding keys. A field name is the consumer-facing property; choose it deliberately and avoid silent reuse with incompatible types.

## One Source of Truth

ReactWP's `reactwp-acf-local-json` plugin saves and loads ACF JSON from the built active theme's `datas/acf` directory. Author that directory at `src/themes/<theme>/template/datas/acf/`; the theme build copies it to `dist/wp-content/themes/<theme>/datas/acf/`, which is the runtime path resolved by the plugin. It creates missing protective `index.php`, `.htaccess`, and `web.config` files, returns the theme directory as ACF's save path, removes only ACF's first default load path, and appends the theme path when it exists. Other load paths added by ACF/project filters are preserved.

Choose one ownership strategy per field group:

- PHP registration for framework/project schemas that belong in code and need explicit conditionals;
- ACF Local JSON for groups managed through the WordPress UI and synchronized through source control;
- another deliberate import/generation process, documented as the owner.

Do not maintain the same group independently in PHP and JSON. If migrating ownership, preserve keys, export/compare the group, remove the former owner, and verify the admin no longer reports an unintended sync conflict.

Commit Local JSON changes with the code that consumes them. Treat edits in production admin as unsynchronized until the JSON artifact has been brought back into source control.

## ReactWP Route Exposure

`RouteResolver::resolve_acf_payload()` finds ACF groups for the current post, user, or term context. It includes a group only when:

- the group is active;
- its location rules match;
- the group has `show_in_rest` enabled;
- its fields have non-empty names.

Values are read with `rwp::field()` and placed in `route.data`, except ReactWP-reserved fields:

- `seo` becomes `route.seo`;
- `media_groups` becomes `route.mediaGroups`;
- `react_template` becomes `route.template`;
- render fields are read separately by `RenderStrategy` and are not exposed publicly.

`show_in_rest` here is a data-exposure switch even for the integrated theme because the same route contract powers client navigation and public headless responses. Do not enable it for internal notes, credentials, operational settings, private relationship details, or fields the consumer does not need.

The group-level flag is not field-level authorization. If a group mixes public and private fields, split it or explicitly shape `rwp_route_payload`/a custom endpoint. Load `security-expert` for any visibility ambiguity.

## Return Formats and Stable Shapes

Choose return formats for consumer stability:

- image: an explicit attachment array or ID according to the media component contract;
- link: a predictable ACF link array with `url`, `title`, and `target`;
- relationship/post object: prefer IDs for storage and resolve a bounded public view model in one service;
- taxonomy: prefer term IDs or deliberate term references;
- user: never expose the complete user object;
- true/false: return a boolean rather than string flags;
- select/radio: return a stable machine value; labels belong to presentation or an explicit choice map;
- repeater/flexible content: provide stable row layout/type identifiers and bound row counts.

Avoid a field that sometimes returns an ID and sometimes an object based on admin configuration. Document empty values (`null`, `false`, `''`, or `[]`) and normalize where the consumer benefits from one predictable shape.

ReactWP `PublicPayload` bounds nested values and converts known WordPress objects for public responses. Shape complex ACF values explicitly anyway so same-origin authenticated bootstrap, SSR, headless, and tests agree.

## Reading Values

Use `rwp::field($name, $id, $format, $escape)` when the value participates in ReactWP conventions. It delegates to ACF and applies configured replacement behavior. Use direct `get_field()` when a WordPress/ACF-specific call is clearer, such as render-strategy internals or an option field, but keep format/escape expectations explicit.

Do not pre-escape values for storage. Store canonical content, validate/sanitize at input according to the contract, and escape or sanitize for the final sink. For React text, JSX escapes strings. For intended rich HTML, publish a value sanitized against the field's allowed markup: the consumer may use `dangerouslySetInnerHTML` for unchanged markup or `html-react-parser` when it must transform nodes. Neither frontend API sanitizes the value.

When an ACF value is also edited through a project form or headless consumer, follow the shared [form field contracts](form-field-contracts.md). ACF field settings or browser validation do not replace validation in custom REST/admin/programmatic writes; preserve one canonical representation and the same accepted grammar across every write path.

Be careful with formatted values in loops. Image, relationship, repeater, and flexible fields can trigger additional database work. Batch or precompute derived list shapes when content volume justifies it.

## Built-in ReactWP Groups

ReactWP registers runtime groups for:

- `media_groups` on configured posts/users/terms;
- `react_template` on configured posts/users/terms;
- `react_render_mode`, `react_render_cache_scope`, and `react_render_cache_ttl` for rendering policy.

Both `media_groups` and `react_template` are always attached to posts, pages, all user forms, and all taxonomies. The Site settings checkboxes `mediaGroups_post_types` and `react_template_post_types` add further post-type locations. The React Rendering group follows the `react_template_post_types` locations. Do not recreate these fields under new keys; extend the existing selection/settings contract or use the existing names.

ReactWP also registers option groups for languages, theme locations, headless allowed origins, and related project settings. Keep operational secrets outside public ACF options and source-controlled JSON.

## Flexible Content and Blocks

If using flexible content, treat each layout as a versioned content contract:

```json
{
  "acf_fc_layout": "text_media",
  "heading": "...",
  "body": "...",
  "media": { "id": 42, "alt": "..." }
}
```

Render by layout identifier, provide a visible fallback for unknown layouts in development, and avoid silently dropping editor content. Removing or renaming a layout requires a migration strategy.

ReactWP disables Gutenberg in its backend plugin, so do not assume block-editor workflows are available. If a project deliberately re-enables blocks, define how block markup/data enters `route.data`, how it is sanitized/rendered, and how it works in both delivery modes.

## Schema Evolution

Safe changes include adding optional fields with consumer fallbacks. Riskier changes include renaming field names, changing return formats, splitting repeaters, removing layouts, or changing relationship targets.

For a breaking change:

1. introduce the new field/shape;
2. make consumers support old and new values temporarily;
3. migrate stored values idempotently;
4. verify Local JSON/PHP definitions and field-key references;
5. invalidate affected caches/static output;
6. remove compatibility only after content and environments are migrated.

Do not use the frontend to guess many historical shapes indefinitely.

## Review Checklist

- Field names describe content meaning and keys are unique/stable.
- The group has one source of truth.
- Location rules match the intended posts/users/terms/options.
- `show_in_rest` exposes only data intended for route consumers.
- Return formats, nullability, row bounds, and rich-text semantics are documented.
- Complex references are normalized into explicit public view models.
- Existing ReactWP reserved fields are reused rather than duplicated.
- Schema changes include compatibility, migration, and cache invalidation.
