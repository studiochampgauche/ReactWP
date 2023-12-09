<?php
    
namespace StudioChampGauche\Render;

class Render{
    
    public static $wp_heads = [];
    
    function __construct(){
		
		add_action('wp_head', [$this, 'wp_head'], 2);
        
    }
    
    
    public function wp_head(){

        self::$wp_heads = apply_filters('scg_wp_head', self::$wp_heads);
        echo implode('', self::$wp_heads);

    }
    
}

new Render();

?>