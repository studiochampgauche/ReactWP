<?php
    
namespace StudioChampGauche\Render;

class Render{
    
    public static $wp_heads = [];
    
    function __construct(){
		
		add_action('wp_head', function(){
			
			if(self::$wp_heads)
            	echo implode('', self::$wp_heads);
			
			
        }, 2);
        
    }
    
    
    public static function headTag($key, $value){

        self::$wp_heads[$key] = $value;

    }
    
}

new Render();

?>