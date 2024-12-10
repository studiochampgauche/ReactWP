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
				$array[$key] = is_numeric($value) && !is_string($value) ? +str_replace($search, $replace, $value) : str_replace($search, $replace, $value);
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