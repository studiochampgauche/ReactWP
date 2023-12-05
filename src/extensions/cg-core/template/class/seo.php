<?php
    
namespace StudioChampGauche\Seo;

class Seo{
	
    function __construct(){
		
		add_filter('scg_wp_head', function($wp_heads){
			
			$wp_heads['title'] = '<title></title>';
			$wp_heads['charset'] = '<meta charset="'. get_bloginfo('charset') .'">';
			$wp_heads['compatible'] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
			$wp_heads['viewport'] = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
			
			$wp_heads['og_site_name'] = '<meta property="og:site_name" content="'. self::site_name() .'" />';
			
			$wp_heads['og_title'] = '<meta property="og:title" content="" />';
			$wp_heads['og_type'] = '<meta property="og:type" content="'. self::type() .'" />';
			$wp_heads['og_image'] = '<meta property="og:image" content="" />';
			$wp_heads['og_url'] = '<meta property="og:url" content="" />';
			
			return $wp_heads;
			
		});
        
    }
	
	public static function site_name(){
		
		return \StudioChampGauche\Utils\Field::get('site_name');
		
	}
	
	public static function type(){
		
		if(is_singular(['post', 'product']))
			return 'article';
		
		return 'website';
		
	}
	
	public static function title(){
		
		
		return null;
		
	}
	
	public static function description(){
		
		
		return null;
		
	}
}
new Seo();

?>