<?php
    
namespace StudioChampGauche\Utils;

class Field{

    public static $elementsToReplace = [];

    public static function get($field = null, $id = null){

        if(!is_array(self::$elementsToReplace)) return;


        $return = ($field && $id ? get_field($field, $id) : ($field ? (!empty(get_field($field, 'option')) ? get_field($field, 'option') : get_field($field)) : null));

		
		if($return && is_array($return) && self::$elementsToReplace)
			self::recursive(self::$elementsToReplace[0], self::$elementsToReplace[1], $return);
		elseif($return && is_string($return) && self::$elementsToReplace)
			$return = str_replace(self::$elementsToReplace[0], self::$elementsToReplace[1], $return);
		
        return $return;

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
				$array[$key] = str_replace($search, $replace, $value);
			}
		}
	}

}

class CustomPostType{

    public static $configs = [];

    public static function get($post_type = 'post', $args = []){

        if(!is_array(self::$configs)) return;

        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }

        self::$configs['post_type'] = $post_type;


        return new \WP_Query(self::$configs);

    }

    public static function default($parameter, $value){

        self::$configs[$parameter] = $value;

    }

}

class Menu{

    public static $configs = [];

    public static function get($theme_location = null, $args = []){

        if(!is_array(self::$configs)) return;

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

        self::$configs['theme_location'] = $theme_location;


        return wp_nav_menu(self::$configs);

    }

    public static function default($parameter, $value){

        self::$configs[$parameter] = $value;

    }

}

class Button{

    public static $configs = [
        'text' => null,
        'href' => null,
        'class' => null,
        'attr' => null,
        'before' => null,
        'after' => null
    ];

    public static function get($args = []){

        if(!is_array(self::$configs)) return;


        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }


        return self::$configs['href'] ? '
            <a href="'. self::$configs['href'] .'" class="btn'. (self::$configs['class'] ? ' ' . self::$configs['class'] : null) .'"'. (self::$configs['attr'] ? ' ' . self::$configs['attr'] : null) .'>

            '. self::$configs['before'] . (self::$configs['text'] ? '<span>'. self::$configs['text'] .'</span>' : null) . self::$configs['after'] .'

            </a>
        ' : '
            <button class="btn'. (self::$configs['class'] ? ' ' . self::$configs['class'] : null) .'"'. (self::$configs['attr'] ? ' ' . self::$configs['attr'] : null) .'>

            '. self::$configs['before'] . (self::$configs['text'] ? '<span>'. self::$configs['text'] .'</span>' : null) . self::$configs['after'] .'

            </button>
        ';

    }

    public static function default($parameter, $value){

        self::$configs[$parameter] = $value;

    }

}

class Source{

    public static $configs = [
        'base' => '/',
        'path' => null,
        'url' => false
    ];

    public static function get($args = []){

        if(!is_array(self::$configs)) return;


        if($args && is_array($args)){
            foreach($args as $arg_key => $arg){
                self::$configs[$arg_key] = $arg;
            }
        }


        return self::$configs['url'] ? ((get_template_directory() === get_stylesheet_directory() ? get_template_directory_uri() : get_stylesheet_directory_uri()) . self::$configs['base'] . self::$configs['path']) : ((get_template_directory() === get_stylesheet_directory() ? get_template_directory() : get_stylesheet_directory()) . self::$configs['base'] . self::$configs['path']);

    }

    public static function default($parameter, $value){

        self::$configs[$parameter] = $value;

    }

}

?>