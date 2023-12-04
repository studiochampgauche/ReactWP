<?php
    
namespace StudioChampGauche\Seo;

class Seo{
    
    function __construct(){
        
		\StudioChampGauche\Render\Render::addToHeadTag('<meta charset="'. get_bloginfo('charset') .'">');
		\StudioChampGauche\Render\Render::addToHeadTag('<meta http-equiv="X-UA-Compatible" content="IE=edge">');
		\StudioChampGauche\Render\Render::addToHeadTag('<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">');
        
    }
    
}

new Seo();

?>