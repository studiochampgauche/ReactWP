<?php

define('ABSPATH', __DIR__);

$actions = [];
$filters = [];
$registered_post_types = [];
$activation_hooks = [];
$deactivation_hooks = [];
$dequeued_styles = [];
$enqueued_styles = [];
$dequeued_scripts = [];
$flush_count = 0;
$unregistered_post_types = [];
$rewrite_events = [];
$is_legal_page = false;
$kses_calls = 0;
$password_kses_calls = 0;
$options = [];

function add_action($hook, $callback, $priority = 10, $accepted_args = 1){
    global $actions;
    $actions[$hook][] = [$callback, $priority, $accepted_args];
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){
    global $filters;
    $filters[$hook][] = [$callback, $priority, $accepted_args];
}

function apply_filters($hook, $value){
    return $value;
}

function register_activation_hook($file, $callback){
    global $activation_hooks;
    $activation_hooks[] = [$file, $callback];
}

function register_deactivation_hook($file, $callback){
    global $deactivation_hooks;
    $deactivation_hooks[] = [$file, $callback];
}

function load_plugin_textdomain(){
    return true;
}

function get_option($name, $default = false){
    global $options;
    return array_key_exists($name, $options) ? $options[$name] : $default;
}

function add_option($name, $value){
    global $options;
    if(array_key_exists($name, $options)){
        return false;
    }
    $options[$name] = $value;
    return true;
}

function plugin_basename($file){
    return basename(dirname($file)) . '/' . basename($file);
}

function sanitize_title($value){
    $value = strtolower(trim((string)$value));
    return preg_replace('/[^a-z0-9]+/', '-', $value);
}

function absint($value){
    return abs((int)$value);
}

function sanitize_text_field($value){
    return trim(strip_tags((string)$value));
}

function sanitize_textarea_field($value){
    return trim(strip_tags((string)$value));
}

function __($value){
    return $value;
}

function register_post_type($post_type, $args){
    global $registered_post_types;
    $registered_post_types[$post_type] = $args;
}

function post_type_exists($post_type){
    global $registered_post_types;
    return isset($registered_post_types[$post_type]);
}

function unregister_post_type($post_type){
    global $registered_post_types, $unregistered_post_types, $rewrite_events;
    $unregistered_post_types[] = $post_type;
    $rewrite_events[] = 'unregister';
    unset($registered_post_types[$post_type]);
    return true;
}

function is_singular($post_type = ''){
    global $is_legal_page;
    return $post_type === 'legal_page' && $is_legal_page;
}

function wp_dequeue_style($handle){
    global $dequeued_styles;
    $dequeued_styles[] = $handle;
}

function wp_enqueue_style($handle, $src, $dependencies = [], $version = false){
    global $enqueued_styles;
    $enqueued_styles[$handle] = compact('src', 'dependencies', 'version');
}

function wp_dequeue_script($handle){
    global $dequeued_scripts;
    $dequeued_scripts[] = $handle;
}

function plugins_url($path){
    return 'https://example.test/wp-content/plugins/universal-legal-pages/' . ltrim($path, '/');
}

function flush_rewrite_rules(){
    global $flush_count, $rewrite_events;
    $flush_count++;
    $rewrite_events[] = 'flush';
}

class WP_Styles{
    public $queue = [];
}

class WP_Scripts{
    public $queue = [];
}

class WP_Post{
    public $post_title = 'Conditions <script>alert(1)</script>';
    public $post_content = '<p>Texte autorisé.</p><script>alert(2)</script>';
    public $post_password = '';
}

function get_queried_object(){
    global $queried_post;
    return $queried_post;
}

function get_the_title($post){
    return $post->post_title;
}

function post_password_required($post){
    return $post->post_password !== '';
}

function get_the_password_form(){
    return '<form class="post-password-form" method="post"><div class="post-password-form__error" role="alert"><p id="password-error">Mot de passe invalide.</p></div><p><label>Mot de passe <input type="password" name="post_password" autocomplete="current-password" required aria-describedby="password-error"></label> <input type="submit" value="Entrer"></p></form>';
}

function get_bloginfo($key){
    return $key === 'charset' ? 'UTF-8' : '';
}

