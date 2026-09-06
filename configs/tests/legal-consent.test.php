<?php

define('ABSPATH', __DIR__);
define('COOKIEPATH', '/');

$actions = [];
$filters = [];
$activation_hooks = [];
$deactivation_hooks = [];
$registered_settings = [];
$submenu_pages = [];
$settings_errors = [];
$options = [];
$post_types = [
    10 => 'legal_page',
    11 => 'legal_page',
    12 => 'legal_page',
    99 => 'post',
];
$post_statuses = [
    10 => 'publish',
    11 => 'publish',
    12 => 'draft',
    99 => 'publish',
];
$post_titles = [
    10 => 'Confidentialité',
    11 => 'Conditions',
    12 => 'Brouillon',
];
$enqueued_scripts = [];
$inline_scripts = [];
$enqueued_styles = [];
$dequeued_styles = [];
$dequeued_scripts = [];
$is_legal_page = false;
$flush_count = 0;
$registered_post_types = [];
$can_manage_options = true;
$can_unfiltered_html = true;
$theme_file_path = '';
$theme_file_uri = '';
$theme_file_requests = [];
$get_posts_requests = [];
$nonce_valid = true;
$nonce_checks = [];

class ULP_Test_JSON_Response extends RuntimeException{
    public $success;
    public $data;
    public $status;

    public function __construct($success, $data, $status){
        parent::__construct('JSON response');
        $this->success = (bool)$success;
        $this->data = $data;
        $this->status = (int)$status;
    }
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1){
    global $actions;
    $actions[$hook][] = [$callback, $priority, $accepted_args];
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1){
    global $filters;
    $filters[$hook][] = [$callback, $priority, $accepted_args];
}

function apply_filters($hook, $value, ...$args){
    global $filters;

    if(empty($filters[$hook])){
        return $value;
    }

    usort($filters[$hook], function($first, $second){
        return $first[1] <=> $second[1];
    });

    foreach($filters[$hook] as $registration){
        $accepted_args = $registration[2];
        $call_args = array_slice(array_merge([$value], $args), 0, $accepted_args);
        $value = call_user_func_array($registration[0], $call_args);
    }

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

function plugin_basename($file){
    return basename(dirname($file)) . '/' . basename($file);
}

function __($value){
    return $value;
}

function sanitize_title($value){
    return trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string)$value)), '-');
}

function sanitize_key($value){
    return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string)$value));
}

function sanitize_text_field($value){
    return trim(strip_tags((string)$value));
}

function sanitize_textarea_field($value){
    return trim(strip_tags((string)$value));
}

function absint($value){
    return abs((int)$value);
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

function update_option($name, $value){
    global $options, $registered_settings;

    $sanitize_callback = $registered_settings[$name]['args']['sanitize_callback'] ?? null;

    if(is_callable($sanitize_callback)){
        $value = call_user_func($sanitize_callback, $value);
    }

    $changed = !array_key_exists($name, $options) || $options[$name] !== $value;
    $options[$name] = $value;
    return $changed;
}

function wp_unslash($value){
    if(is_array($value)){
        return array_map('wp_unslash', $value);
    }

    return is_string($value) ? stripslashes($value) : $value;
}

function register_setting($group, $name, $args = []){
    global $registered_settings;
    $registered_settings[$name] = compact('group', 'args');
}

function add_submenu_page($parent, $page_title, $menu_title, $capability, $slug, $callback){
    global $submenu_pages;
    $submenu_pages[$slug] = compact('parent', 'page_title', 'menu_title', 'capability', 'callback');
    return 'legal_page_page_' . $slug;
}

function add_settings_error($setting, $code, $message, $type = 'error'){
    global $settings_errors;
    $settings_errors[] = compact('setting', 'code', 'message', 'type');
}

function current_user_can($capability){
    global $can_manage_options, $can_unfiltered_html;

    if($capability === 'manage_options'){
        return $can_manage_options;
    }

    return $capability === 'unfiltered_html' && $can_unfiltered_html;
}

function wp_die($message){
    throw new RuntimeException((string)$message);
}

function get_posts($args = []){
    global $get_posts_requests;
    $get_posts_requests[] = $args;
    return [(object)['ID' => 10], (object)['ID' => 11]];
}

function esc_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_attr($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_textarea($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_html_e($value){
    echo esc_html($value);
}

function esc_html__($value){
    return esc_html($value);
}

function settings_errors($setting = ''){
    echo '<!-- settings-errors:' . esc_attr($setting === '' ? 'all' : $setting) . ' -->';
}

function get_settings_errors($setting = '', $sanitize = false){
    global $settings_errors;

    if($setting === ''){
        return $settings_errors;
    }

    return array_values(array_filter($settings_errors, function($error) use ($setting){
        return isset($error['setting']) && $error['setting'] === $setting;
    }));
}

function settings_fields($group){
    echo '<!-- settings-fields:' . esc_attr($group) . ' -->';
}

function wp_nonce_field($action, $name){
    echo '<input type="hidden" name="' . esc_attr($name) . '" value="valid-nonce" data-nonce-action="' . esc_attr($action) . '">';
}

function check_ajax_referer($action, $query_arg = false, $die = true){
    global $nonce_valid, $nonce_checks;
    $nonce_checks[] = compact('action', 'query_arg', 'die');
    return $nonce_valid;
}

function wp_send_json_success($value = null, $status_code = null, $flags = 0){
    throw new ULP_Test_JSON_Response(true, $value, $status_code ?: 200);
}

function wp_send_json_error($value = null, $status_code = null, $flags = 0){
    throw new ULP_Test_JSON_Response(false, $value, $status_code ?: 200);
}

function checked($checked){
    if($checked){
        echo ' checked="checked"';
    }
}

function selected($selected, $current){
    if((string)$selected === (string)$current){
        echo ' selected="selected"';
    }
}

function submit_button($label){
    echo '<button type="submit">' . esc_html($label) . '</button>';
}

function get_post_type($id){
    global $post_types;
    return isset($post_types[(int)$id]) ? $post_types[(int)$id] : false;
}

function get_post_status($id){
    global $post_statuses;
    return isset($post_statuses[(int)$id]) ? $post_statuses[(int)$id] : false;
}

function get_permalink($id){
    return 'https://example.test/legal/' . (int)$id . '/';
}

function get_the_title($post){
    global $post_titles;
    $id = is_object($post) && isset($post->ID) ? (int)$post->ID : (int)$post;
    return isset($post_titles[$id]) ? $post_titles[$id] : '';
}

function wp_strip_all_tags($value){
    return strip_tags((string)$value);
}

function esc_url_raw($value){
    return preg_match('#^https?://#i', (string)$value) ? (string)$value : '';
}

function esc_url($value){
    return preg_match('#^https?://#i', (string)$value) ? esc_attr($value) : '';
}

function get_current_blog_id(){
    return 7;
}

function get_locale(){
    return 'fr_CA';
}

function home_url($path = '/'){
    return 'https://example.test' . $path;
}

function plugins_url($path){
    return 'https://example.test/wp-content/plugins/universal-legal-pages/' . ltrim($path, '/');
}

function admin_url($path = ''){
    return 'https://example.test/wp-admin/' . ltrim($path, '/');
}

function get_theme_file_path($file){
    global $theme_file_path, $theme_file_requests;
    $theme_file_requests[] = ['path', $file];
    return $theme_file_path;
}

function get_theme_file_uri($file){
    global $theme_file_uri, $theme_file_requests;
    $theme_file_requests[] = ['uri', $file];
    return $theme_file_uri;
}

function add_query_arg($key, $value, $url){
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
}

function wp_json_encode($value, $flags = 0){
    return json_encode($value, $flags);
}

function wp_enqueue_script($handle, $src, $dependencies = [], $version = false, $in_footer = false){
    global $enqueued_scripts;
    $enqueued_scripts[$handle] = compact('src', 'dependencies', 'version', 'in_footer');
}

function wp_add_inline_script($handle, $data, $position = 'after'){
    global $inline_scripts;
    $inline_scripts[$handle][] = compact('data', 'position');
    return true;
}

function wp_enqueue_style($handle, $src, $dependencies = [], $version = false){
    global $enqueued_styles;
    $enqueued_styles[$handle] = compact('src', 'dependencies', 'version');
}

function wp_dequeue_style($handle){
    global $dequeued_styles;
    $dequeued_styles[] = $handle;
}

function wp_dequeue_script($handle){
    global $dequeued_scripts;
    $dequeued_scripts[] = $handle;
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
    global $registered_post_types;
    unset($registered_post_types[$post_type]);
    return true;
}

function flush_rewrite_rules(){
    global $flush_count;
    $flush_count++;
}

function is_singular($post_type = ''){
    global $is_legal_page;
    return $post_type === 'legal_page' && $is_legal_page;
}

class WP_Styles{
    public $queue = [];
}

class WP_Scripts{
    public $queue = [];
    public $registered = [];
}

function consent_assert($condition, $message){
    if(!$condition){
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

require_once __DIR__ . '/../../src/plugins/universal-legal-pages/template/init.php';

consent_assert(isset($actions['admin_init'], $actions['admin_menu'], $actions['admin_enqueue_scripts'], $actions['admin_footer'], $actions['wp_footer']), 'Consent settings or bounded service-discovery hooks are missing.');
consent_assert(isset($actions['wp_ajax_' . Universal_Legal_Pages::AJAX_SAVE_ACTION]), 'The authenticated asynchronous settings action is not registered.');
foreach(['the_content', 'embed_oembed_html'] as $iframe_filter_hook){
    $iframe_filter_priority = null;

    foreach($filters[$iframe_filter_hook] ?? [] as $registration){
        if($registration[0] === [Universal_Legal_Pages::class, 'filter_external_iframes']){
            $iframe_filter_priority = $registration[1];
            break;
        }
    }

    consent_assert($iframe_filter_priority === PHP_INT_MAX, $iframe_filter_hook . ' must transform iframes after late shortcode and embed filters.');
}

Universal_Legal_Pages::register_settings();
Universal_Legal_Pages::register_settings_page();

consent_assert(isset($registered_settings[Universal_Legal_Pages::OPTION_NAME]), 'The consent option was not registered.');
consent_assert(
    $registered_settings[Universal_Legal_Pages::OPTION_NAME]['group'] === Universal_Legal_Pages::SETTINGS_GROUP,
    'The consent option uses the wrong Settings API group.'
);
consent_assert(
    $submenu_pages[Universal_Legal_Pages::SETTINGS_SLUG]['capability'] === 'manage_options',
    'The consent settings page must require manage_options.'
);
consent_assert(
    $submenu_pages[Universal_Legal_Pages::SETTINGS_SLUG]['parent'] === 'edit.php?post_type=legal_page',
    'The consent settings page must live under Pages légales.'
);

Universal_Legal_Pages::enqueue_admin_assets('dashboard');
consent_assert(!isset($enqueued_styles[Universal_Legal_Pages::ADMIN_STYLE_HANDLE]), 'The consent admin stylesheet leaked onto another admin screen.');
consent_assert(!isset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]), 'The consent admin language script leaked onto another admin screen.');

Universal_Legal_Pages::enqueue_admin_assets('legal_page_page_' . Universal_Legal_Pages::SETTINGS_SLUG);
consent_assert(isset($enqueued_styles[Universal_Legal_Pages::ADMIN_STYLE_HANDLE]), 'The consent admin stylesheet was not enqueued on its settings screen.');
consent_assert(
    $enqueued_styles[Universal_Legal_Pages::ADMIN_STYLE_HANDLE]['src'] === 'https://example.test/wp-content/plugins/universal-legal-pages/assets/css/admin-consent.css',
    'The consent settings screen must load its local admin stylesheet.'
);
consent_assert(isset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]), 'The portable settings screen must load the page-order controls.');

$defaults = Universal_Legal_Pages::default_options();
consent_assert($defaults['consent_enabled'] === false, 'Consent must remain opt-in for the site administrator.');
consent_assert($defaults['respect_gpc'] === true, 'Global Privacy Control should be respected by default.');
consent_assert($defaults['consent_page_ids'] === [], 'Consent links must be freely selected rather than assigned fixed page roles.');
consent_assert($defaults['consent_link_translations'] === [], 'Portable defaults must not assume multilingual legal-page links.');
consent_assert($defaults['banner_translations'] === [], 'Portable defaults must not assume a multilingual host.');
consent_assert(count($defaults['consent_strings']['actions']) === 6, 'The editable consent copy is missing action labels.');
consent_assert(isset($defaults['consent_strings']['dialog']['description'], $defaults['consent_strings']['categories']['marketing']['description'], $defaults['consent_strings']['error']['generic']), 'The editable consent copy contract is incomplete.');
consent_assert(isset($defaults['consent_strings']['categories']['external']['description']), 'The external-content category copy is missing.');
consent_assert(array_keys($defaults['consent_strings']['services']) === ['title', 'description', 'blocked', 'allow', 'unclassified'], 'The service-specific copy contract has the wrong shape.');
consent_assert($defaults['consent_string_translations'] === [], 'Portable defaults must not assume translated consent copy.');
consent_assert(array_keys($defaults['consent_categories']) === ['necessary'], 'A fresh install must not prefill optional consent categories.');
consent_assert(
    $defaults['ga4_category'] === ''
    && $defaults['gtm_category'] === ''
    && $defaults['google_ads_category'] === ''
    && $defaults['meta_pixel_category'] === '',
    'Predefined integrations must start without an implicit consent category.'
);
consent_assert($defaults['custom_integrations'] === [], 'Portable defaults must not assume trusted custom integrations.');
consent_assert($defaults['services'] === [], 'Portable defaults must not assume detected or configured services.');

$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($defaults, ['consent_enabled' => true]);
consent_assert(!isset($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION]), 'The fresh-install service test did not start with an empty detected registry.');
$fresh_config_before_content = Universal_Legal_Pages::get_public_consent_config();
consent_assert(array_column($fresh_config_before_content['categories'], 'id') === ['necessary'], 'The fresh public registry exposed optional categories that were never created.');
consent_assert(array_column($fresh_config_before_content['services'], 'id') === ['vimeo', 'youtube'], 'A fresh public config must expose known managed embed services before content renders.');
consent_assert(strpos($fresh_config_before_content['stylesheetUrl'], 'https://example.test/wp-content/plugins/universal-legal-pages/assets/css/consent-manager.css?ver=') === 0, 'The public manager must use the cache-busted plugin stylesheet when the theme has no override.');
consent_assert($theme_file_requests[0] === ['path', Universal_Legal_Pages::CONSENT_THEME_STYLESHEET], 'Consent styling must inspect the documented theme-relative override path.');

