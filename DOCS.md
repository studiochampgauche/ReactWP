# Utils PHP

### Use `get_field()` function of ACF differently.
```
scg::field($field, $id = false, $format = true, $escape = false);

// OR

StudioChampGauche\Utils\Field::get($field, $id = false, $format = true, $escape = false);
```

- `$field` is required. Need to be field name or field key.

- `$id` is optional, default is `false` and can be (string) "option" or (string|int) {post_id}. On `false`, the method will first look in 'option' and then look at the current post.

- `$format` is optional and boolean. Default is `true`.

- `$escape` is optional and boolean. Default is `false`.

[More informations here](https://www.advancedcustomfields.com/resources/get_field/)