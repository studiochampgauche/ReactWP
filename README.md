# Our WordPress Boilerplate
Build immersive 2D or 3D WordPress websites Awwwards in "no time" with our Boilerplate.

![minimum-php](https://img.shields.io/badge/Minimum%20PHP-8.2-ff0000.svg)
![multisite-ready](https://img.shields.io/badge/Multisite%20Ready-no-fcba03.svg)
![en-ready](https://img.shields.io/badge/English%20Ready-yes-44cc11.svg)
![fr-ready](https://img.shields.io/badge/French%20Ready-yes-44cc11.svg)

## Guide
1. Put the WordPress Production Files on root
2. Install your Node Modules in `src > built`
3. In `src` directory, duplicate `wp-config-sample.php` to `wp-config.php` and setup it
4. If is the first setup for your project, run `gulp prod-watch` or `gulp prod` in `src > built`. If not, continue watching by only use `gulp`.
5. Start working


## Ready Libraries to import
- Pixi
- Three
- Barba
- Granim
- GSAP


# Core Plugin

![required-yes](https://img.shields.io/badge/Required-yes-ff0000.svg)

We have build a plugin that help us to handle repetitive needs in each project. [Check it out here!](https://wpboilerplate.champgauche.studio)


> [!IMPORTANT]  
> You'll need ACF Pro to play with our Core Plugin. [Get it here](https://www.advancedcustomfields.com/pro).

# Admin Languages

***Default:*** French

***Translation:*** en_US, en_CA, en_GB, en_AU, en_NZ, en_ZA


# PHP Utilities

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


### Call Hyperlink or Button
Use `scg::button($text, $args)` or `StudioChampGauche\Utils\Button::get($text, $args)`.

- Default `$text` is `null`.

- You can conserve `$text` to `null` and shot your `text` parameter in `$args`.

- `$args` parameters can be:
    - text
    `text` will place a span element with your text inner your link or button element
    
    - href
    
    - class
    
    - attr
    
    - before
    
    - after