$theme_file_path = __DIR__ . '/../../src/plugins/universal-legal-pages/template/assets/css/consent-manager.css';
$theme_file_uri = 'https://example.test/wp-content/themes/project/universal-legal-pages/consent-manager.css';
$theme_file_requests = [];
$theme_stylesheet_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(strpos($theme_stylesheet_config['stylesheetUrl'], $theme_file_uri . '?ver=') === 0, 'A readable theme stylesheet must replace the plugin stylesheet inside the consent Shadow DOM.');
consent_assert($theme_file_requests === [
    ['path', Universal_Legal_Pages::CONSENT_THEME_STYLESHEET],
    ['uri', Universal_Legal_Pages::CONSENT_THEME_STYLESHEET],
], 'Theme stylesheet path and URI resolution diverged.');

$filters['universal_legal_pages_consent_stylesheet_url'] = [];
add_filter('universal_legal_pages_consent_stylesheet_url', function($url, $context){
    consent_assert($context['source'] === 'theme', 'The stylesheet filter did not receive the resolved source context.');
    consent_assert($context['themeRelativePath'] === Universal_Legal_Pages::CONSENT_THEME_STYLESHEET, 'The stylesheet filter received the wrong theme override contract.');
    return 'https://cdn.example.test/privacy/consent.css?v=project';
}, 10, 2);
$filtered_stylesheet_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($filtered_stylesheet_config['stylesheetUrl'] === 'https://cdn.example.test/privacy/consent.css?v=project', 'A valid filtered consent stylesheet URL was not exposed publicly.');

$filters['universal_legal_pages_consent_stylesheet_url'] = [];
add_filter('universal_legal_pages_consent_stylesheet_url', function(){
    return 'javascript:alert(1)';
});
$invalid_stylesheet_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(strpos($invalid_stylesheet_config['stylesheetUrl'], $theme_file_uri . '?ver=') === 0, 'An unsafe filtered stylesheet URL must fall back to the resolved local stylesheet.');

$filters['universal_legal_pages_consent_stylesheet_url'] = [];
$theme_file_path = '';
$theme_file_uri = '';
$theme_file_requests = [];
$fresh_youtube_placeholder = Universal_Legal_Pages::filter_external_iframes('<iframe src="https://www.youtube.com/embed/AbCdEf12345"></iframe>');
consent_assert(strpos($fresh_youtube_placeholder, 'data-ulc-service="youtube"') !== false && strpos($fresh_youtube_placeholder, 'data-ulc-allow-service="youtube"') !== false, 'A first-render YouTube iframe was not immediately activable with the already emitted fresh config.');
unset($options[Universal_Legal_Pages::OPTION_NAME], $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION]);

$options[Universal_Legal_Pages::OPTION_NAME] = [
    'privacy_page_id' => 10,
    'cookies_page_id' => 0,
    'terms_page_id' => 11,
    'secret_api_key' => 'PRIVATE',
];
$migrated_options = Universal_Legal_Pages::get_options();
consent_assert($migrated_options['consent_page_ids'] === [10, 11], 'Legacy fixed page selections were not migrated into the flexible consent-link list.');
consent_assert(!array_key_exists('privacy_page_id', $migrated_options) && !array_key_exists('secret_api_key', $migrated_options), 'Legacy or unknown stored keys escaped the current option allowlist.');

$legacy_analytics_category_id = 'category-1111111111111111';
$legacy_marketing_category_id = 'category-2222222222222222';
$legacy_custom_integration_id = 'custom-3333333333333333';
$legacy_purpose_options = array_diff_key($defaults, array_flip([
    'ga4_category',
    'gtm_category',
    'google_ads_category',
    'meta_pixel_category',
]));
$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($legacy_purpose_options, [
    'consent_categories' => [
        'necessary' => [
            'label' => 'Necessary',
            'description' => 'Required for the site.',
            'purpose' => 'necessary',
            'translations' => [],
        ],
        $legacy_analytics_category_id => [
            'label' => 'Site insights',
            'description' => 'Measures use of the site.',
            'purpose' => 'analytics',
            'translations' => [],
        ],
        $legacy_marketing_category_id => [
            'label' => 'Advertising',
            'description' => 'Allows advertising services.',
            'purpose' => 'marketing',
            'translations' => [],
        ],
    ],
    'ga4_measurement_id' => 'G-ABCD1234',
    'gtm_container_id' => 'GTM-ABCD1234',
    'google_ads_id' => 'AW-1234567890',
    'meta_pixel_id' => '1234567890',
    'custom_integrations' => [
        $legacy_custom_integration_id => [
            'label' => 'Legacy audience tool',
            'category' => $legacy_analytics_category_id,
            'script_url' => 'https://legacy.example.test/tool.js',
            'init_code' => '',
        ],
    ],
]);
$purpose_migrated_options = Universal_Legal_Pages::get_options();
consent_assert(!array_key_exists('purpose', $purpose_migrated_options['consent_categories'][$legacy_analytics_category_id]), 'A legacy technical purpose remained attached to a consent category.');
consent_assert(
    $purpose_migrated_options['ga4_category'] === $legacy_analytics_category_id
    && $purpose_migrated_options['gtm_category'] === $legacy_analytics_category_id
    && $purpose_migrated_options['google_ads_category'] === $legacy_marketing_category_id
    && $purpose_migrated_options['meta_pixel_category'] === $legacy_marketing_category_id,
    'Legacy category purposes were not converted into explicit predefined-integration assignments.'
);
consent_assert(
    array_keys($purpose_migrated_options['custom_integrations'][$legacy_custom_integration_id]) === ['label', 'category', 'script_url', 'init_code'],
    'A legacy custom integration retained the obsolete per-integration privacy behavior.'
);
$options = [];

$unassigned_saved = Universal_Legal_Pages::sanitize_options([
    'ga4_measurement_id' => 'g-abcd1234',
    'ga4_category' => '',
    'gtm_container_id' => 'gtm-abcd1234',
    'gtm_category' => '',
    'google_ads_id' => 'aw-1234567890',
    'google_ads_category' => '',
    'meta_pixel_id' => '1234567890',
    'meta_pixel_category' => '',
    'custom_integrations_present' => '1',
    'custom_integrations' => [[
        'id' => '',
        'label' => 'Inactive audience tool',
        'category' => '',
        'script_url' => 'https://inactive.example.test/tool.js',
        'init_code' => '',
    ]],
]);
consent_assert($unassigned_saved['ga4_measurement_id'] === 'G-ABCD1234' && $unassigned_saved['ga4_category'] === '', 'An unassigned predefined integration could not be stored safely.');
$unassigned_custom_values = array_values($unassigned_saved['custom_integrations']);
consent_assert(count($unassigned_custom_values) === 1 && $unassigned_custom_values[0]['category'] === '', 'An unassigned custom integration could not be stored safely.');
$options[Universal_Legal_Pages::OPTION_NAME] = $unassigned_saved;
$unassigned_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($unassigned_config['integrations'] === [
    'googleAnalytics' => '',
    'googleTagManager' => '',
    'googleAds' => '',
    'metaPixel' => '',
], 'An unassigned predefined integration reached the active public adapter configuration.');
consent_assert($unassigned_config['customIntegrations'] === [], 'An unassigned custom integration reached the executable public configuration.');
consent_assert(array_column($unassigned_config['services'], 'id') === ['vimeo', 'youtube'], 'An unassigned integration was published as an activatable service.');

$standard_category_registry = [];
foreach(['necessary', 'preferences', 'analytics', 'marketing', 'external'] as $category_id){
    $standard_category_registry[$category_id] = [
        'label' => $defaults['consent_strings']['categories'][$category_id]['label'],
        'description' => $defaults['consent_strings']['categories'][$category_id]['description'],
        'translations' => [],
    ];
}
$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($defaults, [
    'consent_categories' => $standard_category_registry,
]);

$valid_input = [
    'consent_enabled' => '1',
    'banner_title' => 'Choix de confidentialité',
    'banner_message' => "Mesure facultative.\nAucun suivi avant votre choix.",
    'consent_page_ids' => ['10', '11'],
    'terms_page_id' => '11',
    'terms_required' => '1',
    'policy_version' => '2026.08-1',
    'duration_days' => '365',
    'respect_gpc' => '1',
    'show_revisit_button' => '1',
    'ga4_measurement_id' => 'g-abcd1234',
    'ga4_category' => 'analytics',
    'gtm_container_id' => 'gtm-abcd1234',
    'gtm_category' => 'analytics',
    'google_ads_id' => 'aw-1234567890',
    'google_ads_category' => 'marketing',
    'meta_pixel_id' => '1234567890',
    'meta_pixel_category' => 'marketing',
    'role' => 'administrator',
    'secret_api_key' => 'PRIVATE',
];

$sanitized = Universal_Legal_Pages::sanitize_options($valid_input);

consent_assert($sanitized['consent_enabled'] === true, 'Consent activation did not canonicalize to boolean true.');
consent_assert(strpos($sanitized['banner_title'], '<') === false, 'Banner title markup was not removed.');
consent_assert(strpos($sanitized['banner_message'], '<') === false, 'Banner message markup was not removed.');
consent_assert($sanitized['banner_title'] === 'Choix de confidentialité', 'A valid plain-text title was not retained.');
consent_assert($sanitized['consent_page_ids'] === [10, 11] && $sanitized['terms_page_id'] === 11, 'Published legal page references were rejected.');
consent_assert($sanitized['terms_required'] === true, 'Terms acknowledgement was not retained with a valid page.');
consent_assert($sanitized['policy_version'] === '2026.08-1', 'Policy version canonicalization failed.');
consent_assert($sanitized['duration_days'] === 365, 'Consent duration canonicalization failed.');
consent_assert($sanitized['ga4_measurement_id'] === 'G-ABCD1234', 'GA4 ID canonicalization failed.');
consent_assert($sanitized['gtm_container_id'] === 'GTM-ABCD1234', 'GTM ID canonicalization failed.');
consent_assert($sanitized['google_ads_id'] === 'AW-1234567890', 'Google Ads ID canonicalization failed.');
consent_assert($sanitized['meta_pixel_id'] === '1234567890', 'Meta Pixel ID canonicalization failed.');
consent_assert(!array_key_exists('role', $sanitized) && !array_key_exists('secret_api_key', $sanitized), 'Unknown or privileged fields reached the option allowlist.');
consent_assert($sanitized['services'] === [], 'Missing service settings must preserve the empty registry configuration.');
consent_assert($sanitized['custom_integrations'] === [], 'Missing custom-integration transport must preserve the empty canonical map.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$necessary_integrations = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'ga4_category' => 'necessary',
    'gtm_category' => 'necessary',
    'google_ads_category' => 'necessary',
    'meta_pixel_category' => 'necessary',
]));
$options[Universal_Legal_Pages::OPTION_NAME] = $necessary_integrations;
$necessary_integration_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(
    $necessary_integration_config['integrationCategories'] === [
        'googleAnalytics' => 'necessary',
        'googleTagManager' => 'necessary',
        'googleAds' => 'necessary',
        'metaPixel' => 'necessary',
    ],
    'The required category was rejected by a predefined integration.'
);
foreach($necessary_integration_config['services'] as $necessary_service){
    if(in_array($necessary_service['id'], ['google-analytics-4', 'google-ads-remarketing', 'meta-pixel'], true)){
        consent_assert($necessary_service['category'] === 'necessary', 'A predefined integration lost its required-category assignment in public configuration.');
    }
}
$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;

$standalone_category_input = array_merge($valid_input, [
    'consent_categories_present' => '1',
    'consent_categories' => [
        [
            'id' => 'necessary',
            'label' => 'Essential storage',
            'description' => 'Required to operate and secure the site.',
        ],
        [
            'id' => 'analytics',
            'label' => 'Anonymous statistics',
            'description' => 'Helps us understand site usage.',
        ],
        [
            'id' => 'category-0123456789abcdef',
            'label' => 'Videos',
            'description' => 'Allows video players selected by the visitor.',
        ],
    ],
    'google_ads_category' => 'category-0123456789abcdef',
    'meta_pixel_category' => 'category-0123456789abcdef',
]);
$standalone_category_saved = Universal_Legal_Pages::sanitize_options($standalone_category_input);
consent_assert(
    array_keys($standalone_category_saved['consent_categories']) === ['necessary', 'analytics', 'category-0123456789abcdef'],
    'Default and custom consent categories were not created, edited, deleted, and ordered through one registry.'
);
consent_assert(
    $standalone_category_saved['consent_categories']['analytics']['label'] === 'Anonymous statistics'
    && $standalone_category_saved['consent_categories']['category-0123456789abcdef']['description'] === 'Allows video players selected by the visitor.'
    && array_keys($standalone_category_saved['consent_categories']['category-0123456789abcdef']) === ['label', 'description', 'translations']
    && $standalone_category_saved['consent_categories']['category-0123456789abcdef']['translations'] === [],
    'A valid standalone category definition was not stored exactly.'
);

$options[Universal_Legal_Pages::OPTION_NAME] = $standalone_category_saved;
$settings_errors = [];
$category_with_technical_purpose = $standalone_category_input;
$category_with_technical_purpose['consent_categories'][2]['purpose'] = 'external';
$category_with_technical_purpose_saved = Universal_Legal_Pages::sanitize_options($category_with_technical_purpose);
consent_assert(
    $category_with_technical_purpose_saved['consent_categories'] === $standalone_category_saved['consent_categories'],
    'A category accepted the obsolete technical-purpose field.'
);
consent_assert(end($settings_errors)['code'] === 'invalid_consent_categories', 'An injected category purpose did not emit the stable category error.');

$settings_errors = [];
$missing_necessary_category_input = $standalone_category_input;
array_shift($missing_necessary_category_input['consent_categories']);
$missing_necessary_category_saved = Universal_Legal_Pages::sanitize_options($missing_necessary_category_input);
consent_assert(
    $missing_necessary_category_saved['consent_categories'] === $standalone_category_saved['consent_categories'],
    'Removing the required category did not preserve the complete previous registry atomically.'
);
consent_assert(end($settings_errors)['code'] === 'invalid_consent_categories', 'An invalid category registry did not emit its stable error code.');

$settings_errors = [];
$scriptable_category_input = $standalone_category_input;
$scriptable_category_input['consent_categories'][1]['label'] = '<strong>Statistics</strong>';
$scriptable_category_saved = Universal_Legal_Pages::sanitize_options($scriptable_category_input);
consent_assert(
    $scriptable_category_saved['consent_categories'] === $standalone_category_saved['consent_categories'],
    'Scriptable category copy partially replaced the previous category registry.'
);
consent_assert(end($settings_errors)['code'] === 'invalid_consent_categories', 'Scriptable category copy did not emit the stable category error.');

