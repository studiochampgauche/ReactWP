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
?>