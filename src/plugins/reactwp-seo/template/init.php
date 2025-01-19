<?php
/*
* Plugin Name: ReactWP SEO
* Description: Handle your SEO
* Update URI: false
* Version: 1.0.0
*/


namespace ReactWP\Seo;

require_once 'inc/render.php';

class Seo{
    
    function __construct(){
        
        add_filter('wp_robots', function($robots) {
            
            $obj = get_queried_object();
            
            $robots['noindex'] = false;
            $robots['nofollow'] = false;
            $robots['index'] = true;
            $robots['follow'] = true;
            
            if(
                is_404()
                
                ||
                
                (is_author() && \ReactWP\Utils\Field::get('seo_do_not_index', 'user_' . $obj->ID))
                
                ||
                
                ((is_category() || is_tag() || is_tax()) && \ReactWP\Utils\Field::get('seo_do_not_index', 'term_' . $obj->term_id))
                
                ||
                
                ($obj && $obj->ID && \ReactWP\Utils\Field::get('seo_do_not_index', $obj->ID))

                ||

                (!get_option('blog_public') && !is_search())
            ) {
                
                $robots['noindex'] = true;
                $robots['nofollow'] = true;
                $robots['index'] = false;
                $robots['follow'] = false;
                
            } elseif(is_search()) {
                
                $robots['noindex'] = true;
                $robots['nofollow'] = false;
                $robots['index'] = false;
                $robots['follow'] = true;
                
            }
            
            $robots['max-image-preview'] = 'large';
            
            return $robots;
            
        });
        
        add_filter('rwp_wp_head', function($wp_heads){
            
            $obj = get_queried_object();
            
            
            $wp_heads['charset'] = '<meta charset="'. get_bloginfo('charset') .'">';
            $wp_heads['compatible'] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
            $wp_heads['viewport'] = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
            $wp_heads['title'] = '<title>'. self::title() .'</title>';


            if(self::description())
                $wp_heads['description'] = '<meta name="description" content="'. self::description() .'">';
            
            
            $wp_heads['og_type'] = '<meta property="og:type" content="'. self::og_type() .'" />';
            
            if(self::og_type() === 'profile'){
                
                if($obj->user_firstname)
                    $wp_heads['og_profile_first_name'] = '<meta property="profile:first_name" content="'. $obj->user_firstname .'" />';
                
                if($obj->user_lastname)
                    $wp_heads['og_profile_last_name'] = '<meta property="profile:last_name" content="'. $obj->user_lastname .'" />';
                
                if($obj->user_nicename)
                    $wp_heads['og_profile_username'] = '<meta property="profile:username" content="'. $obj->user_nicename .'" />';

                
            } elseif(self::og_type() === 'article'){
                
                $wp_heads['og_article_published_time'] = '<meta property="article:published_time" content="'. $obj->post_date_gmt .'" />';
                
                $wp_heads['og_article_modified_time'] = '<meta property="article:modified_time" content="'. $obj->post_modified_gmt .'" />';
                
                $wp_heads['og_article_author'] = '<meta property="article:author" content="'. get_author_posts_url($obj->post_author) .'" />';
                
            }

            
            if(self::og_url())
                $wp_heads['og_url'] = '<meta property="og:url" content="'. self::og_url() .'" />';
            
            if(self::og_site_name())
                $wp_heads['og_site_name'] = '<meta property="og:site_name" content="'. self::og_site_name() .'" />';
            
            if(self::og_title())
                $wp_heads['og_title'] = '<meta property="og:title" content="'. self::og_title() .'" />';
            
            if(self::og_description())
                $wp_heads['og_description'] = '<meta property="og:description" content="'. self::og_description() .'" />';
            
            if(self::og_image())
                $wp_heads['og_image'] = '<meta property="og:image" content="'. self::og_image() .'" />';
            
            
            $fav = \ReactWP\Utils\Field::get('seo_favicon');
            
            if($fav)
                $wp_heads['favicon'] = '<link rel="icon" sizes="192x192" href="'. $fav .'">';
                
            
            return $wp_heads;
            
        });
        
    }
    
    public static function site_name(){
        
        return get_bloginfo('name');
        
    }
    
    public static function title(){
        
        $obj = get_queried_object();
        
        if(is_search())
            return (CL === 'fr' ? 'Résultat(s) de recherche pour' : 'Search result(s) for') . ' \'' . $_GET['s'] . '\'' . ' - ' . self::site_name();
        
        elseif(is_404())
            return (CL === 'fr' ? 'Erreur 404' : '404 Error') . ' - ' . self::site_name();
        
        elseif(is_author())
            return (\ReactWP\Utils\Field::get('seo_title_' . CL, 'user_' . $obj->ID) ? \ReactWP\Utils\Field::get('seo_title_' . CL, 'user_' . $obj->ID) : (CL === 'fr' ? 'Publication(s) de' : 'Post(s) of') . ' ' . $obj->display_name . ' - ' . self::site_name());
            
        elseif(is_category() || is_tag() || is_tax())
            return (\ReactWP\Utils\Field::get('seo_title_' . CL, 'term_' . $obj->term_id) ? \ReactWP\Utils\Field::get('seo_title_' . CL, 'term_' . $obj->term_id)  : $obj->name . ' - ' . self::site_name());
            
        elseif($obj && $obj->ID && \ReactWP\Utils\Field::get('seo_title_' . CL, $obj->ID))
            return \ReactWP\Utils\Field::get('seo_title_' . CL, $obj->ID);
        
        elseif($obj && $obj->ID)
            return get_the_title($obj->ID) . ' - ' . self::site_name();
        
        
        return null;
        
    }
    
