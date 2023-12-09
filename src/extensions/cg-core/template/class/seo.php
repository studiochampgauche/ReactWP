<?php
    
namespace StudioChampGauche\Seo;

class Seo{
	
    function __construct(){
		
		add_filter('scg_wp_head', function($wp_heads){
			
			$wp_heads['title'] = '<title>'. self::title() .'</title>';
			$wp_heads['charset'] = '<meta charset="'. get_bloginfo('charset') .'">';
			$wp_heads['compatible'] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
			$wp_heads['viewport'] = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
			
			if(self::description())
				$wp_heads['description'] = '<meta name="description" content="'. self::description() .'">';
			
			
			$wp_heads['og_type'] = '<meta property="og:type" content="'. self::og_type() .'" />';
			
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
	
	
	
	public static function title(){
		
		if(is_search())
			return __('Résultat(s) de recherche pour', 'cg-core-plugin') . ' "' . $_GET['s'] . '"';
		elseif(is_404())
			return __('Erreur 404', 'cg-core-plugin');
		elseif(is_author()){
			$obj = get_queried_object();
			return __('Publications de', 'cg-core-plugin') . ' ' . $obj->data->display_name;
		}
		
		
		return '';
		
	}
	
	public static function description(){
		
		
		return null;
		
	}
	
	public static function og_type(){
		
		if(is_singular(['post', 'product']))
			return 'article';
		elseif(is_author())
			return 'profile';
		
		return 'website';
		
	}
	
	public static function og_site_name(){
		
		return \StudioChampGauche\Utils\Field::get('site_name');
		
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
		
		
		return null;
		
	}
}
new Seo();

?>