function current_theme_supports($feature){
    return $feature !== 'title-tag';
}

function wp_get_document_title(){
    return 'Politique de confidentialité';
}

function language_attributes(){
    echo 'lang="fr"';
}

function esc_attr($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function wp_kses_post($value){
    global $kses_calls;
    $kses_calls++;
    $value = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string)$value);
    return strip_tags($value, '<p><a><strong><em><ul><ol><li><blockquote><h2><h3><h4><h5><h6>');
}

function wp_kses($value, $allowed_html, $allowed_protocols = []){
    global $password_kses_calls;
    $password_kses_calls++;

    if(!isset($allowed_html['form'], $allowed_html['input'])){
        return strip_tags((string)$value, '<p><label>');
    }

    return preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string)$value);
}

function wp_head(){
    echo '<!-- wp-head -->';
}

function wp_body_open(){
    echo '<!-- wp-body-open -->';
}

function body_class($class = ''){
    echo 'class="' . esc_attr($class) . '"';
}

function wp_footer(){
    echo '<!-- wp-footer -->';
}

function legal_pages_assert($condition, $message){
    if(!$condition){
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$queried_post = new WP_Post();

require_once __DIR__ . '/../../src/plugins/universal-legal-pages/template/init.php';

legal_pages_assert(isset($actions['init']), 'The legal page post type is not registered on init.');
legal_pages_assert(isset($actions['wp_enqueue_scripts']), 'The legal page stylesheet hook is missing.');
legal_pages_assert(isset($filters['template_include']), 'The forced legal page template filter is missing.');
legal_pages_assert(count($activation_hooks) === 1, 'The activation hook is missing.');
legal_pages_assert(count($deactivation_hooks) === 1, 'The deactivation hook is missing.');

Universal_Legal_Pages::register_post_type();

legal_pages_assert(isset($registered_post_types['legal_page']), 'The legal_page post type was not registered.');

$post_type = $registered_post_types['legal_page'];

legal_pages_assert($post_type['public'] === true, 'Legal pages must be publicly queryable.');
legal_pages_assert($post_type['show_in_rest'] === true, 'Legal pages must support the native block editor.');
legal_pages_assert($post_type['capability_type'] === 'page', 'Legal pages must use WordPress page capabilities.');
legal_pages_assert($post_type['map_meta_cap'] === true, 'Object-level WordPress meta capabilities must remain enabled.');
legal_pages_assert($post_type['has_archive'] === false, 'The plugin must not create an unnecessary public archive.');
legal_pages_assert($post_type['rewrite']['slug'] === 'legal', 'The default legal page rewrite slug changed unexpectedly.');
legal_pages_assert($post_type['supports'] === ['title', 'editor', 'revisions'], 'The portable editor contract changed unexpectedly.');

$fallback_template = __DIR__ . '/theme-template.php';
legal_pages_assert(
    Universal_Legal_Pages::use_plugin_template($fallback_template) === $fallback_template,
    'A non-legal route must keep the active theme template.'
);

$classes = Universal_Legal_Pages::add_body_class(['existing']);
legal_pages_assert($classes === ['existing'], 'A non-legal route received the legal page body class.');

global $is_legal_page;
$is_legal_page = true;

$expected_template = str_replace('\\', '/', realpath(
    __DIR__ . '/../../src/plugins/universal-legal-pages/template/templates/single-legal-page.php'
));
$resolved_template = str_replace(
    '\\',
    '/',
    Universal_Legal_Pages::use_plugin_template($fallback_template)
);

legal_pages_assert(
    $resolved_template === $expected_template,
    'A legal page did not resolve to the plugin-owned template.'
);

$classes = Universal_Legal_Pages::add_body_class(['existing', 'universal-legal-page']);
legal_pages_assert(
    $classes === ['existing', 'universal-legal-page'],
    'The legal page body class must be present exactly once.'
);

$wp_styles = new WP_Styles();
$wp_styles->queue = ['theme-style', 'admin-bar', 'plugin-style'];
$wp_scripts = new WP_Scripts();
$wp_scripts->queue = ['rwp-main', 'theme-script', 'admin-bar'];

Universal_Legal_Pages::enqueue_assets();

legal_pages_assert(
    $dequeued_styles === ['theme-style', 'plugin-style'],
    'Theme/plugin styles must be removed while the logged-in admin bar style is preserved.'
);
legal_pages_assert(
    $dequeued_scripts === ['rwp-main', 'theme-script'],
    'Theme/ReactWP scripts must be removed while the logged-in admin bar script is preserved.'
);
legal_pages_assert(
    isset($enqueued_styles['universal-legal-pages']),
    'The standalone legal page stylesheet was not enqueued.'
);
legal_pages_assert(
    $enqueued_styles['universal-legal-pages']['dependencies'] === [],
    'The standalone stylesheet must not depend on the active theme.'
);

Universal_Legal_Pages::activate();
Universal_Legal_Pages::deactivate();

legal_pages_assert($flush_count === 2, 'Rewrite rules must flush only through activation/deactivation helpers.');
legal_pages_assert(
    $unregistered_post_types === ['legal_page'],
    'Deactivation must remove the legal page permastruct before flushing rewrite rules.'
);
legal_pages_assert(
    $rewrite_events === ['flush', 'unregister', 'flush'],
    'Deactivation must unregister the post type before its rewrite flush.'
);

ob_start();
require __DIR__ . '/../../src/plugins/universal-legal-pages/template/templates/single-legal-page.php';
$rendered_template = ob_get_clean();

legal_pages_assert(substr_count($rendered_template, '<h1 ') === 1, 'The public template must contain exactly one page heading.');
legal_pages_assert(
    strpos($rendered_template, 'Conditions &lt;script&gt;alert(1)&lt;/script&gt;') !== false,
    'The legal page title was not escaped as HTML text.'
);
legal_pages_assert(strpos($rendered_template, '<p>Texte autorisé.</p>') !== false, 'Allowed rich text was removed.');
legal_pages_assert(strpos($rendered_template, '<script>') === false, 'Scriptable editor content reached the public HTML sink.');
legal_pages_assert($kses_calls === 1, 'Legal rich text must pass through one explicit wp_kses_post output boundary.');
legal_pages_assert(
    strpos($rendered_template, '<title>Politique de confidentialité</title>') !== false,
    'The standalone template must provide a document title when the active theme does not support title-tag.'
);
legal_pages_assert(
    strpos($rendered_template, '<!-- wp-head -->') !== false
        && strpos($rendered_template, '<!-- wp-body-open -->') !== false
        && strpos($rendered_template, '<!-- wp-footer -->') !== false,
    'The standalone template must preserve the standard WordPress ecosystem hooks.'
);

$queried_post->post_title = '';
ob_start();
require __DIR__ . '/../../src/plugins/universal-legal-pages/template/templates/single-legal-page.php';
$empty_title_template = ob_get_clean();

legal_pages_assert(
    strpos($empty_title_template, '<h1 class="universal-legal-page__title">Legal document</h1>') !== false,
    'An empty WordPress title must retain a useful translated page heading.'
);

$queried_post->post_password = 'secret';
$queried_post->post_content = '<p>PRIVATE_LEGAL_TEXT</p>';
ob_start();
require __DIR__ . '/../../src/plugins/universal-legal-pages/template/templates/single-legal-page.php';
$password_template = ob_get_clean();

legal_pages_assert(
    strpos($password_template, 'PRIVATE_LEGAL_TEXT') === false,
    'A password-protected legal document exposed its stored content anonymously.'
);
legal_pages_assert(
    strpos($password_template, 'class="post-password-form"') !== false
        && strpos($password_template, 'type="password"') !== false,
    'A password-protected legal document must retain WordPress\'s password form.'
);
legal_pages_assert(
    strpos($password_template, 'role="alert"') !== false
        && strpos($password_template, 'required') !== false
        && strpos($password_template, 'aria-describedby="password-error"') !== false,
    'The password form allowlist must preserve WordPress accessibility and native validation attributes.'
);
legal_pages_assert(
    $password_kses_calls === 1,
    'The WordPress password form must pass through its dedicated narrow HTML allowlist.'
);

fwrite(STDOUT, "Universal Legal Pages tests passed.\n");
