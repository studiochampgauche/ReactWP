<?php
    
namespace StudioChampGauche\Render;

class Render{
    
    public static $wp_heads = [];
    
    function __construct(){
		
		add_action('wp_head', [$this, 'wp_head'], 2);
        
    }
    
    
    public function wp_head(){

        

    }
    
}

new Render();

?>