$settings_errors = [];
$oversized_category_input = $standalone_category_input;
$oversized_category_input['consent_categories'] = [$standalone_category_input['consent_categories'][0]];
for($category_fixture_index = 0; $category_fixture_index < 16; $category_fixture_index++){
    $oversized_category_input['consent_categories'][] = [
        'id' => 'category-' . str_pad(dechex($category_fixture_index), 16, '0', STR_PAD_LEFT),
        'label' => 'Optional ' . $category_fixture_index,
        'description' => 'Bounded optional category fixture.',
    ];
}
$oversized_category_saved = Universal_Legal_Pages::sanitize_options($oversized_category_input);
consent_assert(
    $oversized_category_saved['consent_categories'] === $standalone_category_saved['consent_categories'],
    'A category registry above the documented limit replaced the previous registry.'
);
consent_assert(end($settings_errors)['code'] === 'invalid_consent_categories', 'An oversized category registry did not emit the stable category error.');

$referenced_category_options = $standalone_category_saved;
$referenced_category_options['custom_integrations'] = [
    'custom-0123456789abcdef' => [
        'label' => 'Referenced analytics service',
        'category' => 'analytics',
        'script_url' => 'https://analytics.example.test/tag.js',
        'init_code' => '',
    ],
];
$options[Universal_Legal_Pages::OPTION_NAME] = $referenced_category_options;
$settings_errors = [];
$referenced_category_delete_input = $standalone_category_input;
array_splice($referenced_category_delete_input['consent_categories'], 1, 1);
$referenced_category_delete_saved = Universal_Legal_Pages::sanitize_options($referenced_category_delete_input);
consent_assert(
    isset($referenced_category_delete_saved['consent_categories']['analytics']),
    'A consent category still referenced by a custom integration was deleted.'
);
consent_assert(end($settings_errors)['code'] === 'category_in_use', 'A referenced category deletion did not emit its stable dependency error.');

$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($defaults, [
    'consent_categories' => $standard_category_registry,
]);
$settings_errors = [];
$nonce_valid = true;
$nonce_checks = [];
$_POST = [
    Universal_Legal_Pages::OPTION_NAME => $valid_input,
    'ulp_save_nonce' => 'valid-nonce',
];
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && $ajax_response->success && $ajax_response->status === 200, 'An authorized asynchronous settings save did not return success.');
consent_assert(end($nonce_checks)['action'] === Universal_Legal_Pages::AJAX_SAVE_ACTION && end($nonce_checks)['query_arg'] === 'ulp_save_nonce', 'The asynchronous save did not verify its dedicated nonce.');
consent_assert($options[Universal_Legal_Pages::OPTION_NAME]['ga4_measurement_id'] === 'G-ABCD1234', 'The asynchronous save bypassed the canonical settings sanitizer.');
consent_assert(!isset($options[Universal_Legal_Pages::OPTION_NAME]['role'], $options[Universal_Legal_Pages::OPTION_NAME]['secret_api_key']), 'An asynchronous mass-assignment payload reached storage.');

$stored_before_denial = $options[Universal_Legal_Pages::OPTION_NAME];
$can_manage_options = false;
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && !$ajax_response->success && $ajax_response->status === 403, 'A user without manage_options was not denied by the asynchronous save endpoint.');
consent_assert($options[Universal_Legal_Pages::OPTION_NAME] === $stored_before_denial, 'An unauthorized asynchronous request changed stored settings.');

$can_manage_options = true;
$nonce_valid = false;
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && !$ajax_response->success && $ajax_response->status === 403, 'An asynchronous save with an invalid nonce was not denied.');
consent_assert($options[Universal_Legal_Pages::OPTION_NAME] === $stored_before_denial, 'A nonce-invalid asynchronous request changed stored settings.');

$nonce_valid = true;
$settings_errors = [];
$_POST = [Universal_Legal_Pages::OPTION_NAME => 'invalid-shape', 'ulp_save_nonce' => 'valid-nonce'];
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && !$ajax_response->success && $ajax_response->status === 422, 'Malformed asynchronous settings did not return a bounded validation response.');
consent_assert($ajax_response->data['errors'][0]['code'] === 'invalid_settings_shape', 'The asynchronous validation response lost its stable field error code.');
consent_assert($options[Universal_Legal_Pages::OPTION_NAME] === $stored_before_denial, 'Malformed asynchronous settings changed the stored option.');

$_POST = [];
$options = [Universal_Legal_Pages::OPTION_NAME => $sanitized];
$settings_errors = [];

$custom_definition_one = [
    'id' => '',
    'label' => 'Customer chat',
    'category' => 'preferences',
    'script_url' => 'HTTPS://CDN.VENDOR-CHAT.TEST:443/widget.js?mode=public',
    'init_code' => 'window.vendorChat = { ready: true };</script><script>alert("x")</script>',
];
$custom_definition_two = [
    'id' => '',
    'label' => 'Video partner',
    'category' => 'external',
    'script_url' => 'https://media.partner.test/player.js',
    'init_code' => '',
];
$custom_save_input = array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => [$custom_definition_one, $custom_definition_two],
]);
$base_custom_version = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
$custom_saved = Universal_Legal_Pages::sanitize_options($custom_save_input);

consent_assert(count($custom_saved['custom_integrations']) === 2, 'Two valid custom integrations were not stored.');
foreach($custom_saved['custom_integrations'] as $custom_id => $custom_definition){
    consent_assert(preg_match('/\Acustom-[a-f0-9]{16}\z/', $custom_id) === 1, 'A generated custom integration ID is not canonical.');
    consent_assert(array_keys($custom_definition) === ['label', 'category', 'script_url', 'init_code'], 'A stored custom integration has the wrong exact shape.');
}

$custom_one_id = '';
$custom_two_id = '';
foreach($custom_saved['custom_integrations'] as $custom_id => $custom_definition){
    if($custom_definition['label'] === 'Customer chat'){
        $custom_one_id = $custom_id;
        consent_assert($custom_definition['script_url'] === 'https://cdn.vendor-chat.test/widget.js?mode=public', 'The custom script URL was not canonicalized.');
        consent_assert($custom_definition['init_code'] === $custom_definition_one['init_code'], 'Approved initialization code was altered before storage.');
    }elseif($custom_definition['label'] === 'Video partner'){
        $custom_two_id = $custom_id;
    }
}
consent_assert($custom_one_id !== '' && $custom_two_id !== '', 'The canonical custom integrations could not be addressed by their generated IDs.');

$expected_custom_one_record = [
    'label' => 'Customer chat',
    'category' => 'preferences',
    'script_url' => 'https://cdn.vendor-chat.test/widget.js?mode=public',
    'init_code' => $custom_definition_one['init_code'],
];
$expected_custom_one_id = 'custom-' . substr(hash('sha256', json_encode($expected_custom_one_record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 16);
consent_assert($custom_one_id === $expected_custom_one_id, 'The deterministic custom integration ID did not use the canonical definition material.');
$custom_saved_again = Universal_Legal_Pages::sanitize_options($custom_save_input);
consent_assert(array_keys($custom_saved_again['custom_integrations']) === array_keys($custom_saved['custom_integrations']), 'Equivalent canonical input generated unstable custom integration IDs.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$settings_errors = [];
$nonce_valid = true;
$_POST = [Universal_Legal_Pages::OPTION_NAME => $custom_save_input, 'ulp_save_nonce' => 'valid-nonce'];
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && $ajax_response->success, 'A valid custom integration list failed through the asynchronous settings endpoint.');
consent_assert(count($ajax_response->data['customIntegrations']) === 2, 'The asynchronous save did not return canonical IDs for new custom integrations.');
$ajax_custom_keys = array_keys($options[Universal_Legal_Pages::OPTION_NAME]['custom_integrations']);
$repeat_custom_input = $custom_save_input;

foreach($ajax_response->data['customIntegrations'] as $identity){
    $repeat_custom_input['custom_integrations'][$identity['index']]['id'] = $identity['id'];
}

$settings_errors = [];
$_POST = [Universal_Legal_Pages::OPTION_NAME => $repeat_custom_input, 'ulp_save_nonce' => 'valid-nonce'];
$ajax_response = null;

try{
    Universal_Legal_Pages::ajax_save_settings();
}catch(ULP_Test_JSON_Response $response){
    $ajax_response = $response;
}

consent_assert($ajax_response instanceof ULP_Test_JSON_Response && $ajax_response->success, 'A repeated asynchronous save rejected canonical custom integration IDs.');
consent_assert(array_keys($options[Universal_Legal_Pages::OPTION_NAME]['custom_integrations']) === $ajax_custom_keys, 'Repeated asynchronous saves changed custom integration identity.');

$_POST = [];
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_saved;
$custom_config = Universal_Legal_Pages::get_public_consent_config();
$custom_services_by_id = [];
foreach($custom_config['services'] as $service){
    $custom_services_by_id[$service['id']] = $service;
}
consent_assert(isset($custom_services_by_id[$custom_one_id], $custom_services_by_id[$custom_two_id]), 'Custom integrations were not published as individual managed services.');
consent_assert($custom_services_by_id[$custom_one_id] === [
    'id' => $custom_one_id,
    'label' => 'Customer chat',
    'category' => 'preferences',
    'purpose' => 'preferences',
    'domains' => ['cdn.vendor-chat.test'],
    'kind' => 'integration',
    'managed' => true,
], 'A custom integration service has the wrong exact public contract.');
consent_assert(count($custom_config['services']) <= Universal_Legal_Pages::MAX_SERVICES, 'Custom integrations exceeded the total public service limit.');
consent_assert(array_keys($custom_config['customIntegrations'][0]) === ['id', 'scriptUrl', 'initCode'], 'The public custom integration payload has the wrong exact shape.');
$public_custom_by_id = [];
foreach($custom_config['customIntegrations'] as $definition){
    $public_custom_by_id[$definition['id']] = $definition;
}
consent_assert($public_custom_by_id[$custom_one_id]['scriptUrl'] === 'https://cdn.vendor-chat.test/widget.js?mode=public', 'The canonical custom script URL is missing from public configuration.');
consent_assert($public_custom_by_id[$custom_one_id]['initCode'] === $custom_definition_one['init_code'], 'The approved initialization code is missing from public configuration.');
consent_assert(strlen(json_encode($custom_config['customIntegrations'])) <= Universal_Legal_Pages::MAX_CUSTOM_PUBLIC_BYTES, 'The public custom-integration payload exceeded its byte budget.');
consent_assert($custom_config['serviceRegistryVersion'] !== $base_custom_version, 'Adding custom executable definitions did not invalidate prior consent.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$required_custom_definition = array_merge($custom_definition_one, [
    'label' => 'Required project loader',
    'category' => 'necessary',
    'script_url' => 'https://required.vendor.test/loader.js',
    'init_code' => '',
]);
$required_custom_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => [$required_custom_definition],
]));
$options[Universal_Legal_Pages::OPTION_NAME] = $required_custom_saved;
$required_custom_config = Universal_Legal_Pages::get_public_consent_config();
$required_custom_service = array_values(array_filter($required_custom_config['services'], function($service){
    return $service['label'] === 'Required project loader';
}));
consent_assert(
    count($required_custom_service) === 1
    && $required_custom_service[0]['category'] === 'necessary'
    && $required_custom_service[0]['purpose'] === 'necessary',
    'A custom integration could not derive its always-required behavior from the selected category.'
);
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_saved;

$custom_existing_transport = [];
foreach($custom_saved['custom_integrations'] as $custom_id => $definition){
    $custom_existing_transport[] = array_merge(['id' => $custom_id], $definition);
}
$custom_updated_transport = $custom_existing_transport;
foreach($custom_updated_transport as &$definition){
    if($definition['id'] === $custom_one_id){
        $definition['init_code'] .= '\nwindow.vendorChat.started = true;';
    }
}
unset($definition);
$custom_updated = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $custom_updated_transport,
]));
consent_assert(isset($custom_updated['custom_integrations'][$custom_one_id]), 'Editing a canonical existing custom integration changed its ID.');
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_updated;
$custom_updated_version = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($custom_updated_version !== $custom_config['serviceRegistryVersion'], 'Changing custom initialization code did not invalidate prior consent.');

$custom_url_transport = $custom_updated_transport;
foreach($custom_url_transport as &$definition){
    if($definition['id'] === $custom_one_id){
        $definition['script_url'] = 'https://cdn.vendor-chat.test/widget-v2.js?mode=public';
    }
}
unset($definition);
$custom_url_updated = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $custom_url_transport,
]));
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_url_updated;
$custom_url_version = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($custom_url_version !== $custom_updated_version, 'Changing a custom script URL on the same domain did not invalidate prior consent.');

$custom_category_transport = $custom_url_transport;
foreach($custom_category_transport as &$definition){
    if($definition['id'] === $custom_one_id){
        $definition['category'] = 'analytics';
    }
}
unset($definition);
$custom_category_updated = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $custom_category_transport,
]));
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_category_updated;
$custom_category_version = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($custom_category_version !== $custom_url_version, 'Changing a custom integration category did not invalidate prior consent.');

$custom_label_transport = $custom_category_transport;
foreach($custom_label_transport as &$definition){
    if($definition['id'] === $custom_one_id){
        $definition['label'] = 'Customer support chat';
    }
}
unset($definition);
$custom_label_updated = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $custom_label_transport,
]));
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_label_updated;
consent_assert(Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'] !== $custom_category_version, 'Changing a custom registry label did not invalidate prior consent.');

$options[Universal_Legal_Pages::OPTION_NAME] = $custom_saved;
$settings_errors = [];
$custom_truncated = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
]));
consent_assert($custom_truncated['custom_integrations'] === $custom_saved['custom_integrations'], 'A marker with a missing custom-integration payload did not preserve the complete previous map.');
consent_assert($settings_errors[0]['code'] === 'invalid_custom_integrations', 'A truncated custom-integration payload did not emit the stable validation error.');

$settings_errors = [];
$custom_deleted = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => '',
]));
consent_assert($custom_deleted['custom_integrations'] === [], 'The explicit empty sentinel did not delete every custom integration.');

$settings_errors = [];
$custom_without_marker = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations' => [],
]));
consent_assert($custom_without_marker['custom_integrations'] === $custom_saved['custom_integrations'], 'A missing marker mutated custom integrations.');

$can_unfiltered_html = false;
$settings_errors = [];
$custom_forbidden = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => [],
]));
consent_assert($custom_forbidden['custom_integrations'] === $custom_saved['custom_integrations'], 'A user without unfiltered_html changed trusted custom code.');
consent_assert($settings_errors[0]['code'] === 'forbidden_custom_integrations', 'Missing unfiltered_html did not emit the stable custom-integration authorization error.');

$can_unfiltered_html = true;
$can_manage_options = false;
$settings_errors = [];
$custom_without_settings_capability = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => [],
]));
consent_assert($custom_without_settings_capability['custom_integrations'] === $custom_saved['custom_integrations'], 'A direct sanitizer call without manage_options changed trusted custom code.');
consent_assert($settings_errors[0]['code'] === 'forbidden_custom_integrations', 'Missing manage_options did not emit the stable custom-integration authorization error.');
$can_manage_options = true;

