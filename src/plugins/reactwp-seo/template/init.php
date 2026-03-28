<?php
/*
* Plugin Name: ReactWP SEO
* Description: Handle your SEO
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
* Update URI: false
* Version: 1.0.1
*/


namespace ReactWP\Seo;

require_once 'inc/render.php';

class Seo{
    
    function __construct(){
        
        add_filter('wp_robots', function($robots){

            if(is_search()){
                $robots['noindex'] = true;
                $robots['follow'] = true;
                $robots['index'] = false;
                $robots['nofollow'] = false;
                $robots['max-image-preview'] = 'large';

                return $robots;
            }

            if(self::should_noindex()){
                $robots['noindex'] = true;
                $robots['nofollow'] = true;
                $robots['index'] = false;
                $robots['follow'] = false;
            } else {
                $robots['noindex'] = false;
                $robots['nofollow'] = false;
                $robots['index'] = true;
                $robots['follow'] = true;
            }

            $robots['max-image-preview'] = 'large';

            return $robots;

        });
        
        add_filter('rwp_wp_head', function($wp_heads){

            $obj = self::queried_object();

            $title = self::title();
            $description = self::description();
            $og_type = self::og_type();
            $og_url = self::og_url();
            $og_site_name = self::og_site_name();
            $og_title = self::og_title();
            $og_description = self::og_description();
            $og_image = self::og_image();
            $favicon = self::image_url(\ReactWP\Utils\Field::get('seo_favicon', 'option'));

            $charset = get_bloginfo('charset');

            if(self::has_value($charset)){
                $wp_heads['charset'] = '<meta charset="' . \rwp::escape('attr', $charset) . '">';
            }

            $wp_heads['compatible'] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
            $wp_heads['viewport'] = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';

            if(self::has_value($title)){
                $wp_heads['title'] = '<title>' . \rwp::escape('html', $title) . '</title>';
            }

            if(self::has_value($description)){
                $wp_heads['description'] = '<meta name="description" content="' . \rwp::escape('attr', $description) . '">';
            }

            if(self::has_value($og_type)){
                $wp_heads['og_type'] = '<meta property="og:type" content="' . \rwp::escape('attr', $og_type) . '" />';
            }

            if($og_type === 'profile' && $obj instanceof \WP_User){

                if(self::has_value($obj->user_firstname ?? null)){
                    $wp_heads['og_profile_first_name'] = '<meta property="profile:first_name" content="' . \rwp::escape('attr', $obj->user_firstname) . '" />';
                }

                if(self::has_value($obj->user_lastname ?? null)){
                    $wp_heads['og_profile_last_name'] = '<meta property="profile:last_name" content="' . \rwp::escape('attr', $obj->user_lastname) . '" />';
                }

                if(self::has_value($obj->user_nicename ?? null)){
                    $wp_heads['og_profile_username'] = '<meta property="profile:username" content="' . \rwp::escape('attr', $obj->user_nicename) . '" />';
                }

            } elseif($og_type === 'article' && $obj instanceof \WP_Post){

                $published_time = get_post_time(DATE_W3C, true, $obj->ID);
                $modified_time = get_post_modified_time(DATE_W3C, true, $obj->ID);
                $author_url = get_author_posts_url($obj->post_author);

                if(self::has_value($published_time)){
                    $wp_heads['og_article_published_time'] = '<meta property="article:published_time" content="' . \rwp::escape('attr', $published_time) . '" />';
                }

                if(self::has_value($modified_time)){
                    $wp_heads['og_article_modified_time'] = '<meta property="article:modified_time" content="' . \rwp::escape('attr', $modified_time) . '" />';
                }

                if(self::has_value($author_url)){
                    $wp_heads['og_article_author'] = '<meta property="article:author" content="' . \rwp::escape('url', $author_url) . '" />';
                }

            }

            if(self::has_value($og_url)){
                $wp_heads['og_url'] = '<meta property="og:url" content="' . \rwp::escape('url', $og_url) . '" />';
            }

            if(self::has_value($og_site_name)){
                $wp_heads['og_site_name'] = '<meta property="og:site_name" content="' . \rwp::escape('attr', $og_site_name) . '" />';
            }

            if(self::has_value($og_title)){
                $wp_heads['og_title'] = '<meta property="og:title" content="' . \rwp::escape('attr', $og_title) . '" />';
            }

            if(self::has_value($og_description)){
                $wp_heads['og_description'] = '<meta property="og:description" content="' . \rwp::escape('attr', $og_description) . '" />';
            }

            if(self::has_value($og_image)){
                $wp_heads['og_image'] = '<meta property="og:image" content="' . \rwp::escape('url', $og_image) . '" />';
            }

            if(self::has_value($favicon)){
                $wp_heads['favicon'] = '<link rel="icon" sizes="192x192" href="' . \rwp::escape('url', $favicon) . '">';
            }

            return $wp_heads;

        });
        
    }

    public static function site_name(){

        return get_bloginfo('name');

    }

