<?php

define('ABSPATH', __DIR__);

$plugin_root = __DIR__ . '/../../src/plugins/reactwp-accept-svg/template';
$checks = 0;
$assert = static function($condition, $message) use (&$checks){
    $checks++;

    if(!$condition){
        throw new RuntimeException($message);
    }
};

class WP_Error {
    private $code;
    private $message;

    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}

$GLOBALS['svg_test_filters'] = [];
$GLOBALS['svg_test_can_upload'] = false;

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['svg_test_filters'][$hook] = $callback;
}

function apply_filters($hook, $value) { return $value; }
function current_user_can($capability) { return $capability === 'manage_options' && $GLOBALS['svg_test_can_upload']; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_file_name($value) { return basename($value); }
function size_format($value) { return (string)$value; }
function __($message, $domain = '') { return $message; }
function is_wp_error($value) { return $value instanceof WP_Error; }

require_once $plugin_root . '/init.php';

$lock = json_decode(file_get_contents($plugin_root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
$locked_sanitizer = null;

foreach($lock['packages'] as $package){
    if($package['name'] === 'enshrined/svg-sanitize'){
        $locked_sanitizer = $package;
        break;
    }
}

$assert(is_array($locked_sanitizer), 'The SVG sanitizer must be present in the Composer lock.');
$assert(
    Composer\InstalledVersions::getPrettyVersion('enshrined/svg-sanitize') === $locked_sanitizer['version']
    && Composer\InstalledVersions::getReference('enshrined/svg-sanitize') === $locked_sanitizer['source']['reference'],
    'The bundled runtime sanitizer must match the version and source reference in composer.lock. Regenerate vendor with Composer.'
);
$assert(
    realpath((new ReflectionClass(enshrined\svgSanitize\Sanitizer::class))->getFileName())
        === realpath($plugin_root . '/vendor/enshrined/svg-sanitize/src/Sanitizer.php'),
    'The sanitizer tests must exercise the vendor shipped with the plugin.'
);

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

$assert(is_string($clean) && trim($clean) !== '', 'A parseable SVG must return safe markup.');

foreach(['<script', 'onload=', 'javascript:', 'https://example.com'] as $unsafe_fragment){
    $assert(stripos($clean, $unsafe_fragment) === false, "Unsafe SVG fragment survived sanitization: {$unsafe_fragment}");
}

$assert(stripos($clean, '<svg') !== false && stripos($clean, '<rect') !== false, 'Safe vector markup must survive.');

foreach(['href', 'xlink:href', 'HrEf', 'xlink:HrEf'] as $attribute){
    foreach(['', '#shape', 'javascript:alert(1)', 'https://example.com/image.svg', '//example.com/image.svg', '/image.svg', 'data:image/png;base64,AA=='] as $value){
        $markup = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a '
            . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"><rect width="10" height="10" /></a></svg>';
        $document = new DOMDocument();
        $assert($document->loadXML($sanitizer->sanitize($markup), LIBXML_NONET), 'Sanitized href fixtures must remain valid XML.');
        $link = $document->getElementsByTagName('a')->item(0);
        $allowed = $value === '' || $value === '#shape';
        $assert(
            $link instanceof DOMElement
                && $link->hasAttribute(strtolower($attribute)) === $allowed
                && (!$allowed || $link->getAttribute(strtolower($attribute)) === $value),
            "The fragment-only href policy changed for {$attribute}={$value}."
        );
    }
}

$css = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg">
    <defs><linearGradient id="paint"><stop offset="0" stop-color="red" /></linearGradient></defs>
    <style>@import "https://example.com/remote.css"; .remote { fill: \75 rl("https://example.com/paint.svg"); } .safe { fill: url(#paint); stroke: #000; }</style>
    <rect width="10" height="10" style="fill: url(https://example.com/paint.svg)" />
    <circle r="5" style="fill: url(#paint)" />
</svg>
SVG;
$clean_css = $sanitizer->sanitize($css);
$assert(is_string($clean_css) && stripos($clean_css, 'example.com') === false, 'Remote and CSS-escaped references must be removed.');
$assert(strpos($clean_css, 'url(#paint)') !== false, 'Same-document paint references must remain available.');

$path = tempnam(sys_get_temp_dir(), 'rwp-svg-test-');
$assert(is_string($path), 'A temporary upload fixture must be available.');

try {
    $invalid_documents = [
        '<!DOCTYPE svg [<!ENTITY payload SYSTEM "file:///unavailable-test-resource">]><svg xmlns="http://www.w3.org/2000/svg">&payload;</svg>',
        '<!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg" />',
        '<svg xmlns="http://www.w3.org/2000/svg"><rect></svg>',
        '<html xmlns="http://www.w3.org/1999/xhtml"><body /></html>',
        '<svg xmlns="https://example.com/not-svg" />',
    ];

    foreach($invalid_documents as $markup){
        file_put_contents($path, $markup);
        clearstatcache(true, $path);
        $result = rwp_sanitize_svg_path($path);
        $assert(is_wp_error($result) && $result->get_error_code() === 'reactwp_svg_invalid_document', 'Unsafe XML must be rejected by the upload boundary.');
        $assert(file_get_contents($path) === $markup, 'Rejected XML must not replace the uploaded file.');
    }

    $oversized = '<svg>' . str_repeat(' ', 2 * 1024 * 1024) . '</svg>';
    file_put_contents($path, $oversized);
    clearstatcache(true, $path);
    $result = rwp_sanitize_svg_path($path);
    $assert(is_wp_error($result) && $result->get_error_code() === 'reactwp_svg_too_large', 'Oversized SVG input must fail before parsing.');

    foreach(['wp_handle_upload_prefilter', 'wp_handle_sideload_prefilter'] as $hook){
        file_put_contents($path, $unsafe);
        clearstatcache(true, $path);
        $file = ['name' => 'fixture.svg', 'tmp_name' => $path, 'type' => 'text/plain', 'size' => strlen($unsafe)];
        $GLOBALS['svg_test_can_upload'] = false;
        $denied = $GLOBALS['svg_test_filters'][$hook]($file);
        $assert(!empty($denied['error']) && file_get_contents($path) === $unsafe, "{$hook} must deny an unauthorized upload without changing its file.");

        $GLOBALS['svg_test_can_upload'] = true;
        $accepted = $GLOBALS['svg_test_filters'][$hook]($file);
        $saved = file_get_contents($path);
        $assert(empty($accepted['error']) && $accepted['type'] === 'image/svg+xml' && $accepted['size'] === strlen($saved), "{$hook} must return the sanitized MIME and byte size.");
        $assert(rwp_is_svg_document($saved) && stripos($saved, '<script') === false && stripos($saved, 'javascript:') === false, "{$hook} must replace the upload with safe SVG markup.");
    }

    $check_type = $GLOBALS['svg_test_filters']['wp_check_filetype_and_ext'];
    file_put_contents($path, '<html><body /></html>');
    clearstatcache(true, $path);
    $rejected_type = $check_type(['ext' => 'svg', 'type' => 'image/svg+xml'], $path, 'fixture.svg', []);
    $assert($rejected_type['ext'] === false && $rejected_type['type'] === false, 'Filetype verification must reject a non-SVG document.');

    file_put_contents($path, $unsafe);
    clearstatcache(true, $path);
    $accepted_type = $check_type(['ext' => false, 'type' => false], $path, 'fixture.svg', []);
    $assert($accepted_type['ext'] === 'svg' && $accepted_type['type'] === 'image/svg+xml', 'Filetype verification must accept sanitized SVG content.');
    $assert(stripos(file_get_contents($path), '<script') === false, 'Filetype verification must also sanitize the uploaded file.');
} finally {
    if(is_file($path)){
        unlink($path);
    }
}

fwrite(STDOUT, "SVG sanitizer tests passed ({$checks} checks).\n");