$invalid_custom_cases = [
    'invalid marker' => ['custom_integrations_present' => 'yes', 'custom_integrations' => []],
    'wrong list type' => ['custom_integrations_present' => '1', 'custom_integrations' => 'invalid'],
    'non-list keys' => ['custom_integrations_present' => '1', 'custom_integrations' => [2 => $custom_definition_one]],
    'too many items' => ['custom_integrations_present' => '1', 'custom_integrations' => array_fill(0, Universal_Legal_Pages::MAX_CUSTOM_INTEGRATIONS + 1, $custom_definition_one)],
    'scalar item' => ['custom_integrations_present' => '1', 'custom_integrations' => ['invalid']],
    'unknown field' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['secret' => 'no'])]],
    'missing field' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_diff_key($custom_definition_one, ['init_code' => true])]],
    'nested label' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['label' => ['Chat']])]],
    'nested category' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['category' => ['preferences']])]],
    'obsolete purpose field' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['purpose' => 'preferences'])]],
    'nested URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => ['https://cdn.vendor-chat.test/widget.js']])]],
    'nested initialization code' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['init_code' => ['window.ok=true;']])]],
    'non-string ID' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['id' => 12])]],
    'empty label' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['label' => '  '])]],
    'oversized Unicode label' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['label' => str_repeat('😀', 81)])]],
    'invalid category' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['category' => 'unclassified'])]],
    'forged ID' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['id' => 'custom-aaaaaaaaaaaaaaaa'])]],
    'built-in ID collision' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['id' => 'youtube'])]],
    'insecure URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'http://cdn.vendor-chat.test/widget.js'])]],
    'credentials in URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://user:pass@cdn.vendor-chat.test/widget.js'])]],
    'fragment in URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/widget.js#start'])]],
    'whitespace in URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/widget path.js'])]],
    'backslash in URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test\\widget.js'])]],
    'markup delimiter in URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/widget.js"onload="alert(1)'])]],
    'malformed percent encoding' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/widget%ZZ.js'])]],
    'ambiguous path segments' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/assets/../widget.js'])]],
    'invalid host' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://bad_host.test/widget.js'])]],
    'oversized URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/' . str_repeat('a', 2049)])]],
    'NUL initialization code' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['init_code' => "window.ok=true;\0window.bad=true;"])]],
    'invalid UTF-8 initialization code' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['init_code' => "\xC3\x28"])]],
    'oversized initialization code' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['init_code' => str_repeat('x', Universal_Legal_Pages::MAX_CUSTOM_INIT_CODE_BYTES + 1)])]],
    'duplicate existing ID' => ['custom_integrations_present' => '1', 'custom_integrations' => [$custom_existing_transport[0], $custom_existing_transport[0]]],
    'duplicate canonical URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [$custom_definition_one, array_merge($custom_definition_two, ['script_url' => $custom_definition_one['script_url']])]],
    'duplicate percent-canonical URL' => ['custom_integrations_present' => '1', 'custom_integrations' => [array_merge($custom_definition_one, ['script_url' => 'https://cdn.vendor-chat.test/wid%67et.js?mode=public']), array_merge($custom_definition_two, ['script_url' => 'https://cdn.vendor-chat.test/widget.js?mode=public'])]],
    'duplicate new definition' => ['custom_integrations_present' => '1', 'custom_integrations' => [$custom_definition_one, $custom_definition_one]],
];

foreach($invalid_custom_cases as $case => $custom_case){
    $settings_errors = [];
    $invalid_custom_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, $custom_case));
    consent_assert($invalid_custom_saved['custom_integrations'] === $custom_saved['custom_integrations'], 'The ' . $case . ' request partially replaced the previous custom integrations.');
    consent_assert(isset($settings_errors[0]) && $settings_errors[0]['code'] === 'invalid_custom_integrations', 'The ' . $case . ' request did not emit the stable custom integration error code.');
}

$maximum_custom_transport = [];
for($custom_index = 0; $custom_index < Universal_Legal_Pages::MAX_CUSTOM_INTEGRATIONS; $custom_index++){
    $maximum_custom_transport[] = [
        'id' => '',
        'label' => 'Bounded service ' . $custom_index,
        'category' => 'preferences',
        'script_url' => 'https://bounded-' . $custom_index . '.vendor.test/service.js',
        'init_code' => '',
    ];
}
$maximum_custom_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $maximum_custom_transport,
]));
consent_assert(count($maximum_custom_saved['custom_integrations']) === Universal_Legal_Pages::MAX_CUSTOM_INTEGRATIONS, 'The exact maximum number of custom integrations was rejected.');
$options[Universal_Legal_Pages::OPTION_NAME] = $maximum_custom_saved;
$maximum_custom_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(count($maximum_custom_config['customIntegrations']) === Universal_Legal_Pages::MAX_CUSTOM_INTEGRATIONS, 'The exact maximum custom integration list did not reach public configuration.');
consent_assert(count($maximum_custom_config['services']) <= Universal_Legal_Pages::MAX_SERVICES, 'The exact maximum custom list exceeded the shared public service bound.');
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_saved;

$hex_expansion_code = str_repeat("<>&'\"", 1638);
consent_assert(strlen($hex_expansion_code) <= Universal_Legal_Pages::MAX_CUSTOM_INIT_CODE_BYTES, 'The JSON_HEX expansion fixture exceeds the per-integration source limit.');
$hex_expansion_transport = [];
for($custom_index = 0; $custom_index < Universal_Legal_Pages::MAX_CUSTOM_INTEGRATIONS; $custom_index++){
    $hex_expansion_transport[] = [
        'id' => '',
        'label' => 'Expanded service ' . $custom_index,
        'category' => 'external',
        'script_url' => 'https://expanded-' . $custom_index . '.vendor.test/service.js',
        'init_code' => $hex_expansion_code,
    ];
}
$settings_errors = [];
$hex_expansion_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $hex_expansion_transport,
]));
consent_assert($hex_expansion_saved['custom_integrations'] === $custom_saved['custom_integrations'], 'A custom payload exceeding 128 KiB after JSON_HEX expansion replaced the previous map.');
consent_assert($settings_errors[0]['code'] === 'invalid_custom_integrations', 'An oversized JSON_HEX public payload did not emit the stable custom integration error.');

$detected_registry_before_custom_collision = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] ?? [];
$options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION][$custom_one_id] = [
    'domains' => ['collision.vendor.test'],
    'handles' => [],
    'kind' => 'iframe',
    'managed' => true,
];
$settings_errors = [];
$detected_collision_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'custom_integrations_present' => '1',
    'custom_integrations' => $custom_existing_transport,
]));
consent_assert($detected_collision_saved['custom_integrations'] === $custom_saved['custom_integrations'], 'A custom ID collision with the detected registry replaced the previous canonical map.');
consent_assert($settings_errors[0]['code'] === 'invalid_custom_integrations', 'A detected-registry ID collision did not emit the stable custom integration error.');
$options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] = $detected_registry_before_custom_collision;

$corrupt_custom_storage = $custom_saved;
$corrupt_custom_storage['custom_integrations'][$custom_one_id]['protected'] = 'no';
$options[Universal_Legal_Pages::OPTION_NAME] = $corrupt_custom_storage;
consent_assert(Universal_Legal_Pages::get_options()['custom_integrations'] === [], 'A malformed stored custom map escaped strict read normalization.');
$options[Universal_Legal_Pages::OPTION_NAME] = $custom_saved;

ob_start();
Universal_Legal_Pages::render_settings_page();
$custom_settings_page = ob_get_clean();
consent_assert(strpos($custom_settings_page, 'data-ulc-custom-integrations') !== false && strpos($custom_settings_page, 'data-ulc-custom-list') !== false, 'The accessible custom integration editor is missing its frontend hooks.');
consent_assert(strpos($custom_settings_page, 'data-ulc-custom-template') !== false && strpos($custom_settings_page, 'data-ulc-add-custom') !== false && strpos($custom_settings_page, 'data-ulc-remove-custom') !== false, 'The custom integration add/remove template contract is incomplete.');
consent_assert(strpos($custom_settings_page, 'data-max="12"') !== false && strpos($custom_settings_page, 'data-limit-message=') !== false, 'The custom integration editor does not expose its bounded UI contract.');
consent_assert(strpos($custom_settings_page, 'data-added-message=') !== false && strpos($custom_settings_page, 'data-removed-message=') !== false && strpos($custom_settings_page, 'data-ulc-custom-status') !== false, 'The custom integration editor is missing accessible translated status hooks.');
consent_assert(strpos($custom_settings_page, '[custom_integrations_present]') !== false, 'The explicit custom-integration deletion marker is missing.');
consent_assert(strpos($custom_settings_page, '[custom_integrations][0][id]') !== false && strpos($custom_settings_page, '[custom_integrations][0][script_url]') !== false && strpos($custom_settings_page, '[custom_integrations][0][init_code]') !== false, 'Persisted custom integration fields use the wrong Settings API names.');
consent_assert(strpos($custom_settings_page, '[custom_integrations][__INDEX__][label]') !== false, 'The custom integration template does not expose its replaceable index token.');
consent_assert(substr_count($custom_settings_page, '>Not selected</option>') >= 5, 'Predefined and custom integrations must always expose the explicit unassigned category choice.');
consent_assert(substr_count($custom_settings_page, '>Necessary</option>') >= 5, 'Every integration category selector must include the required category.');
consent_assert(strpos($custom_settings_page, 'data-not-selected-label="Not selected"') !== false, 'The dynamic category editor is missing its localized unassigned-option label.');
consent_assert(strpos($custom_settings_page, 'data-ulc-category-select="integration" required') === false, 'A custom integration still requires a category before it can be saved inactive.');
consent_assert(strpos($custom_settings_page, '[custom_integrations][0][purpose]') === false && strpos($custom_settings_page, 'Privacy behavior') === false, 'The custom integration editor still exposes the redundant privacy-behavior field.');
consent_assert(strpos($custom_settings_page, 'must never contain passwords, tokens, or other secrets') !== false && strpos($custom_settings_page, 'Necessary runs without asking') !== false && strpos($custom_settings_page, 'every other category waits until this individual service is accepted') !== false, 'The custom-code trust and category-gating notice is incomplete.');
consent_assert(strpos($custom_settings_page, '&lt;/script&gt;&lt;script&gt;') !== false, 'Initialization code was not escaped in its admin textarea context.');

$can_unfiltered_html = false;
ob_start();
Universal_Legal_Pages::render_settings_page();
$restricted_custom_settings_page = ob_get_clean();
consent_assert(strpos($restricted_custom_settings_page, 'permission to publish unfiltered code') !== false, 'The restricted custom-integration notice is missing.');
consent_assert(strpos($restricted_custom_settings_page, '[custom_integrations_present]') === false && strpos($restricted_custom_settings_page, '[custom_integrations]') === false && strpos($restricted_custom_settings_page, 'data-ulc-custom-template') === false, 'A user without unfiltered_html received mutable custom-integration fields.');
$can_unfiltered_html = true;

$inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE] = [];
Universal_Legal_Pages::enqueue_assets();
$custom_inline_config = $inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][1]['data'];
consent_assert(strpos($custom_inline_config, '</script>') === false && strpos($custom_inline_config, '<script>') === false, 'A custom initialization payload broke out of the inline JSON sink.');
consent_assert(strpos($custom_inline_config, '\\u003C') !== false, 'The inline custom integration payload was not JSON_HEX encoded.');
unset($enqueued_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE]);
$inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE] = [];

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$settings_errors = [];

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$editable_copy = $defaults['consent_strings'];
$editable_copy['title'] = 'Centre de confidentialité';
$editable_copy['actions']['revisit'] = 'Paramètres des témoins';
$editable_copy['dialog']['title'] = 'Choisir mes préférences';
$editable_copy['error']['generic'] = 'Réessayez dans quelques instants.';
$full_copy_input = array_merge($valid_input, ['consent_strings' => $editable_copy]);
$full_copy_saved = Universal_Legal_Pages::sanitize_options($full_copy_input);
consent_assert($full_copy_saved['consent_strings'] === $editable_copy, 'A valid complete standalone copy bundle was not stored exactly.');
consent_assert($full_copy_saved['banner_title'] === $editable_copy['title'], 'The legacy title mirror did not follow the complete copy bundle.');

$options[Universal_Legal_Pages::OPTION_NAME] = $full_copy_saved;
$settings_errors = [];
$invalid_copy_input = $full_copy_input;
$invalid_copy_input['consent_strings']['actions']['revisit'] = '<strong>Cookies</strong>';
$invalid_copy_saved = Universal_Legal_Pages::sanitize_options($invalid_copy_input);
consent_assert($invalid_copy_saved['consent_strings'] === $full_copy_saved['consent_strings'], 'One invalid leaf partially replaced the standalone copy bundle.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_strings', 'Invalid standalone copy did not emit its stable error code.');

$invalid_copy_cases = [
    'missing leaf' => function($copy){ unset($copy['actions']['save']); return $copy; },
    'unknown leaf' => function($copy){ $copy['dialog']['unknown'] = 'No'; return $copy; },
    'structured leaf' => function($copy){ $copy['terms']['label'] = ['No']; return $copy; },
    'oversized action' => function($copy){ $copy['actions']['save'] = str_repeat('x', 81); return $copy; },
    'control character' => function($copy){ $copy['error']['generic'] = "Invalid\x07value"; return $copy; },
];

foreach($invalid_copy_cases as $case => $mutate_copy){
    $settings_errors = [];
    $case_input = $full_copy_input;
    $case_input['consent_strings'] = $mutate_copy($editable_copy);
    $case_saved = Universal_Legal_Pages::sanitize_options($case_input);
    consent_assert($case_saved['consent_strings'] === $full_copy_saved['consent_strings'], 'The ' . $case . ' case partially replaced the standalone copy bundle.');
    consent_assert($settings_errors[0]['code'] === 'invalid_consent_strings', 'The ' . $case . ' case did not emit the stable copy error code.');
}

$unicode_copy = $editable_copy;
$unicode_copy['actions']['save'] = str_repeat('😀', 80);
$unicode_copy_input = $full_copy_input;
$unicode_copy_input['consent_strings'] = $unicode_copy;
$unicode_copy_saved = Universal_Legal_Pages::sanitize_options($unicode_copy_input);
consent_assert($unicode_copy_saved['consent_strings']['actions']['save'] === $unicode_copy['actions']['save'], 'A valid Unicode code-point boundary was rejected or truncated.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$settings_errors = [];
$google_ads_minimum = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'google_ads_id' => ' aw-12345 ',
]));
consent_assert($google_ads_minimum['google_ads_id'] === 'AW-12345', 'The minimum Google Ads ID boundary was rejected.');