    public static function title(){

        $obj = self::queried_object();
        $search_query = self::search_query();

        if(is_search()){
            return self::has_value($search_query)
                ? (CL === 'fr' ? 'Résultat(s) de recherche pour' : 'Search result(s) for') . ' "' . $search_query . '" - ' . self::site_name()
                : self::site_name();
        }

        if(is_404()){
            return (CL === 'fr' ? 'Erreur 404' : '404 Error') . ' - ' . self::site_name();
        }

        $context_title = self::context_value('seo_title_' . CL);

        if(self::has_value($context_title)){
            return $context_title;
        }

        if(is_author() && $obj instanceof \WP_User){
            return (CL === 'fr' ? 'Publication(s) de' : 'Post(s) of') . ' ' . $obj->display_name . ' - ' . self::site_name();
        }

        if((is_category() || is_tag() || is_tax()) && $obj instanceof \WP_Term){
            return $obj->name . ' - ' . self::site_name();
        }

        if($obj instanceof \WP_Post && !empty($obj->ID)){
            return get_the_title($obj->ID) . ' - ' . self::site_name();
        }

        return self::site_name();

    }

    public static function description(){

        return self::resolve_value('seo_description_' . CL);

    }

    public static function og_type(){

        if(is_singular(['post'])){
            return 'article';
        }

        if(is_author()){
            return 'profile';
        }

        return 'website';

    }

    public static function og_site_name(){

        return self::site_name();

    }

    public static function og_title(){

        return self::resolve_value('seo_og_title_' . CL, self::title());

    }

    public static function og_description(){

        return self::resolve_value('seo_og_description_' . CL, self::description());

    }

    public static function og_image(){

        return self::image_url(self::resolve_value('seo_og_image'));

    }

    public static function og_url(){

        $obj = self::queried_object();

        if(is_search()){
            $search_query = self::search_query();

            return self::has_value($search_query)
                ? get_search_link($search_query)
                : home_url('/');
        }

        if(is_author() && $obj instanceof \WP_User && !empty($obj->ID)){
            return get_author_posts_url($obj->ID);
        }

        if((is_category() || is_tag() || is_tax()) && $obj instanceof \WP_Term){
            $term_url = get_term_link($obj);

            return !is_wp_error($term_url) ? $term_url : home_url('/');
        }

        if($obj instanceof \WP_Post && !empty($obj->ID)){
            return get_permalink($obj->ID);
        }

        global $wp;

        return home_url('/' . ltrim((string)($wp->request ?? ''), '/'));

    }

    private static function queried_object(){

        return get_queried_object();

    }

    private static function has_value($value){

        if(!$value){
            return false;
        }

        return true;

    }

    private static function search_query(){

        return isset($_GET['s'])
            ? sanitize_text_field(wp_unslash($_GET['s']))
            : '';

    }

    private static function context_value($field){

        $obj = self::queried_object();

        if(is_author() && $obj instanceof \WP_User && !empty($obj->ID)){
            return \ReactWP\Utils\Field::get($field, 'user_' . $obj->ID);
        }

        if((is_category() || is_tag() || is_tax()) && $obj instanceof \WP_Term && !empty($obj->term_id)){
            return \ReactWP\Utils\Field::get($field, 'term_' . $obj->term_id);
        }

        if($obj instanceof \WP_Post && !empty($obj->ID)){
            return \ReactWP\Utils\Field::get($field, $obj->ID);
        }

        return null;

    }

    private static function option_value($field){

        return \ReactWP\Utils\Field::get($field, 'option');

    }

    private static function resolve_value($field, $fallback = null){

        $context_value = self::context_value($field);

        if(self::has_value($context_value)){
            return $context_value;
        }

        $option_value = self::option_value($field);

        if(self::has_value($option_value)){
            return $option_value;
        }

        return $fallback;

    }

    private static function image_url($value){

        if(!self::has_value($value)){
            return null;
        }

        if(is_array($value)){
            return $value['url'] ?? null;
        }

        if(is_numeric($value)){
            $image_url = wp_get_attachment_image_url((int)$value);

            return $image_url ?: null;
        }

        if(is_string($value)){
            return $value;
        }

        return null;

    }

    private static function should_noindex(){

        $obj = get_queried_object();

        if(is_404()){
            return true;
        }

        if(!get_option('blog_public') && !is_search()){
            return true;
        }

        if(is_author() && $obj && !empty($obj->ID)){
            return (bool)\ReactWP\Utils\Field::get('seo_do_not_index', 'user_' . $obj->ID);
        }

        if((is_category() || is_tag() || is_tax()) && $obj && !empty($obj->term_id)){
            return (bool)\ReactWP\Utils\Field::get('seo_do_not_index', 'term_' . $obj->term_id);
        }

        if($obj && !empty($obj->ID)){
            return (bool)\ReactWP\Utils\Field::get('seo_do_not_index', $obj->ID);
        }

        return false;

    }
}
new Seo();