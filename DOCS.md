# Utils PHP

## Use `get_field()` function of ACF differently.
```
scg::field($field, $id = false, $format = true, $escape = false);

/*
* OR
*/

StudioChampGauche\Utils\Field::get($field, $id = false, $format = true, $escape = false);
```

- `$field` is required. Need to be field name or field key.

- `$id` is optional, default is `false` and can be (string) "option" or (string|int) {post_id}. On `false`, the method will first look in 'option' and then look at the current post.

- `$format` is optional and boolean. Default is `true`.

- `$escape` is optional and boolean. Default is `false`.

- [More informations here](https://www.advancedcustomfields.com/resources/get_field/)


### Replace values returned by `scg::field()`

> [!WARNING]
> When you replace your value by an ACF field like our example just below, you need to place your code in the `acf/init` hook.

```
StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email')]);
```



## Play with `new WP_Query()`
```
scg::cpt($post_type = 'post', $args = []);

/*
* OR
*/

StudioChampGauche\Utils\CustomPostType::get($post_type = 'post', $args = []);
```

- `$post_type` is optional. Default is `post`. You can pass it to `null` for 2 reasons:
    
    - If you want play with the `post_type` parameter directly in `$args`.
    
    - If you want to respect the original behavior of WordPress. e.g. WordPress will give you the default post_type `any` instead of `post` if you play with tax_query`.

- `$args` are defined by WordPress [here](https://developer.wordpress.org/reference/classes/wp_query/)

### Set default arguments
```
StudioChampGauche\Utils\CustomPostType::default($key, $value);
```



## Call a menu
```
scg::menu($theme_location = null, $args = []);

/*
* OR
*/

StudioChampGauche\Utils\Menu::get($theme_location = null, $args = []);
```

- `$theme_location` is optional. Default is `null`.

- `$args` are based by WordPress [here](https://developer.wordpress.org/reference/functions/wp_nav_menu/)

- In `$args`, you can add the `mobile_bars` parameter with an integer value for add a hamburger Menu after your Items Wrapper.

### Set default arguments
```
StudioChampGauche\Utils\Menu::default($key, $value);
```



## Create an Hyperlink or a Button
```
scg::button($text = null, $args = []);

/*
* OR
*/

StudioChampGauche\Utils\Button::get($text = null, $args = []);
```

- `$text` is optional. Default is `null`.

- `$args` parameters can be:
    - ***text***
    
      `text` will add a span element with your text. Will work only if `$text` is `null`.
    
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
      
### Set default arguments
```
StudioChampGauche\Utils\Button::default($key, $value);
```



## Get Path or URL
```
scg::source($args = []);

/*
* OR
*/

StudioChampGauche\Utils\Source::get($args = []);
```

- `$args` parameters can be:
    - ***base***
    
      `base` default is `/`, equal to theme root.
    
    - ***path***
    
      `path` to your source. Default is `null`.
    
    - ***url***
    
      `url` is bool, default is `false`. Do you want the URL or the Path?


### Set default arguments
```
StudioChampGauche\Utils\Source::default($key, $value);
```


# SEO

## Increase your SEO
```
add_filter('scg_wp_head', function($wp_heads){
				
	/*
	* Add Open Graph article:section and article:tag on Post Type 'post'
	*/
	if(is_singular(['post'])){

		$wp_heads['og_article_section'] = '<meta property="article:section" content="" />';

		$wp_heads['og_article_tag'] = '<meta property="article:tag" content="" />';

	}

	return $wp_heads;

});
```

## Get
```
// Current Site Name
\StudioChampGauche\Seo\Seo::site_name()

// Current Title
\StudioChampGauche\Seo\Seo::title()

// Current Description
\StudioChampGauche\Seo\Seo::description()

// Current OG Type
\StudioChampGauche\Seo\Seo::og_type()

// Current OG Site Name
\StudioChampGauche\Seo\Seo::og_site_name()

// Current OG Title
\StudioChampGauche\Seo\Seo::og_title()

// Current OG Description
\StudioChampGauche\Seo\Seo::og_description()

// Current OG Sharing Image URL
\StudioChampGauche\Seo\Seo::og_image()
```