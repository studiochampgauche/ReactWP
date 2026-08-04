<?php
/*
* Plugin Name: ReactWP Accept SVG
* Description: Upload your SVGs
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
* Update URI: false
* Version: 1.2.0
*/

if(!defined('ABSPATH')){
    exit;
}

$rwp_svg_autoload = __DIR__ . '/vendor/autoload.php';

if(is_readable($rwp_svg_autoload)){
    require_once $rwp_svg_autoload;
    require_once __DIR__ . '/inc/SvgSanitizer.php';
}

function rwp_svg_upload_capability() {

    $capability = sanitize_key((string)apply_filters('rwp_svg_upload_capability', 'manage_options'));

    return $capability !== '' ? $capability : 'manage_options';

}

add_filter('upload_mimes', function($mimes){

    if(!current_user_can(rwp_svg_upload_capability())){
        unset($mimes['svg']);
        return $mimes;
    }

    $mimes['svg'] = 'image/svg+xml';

    return $mimes;

});

function rwp_sanitize_svg_path($path) {

    if(
        !is_string($path)
        || $path === ''
        || is_link($path)
        || !is_file($path)
        || !is_readable($path)
    ){
        return new WP_Error(
            'reactwp_svg_unreadable',
            __('The SVG file could not be read.', 'reactwp-accept-svg')
        );
    }

    $max_bytes = max(1024, (int)apply_filters('rwp_svg_max_bytes', 2 * 1024 * 1024));
    $size = filesize($path);

    if($size === false || $size > $max_bytes){
        return new WP_Error(
            'reactwp_svg_too_large',
            sprintf(
                __('SVG files may not exceed %s.', 'reactwp-accept-svg'),
                size_format($max_bytes)
            )
        );
    }

    if(!class_exists('ReactWP_SVG_Sanitizer')){
        return new WP_Error(
            'reactwp_svg_sanitizer_unavailable',
            __('The SVG sanitizer is unavailable.', 'reactwp-accept-svg')
        );
    }

    $contents = file_get_contents($path);

    if($contents === false){
        return new WP_Error(
            'reactwp_svg_unreadable',
            __('The SVG file could not be read.', 'reactwp-accept-svg')
        );
    }

    if(!rwp_is_svg_document($contents)){
        return new WP_Error(
            'reactwp_svg_invalid_document',
            __('The uploaded file is not a safe SVG document.', 'reactwp-accept-svg')
        );
    }

    $sanitizer = new ReactWP_SVG_Sanitizer();
    $sanitizer->removeRemoteReferences(true);
    $sanitizer->minify(true);
    $sanitized = $sanitizer->sanitize($contents);

    if(!is_string($sanitized) || trim($sanitized) === '' || !rwp_is_svg_document($sanitized)){
        return new WP_Error(
            'reactwp_svg_invalid',
            __('The uploaded file is not a valid SVG.', 'reactwp-accept-svg')
        );
    }

    $temporary_path = tempnam(dirname($path), '.rwp-svg-');

    if(
        !is_string($temporary_path)
        || file_put_contents($temporary_path, $sanitized, LOCK_EX) === false
    ){
        if(is_string($temporary_path)){
            @unlink($temporary_path);
        }

        return new WP_Error(
            'reactwp_svg_write_failed',
            __('The sanitized SVG could not be saved.', 'reactwp-accept-svg')
        );
    }

    $permissions = fileperms($path);

    if($permissions !== false){
        @chmod($temporary_path, $permissions & 0777);
    }

    if(!@rename($temporary_path, $path)){
        @unlink($temporary_path);
        return new WP_Error(
            'reactwp_svg_write_failed',
            __('The sanitized SVG could not be saved.', 'reactwp-accept-svg')
        );
    }

    return strlen($sanitized);

}

function rwp_is_svg_document($contents) {

    if(
        !is_string($contents)
        || $contents === ''
        || stripos($contents, '<!doctype') !== false
        || stripos($contents, '<!entity') !== false
        || strlen($contents) > max(1024, (int)apply_filters('rwp_svg_max_bytes', 2 * 1024 * 1024))
    ){
        return false;
    }

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $loaded
        && $document->doctype === null
        && $document->documentElement instanceof DOMElement
        && strtolower($document->documentElement->localName) === 'svg'
        && in_array($document->documentElement->namespaceURI, [null, '', 'http://www.w3.org/2000/svg'], true);

}

function rwp_sanitize_svg_upload($file) {

    $extension = strtolower((string)pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if($extension !== 'svg') return $file;

    if(!current_user_can(rwp_svg_upload_capability())){
        $file['error'] = __('You are not allowed to upload SVG files.', 'reactwp-accept-svg');
        return $file;
    }

    $filename = sanitize_file_name((string)$file['name']);

    if(!$filename){
        $file['error'] = __('This SVG can\'t be sanitize.', 'reactwp-accept-svg');
        return $file;
    }

    if(strtolower((string) pathinfo($filename, PATHINFO_EXTENSION)) !== 'svg'){
        $file['error'] = __('Invalid SVG file name.', 'reactwp-accept-svg');
        return $file;
    }

    $file['name'] = $filename;

    $sanitized_size = rwp_sanitize_svg_path($file['tmp_name'] ?? '');

    if(is_wp_error($sanitized_size)){
        $file['error'] = $sanitized_size->get_error_message();
        return $file;
    }

    $file['type'] = 'image/svg+xml';
    $file['size'] = $sanitized_size;

    return $file;

}

add_filter('wp_handle_upload_prefilter', 'rwp_sanitize_svg_upload');
add_filter('wp_handle_sideload_prefilter', 'rwp_sanitize_svg_upload');

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){

    if(strtolower((string)pathinfo((string)$filename, PATHINFO_EXTENSION)) !== 'svg'){
        return $data;
    }

    $sanitized_size = rwp_sanitize_svg_path($file);

    if(is_wp_error($sanitized_size)){
        $data['ext'] = false;
        $data['type'] = false;
        $data['proper_filename'] = false;
        return $data;
    }

    $data['ext'] = 'svg';
    $data['type'] = 'image/svg+xml';

    return $data;

}, 10, 4);
