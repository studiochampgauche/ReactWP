<?php
/*
* Plugin Name: ReactWP Accept SVG
* Description: Upload your SVGs
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
* Update URI: false
* Version: 1.0.1
*/


add_filter('upload_mimes', function($mimes){

    $mimes['svg'] = 'image/svg+xml';

    return $mimes;

});

add_filter('wp_handle_upload_prefilter', function($file){

    $extension = strtolower((string)pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if($extension !== 'svg') return $file;

    if(!current_user_can('upload_files')){
        $file['error'] = __('You are not allowed to upload SVG files.', 'reactwp-accept-svg');
        return $file;
    }

    $file['name'] = rwp::sanitize('file_name', [
        'value' => $file['name']
    ]) ?: $file['name'];

    if(
        empty($file['tmp_name'])
        ||
        !is_string($file['tmp_name'])
        ||
        !is_readable($file['tmp_name'])
    ){
        $file['error'] = __('The SVG file could not be read.', 'reactwp-accept-svg');
        return $file;
    }

    $contents = file_get_contents($file['tmp_name']);

    if($contents === false){
        $file['error'] = __('The SVG file could not be read.', 'reactwp-accept-svg');
        return $file;
    }

    if(!preg_match('/<svg\b/i', $contents)){
        $file['error'] = __('The uploaded file is not a valid SVG.', 'reactwp-accept-svg');
        return $file;
    }

    if(
        preg_match('/<script\b/i', $contents)
        ||
        preg_match('/<foreignObject\b/i', $contents)
        ||
        preg_match('/\son[a-z]+\s*=/i', $contents)
        ||
        preg_match('/javascript\s*:/i', $contents)
        ||
        preg_match('/<!ENTITY/i', $contents)
        ||
        preg_match('/<!DOCTYPE/i', $contents)
        ||
        preg_match('/<iframe\b/i', $contents)
        ||
        preg_match('/<object\b/i', $contents)
        ||
        preg_match('/<embed\b/i', $contents)
        ||
        preg_match('/<\?xml-stylesheet\b/i', $contents)
        ||
        preg_match('/(?:href|xlink:href)\s*=\s*["\']\s*(?!#)/i', $contents)
        ||
        preg_match('/url\(\s*["\']?\s*(?!#)/i', $contents)
        ||
        preg_match('/@import\b/i', $contents)
    ){
        $file['error'] = __('This SVG contains unsafe markup.', 'reactwp-accept-svg');
        return $file;
    }

    $file['type'] = 'image/svg+xml';

    return $file;

});

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){

    if(strtolower((string)pathinfo((string)$filename, PATHINFO_EXTENSION)) !== 'svg'){
        return $data;
    }

    $data['ext'] = 'svg';
    $data['type'] = 'image/svg+xml';

    return $data;

}, 10, 4);