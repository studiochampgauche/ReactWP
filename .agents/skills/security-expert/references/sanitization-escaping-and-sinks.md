---
name: security-expert-sanitization-escaping-sinks
description: Context-specific ReactWP and WordPress guidance for validation, rwp sanitization/escaping APIs, ACF fields, PHP/React output, rich HTML, URLs, SQL, paths, and avoiding double escaping.
---

# Sanitization, Escaping, and Sinks

## When to Use This Reference

Apply when data enters through requests, ACF, REST, remote services, files, or cached payloads, or leaves through PHP/React HTML, attributes, URLs, JavaScript, CSS, SQL, redirects, headers, filesystem paths, logs, or commands.

## The Correct Order

For a typical WordPress mutation:

1. Read and `wp_unslash()` request values.
2. Validate type, presence, enum/range/shape, size, and cross-field rules.
3. Verify capability/ownership and a nonce for cookie-authenticated state changes.
4. Normalize/sanitize into a canonical storage value.
5. Store with a WordPress API or prepared database operation.
6. Escape each time the value is rendered, for that final sink.

Do not escape before storage. A value stored as `Tom &amp; Jerry` is presentation data and is likely to be double-escaped later.

## ReactWP PHP Facades

### `rwp::sanitize($type, ['value' => ...])`

Available types dispatch to WordPress functions:

| Type | Underlying intent |
| --- | --- |
| `email` | `sanitize_email` normalization; separately validate deliverability/required format |
| `file_name` | filename normalization, not path confinement or upload validation |
| `hex_color`, `hex_color_no_hash` | strict color normalization |
| `html_class`, `key`, `slug`/`title`, `user` | identifier normalization with different allowed character rules |
| `mime_type` | MIME string normalization, not file-content verification |
| `meta`, `option`, `term`, `term_field` | WordPress object-context sanitization |
| `text_field`, `textarea_field` | plain text normalization; not HTML-preserving |
| `url` | storage/transport URL sanitization; still apply scheme/host/business allowlists |
| `sql_orderby` | narrow ORDER BY validation only; not a general SQL sanitizer |
| `html` | `wp_kses` with supplied allowed HTML/protocols |
| `post_content` | `wp_kses_post` |

Unsupported types return `null`. Treat `null`/empty results according to the field contract; do not silently save them when the input should be rejected.

Example input handling:

```php
function project_handle_settings() {
    if(!current_user_can('manage_options')){
        wp_die(esc_html__('You are not allowed to perform this action.', 'project'));
    }

    check_admin_referer('project_save_settings');

    $raw_label = isset($_POST['project_label'])
        ? wp_unslash($_POST['project_label'])
        : '';

    if(!is_string($raw_label) || strlen($raw_label) > 200){
        wp_die(esc_html__('Invalid label.', 'project'), 400);
    }

    $label = rwp::sanitize('text_field', [
        'value' => $raw_label,
    ]);

    if($label === null || $label === ''){
        wp_die(esc_html__('A label is required.', 'project'), 400);
    }

    update_option('project_label', $label, false);
}
```

The capability and nonce are independent of sanitization.

### `rwp::escape($type, $value, $args)`

Use at output:

| Sink | ReactWP type / WordPress equivalent |
| --- | --- |
| Text between HTML tags | `html` / `esc_html` |
| HTML attribute | `attr` / `esc_attr` |
| URL in rendered HTML | `url` / `esc_url` |
| URL stored or sent outside HTML | `url_raw` / `esc_url_raw` |
| Textarea content | `textarea` / `esc_textarea` |
| XML text | `xml` / `esc_xml` |
| JavaScript string fragment | `js` / `esc_js`, though data should usually use `wp_json_encode` |
| Translated text/attribute | `html__`, `html_x`, `attr__`, `attr_x` |

Examples:

```php
<h2><?php echo rwp::escape('html', $title); ?></h2>
<a href="<?php echo rwp::escape('url', $url); ?>"
   data-label="<?php echo rwp::escape('attr', $title); ?>">
    <?php echo rwp::escape('html', $label); ?>
</a>
```

One value can require different escaping in different sinks. Do not create an `$escaped_value` and reuse it across HTML text, attributes, URLs, and JavaScript.

### `rwp::field()` and ACF

`rwp::field($field, $id = false, $format = true, $escape = false)` delegates to ACF. The escape flag defaults to `false`. `RouteResolver` intentionally reads formatted ACF fields with escaping disabled and carries them as data.

Consequences:

- ACF field configuration and editor capability are part of trust, but stored content is not automatically safe for every sink.
- `PublicPayload::sanitize_value()` does not turn arbitrary ACF strings into safe HTML.
- Plain React `{value}` text is encoded by React.
- Rich ACF HTML needs a reviewed server-side `wp_kses` allowlist before any React raw-HTML sink; use `RichText` only when its node transformations are also needed.
- ACF URL fields should use `AppLink`/`Button` in React or contextual URL validation/escaping in PHP.
- A field used as a class, tag name, style, selector, query key, filename, or external host needs a strict local allowlist/normalizer.

Do not set `$escape = true` globally to avoid thinking about sinks. It can remove markup a component intentionally supports and does not solve URL, SQL, authorization, or path safety.

## HTML: Preserve or Encode Deliberately

Choose one:

- **Plain text:** escape/React-render it as text.
- **Limited rich text:** sanitize with a specific server-side `wp_kses` allowlist, then render unchanged sanitized HTML with `dangerouslySetInnerHTML` or use `html-react-parser` only when the React tree must be transformed.
- **Trusted application markup:** write it in the template/component source, not in CMS data.

PHP example for limited HTML:

