# Utils PHP

## Use `get_field()` function of ACF differently.
```
scg::field($field, $id = false, $format = true, $escape = false);

// OR

StudioChampGauche\Utils\Field::get($field, $id = false, $format = true, $escape = false);
```

- `$field` is required. Need to be field name or field key.

- `$id` is optional, default is `false` and can be (string) "option" or (string|int) {post_id}. On `false`, the method will first look in 'option' and then look at the current post.

- `$format` is optional and boolean. Default is `true`.

- `$escape` is optional and boolean. Default is `false`.

- [More informations here](https://www.advancedcustomfields.com/resources/get_field/)


## str_replace your values returned by `scg::field()`
```
// functions.php
StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email')]);
```

> [!WARNING]
> When you replace your value by an ACF field like our example, you need to place your code in the `acf/init` hook.


## Play with `new WP_Query()`
```
scg::cpt($post_type = 'post', $args = []);

// OR

StudioChampGauche\Utils\CustomPostType::get($post_type = 'post', $args = []);
```

- `$post_type` is required. Default is `post`. You can pass it to `null` for 2 reasons:
    
    - If you want play with the `post_type` parameter directly in `$args`.
    
    - If you want to respect the original behavior of WordPress. e.g. WordPress will give you the default post_type `any` instead of `post` if you play with tax_query`.

- `$args` are defined by WordPress [here](https://developer.wordpress.org/reference/classes/wp_query/)