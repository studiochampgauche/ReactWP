<?php
    
namespace ReactWP\Utils;

class Field{

    public static $replacements = [];

    public static function get($field, $id = false, $format = true, $escape = false){

        $value = get_field($field, $id, $format, $escape);

        if(!$format && $value){
            return self::apply_replacement($value);
        }

        return $value;

    }

    public static function replace($search, $replace){

        if(is_array($search)){
            foreach($search as $key => $search_value){
                $replace_value = is_array($replace) && array_key_exists($key, $replace) ? $replace[$key] : '';
                self::add_replacement($search_value, $replace_value);
            }

            return;
        }

        self::add_replacement($search, $replace);

    }

    public static function remove($search){

        if(is_array($search)){
            foreach($search as $search_value){
                self::remove($search_value);
            }

            return;
        }

        if($search === null || $search === ''){
            return;
        }

        unset(self::$replacements[(string)$search]);

    }

    public static function clear(){

        self::$replacements = [];

    }

    public static function has_replacement(){

        return !empty(self::$replacements);

    }

    public static function apply_replacement($value){

        if(!self::has_replacement()){
            return $value;
        }

        $search = array_keys(self::$replacements);
        $replace = array_map([self::class, 'normalize_replacement'], array_values(self::$replacements));

        if(is_string($value)){
            return str_replace($search, $replace, $value);
        }

        if(is_array($value)){
            self::recursive($search, $replace, $value);
        }

        return $value;

    }

    public static function recursive($search, $replace, &$array){

        foreach($array as &$value){

            if(is_array($value)){
                self::recursive($search, $replace, $value);
                continue;
            }

            if(is_string($value)){
                $value = str_replace($search, $replace, $value);
            }

        }

    }

    private static function add_replacement($search, $replace){

        if($search === null || $search === ''){
            return;
        }

        self::$replacements[(string)$search] = $replace;

    }

    private static function normalize_replacement($value){

        if(is_bool($value)){
            return $value ? '1' : '0';
        }

        if(is_scalar($value) || $value === null){
            return (string)$value;
        }

        return '';

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

    public static $defaults = [
        'echo' => false
    ];
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

class Sanitize {

    public static function email($args){
        return isset($args['value']) ? sanitize_email(
            $args['value']
        ) : null;
    }

    public static function file_name($args){
        return isset($args['value']) ? sanitize_file_name(
            $args['value']
        ) : null;
    }

    public static function hex_color($args){
        return isset($args['value']) ? sanitize_hex_color(
            $args['value']
        ) : null;
    }

    public static function hex_color_no_hash($args){
        return isset($args['value']) ? sanitize_hex_color_no_hash(
            $args['value']
        ) : null;
    }

    public static function html_class($args){
        return isset($args['value']) ? sanitize_html_class(
            $args['value'],
            $args['fallback'] ?? ''
        ) : null;
    }

    public static function key($args){
        return isset($args['value']) ? sanitize_key(
            $args['value']
        ) : null;
    }

    public static function meta($args){
        return isset($args['key'], $args['value'], $args['object_type']) ? sanitize_meta(
            $args['key'],
            $args['value'],
            $args['object_type'],
            $args['object_subtype'] ?? ''
        ) : null;
    }

    public static function mime_type($args){
        return isset($args['value']) ? sanitize_mime_type(
            $args['value']
        ) : null;
    }

    public static function option($args){
        return isset($args['option'], $args['value']) ? sanitize_option(
            $args['option'],
            $args['value']
        ) : null;
    }

    public static function sql_orderby($args){
        return isset($args['value']) ? sanitize_sql_orderby(
            $args['value']
        ) : null;
    }

    public static function term($args){
        return isset($args['value'], $args['taxonomy']) ? sanitize_term(
            $args['value'],
            $args['taxonomy'],
            $args['context'] ?? 'display'
        ) : null;
    }

    public static function term_field($args){
        return isset($args['field'], $args['value'], $args['term_id'], $args['taxonomy']) ? sanitize_term_field(
            $args['field'],
            $args['value'],
            (int)$args['term_id'],
            $args['taxonomy'],
            $args['context'] ?? 'display'
        ) : null;
    }

    public static function text_field($args){
        return isset($args['value']) ? sanitize_text_field(
            $args['value']
        ) : null;
    }

    public static function textarea_field($args){
        return isset($args['value']) ? sanitize_textarea_field(
            $args['value']
        ) : null;
    }

    public static function title($args){
        return isset($args['value']) ? sanitize_title(
            $args['value'],
            $args['fallback'] ?? '',
            $args['context'] ?? 'save'
        ) : null;
    }

    public static function title_for_query($args){
        return isset($args['value']) ? sanitize_title_for_query(
            $args['value']
        ) : null;
    }

    public static function title_with_dashes($args){
        return isset($args['value']) ? sanitize_title_with_dashes(
            $args['value'],
            $args['raw_title'] ?? '',
            $args['context'] ?? 'display'
        ) : null;
    }

    public static function user($args){
        return isset($args['value']) ? sanitize_user(
            $args['value'],
            $args['strict'] ?? false
        ) : null;
    }

    public static function url($args){
        return isset($args['value']) ? sanitize_url(
            $args['value'],
            $args['protocols'] ?? null
        ) : null;
    }

    public static function html($args){
        return isset($args['value']) ? wp_kses(
            $args['value'],
            $args['allowed_html'] ?? 'post',
            $args['allowed_protocols'] ?? []
        ) : null;
    }

    public static function post_content($args){
        return isset($args['value']) ? wp_kses_post(
            $args['value']
        ) : null;
    }
    
}

class Escape {

    public static function html($v){
        return esc_html($v);
    }

    public static function js($v){
        return esc_js($v);
    }

    public static function url($v){
        return esc_url($v);
    }

    public static function url_raw($v){
        return esc_url_raw($v);
    }

    public static function xml($v){
        return esc_xml($v);
    }

    public static function attr($v){
        return esc_attr($v);
    }

    public static function textarea($v){
        return esc_textarea($v);
    }

    public static function html__($v, $args = []){
        return esc_html__($v, $args['domain'] ?? 'default');
    }

    public static function html_x($v, $args = []){
        return isset($args['context']) ? esc_html_x($v, $args['context'], $args['domain'] ?? 'default') : null;
    }

    public static function attr__($v, $args = []){
        return esc_attr__($v, $args['domain'] ?? 'default');
    }

    public static function attr_x($v, $args = []){
        return isset($args['context']) ? esc_attr_x($v, $args['context'], $args['domain'] ?? 'default') : null;
    }

}