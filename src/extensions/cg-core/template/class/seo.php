<?php
    
namespace StudioChampGauche\Seo;

class Seo{
	
    function __construct(){
		
		add_filter('wp_robots', function($robots) {
			
			$obj = get_queried_object();
			
			$robots['noindex'] = false;
			$robots['nofollow'] = false;
			$robots['index'] = true;
			$robots['follow'] = true;
			
			if (
				is_404()
				
				||
				
				(is_author() && \StudioChampGauche\Utils\Field::get('search_engine_stop_indexing', 'user_' . $obj->ID))
				
				||
				
				((is_category() || is_tag()) && \StudioChampGauche\Utils\Field::get('search_engine_stop_indexing', 'term_' . $obj->term_id))
				
				||
				
				($obj && $obj->ID && \StudioChampGauche\Utils\Field::get('search_engine_stop_indexing', $obj->ID))
			) {
				
				$robots['noindex'] = true;
				$robots['nofollow'] = true;
				$robots['index'] = false;
				$robots['follow'] = false;
				
			} elseif (is_search()) {
				
				$robots['noindex'] = true;
				$robots['nofollow'] = false;
				$robots['index'] = false;
				$robots['follow'] = true;
				
			}
			
			$robots['max-image-preview'] = 'large';
			
			return $robots;
			
		});
		
		add_filter('scg_wp_head', function($wp_heads){
			
			$obj = get_queried_object();
			
			$wp_heads['title'] = '<title>'. self::title() .'</title>';
			$wp_heads['charset'] = '<meta charset="'. get_bloginfo('charset') .'">';
			$wp_heads['compatible'] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
			$wp_heads['viewport'] = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
			
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
				
				$wp_heads['og_article_expiration_time'] = ''; //'<meta property="article:expiration_time" content="" />';
				
				$wp_heads['og_article_author'] = '<meta property="article:author" content="'. get_author_posts_url($obj->post_author) .'" />';
				
				$wp_heads['og_article_section'] = ''; //'<meta property="article:section" content="" />';
				
				$wp_heads['og_article_tag'] = ''; //'<meta property="article:tag" content="" />';
				
			}
			
			$wp_heads['og_url'] = '<meta property="og:url" content="'. self::og_url() .'" />';
			
			if(self::og_site_name())
				$wp_heads['og_site_name'] = '<meta property="og:site_name" content="'. self::og_site_name() .'" />';
			
			if(self::og_title())
				$wp_heads['og_title'] = '<meta property="og:title" content="'. self::og_title() .'" />';
			
			if(self::og_description())
				$wp_heads['og_description'] = '<meta property="og:description" content="'. self::og_description() .'" />';
			
			if(self::og_image())
				$wp_heads['og_image'] = '<meta property="og:image" content="'. self::og_image() .'" />';
				
			
			return $wp_heads;
			
		});
        
    }
	
	public static function site_name(){
		
		$siteName = \StudioChampGauche\Utils\Field::get('search_engine_site_name');
		
		return $siteName ? $siteName : get_bloginfo('name');
		
	}
	
	public static function title(){
		
		$obj = get_queried_object();
		
		if(is_search())
			return __('Résultat(s) de recherche pour', 'cg-core-plugin') . ' "' . $_GET['s'] . '"' . ' - ' . self::site_name();
		
		elseif(is_404())
			return __('Erreur 404', 'cg-core-plugin') . ' - ' . self::site_name();
		
		elseif(is_author())
			return (\StudioChampGauche\Utils\Field::get('search_engine_title', 'user_' . $obj->ID) ? \StudioChampGauche\Utils\Field::get('search_engine_title', 'user_' . $obj->ID) : __('Publications de', 'cg-core-plugin') . ' ' . $obj->display_name . ' - ' . self::site_name());
			
		elseif(is_category() || is_tag())
			return (\StudioChampGauche\Utils\Field::get('search_engine_title', 'term_' . $obj->term_id) ? \StudioChampGauche\Utils\Field::get('search_engine_title', 'term_' . $obj->term_id)  : $obj->name . ' - ' . self::site_name());
			
		elseif($obj && $obj->ID && \StudioChampGauche\Utils\Field::get('search_engine_title', $obj->ID))
			return \StudioChampGauche\Utils\Field::get('search_engine_title', $obj->ID);
		
		elseif($obj && $obj->ID)
			return get_the_title($obj->ID) . ' - ' . self::site_name();
		
		
		return null;
		
	}
	
	public static function description(){
		
		$obj = get_queried_object();
		
		if(is_author() && \StudioChampGauche\Utils\Field::get('search_engine_description', 'user_' . $obj->ID))
			return \StudioChampGauche\Utils\Field::get('search_engine_description', 'user_' . $obj->ID);
		
		elseif((is_category() || is_tag()) && \StudioChampGauche\Utils\Field::get('search_engine_description', 'term_' . $obj->term_id))
			return \StudioChampGauche\Utils\Field::get('search_engine_description', 'term_' . $obj->term_id);
		
		elseif($obj && $obj->ID && \StudioChampGauche\Utils\Field::get('search_engine_description', $obj->ID))
			return \StudioChampGauche\Utils\Field::get('search_engine_description', $obj->ID);
		
		
			
		return \StudioChampGauche\Utils\Field::get('search_engine_description');
		
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
		
		
		return null;
		
	}
	
	
	
	public static function og_description(){
		
		
		return null;
		
	}
	
	public static function og_image(){
		
		
		return null;
		
	}
	
	public static function og_url(){
		
		global $wp;
		
		return home_url(add_query_arg((isset($_GET) ? $_GET : []), $wp->request));
		
	}
}
new Seo();

?>