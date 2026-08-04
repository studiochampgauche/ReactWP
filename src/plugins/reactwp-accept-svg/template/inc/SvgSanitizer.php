<?php

if(!defined('ABSPATH')){
    exit;
}

class ReactWP_SVG_Sanitizer extends \enshrined\svgSanitize\Sanitizer {

    protected function isHrefSafeValue($value) {

        $value = is_string($value) ? trim($value) : '';

        return $value === '' || strpos($value, '#') === 0;

    }

}