    public static function description(){
        
        $obj = get_queried_object();
        
        if(is_author() && \ReactWP\Utils\Field::get('seo_description_' . CL, 'user_' . $obj->ID))
            return \ReactWP\Utils\Field::get('seo_description_' . CL, 'user_' . $obj->ID);
        
        elseif((is_category() || is_tag() || is_tax()) && \ReactWP\Utils\Field::get('seo_description_' . CL, 'term_' . $obj->term_id))
            return \ReactWP\Utils\Field::get('seo_description_' . CL, 'term_' . $obj->term_id);
        
        elseif($obj && $obj->ID && \ReactWP\Utils\Field::get('seo_description_' . CL, $obj->ID))
            return \ReactWP\Utils\Field::get('seo_description_' . CL, $obj->ID);
        
        
            
        return \ReactWP\Utils\Field::get('seo_description_' . CL);
        
    }
    
    public static function og_type(){
        
        if(is_singular(['post']))
            return 'article';
        elseif(is_author())
            return 'profile';
        
        return 'website';
        
    }
    
    public static function og_site_name(){
        
        return self::site_name();
        
    }
    
    public static function og_title(){
        
        $obj = get_queried_object();
        
        
        if(is_author())
            return (\ReactWP\Utils\Field::get('seo_og_title_' . CL, 'user_' . $obj->ID) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL, 'user_' . $obj->ID) : (\ReactWP\Utils\Field::get('seo_og_title_' . CL) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL) : __('Publications de', 'cg-core-plugin') . ' ' . $obj->display_name . ' - ' . self::site_name()));
            
        elseif(is_category() || is_tag() || is_tax())
            return (\ReactWP\Utils\Field::get('seo_og_title_' . CL, 'term_' . $obj->term_id) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL, 'term_' . $obj->term_id)  : (\ReactWP\Utils\Field::get('seo_og_title_' . CL) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL) : $obj->name . ' - ' . self::site_name()));
            
        elseif($obj && $obj->ID)
            return (\ReactWP\Utils\Field::get('seo_og_title_' . CL, $obj->ID) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL, $obj->ID) : (\ReactWP\Utils\Field::get('seo_og_title_' . CL) ? \ReactWP\Utils\Field::get('seo_og_title_' . CL) : self::title()));
    
        
        
        return self::title();
        
    }
    
    
    
    public static function og_description(){
        
        
        $obj = get_queried_object();
        
        if(is_author() && \ReactWP\Utils\Field::get('seo_og_description_' . CL, 'user_' . $obj->ID))
            return \ReactWP\Utils\Field::get('seo_og_description_' . CL, 'user_' . $obj->ID);
        
        elseif((is_category() || is_tag() || is_tax()) && \ReactWP\Utils\Field::get('seo_og_description_' . CL, 'term_' . $obj->term_id))
            return \ReactWP\Utils\Field::get('seo_og_description_' . CL, 'term_' . $obj->term_id);
        
        elseif($obj && $obj->ID && \ReactWP\Utils\Field::get('seo_og_description_' . CL, $obj->ID))
            return \ReactWP\Utils\Field::get('seo_og_description_' . CL, $obj->ID);
        
        
            
        return \ReactWP\Utils\Field::get('seo_og_description_' . CL) ? \ReactWP\Utils\Field::get('seo_og_description_' . CL) : self::description();
        
    }
    
    public static function og_image(){
        
        
        $obj = get_queried_object();
        
        if(is_author() && \ReactWP\Utils\Field::get('seo_og_image', 'user_' . $obj->ID))
            return \ReactWP\Utils\Field::get('seo_og_image', 'user_' . $obj->ID);
        
        elseif((is_category() || is_tag() || is_tax()) && \ReactWP\Utils\Field::get('seo_og_image', 'term_' . $obj->term_id))
            return \ReactWP\Utils\Field::get('seo_og_image', 'term_' . $obj->term_id);
        
        elseif($obj && $obj->ID && \ReactWP\Utils\Field::get('seo_og_image', $obj->ID))
            return \ReactWP\Utils\Field::get('seo_og_image', $obj->ID);
        

            
        return \ReactWP\Utils\Field::get('seo_og_image');
        
    }
    
    public static function og_url(){
        
        global $wp;
        
        return home_url(add_query_arg((isset($_GET) ? $_GET : []), $wp->request));
        
    }
}
new Seo();