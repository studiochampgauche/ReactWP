<?php
    
namespace ReactWP\Utils;

class Field{

    public static $elementsToReplace = [];

    public static function get($field, $id = false, $format = true, $escape = false){

        if(!is_array(self::$elementsToReplace)) return;

		
        return ($id ? get_field($field, $id, $format, $escape) : (!empty(get_field($field, 'option', $format, $escape)) ? get_field($field, 'option', $format, $escape) : get_field($field, $id, $format, $escape)));

    }

    public static function replace($elementToReplace, $replacedElement){

        self::$elementsToReplace = [
            $elementToReplace,
            $replacedElement
        ];

    }
	
	public static function recursive($search, $replace, &$array) {
		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				self::recursive($search, $replace, $array[$key]);
			} else {
				$array[$key] = !is_null($value) && !is_bool($value) ? (is_numeric($value) && !is_string($value) ? +str_replace($search, $replace, $value) : str_replace($search, $replace, $value)) : $value;
			}
		}
	}

}

class CustomPostType{

    public static $defaults = [];
    public static $configs = [];

    public static function get($post_type = 'post', $args = []){

        if(!is_array(self::$configs)) return;
		
		self::$configs = self::$defaults;
		
        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }
        
        if($post_type)
            self::$configs['post_type'] = $post_type;


        return new \WP_Query(self::$configs);

    }

    public static function default($parameter, $value){

        self::$defaults[$parameter] = $value;

    }

}

class Menu{

    public static $defaults = [];
    public static $configs = [];

    public static function get($theme_location = null, $args = []){

        if(!is_array(self::$configs)) return;
        
        self::$configs = self::$defaults;
        
        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }


        if(isset(self::$configs['mobile_bars']) && (int)self::$configs['mobile_bars'] > 0){

            $html = '<div class="ham-menu">';
                $html .= '<div class="inner">';
                for ($i=0; $i < (int)self::$configs['mobile_bars']; $i++) {
                    $html .= '<span></span>';
                }
                $html .= '</div>';
            $html .= '</div>';

            self::$configs['items_wrap'] = self::$configs['items_wrap'] . $html;

        }
        
        if($theme_location)
            self::$configs['theme_location'] = $theme_location;


        return wp_nav_menu(self::$configs);

    }

    public static function default($parameter, $value){

        self::$defaults[$parameter] = $value;

    }

}

class Button{
    
    public static $defaults = [
        'text' => null,
        'href' => null,
        'class' => null,
        'attr' => null,
        'before' => null,
        'after' => null
    ];
    
    
    public static $configs = [];

    public static function get($text = null, $args = []){

        if(!is_array(self::$configs)) return;
        
        self::$configs = self::$defaults;

        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }
        
        if($text)
            self::$configs['text'] = $text;
            
        return self::$configs['href'] ? '
            <a href="'. self::$configs['href'] .'" class="btn'. (self::$configs['class'] ? ' ' . self::$configs['class'] : null) .'"'. (self::$configs['attr'] ? ' ' . self::$configs['attr'] : null) .'>

            '. (self::$configs['before'] ? '<div class="btn-before">'. self::$configs['before'] .'</div>' : null) . (self::$configs['text'] ? '<span>'. self::$configs['text'] .'</span>' : null) . (self::$configs['after'] ? '<div class="btn-after">'. self::$configs['after'] .'</div>' : null) .'

            </a>
        ' : '
            <button class="btn'. (self::$configs['class'] ? ' ' . self::$configs['class'] : null) .'"'. (self::$configs['attr'] ? ' ' . self::$configs['attr'] : null) .'>

            '. (self::$configs['before'] ? '<div class="btn-before">'. self::$configs['before'] .'</div>' : null) . (self::$configs['text'] ? '<span>'. self::$configs['text'] .'</span>' : null) . (self::$configs['after'] ? '<div class="btn-after">'. self::$configs['after'] .'</div>' : null) .'

            </button>
        ';

    }

    public static function default($parameter, $value){

        self::$defaults[$parameter] = $value;

    }

}

class Source{

    public static $defaults = [
        'base' => '/',
        'path' => null,
        'url' => false
    ];
		
	public static $configs = [];

    public static function get($args = []){

        if(!is_array(self::$configs)) return;
		
		self::$configs = self::$defaults;

        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }


        return self::$configs['url'] ? ((get_template_directory() === get_stylesheet_directory() ? get_template_directory_uri() : get_stylesheet_directory_uri()) . self::$configs['base'] . self::$configs['path']) : ((get_template_directory() === get_stylesheet_directory() ? get_template_directory() : get_stylesheet_directory()) . self::$configs['base'] . self::$configs['path']);

    }

    public static function default($parameter, $value){

        self::$defaults[$parameter] = $value;

    }

}

?>