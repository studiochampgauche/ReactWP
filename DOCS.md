# Utils PHP

### Call an ACF Field
Use `scg::field($field_name, $id)` or `StudioChampGauche\Utils\Field::get($field_name, $id)`.

- Without `$field_name`, `null` is returned. Otherwise, the return is given by ACF.
- If you don't give the `$id` parameter, the method will first look in 'option' and then look at the current page or the current looped element.

> [!TIP]  
> You can use `StudioChampGauche\Utils\Field::replace()` for replace some parts returned by `::field` or `::get`. (e.g: `StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email')])`)
>
> Just know, when you use an ACF Field in your replace method like our example, you need to place your code in the `acf/init` hook.

> [!WARNING]  
> `scg::field` use `get_field()`. It can't work for `get_sub_field()`.


### Call the `new WP_Query()`
Use `scg::cpt($post_type, $args)` or `StudioChampGauche\Utils\CustomPostType::get($post_type, $args)`.

- Default `$post_type` is `post`. You can pass it to `null` and shot your `post_type` parameter in `$args`.
- `$args` are based by WordPress [here](https://developer.wordpress.org/reference/classes/wp_query/)

> [!TIP]  
> If you want manage default arguments when you call `new WP_Query` with `::cpt` or `::get`, you can manage it from your functions.php file like this:
> ```
> StudioChampGauche\Utils\CustomPostType::default('posts_per_page', -1);
> StudioChampGauche\Utils\CustomPostType::default('paged', 1);
> ```


### Call a menu
Use `scg::menu($theme_location, $args)` or `StudioChampGauche\Utils\Menu::get($theme_location, $args)`.

- Default `$theme_location` is `null`. If your `$theme_location` is null or if no such location exists or no menu is assigned to it, the parameter fallback_cb will determine what is displayed. [More information here](https://developer.wordpress.org/reference/functions/wp_nav_menu/#more-information).

- You can conserve `$theme_location` to `null` and shot your `theme_location` parameter in `$args`.

- `$args` are based by WordPress [here](https://developer.wordpress.org/reference/functions/wp_nav_menu/)

- In `$args`, you can add the `mobile_bars` parameter with an integer value for add a hamburger Menu after your Items Wrapper.

> [!TIP]  
> If you want manage default arguments when you call a menu with `::menu` or `::get`, you can manage it from your functions.php file like this:
> ```
> StudioChampGauche\Utils\Menu::default('container', null);
> StudioChampGauche\Utils\Menu::default('items_wrap', '<ul>%3$s</ul>');
> ```


### Call a hyperlink or a button
Use `scg::button($text, $args)` or `StudioChampGauche\Utils\Button::get($text, $args)`.

- Default `$text` is `null`.

- You can conserve `$text` to `null` and shot your `text` parameter in `$args`.

- `$args` parameters can be:
    - ***text***
    
      `text` will add a span element with your text.
    
    - ***href***
    
      `href` turn your button element to a link element with href attribute.
    
    - ***class***
    
      `class` add your classes to .btn class.
    
    - ***attr***
    
      `attr` need to be a string. (e.g. 'attr' => 'data-one="" data-two=""'). This will be added to your button element.
    
    - ***before***
    
      `before` add html or something else right after your button opening tag. Before because is before your text span element.
    
    - ***after***
    
      `after` add html or something else right before your button end tag. After because is after your text span element.
      

> [!TIP]  
> If you want manage default arguments when you call a button with `::button` or `::get`, you can manage it from your functions.php file like this:
> ```
> StudioChampGauche\Utils\Button::default('attr', 'x');
> StudioChampGauche\Utils\Button::default('before', 'x');
> ```


### Get Path or URL
Use `scg::source($args)` or `StudioChampGauche\Utils\Source::get($args)`.

- `$args` parameters can be:
    - ***base***
    
      `base` default is `/`, equal to theme root.
    
    - ***path***
    
      `path` to your source. Default is `null`.
    
    - ***url***
    
      `url` is bool, default is `false`. Do you want the URL or the Path?
      

> [!TIP]  
> If you want manage default arguments when you call a source with `::source` or `::get`, you can manage it from your functions.php file like this:
> ```
> StudioChampGauche\Utils\Source::default('url', false);
> ```