$google_ads_maximum_value = 'AW-' . str_repeat('9', 20);
$google_ads_maximum = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'google_ads_id' => $google_ads_maximum_value,
]));
consent_assert($google_ads_maximum['google_ads_id'] === $google_ads_maximum_value, 'The maximum Google Ads ID boundary was rejected.');

$google_ads_disabled = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'google_ads_id' => '',
]));
consent_assert($google_ads_disabled['google_ads_id'] === '', 'An empty Google Ads ID must disable the standalone adapter.');

$settings_errors = [];
$google_ads_malformed_shape = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'google_ads_id' => ['AW-1234567890'],
]));
consent_assert($google_ads_malformed_shape['google_ads_id'] === $sanitized['google_ads_id'], 'A structured Google Ads value replaced the previous valid ID.');
consent_assert($settings_errors[0]['code'] === 'invalid_google_ads_id', 'A malformed Google Ads value did not emit its stable error code.');

$settings_errors = [];
$google_ads_too_long = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'google_ads_id' => 'AW-' . str_repeat('9', 21),
]));
consent_assert($google_ads_too_long['google_ads_id'] === $sanitized['google_ads_id'], 'An oversized Google Ads ID replaced the previous valid ID.');
consent_assert($settings_errors[0]['code'] === 'invalid_google_ads_id', 'An oversized Google Ads ID did not emit its stable error code.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$settings_errors = [];
$invalid_booleans = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'consent_enabled' => ['1'],
    'terms_required' => new stdClass(),
    'respect_gpc' => 'yes',
    'show_revisit_button' => 2,
]));
consent_assert($invalid_booleans['consent_enabled'] === $sanitized['consent_enabled'], 'An array changed the consent activation flag.');
consent_assert($invalid_booleans['terms_required'] === $sanitized['terms_required'], 'An object changed the terms-required flag.');
consent_assert($invalid_booleans['respect_gpc'] === $sanitized['respect_gpc'], 'An arbitrary string changed the GPC flag.');
consent_assert($invalid_booleans['show_revisit_button'] === $sanitized['show_revisit_button'], 'An arbitrary integer changed the revisit-button flag.');
consent_assert(count($settings_errors) === 4, 'Malformed checkbox shapes did not produce one error per field.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$settings_errors = [];
$invalid_page_shapes = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'consent_page_ids' => ['10', ['11']],
    'terms_page_id' => '-11',
]));
consent_assert($invalid_page_shapes['consent_page_ids'] === [10, 11], 'A malformed page list silently replaced valid consent links.');
consent_assert($invalid_page_shapes['terms_page_id'] === 11, 'A negative identifier was converted into a valid terms-page reference.');
consent_assert(count($settings_errors) === 2, 'Malformed legal-page identifiers did not produce one error per field.');

$settings_errors = [];
$duplicate_page_ids = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'consent_page_ids' => ['10', '10'],
]));
consent_assert($duplicate_page_ids['consent_page_ids'] === [10, 11], 'Duplicate page IDs replaced the previous consent links.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_page_ids', 'Duplicate page IDs did not emit the stable list error code.');

$settings_errors = [];
$too_many_page_ids = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
    'consent_page_ids' => array_fill(0, 1001, '10'),
]));
consent_assert($too_many_page_ids['consent_page_ids'] === [10, 11], 'An oversized page list replaced the previous consent links.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_page_ids', 'An oversized page list did not emit the stable list error code.');

$no_page_links_input = $valid_input;
unset($no_page_links_input['consent_page_ids']);
$no_page_links = Universal_Legal_Pages::sanitize_options($no_page_links_input);
consent_assert($no_page_links['consent_page_ids'] === [], 'Missing consent-page input must intentionally clear the optional link list.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$reordered_page_input = array_merge($valid_input, [
    'consent_page_ids' => ['11', '10'],
]);
$reordered_pages = Universal_Legal_Pages::sanitize_options($reordered_page_input);
consent_assert($reordered_pages['consent_page_ids'] === [11, 10], 'The chosen legal-page order was not preserved in storage.');
$options[Universal_Legal_Pages::OPTION_NAME] = $reordered_pages;
$reordered_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($reordered_config['legalLinks'][0]['label'] === 'Conditions' && $reordered_config['legalLinks'][1]['label'] === 'Confidentialité', 'The chosen legal-page order did not reach the public consent contract.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$previous = $sanitized;
$invalid = Universal_Legal_Pages::sanitize_options([
    'banner_title' => '<script>alert(1)</script>',
    'banner_message' => '<img src=x onerror=alert(2)>',
    'consent_page_ids' => ['99', '12'],
    'terms_page_id' => '0',
    'terms_required' => '1',
    'policy_version' => '../bad version',
    'duration_days' => '999',
    'ga4_measurement_id' => 'UA-123',
    'gtm_container_id' => 'https://attacker.test/x.js',
    'google_ads_id' => 'AW-123<script>',
    'meta_pixel_id' => '123abc',
]);

consent_assert($invalid['consent_page_ids'] === $previous['consent_page_ids'], 'Foreign or unpublished posts replaced valid consent links.');
consent_assert($invalid['banner_title'] === $previous['banner_title'], 'Scriptable title input replaced the previous plain-text value.');
consent_assert($invalid['banner_message'] === $previous['banner_message'], 'Scriptable message input replaced the previous plain-text value.');
consent_assert($invalid['terms_required'] === false, 'Terms cannot be required without a published terms page.');
consent_assert($invalid['policy_version'] === $previous['policy_version'], 'An invalid policy version replaced the previous value.');
consent_assert($invalid['duration_days'] === $previous['duration_days'], 'An invalid duration replaced the previous value.');
consent_assert($invalid['ga4_measurement_id'] === $previous['ga4_measurement_id'], 'An invalid GA4 ID replaced the previous value.');
consent_assert($invalid['gtm_container_id'] === $previous['gtm_container_id'], 'An invalid GTM ID replaced the previous value.');
consent_assert($invalid['google_ads_id'] === $previous['google_ads_id'], 'An invalid Google Ads ID replaced the previous value.');
consent_assert($invalid['meta_pixel_id'] === $previous['meta_pixel_id'], 'An invalid Meta Pixel ID replaced the previous value.');
consent_assert(count($settings_errors) >= 8, 'Invalid consent settings did not produce stable Settings API errors.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
$config = Universal_Legal_Pages::get_public_consent_config();

consent_assert($config['version'] === 4, 'The public consent schema must match consent-manager.js API_VERSION.');
consent_assert($config['cookieName'] === 'ulp_consent_7', 'The consent cookie is not partitioned by WordPress site.');
consent_assert($config['cookiePath'] === '/', 'The consent cookie path changed unexpectedly.');
consent_assert($config['termsRequired'] === true, 'The public contract lost the valid terms requirement.');
consent_assert(count($config['legalLinks']) === 2, 'The selected consent links are missing from the public contract.');
consent_assert($config['legalLinks'][0]['url'] === 'https://example.test/legal/10/', 'The first selected legal link is missing from the public contract.');
consent_assert($config['legalLinks'][1]['label'] === 'Conditions', 'The selected legal-link order or label changed unexpectedly.');
consent_assert($config['termsLink']['url'] === 'https://example.test/legal/11/', 'The terms confirmation link is missing from the public contract.');
consent_assert($config['integrations']['googleAnalytics'] === 'G-ABCD1234', 'The GA4 adapter configuration is missing.');
consent_assert($config['integrations']['googleTagManager'] === 'GTM-ABCD1234', 'The GTM adapter configuration is missing.');
consent_assert($config['integrations']['googleAds'] === 'AW-1234567890', 'The standalone Google Ads adapter configuration is missing.');
consent_assert($config['integrationCategories'] === [
    'googleAnalytics' => 'analytics',
    'googleTagManager' => 'analytics',
    'googleAds' => 'marketing',
    'metaPixel' => 'marketing',
], 'The predefined integrations are not assigned explicitly to consent categories.');
consent_assert($config['customIntegrations'] === [], 'The public custom-integration migration default must be an empty list.');
consent_assert(isset($config['strings']['categories']['analytics']['description']), 'The nested frontend copy contract is incomplete.');
consent_assert(isset($config['strings']['categories']['external']['description'], $config['strings']['services']['unclassified']), 'The external-service frontend copy contract is incomplete.');
consent_assert(preg_match('/\A[a-f0-9]{24}\z/', $config['serviceRegistryVersion']) === 1, 'The service registry version must be exactly 24 lowercase hexadecimal characters.');
consent_assert(count($config['services']) === 5, 'Known embed services and the three configured direct Google/Meta integrations must be active immediately.');
consent_assert(array_column($config['services'], 'id') === ['google-ads-remarketing', 'google-analytics-4', 'meta-pixel', 'vimeo', 'youtube'], 'The configured integration and known embed service identifiers are unstable or GTM leaked into individual services.');
foreach($config['services'] as $service){
    consent_assert(array_keys($service) === ['id', 'label', 'category', 'purpose', 'domains', 'kind', 'managed'], 'A public service has the wrong exact shape.');
}
consent_assert(strpos(json_encode($config), 'PRIVATE') === false, 'An unknown secret reached the public consent contract.');

$youtube_embed = '<iframe title="Présentation" src="https://www.youtube.com/embed/AbCdEf12345?rel=0&amp;controls=1&amp;autoplay=1&amp;cc_lang_pref=fr"></iframe>';
$youtube_placeholder = Universal_Legal_Pages::filter_external_iframes($youtube_embed);
consent_assert(strpos($youtube_placeholder, '<iframe') === false, 'A YouTube iframe remained executable before service consent.');
consent_assert(strpos($youtube_placeholder, ' src=') === false, 'A blocked YouTube placeholder retained an executable src attribute.');
consent_assert(strpos($youtube_placeholder, 'data-ulc-service="youtube"') !== false, 'The YouTube placeholder uses the wrong service ID.');
consent_assert(strpos($youtube_placeholder, 'data-ulc-src="https://www.youtube-nocookie.com/embed/AbCdEf12345?autoplay=1&amp;cc_lang_pref=fr&amp;controls=1&amp;rel=0"') !== false, 'YouTube was not canonicalized to the privacy-enhanced host with the shared query allowlist and stable order.');
consent_assert(isset($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION]['youtube']), 'Detected YouTube content was not persisted in the bounded internal registry.');
consent_assert($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION]['youtube']['kind'] === 'iframe', 'Detected YouTube content has the wrong internal kind.');
$config_after_youtube_detection = Universal_Legal_Pages::get_public_consent_config();
$service_ids_after_youtube_detection = array_column($config_after_youtube_detection['services'], 'id');
consent_assert(count($service_ids_after_youtube_detection) === count(array_unique($service_ids_after_youtube_detection)), 'Detecting a known built-in embed service created a duplicate public definition.');

add_filter('the_content', function($content){
    return $content . '<iframe src="https://player.vimeo.com/video/123456789"></iframe>';
}, 100);
$late_pipeline_content = apply_filters('the_content', '<p>Late shortcode output:</p>');
consent_assert(strpos($late_pipeline_content, '<iframe') === false, 'An iframe added by a priority-100 content filter escaped the final consent transformation.');
consent_assert(strpos($late_pipeline_content, 'data-ulc-service="vimeo"') !== false && strpos($late_pipeline_content, 'data-ulc-allow-service="vimeo"') !== false, 'A route-time Vimeo iframe added before the final filter was not immediately activable.');

$quoted_title_placeholder = Universal_Legal_Pages::filter_external_iframes(
    '<iframe title="Video > introduction" src="https://www.youtube.com/embed/AbCdEf12345"></iframe>'
);
consent_assert(strpos($quoted_title_placeholder, '<iframe') === false, 'A quoted greater-than sign confused the iframe opening-tag boundary.');
consent_assert(strpos($quoted_title_placeholder, 'data-ulc-title="Video &gt; introduction"') !== false, 'A safe quoted iframe title was not preserved canonically.');

$ambiguous_iframe_cases = [
    'slash-separated attribute' => '<iframe/src=https://evil.example/x></iframe>',
    'duplicate src' => '<iframe src="https://evil.example/one" src="https://evil.example/two"></iframe>',
    'srcdoc' => '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
    'self-closing syntax with raw-text body' => '<iframe src="https://evil.example/x"/><img src="https://tracker.test/pixel"></iframe>',
    'unterminated opening quote' => '<iframe src="https://evil.example/x></iframe>',
    'missing closing tag' => '<iframe src="https://evil.example/x">unclosed content',
    'malformed closing tag' => '<iframe src="https://evil.example/x">content</iframe garbage>',
    'unterminated closing tag' => '<iframe src="https://evil.example/x">content</iframe',
];

foreach($ambiguous_iframe_cases as $case => $markup){
    $blocked_markup = Universal_Legal_Pages::filter_external_iframes($markup);
    consent_assert(stripos($blocked_markup, '<iframe') === false, 'The ' . $case . ' case left an executable iframe start tag.');
    consent_assert(strpos($blocked_markup, 'evil.example') === false, 'The ' . $case . ' case retained its external source in rendered output.');
    consent_assert(stripos($blocked_markup, 'srcdoc') === false, 'The ' . $case . ' case retained executable srcdoc content.');
    consent_assert(stripos($blocked_markup, 'tracker.test') === false, 'The ' . $case . ' case re-emitted an iframe raw-text body as active markup.');
}

$unknown_domain = 'widgets.vendor-example.test';
$unknown_id = 'external-' . substr(hash('sha256', $unknown_domain), 0, 16);
$unknown_embed = '<iframe src="https://' . $unknown_domain . '/widget?public=1"></iframe>';
$unknown_placeholder = Universal_Legal_Pages::filter_external_iframes($unknown_embed);
consent_assert(strpos($unknown_placeholder, 'data-ulc-service="' . $unknown_id . '"') !== false, 'An unknown iframe did not receive its deterministic service ID.');
consent_assert(strpos($unknown_placeholder, 'data-ulc-src=') !== false && strpos($unknown_placeholder, 'data-ulc-allow-service=') === false, 'An unclassified service must stay blocked without an allow action.');

$registry_before_visitor_render = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION];
$can_manage_options = false;
$visitor_placeholder = Universal_Legal_Pages::filter_external_iframes('<iframe src="https://visitor-only.test/embed"></iframe>');
consent_assert(strpos($visitor_placeholder, '<iframe') === false, 'A visitor render failed to block an unknown external iframe.');
consent_assert($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] === $registry_before_visitor_render, 'An unauthenticated visitor render mutated the detected-service inventory.');
$can_manage_options = true;

$detected_registry_before_invalid = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION];
$service_input = array_merge($valid_input, [
    'services' => [
        'youtube' => ['label' => 'YouTube vidéo', 'category' => 'external'],
        $unknown_id => ['label' => 'Widget partenaire', 'category' => 'external'],
    ],
]);
$configured_services = Universal_Legal_Pages::sanitize_options($service_input);
consent_assert($configured_services['services']['youtube'] === ['label' => 'YouTube vidéo', 'category' => 'external'], 'A valid detected-service classification was not stored canonically.');