```php
$allowed = [
    'p' => [],
    'strong' => [],
    'em' => [],
    'a' => [
        'href' => true,
        'rel' => true,
        'target' => true,
    ],
];

echo wp_kses($editor_html, $allowed, ['http', 'https', 'mailto']);
```

Do not run `esc_html()` after `wp_kses()` if the intention is to render the allowed tags; that would display them as text. Re-sanitize at output when the stored value or policy may have changed, but do not repeatedly entity-escape it.

## React Sinks

Safe default:

```jsx
<h1>{route.data.title}</h1>
```

React encodes markup characters here. It does not validate meaning or URL schemes.

For unchanged HTML that has already passed the backend's explicit HTML allowlist, keep the raw sink small and named by its contract:

```jsx
const SanitizedHtml = ({ html = '', className = '' }) => {
    const markup = { __html: html };

    return <div className={className} dangerouslySetInnerHTML={markup} />;
};
```

The component name is not a sanitizer: its caller must supply the sanitized contract. Keeping the `{ __html }` object close to this boundary makes raw HTML use easy to audit.

Use project URL primitives independently:

```jsx
<AppLink to={route.data.cta_url}>{route.data.cta_label}</AppLink>
<Button href={route.data.cta_url}>{route.data.cta_label}</Button>
```

Use `html-react-parser` when rendering must transform the HTML tree. ReactWP's `RichText` is a valid example because its `replace` function removes unsupported elements, maps attributes, validates URLs and `srcset`, and adjusts `_blank` links:

```jsx
import { createElement } from 'react';
import parse, { domToReact, Element } from 'html-react-parser';

const options = {
    replace(node){
        if(!(node instanceof Element)){
            return undefined;
        }

        const tag = String(node.name || '').toLowerCase();

        if(!['p', 'strong', 'em', 'br'].includes(tag)){
            return <></>;
        }

        return createElement(tag, {}, domToReact(node.children, options));
    }
};

const TransformedHtml = ({ value = '' }) => parse(value, options);
```

Choosing the parser is a rendering decision, not a security guarantee. `html-react-parser` converts markup into React elements but does not sanitize it. Both paths require an upstream trust/sanitization contract; parser transformations may add a narrower defense-in-depth policy when the component intentionally needs one.

React escapes a direct `href` attribute's markup characters but does not make every protocol acceptable. `sanitizeDomProps()` also does not validate general `href`/`src` values; URL-owning components must do that themselves.

Never pass CMS JSON directly into component spreads:

```jsx
// Unsafe design: the data shape controls the DOM surface.
<div {...route.data.attributes} />
```

Map allowed fields to explicit props or pass them through a purpose-built, reviewed allowlist.

## URLs, Redirects, and Remote Hosts

URL safety has multiple layers:

- syntax and allowed protocol;
- absolute vs local requirement;
- same-origin or explicit host allowlist;
- no embedded credentials;
- no control characters;
- destination authorization/business rule;
- output escaping or safe request API.

`esc_url_raw()`/`rwp::sanitize('url')` is not an SSRF allowlist. For server-side fetches use `wp_safe_remote_*`, HTTPS where required, explicit allowed hosts, redirect limits, byte/time limits, and careful handling of DNS/proxy behavior.

For redirects, prefer `wp_safe_redirect()` and then `exit`. If an external redirect is intentionally allowed, validate it against an exact host/origin allowlist.

## SQL

Prefer WordPress query APIs. For custom SQL:

- use `$wpdb->prepare()` for values;
- cast numeric IDs with `absint()`/`(int)` after validation;
- allowlist table/column/direction identifiers because placeholders do not make arbitrary identifiers safe;
- use `$wpdb->esc_like()` before a prepared `LIKE` value;
- never concatenate `$_GET`, `$_POST`, route data, ACF, or remote values;
- do not use `sanitize_text_field()` or `sanitize_sql_orderby()` as a general SQL defense.

```php
$status = in_array($raw_status, ['publish', 'draft'], true)
    ? $raw_status
    : 'publish';

$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND post_author = %d",
    $status,
    $author_id
));
```

The table identifier above is a trusted WordPress property, not user input.

## Paths and Files

`sanitize_file_name()` secures a filename representation; it does not prove a final path stays inside an allowed root.

For project-owned file access:

- reject null/control characters and unexpected separators;
- use an allowlisted extension and independently verify file content when relevant;
- build paths from a fixed root and a validated relative path;
- reject `.`/`..`, symlinks where inappropriate, and path normalization escapes;
- resolve/check the final parent or existing target remains inside the intended root;
- bound file size and count before reading/extracting;
- use atomic temporary-write plus rename for generated mutable files;
- keep executable content out of uploads and public writable directories.

Do not pass a sanitized filename into a shell command. Avoid shell execution; if unavoidable, use a fixed executable/argument structure and the platform's proper process API rather than composing a command string.

## Headers, Logs, and Errors

- Reject control characters in any project-controlled header value.
- Do not let request values select arbitrary header names.
- Keep authentication, preview, renderer, license, and remote API secrets out of URLs and logs.
- Return generic authentication failures and visitor-safe remote/render errors; log diagnostic detail only to protected server logs.
- Do not include stack traces, filesystem paths, SQL, environment variables, or upstream response bodies in public errors.

## Double-processing Checklist

Before adding another sanitizer/escape call, ask:

- Is this value canonical input, stored content, transport data, or output?
- Has it been normalized, or entity-escaped?
- Is markup meant to survive?
- Does the existing guard protect this exact sink or only a previous boundary?
- Would applying it twice corrupt URLs, ampersands, quotes, JSON, or allowed HTML?

Normalize once per input boundary when possible; validate whenever a trust boundary is crossed; escape every final output according to its context.
