<?php
    
    namespace StudioChampGauche\Utils;

    class Field{
        
        public static $elementsToReplace = [];
        
        public static function get($field = null, $id = null){
            
            if(!is_array(self::$elementsToReplace)) return;
            
            
            $return = ($field && $id ? get_field($field, $id) : ($field ? (!empty(get_field($field, 'option')) ? get_field($field, 'option') : get_field($field)) : null));
            
            
            return $return && self::$elementsToReplace ? str_replace(self::$elementsToReplace[0], self::$elementsToReplace[1], $return) : $return;
            
        }
        
        public static function replace($elementToReplace, $replacedElement){
            
            self::$elementsToReplace = [
                $elementToReplace,
                $replacedElement
            ];
            
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

?>