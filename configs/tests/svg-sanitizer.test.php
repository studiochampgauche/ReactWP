<?php

define('ABSPATH', __DIR__);

require_once __DIR__ . '/../../src/plugins/reactwp-accept-svg/template/vendor/autoload.php';
require_once __DIR__ . '/../../src/plugins/reactwp-accept-svg/template/inc/SvgSanitizer.php';

$sanitizer = new ReactWP_SVG_Sanitizer();
$sanitizer->removeRemoteReferences(true);
$sanitizer->minify(true);

$unsafe = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">
    <script>alert(1)</script>
    <a href="javascript:alert(2)"><rect width="10" height="10" /></a>
    <image href="https://example.com/tracker.png" width="10" height="10" />
</svg>
SVG;

$clean = $sanitizer->sanitize($unsafe);

if(!is_string($clean) || trim($clean) === ''){
    fwrite(STDERR, "The sanitizer rejected a parseable SVG instead of returning safe markup.\n");
    exit(1);
}

foreach(['<script', 'onload=', 'javascript:', 'https://example.com'] as $unsafe_fragment){
    if(stripos($clean, $unsafe_fragment) !== false){
        fwrite(STDERR, "Unsafe SVG fragment survived sanitization: {$unsafe_fragment}\n");
        exit(1);
    }
}

if(stripos($clean, '<svg') === false || stripos($clean, '<rect') === false){
    fwrite(STDERR, "Safe SVG markup was unexpectedly removed.\n");
    exit(1);
}

fwrite(STDOUT, "SVG sanitizer tests passed.\n");