$options[Universal_Legal_Pages::OPTION_NAME] = $configured_services;
$classified_placeholder = Universal_Legal_Pages::filter_external_iframes($unknown_embed);
consent_assert(strpos($classified_placeholder, 'data-ulc-allow-service="' . $unknown_id . '"') !== false, 'A classified managed iframe did not expose its individual allow action.');
$classified_config = Universal_Legal_Pages::get_public_consent_config();
$classified_by_id = [];
foreach($classified_config['services'] as $service){
    $classified_by_id[$service['id']] = $service;
}
consent_assert($classified_by_id[$unknown_id]['label'] === 'Widget partenaire' && $classified_by_id[$unknown_id]['category'] === 'external', 'Detected-service classification did not reach the public contract.');
consent_assert($classified_by_id[$unknown_id]['domains'] === [$unknown_domain] && $classified_by_id[$unknown_id]['kind'] === 'iframe' && $classified_by_id[$unknown_id]['managed'] === true, 'Detected domain/type relationships were not derived from the internal registry.');

$invalid_service_cases = [
    'unknown service' => ['not-detected' => ['label' => 'Unknown', 'category' => 'external']],
    'invalid identifier' => ['Bad ID' => ['label' => 'Unknown', 'category' => 'external']],
    'protected domain field' => ['youtube' => ['label' => 'YouTube', 'category' => 'external', 'domains' => ['attacker.test']]],
    'protected managed field' => ['youtube' => ['label' => 'YouTube', 'category' => 'external', 'managed' => false]],
    'invalid category' => ['youtube' => ['label' => 'YouTube', 'category' => 'advertising']],
    'invalid label markup' => ['youtube' => ['label' => '<b>YouTube</b>', 'category' => 'external']],
    'oversized label' => ['youtube' => ['label' => str_repeat('x', 81), 'category' => 'external']],
    'too many services' => array_fill_keys(array_map(function($index){ return 'service-' . $index; }, range(1, 33)), ['label' => 'Service', 'category' => 'external']),
];

foreach($invalid_service_cases as $case => $submitted_services){
    $settings_errors = [];
    $invalid_service_saved = Universal_Legal_Pages::sanitize_options(array_merge($valid_input, [
        'services' => $submitted_services,
    ]));
    consent_assert($invalid_service_saved['services'] === $configured_services['services'], 'The ' . $case . ' request partially replaced the previous complete service configuration.');
    consent_assert(isset($settings_errors[0]) && $settings_errors[0]['code'] === 'invalid_services', 'The ' . $case . ' request did not emit the stable service error code.');
    consent_assert($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] === $detected_registry_before_invalid, 'Submitted service fields mutated protected detected domain/type relations.');
}

$host_confusion_cases = [
    '<iframe src="https://youtube.com.evil.test/embed/AbCdEf12345"></iframe>',
    '<iframe src="https://user@www.youtube.com/embed/AbCdEf12345"></iframe>',
    '<iframe src="https://www.youtube.com:8443/embed/AbCdEf12345"></iframe>',
    '<iframe src="https://www.youtube.com/embed/AbCdEf12345#fragment"></iframe>',
    '<iframe src="https://www.youtube.com/watch?v=AbCdEf12345"></iframe>',
    '<iframe src="https://player.vimeo.com/channels/demo"></iframe>',
];

foreach($host_confusion_cases as $host_confusion_embed){
    $blocked = Universal_Legal_Pages::filter_external_iframes($host_confusion_embed);
    consent_assert(strpos($blocked, '<iframe') === false && strpos($blocked, ' src=') === false, 'A malformed or host-confused embed retained an executable iframe source.');
}

$canonical_host_embed = '<iframe src="https://WWW.YOUTUBE.COM./embed/AbCdEf12345"></iframe>';
$canonical_host_placeholder = Universal_Legal_Pages::filter_external_iframes($canonical_host_embed);
consent_assert(strpos($canonical_host_placeholder, 'data-ulc-src="https://www.youtube-nocookie.com/embed/AbCdEf12345"') !== false, 'A canonicalizable YouTube host was not rewritten to the fixed privacy-enhanced host.');

$vimeo_placeholder = Universal_Legal_Pages::filter_external_iframes('<iframe src="https://player.vimeo.com/video/123456789?dnt=1"></iframe>');
consent_assert(strpos($vimeo_placeholder, 'data-ulc-service="vimeo"') !== false && strpos($vimeo_placeholder, '<iframe') === false, 'A valid Vimeo embed was not detected and blocked by service.');

$many_iframes = str_repeat($unknown_embed, Universal_Legal_Pages::MAX_URL_CANDIDATES + 1);
$many_iframe_placeholders = Universal_Legal_Pages::filter_external_iframes($many_iframes);
consent_assert(strpos($many_iframe_placeholders, '<iframe') === false, 'An iframe beyond the URL-candidate limit remained executable.');
consent_assert(substr_count($many_iframe_placeholders, 'data-ulc-src=') === Universal_Legal_Pages::MAX_URL_CANDIDATES, 'The iframe filter parsed more than its 64 URL-candidate budget.');

$oversized_plain_content = str_repeat('a', Universal_Legal_Pages::MAX_CONTENT_SCAN_BYTES + 1);
consent_assert(Universal_Legal_Pages::filter_external_iframes($oversized_plain_content) === $oversized_plain_content, 'Oversized content without an iframe was modified unnecessarily.');

$oversized_embed_content = $oversized_plain_content . '<iframe src="https://www.youtube.com/embed/AbCdEf12345"></iframe>';
$oversized_embed_result = Universal_Legal_Pages::filter_external_iframes($oversized_embed_content);
consent_assert(strpos($oversized_embed_result, '<iframe') === false && strlen($oversized_embed_result) < 1000, 'Oversized content did not fail closed before the bounded iframe scan.');

$enabled_service_options = $options[Universal_Legal_Pages::OPTION_NAME];
$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($enabled_service_options, ['consent_enabled' => false]);
consent_assert(Universal_Legal_Pages::filter_external_iframes($oversized_embed_content) === $oversized_embed_content, 'A disabled consent manager modified oversized iframe content.');
$options[Universal_Legal_Pages::OPTION_NAME] = $enabled_service_options;

$version_before_rename = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
$renamed_service_input = $service_input;
$renamed_service_input['services']['youtube']['label'] = 'YouTube renommé';
$renamed_services = Universal_Legal_Pages::sanitize_options($renamed_service_input);
$options[Universal_Legal_Pages::OPTION_NAME] = $renamed_services;
$version_after_rename = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($version_after_rename === $version_before_rename, 'A display-only service label unexpectedly invalidated prior consent.');

$reclassified_service_input = $renamed_service_input;
$reclassified_service_input['services']['youtube']['category'] = 'marketing';
$reclassified_services = Universal_Legal_Pages::sanitize_options($reclassified_service_input);
$options[Universal_Legal_Pages::OPTION_NAME] = $reclassified_services;
$version_after_reclassification = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($version_after_reclassification !== $version_before_rename, 'A consent-relevant service reclassification did not invalidate the registry version.');

$registry_order_a = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION];
$registry_order_b = array_reverse($registry_order_a, true);
$options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] = $registry_order_b;
$version_reordered = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
consent_assert($version_reordered === $version_after_reclassification, 'Service registry hashing depends on input order instead of canonical records.');
$options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] = $registry_order_a;

$wp_scripts = new WP_Scripts();
$wp_scripts->queue = ['vendor-widget'];
$wp_scripts->registered['vendor-widget'] = (object)[
    'src' => 'https://scripts.vendor-cdn.test/widget.js?token=must-not-be-stored',
];
$version_before_script_inventory = Universal_Legal_Pages::get_public_consent_config()['serviceRegistryVersion'];
Universal_Legal_Pages::detect_admin_enqueued_scripts();
$script_id = 'script-' . substr(hash('sha256', 'scripts.vendor-cdn.test'), 0, 16);
$script_relation = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION][$script_id] ?? null;
consent_assert(is_array($script_relation) && $script_relation['domains'] === ['scripts.vendor-cdn.test'], 'External script discovery did not store only its canonical host.');
consent_assert($script_relation['kind'] === 'script' && $script_relation['managed'] === false && $script_relation['handles'] === ['vendor-widget'], 'A detected script must remain reporting-only and retain only its bounded WordPress handle.');
consent_assert(strpos(json_encode($script_relation), 'token') === false && strpos(json_encode($script_relation), 'widget.js') === false, 'Script discovery persisted an external URL path or query value.');
$script_inventory_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(!in_array($script_id, array_column($script_inventory_config['services'], 'id'), true), 'A reporting-only detected script leaked into the visitor service registry.');
consent_assert($script_inventory_config['serviceRegistryVersion'] === $version_before_script_inventory, 'A reporting-only script inventory invalidated visitor consent.');

$script_classification_input = $reclassified_service_input;
$script_classification_input['services'][$script_id] = [
    'label' => 'Vendor widget script',
    'category' => 'preferences',
];
$script_classified_options = Universal_Legal_Pages::sanitize_options($script_classification_input);
$options[Universal_Legal_Pages::OPTION_NAME] = $script_classified_options;
$script_classified_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(!in_array($script_id, array_column($script_classified_config['services'], 'id'), true), 'Classifying a reporting-only script exposed it without a trusted adapter.');
consent_assert($script_classified_config['serviceRegistryVersion'] === $version_before_script_inventory, 'Classifying a reporting-only script changed the visitor registry hash without an adapter.');

$script_registry_before_denied_audit = $options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION];
$can_manage_options = false;
$wp_scripts->queue = ['denied-script'];
$wp_scripts->registered['denied-script'] = (object)['src' => 'https://denied.test/script.js'];
Universal_Legal_Pages::detect_admin_enqueued_scripts();
consent_assert($options[Universal_Legal_Pages::DETECTED_SERVICES_OPTION] === $script_registry_before_denied_audit, 'A visitor without manage_options mutated the detected-service inventory.');
$can_manage_options = true;

add_filter('universal_legal_pages_services', function($services) use ($script_id){
    $services[] = [
        'id' => $script_id,
        'label' => 'Managed vendor widget',
        'category' => 'preferences',
        'purpose' => 'preferences',
        'domains' => ['scripts.vendor-cdn.test'],
        'kind' => 'script',
        'managed' => true,
    ];
    return $services;
}, 5);
$managed_script_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(in_array($script_id, array_column($managed_script_config['services'], 'id'), true), 'A trusted PHP adapter could not publish the previously reporting-only script as managed.');
consent_assert($managed_script_config['serviceRegistryVersion'] !== $version_before_script_inventory, 'Publishing a managed script did not update the visitor service registry hash.');

add_filter('universal_legal_pages_services', function($services){
    $services[] = [
        'id' => 'project-chat',
        'label' => 'Project chat',
        'category' => 'preferences',
        'purpose' => 'preferences',
        'domains' => ['chat.project.test'],
        'kind' => 'integration',
        'managed' => true,
    ];
    return $services;
});
$custom_service_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(in_array('project-chat', array_column($custom_service_config['services'], 'id'), true), 'A valid trusted PHP service filter definition was not exposed.');
consent_assert(count($custom_service_config['services']) <= Universal_Legal_Pages::MAX_SERVICES, 'The public service contract exceeded its hard bound.');

add_filter('universal_legal_pages_services', function($services){
    $services[] = [
        'id' => 'invalid-project-service',
        'label' => 'Invalid project service',
        'category' => 'external',
        'purpose' => 'external',
        'domains' => ['https://attacker.test/path'],
        'kind' => 'iframe',
        'managed' => true,
    ];
    return $services;
}, 20);
$invalid_custom_service_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(!in_array('invalid-project-service', array_column($invalid_custom_service_config['services'], 'id'), true), 'A trusted PHP service with an invalid domain escaped validation.');

$oversized_detected_registry = [];
for($service_index = 0; $service_index < 40; $service_index++){
    $service_id = 'bounded-' . str_pad((string)$service_index, 2, '0', STR_PAD_LEFT);
    $oversized_detected_registry[$service_id] = [
        'domains' => [$service_id . '.example.test'],
        'handles' => [],
        'kind' => 'iframe',
        'managed' => true,
    ];
}
$bounded_public_services = Universal_Legal_Pages::get_public_services($reclassified_services, $oversized_detected_registry);
consent_assert(count($bounded_public_services) === Universal_Legal_Pages::MAX_SERVICES, 'The public service contract did not enforce its exact maximum of 32 services.');

$post_statuses[11] = 'draft';
$missing_terms_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($missing_terms_config['termsRequired'] === true, 'A missing terms page silently disabled the configured legal requirement.');
consent_assert($missing_terms_config['termsLink'] === null, 'An unpublished terms page remained public.');
consent_assert(count($missing_terms_config['legalLinks']) === 1, 'An unpublished selected legal page remained in the public link list.');
$post_statuses[11] = 'publish';

$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($sanitized, ['consent_enabled' => false]);
Universal_Legal_Pages::enqueue_assets();
consent_assert(!isset($enqueued_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE]), 'A disabled consent manager was enqueued publicly.');

$options[Universal_Legal_Pages::OPTION_NAME] = $sanitized;
Universal_Legal_Pages::enqueue_assets();

consent_assert(isset($enqueued_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE]), 'The enabled consent manager script was not enqueued.');
consent_assert(
    $enqueued_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE]['src'] === 'https://example.test/wp-content/plugins/universal-legal-pages/assets/js/consent-manager.js',
    'The consent manager must load only its local portable script.'
);
consent_assert(count($inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE]) === 2, 'Consent bootstrap and public configuration must both be emitted before the manager.');
consent_assert(
    $inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][0]['position'] === 'before'
        && $inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][1]['position'] === 'before',
    'Consent configuration must be available before the local manager executes.'
);
consent_assert(
    strpos($inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][1]['data'], 'G-ABCD1234') !== false,
    'The validated GA4 identifier is absent from the inline data contract.'
);
consent_assert(
    strpos($inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][1]['data'], 'AW-1234567890') !== false,
    'The validated Google Ads identifier is absent from the inline data contract.'
);
consent_assert(
    strpos($inline_scripts[Universal_Legal_Pages::CONSENT_SCRIPT_HANDLE][1]['data'], '<script>') === false,
    'Scriptable settings content reached the JavaScript configuration sink.'
);

ob_start();
Universal_Legal_Pages::render_settings_page();
$settings_page = ob_get_clean();

consent_assert(end($get_posts_requests)['suppress_filters'] === false, 'Standalone settings must preserve the host WordPress language filters.');
consent_assert(strpos($settings_page, 'action="options.php"') !== false, 'The settings screen does not submit through the WordPress Settings API.');
consent_assert(
    strpos($settings_page, 'settings-fields:' . Universal_Legal_Pages::SETTINGS_GROUP) !== false,
    'The settings screen did not emit the core Settings API nonce/action fields.'
);
consent_assert(strpos($settings_page, '[ga4_measurement_id]') !== false, 'The GA4 integration field is missing from the settings screen.');
consent_assert(strpos($settings_page, '[gtm_container_id]') !== false, 'The GTM integration field is missing from the settings screen.');
consent_assert(strpos($settings_page, '[google_ads_id]') !== false, 'The standalone Google Ads integration field is missing from the settings screen.');
consent_assert(strpos($settings_page, '[meta_pixel_id]') !== false, 'The Meta Pixel integration field is missing from the settings screen.');
consent_assert(strpos($settings_page, '[terms_required]') !== false, 'The explicit terms acknowledgement setting is missing.');
consent_assert(strpos($settings_page, 'class="wrap ulp-admin"') !== false, 'The consent settings screen is missing its scoped admin layout.');
consent_assert(substr_count($settings_page, 'data-ulp-section-panel') === 6, 'The consent settings screen must expose six focused configuration sections.');
consent_assert(substr_count($settings_page, '<option value="ulp-settings-panel-') === 6, 'Every consent configuration section must appear in the native section selector.');
consent_assert(substr_count($settings_page, 'data-ulp-section-select') === 1, 'The consent settings screen must use one compact section selector.');
consent_assert(substr_count($settings_page, 'ulp-admin-section--initially-hidden') === 5, 'Every section after the first must be hidden during the JavaScript first paint.');
consent_assert(!preg_match('/data-ulp-section-navigation\s+hidden/u', $settings_page), 'The section selector must be available before plugin JavaScript initializes.');
consent_assert(strpos($settings_page, 'data-ulp-section-tab') === false, 'The crowded top-level settings tabs must not remain.');
consent_assert(strpos($settings_page, '<section id="ulp-settings-panel-copy" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-copy-title" data-ulp-section-panel>') !== false, 'The copy editor must use the same two-column section structure as the other settings.');
consent_assert((bool)preg_match('/<\/div>\s*<footer class="ulp-admin__actions">/u', $settings_page), 'The one save action must remain global and outside the tab panels.');
consent_assert(substr_count($settings_page, 'class="ulp-admin-section__body"') === 6, 'Every consent configuration section must use the same shared body layout.');
consent_assert(strpos($settings_page, 'ulp-admin-section__body--') === false, 'Consent configuration sections must not retain body-specific spacing exceptions.');
consent_assert(strpos($settings_page, 'Policy version') !== false, 'The policy summary does not identify its value as a version.');
consent_assert(strpos($settings_page, 'of 3 configured') === false, 'The consent settings summary must not imply that every available integration should be configured.');
consent_assert(strpos($settings_page, 'ulp-admin__summary') === false, 'The obsolete consent summary is still rendered.');
consent_assert(strpos($settings_page, 'ulp-admin__mark') === false, 'The decorative admin mark is still rendered.');
consent_assert(strpos($settings_page, 'class="ulp-admin__credit"') !== false, 'The Studio Champ Gauche credit is missing.');
consent_assert(strpos($settings_page, 'href="https://champgauche.studio"') !== false, 'The Studio Champ Gauche credit uses the wrong destination.');
consent_assert(strpos($settings_page, 'rel="noopener noreferrer"') !== false, 'The external Studio Champ Gauche link is missing its opener protection.');
consent_assert(strpos($settings_page, 'data-ulc-open') !== false, 'The custom consent-trigger attribute is not explained in the settings screen.');
consent_assert(strpos($settings_page, 'does not trigger the same Google Analytics or Google Ads destinations a second time') !== false, 'Concurrent GTM and direct Google tags must display a duplicate-destination warning.');
consent_assert(strpos($settings_page, 'Confidentialité') !== false, 'Published legal pages are missing from the settings selectors.');
consent_assert(strpos($settings_page, 'for="ulp-terms-page-id"') !== false && strpos($settings_page, 'id="ulp-terms-page-id"') !== false, 'The terms-page selector is missing its accessible label association.');
consent_assert(strpos($settings_page, '[consent_page_ids][]') !== false, 'The flexible consent-page checklist is missing from the settings screen.');
consent_assert(strpos($settings_page, 'Linked pages and terms') === false, 'The obsolete fixed-page section label is still rendered.');
consent_assert(strpos($settings_page, 'This selection does not modify any theme menu.') !== false, 'The consent-link purpose is not explained in the settings screen.');
consent_assert(strpos($settings_page, 'Choose up to 20 pages') === false, 'The obsolete 20-page product limit is still shown.');
consent_assert(strpos($settings_page, 'dragging and dropping') !== false, 'The drag-and-drop ordering instruction is missing.');
consent_assert(strpos($settings_page, 'data-ulp-selected-pages') !== false && strpos($settings_page, 'data-ulp-available-pages') !== false, 'The ordered and available page lists are missing.');
consent_assert(strpos($settings_page, 'data-ulp-page-up') !== false && strpos($settings_page, 'data-ulp-page-down') !== false, 'Keyboard-accessible page-order controls are missing.');
consent_assert(strpos($settings_page, 'for="ulp-consent-page-10"') !== false && strpos($settings_page, 'id="ulp-consent-page-10"') !== false, 'A selected legal-page checkbox is missing its accessible label association.');
consent_assert(strpos($settings_page, '[consent_strings][title]') !== false, 'The standalone settings screen lost its editable banner title.');
consent_assert(strpos($settings_page, '[consent_strings][actions][revisit]') !== false, 'The standalone settings screen lost the permanent cookie-button label.');
consent_assert(strpos($settings_page, '[consent_strings][dialog][description]') !== false, 'The standalone settings screen lost the preferences popup copy.');
consent_assert(substr_count($settings_page, '<details class="ulp-admin__copy-group"') === 5, 'The standalone copy editor must expose five concise accordion groups.');
consent_assert(strpos($settings_page, '<details class="ulp-admin__copy-group" name="ulp-consent-copy-groups" open') === false, 'No standalone copy group may be expanded by default.');
consent_assert(substr_count($settings_page, 'name="ulp-consent-copy-groups"') === 5, 'Standalone copy groups must belong to one mutually exclusive accordion.');
consent_assert(strpos($settings_page, 'class="ulp-admin__copy-summary-description"') !== false, 'Accordion summaries must explain their editing scope.');
consent_assert(strpos($settings_page, '<legend>Primary choices</legend>') !== false, 'Button copy must be split into understandable editing units.');
consent_assert(strpos($settings_page, 'data-ulc-category-card') !== false, 'Consent categories must use dedicated editable cards.');
consent_assert(substr_count($settings_page, 'dir="auto"') === 33, 'Every standalone public string and category template must adapt its writing direction.');
consent_assert(strpos($settings_page, 'id="ulp-section-services-title"') !== false, 'The detected-service classification section is missing.');
consent_assert(strpos($settings_page, 'Unclassified services remain blocked.') !== false, 'The fail-closed unclassified-service behavior is not explained.');
consent_assert(strpos($settings_page, 'data-ulp-language-editor') === false, 'ReactWP language controls leaked into the standalone settings screen.');

$can_manage_options = false;
$unauthorized_blocked = false;

try{
    Universal_Legal_Pages::render_settings_page();
}catch(RuntimeException $error){
    $unauthorized_blocked = true;
}

consent_assert($unauthorized_blocked, 'A user without manage_options reached the consent settings renderer.');

if(!class_exists('ReactWP', false)){
    class ReactWP{}
}

$reactwp_test_languages = [
    ['name' => 'Français', 'code' => 'fr'],
    ['name' => 'English', 'code' => 'en'],
];
$reactwp_test_current_language = 'en';

if(!function_exists('rwp_admin_langs')){
    function rwp_admin_langs(){
        global $reactwp_test_languages;
        return $reactwp_test_languages;
    }
}

if(!function_exists('pll_current_language')){
    function pll_current_language($field = 'slug'){
        global $reactwp_test_current_language;
        return $reactwp_test_current_language;
    }
}

$can_manage_options = true;
$stored_translations = [
    'fr' => [
        'title' => 'Vos choix en français',
        'message' => 'Message de consentement en français.',
    ],
    'en' => [
        'title' => 'Your choices in English',
        'message' => 'Consent message in English.',
    ],
];
$options[Universal_Legal_Pages::OPTION_NAME] = array_merge($sanitized, [
    'banner_translations' => $stored_translations,
]);

$reactwp_options = Universal_Legal_Pages::get_options();
consent_assert($reactwp_options['banner_translations'] === $stored_translations, 'ReactWP translations were not hydrated from the stored allowlisted map.');
consent_assert($reactwp_options['consent_link_translations'] === [
    'fr' => ['consent_page_ids' => [10, 11], 'terms_page_id' => 11],
    'en' => ['consent_page_ids' => [10, 11], 'terms_page_id' => 11],
], 'Legacy global consent links were not migrated in memory to every ReactWP language.');

$reactwp_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($reactwp_config['currentLanguage'] === 'en', 'The current ReactWP language was not resolved from the active locale.');
consent_assert($reactwp_config['bannerTranslations'] === $stored_translations, 'The bounded ReactWP translations are missing from the public contract.');
consent_assert($reactwp_config['strings']['title'] === 'Your choices in English', 'The public banner did not resolve its active English title.');
consent_assert(array_keys($reactwp_config['linkTranslations']) === ['fr', 'en'], 'The public link-translation contract does not match the configured ReactWP languages.');

$stored_reactwp_source = $options[Universal_Legal_Pages::OPTION_NAME];
$multilingual_category_input = array_merge($valid_input, [
    'consent_categories_present' => '1',
    'consent_categories' => [
        [
            'id' => 'necessary',
            'translations' => [
                'fr' => ['label' => 'Essentiels', 'description' => 'Nécessaires au fonctionnement sécurisé du site.'],
                'en' => ['label' => 'Essentials', 'description' => 'Required for secure site operation.'],
            ],
        ],
        [
            'id' => 'category-fedcba9876543210',
            'translations' => [
                'fr' => ['label' => 'Publicité personnalisée', 'description' => 'Permet les campagnes publicitaires personnalisées.'],
                'en' => ['label' => 'Personalized advertising', 'description' => 'Allows personalized advertising campaigns.'],
            ],
        ],
    ],
    'ga4_category' => 'category-fedcba9876543210',
    'gtm_category' => 'category-fedcba9876543210',
    'google_ads_category' => 'category-fedcba9876543210',
    'meta_pixel_category' => 'category-fedcba9876543210',
]);
$multilingual_category_saved = Universal_Legal_Pages::sanitize_options($multilingual_category_input);
consent_assert(
    $multilingual_category_saved['consent_categories']['category-fedcba9876543210']['translations']['fr']['label'] === 'Publicité personnalisée'
    && $multilingual_category_saved['consent_categories']['category-fedcba9876543210']['translations']['en']['label'] === 'Personalized advertising',
    'Consent category copy was not stored independently for every ReactWP language.'
);
$options[Universal_Legal_Pages::OPTION_NAME] = $multilingual_category_saved;
$multilingual_category_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(
    $multilingual_category_config['categories'][1]['label'] === 'Personalized advertising'
    && $multilingual_category_config['categoryTranslations']['fr'][1]['label'] === 'Publicité personnalisée',
    'The public category registry did not resolve or expose its ReactWP language variants.'
);
$options[Universal_Legal_Pages::OPTION_NAME] = $stored_reactwp_source;

$reactwp_test_current_language = 'fr';
$reactwp_french_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($reactwp_french_config['strings']['title'] === 'Vos choix en français', 'The public banner did not resolve its active French title.');
consent_assert($reactwp_french_config['strings']['message'] === 'Message de consentement en français.', 'The public banner did not resolve its active French message.');

$reactwp_test_current_language = 'zz';
$reactwp_fallback_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($reactwp_fallback_config['currentLanguage'] === 'fr', 'An unknown route language did not fall back to the WordPress locale or first configured language.');

ob_start();
Universal_Legal_Pages::render_settings_page();
$multilingual_settings_page = ob_get_clean();

consent_assert(end($get_posts_requests)['suppress_filters'] === true, 'ReactWP multilingual link settings must list published legal pages across language filters.');
consent_assert(strpos($multilingual_settings_page, '<!-- settings-errors:all -->') !== false, 'The consent screen must retain WordPress general notices so the successful-save confirmation remains visible.');
consent_assert(strpos($multilingual_settings_page, 'data-ulp-settings-form') !== false, 'The consent form is missing its progressive asynchronous-save hook.');
consent_assert(strpos($multilingual_settings_page, 'data-ulp-save-notice') !== false, 'The consent form is missing its custom live save notice.');
consent_assert(strpos($multilingual_settings_page, 'data-nonce-action="' . Universal_Legal_Pages::AJAX_SAVE_ACTION . '"') !== false, 'The consent form is missing its dedicated asynchronous-save nonce.');
consent_assert(strpos($multilingual_settings_page, 'data-ulp-language-editor') !== false, 'The ReactWP language editor is missing.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_string_translations][fr][title]') !== false, 'The French title field uses the wrong nested contract.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_string_translations][en][message]') !== false, 'The English message field uses the wrong nested contract.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_string_translations][fr][actions][revisit]') !== false, 'The localized cookie-button label is missing.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[banner_title]') === false, 'The flat title input must not compete with ReactWP translations.');
consent_assert(substr_count($multilingual_settings_page, 'data-ulp-language-tab') === 18, 'Copy, consent links, confirmation pages, category cards, and the category template must expose one accessible tab per ReactWP language.');
consent_assert(substr_count($multilingual_settings_page, 'role="tabpanel"') === 18, 'Copy, consent links, confirmation pages, category cards, and the category template must expose accessible panels for every ReactWP language.');
consent_assert(strpos($multilingual_settings_page, '<code aria-hidden="true">') === false, 'Language tabs must not repeat the language as a short code badge.');
consent_assert(strpos($multilingual_settings_page, '<code>fr</code>') === false && strpos($multilingual_settings_page, '<code>en</code>') === false, 'Language panel headings must not repeat the language code.');
consent_assert(strpos($multilingual_settings_page, '>Français</span>') !== false && strpos($multilingual_settings_page, '>English</span>') !== false, 'Removing language-code badges must preserve the full language names.');
consent_assert(strpos($multilingual_settings_page, '<details class="ulp-admin__copy-group" name="ulp-consent-copy-fr-groups" open') === false, 'No localized copy group may be expanded by default.');
consent_assert(substr_count($multilingual_settings_page, 'name="ulp-consent-copy-fr-groups"') === 5, 'French copy groups must use one mutually exclusive accordion.');
consent_assert(substr_count($multilingual_settings_page, 'name="ulp-consent-copy-en-groups"') === 5, 'English copy groups must use one mutually exclusive accordion.');
consent_assert(substr_count($multilingual_settings_page, 'dir="auto"') === 66, 'All public fields and category templates for every ReactWP language must adapt their writing direction.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_link_translations][fr][consent_page_ids][]') !== false, 'The French ordered consent-link field uses the wrong nested contract.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_link_translations][en][terms_page_id]') !== false, 'The English terms-page field uses the wrong nested contract.');
consent_assert(strpos($multilingual_settings_page, 'id="ulp-consent-links-fr-pages-page-10"') !== false, 'Localized legal-page controls do not have language-scoped IDs.');
consent_assert(strpos($multilingual_settings_page, 'class="ulp-admin__terms-setting ulp-admin__terms-setting--localized"') !== false, 'The localized explicit-acceptance controls are missing their dedicated fieldset.');
consent_assert(strpos($multilingual_settings_page, 'id="ulp-terms-language-tab-fr"') !== false, 'Localized confirmation pages are missing their language tabs.');
consent_assert(strpos($multilingual_settings_page, 'aria-controls="ulp-terms-language-panel-fr"') !== false, 'A confirmation-page language tab is not associated with its panel.');
consent_assert(strpos($multilingual_settings_page, 'id="ulp-terms-language-panel-en"') !== false, 'Localized confirmation pages are missing their language panels.');
$localized_links_editor_position = strpos($multilingual_settings_page, 'class="ulp-admin__language-editor ulp-admin__language-editor--links"');
$localized_terms_toggle_position = strpos($multilingual_settings_page, 'Request explicit acceptance in preferences');
$localized_terms_link_position = strpos($multilingual_settings_page, 'id="ulp-consent-links-fr-terms-page-id"');
consent_assert(
    $localized_links_editor_position !== false
    && $localized_terms_toggle_position > $localized_links_editor_position
    && $localized_terms_link_position > $localized_terms_toggle_position,
    'The global explicit-acceptance toggle must sit beside and before its per-language confirmation-page fields.'
);
consent_assert(strpos($multilingual_settings_page, 'The languages come from ReactWP > Site settings.') === false, 'Redundant ReactWP language notices must not appear in either multilingual editor.');
consent_assert(strpos($multilingual_settings_page, 'universal_legal_pages_options[consent_page_ids][]') === false, 'The global legal-link field must not compete with ReactWP language selections.');

unset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]);
Universal_Legal_Pages::enqueue_admin_assets('legal_page_page_' . Universal_Legal_Pages::SETTINGS_SLUG);
consent_assert(isset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]), 'The ReactWP language tabs script was not enqueued.');
consent_assert(
    $enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]['src'] === 'https://example.test/wp-content/plugins/universal-legal-pages/assets/js/admin-consent.js',
    'The ReactWP language editor must load only its local admin script.'
);

$reactwp_test_current_language = 'en';
$multilingual_input = array_merge($valid_input, [
    'banner_title' => 'This flat value must be ignored',
    'banner_message' => 'This flat message must be ignored',
    'banner_translations' => [
        'fr' => [
            'title' => 'Nouveau titre français',
            'message' => 'Nouveau message français.',
        ],
        'en' => [
            'title' => 'New English title',
            'message' => 'New English message.',
        ],
    ],
]);
$multilingual_saved = Universal_Legal_Pages::sanitize_options($multilingual_input);
consent_assert($multilingual_saved['banner_translations'] === $multilingual_input['banner_translations'], 'A valid complete translation map was not stored exactly.');
consent_assert($multilingual_saved['banner_title'] === 'New English title', 'The portable flat fallback must follow the current ReactWP language.');
consent_assert($multilingual_saved['banner_message'] === 'New English message.', 'The portable flat message fallback must follow the current ReactWP language.');
consent_assert($multilingual_saved['consent_link_translations']['fr']['consent_page_ids'] === [10, 11], 'A legacy multilingual save did not preserve its global legal links for French.');

$options[Universal_Legal_Pages::OPTION_NAME] = $multilingual_saved;
$localized_link_input = $multilingual_input;
$localized_link_input['consent_link_translations'] = [
    'fr' => [
        'consent_page_ids' => ['10'],
        'terms_page_id' => '10',
    ],
    'en' => [
        'consent_page_ids' => ['11', '10'],
        'terms_page_id' => '11',
    ],
];
$localized_link_saved = Universal_Legal_Pages::sanitize_options($localized_link_input);
consent_assert($localized_link_saved['consent_link_translations'] === [
    'fr' => ['consent_page_ids' => [10], 'terms_page_id' => 10],
    'en' => ['consent_page_ids' => [11, 10], 'terms_page_id' => 11],
], 'Valid ordered legal-page selections were not stored independently by language.');
consent_assert($localized_link_saved['consent_page_ids'] === [11, 10] && $localized_link_saved['terms_page_id'] === 11, 'Portable link mirrors did not follow the active ReactWP language.');

$options[Universal_Legal_Pages::OPTION_NAME] = $localized_link_saved;
$reactwp_test_current_language = 'fr';
$localized_french_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(array_column($localized_french_config['legalLinks'], 'label') === ['Confidentialité'], 'The active French public links did not use their language selection.');
consent_assert($localized_french_config['termsLink']['label'] === 'Confidentialité', 'The active French terms link did not use its language selection.');
consent_assert(array_column($localized_french_config['linkTranslations']['en']['legalLinks'], 'label') === ['Conditions', 'Confidentialité'], 'The English route-switching links lost their configured order.');
consent_assert($localized_french_config['linkTranslations']['en']['termsLink']['label'] === 'Conditions', 'The English terms link is missing from the route-switching contract.');

$reactwp_test_current_language = 'en';
$settings_errors = [];
$invalid_link_input = $localized_link_input;
$invalid_link_input['consent_link_translations']['fr']['consent_page_ids'][] = '99';
$invalid_link_saved = Universal_Legal_Pages::sanitize_options($invalid_link_input);
consent_assert($invalid_link_saved['consent_link_translations'] === $localized_link_saved['consent_link_translations'], 'One invalid localized page partially replaced the previous language map.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_link_translations', 'Invalid localized links did not emit their stable error code.');

$settings_errors = [];
$unknown_link_language_input = $localized_link_input;
$unknown_link_language_input['consent_link_translations']['es'] = ['consent_page_ids' => [], 'terms_page_id' => '0'];
$unknown_link_language_saved = Universal_Legal_Pages::sanitize_options($unknown_link_language_input);
consent_assert($unknown_link_language_saved['consent_link_translations'] === $localized_link_saved['consent_link_translations'], 'An unknown link language replaced the complete previous map.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_link_translations', 'An unknown link language did not emit the stable error code.');

$settings_errors = [];
$missing_localized_terms_input = $localized_link_input;
$missing_localized_terms_input['consent_link_translations']['fr']['terms_page_id'] = '0';
$missing_localized_terms_saved = Universal_Legal_Pages::sanitize_options($missing_localized_terms_input);
consent_assert($missing_localized_terms_saved['terms_required'] === false, 'Explicit acceptance remained enabled without a terms document in every language.');
consent_assert(end($settings_errors)['code'] === 'missing_terms_page', 'A missing localized terms page did not emit the stable dependency error.');

$options[Universal_Legal_Pages::OPTION_NAME] = $localized_link_saved;
$complete_translations = $multilingual_saved['consent_string_translations'];
$complete_translations['fr']['actions']['revisit'] = 'Gérer les témoins';
$complete_translations['fr']['dialog']['title'] = 'Préférences en français';
$complete_translations['en']['actions']['revisit'] = 'Cookie settings';
$complete_translations['en']['dialog']['title'] = 'English privacy preferences';
$complete_translation_input = array_merge($valid_input, [
    'consent_string_translations' => $complete_translations,
]);
$complete_translation_saved = Universal_Legal_Pages::sanitize_options($complete_translation_input);
consent_assert($complete_translation_saved['consent_string_translations'] === $complete_translations, 'A valid full multilingual copy map was not stored exactly.');
consent_assert($complete_translation_saved['banner_translations']['en']['title'] === 'New English title', 'The legacy banner projection was not preserved.');

$options[Universal_Legal_Pages::OPTION_NAME] = $complete_translation_saved;
$reactwp_test_current_language = 'fr';
$complete_french_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert($complete_french_config['strings']['actions']['revisit'] === 'Gérer les témoins', 'The localized permanent cookie-button label is absent from the public contract.');
consent_assert($complete_french_config['strings']['dialog']['title'] === 'Préférences en français', 'The localized popup copy is absent from the public contract.');
consent_assert($complete_french_config['stringTranslations']['en']['actions']['revisit'] === 'Cookie settings', 'The complete language map is absent from the route-switching contract.');

$reactwp_test_current_language = 'en';
$settings_errors = [];
$invalid_complete_translation_input = $complete_translation_input;
$invalid_complete_translation_input['consent_string_translations']['fr']['actions']['unexpected'] = 'Non';
$invalid_complete_translation_saved = Universal_Legal_Pages::sanitize_options($invalid_complete_translation_input);
consent_assert($invalid_complete_translation_saved['consent_string_translations'] === $complete_translation_saved['consent_string_translations'], 'An unknown localized copy leaf partially replaced the previous language map.');
consent_assert($settings_errors[0]['code'] === 'invalid_consent_string_translations', 'Invalid complete translations did not emit the stable error code.');

$options[Universal_Legal_Pages::OPTION_NAME] = $multilingual_saved;
$settings_errors = [];
$invalid_translation_input = $multilingual_input;
$invalid_translation_input['banner_translations']['es'] = [
    'title' => 'Título desconocido',
    'message' => 'Mensaje desconocido.',
];
$invalid_translation_saved = Universal_Legal_Pages::sanitize_options($invalid_translation_input);
consent_assert($invalid_translation_saved['banner_translations'] === $multilingual_saved['banner_translations'], 'An unknown language key replaced the complete translation map.');
consent_assert($settings_errors[0]['code'] === 'invalid_banner_translations', 'An unknown language key did not emit the stable translation error code.');

$settings_errors = [];
$incomplete_translation_input = $multilingual_input;
unset($incomplete_translation_input['banner_translations']['en']);
$incomplete_translation_saved = Universal_Legal_Pages::sanitize_options($incomplete_translation_input);
consent_assert($incomplete_translation_saved['banner_translations'] === $multilingual_saved['banner_translations'], 'An incomplete translation map replaced the previous valid map.');
consent_assert($settings_errors[0]['code'] === 'invalid_banner_translations', 'An incomplete translation map did not emit the stable translation error code.');

$settings_errors = [];
$scriptable_translation_input = $multilingual_input;
$scriptable_translation_input['banner_translations']['fr']['message'] = '<script>alert(1)</script>';
$scriptable_translation_saved = Universal_Legal_Pages::sanitize_options($scriptable_translation_input);
consent_assert($scriptable_translation_saved['banner_translations'] === $multilingual_saved['banner_translations'], 'Scriptable localized copy replaced the previous valid map.');
consent_assert($settings_errors[0]['code'] === 'invalid_banner_translations', 'Scriptable localized copy did not emit the stable translation error code.');

$settings_errors = [];
$oversized_translation_input = $multilingual_input;
$oversized_translation_input['banner_translations']['en']['title'] = str_repeat('x', 121);
$oversized_translation_saved = Universal_Legal_Pages::sanitize_options($oversized_translation_input);
consent_assert($oversized_translation_saved['banner_translations'] === $multilingual_saved['banner_translations'], 'Oversized localized copy replaced the previous valid map.');
consent_assert($settings_errors[0]['code'] === 'invalid_banner_translations', 'Oversized localized copy did not emit the stable translation error code.');
consent_assert(strpos($settings_errors[0]['message'], 'title') !== false && strpos($settings_errors[0]['message'], 'English (en)') !== false, 'The localized validation error did not identify its field and language.');

ob_start();
Universal_Legal_Pages::render_settings_page();
$invalid_translation_settings_page = ob_get_clean();
consent_assert(strpos($invalid_translation_settings_page, 'id="ulp-consent-copy-error"') !== false, 'The localized editor did not render its associated validation guidance.');
consent_assert(substr_count($invalid_translation_settings_page, 'aria-invalid="true"') === 42, 'Localized interface-copy fields were not associated with the invalid translation state.');
consent_assert(substr_count($invalid_translation_settings_page, 'aria-describedby="ulp-consent-copy-error"') === 42, 'Localized interface-copy fields do not reference their visible validation guidance.');

$reactwp_test_languages = [];
for($language_fixture_index = 0; $language_fixture_index < 18; $language_fixture_index++){
    $reactwp_test_languages[] = [
        'name' => 'Language ' . $language_fixture_index,
        'code' => 'x' . $language_fixture_index,
    ];
}
$reactwp_test_current_language = 'x17';
$eighteen_language_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(
    $eighteen_language_config['currentLanguage'] === 'x17'
    && count($eighteen_language_config['stringTranslations']) === 18
    && count($eighteen_language_config['categoryTranslations']) === 18,
    'A practical 18-language ReactWP site did not receive complete interface and category translation maps.'
);

$reactwp_test_languages = [];
for($language_fixture_index = 0; $language_fixture_index < 33; $language_fixture_index++){
    $reactwp_test_languages[] = [
        'name' => 'Language ' . $language_fixture_index,
        'code' => 'x' . $language_fixture_index,
    ];
}
$oversized_language_config = Universal_Legal_Pages::get_public_consent_config();
consent_assert(!isset($oversized_language_config['currentLanguage'], $oversized_language_config['bannerTranslations']), 'An oversized ReactWP language source reached the public contract.');
consent_assert($oversized_language_config['strings']['title'] === 'New English title', 'Invalid ReactWP language data did not preserve the portable flat fallback.');

ob_start();
Universal_Legal_Pages::render_settings_page();
$invalid_language_settings_page = ob_get_clean();
consent_assert(strpos($invalid_language_settings_page, 'data-ulp-language-editor') === false, 'Invalid ReactWP language data enabled multilingual settings.');
consent_assert(strpos($invalid_language_settings_page, '[consent_strings][title]') !== false, 'Invalid ReactWP language data did not fall back to the portable copy editor.');

$reactwp_test_languages = [
    ['name' => 'Français', 'code' => 'fr'],
];
unset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]);
Universal_Legal_Pages::enqueue_admin_assets('legal_page_page_' . Universal_Legal_Pages::SETTINGS_SLUG);
consent_assert(isset($enqueued_scripts[Universal_Legal_Pages::ADMIN_SCRIPT_HANDLE]), 'A single-language screen still needs the page-order controls.');

$reactwp_test_languages = [
    ['name' => 'Français', 'code' => 'fr'],
    ['name' => 'English', 'code' => 'en'],
];

fwrite(STDOUT, "Universal Legal Pages consent tests passed.\n");
