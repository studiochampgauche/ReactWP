<?php
/*
 * Plugin Name: Universal Legal Pages
 * Description: Manage legal documents, cookie consent, and privacy-aware integrations from one portable WordPress plugin.
 * Author: Studio Champ Gauche
 * Author URI: https://champgauche.studio
 * License: GPL-2.0-or-later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: universal-legal-pages
 * Domain Path: /languages
 * Update URI: false
 * Version: 1.5.0
 */

if(!defined('ABSPATH')){
    exit;
}

final class Universal_Legal_Pages{

    const POST_TYPE = 'legal_page';
    const STYLE_HANDLE = 'universal-legal-pages';
    const ADMIN_STYLE_HANDLE = 'universal-legal-pages-admin';
    const ADMIN_SCRIPT_HANDLE = 'universal-legal-pages-admin';
    const CONSENT_SCRIPT_HANDLE = 'universal-legal-consent';
    const OPTION_NAME = 'universal_legal_pages_options';
    const SETTINGS_GROUP = 'universal_legal_pages_settings';
    const SETTINGS_SLUG = 'universal-legal-pages-consent';
    const AJAX_SAVE_ACTION = 'ulp_save_consent_settings';
    const VERSION = '1.5.0';
    const MAX_REACTWP_LANGUAGES = 32;
    const MAX_CONSENT_PAGES = 1000;
    const MAX_CONSENT_CATEGORIES = 16;
    const MAX_SERVICES = 32;
    const MAX_SERVICE_DOMAINS = 8;
    const MAX_SERVICE_HANDLES = 8;
    const MAX_SCRIPT_QUEUE = 200;
    const MAX_CONTENT_SCAN_BYTES = 1048576;
    const MAX_URL_CANDIDATES = 64;
    const MAX_CUSTOM_INTEGRATIONS = 12;
    const MAX_CUSTOM_SCRIPT_URL_BYTES = 2048;
    const MAX_CUSTOM_INIT_CODE_BYTES = 8192;
    const MAX_CUSTOM_PUBLIC_BYTES = 131072;
    const DETECTED_SERVICES_OPTION = 'universal_legal_pages_detected_services';
    const CONSENT_THEME_STYLESHEET = 'universal-legal-pages/consent-manager.css';

    private static $settings_page_hook = '';

    public static function boot(){

        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'], PHP_INT_MAX);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_menu', [__CLASS__, 'register_settings_page']);
        add_action('wp_ajax_' . self::AJAX_SAVE_ACTION, [__CLASS__, 'ajax_save_settings']);
        add_action('admin_footer', [__CLASS__, 'detect_admin_enqueued_scripts'], PHP_INT_MAX);
        add_action('wp_footer', [__CLASS__, 'detect_admin_enqueued_scripts'], PHP_INT_MAX);

        add_filter('template_include', [__CLASS__, 'use_plugin_template'], PHP_INT_MAX);
        add_filter('body_class', [__CLASS__, 'add_body_class']);
        add_filter('the_content', [__CLASS__, 'filter_external_iframes'], PHP_INT_MAX);
        add_filter('embed_oembed_html', [__CLASS__, 'filter_external_iframes'], PHP_INT_MAX);

    }

    public static function load_textdomain(){

        load_plugin_textdomain(
            'universal-legal-pages',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

    }

    public static function register_post_type(){

        $rewrite_slug = apply_filters('universal_legal_pages_rewrite_slug', 'legal');
        $rewrite_slug = is_string($rewrite_slug) ? sanitize_title($rewrite_slug) : '';

        if($rewrite_slug === ''){
            $rewrite_slug = 'legal';
        }

        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Legal pages', 'universal-legal-pages'),
                'singular_name' => __('Legal page', 'universal-legal-pages'),
                'menu_name' => __('Legal pages', 'universal-legal-pages'),
                'name_admin_bar' => __('Legal page', 'universal-legal-pages'),
                'add_new' => __('Add new', 'universal-legal-pages'),
                'add_new_item' => __('Add new legal page', 'universal-legal-pages'),
                'edit_item' => __('Edit legal page', 'universal-legal-pages'),
                'new_item' => __('New legal page', 'universal-legal-pages'),
                'view_item' => __('View legal page', 'universal-legal-pages'),
                'view_items' => __('View legal pages', 'universal-legal-pages'),
                'search_items' => __('Search legal pages', 'universal-legal-pages'),
                'not_found' => __('No legal pages found.', 'universal-legal-pages'),
                'not_found_in_trash' => __('No legal pages found in Trash.', 'universal-legal-pages'),
                'all_items' => __('All legal pages', 'universal-legal-pages'),
                'archives' => __('Archive of legal pages', 'universal-legal-pages'),
                'attributes' => __('Legal page attributes', 'universal-legal-pages'),
                'insert_into_item' => __('Insert into legal page', 'universal-legal-pages'),
                'uploaded_to_this_item' => __('Uploaded to this legal page', 'universal-legal-pages'),
                'filter_items_list' => __('Filter legal pages list', 'universal-legal-pages'),
                'items_list_navigation' => __('Legal pages list navigation', 'universal-legal-pages'),
                'items_list' => __('Legal pages list', 'universal-legal-pages'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'exclude_from_search' => false,
            'has_archive' => false,
            'hierarchical' => false,
            'rewrite' => [
                'slug' => $rewrite_slug,
                'with_front' => false,
            ],
            'query_var' => true,
            'capability_type' => 'page',
            'map_meta_cap' => true,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-media-document',
            'supports' => [
                'title',
                'editor',
                'revisions',
            ],
            'delete_with_user' => false,
        ]);

    }

    private static function default_consent_strings(){

        return [
            'title' => __('Your privacy choices', 'universal-legal-pages'),
            'message' => __('We use cookies required for the site to work and, with your consent, analytics and marketing tools.', 'universal-legal-pages'),
            'legalLinksLabel' => __('Legal documents', 'universal-legal-pages'),
            'actions' => [
                'acceptAll' => __('Accept all', 'universal-legal-pages'),
                'rejectAll' => __('Reject all', 'universal-legal-pages'),
                'customize' => __('Customize', 'universal-legal-pages'),
                'save' => __('Save my choices', 'universal-legal-pages'),
                'close' => __('Close', 'universal-legal-pages'),
                'revisit' => __('Manage my cookies', 'universal-legal-pages'),
            ],
            'dialog' => [
                'title' => __('Consent preferences', 'universal-legal-pages'),
                'description' => __('Choose the optional categories you authorize. You can change this choice later.', 'universal-legal-pages'),
            ],
            'categories' => [
                'necessary' => [
                    'label' => __('Necessary', 'universal-legal-pages'),
                    'description' => __('Required for site operation and security. Always active.', 'universal-legal-pages'),
                ],
                'preferences' => [
                    'label' => __('Preferences', 'universal-legal-pages'),
                    'description' => __('Remembers your display and functionality preferences.', 'universal-legal-pages'),
                ],
                'analytics' => [
                    'label' => __('Analytics', 'universal-legal-pages'),
                    'description' => __('Measures how the site is used to help improve it.', 'universal-legal-pages'),
                ],
                'marketing' => [
                    'label' => __('Marketing', 'universal-legal-pages'),
                    'description' => __('Measures campaign performance and enables advertising personalization.', 'universal-legal-pages'),
                ],
                'external' => [
                    'label' => __('External content', 'universal-legal-pages'),
                    'description' => __('Loads embedded video and other content supplied by external services.', 'universal-legal-pages'),
                ],
            ],
            'services' => [
                'title' => __('External services', 'universal-legal-pages'),
                'description' => __('Choose which individual external services may load.', 'universal-legal-pages'),
                'blocked' => __('This content is blocked until you allow its service.', 'universal-legal-pages'),
                'allow' => __('Allow this service', 'universal-legal-pages'),
                'unclassified' => __('This service has not been classified and remains blocked.', 'universal-legal-pages'),
            ],
            'terms' => [
                'label' => __('I confirm that I have read and accepted', 'universal-legal-pages'),
                'description' => __('This confirmation is stored in this browser with your privacy choices.', 'universal-legal-pages'),
                'requiredError' => __('Please accept the terms to save this choice.', 'universal-legal-pages'),
            ],
            'gpc' => [
                'notice' => __('Your browser indicates a global privacy preference. Marketing remains disabled.', 'universal-legal-pages'),
            ],
            'error' => [
                'generic' => __('Unable to save this choice. Please try again.', 'universal-legal-pages'),
            ],
        ];

    }

    private static function default_consent_categories($strings = null){

        $strings = is_array($strings) ? $strings : self::default_consent_strings();
        $copy = isset($strings['categories']) && is_array($strings['categories'])
            ? $strings['categories']
            : self::default_consent_strings()['categories'];
        $categories = [];

        foreach(['necessary'] as $id){
            $fallback = self::default_consent_strings()['categories'][$id];
            $category_copy = isset($copy[$id]) && is_array($copy[$id]) ? $copy[$id] : [];
            $label = self::trim_plain_text($category_copy['label'] ?? '', 80);
            $description = self::trim_plain_text($category_copy['description'] ?? '', 300, true);
            $categories[$id] = [
                'label' => $label !== '' ? $label : $fallback['label'],
                'description' => $description !== '' ? $description : $fallback['description'],
                'translations' => [],
            ];
        }

        return $categories;

    }

    private static function consent_string_schema(){

        return [
            'title' => ['label' => __('Banner title', 'universal-legal-pages'), 'group' => 'banner', 'maximum' => 120, 'textarea' => false],
            'message' => ['label' => __('Banner message', 'universal-legal-pages'), 'group' => 'banner', 'maximum' => 600, 'textarea' => true],
            'actions.acceptAll' => ['label' => __('Accept all', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'actions.rejectAll' => ['label' => __('Reject all', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'actions.customize' => ['label' => __('Customize', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'actions.save' => ['label' => __('Save choices', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'actions.close' => ['label' => __('Close dialog', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'actions.revisit' => ['label' => __('Reopen preferences', 'universal-legal-pages'), 'group' => 'actions', 'maximum' => 80, 'textarea' => false],
            'dialog.title' => ['label' => __('Dialog title', 'universal-legal-pages'), 'group' => 'dialog', 'maximum' => 120, 'textarea' => false],
            'dialog.description' => ['label' => __('Dialog description', 'universal-legal-pages'), 'group' => 'dialog', 'maximum' => 500, 'textarea' => true],
            'legalLinksLabel' => ['label' => __('Legal links accessible name', 'universal-legal-pages'), 'group' => 'dialog', 'maximum' => 120, 'textarea' => false],
            'categories.necessary.label' => ['label' => __('Necessary — label', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 80, 'textarea' => false],
            'categories.necessary.description' => ['label' => __('Necessary — description', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 300, 'textarea' => true],
            'categories.preferences.label' => ['label' => __('Preferences — label', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 80, 'textarea' => false],
            'categories.preferences.description' => ['label' => __('Preferences — description', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 300, 'textarea' => true],
            'categories.analytics.label' => ['label' => __('Analytics — label', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 80, 'textarea' => false],
            'categories.analytics.description' => ['label' => __('Analytics — description', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 300, 'textarea' => true],
            'categories.marketing.label' => ['label' => __('Marketing — label', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 80, 'textarea' => false],
            'categories.marketing.description' => ['label' => __('Marketing — description', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 300, 'textarea' => true],
            'categories.external.label' => ['label' => __('External content — label', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 80, 'textarea' => false],
            'categories.external.description' => ['label' => __('External content — description', 'universal-legal-pages'), 'group' => 'categories', 'maximum' => 300, 'textarea' => true],
            'services.title' => ['label' => __('Services — title', 'universal-legal-pages'), 'group' => 'services', 'maximum' => 120, 'textarea' => false],
            'services.description' => ['label' => __('Services — description', 'universal-legal-pages'), 'group' => 'services', 'maximum' => 300, 'textarea' => true],
            'services.blocked' => ['label' => __('Blocked content message', 'universal-legal-pages'), 'group' => 'services', 'maximum' => 300, 'textarea' => true],
            'services.allow' => ['label' => __('Allow-service button', 'universal-legal-pages'), 'group' => 'services', 'maximum' => 80, 'textarea' => false],
            'services.unclassified' => ['label' => __('Unclassified-service message', 'universal-legal-pages'), 'group' => 'services', 'maximum' => 300, 'textarea' => true],
            'terms.label' => ['label' => __('Terms — label', 'universal-legal-pages'), 'group' => 'status', 'maximum' => 160, 'textarea' => false],
            'terms.description' => ['label' => __('Terms — description', 'universal-legal-pages'), 'group' => 'status', 'maximum' => 300, 'textarea' => true],
            'terms.requiredError' => ['label' => __('Required terms error', 'universal-legal-pages'), 'group' => 'status', 'maximum' => 240, 'textarea' => true],
            'gpc.notice' => ['label' => __('Global Privacy Control notice', 'universal-legal-pages'), 'group' => 'status', 'maximum' => 300, 'textarea' => true],
            'error.generic' => ['label' => __('Generic error', 'universal-legal-pages'), 'group' => 'status', 'maximum' => 240, 'textarea' => true],
        ];

    }

    public static function default_options(){

        $consent_strings = self::default_consent_strings();

        return [
            'consent_enabled' => false,
            'banner_title' => $consent_strings['title'],
            'banner_message' => $consent_strings['message'],
            'banner_translations' => [],
            'consent_strings' => $consent_strings,
            'consent_string_translations' => [],
            'consent_categories' => self::default_consent_categories($consent_strings),
            'consent_page_ids' => [],
            'terms_page_id' => 0,
            'consent_link_translations' => [],
            'terms_required' => false,
            'policy_version' => '1',
            'duration_days' => 180,
            'respect_gpc' => true,
            'show_revisit_button' => true,
            'ga4_measurement_id' => '',
            'ga4_category' => '',
            'gtm_container_id' => '',
            'gtm_category' => '',
            'google_ads_id' => '',
            'google_ads_category' => '',
            'meta_pixel_id' => '',
            'meta_pixel_category' => '',
            'custom_integrations' => [],
            'services' => [],
        ];

    }

    private static function default_category_ids(){

        return ['necessary', 'preferences', 'analytics', 'marketing', 'external'];

    }

    private static function service_purposes(){

        return ['necessary', 'preferences', 'analytics', 'marketing', 'external'];

    }

    private static function category_id_is_valid($value){

        return is_string($value)
            && (
                in_array($value, self::default_category_ids(), true)
                || preg_match('/\Acategory-[a-f0-9]{16}\z/', $value) === 1
            );

    }

    private static function service_categories($categories = null){

        $ids = is_array($categories)
            ? array_keys($categories)
            : array_keys(self::default_consent_categories());
        $ids[] = 'unclassified';

        return array_values(array_unique($ids));

    }

    private static function service_kinds(){

        return ['integration', 'iframe', 'script', 'pixel', 'unknown'];

    }

    private static function service_id_is_valid($value){

        return is_string($value)
            && preg_match('/\A[a-z0-9][a-z0-9._-]{0,63}\z/', $value) === 1;

    }

    private static function service_domain_is_valid($value){

        if(!is_string($value) || strlen($value) > 253){
            return false;
        }

        $domain = strtolower(rtrim(trim($value), '.'));

        if($domain === '' || preg_match('/[\x00-\x20\x7F]/', $domain)){
            return false;
        }

        if(filter_var($domain, FILTER_VALIDATE_IP) !== false){
            return true;
        }

        return preg_match('/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/', $domain) === 1;

    }

    private static function normalize_service_domain($value){

        if(!self::service_domain_is_valid($value)){
            return '';
        }

        return strtolower(rtrim(trim($value), '.'));

    }

    private static function integration_categories($categories = null){

        $categories = is_array($categories) ? $categories : self::default_consent_categories();
        $ids = [];

        foreach($categories as $id => $definition){
            if(is_array($definition)){
                $ids[] = $id;
            }
        }

        return $ids;

    }

    private static function category_service_purpose($category){

        return in_array($category, self::service_purposes(), true)
            ? $category
            : 'external';

    }

    private static function integration_category_reference($value, $categories = null, $preferred = ''){

        $available = self::integration_categories($categories);

        if($value === ''){
            return '';
        }

        if(is_string($value) && in_array($value, $available, true)){
            return $value;
        }

        if($preferred !== '' && in_array($preferred, $available, true)){
            return $preferred;
        }

        return '';

    }

    private static function classifiable_service_categories($categories = null){

        return array_merge(self::integration_categories($categories), ['unclassified']);

    }

    private static function custom_integration_id_is_valid($value){

        return is_string($value)
            && preg_match('/\Acustom-[a-f0-9]{16}\z/', $value) === 1;

    }

    private static function normalize_url_percent_encoding($value){

        if(!is_string($value) || preg_match('/%(?![A-Fa-f0-9]{2})/', $value)){
            return null;
        }

        return preg_replace_callback('/%([A-Fa-f0-9]{2})/', function($match){
            $character = chr(hexdec($match[1]));

            return preg_match('/[A-Za-z0-9._~-]/', $character)
                ? $character
                : '%' . strtoupper($match[1]);
        }, $value);

    }

    private static function normalize_custom_script_url($value){

        if(!is_string($value)
            || $value === ''
            || strlen($value) > self::MAX_CUSTOM_SCRIPT_URL_BYTES
            || preg_match('/[\x00-\x20\x7F\\\\]/', $value)
            || preg_match('/[<>"\']/', $value)
            || strpos($value, '#') !== false){
            return '';
        }

        $parts = parse_url($value);

        if(!is_array($parts)
            || empty($parts['scheme'])
            || strtolower($parts['scheme']) !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])){
            return '';
        }

        $domain = self::normalize_service_domain($parts['host']);

        if($domain === ''){
            return '';
        }

        $port = '';

        if(isset($parts['port'])){
            $port_number = (int)$parts['port'];

            if($port_number < 1 || $port_number > 65535){
                return '';
            }

            if($port_number !== 443){
                $port = ':' . $port_number;
            }
        }

        $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
        $path = self::normalize_url_percent_encoding($path);
        $query = isset($parts['query']) && $parts['query'] !== ''
            ? self::normalize_url_percent_encoding($parts['query'])
            : '';

        if(!is_string($path)
            || $path === ''
            || $path[0] !== '/'
            || !is_string($query)
            || preg_match('#(?:\A|/)\.{1,2}(?:/|\z)#', $path)){
            return '';
        }

        $canonical = 'https://' . $domain . $port . $path . ($query !== '' ? '?' . $query : '');

        if(strlen($canonical) > self::MAX_CUSTOM_SCRIPT_URL_BYTES){
            return '';
        }

        $safe = esc_url_raw($canonical);

        return is_string($safe) && $safe === $canonical ? $canonical : '';

    }

    private static function custom_init_code_is_valid($value){

        return is_string($value)
            && strlen($value) <= self::MAX_CUSTOM_INIT_CODE_BYTES
            && strpos($value, "\0") === false
            && preg_match('//u', $value) === 1;

    }

    private static function normalize_custom_integration_record($value, $categories = null){

        if(!is_array($value)){
            return null;
        }

        $keys = array_keys($value);
        sort($keys);

        $has_purpose = array_key_exists('purpose', $value);
        $expected_keys = $has_purpose
            ? ['category', 'init_code', 'label', 'purpose', 'script_url']
            : ['category', 'init_code', 'label', 'script_url'];
        if($keys !== $expected_keys
            || !self::consent_string_value_is_valid($value['label'], 80, false)
            || !is_string($value['category'])
            || ($value['category'] !== ''
                && !in_array($value['category'], self::integration_categories($categories), true))
            || ($has_purpose
                && (!is_string($value['purpose']) || !in_array($value['purpose'], self::service_purposes(), true)))
            || !self::custom_init_code_is_valid($value['init_code'])){
            return null;
        }

        $script_url = self::normalize_custom_script_url($value['script_url']);

        if($script_url === ''){
            return null;
        }

        return [
            'label' => self::trim_plain_text($value['label'], 80),
            'category' => $value['category'],
            'script_url' => $script_url,
            'init_code' => $value['init_code'],
        ];

    }

    private static function normalize_stored_custom_integrations($value, $categories = null){

        if(!is_array($value) || count($value) > self::MAX_CUSTOM_INTEGRATIONS){
            return [];
        }

        $integrations = [];
        $urls = [];

        foreach($value as $id => $definition){
            if(!self::custom_integration_id_is_valid($id)){
                return [];
            }

            $normalized = self::normalize_custom_integration_record($definition, $categories);

            if($normalized === null || isset($urls[$normalized['script_url']])){
                return [];
            }

            $integrations[$id] = $normalized;
            $urls[$normalized['script_url']] = true;
        }

        ksort($integrations, SORT_STRING);

        return self::custom_integrations_fit_public_budget($integrations)
            ? $integrations
            : [];

    }

    private static function custom_integration_id($definition){

        $material = wp_json_encode($definition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'custom-' . substr(hash('sha256', is_string($material) ? $material : ''), 0, 16);

    }

    private static function public_custom_integrations($integrations, $categories = null){

        $public = [];

        foreach(self::normalize_stored_custom_integrations($integrations, $categories) as $id => $definition){
            if($definition['category'] === ''){
                continue;
            }

            $public[] = [
                'id' => $id,
                'scriptUrl' => $definition['script_url'],
                'initCode' => $definition['init_code'],
            ];
        }

        return $public;

    }

    private static function custom_integrations_fit_public_budget($integrations){

        if(empty($integrations)){
            return true;
        }

        $public = [];

        foreach($integrations as $id => $definition){
            $public[] = [
                'id' => $id,
                'scriptUrl' => $definition['script_url'],
                'initCode' => $definition['init_code'],
            ];
        }

        $json = wp_json_encode(
            $public,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return is_string($json) && strlen($json) <= self::MAX_CUSTOM_PUBLIC_BYTES;

    }

    private static function default_detected_service($domain, $kind, $handle = ''){

        if($kind === 'iframe' && in_array($domain, ['www.youtube.com', 'www.youtube-nocookie.com'], true)){
            return [
                'id' => 'youtube',
                'label' => 'YouTube',
                'category' => 'unclassified',
                'domains' => ['www.youtube.com', 'www.youtube-nocookie.com'],
                'kind' => 'iframe',
                'managed' => true,
                'handles' => [],
            ];
        }

        if($kind === 'iframe' && $domain === 'player.vimeo.com'){
            return [
                'id' => 'vimeo',
                'label' => 'Vimeo',
                'category' => 'unclassified',
                'domains' => [$domain],
                'kind' => 'iframe',
                'managed' => true,
                'handles' => [],
            ];
        }

        $prefix = $kind === 'script' ? 'script-' : 'external-';

        return [
            'id' => $prefix . substr(hash('sha256', $domain), 0, 16),
            'label' => $domain,
            'category' => 'unclassified',
            'domains' => [$domain],
            'kind' => $kind,
            'managed' => $kind === 'iframe',
            'handles' => $kind === 'script' && self::script_handle_is_valid($handle) ? [$handle] : [],
        ];

    }

    private static function script_handle_is_valid($value){

        return is_string($value)
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,63}\z/', $value) === 1;

    }

    private static function normalize_detected_service_registry($value){

        if(!is_array($value)){
            return [];
        }

        $registry = [];

        foreach(array_slice($value, 0, self::MAX_SERVICES, true) as $id => $relation){
            if(!self::service_id_is_valid($id) || !is_array($relation)){
                continue;
            }

            $keys = array_keys($relation);
            sort($keys);

            if($keys !== ['domains', 'handles', 'kind', 'managed']
                || !in_array($relation['kind'], self::service_kinds(), true)
                || !is_bool($relation['managed'])
                || !is_array($relation['domains'])
                || empty($relation['domains'])
                || count($relation['domains']) > self::MAX_SERVICE_DOMAINS
                || !is_array($relation['handles'])
                || count($relation['handles']) > self::MAX_SERVICE_HANDLES){
                continue;
            }

            $domains = [];

            foreach($relation['domains'] as $raw_domain){
                $domain = self::normalize_service_domain($raw_domain);

                if($domain === '' || in_array($domain, $domains, true)){
                    $domains = [];
                    break;
                }

                $domains[] = $domain;
            }

            if(empty($domains)){
                continue;
            }

            $handles = [];

            foreach($relation['handles'] as $raw_handle){
                if(!self::script_handle_is_valid($raw_handle) || in_array($raw_handle, $handles, true)){
                    $handles = [];
                    break;
                }

                $handles[] = $raw_handle;
            }

            if($relation['kind'] === 'script' && empty($handles)){
                continue;
            }

            sort($domains, SORT_STRING);
            sort($handles, SORT_STRING);
            $kind = $relation['kind'];
            $registry[$id] = [
                'domains' => $domains,
                'handles' => $handles,
                'kind' => $kind,
                'managed' => $kind === 'script' ? false : $relation['managed'],
            ];
        }

        ksort($registry, SORT_STRING);

        return $registry;

    }

    private static function get_detected_service_registry(){

        return self::normalize_detected_service_registry(
            get_option(self::DETECTED_SERVICES_OPTION, [])
        );

    }

    private static function persist_detected_service_registry($registry){

        $registry = self::normalize_detected_service_registry($registry);
        $stored = self::get_detected_service_registry();

        if($registry === $stored){
            return false;
        }

        if(get_option(self::DETECTED_SERVICES_OPTION, null) === null){
            return add_option(self::DETECTED_SERVICES_OPTION, $registry, '', false);
        }

        return update_option(self::DETECTED_SERVICES_OPTION, $registry, false);

    }

    private static function merge_detected_service(&$registry, $definition){

        if(!is_array($definition)
            || !isset($definition['id'], $definition['domains'], $definition['kind'], $definition['managed'], $definition['handles'])
            || !self::service_id_is_valid($definition['id'])){
            return false;
        }

        $id = $definition['id'];
        $domains = [];

        foreach(array_slice($definition['domains'], 0, self::MAX_SERVICE_DOMAINS) as $raw_domain){
            $domain = self::normalize_service_domain($raw_domain);

            if($domain === '' || in_array($domain, $domains, true)){
                continue;
            }

            $domains[] = $domain;
        }

        sort($domains, SORT_STRING);

        if(empty($domains) || !in_array($definition['kind'], self::service_kinds(), true)){
            return false;
        }

        if(!isset($registry[$id])){
            if(count($registry) >= self::MAX_SERVICES){
                return false;
            }

            $registry[$id] = [
                'domains' => $domains,
                'handles' => $definition['kind'] === 'script' && isset($definition['handles'][0]) && self::script_handle_is_valid($definition['handles'][0])
                    ? [$definition['handles'][0]]
                    : [],
                'kind' => $definition['kind'],
                'managed' => $definition['kind'] === 'script' ? false : (bool)$definition['managed'],
            ];
            ksort($registry, SORT_STRING);
            return true;
        }

        if($registry[$id]['kind'] !== $definition['kind']){
            return false;
        }

        $changed = false;

        foreach($domains as $domain){
            if(in_array($domain, $registry[$id]['domains'], true)
                || count($registry[$id]['domains']) >= self::MAX_SERVICE_DOMAINS){
                continue;
            }

            $registry[$id]['domains'][] = $domain;
            $changed = true;
        }

        if($changed){
            sort($registry[$id]['domains'], SORT_STRING);
        }

        $handle = isset($definition['handles'][0]) ? $definition['handles'][0] : '';

        if($definition['kind'] === 'script'
            && self::script_handle_is_valid($handle)
            && !in_array($handle, $registry[$id]['handles'], true)
            && count($registry[$id]['handles']) < self::MAX_SERVICE_HANDLES){
            $registry[$id]['handles'][] = $handle;
            sort($registry[$id]['handles'], SORT_STRING);
            $changed = true;
        }

        return $changed;

    }

    private static function normalize_stored_service_settings($value, $registry, $categories = null){

        if(!is_array($value) || count($value) > self::MAX_SERVICES){
            return [];
        }

        $services = [];

        foreach($value as $id => $settings){
            if(!self::service_id_is_valid($id) || !isset($registry[$id]) || !is_array($settings)){
                continue;
            }

            $keys = array_keys($settings);
            sort($keys);

            if($keys !== ['category', 'label']
                || !in_array($settings['category'], self::classifiable_service_categories($categories), true)
                || !self::consent_string_value_is_valid($settings['label'], 80, false)){
                continue;
            }

            $services[$id] = [
                'label' => self::trim_plain_text($settings['label'], 80),
                'category' => $settings['category'],
            ];
        }

        ksort($services, SORT_STRING);

        return $services;

    }

    private static function sanitize_service_settings($value, $previous, $registry, $categories = null){

        $invalid = !is_array($value) || count($value) > self::MAX_SERVICES;
        $services = [];

        if(!$invalid){
            foreach($value as $id => $settings){
                if(!self::service_id_is_valid($id) || !isset($registry[$id]) || !is_array($settings)){
                    $invalid = true;
                    break;
                }

                $keys = array_keys($settings);
                sort($keys);

                if($keys !== ['category', 'label']
                    || !is_string($settings['category'])
                    || !in_array($settings['category'], self::classifiable_service_categories($categories), true)
                    || !self::consent_string_value_is_valid($settings['label'] ?? null, 80, false)){
                    $invalid = true;
                    break;
                }

                $services[$id] = [
                    'label' => self::trim_plain_text($settings['label'], 80),
                    'category' => $settings['category'],
                ];
            }
        }

        if($invalid){
            self::add_field_error(
                'invalid_services',
                __('Service settings are invalid; the complete previous service registry was preserved.', 'universal-legal-pages')
            );

            return is_array($previous) ? $previous : [];
        }

        ksort($services, SORT_STRING);

        return $services;

    }

    private static function public_service_definitions_are_valid($value, $categories = null){

        if(!is_array($value) || count($value) > self::MAX_SERVICES){
            return false;
        }

        $ids = [];

        foreach($value as $definition){
            if(!is_array($definition)){
                return false;
            }

            $keys = array_keys($definition);
            sort($keys);

            if($keys !== ['category', 'domains', 'id', 'kind', 'label', 'managed', 'purpose']
                || !self::service_id_is_valid($definition['id'])
                || in_array($definition['id'], $ids, true)
                || !self::consent_string_value_is_valid($definition['label'], 80, false)
                || !in_array($definition['category'], self::service_categories($categories), true)
                || !is_string($definition['purpose'])
                || !in_array($definition['purpose'], self::service_purposes(), true)
                || !in_array($definition['kind'], self::service_kinds(), true)
                || !is_bool($definition['managed'])
                || !is_array($definition['domains'])
                || empty($definition['domains'])
                || count($definition['domains']) > self::MAX_SERVICE_DOMAINS){
                return false;
            }

            $domains = [];

            foreach($definition['domains'] as $raw_domain){
                $domain = self::normalize_service_domain($raw_domain);

                if($domain === '' || in_array($domain, $domains, true)){
                    return false;
                }

                $domains[] = $domain;
            }

            $ids[] = $definition['id'];
        }

        return true;

    }

    private static function canonicalize_public_services($services){

        $canonical = [];

        foreach($services as $definition){
            $domains = array_map([__CLASS__, 'normalize_service_domain'], $definition['domains']);
            sort($domains, SORT_STRING);
            $canonical[] = [
                'id' => $definition['id'],
                'label' => self::trim_plain_text($definition['label'], 80),
                'category' => $definition['category'],
                'purpose' => $definition['purpose'],
                'domains' => $domains,
                'kind' => $definition['kind'],
                'managed' => (bool)$definition['managed'],
            ];
        }

        usort($canonical, function($first, $second){
            return strcmp($first['id'], $second['id']);
        });

        return $canonical;

    }

    private static function integration_services($options){

        $categories = isset($options['consent_categories']) && is_array($options['consent_categories'])
            ? $options['consent_categories']
            : self::default_consent_categories();
        $services = [];

        $definitions = [
            'ga4_measurement_id' => [
                'id' => 'google-analytics-4',
                'label' => __('Google Analytics 4', 'universal-legal-pages'),
                'category' => self::integration_category_reference($options['ga4_category'] ?? '', $categories, 'analytics'),
                'purpose' => 'analytics',
                'domains' => ['www.google-analytics.com', 'www.googletagmanager.com'],
                'kind' => 'integration',
                'managed' => true,
            ],
            'google_ads_id' => [
                'id' => 'google-ads-remarketing',
                'label' => __('Google Ads', 'universal-legal-pages'),
                'category' => self::integration_category_reference($options['google_ads_category'] ?? '', $categories, 'marketing'),
                'purpose' => 'marketing',
                'domains' => ['googleads.g.doubleclick.net', 'www.googletagmanager.com'],
                'kind' => 'integration',
                'managed' => true,
            ],
            'meta_pixel_id' => [
                'id' => 'meta-pixel',
                'label' => __('Meta Pixel', 'universal-legal-pages'),
                'category' => self::integration_category_reference($options['meta_pixel_category'] ?? '', $categories, 'marketing'),
                'purpose' => 'marketing',
                'domains' => ['connect.facebook.net', 'www.facebook.com'],
                'kind' => 'pixel',
                'managed' => true,
            ],
        ];

        foreach($definitions as $option_key => $definition){
            if(isset($options[$option_key])
                && is_string($options[$option_key])
                && $options[$option_key] !== ''
                && $definition['category'] !== ''){
                $services[] = $definition;
            }
        }

        $custom_integrations = isset($options['custom_integrations'])
            ? self::normalize_stored_custom_integrations($options['custom_integrations'], $categories)
            : [];

        foreach($custom_integrations as $id => $definition){
            if(count($services) >= self::MAX_SERVICES){
                break;
            }

            if($definition['category'] === ''){
                continue;
            }

            $domain = self::normalize_service_domain(parse_url($definition['script_url'], PHP_URL_HOST));

            if($domain === ''){
                continue;
            }

            $services[] = [
                'id' => $id,
                'label' => $definition['label'],
                'category' => $definition['category'],
                'purpose' => self::category_service_purpose($definition['category']),
                'domains' => [$domain],
                'kind' => 'integration',
                'managed' => true,
            ];
        }

        return $services;

    }

    public static function get_public_services($options = null, $detected_registry = null){

        $options = is_array($options) ? $options : self::get_options();
        $detected_registry = is_array($detected_registry)
            ? self::normalize_detected_service_registry($detected_registry)
            : self::get_detected_service_registry();
        $categories = isset($options['consent_categories']) && is_array($options['consent_categories'])
            ? $options['consent_categories']
            : self::default_consent_categories();
        $services = self::integration_services($options);
        $configured = self::normalize_stored_service_settings(
            $options['services'] ?? [],
            $detected_registry,
            $categories
        );

        foreach($detected_registry as $id => $relation){
            if(count($services) >= self::MAX_SERVICES){
                break;
            }

            if(empty($relation['managed'])){
                continue;
            }

            if(in_array($id, array_column($services, 'id'), true)){
                continue;
            }

            $settings = isset($configured[$id]) && is_array($configured[$id])
                ? $configured[$id]
                : [];

            if(empty($settings)
                || !isset($settings['category'])
                || $settings['category'] === 'unclassified'){
                continue;
            }

            $default = self::default_detected_service($relation['domains'][0], $relation['kind']);
            $services[] = [
                'id' => $id,
                'label' => isset($settings['label']) ? $settings['label'] : $default['label'],
                'category' => $settings['category'],
                'purpose' => self::category_service_purpose($settings['category']),
                'domains' => $relation['domains'],
                'kind' => $relation['kind'],
                'managed' => (bool)$relation['managed'],
            ];
        }

        $services = self::canonicalize_public_services($services);
        $filtered = apply_filters('universal_legal_pages_services', $services, $options);

        if(!self::public_service_definitions_are_valid($filtered, $categories)){
            return $services;
        }

        $filtered = array_values(array_filter($filtered, function($definition){
            return !empty($definition['managed'])
                && $definition['category'] !== 'unclassified';
        }));

        return self::canonicalize_public_services($filtered);

    }

    private static function service_registry_version($services, $custom_integrations = [], $categories = null){

        $categories = is_array($categories) ? $categories : self::default_consent_categories();
        $custom_integrations = self::normalize_stored_custom_integrations($custom_integrations, $categories);

        $records = [];

        foreach($services as $service){
            $domains = $service['domains'];
            sort($domains, SORT_STRING);
            $record = [
                'id' => $service['id'],
                'category' => $service['category'],
                'purpose' => $service['purpose'],
                'domains' => $domains,
                'kind' => $service['kind'],
                'managed' => (bool)$service['managed'],
            ];

            if(isset($custom_integrations[$service['id']])){
                $definition = $custom_integrations[$service['id']];
                $record['customDefinition'] = [
                    'label' => $definition['label'],
                    'category' => $definition['category'],
                    'scriptUrl' => $definition['script_url'],
                    'initCode' => $definition['init_code'],
                ];
            }

            $records[] = $record;
        }

        foreach($categories as $id => $definition){
            $records[] = [
                'categoryId' => $id,
            ];
        }

        usort($records, function($first, $second){
            $first_key = isset($first['id']) ? 'service:' . $first['id'] : 'category:' . $first['categoryId'];
            $second_key = isset($second['id']) ? 'service:' . $second['id'] : 'category:' . $second['categoryId'];

            return strcmp($first_key, $second_key);
        });

        $json = wp_json_encode($records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return substr(hash('sha256', is_string($json) ? $json : '[]'), 0, 24);

    }

    private static function current_site_domain(){

        $url = function_exists('home_url') ? home_url('/') : '';
        $parts = is_string($url) ? parse_url($url) : false;

        return is_array($parts) && isset($parts['host'])
            ? self::normalize_service_domain($parts['host'])
            : '';

    }

    private static function external_url_domain($raw_url){

        if(!is_string($raw_url) || strlen($raw_url) > 2048 || preg_match('/[\x00-\x20\x7F]/', $raw_url)){
            return '';
        }

        $url = html_entity_decode(trim($raw_url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if(strpos($url, '//') === 0){
            $url = 'https:' . $url;
        }

        $parts = parse_url($url);

        if(!is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || isset($parts['fragment'])){
            return '';
        }

        $domain = self::normalize_service_domain($parts['host']);

        return $domain !== self::current_site_domain() ? $domain : '';

    }

    private static function normalized_embed_query($query, $allowed_keys){

        if(!is_string($query) || $query === ''){
            return '';
        }

        $pairs = preg_split('/[&;]/', $query);

        if(!is_array($pairs) || count($pairs) > 20){
            return null;
        }

        $normalized = [];

        foreach($pairs as $pair){
            $parts = explode('=', $pair, 2);
            $key = rawurldecode($parts[0]);
            $value = isset($parts[1]) ? rawurldecode($parts[1]) : '';

            if(!in_array($key, $allowed_keys, true)
                || array_key_exists($key, $normalized)
                || strlen($value) > 128
                || preg_match('/\A[A-Za-z0-9._~-]*\z/', $value) !== 1){
                return null;
            }

            $normalized[$key] = $value;
        }

        ksort($normalized, SORT_STRING);

        return http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);

    }

    private static function normalize_external_iframe_url($raw_url){

        if(!is_string($raw_url) || strlen($raw_url) > 2048){
            return null;
        }

        $decoded = html_entity_decode(trim($raw_url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if($decoded === '' || preg_match('/[\x00-\x20\x7F]/', $decoded)){
            return null;
        }

        if($decoded[0] === '/' && strpos($decoded, '//') !== 0){
            return ['external' => false];
        }

        if(strpos($decoded, '//') === 0){
            $decoded = 'https:' . $decoded;
        }

        $parts = parse_url($decoded);

        if(!is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || isset($parts['fragment'])){
            return null;
        }

        $domain = self::normalize_service_domain($parts['host']);

        if($domain === ''){
            return null;
        }

        if($domain === self::current_site_domain()){
            return ['external' => false];
        }

        $path = isset($parts['path']) ? $parts['path'] : '/';
        $query = isset($parts['query']) ? $parts['query'] : '';

        if(in_array($domain, ['www.youtube.com', 'www.youtube-nocookie.com'], true)){
            if(preg_match('#\A/embed/([A-Za-z0-9_-]{6,64})/?\z#', $path, $match) !== 1){
                return null;
            }

            $query = self::normalized_embed_query($query, [
                'autoplay', 'cc_lang_pref', 'cc_load_policy', 'color', 'controls', 'disablekb', 'end', 'fs',
                'hl', 'iv_load_policy', 'loop', 'modestbranding', 'mute', 'playlist',
                'playsinline', 'rel', 'start'
            ]);

            if($query === null){
                return null;
            }

            $url = 'https://www.youtube-nocookie.com/embed/' . $match[1];
            $domain = 'www.youtube-nocookie.com';

            if($query !== ''){
                $url .= '?' . $query;
            }
        }elseif($domain === 'player.vimeo.com'){
            if(preg_match('#\A/video/([0-9]{1,20})/?\z#', $path, $match) !== 1){
                return null;
            }

            $query = self::normalized_embed_query($query, [
                'autopause', 'autoplay', 'background', 'byline', 'color', 'controls', 'dnt',
                'loop', 'muted', 'playsinline', 'portrait', 'quality', 'responsive', 'speed',
                'texttrack', 'title', 'transparent'
            ]);

            if($query === null){
                return null;
            }

            $url = 'https://player.vimeo.com/video/' . $match[1];

            if($query !== ''){
                $url .= '?' . $query;
            }
        }else{
            $url = esc_url_raw($decoded);

            if(!is_string($url) || $url === ''){
                return null;
            }
        }

        return [
            'external' => true,
            'url' => $url,
            'domain' => $domain,
        ];

    }

    private static function find_html_tag_end($html, $start){

        $length = strlen($html);
        $quote = '';

        for($index = $start; $index < $length; $index++){
            $character = $html[$index];

            if($quote !== ''){
                if($character === $quote){
                    $quote = '';
                }
                continue;
            }

            if($character === '"' || $character === "'"){
                $quote = $character;
                continue;
            }

            if($character === '>'){
                return $index;
            }
        }

        return -1;

    }

    private static function parse_iframe_opening_tag($tag){

        $length = strlen($tag);

        if($length < 9 || strncasecmp($tag, '<iframe', 7) !== 0 || $tag[$length - 1] !== '>'){
            return null;
        }

        $cursor = 7;
        $limit = $length - 1;
        $attributes = [];
        $self_closing = false;

        while($cursor < $limit){
            while($cursor < $limit && preg_match('/\s/', $tag[$cursor])){
                $cursor++;
            }

            if($cursor >= $limit){
                break;
            }

            if($tag[$cursor] === '/'){
                $cursor++;

                while($cursor < $limit && preg_match('/\s/', $tag[$cursor])){
                    $cursor++;
                }

                if($cursor !== $limit){
                    return null;
                }

                $self_closing = true;
                break;
            }

            $name_start = $cursor;

            while($cursor < $limit && preg_match('/[^\s"\'<>\/=]/', $tag[$cursor])){
                $cursor++;
            }

            if($cursor === $name_start){
                return null;
            }

            $name = strtolower(substr($tag, $name_start, $cursor - $name_start));

            if(isset($attributes[$name])){
                return null;
            }

            while($cursor < $limit && preg_match('/\s/', $tag[$cursor])){
                $cursor++;
            }

            $value = null;

            if($cursor < $limit && $tag[$cursor] === '='){
                $cursor++;

                while($cursor < $limit && preg_match('/\s/', $tag[$cursor])){
                    $cursor++;
                }

                if($cursor >= $limit){
                    return null;
                }

                if($tag[$cursor] === '"' || $tag[$cursor] === "'"){
                    $quote = $tag[$cursor];
                    $cursor++;
                    $value_start = $cursor;

                    while($cursor < $limit && $tag[$cursor] !== $quote){
                        $cursor++;
                    }

                    if($cursor >= $limit){
                        return null;
                    }

                    $value = substr($tag, $value_start, $cursor - $value_start);
                    $cursor++;
                }else{
                    $value_start = $cursor;

                    while($cursor < $limit && !preg_match('/\s/', $tag[$cursor])){
                        if(strpos('"\'<=`', $tag[$cursor]) !== false){
                            return null;
                        }
                        $cursor++;
                    }

                    if($cursor === $value_start){
                        return null;
                    }

                    $value = substr($tag, $value_start, $cursor - $value_start);
                }
            }

            $attributes[$name] = $value;
        }

        return [
            'attributes' => $attributes,
            'selfClosing' => $self_closing,
        ];

    }

    private static function find_iframe_closing_tag($html, $start){

        $cursor = $start;

        while(($closing_start = stripos($html, '</iframe', $cursor)) !== false){
            $boundary_index = $closing_start + 8;
            $boundary = isset($html[$boundary_index]) ? $html[$boundary_index] : '';

            if($boundary !== '' && $boundary !== '>' && !preg_match('/\s/', $boundary)){
                $cursor = $boundary_index;
                continue;
            }

            $closing_end = self::find_html_tag_end($html, $boundary_index);

            if($closing_end < 0){
                return [
                    'end' => strlen($html) - 1,
                    'valid' => false,
                ];
            }

            $remainder = trim(substr($html, $boundary_index, $closing_end - $boundary_index));

            return [
                'end' => $closing_end,
                'valid' => $remainder === '',
            ];
        }

        return null;

    }

    private static function generic_blocked_placeholder($message){

        return '<div class="ulp-consent-placeholder" data-ulc-service="unclassified" role="group"><p>'
            . esc_html($message)
            . '</p></div>';

    }

    public static function filter_external_iframes($html){

        if(!is_string($html) || $html === ''){
            return $html;
        }

        $options = self::get_options();

        if(empty($options['consent_enabled']) || stripos($html, '<iframe') === false){
            return $html;
        }

        if(strlen($html) > self::MAX_CONTENT_SCAN_BYTES){
            $strings = self::resolve_consent_strings($options);
            $message = isset($strings['services']['unclassified'])
                ? $strings['services']['unclassified']
                : self::default_consent_strings()['services']['unclassified'];

            return self::generic_blocked_placeholder($message);
        }

        $registry = self::get_detected_service_registry();
        $registry_changed = false;
        $candidate_count = 0;
        $strings = self::resolve_consent_strings($options);
        $service_strings = isset($strings['services']) && is_array($strings['services'])
            ? $strings['services']
            : self::default_consent_strings()['services'];
        $output = '';
        $cursor = 0;
        $length = strlen($html);

        while(($iframe_start = stripos($html, '<iframe', $cursor)) !== false){
            $boundary_index = $iframe_start + 7;
            $boundary = isset($html[$boundary_index]) ? $html[$boundary_index] : '';

            if($boundary !== '' && $boundary !== '>' && $boundary !== '/' && !preg_match('/\s/', $boundary)){
                $output .= substr($html, $cursor, $boundary_index - $cursor);
                $cursor = $boundary_index;
                continue;
            }

            $output .= substr($html, $cursor, $iframe_start - $cursor);
            $opening_end = self::find_html_tag_end($html, $boundary_index);

            if($opening_end < 0){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                $cursor = $length;
                break;
            }

            $opening_tag = substr($html, $iframe_start, $opening_end - $iframe_start + 1);
            $parsed = self::parse_iframe_opening_tag($opening_tag);
            // HTML does not define iframe as a void element: browsers ignore the
            // self-closing slash and keep its body as raw text until </iframe>.
            $closing = self::find_iframe_closing_tag($html, $opening_end + 1);

            if($closing === null){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                $cursor = $length;
                break;
            }

            $iframe_end = $closing['end'];
            $original_iframe = substr($html, $iframe_start, $iframe_end - $iframe_start + 1);
            $cursor = $iframe_end + 1;

            if(!is_array($parsed) || !empty($parsed['selfClosing']) || empty($closing['valid'])){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                continue;
            }

            $attributes = $parsed['attributes'];

            if(array_key_exists('srcdoc', $attributes)){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                continue;
            }

            if(!array_key_exists('src', $attributes)){
                $output .= $original_iframe;
                continue;
            }

            if(!is_string($attributes['src']) || $attributes['src'] === ''){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                continue;
            }

            $candidate_count++;

            if($candidate_count > self::MAX_URL_CANDIDATES){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                continue;
            }

            $normalized = self::normalize_external_iframe_url($attributes['src']);

            if(!is_array($normalized)){
                $output .= self::generic_blocked_placeholder($service_strings['unclassified']);
                continue;
            }

            if(empty($normalized['external'])){
                $output .= $original_iframe;
                continue;
            }

            $detected = self::default_detected_service($normalized['domain'], 'iframe');

            if(self::merge_detected_service($registry, $detected)){
                $registry_changed = true;
            }

            $configured = isset($options['services'][$detected['id']]) && is_array($options['services'][$detected['id']])
                ? $options['services'][$detected['id']]
                : [];
            $label = isset($configured['label']) ? $configured['label'] : $detected['label'];
            $category = isset($configured['category']) ? $configured['category'] : $detected['category'];
            $raw_title = isset($attributes['title']) && is_string($attributes['title'])
                ? html_entity_decode($attributes['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : '';
            $title = self::trim_plain_text($raw_title, 160);
            $title = $title !== '' ? $title : $label;
            $message = $category === 'unclassified'
                ? $service_strings['unclassified']
                : $service_strings['blocked'];
            $placeholder = '<div class="ulp-consent-placeholder" data-ulc-service="'
                . esc_attr($detected['id'])
                . '" data-ulc-src="'
                . esc_attr($normalized['url'])
                . '" data-ulc-domain="'
                . esc_attr($normalized['domain'])
                . '" data-ulc-title="'
                . esc_attr($title)
                . '" role="group" aria-label="'
                . esc_attr($title)
                . '"><p>'
                . esc_html($message)
                . '</p>';

            if($category !== 'unclassified'){
                $placeholder .= '<button type="button" data-ulc-allow-service="'
                    . esc_attr($detected['id'])
                    . '">'
                    . esc_html($service_strings['allow'])
                    . '</button>';
            }

            $output .= $placeholder . '</div>';
        }

        if($cursor < $length){
            $output .= substr($html, $cursor);
        }

        if($registry_changed && current_user_can('manage_options')){
            self::persist_detected_service_registry($registry);
        }

        return $output;

    }

    public static function detect_admin_enqueued_scripts(){

        if(!current_user_can('manage_options')){
            return;
        }

        global $wp_scripts;

        if(!$wp_scripts instanceof WP_Scripts
            || !isset($wp_scripts->queue, $wp_scripts->registered)
            || !is_array($wp_scripts->queue)
            || !is_array($wp_scripts->registered)){
            return;
        }

        $registry = self::get_detected_service_registry();
        $changed = false;

        foreach(array_slice($wp_scripts->queue, 0, self::MAX_SCRIPT_QUEUE) as $handle){
            if(!self::script_handle_is_valid($handle) || !isset($wp_scripts->registered[$handle])){
                continue;
            }

            $dependency = $wp_scripts->registered[$handle];
            $src = is_object($dependency) && isset($dependency->src) ? $dependency->src : '';
            $domain = self::external_url_domain($src);

            if($domain === ''){
                continue;
            }

            $definition = self::default_detected_service($domain, 'script', $handle);

            if(self::merge_detected_service($registry, $definition)){
                $changed = true;
            }
        }

        if($changed){
            self::persist_detected_service_registry($registry);
        }

    }

    public static function get_options(){

        $stored = get_option(self::OPTION_NAME, []);
        $defaults = self::default_options();

        if(!is_array($stored)){
            $stored = [];
        }

        $raw_stored = $stored;

        $has_consent_page_ids = array_key_exists('consent_page_ids', $stored);
        $legacy_page_ids = [];

        if(!$has_consent_page_ids){
            foreach(['privacy_page_id', 'cookies_page_id', 'terms_page_id'] as $legacy_page_key){
                if(isset($stored[$legacy_page_key]) && is_scalar($stored[$legacy_page_key])){
                    $legacy_page_ids[] = $stored[$legacy_page_key];
                }
            }
        }

        $stored = array_intersect_key($stored, $defaults);
        $options = array_merge($defaults, $stored);

        foreach(['consent_enabled', 'terms_required', 'respect_gpc', 'show_revisit_button'] as $boolean_key){
            $options[$boolean_key] = (bool)$options[$boolean_key];
        }

        $options['terms_page_id'] = is_scalar($options['terms_page_id']) ? absint($options['terms_page_id']) : 0;
        $options['consent_page_ids'] = self::normalize_stored_legal_page_ids(
            $has_consent_page_ids ? $options['consent_page_ids'] : $legacy_page_ids
        );
        $options['consent_link_translations'] = self::normalize_stored_consent_link_translations(
            $options['consent_link_translations']
        );

        $options['banner_title'] = self::trim_plain_text($options['banner_title'], 120);
        $options['banner_message'] = self::trim_plain_text($options['banner_message'], 600, true);
        $options['banner_title'] = $options['banner_title'] !== ''
            ? $options['banner_title']
            : $defaults['banner_title'];
        $options['banner_message'] = $options['banner_message'] !== ''
            ? $options['banner_message']
            : $defaults['banner_message'];
        $options['banner_translations'] = self::normalize_stored_banner_translations(
            $options['banner_translations']
        );
        $stored_consent_strings = array_key_exists('consent_strings', $raw_stored)
            ? $raw_stored['consent_strings']
            : [];
        $options['consent_strings'] = self::normalize_stored_consent_strings(
            $stored_consent_strings,
            $defaults['consent_strings']
        );

        if(!array_key_exists('consent_strings', $raw_stored)){
            $options['consent_strings']['title'] = $options['banner_title'];
            $options['consent_strings']['message'] = $options['banner_message'];
        }

        $options['banner_title'] = $options['consent_strings']['title'];
        $options['banner_message'] = $options['consent_strings']['message'];
        $options['consent_string_translations'] = self::normalize_stored_consent_string_translations(
            $options['consent_string_translations'],
            $options['consent_strings']
        );

        $languages = self::reactwp_languages();

        if(!empty($languages)){
            $string_translations = [];
            $link_translations = [];

            foreach($languages as $language){
                $code = $language['code'];
                $stored_string_translation = isset($options['consent_string_translations'][$code])
                    && is_array($options['consent_string_translations'][$code])
                    ? $options['consent_string_translations'][$code]
                    : $options['consent_strings'];
                $legacy_translation = isset($options['banner_translations'][$code])
                    && is_array($options['banner_translations'][$code])
                    ? $options['banner_translations'][$code]
                    : [];

                if(!isset($raw_stored['consent_string_translations'][$code])
                    || !is_array($raw_stored['consent_string_translations'][$code])){
                    $legacy_title = self::trim_plain_text($legacy_translation['title'] ?? '', 120);
                    $legacy_message = self::trim_plain_text($legacy_translation['message'] ?? '', 600, true);
                    $stored_string_translation['title'] = $legacy_title !== '' ? $legacy_title : $options['banner_title'];
                    $stored_string_translation['message'] = $legacy_message !== '' ? $legacy_message : $options['banner_message'];
                }

                $string_translations[$code] = $stored_string_translation;
                $link_translations[$code] = isset($options['consent_link_translations'][$code])
                    ? $options['consent_link_translations'][$code]
                    : [
                        'consent_page_ids' => $options['consent_page_ids'],
                        'terms_page_id' => $options['terms_page_id'],
                    ];
            }

            $options['consent_string_translations'] = $string_translations;
            $options['banner_translations'] = self::project_banner_translations($string_translations);
            $options['consent_link_translations'] = $link_translations;

            $current_language = self::reactwp_language_code($languages);
            $fallback_links = $link_translations[$current_language]
                ?? $link_translations[$languages[0]['code']]
                ?? null;

            if(is_array($fallback_links)){
                $options['consent_page_ids'] = $fallback_links['consent_page_ids'];
                $options['terms_page_id'] = $fallback_links['terms_page_id'];
            }
        }

        $legacy_categories = self::legacy_consent_categories(
            $options['consent_strings'],
            $options['consent_string_translations'],
            $languages
        );
        $legacy_category_purposes = self::legacy_category_purpose_map($options['consent_categories']);
        $options['consent_categories'] = array_key_exists('consent_categories', $raw_stored)
            ? self::normalize_stored_consent_categories(
                $options['consent_categories'],
                $languages,
                $legacy_categories
            )
            : $legacy_categories;

        $policy_version = is_scalar($options['policy_version'])
            ? trim((string)$options['policy_version'])
            : '';
        $options['policy_version'] = preg_match('/\A[A-Za-z0-9._-]{1,32}\z/', $policy_version)
            ? $policy_version
            : $defaults['policy_version'];

        $duration = is_scalar($options['duration_days']) ? (int)$options['duration_days'] : 0;
        $options['duration_days'] = $duration >= 30 && $duration <= 365
            ? $duration
            : $defaults['duration_days'];

        $integration_patterns = [
            'ga4_measurement_id' => '/\AG-[A-Z0-9]{4,20}\z/',
            'gtm_container_id' => '/\AGTM-[A-Z0-9]{4,20}\z/',
            'google_ads_id' => '/\AAW-[0-9]{5,20}\z/',
            'meta_pixel_id' => '/\A[0-9]{5,32}\z/',
        ];

        foreach($integration_patterns as $integration_key => $pattern){
            $value = is_scalar($options[$integration_key])
                ? strtoupper(trim((string)$options[$integration_key]))
                : '';
            $options[$integration_key] = preg_match($pattern, $value) ? $value : '';
        }

        foreach([
            'ga4_category' => ['preferred' => 'analytics', 'legacy_purpose' => 'analytics'],
            'gtm_category' => ['preferred' => 'analytics', 'legacy_purpose' => 'analytics'],
            'google_ads_category' => ['preferred' => 'marketing', 'legacy_purpose' => 'marketing'],
            'meta_pixel_category' => ['preferred' => 'marketing', 'legacy_purpose' => 'marketing'],
        ] as $category_key => $category_defaults){
            $category_value = $options[$category_key];

            if(!array_key_exists($category_key, $raw_stored)){
                foreach($legacy_category_purposes as $legacy_category_id => $legacy_purpose){
                    if($legacy_purpose === $category_defaults['legacy_purpose']
                        && isset($options['consent_categories'][$legacy_category_id])){
                        $category_value = $legacy_category_id;
                        break;
                    }
                }
            }

            $options[$category_key] = self::integration_category_reference(
                $category_value,
                $options['consent_categories'],
                $category_defaults['preferred']
            );
        }

        $options['custom_integrations'] = self::normalize_stored_custom_integrations(
            $options['custom_integrations'],
            $options['consent_categories']
        );

        $options['services'] = self::normalize_stored_service_settings(
            $options['services'],
            self::get_detected_service_registry(),
            $options['consent_categories']
        );

        return $options;

    }

    private static function is_published_legal_page($id){

        return get_post_type($id) === self::POST_TYPE && get_post_status($id) === 'publish';

    }

    private static function parse_positive_integer_id($value){

        if(is_int($value) && $value > 0){
            return $value;
        }

        if(!is_string($value) || !preg_match('/\A[1-9][0-9]*\z/', $value)){
            return 0;
        }

        $maximum = (string)PHP_INT_MAX;

        if(strlen($value) > strlen($maximum) || (strlen($value) === strlen($maximum) && strcmp($value, $maximum) > 0)){
            return 0;
        }

        return (int)$value;

    }

    private static function normalize_stored_legal_page_ids($value){

        if(!is_array($value)){
            return [];
        }

        $normalized = [];

        foreach(array_slice($value, 0, self::MAX_CONSENT_PAGES) as $raw_id){
            $id = self::parse_positive_integer_id($raw_id);

            if($id > 0 && !in_array($id, $normalized, true) && self::is_published_legal_page($id)){
                $normalized[] = $id;
            }
        }

        return $normalized;

    }

    private static function normalize_stored_consent_link_translations($value){

        if(!is_array($value) || count($value) > self::MAX_REACTWP_LANGUAGES){
            return [];
        }

        $translations = [];

        foreach($value as $raw_code => $bundle){
            $code = self::normalize_language_code($raw_code);

            if($code === '' || $code !== $raw_code || !is_array($bundle)){
                continue;
            }

            $terms_page_id = isset($bundle['terms_page_id']) && is_scalar($bundle['terms_page_id'])
                ? absint($bundle['terms_page_id'])
                : 0;

            if($terms_page_id > 0 && !self::is_published_legal_page($terms_page_id)){
                $terms_page_id = 0;
            }

            $translations[$code] = [
                'consent_page_ids' => self::normalize_stored_legal_page_ids($bundle['consent_page_ids'] ?? []),
                'terms_page_id' => $terms_page_id,
            ];
        }

        return $translations;

    }

    public static function register_settings(){

        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default' => self::default_options(),
        ]);

    }

    private static function ajax_settings_error_items($errors){

        $items = [];

        foreach(array_slice(is_array($errors) ? $errors : [], 0, 20) as $error){
            if(!is_array($error)){
                continue;
            }

            $code = isset($error['code']) && is_scalar($error['code'])
                ? sanitize_key((string)$error['code'])
                : 'invalid_settings';
            $message = isset($error['message']) && is_scalar($error['message'])
                ? self::trim_plain_text((string)$error['message'], 500, true)
                : '';

            if($message === ''){
                continue;
            }

            $items[] = [
                'code' => $code !== '' ? $code : 'invalid_settings',
                'message' => $message,
            ];
        }

        return $items;

    }

    private static function ajax_custom_integration_ids($options, $input){

        $items = [];
        $integrations = isset($options['custom_integrations']) && is_array($options['custom_integrations'])
            ? $options['custom_integrations']
            : [];
        $submitted = is_array($input) && isset($input['custom_integrations']) && is_array($input['custom_integrations'])
            ? array_slice($input['custom_integrations'], 0, self::MAX_CUSTOM_INTEGRATIONS)
            : [];

        foreach($submitted as $index => $definition){
            if(!is_array($definition)){
                continue;
            }

            $submitted_id = isset($definition['id']) && is_scalar($definition['id'])
                ? (string)$definition['id']
                : '';
            $script_url = isset($definition['script_url'])
                ? self::normalize_custom_script_url($definition['script_url'])
                : '';
            $matched_id = '';

            if($submitted_id !== '' && isset($integrations[$submitted_id])){
                $matched_id = $submitted_id;
            }elseif($script_url !== ''){
                foreach($integrations as $id => $stored_definition){
                    if(is_array($stored_definition) && ($stored_definition['script_url'] ?? '') === $script_url){
                        $matched_id = (string)$id;
                        break;
                    }
                }
            }

            if($matched_id === ''){
                continue;
            }

            $items[] = [
                'index' => (int)$index,
                'id' => $matched_id,
            ];
        }

        return $items;

    }

    public static function ajax_save_settings(){

        if(!current_user_can('manage_options')){
            wp_send_json_error([
                'message' => __('You do not have permission to manage these settings.', 'universal-legal-pages'),
                'errors' => [],
            ], 403);
            return;
        }

        if(!check_ajax_referer(self::AJAX_SAVE_ACTION, 'ulp_save_nonce', false)){
            wp_send_json_error([
                'message' => __('Your session has expired. Reload the page and try again.', 'universal-legal-pages'),
                'errors' => [],
            ], 403);
            return;
        }

        $before_errors = get_settings_errors(self::OPTION_NAME);
        $input = array_key_exists(self::OPTION_NAME, $_POST)
            ? wp_unslash($_POST[self::OPTION_NAME])
            : null;
        update_option(self::OPTION_NAME, $input);
        $options = self::get_options();

        $all_errors = get_settings_errors(self::OPTION_NAME);
        $new_errors = array_slice(
            is_array($all_errors) ? $all_errors : [],
            is_array($before_errors) ? count($before_errors) : 0
        );
        $custom_integrations_valid = true;

        foreach($new_errors as $error){
            $error_code = is_array($error) && isset($error['code']) ? (string)$error['code'] : '';

            if(in_array($error_code, ['invalid_custom_integrations', 'forbidden_custom_integrations'], true)){
                $custom_integrations_valid = false;
                break;
            }
        }

        $payload = [
            'customIntegrations' => $custom_integrations_valid
                ? self::ajax_custom_integration_ids($options, $input)
                : [],
            'errors' => self::ajax_settings_error_items($new_errors),
        ];

        if(!empty($new_errors)){
            $payload['message'] = __('The settings were saved with validation errors. Review the messages and try again.', 'universal-legal-pages');
            wp_send_json_error($payload, 422);
            return;
        }

        $payload['message'] = __('Settings saved.', 'universal-legal-pages');
        wp_send_json_success($payload, 200);

    }

    public static function register_settings_page(){

        self::$settings_page_hook = add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            __('Consent and integrations', 'universal-legal-pages'),
            __('Consent', 'universal-legal-pages'),
            'manage_options',
            self::SETTINGS_SLUG,
            [__CLASS__, 'render_settings_page']
        );

    }

    public static function enqueue_admin_assets($hook_suffix){

        if(!is_string($hook_suffix) || $hook_suffix !== self::$settings_page_hook){
            return;
        }

        $stylesheet = __DIR__ . '/assets/css/admin-consent.css';
        $version = is_readable($stylesheet)
            ? (string)filemtime($stylesheet)
            : self::VERSION;

        wp_enqueue_style(
            self::ADMIN_STYLE_HANDLE,
            plugins_url('assets/css/admin-consent.css', __FILE__),
            [],
            $version
        );

        $script = __DIR__ . '/assets/js/admin-consent.js';
        $script_version = is_readable($script)
            ? (string)filemtime($script)
            : self::VERSION;

        wp_enqueue_script(
            self::ADMIN_SCRIPT_HANDLE,
            plugins_url('assets/js/admin-consent.js', __FILE__),
            [],
            $script_version,
            true
        );

    }

    private static function trim_plain_text($value, $maximum, $textarea = false){

        if(!is_string($value)){
            return '';
        }

        $value = $textarea
            ? sanitize_textarea_field($value)
            : sanitize_text_field($value);

        if(function_exists('mb_substr')){
            return mb_substr($value, 0, $maximum);
        }

        if(preg_match_all('/./us', $value, $characters) !== false){
            return implode('', array_slice($characters[0], 0, $maximum));
        }

        return substr($value, 0, $maximum);

    }

    private static function normalize_language_code($value){

        if(!is_scalar($value)){
            return '';
        }

        $code = strtolower(trim((string)$value));

        return strlen($code) <= 32 && preg_match('/\A[a-z][a-z0-9_-]{1,31}\z/', $code)
            ? $code
            : '';

    }

    private static function reactwp_languages(){

        if(!class_exists('ReactWP', false) || !function_exists('rwp_admin_langs')){
            return [];
        }

        $raw_languages = rwp_admin_langs();

        if(!is_array($raw_languages) || empty($raw_languages) || count($raw_languages) > self::MAX_REACTWP_LANGUAGES){
            return [];
        }

        $languages = [];
        $codes = [];

        foreach($raw_languages as $raw_language){
            if(!is_array($raw_language)){
                return [];
            }

            $code = self::normalize_language_code($raw_language['code'] ?? null);

            if($code === '' || in_array($code, $codes, true)){
                return [];
            }

            $name = self::trim_plain_text($raw_language['name'] ?? '', 80);

            $languages[] = [
                'name' => $name !== '' ? $name : strtoupper($code),
                'code' => $code,
            ];
            $codes[] = $code;
        }

        return $languages;

    }

    private static function reactwp_language_code($languages){

        if(empty($languages)){
            return '';
        }

        $allowed_codes = array_column($languages, 'code');
        $candidates = [];

        if(function_exists('pll_current_language')){
            $candidates[] = pll_current_language('slug');
        }

        if(defined('CL')){
            $candidates[] = CL;
        }

        $locale = get_locale();
        $candidates[] = $locale;

        foreach($candidates as $candidate){
            $code = self::normalize_language_code($candidate);

            if($code !== '' && in_array($code, $allowed_codes, true)){
                return $code;
            }

            $primary = preg_split('/[-_]/', $code)[0] ?? '';

            if($primary !== '' && in_array($primary, $allowed_codes, true)){
                return $primary;
            }
        }

        return $allowed_codes[0];

    }

    private static function normalize_stored_banner_translations($value){

        if(!is_array($value)){
            return [];
        }

        $translations = [];

        foreach(array_slice($value, 0, self::MAX_REACTWP_LANGUAGES, true) as $raw_code => $raw_translation){
            $code = self::normalize_language_code($raw_code);

            if($code === '' || isset($translations[$code]) || !is_array($raw_translation)){
                continue;
            }

            $translations[$code] = [
                'title' => self::trim_plain_text($raw_translation['title'] ?? '', 120),
                'message' => self::trim_plain_text($raw_translation['message'] ?? '', 600, true),
            ];
        }

        return $translations;

    }

    private static function nested_string_value($value, $path){

        foreach(explode('.', $path) as $key){
            if(!is_array($value) || !array_key_exists($key, $value)){
                return null;
            }

            $value = $value[$key];
        }

        return $value;

    }

    private static function set_nested_string_value(&$value, $path, $replacement){

        $keys = explode('.', $path);
        $cursor = &$value;

        foreach($keys as $index => $key){
            if($index === count($keys) - 1){
                $cursor[$key] = $replacement;
                break;
            }

            if(!isset($cursor[$key]) || !is_array($cursor[$key])){
                $cursor[$key] = [];
            }

            $cursor = &$cursor[$key];
        }

    }

    private static function normalize_stored_consent_strings($value, $fallback){

        $normalized = is_array($fallback) ? $fallback : self::default_consent_strings();

        if(!is_array($value)){
            return $normalized;
        }

        foreach(self::consent_string_schema() as $path => $field){
            $raw_value = self::nested_string_value($value, $path);
            $clean_value = self::trim_plain_text($raw_value, $field['maximum'], $field['textarea']);

            if($clean_value !== ''){
                self::set_nested_string_value($normalized, $path, $clean_value);
            }
        }

        return $normalized;

    }

    private static function normalize_stored_consent_string_translations($value, $fallback){

        if(!is_array($value)){
            return [];
        }

        $translations = [];

        foreach(array_slice($value, 0, self::MAX_REACTWP_LANGUAGES, true) as $raw_code => $raw_translation){
            $code = self::normalize_language_code($raw_code);

            if($code === '' || isset($translations[$code]) || !is_array($raw_translation)){
                continue;
            }

            $translations[$code] = self::normalize_stored_consent_strings($raw_translation, $fallback);
        }

        return $translations;

    }

    private static function legacy_consent_categories($strings, $translations, $languages){

        $categories = self::default_consent_categories($strings);

        foreach($categories as $id => &$definition){
            $definition['translations'] = [];

            foreach($languages as $language){
                $code = $language['code'];
                $bundle = isset($translations[$code]) && is_array($translations[$code])
                    ? $translations[$code]
                    : [];
                $copy = isset($bundle['categories'][$id]) && is_array($bundle['categories'][$id])
                    ? $bundle['categories'][$id]
                    : [];
                $label = self::trim_plain_text($copy['label'] ?? '', 80);
                $description = self::trim_plain_text($copy['description'] ?? '', 300, true);
                $definition['translations'][$code] = [
                    'label' => $label !== '' ? $label : $definition['label'],
                    'description' => $description !== '' ? $description : $definition['description'],
                ];
            }
        }
        unset($definition);

        return $categories;

    }

    private static function normalize_stored_consent_categories($value, $languages, $fallback){

        $fallback = is_array($fallback) ? $fallback : self::default_consent_categories();

        if(!is_array($value) || count($value) > self::MAX_CONSENT_CATEGORIES){
            return $fallback;
        }

        $categories = [];

        foreach($value as $id => $definition){
            if(count($categories) >= self::MAX_CONSENT_CATEGORIES
                || !self::category_id_is_valid($id)
                || isset($categories[$id])
                || !is_array($definition)){
                continue;
            }

            $fallback_definition = isset($fallback[$id]) && is_array($fallback[$id])
                ? $fallback[$id]
                : [];
            $label = self::trim_plain_text($definition['label'] ?? '', 80);
            $description = self::trim_plain_text($definition['description'] ?? '', 300, true);
            $label = $label !== '' ? $label : self::trim_plain_text($fallback_definition['label'] ?? '', 80);
            $description = $description !== ''
                ? $description
                : self::trim_plain_text($fallback_definition['description'] ?? '', 300, true);

            if($label === '' || $description === ''){
                continue;
            }

            $translations = [];
            $stored_translations = isset($definition['translations']) && is_array($definition['translations'])
                ? $definition['translations']
                : [];
            $fallback_translations = isset($fallback_definition['translations']) && is_array($fallback_definition['translations'])
                ? $fallback_definition['translations']
                : [];

            foreach($languages as $language){
                $code = $language['code'];
                $translation = isset($stored_translations[$code]) && is_array($stored_translations[$code])
                    ? $stored_translations[$code]
                    : [];
                $fallback_translation = isset($fallback_translations[$code]) && is_array($fallback_translations[$code])
                    ? $fallback_translations[$code]
                    : [];
                $translated_label = self::trim_plain_text($translation['label'] ?? '', 80);
                $translated_description = self::trim_plain_text($translation['description'] ?? '', 300, true);
                $translations[$code] = [
                    'label' => $translated_label !== ''
                        ? $translated_label
                        : (self::trim_plain_text($fallback_translation['label'] ?? '', 80) ?: $label),
                    'description' => $translated_description !== ''
                        ? $translated_description
                        : (self::trim_plain_text($fallback_translation['description'] ?? '', 300, true) ?: $description),
                ];
            }

            $categories[$id] = [
                'label' => $label,
                'description' => $description,
                'translations' => $translations,
            ];
        }

        if(!isset($categories['necessary'])){
            $necessary = isset($fallback['necessary']) && is_array($fallback['necessary'])
                ? $fallback['necessary']
                : self::default_consent_categories()['necessary'];
            $categories = ['necessary' => $necessary] + $categories;
        }elseif(array_key_first($categories) !== 'necessary'){
            $necessary = $categories['necessary'];
            unset($categories['necessary']);
            $categories = ['necessary' => $necessary] + $categories;
        }

        return $categories;

    }

    private static function legacy_category_purpose_map($value){

        $purposes = [];

        foreach(self::default_category_ids() as $id){
            $purposes[$id] = $id;
        }

        if(!is_array($value)){
            return $purposes;
        }

        foreach($value as $id => $definition){
            $purpose = is_array($definition) && isset($definition['purpose']) && is_string($definition['purpose'])
                ? $definition['purpose']
                : '';

            if(self::category_id_is_valid($id) && in_array($purpose, self::service_purposes(), true)){
                $purposes[$id] = $purpose;
            }
        }

        return $purposes;

    }

    private static function reject_consent_categories($previous){

        self::add_field_error(
            'invalid_consent_categories',
            __('Consent categories are invalid or incomplete; the complete previous category registry was preserved.', 'universal-legal-pages')
        );

        return is_array($previous) ? $previous : self::default_consent_categories();

    }

    private static function sanitize_consent_categories($value, $previous, $languages){

        if(!is_array($value)
            || empty($value)
            || count($value) > self::MAX_CONSENT_CATEGORIES
            || array_keys($value) !== range(0, count($value) - 1)){
            return self::reject_consent_categories($previous);
        }

        $categories = [];
        $expected_language_codes = array_column($languages, 'code');

        foreach($value as $index => $item){
            if(!is_array($item)){
                return self::reject_consent_categories($previous);
            }

            $keys = array_keys($item);
            sort($keys);
            $expected_keys = empty($languages)
                ? ['description', 'id', 'label']
                : ['id', 'translations'];

            if($keys !== $expected_keys
                || !is_string($item['id'])
                || !self::category_id_is_valid($item['id'])
                || isset($categories[$item['id']])
                || ($item['id'] === 'necessary' && $index !== 0)){
                return self::reject_consent_categories($previous);
            }

            $label = '';
            $description = '';
            $translations = [];

            if(empty($languages)){
                if(!self::consent_string_value_is_valid($item['label'] ?? null, 80, false)
                    || !self::consent_string_value_is_valid($item['description'] ?? null, 300, true)){
                    return self::reject_consent_categories($previous);
                }

                $label = self::trim_plain_text($item['label'], 80);
                $description = self::trim_plain_text($item['description'], 300, true);
            }else{
                if(!is_array($item['translations'])){
                    return self::reject_consent_categories($previous);
                }

                $submitted_codes = array_keys($item['translations']);
                sort($submitted_codes);
                $sorted_expected_codes = $expected_language_codes;
                sort($sorted_expected_codes);

                if($submitted_codes !== $sorted_expected_codes){
                    return self::reject_consent_categories($previous);
                }

                foreach($languages as $language){
                    $code = $language['code'];
                    $translation = $item['translations'][$code] ?? null;

                    $translation_keys = is_array($translation) ? array_keys($translation) : [];
                    sort($translation_keys);

                    if(!is_array($translation)
                        || $translation_keys !== ['description', 'label']
                        || !self::consent_string_value_is_valid($translation['label'] ?? null, 80, false)
                        || !self::consent_string_value_is_valid($translation['description'] ?? null, 300, true)){
                        return self::reject_consent_categories($previous);
                    }

                    $translations[$code] = [
                        'label' => self::trim_plain_text($translation['label'], 80),
                        'description' => self::trim_plain_text($translation['description'], 300, true),
                    ];
                }

                $fallback_code = self::reactwp_language_code($languages);
                $fallback_translation = $translations[$fallback_code]
                    ?? $translations[$languages[0]['code']]
                    ?? null;
                $label = $fallback_translation['label'];
                $description = $fallback_translation['description'];
            }

            $categories[$item['id']] = [
                'label' => $label,
                'description' => $description,
                'translations' => $translations,
            ];
        }

        if(!isset($categories['necessary'])){
            return self::reject_consent_categories($previous);
        }

        return $categories;

    }

    private static function public_consent_categories($options, $language_code = ''){

        $public = [];
        $categories = isset($options['consent_categories']) && is_array($options['consent_categories'])
            ? $options['consent_categories']
            : self::default_consent_categories();

        foreach($categories as $id => $definition){
            $copy = $language_code !== ''
                && isset($definition['translations'][$language_code])
                && is_array($definition['translations'][$language_code])
                    ? $definition['translations'][$language_code]
                    : $definition;
            $public[] = [
                'id' => $id,
                'label' => $copy['label'],
                'description' => $copy['description'],
            ];
        }

        return $public;

    }

    private static function consent_string_structure_is_exact($value, $expected){

        if(!is_array($value) || !is_array($expected)){
            return false;
        }

        $value_keys = array_keys($value);
        $expected_keys = array_keys($expected);
        sort($value_keys);
        sort($expected_keys);

        if($value_keys !== $expected_keys){
            return false;
        }

        foreach($expected as $key => $expected_value){
            if(is_array($expected_value)){
                if(!self::consent_string_structure_is_exact($value[$key], $expected_value)){
                    return false;
                }
            }elseif(!is_string($value[$key])){
                return false;
            }
        }

        return true;

    }

    private static function unicode_length($value){

        if(function_exists('mb_strlen')){
            return mb_strlen($value, 'UTF-8');
        }

        $count = preg_match_all('/./us', $value, $matches);

        return $count === false ? strlen($value) : $count;

    }

    private static function consent_string_value_is_valid($value, $maximum, $textarea){

        if(!is_string($value) || preg_match('//u', $value) !== 1){
            return false;
        }

        if(self::unicode_length($value) > $maximum || wp_strip_all_tags($value) !== $value){
            return false;
        }

        if(preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)){
            return false;
        }

        if(!$textarea && preg_match('/[\r\n]/', $value)){
            return false;
        }

        return self::trim_plain_text($value, $maximum, $textarea) !== '';

    }

    private static function sanitize_consent_strings($value, $previous, $error_code, $language_label = ''){

        $expected = self::default_consent_strings();
        $invalid_field = '';

        // Category copy has its own ordered registry. Preserve the legacy bundle so
        // older saved options and public integrations remain backward compatible.
        if(is_array($value)){
            $value['categories'] = isset($previous['categories']) && is_array($previous['categories'])
                ? $previous['categories']
                : $expected['categories'];
        }

        if(!self::consent_string_structure_is_exact($value, $expected)){
            $invalid_field = __('Text structure', 'universal-legal-pages');
        }else{
            foreach(self::consent_string_schema() as $path => $field){
                if(!self::consent_string_value_is_valid(
                    self::nested_string_value($value, $path),
                    $field['maximum'],
                    $field['textarea']
                )){
                    $invalid_field = $field['label'];
                    break;
                }
            }
        }

        if($invalid_field !== ''){
            $scope = $language_label !== ''
                ? sprintf(__(' for “%s”', 'universal-legal-pages'), $language_label)
                : '';
            self::add_field_error(
                $error_code,
                sprintf(
                    __('The “%1$s” field%2$s is invalid or incomplete; all previous text was preserved.', 'universal-legal-pages'),
                    $invalid_field,
                    $scope
                )
            );

            return is_array($previous) ? $previous : $expected;
        }

        $sanitized = $expected;

        foreach(self::consent_string_schema() as $path => $field){
            self::set_nested_string_value(
                $sanitized,
                $path,
                self::trim_plain_text(self::nested_string_value($value, $path), $field['maximum'], $field['textarea'])
            );
        }

        return $sanitized;

    }

    private static function sanitize_consent_string_translations($value, $previous, $languages){

        $expected_codes = array_column($languages, 'code');
        $submitted_codes = is_array($value) ? array_keys($value) : [];
        $sorted_expected_codes = $expected_codes;
        $sorted_submitted_codes = $submitted_codes;
        sort($sorted_expected_codes);
        sort($sorted_submitted_codes);

        if(!is_array($value) || $sorted_submitted_codes !== $sorted_expected_codes){
            self::add_field_error(
                'invalid_consent_string_translations',
                __('Multilingual copy is invalid or incomplete; all previous values were preserved.', 'universal-legal-pages')
            );

            return is_array($previous) ? $previous : [];
        }

        $translations = [];

        foreach($languages as $language){
            $code = $language['code'];
            $previous_translation = isset($previous[$code]) && is_array($previous[$code])
                ? $previous[$code]
                : self::default_consent_strings();
            $before_errors = count(get_settings_errors(self::OPTION_NAME));
            $translations[$code] = self::sanitize_consent_strings(
                $value[$code] ?? null,
                $previous_translation,
                'invalid_consent_string_translations',
                $language['name'] . ' (' . $code . ')'
            );

            if(count(get_settings_errors(self::OPTION_NAME)) > $before_errors){
                return is_array($previous) ? $previous : [];
            }
        }

        return $translations;

    }

    private static function project_banner_translations($translations){

        $projected = [];

        foreach($translations as $code => $strings){
            if(is_array($strings)){
                $projected[$code] = [
                    'title' => (string)($strings['title'] ?? ''),
                    'message' => (string)($strings['message'] ?? ''),
                ];
            }
        }

        return $projected;

    }

    private static function resolve_consent_strings($options){

        $languages = self::reactwp_languages();
        $current_language = self::reactwp_language_code($languages);

        if($current_language !== ''
            && isset($options['consent_string_translations'][$current_language])
            && is_array($options['consent_string_translations'][$current_language])){
            return $options['consent_string_translations'][$current_language];
        }

        return isset($options['consent_strings']) && is_array($options['consent_strings'])
            ? $options['consent_strings']
            : self::default_consent_strings();

    }

    private static function localized_plain_text_is_valid($value, $maximum, $textarea = false){

        return self::consent_string_value_is_valid($value, $maximum, $textarea);

    }

    private static function sanitize_banner_translations($value, $previous, $languages){

        $expected_codes = array_column($languages, 'code');
        $submitted_codes = is_array($value) ? array_keys($value) : [];
        $sorted_expected_codes = $expected_codes;
        $sorted_submitted_codes = $submitted_codes;
        sort($sorted_expected_codes);
        sort($sorted_submitted_codes);
        $invalid = !is_array($value) || $sorted_submitted_codes !== $sorted_expected_codes;
        $error_message = __('Multilingual copy is invalid or incomplete; the previous values were preserved.', 'universal-legal-pages');
        $translations = [];

        if(!$invalid){
            foreach($languages as $language){
                $code = $language['code'];
                $language_label = $language['name'] . ' (' . $code . ')';
                $translation = $value[$code] ?? null;

                if(!is_array($translation)){
                    $invalid = true;
                    $error_message = sprintf(
                        __('The multilingual text for “%s” is invalid; the previous values were preserved.', 'universal-legal-pages'),
                        $language_label
                    );
                    break;
                }

                $translation_keys = array_keys($translation);
                sort($translation_keys);

                if($translation_keys !== ['message', 'title']){
                    $invalid = true;
                    $error_message = sprintf(
                        __('The fields submitted for “%s” are invalid; the previous values were preserved.', 'universal-legal-pages'),
                        $language_label
                    );
                    break;
                }

                if(!self::localized_plain_text_is_valid($translation['title'] ?? null, 120)){
                    $invalid = true;
                    $error_message = sprintf(
                        __('The title for “%s” must contain plain text with no more than 120 characters; the previous values were preserved.', 'universal-legal-pages'),
                        $language_label
                    );
                    break;
                }

                if(!self::localized_plain_text_is_valid($translation['message'] ?? null, 600, true)){
                    $invalid = true;
                    $error_message = sprintf(
                        __('The message for “%s” must contain plain text with no more than 600 characters; the previous values were preserved.', 'universal-legal-pages'),
                        $language_label
                    );
                    break;
                }

                $translations[$code] = [
                    'title' => self::trim_plain_text($translation['title'], 120),
                    'message' => self::trim_plain_text($translation['message'], 600, true),
                ];
            }
        }

        if($invalid){
            self::add_field_error(
                'invalid_banner_translations',
                $error_message
            );

            return is_array($previous) ? $previous : [];
        }

        return $translations;

    }

    private static function has_field_error($code){

        if(!function_exists('get_settings_errors')){
            return false;
        }

        $errors = get_settings_errors(self::OPTION_NAME);

        if(!is_array($errors)){
            return false;
        }

        foreach($errors as $error){
            if(is_array($error) && isset($error['code']) && $error['code'] === $code){
                return true;
            }
        }

        return false;

    }

    private static function add_field_error($code, $message){

        add_settings_error(self::OPTION_NAME, $code, $message, 'error');

    }

    private static function sanitize_checkbox_option($input, $key, $previous, $label){

        if(!array_key_exists($key, $input)){
            return false;
        }

        if(in_array($input[$key], ['1', 1, true], true)){
            return true;
        }

        self::add_field_error(
            'invalid_' . $key,
            sprintf(__('%s contains an invalid value; the previous choice was preserved.', 'universal-legal-pages'), $label)
        );

        return (bool)$previous;

    }

    private static function sanitize_plain_option($value, $previous, $default, $maximum, $field_label, $textarea = false){

        if(!self::consent_string_value_is_valid($value, $maximum, $textarea)){
            self::add_field_error(
                'invalid_plain_text',
                sprintf(
                    __('The “%1$s” field must contain non-empty plain text with no more than %2$d characters.', 'universal-legal-pages'),
                    $field_label,
                    $maximum
                )
            );

            return $previous;
        }

        $value = $textarea
            ? sanitize_textarea_field($value)
            : sanitize_text_field($value);

        return $value !== '' ? $value : $default;

    }

    private static function sanitize_legal_page_id($value, $previous, $field_label){

        $id = null;

        if(is_int($value) && $value >= 0){
            $id = $value;
        }elseif(is_string($value) && preg_match('/\A(?:0|[1-9][0-9]*)\z/', $value)){
            $maximum = (string)PHP_INT_MAX;

            if(strlen($value) < strlen($maximum) || (strlen($value) === strlen($maximum) && strcmp($value, $maximum) <= 0)){
                $id = (int)$value;
            }
        }

        if($id === null){
            self::add_field_error(
                'invalid_legal_page_shape',
                sprintf(
                    __('Page selection “%s” contains an invalid ID; the previous value was preserved.', 'universal-legal-pages'),
                    $field_label
                )
            );

            return absint($previous);
        }

        if($id === 0){
            return 0;
        }

        if(!self::is_published_legal_page($id)){
            self::add_field_error(
                'invalid_legal_page',
                sprintf(
                    __('Page selection “%s” must be a published legal page.', 'universal-legal-pages'),
                    $field_label
                )
            );

            return absint($previous);
        }

        return $id;

    }

    private static function sanitize_consent_page_ids($value, $previous){

        $invalid = !is_array($value) || count($value) > self::MAX_CONSENT_PAGES;
        $normalized = [];

        if(!$invalid){
            foreach($value as $raw_id){
                $id = self::parse_positive_integer_id($raw_id);

                if($id === 0 || in_array($id, $normalized, true) || !self::is_published_legal_page($id)){
                    $invalid = true;
                    break;
                }

                $normalized[] = $id;
            }
        }

        if($invalid){
            self::add_field_error(
                'invalid_consent_page_ids',
                __('Displayed pages selection is invalid; the previous choice was preserved.', 'universal-legal-pages')
            );

            return is_array($previous) ? $previous : [];
        }

        return $normalized;

    }

    private static function sanitize_consent_link_translations($value, $previous, $languages){

        $expected_codes = array_column($languages, 'code');
        $submitted_codes = is_array($value) ? array_keys($value) : [];
        $sorted_expected_codes = $expected_codes;
        $sorted_submitted_codes = $submitted_codes;
        sort($sorted_expected_codes);
        sort($sorted_submitted_codes);
        $invalid = !is_array($value) || $sorted_submitted_codes !== $sorted_expected_codes;
        $translations = [];

        if(!$invalid){
            foreach($languages as $language){
                $code = $language['code'];
                $bundle = $value[$code] ?? null;
                $bundle_keys = is_array($bundle) ? array_keys($bundle) : [];
                sort($bundle_keys);

                if(
                    !is_array($bundle)
                    || !in_array($bundle_keys, [
                        ['consent_page_ids', 'terms_page_id'],
                        ['terms_page_id'],
                    ], true)
                ){
                    $invalid = true;
                    break;
                }

                $page_ids = $bundle['consent_page_ids'] ?? [];

                if(!is_array($page_ids) || count($page_ids) > self::MAX_CONSENT_PAGES){
                    $invalid = true;
                    break;
                }

                $normalized_page_ids = [];

                foreach($page_ids as $raw_id){
                    $id = self::parse_positive_integer_id($raw_id);

                    if($id === 0 || in_array($id, $normalized_page_ids, true) || !self::is_published_legal_page($id)){
                        $invalid = true;
                        break 2;
                    }

                    $normalized_page_ids[] = $id;
                }

                $raw_terms_page_id = $bundle['terms_page_id'];
                $terms_page_id = null;

                if(is_int($raw_terms_page_id) && $raw_terms_page_id >= 0){
                    $terms_page_id = $raw_terms_page_id;
                }elseif(is_string($raw_terms_page_id) && preg_match('/\A(?:0|[1-9][0-9]*)\z/', $raw_terms_page_id)){
                    $maximum = (string)PHP_INT_MAX;

                    if(strlen($raw_terms_page_id) < strlen($maximum)
                        || (strlen($raw_terms_page_id) === strlen($maximum) && strcmp($raw_terms_page_id, $maximum) <= 0)){
                        $terms_page_id = (int)$raw_terms_page_id;
                    }
                }

                if($terms_page_id === null || ($terms_page_id > 0 && !self::is_published_legal_page($terms_page_id))){
                    $invalid = true;
                    break;
                }

                $translations[$code] = [
                    'consent_page_ids' => $normalized_page_ids,
                    'terms_page_id' => $terms_page_id,
                ];
            }
        }

        if($invalid){
            self::add_field_error(
                'invalid_consent_link_translations',
                __('Multilingual consent links are invalid or incomplete; all previous values were preserved.', 'universal-legal-pages')
            );

            return is_array($previous) ? $previous : [];
        }

        return $translations;

    }

    private static function sanitize_integration_id($value, $previous, $pattern, $error_code, $field_label){

        if(!is_scalar($value)){
            self::add_field_error(
                $error_code,
                sprintf(__('The identifier for %s is invalid.', 'universal-legal-pages'), $field_label)
            );

            return (string)$previous;
        }

        $value = strtoupper(trim((string)$value));

        if($value === ''){
            return '';
        }

        if(strlen($value) > 32 || !preg_match($pattern, $value)){
            self::add_field_error(
                $error_code,
                sprintf(__('The identifier for %s is invalid.', 'universal-legal-pages'), $field_label)
            );

            return (string)$previous;
        }

        return $value;

    }

    private static function sanitize_integration_category($value, $previous, $categories, $integration_enabled, $field_label){

        $available = self::integration_categories($categories);

        if($value === ''){
            return '';
        }

        if(is_string($value) && in_array($value, $available, true)){
            return $value;
        }

        if(!$integration_enabled){
            return '';
        }

        self::add_field_error(
            'invalid_integration_category',
            sprintf(__('Select an existing consent category for %s.', 'universal-legal-pages'), $field_label)
        );

        return is_string($previous) ? $previous : '';

    }

    private static function reject_custom_integrations($previous, $code){

        self::add_field_error(
            $code,
            $code === 'forbidden_custom_integrations'
                ? __('You do not have permission to manage these settings.', 'universal-legal-pages')
                : __('Service settings are invalid; the complete previous service registry was preserved.', 'universal-legal-pages')
        );

        return $previous;

    }

    private static function sanitize_custom_integrations($input, $previous, $categories = null, $previous_categories = null){

        $previous_category_registry = is_array($previous_categories) ? $previous_categories : $categories;
        $previous = self::normalize_stored_custom_integrations(
            $previous,
            $previous_category_registry
        );

        if(!array_key_exists('custom_integrations_present', $input)){
            return $previous;
        }

        if(!current_user_can('manage_options') || !current_user_can('unfiltered_html')){
            return self::reject_custom_integrations($previous, 'forbidden_custom_integrations');
        }

        if(!in_array($input['custom_integrations_present'], ['1', 1, true], true)){
            return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
        }

        if(!array_key_exists('custom_integrations', $input)){
            return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
        }

        if($input['custom_integrations'] === ''){
            return [];
        }

        $submitted = $input['custom_integrations'];

        if(!is_array($submitted) || count($submitted) > self::MAX_CUSTOM_INTEGRATIONS){
            return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
        }

        $expected_indexes = count($submitted) > 0 ? range(0, count($submitted) - 1) : [];

        if(array_keys($submitted) !== $expected_indexes){
            return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
        }

        $detected_registry = self::get_detected_service_registry();
        $integrations = [];
        $urls = [];

        foreach($submitted as $item){
            if(!is_array($item)){
                return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
            }

            $keys = array_keys($item);
            sort($keys);

            if($keys !== ['category', 'id', 'init_code', 'label', 'script_url']
                || !is_string($item['id'])){
                return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
            }

            $definition = self::normalize_custom_integration_record([
                'label' => $item['label'],
                'category' => $item['category'],
                'script_url' => $item['script_url'],
                'init_code' => $item['init_code'],
            ], $categories);

            if($definition === null || isset($urls[$definition['script_url']])){
                return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
            }

            if($item['id'] === ''){
                $id = self::custom_integration_id($definition);

                if(isset($previous[$id])){
                    return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
                }
            }else{
                $id = $item['id'];

                if(!self::custom_integration_id_is_valid($id) || !isset($previous[$id])){
                    return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
                }
            }

            if(isset($integrations[$id]) || isset($detected_registry[$id])){
                return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
            }

            $integrations[$id] = $definition;
            $urls[$definition['script_url']] = true;
        }

        ksort($integrations, SORT_STRING);

        if(!self::custom_integrations_fit_public_budget($integrations)){
            return self::reject_custom_integrations($previous, 'invalid_custom_integrations');
        }

        return $integrations;

    }

    private static function category_references_are_valid($categories, $custom_integrations, $services, $options = []){

        $integration_category_ids = self::integration_categories($categories);
        $service_category_ids = self::service_categories($categories);

        foreach($custom_integrations as $integration){
            if(!is_array($integration)
                || (($integration['category'] ?? '') !== ''
                    && !in_array($integration['category'], $integration_category_ids, true))){
                return false;
            }
        }

        foreach($services as $settings){
            if(!is_array($settings)
                || !in_array($settings['category'] ?? '', $service_category_ids, true)){
                return false;
            }
        }

        foreach([
            'ga4_measurement_id' => 'ga4_category',
            'gtm_container_id' => 'gtm_category',
            'google_ads_id' => 'google_ads_category',
            'meta_pixel_id' => 'meta_pixel_category',
        ] as $id_key => $category_key){
            $category = $options[$category_key] ?? '';

            if(!empty($options[$id_key])
                && $category !== ''
                && !in_array($category, $integration_category_ids, true)){
                return false;
            }
        }

        return true;

    }

    public static function sanitize_options($input){

        $previous = self::get_options();
        $defaults = self::default_options();

        if(!is_array($input)){
            self::add_field_error(
                'invalid_settings_shape',
                __('Settings submitted are invalid.', 'universal-legal-pages')
            );

            return $previous;
        }

        $options = $defaults;
        $options['consent_enabled'] = self::sanitize_checkbox_option($input, 'consent_enabled', $previous['consent_enabled'], __('Banner activation', 'universal-legal-pages'));
        $options['terms_required'] = self::sanitize_checkbox_option($input, 'terms_required', $previous['terms_required'], __('Accept terms', 'universal-legal-pages'));
        $options['respect_gpc'] = self::sanitize_checkbox_option($input, 'respect_gpc', $previous['respect_gpc'], __('Global Privacy Control', 'universal-legal-pages'));
        $options['show_revisit_button'] = self::sanitize_checkbox_option($input, 'show_revisit_button', $previous['show_revisit_button'], __('Management button', 'universal-legal-pages'));

        $languages = self::reactwp_languages();

        if(!empty($languages)){
            if(array_key_exists('consent_string_translations', $input)){
                $options['consent_string_translations'] = self::sanitize_consent_string_translations(
                    $input['consent_string_translations'],
                    $previous['consent_string_translations'],
                    $languages
                );
            }else{
                $legacy_translations = self::sanitize_banner_translations(
                    isset($input['banner_translations']) ? $input['banner_translations'] : null,
                    $previous['banner_translations'],
                    $languages
                );
                $options['consent_string_translations'] = [];

                foreach($languages as $language){
                    $code = $language['code'];
                    $strings = isset($previous['consent_string_translations'][$code])
                        ? $previous['consent_string_translations'][$code]
                        : $previous['consent_strings'];
                    $strings['title'] = $legacy_translations[$code]['title'];
                    $strings['message'] = $legacy_translations[$code]['message'];
                    $options['consent_string_translations'][$code] = $strings;
                }
            }

            $fallback_code = self::reactwp_language_code($languages);
            $first_language_code = $languages[0]['code'];
            $fallback_translation = $options['consent_string_translations'][$fallback_code]
                ?? $options['consent_string_translations'][$first_language_code]
                ?? null;
            $options['consent_strings'] = is_array($fallback_translation)
                ? $fallback_translation
                : $previous['consent_strings'];
            $options['banner_title'] = $options['consent_strings']['title'];
            $options['banner_message'] = $options['consent_strings']['message'];
            $options['banner_translations'] = self::project_banner_translations($options['consent_string_translations']);
        }else{
            $options['banner_translations'] = $previous['banner_translations'];
            $options['consent_string_translations'] = $previous['consent_string_translations'];

            if(array_key_exists('consent_strings', $input)){
                $options['consent_strings'] = self::sanitize_consent_strings(
                    $input['consent_strings'],
                    $previous['consent_strings'],
                    'invalid_consent_strings'
                );
            }else{
                $options['consent_strings'] = $previous['consent_strings'];
                $options['consent_strings']['title'] = self::sanitize_plain_option(
                    isset($input['banner_title']) ? $input['banner_title'] : '',
                    $previous['banner_title'],
                    $defaults['banner_title'],
                    120,
                    __('Title', 'universal-legal-pages')
                );
                $options['consent_strings']['message'] = self::sanitize_plain_option(
                    isset($input['banner_message']) ? $input['banner_message'] : '',
                    $previous['banner_message'],
                    $defaults['banner_message'],
                    600,
                    __('Message', 'universal-legal-pages'),
                    true
                );
            }

            $options['banner_title'] = $options['consent_strings']['title'];
            $options['banner_message'] = $options['consent_strings']['message'];
        }

        if(!empty($languages)){
            if(array_key_exists('consent_link_translations', $input)){
                $options['consent_link_translations'] = self::sanitize_consent_link_translations(
                    $input['consent_link_translations'],
                    $previous['consent_link_translations'],
                    $languages
                );
            }else{
                $legacy_page_ids = self::sanitize_consent_page_ids(
                    isset($input['consent_page_ids']) ? $input['consent_page_ids'] : [],
                    $previous['consent_page_ids']
                );
                $legacy_terms_page_id = self::sanitize_legal_page_id(
                    isset($input['terms_page_id']) ? $input['terms_page_id'] : 0,
                    $previous['terms_page_id'],
                    __('Terms', 'universal-legal-pages')
                );
                $options['consent_link_translations'] = [];

                foreach($languages as $language){
                    $options['consent_link_translations'][$language['code']] = [
                        'consent_page_ids' => $legacy_page_ids,
                        'terms_page_id' => $legacy_terms_page_id,
                    ];
                }
            }

            $fallback_code = self::reactwp_language_code($languages);
            $first_language_code = $languages[0]['code'];
            $fallback_links = $options['consent_link_translations'][$fallback_code]
                ?? $options['consent_link_translations'][$first_language_code]
                ?? null;
            $options['consent_page_ids'] = is_array($fallback_links)
                ? $fallback_links['consent_page_ids']
                : $previous['consent_page_ids'];
            $options['terms_page_id'] = is_array($fallback_links)
                ? $fallback_links['terms_page_id']
                : $previous['terms_page_id'];
        }else{
            $options['consent_link_translations'] = $previous['consent_link_translations'];
            $options['consent_page_ids'] = self::sanitize_consent_page_ids(
                isset($input['consent_page_ids']) ? $input['consent_page_ids'] : [],
                $previous['consent_page_ids']
            );
            $options['terms_page_id'] = self::sanitize_legal_page_id(
                isset($input['terms_page_id']) ? $input['terms_page_id'] : 0,
                $previous['terms_page_id'],
                __('Terms', 'universal-legal-pages')
            );
        }

        if(array_key_exists('consent_categories_present', $input)){
            if(in_array($input['consent_categories_present'], ['1', 1, true], true)
                && array_key_exists('consent_categories', $input)){
                $options['consent_categories'] = self::sanitize_consent_categories(
                    $input['consent_categories'],
                    $previous['consent_categories'],
                    $languages
                );
            }else{
                $options['consent_categories'] = self::reject_consent_categories(
                    $previous['consent_categories']
                );
            }
        }else{
            $options['consent_categories'] = $previous['consent_categories'];
        }

        $policy_version = isset($input['policy_version']) && is_scalar($input['policy_version'])
            ? trim((string)$input['policy_version'])
            : '';

        if(!preg_match('/\A[A-Za-z0-9._-]{1,32}\z/', $policy_version)){
            self::add_field_error(
                'invalid_policy_version',
                __('Policy version must contain 1 to 32 letters, numbers, periods, hyphens, or underscores.', 'universal-legal-pages')
            );
            $options['policy_version'] = $previous['policy_version'];
        }else{
            $options['policy_version'] = $policy_version;
        }

        $duration = isset($input['duration_days']) && is_scalar($input['duration_days'])
            ? (string)$input['duration_days']
            : '';

        if(!preg_match('/\A\d{1,3}\z/', $duration) || (int)$duration < 30 || (int)$duration > 365){
            self::add_field_error(
                'invalid_duration_days',
                __('Consent duration must be between 30 and 365 days.', 'universal-legal-pages')
            );
            $options['duration_days'] = (int)$previous['duration_days'];
        }else{
            $options['duration_days'] = (int)$duration;
        }

        $options['ga4_measurement_id'] = self::sanitize_integration_id(
            isset($input['ga4_measurement_id']) ? $input['ga4_measurement_id'] : '',
            $previous['ga4_measurement_id'],
            '/\AG-[A-Z0-9]{4,20}\z/',
            'invalid_ga4_measurement_id',
            'Google Analytics 4'
        );
        $options['gtm_container_id'] = self::sanitize_integration_id(
            isset($input['gtm_container_id']) ? $input['gtm_container_id'] : '',
            $previous['gtm_container_id'],
            '/\AGTM-[A-Z0-9]{4,20}\z/',
            'invalid_gtm_container_id',
            'Google Tag Manager'
        );
        $options['google_ads_id'] = self::sanitize_integration_id(
            isset($input['google_ads_id']) ? $input['google_ads_id'] : '',
            $previous['google_ads_id'],
            '/\AAW-[0-9]{5,20}\z/',
            'invalid_google_ads_id',
            'Google Ads'
        );
        $options['meta_pixel_id'] = self::sanitize_integration_id(
            isset($input['meta_pixel_id']) ? $input['meta_pixel_id'] : '',
            $previous['meta_pixel_id'],
            '/\A[0-9]{5,32}\z/',
            'invalid_meta_pixel_id',
            'Meta Pixel'
        );
        foreach([
            'ga4_category' => ['id' => 'ga4_measurement_id', 'label' => 'Google Analytics 4'],
            'gtm_category' => ['id' => 'gtm_container_id', 'label' => 'Google Tag Manager'],
            'google_ads_category' => ['id' => 'google_ads_id', 'label' => 'Google Ads'],
            'meta_pixel_category' => ['id' => 'meta_pixel_id', 'label' => 'Meta Pixel'],
        ] as $category_key => $integration){
            $options[$category_key] = self::sanitize_integration_category(
                isset($input[$category_key]) ? $input[$category_key] : '',
                $previous[$category_key],
                $options['consent_categories'],
                $options[$integration['id']] !== '',
                $integration['label']
            );
        }
        $options['custom_integrations'] = self::sanitize_custom_integrations(
            $input,
            $previous['custom_integrations'],
            $options['consent_categories'],
            $previous['consent_categories']
        );
        $options['services'] = array_key_exists('services', $input)
            ? self::sanitize_service_settings(
                $input['services'],
                $previous['services'],
                self::get_detected_service_registry(),
                $options['consent_categories']
            )
            : $previous['services'];

        if(!self::category_references_are_valid(
            $options['consent_categories'],
            $options['custom_integrations'],
            $options['services'],
            $options
        )){
            self::add_field_error(
                'category_in_use',
                __('Reassign or remove every service and custom integration before deleting its consent category. The previous categories were preserved.', 'universal-legal-pages')
            );
            $options['consent_categories'] = $previous['consent_categories'];
            $options['custom_integrations'] = self::sanitize_custom_integrations(
                $input,
                $previous['custom_integrations'],
                $options['consent_categories'],
                $previous['consent_categories']
            );
            $options['services'] = array_key_exists('services', $input)
                ? self::sanitize_service_settings(
                    $input['services'],
                    $previous['services'],
                    self::get_detected_service_registry(),
                    $options['consent_categories']
                )
                : $previous['services'];
        }

        $missing_terms_page = $options['terms_page_id'] === 0;

        if(!empty($languages)){
            $missing_terms_page = false;

            foreach($languages as $language){
                $code = $language['code'];

                if(empty($options['consent_link_translations'][$code]['terms_page_id'])){
                    $missing_terms_page = true;
                    break;
                }
            }
        }

        if($options['terms_required'] && $missing_terms_page){
            self::add_field_error(
                'missing_terms_page',
                !empty($languages)
                    ? __('Select a published terms page for every configured language before requiring acceptance.', 'universal-legal-pages')
                    : __('Select a published terms page before requiring acceptance.', 'universal-legal-pages')
            );
            $options['terms_required'] = false;
        }

        return $options;

    }

    private static function render_checkbox($name, $checked, $label, $description = ''){

        $id = 'ulp-' . str_replace('_', '-', $name);

        ?>
        <label for="<?php echo esc_attr($id); ?>">
            <input
                id="<?php echo esc_attr($id); ?>"
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $name . ']'); ?>"
                value="1"
                <?php checked($checked); ?>
            >
            <?php echo esc_html($label); ?>
        </label>
        <?php if($description !== '') : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;

    }

    private static function render_legal_page_select($name, $selected, $pages, $describedby = '', $id = ''){

        $id = $id !== '' ? $id : 'ulp-' . str_replace('_', '-', $name);

        ?>
        <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $name . ']'); ?>"<?php echo $describedby !== '' ? ' aria-describedby="' . esc_attr($describedby) . '"' : ''; ?>>
            <option value="0"><?php esc_html_e('— None —', 'universal-legal-pages'); ?></option>
            <?php foreach($pages as $page) : ?>
                <option value="<?php echo esc_attr((string)$page->ID); ?>" <?php selected($selected, $page->ID); ?>>
                    <?php echo esc_html(get_the_title($page)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php

    }

    private static function render_consent_page_choice_item($page, $selected, $input_name, $id_prefix){

        $page_id = absint($page->ID);
        $page_title = trim(wp_strip_all_tags(get_the_title($page)));
        $page_title = $page_title !== '' ? $page_title : __('Legal document', 'universal-legal-pages');

        ?>
        <li
            class="ulp-admin__page-item"
            data-ulp-page-item
            data-page-id="<?php echo esc_attr((string)$page_id); ?>"
            data-page-label="<?php echo esc_attr($page_title); ?>"
            <?php if($selected) : ?>draggable="true"<?php endif; ?>
        >
            <span class="ulp-admin__drag-handle" aria-hidden="true">⋮⋮</span>
            <span class="ulp-admin__page-position" data-ulp-page-position aria-hidden="true"></span>
            <label for="<?php echo esc_attr($id_prefix . '-' . $page_id); ?>">
                <input
                    id="<?php echo esc_attr($id_prefix . '-' . $page_id); ?>"
                    type="checkbox"
                    name="<?php echo esc_attr($input_name); ?>"
                    value="<?php echo esc_attr((string)$page_id); ?>"
                    data-ulp-page-toggle
                    <?php checked($selected); ?>
                >
                <span><?php echo esc_html($page_title); ?></span>
            </label>
            <span class="ulp-admin__page-order-actions">
                <button
                    class="button ulp-admin__page-order-button"
                    type="button"
                    data-ulp-page-up
                    aria-label="<?php echo esc_attr(sprintf(__('Move “%s” up', 'universal-legal-pages'), $page_title)); ?>"
                    <?php if(!$selected) : ?>disabled<?php endif; ?>
                >↑</button>
                <button
                    class="button ulp-admin__page-order-button"
                    type="button"
                    data-ulp-page-down
                    aria-label="<?php echo esc_attr(sprintf(__('Move “%s” down', 'universal-legal-pages'), $page_title)); ?>"
                    <?php if(!$selected) : ?>disabled<?php endif; ?>
                >↓</button>
            </span>
        </li>
        <?php

    }

    private static function render_consent_page_choices($selected, $pages, $field_name = 'consent_page_ids', $id_prefix = 'ulp-consent'){

        $selected = is_array($selected) ? $selected : [];
        $input_name = self::OPTION_NAME . '[' . $field_name . '][]';
        $pages_by_id = [];
        $ordered_pages = [];
        $available_pages = [];

        foreach($pages as $page){
            $pages_by_id[absint($page->ID)] = $page;
        }

        foreach($selected as $page_id){
            if(isset($pages_by_id[$page_id])){
                $ordered_pages[] = $pages_by_id[$page_id];
                unset($pages_by_id[$page_id]);
            }
        }

        foreach($pages as $page){
            $page_id = absint($page->ID);

            if(isset($pages_by_id[$page_id])){
                $available_pages[] = $page;
            }
        }

        ?>
        <fieldset class="ulp-admin__page-fieldset" aria-describedby="<?php echo esc_attr($id_prefix . '-description'); ?>">
            <legend class="screen-reader-text"><?php esc_html_e('Documents displayed in the consent module', 'universal-legal-pages'); ?></legend>
            <p id="<?php echo esc_attr($id_prefix . '-description'); ?>" class="description"><?php esc_html_e('Select the pages to display, then arrange them in the desired order by dragging and dropping or with the move up and move down buttons. This selection does not modify any theme menu.', 'universal-legal-pages'); ?></p>
            <?php if(empty($pages)) : ?>
                <p class="ulp-admin__empty-state"><?php esc_html_e('Publish a legal page before adding it.', 'universal-legal-pages'); ?></p>
            <?php else : ?>
                <div
                    class="ulp-admin__page-order"
                    data-ulp-page-order
                    data-added-message="<?php echo esc_attr(__('added to displayed pages', 'universal-legal-pages')); ?>"
                    data-removed-message="<?php echo esc_attr(__('removed from displayed pages', 'universal-legal-pages')); ?>"
                    data-moved-message="<?php echo esc_attr(__('moved in the display order', 'universal-legal-pages')); ?>"
                >
                    <section class="ulp-admin__page-bucket" aria-labelledby="<?php echo esc_attr($id_prefix . '-selected-title'); ?>">
                        <h3 id="<?php echo esc_attr($id_prefix . '-selected-title'); ?>"><?php esc_html_e('Displayed pages', 'universal-legal-pages'); ?></h3>
                        <ol class="ulp-admin__page-list" data-ulp-selected-pages>
                            <?php foreach($ordered_pages as $page) : ?>
                                <?php self::render_consent_page_choice_item($page, true, $input_name, $id_prefix . '-page'); ?>
                            <?php endforeach; ?>
                        </ol>
                        <p class="ulp-admin__page-empty" data-ulp-selected-empty <?php if(!empty($ordered_pages)) : ?>hidden<?php endif; ?>><?php esc_html_e('No page will be displayed in the module.', 'universal-legal-pages'); ?></p>
                    </section>
                    <section class="ulp-admin__page-bucket" aria-labelledby="<?php echo esc_attr($id_prefix . '-available-title'); ?>">
                        <h3 id="<?php echo esc_attr($id_prefix . '-available-title'); ?>"><?php esc_html_e('Published pages', 'universal-legal-pages'); ?></h3>
                        <ul class="ulp-admin__page-list" data-ulp-available-pages>
                            <?php foreach($available_pages as $page) : ?>
                                <?php self::render_consent_page_choice_item($page, false, $input_name, $id_prefix . '-page'); ?>
                            <?php endforeach; ?>
                        </ul>
                        <p class="ulp-admin__page-empty" data-ulp-available-empty <?php if(!empty($available_pages)) : ?>hidden<?php endif; ?>><?php esc_html_e('All published pages are already displayed.', 'universal-legal-pages'); ?></p>
                    </section>
                    <p class="screen-reader-text" data-ulp-page-status aria-live="polite"></p>
                </div>
            <?php endif; ?>
        </fieldset>
        <?php

    }

    private static function service_category_labels($options = null){

        $options = is_array($options) ? $options : self::get_options();
        $language_code = self::reactwp_language_code(self::reactwp_languages());
        $labels = [];

        foreach($options['consent_categories'] as $id => $definition){
            $copy = $language_code !== ''
                && isset($definition['translations'][$language_code])
                && is_array($definition['translations'][$language_code])
                    ? $definition['translations'][$language_code]
                    : $definition;
            $labels[$id] = $copy['label'];
        }

        $labels['unclassified'] = __('Unclassified', 'universal-legal-pages');

        return $labels;

    }

    private static function render_consent_category_card($definition, $index, $languages){

        $definition = is_array($definition) ? $definition : [];
        $id = isset($definition['id']) ? (string)$definition['id'] : '__CATEGORY_ID__';
        $is_necessary = $id === 'necessary';
        $label = isset($definition['label']) ? (string)$definition['label'] : '';
        $description = isset($definition['description']) ? (string)$definition['description'] : '';
        $translations = isset($definition['translations']) && is_array($definition['translations'])
            ? $definition['translations']
            : [];
        $field_name = self::OPTION_NAME . '[consent_categories][' . $index . ']';
        $field_prefix = 'ulp-consent-category-' . $index;
        $active_code = self::reactwp_language_code($languages);

        if($active_code === '' && !empty($languages)){
            $active_code = $languages[0]['code'];
        }

        ?>
        <article
            class="ulp-admin__category-card"
            data-ulc-category-card
            data-category-id="<?php echo esc_attr($id); ?>"
            data-category-protected="<?php echo $is_necessary ? 'true' : 'false'; ?>"
        >
            <header class="ulp-admin__category-card-header">
                <div>
                    <h3 data-ulc-category-title><?php echo $label !== '' ? esc_html($label) : esc_html__('New consent category', 'universal-legal-pages'); ?></h3>
                    <?php if($is_necessary) : ?>
                        <span class="ulp-admin__category-required"><?php esc_html_e('Always required', 'universal-legal-pages'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if(!$is_necessary) : ?>
                    <button type="button" class="button-link-delete" data-ulc-remove-category><?php esc_html_e('Remove', 'universal-legal-pages'); ?></button>
                <?php endif; ?>
            </header>
            <input type="hidden" name="<?php echo esc_attr($field_name . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" data-ulc-category-id>
            <?php if(empty($languages)) : ?>
                <div class="ulp-admin__category-copy">
                    <p>
                        <label for="<?php echo esc_attr($field_prefix . '-label'); ?>"><?php esc_html_e('Public name', 'universal-legal-pages'); ?></label>
                        <input id="<?php echo esc_attr($field_prefix . '-label'); ?>" class="regular-text" maxlength="80" type="text" dir="auto" name="<?php echo esc_attr($field_name . '[label]'); ?>" value="<?php echo esc_attr($label); ?>" data-ulc-category-label required>
                    </p>
                    <p>
                        <label for="<?php echo esc_attr($field_prefix . '-description'); ?>"><?php esc_html_e('Public description', 'universal-legal-pages'); ?></label>
                        <textarea id="<?php echo esc_attr($field_prefix . '-description'); ?>" class="large-text" rows="3" maxlength="300" dir="auto" name="<?php echo esc_attr($field_name . '[description]'); ?>" required><?php echo esc_textarea($description); ?></textarea>
                    </p>
                </div>
            <?php else : ?>
                <div class="ulp-admin__language-editor ulp-admin__category-language-editor" data-ulp-language-editor>
                    <?php if(count($languages) > 1) : ?>
                        <div class="ulp-admin__language-tabs" role="tablist" aria-label="<?php echo esc_attr(__('Category language', 'universal-legal-pages')); ?>">
                            <?php foreach($languages as $language) :
                                $code = $language['code'];
                                $is_active = $code === $active_code;
                                ?>
                                <button
                                    id="<?php echo esc_attr($field_prefix . '-language-tab-' . $code); ?>"
                                    class="ulp-admin__language-tab"
                                    type="button"
                                    role="tab"
                                    data-ulp-language-tab
                                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                    aria-controls="<?php echo esc_attr($field_prefix . '-language-panel-' . $code); ?>"
                                    tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                ><?php echo esc_html($language['name']); ?></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach($languages as $language) :
                        $code = $language['code'];
                        $is_active = $code === $active_code;
                        $copy = isset($translations[$code]) && is_array($translations[$code])
                            ? $translations[$code]
                            : ['label' => $label, 'description' => $description];
                        ?>
                        <section
                            id="<?php echo esc_attr($field_prefix . '-language-panel-' . $code); ?>"
                            class="ulp-admin__language-panel ulp-admin__category-copy"
                            role="tabpanel"
                            data-ulp-language-panel
                            <?php if(count($languages) > 1) : ?>aria-labelledby="<?php echo esc_attr($field_prefix . '-language-tab-' . $code); ?>"<?php endif; ?>
                            <?php if(!$is_active) : ?>hidden<?php endif; ?>
                        >
                            <p>
                                <label for="<?php echo esc_attr($field_prefix . '-' . $code . '-label'); ?>"><?php esc_html_e('Public name', 'universal-legal-pages'); ?></label>
                                <input id="<?php echo esc_attr($field_prefix . '-' . $code . '-label'); ?>" class="regular-text" maxlength="80" type="text" dir="auto" name="<?php echo esc_attr($field_name . '[translations][' . $code . '][label]'); ?>" value="<?php echo esc_attr($copy['label']); ?>" data-ulc-category-label required>
                            </p>
                            <p>
                                <label for="<?php echo esc_attr($field_prefix . '-' . $code . '-description'); ?>"><?php esc_html_e('Public description', 'universal-legal-pages'); ?></label>
                                <textarea id="<?php echo esc_attr($field_prefix . '-' . $code . '-description'); ?>" class="large-text" rows="3" maxlength="300" dir="auto" name="<?php echo esc_attr($field_name . '[translations][' . $code . '][description]'); ?>" required><?php echo esc_textarea($copy['description']); ?></textarea>
                            </p>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
        <?php

    }

    private static function render_consent_categories($options, $languages){

        $categories = isset($options['consent_categories']) && is_array($options['consent_categories'])
            ? $options['consent_categories']
            : self::default_consent_categories();

        ?>
        <fieldset
            class="ulp-admin__category-editor"
            data-ulc-categories
            data-max="<?php echo esc_attr((string)self::MAX_CONSENT_CATEGORIES); ?>"
            data-unclassified-label="<?php echo esc_attr(__('Unclassified', 'universal-legal-pages')); ?>"
            data-not-selected-label="<?php echo esc_attr(__('Not selected', 'universal-legal-pages')); ?>"
            data-limit-message="<?php echo esc_attr(sprintf(__('You can add up to %d consent categories.', 'universal-legal-pages'), self::MAX_CONSENT_CATEGORIES)); ?>"
            data-added-message="<?php echo esc_attr(__('Consent category added.', 'universal-legal-pages')); ?>"
            data-removed-message="<?php echo esc_attr(__('Consent category removed. Reassign any service that used it before saving.', 'universal-legal-pages')); ?>"
        >
            <legend class="screen-reader-text"><?php esc_html_e('Consent categories', 'universal-legal-pages'); ?></legend>
            <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME . '[consent_categories_present]'); ?>" value="1">
            <div data-ulc-category-list>
                <?php $category_index = 0; ?>
                <?php foreach($categories as $id => $definition) :
                    self::render_consent_category_card(array_merge(['id' => $id], $definition), $category_index, $languages);
                    $category_index++;
                endforeach; ?>
            </div>
            <template data-ulc-category-template>
                <?php self::render_consent_category_card([], '__INDEX__', $languages); ?>
            </template>
            <p><button type="button" class="button" data-ulc-add-category><?php esc_html_e('Add a consent category', 'universal-legal-pages'); ?></button></p>
            <p class="screen-reader-text" data-ulc-category-status aria-live="polite"></p>
        </fieldset>
        <?php

    }

    private static function render_detected_services($options){

        $registry = self::get_detected_service_registry();
        $configured = isset($options['services']) && is_array($options['services'])
            ? $options['services']
            : [];
        $categories = self::service_category_labels($options);

        if(empty($registry)) : ?>
            <p class="ulp-admin__empty-state"><?php esc_html_e('No external service has been detected yet. Visit the public site while signed in as an administrator to inventory queued external scripts.', 'universal-legal-pages'); ?></p>
        <?php else : ?>
            <table class="form-table ulp-admin__services" role="presentation">
                <tbody>
                    <?php foreach($registry as $id => $relation) :
                        $default = self::default_detected_service($relation['domains'][0], $relation['kind']);
                        $settings = isset($configured[$id]) ? $configured[$id] : [];
                        $label = isset($settings['label']) ? $settings['label'] : $default['label'];
                        $category = isset($settings['category']) ? $settings['category'] : $default['category'];
                        $field_id = 'ulp-service-' . $id;
                        ?>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($field_id . '-label'); ?>"><?php echo esc_html($default['label']); ?></label></th>
                            <td>
                                <p><strong><?php echo esc_html(implode(', ', $relation['domains'])); ?></strong></p>
                                <p class="description">
                                    <?php if($relation['kind'] === 'script') : ?>
                                        <?php esc_html_e('Detected script, reporting only. It remains unmanaged until trusted project code supplies an adapter.', 'universal-legal-pages'); ?>
                                    <?php else : ?>
                                        <?php esc_html_e('Detected embedded content. Its original address is withheld until this individual service is allowed.', 'universal-legal-pages'); ?>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <label for="<?php echo esc_attr($field_id . '-label'); ?>"><?php esc_html_e('Public service name', 'universal-legal-pages'); ?></label><br>
                                    <input
                                        id="<?php echo esc_attr($field_id . '-label'); ?>"
                                        class="regular-text"
                                        maxlength="80"
                                        type="text"
                                        name="<?php echo esc_attr(self::OPTION_NAME . '[services][' . $id . '][label]'); ?>"
                                        value="<?php echo esc_attr($label); ?>"
                                        required
                                    >
                                </p>
                                <p>
                                    <label for="<?php echo esc_attr($field_id . '-category'); ?>"><?php esc_html_e('Consent category', 'universal-legal-pages'); ?></label><br>
                                    <select
                                        id="<?php echo esc_attr($field_id . '-category'); ?>"
                                        name="<?php echo esc_attr(self::OPTION_NAME . '[services][' . $id . '][category]'); ?>"
                                        data-ulc-category-select="service"
                                    >
                                        <?php foreach($categories as $category_value => $category_label) : ?>
                                            <option value="<?php echo esc_attr($category_value); ?>" <?php selected($category, $category_value); ?>><?php echo esc_html($category_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;

    }

    private static function render_custom_integration_card($definition, $index, $options){

        $definition = is_array($definition) ? $definition : [];
        $id = isset($definition['id']) ? (string)$definition['id'] : '';
        $label = isset($definition['label']) ? (string)$definition['label'] : '';
        $available_category_ids = self::integration_categories($options['consent_categories']);
        $category = isset($definition['category']) ? (string)$definition['category'] : '';
        $script_url = isset($definition['script_url']) ? (string)$definition['script_url'] : '';
        $init_code = isset($definition['init_code']) ? (string)$definition['init_code'] : '';
        $field_prefix = 'ulp-custom-integration-' . $index;
        $field_name = self::OPTION_NAME . '[custom_integrations][' . $index . ']';
        $categories = self::service_category_labels($options);

        ?>
        <article class="ulp-admin__custom-integration" data-ulc-custom-card>
            <header>
                <h3 data-ulc-custom-title><?php echo $label !== '' ? esc_html($label) : esc_html__('New custom integration', 'universal-legal-pages'); ?></h3>
                <button type="button" class="button-link-delete" data-ulc-remove-custom><?php esc_html_e('Remove', 'universal-legal-pages'); ?></button>
            </header>
            <input type="hidden" name="<?php echo esc_attr($field_name . '[id]'); ?>" value="<?php echo esc_attr($id); ?>" data-ulc-custom-id>
            <p>
                <label for="<?php echo esc_attr($field_prefix . '-label'); ?>"><?php esc_html_e('Public service name', 'universal-legal-pages'); ?></label><br>
                <input
                    id="<?php echo esc_attr($field_prefix . '-label'); ?>"
                    class="regular-text"
                    maxlength="80"
                    type="text"
                    name="<?php echo esc_attr($field_name . '[label]'); ?>"
                    value="<?php echo esc_attr($label); ?>"
                    data-ulc-custom-label
                    required
                >
            </p>
            <p>
                <label for="<?php echo esc_attr($field_prefix . '-category'); ?>"><?php esc_html_e('Consent category', 'universal-legal-pages'); ?></label><br>
                <select id="<?php echo esc_attr($field_prefix . '-category'); ?>" name="<?php echo esc_attr($field_name . '[category]'); ?>" data-ulc-category-select="integration">
                    <option value="" <?php selected($category, ''); ?>><?php esc_html_e('Not selected', 'universal-legal-pages'); ?></option>
                    <?php foreach($available_category_ids as $category_value) : ?>
                        <option value="<?php echo esc_attr($category_value); ?>" <?php selected($category, $category_value); ?>><?php echo esc_html($categories[$category_value]); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="<?php echo esc_attr($field_prefix . '-script-url'); ?>"><?php esc_html_e('HTTPS script URL', 'universal-legal-pages'); ?></label><br>
                <input
                    id="<?php echo esc_attr($field_prefix . '-script-url'); ?>"
                    class="large-text code"
                    maxlength="2048"
                    type="url"
                    inputmode="url"
                    spellcheck="false"
                    name="<?php echo esc_attr($field_name . '[script_url]'); ?>"
                    value="<?php echo esc_attr($script_url); ?>"
                    placeholder="https://cdn.example.com/service.js"
                    required
                >
            </p>
            <p>
                <label for="<?php echo esc_attr($field_prefix . '-init-code'); ?>"><?php esc_html_e('Initialization code (optional)', 'universal-legal-pages'); ?></label><br>
                <textarea
                    id="<?php echo esc_attr($field_prefix . '-init-code'); ?>"
                    class="large-text code"
                    rows="6"
                    maxlength="8192"
                    spellcheck="false"
                    name="<?php echo esc_attr($field_name . '[init_code]'); ?>"
                ><?php echo esc_textarea($init_code); ?></textarea>
            </p>
        </article>
        <?php

    }

    private static function render_integration_category_field($options, $option_key, $field_id){

        $available = self::integration_categories($options['consent_categories']);
        $labels = self::service_category_labels($options);
        $selected = isset($options[$option_key]) ? (string)$options[$option_key] : '';

        ?>
        <p class="ulp-admin__integration-category">
            <label for="<?php echo esc_attr($field_id); ?>"><?php esc_html_e('Consent category', 'universal-legal-pages'); ?></label><br>
            <select
                id="<?php echo esc_attr($field_id); ?>"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $option_key . ']'); ?>"
                data-ulc-category-select="integration"
            >
                <option value="" <?php selected($selected, ''); ?>><?php esc_html_e('Not selected', 'universal-legal-pages'); ?></option>
                <?php foreach($available as $category_id) : ?>
                    <option value="<?php echo esc_attr($category_id); ?>" <?php selected($selected, $category_id); ?>><?php echo esc_html($labels[$category_id]); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php

    }

    private static function render_custom_integrations($options){

        ?>
        <fieldset
            class="ulp-admin__custom-integrations"
            data-ulc-custom-integrations
            data-max="<?php echo esc_attr((string)self::MAX_CUSTOM_INTEGRATIONS); ?>"
            data-limit-message="<?php echo esc_attr(sprintf(__('You can add up to %d custom integrations.', 'universal-legal-pages'), self::MAX_CUSTOM_INTEGRATIONS)); ?>"
            data-added-message="<?php echo esc_attr(__('Custom integration added.', 'universal-legal-pages')); ?>"
            data-removed-message="<?php echo esc_attr(__('Custom integration removed.', 'universal-legal-pages')); ?>"
        >
            <legend><?php esc_html_e('Custom integrations', 'universal-legal-pages'); ?></legend>
            <?php if(!current_user_can('unfiltered_html')) : ?>
                <p class="notice notice-warning inline"><?php esc_html_e('You need permission to publish unfiltered code before you can manage custom integrations. Existing integrations are preserved.', 'universal-legal-pages'); ?></p>
            <?php else :
                $integrations = isset($options['custom_integrations'])
                    ? self::normalize_stored_custom_integrations($options['custom_integrations'], $options['consent_categories'])
                    : [];
                ?>
                <p class="description"><?php esc_html_e('Custom code is public and must never contain passwords, tokens, or other secrets. Necessary runs without asking; every other category waits until this individual service is accepted.', 'universal-legal-pages'); ?></p>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME . '[custom_integrations_present]'); ?>" value="1">
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME . '[custom_integrations]'); ?>" value="">
                <div data-ulc-custom-list>
                    <?php $custom_index = 0; ?>
                    <?php foreach($integrations as $id => $definition) :
                        self::render_custom_integration_card(array_merge(['id' => $id], $definition), $custom_index, $options);
                        $custom_index++;
                    endforeach; ?>
                </div>
                <template data-ulc-custom-template>
                    <?php self::render_custom_integration_card([], '__INDEX__', $options); ?>
                </template>
                <p><button type="button" class="button" data-ulc-add-custom><?php esc_html_e('Add a custom integration', 'universal-legal-pages'); ?></button></p>
                <p class="screen-reader-text" data-ulc-custom-status aria-live="polite"></p>
            <?php endif; ?>
        </fieldset>
        <?php

    }

    private static function consent_string_group_labels(){

        return [
            'banner' => __('Banner', 'universal-legal-pages'),
            'actions' => __('Buttons and actions', 'universal-legal-pages'),
            'dialog' => __('Preferences dialog', 'universal-legal-pages'),
            'services' => __('Services and blocked content', 'universal-legal-pages'),
            'status' => __('Terms and status messages', 'universal-legal-pages'),
        ];

    }

    private static function consent_string_group_descriptions(){

        return [
            'banner' => __('The title and message first shown to visitors.', 'universal-legal-pages'),
            'actions' => __('Labels used to accept, reject, customize, save, close, or reopen.', 'universal-legal-pages'),
            'dialog' => __('The heading, explanation, and legal navigation in the preferences window.', 'universal-legal-pages'),
            'services' => __('Text shown around individual services and blocked content.', 'universal-legal-pages'),
            'status' => __('Terms confirmation, privacy signals, and error messages.', 'universal-legal-pages'),
        ];

    }

    private static function consent_string_editor_sections($group){

        $sections = [
            'banner' => [
                ['label' => __('Banner content', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'title' => __('Title', 'universal-legal-pages'),
                    'message' => __('Message', 'universal-legal-pages'),
                ]],
            ],
            'actions' => [
                ['label' => __('Primary choices', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'actions.acceptAll' => null,
                    'actions.rejectAll' => null,
                    'actions.customize' => null,
                    'actions.save' => null,
                ]],
                ['label' => __('Other controls', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'actions.close' => null,
                    'actions.revisit' => null,
                ]],
            ],
            'dialog' => [
                ['label' => __('Dialog content', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'dialog.title' => __('Title', 'universal-legal-pages'),
                    'dialog.description' => __('Description', 'universal-legal-pages'),
                ]],
                ['label' => __('Legal navigation', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'legalLinksLabel' => __('Accessible name', 'universal-legal-pages'),
                ]],
            ],
            'services' => [
                ['label' => __('Service list', 'universal-legal-pages'), 'wide' => false, 'fields' => [
                    'services.title' => __('Title', 'universal-legal-pages'),
                    'services.description' => __('Description', 'universal-legal-pages'),
                ]],
                ['label' => __('Blocked content', 'universal-legal-pages'), 'wide' => false, 'fields' => [
                    'services.blocked' => __('Message', 'universal-legal-pages'),
                    'services.allow' => __('Button label', 'universal-legal-pages'),
                ]],
                ['label' => __('Unclassified service', 'universal-legal-pages'), 'wide' => false, 'fields' => [
                    'services.unclassified' => __('Message', 'universal-legal-pages'),
                ]],
            ],
            'status' => [
                ['label' => __('Terms', 'universal-legal-pages'), 'wide' => true, 'fields' => [
                    'terms.label' => __('Checkbox label', 'universal-legal-pages'),
                    'terms.description' => __('Description', 'universal-legal-pages'),
                    'terms.requiredError' => __('Validation error', 'universal-legal-pages'),
                ]],
                ['label' => __('Privacy signal', 'universal-legal-pages'), 'wide' => false, 'fields' => [
                    'gpc.notice' => __('Notice', 'universal-legal-pages'),
                ]],
                ['label' => __('Error messages', 'universal-legal-pages'), 'wide' => false, 'fields' => [
                    'error.generic' => __('Message', 'universal-legal-pages'),
                ]],
            ],
        ];

        return isset($sections[$group]) ? $sections[$group] : [];

    }

    private static function consent_string_input_name($root, $path, $language_code = ''){

        $name = self::OPTION_NAME . '[' . $root . ']';

        if($language_code !== ''){
            $name .= '[' . $language_code . ']';
        }

        foreach(explode('.', $path) as $key){
            $name .= '[' . $key . ']';
        }

        return $name;

    }

    private static function render_consent_string_fields($strings, $root, $id_prefix, $language_code = '', $has_error = false){

        $schema = self::consent_string_schema();
        $descriptions = self::consent_string_group_descriptions();
        $accordion_name = $id_prefix . '-groups';

        foreach(self::consent_string_group_labels() as $group => $group_label) :
            ?>
            <details class="ulp-admin__copy-group" name="<?php echo esc_attr($accordion_name); ?>">
                <summary>
                    <span class="ulp-admin__copy-summary">
                        <span class="ulp-admin__copy-summary-title"><?php echo esc_html($group_label); ?></span>
                        <span class="ulp-admin__copy-summary-description"><?php echo esc_html($descriptions[$group]); ?></span>
                    </span>
                    <span class="ulp-admin__copy-summary-icon" aria-hidden="true"></span>
                </summary>
                <div class="ulp-admin__copy-content">
                    <div class="ulp-admin__copy-sections">
                        <?php foreach(self::consent_string_editor_sections($group) as $section) : ?>
                            <fieldset class="ulp-admin__copy-unit <?php echo $section['wide'] ? 'ulp-admin__copy-unit--wide' : ''; ?>">
                                <legend><?php echo esc_html($section['label']); ?></legend>
                                <div class="ulp-admin__copy-grid">
                                    <?php foreach($section['fields'] as $path => $editor_label) :
                                        $field = $schema[$path];
                                        $field_id = $id_prefix . '-' . str_replace('.', '-', $path);
                                        $value = self::nested_string_value($strings, $path);
                                        $name = self::consent_string_input_name($root, $path, $language_code);
                                        $visible_label = $editor_label !== null ? $editor_label : $field['label'];
                                        ?>
                                        <div class="ulp-admin__copy-field <?php echo $field['textarea'] ? 'ulp-admin__copy-field--wide' : ''; ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($visible_label); ?></label>
                                            <?php if($field['textarea']) : ?>
                                                <textarea
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    class="large-text"
                                                    rows="3"
                                                    maxlength="<?php echo esc_attr((string)$field['maximum']); ?>"
                                                    dir="auto"
                                                    name="<?php echo esc_attr($name); ?>"
                                                    <?php if($has_error) : ?>aria-invalid="true" aria-describedby="ulp-consent-copy-error"<?php endif; ?>
                                                    required
                                                ><?php echo esc_textarea((string)$value); ?></textarea>
                                            <?php else : ?>
                                                <input
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    class="regular-text"
                                                    maxlength="<?php echo esc_attr((string)$field['maximum']); ?>"
                                                    type="text"
                                                    dir="auto"
                                                    name="<?php echo esc_attr($name); ?>"
                                                    value="<?php echo esc_attr((string)$value); ?>"
                                                    <?php if($has_error) : ?>aria-invalid="true" aria-describedby="ulp-consent-copy-error"<?php endif; ?>
                                                    required
                                                >
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach;

    }

    private static function render_localized_consent_fields($translations, $languages, $has_error = false){

        $has_tabs = count($languages) > 1;
        $active_code = self::reactwp_language_code($languages);

        ?>
        <div class="ulp-admin__language-editor" data-ulp-language-editor>
            <?php if($has_error) : ?>
                <p id="ulp-consent-copy-error" class="ulp-admin__language-error" role="alert">
                    <?php esc_html_e('Verify all text for the language identified in the error above.', 'universal-legal-pages'); ?>
                </p>
            <?php endif; ?>

            <?php if($has_tabs) : ?>
                <div class="ulp-admin__language-tabs" role="tablist" aria-label="<?php echo esc_attr(__('Consent copy language', 'universal-legal-pages')); ?>">
                    <?php foreach($languages as $language) :
                        $code = $language['code'];
                        $is_active = $code === $active_code;
                        ?>
                        <button
                            id="ulp-language-tab-<?php echo esc_attr($code); ?>"
                            class="ulp-admin__language-tab"
                            type="button"
                            role="tab"
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            aria-controls="ulp-language-panel-<?php echo esc_attr($code); ?>"
                            tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                            data-ulp-language-tab
                        >
                            <span><?php echo esc_html($language['name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ulp-admin__language-panels">
                <?php foreach($languages as $language) :
                    $code = $language['code'];
                    $translation = $translations[$code];
                    ?>
                    <section
                        id="ulp-language-panel-<?php echo esc_attr($code); ?>"
                        class="ulp-admin__language-panel"
                        <?php if($has_tabs) : ?>
                            role="tabpanel"
                            aria-labelledby="ulp-language-tab-<?php echo esc_attr($code); ?>"
                            tabindex="0"
                            data-ulp-language-panel
                        <?php endif; ?>
                    >
                        <h3>
                            <span><?php echo esc_html($language['name']); ?></span>
                        </h3>

                        <?php self::render_consent_string_fields(
                            $translation,
                            'consent_string_translations',
                            'ulp-consent-copy-' . $code,
                            $code,
                            $has_error
                        ); ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

    }

    private static function render_localized_consent_links($translations, $languages, $pages, $has_error = false){

        $has_tabs = count($languages) > 1;
        $active_code = self::reactwp_language_code($languages);

        ?>
        <div class="ulp-admin__language-editor ulp-admin__language-editor--links" data-ulp-language-editor>
            <?php if($has_error) : ?>
                <p id="ulp-consent-links-error" class="ulp-admin__language-error" role="alert">
                    <?php esc_html_e('Verify the legal-page selection for every configured language.', 'universal-legal-pages'); ?>
                </p>
            <?php endif; ?>

            <?php if($has_tabs) : ?>
                <div class="ulp-admin__language-tabs" role="tablist" aria-label="<?php echo esc_attr(__('Consent links language', 'universal-legal-pages')); ?>">
                    <?php foreach($languages as $language) :
                        $code = $language['code'];
                        $is_active = $code === $active_code;
                        ?>
                        <button
                            id="ulp-link-language-tab-<?php echo esc_attr($code); ?>"
                            class="ulp-admin__language-tab"
                            type="button"
                            role="tab"
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            aria-controls="ulp-link-language-panel-<?php echo esc_attr($code); ?>"
                            tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                            data-ulp-language-tab
                        >
                            <span><?php echo esc_html($language['name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ulp-admin__language-panels">
                <?php foreach($languages as $language) :
                    $code = $language['code'];
                    $translation = isset($translations[$code]) && is_array($translations[$code])
                        ? $translations[$code]
                        : ['consent_page_ids' => [], 'terms_page_id' => 0];
                    $field_root = 'consent_link_translations][' . $code;
                    $id_prefix = 'ulp-consent-links-' . $code;
                    ?>
                    <section
                        id="ulp-link-language-panel-<?php echo esc_attr($code); ?>"
                        class="ulp-admin__language-panel ulp-admin__language-panel--links"
                        <?php if($has_tabs) : ?>
                            role="tabpanel"
                            aria-labelledby="ulp-link-language-tab-<?php echo esc_attr($code); ?>"
                            tabindex="0"
                            data-ulp-language-panel
                        <?php endif; ?>
                    >
                        <h3><span><?php echo esc_html($language['name']); ?></span></h3>
                        <div class="ulp-admin__link-language-fields">
                            <div class="ulp-admin__link-language-field">
                                <p class="ulp-admin__field-label"><?php esc_html_e('Pages to display', 'universal-legal-pages'); ?></p>
                                <?php self::render_consent_page_choices(
                                    $translation['consent_page_ids'],
                                    $pages,
                                    $field_root . '][consent_page_ids',
                                    $id_prefix . '-pages'
                                ); ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

    }

    private static function render_localized_terms_setting($terms_required, $translations, $languages, $pages){

        $has_tabs = count($languages) > 1;
        $active_code = self::reactwp_language_code($languages);

        ?>
        <fieldset class="ulp-admin__terms-setting ulp-admin__terms-setting--localized">
            <legend><?php esc_html_e('Explicit acceptance', 'universal-legal-pages'); ?></legend>
            <div class="ulp-admin__terms-toggle">
                <?php self::render_checkbox(
                    'terms_required',
                    $terms_required,
                    __('Request explicit acceptance in preferences', 'universal-legal-pages'),
                    __('This browser-only choice does not by itself constitute a contractual record or proof of identity.', 'universal-legal-pages')
                ); ?>
            </div>
            <div class="ulp-admin__dependent-field ulp-admin__localized-terms-fields">
                <p id="ulp-localized-terms-description" class="description"><?php esc_html_e('Only used when explicit acceptance is enabled. This document will be linked directly in the confirmation checkbox.', 'universal-legal-pages'); ?></p>
                <div class="ulp-admin__language-editor ulp-admin__terms-language-editor" data-ulp-language-editor>
                    <?php if($has_tabs) : ?>
                        <div class="ulp-admin__language-tabs" role="tablist" aria-label="<?php echo esc_attr(__('Confirmation page language', 'universal-legal-pages')); ?>">
                            <?php foreach($languages as $language) :
                                $code = $language['code'];
                                $is_active = $code === $active_code;
                                ?>
                                <button
                                    id="ulp-terms-language-tab-<?php echo esc_attr($code); ?>"
                                    class="ulp-admin__language-tab"
                                    type="button"
                                    role="tab"
                                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                    aria-controls="ulp-terms-language-panel-<?php echo esc_attr($code); ?>"
                                    tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                    data-ulp-language-tab
                                >
                                    <span><?php echo esc_html($language['name']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ulp-admin__language-panels">
                        <?php foreach($languages as $language) :
                            $code = $language['code'];
                            $translation = isset($translations[$code]) && is_array($translations[$code])
                                ? $translations[$code]
                                : ['terms_page_id' => 0];
                            $field_root = 'consent_link_translations][' . $code;
                            $field_id = 'ulp-consent-links-' . $code . '-terms-page-id';
                            ?>
                            <section
                                id="ulp-terms-language-panel-<?php echo esc_attr($code); ?>"
                                class="ulp-admin__language-panel ulp-admin__language-panel--terms"
                                <?php if($has_tabs) : ?>
                                    role="tabpanel"
                                    aria-labelledby="ulp-terms-language-tab-<?php echo esc_attr($code); ?>"
                                    tabindex="0"
                                    data-ulp-language-panel
                                <?php endif; ?>
                            >
                                <h3><span><?php echo esc_html($language['name']); ?></span></h3>
                                <label for="<?php echo esc_attr($field_id); ?>"><?php esc_html_e('Link to this confirmation', 'universal-legal-pages'); ?></label>
                                <?php self::render_legal_page_select(
                                    $field_root . '][terms_page_id',
                                    isset($translation['terms_page_id']) ? $translation['terms_page_id'] : 0,
                                    $pages,
                                    'ulp-localized-terms-description',
                                    $field_id
                                ); ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </fieldset>
        <?php

    }

    public static function render_settings_page(){

        if(!current_user_can('manage_options')){
            wp_die(esc_html__('You do not have permission to manage these settings.', 'universal-legal-pages'));
        }

        $options = self::get_options();
        $languages = self::reactwp_languages();
        $has_translation_error = self::has_field_error('invalid_consent_string_translations')
            || self::has_field_error('invalid_banner_translations');
        $has_copy_error = self::has_field_error('invalid_consent_strings');
        $has_link_translation_error = self::has_field_error('invalid_consent_link_translations')
            || self::has_field_error('missing_terms_page');
        $pages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => self::MAX_CONSENT_PAGES,
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => !empty($languages),
        ]);

        ?>
        <div class="wrap ulp-admin">
            <header class="ulp-admin__hero">
                <div class="ulp-admin__hero-copy">
                    <p class="ulp-admin__context"><?php esc_html_e('Privacy centre', 'universal-legal-pages'); ?></p>
                    <h1><?php esc_html_e('Consent and integrations', 'universal-legal-pages'); ?></h1>
                    <p class="ulp-admin__lead"><?php esc_html_e('Control what may load on the site and give each visitor a clear choice before any optional tracking begins.', 'universal-legal-pages'); ?></p>
                </div>
            </header>

            <?php settings_errors(); ?>

            <div
                class="ulp-admin__save-notice"
                data-ulp-save-notice
                role="status"
                aria-live="polite"
                aria-atomic="true"
                tabindex="-1"
                hidden
            >
                <p data-ulp-save-message></p>
                <button type="button" data-ulp-save-dismiss><?php esc_html_e('Close', 'universal-legal-pages'); ?></button>
            </div>

            <?php if($options['gtm_container_id'] !== '' && ($options['ga4_measurement_id'] !== '' || $options['google_ads_id'] !== '')) : ?>
                <div class="notice notice-warning inline ulp-admin__notice"><p>
                    <?php esc_html_e('Google Tag Manager and at least one direct Google tag are configured. Make sure the container does not trigger the same Google Analytics or Google Ads destinations a second time.', 'universal-legal-pages'); ?>
                </p></div>
            <?php endif; ?>

            <form
                class="ulp-admin__form"
                method="post"
                action="options.php"
                data-ulp-settings-form
                data-ulp-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                data-ulp-ajax-action="<?php echo esc_attr(self::AJAX_SAVE_ACTION); ?>"
                data-ulp-saving-label="<?php echo esc_attr(__('Saving settings…', 'universal-legal-pages')); ?>"
                data-ulp-network-error="<?php echo esc_attr(__('Unable to save settings. Please try again.', 'universal-legal-pages')); ?>"
            >
                <?php settings_fields(self::SETTINGS_GROUP); ?>
                <?php wp_nonce_field(self::AJAX_SAVE_ACTION, 'ulp_save_nonce'); ?>

                <div class="ulp-admin__settings" data-ulp-section-switcher>
                    <div
                        class="ulp-admin__section-navigation"
                        data-ulp-section-navigation
                    >
                        <label for="ulp-settings-section"><?php esc_html_e('Settings section', 'universal-legal-pages'); ?></label>
                        <select id="ulp-settings-section" data-ulp-section-select>
                            <option value="ulp-settings-panel-banner"><?php esc_html_e('Consent banner', 'universal-legal-pages'); ?></option>
                            <option value="ulp-settings-panel-copy"><?php esc_html_e('Consent interface copy', 'universal-legal-pages'); ?></option>
                            <option value="ulp-settings-panel-categories"><?php esc_html_e('Consent categories', 'universal-legal-pages'); ?></option>
                            <option value="ulp-settings-panel-services"><?php esc_html_e('Detected services', 'universal-legal-pages'); ?></option>
                            <option value="ulp-settings-panel-pages"><?php esc_html_e('Consent module links', 'universal-legal-pages'); ?></option>
                            <option value="ulp-settings-panel-integrations"><?php esc_html_e('Integrations', 'universal-legal-pages'); ?></option>
                        </select>
                    </div>

                <section id="ulp-settings-panel-banner" class="ulp-admin-section" aria-labelledby="ulp-section-banner-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-banner-title"><?php esc_html_e('Consent banner', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Configure consent activation, policy version, choice duration, and general privacy controls.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Activation', 'universal-legal-pages'); ?></th>
                                    <td><?php self::render_checkbox('consent_enabled', $options['consent_enabled'], __('Display the banner on the site', 'universal-legal-pages')); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ulp-policy-version"><?php esc_html_e('Policy version', 'universal-legal-pages'); ?></label></th>
                                    <td>
                                        <input id="ulp-policy-version" class="regular-text" maxlength="32" pattern="[A-Za-z0-9._-]{1,32}" type="text" name="<?php echo esc_attr(self::OPTION_NAME . '[policy_version]'); ?>" value="<?php echo esc_attr($options['policy_version']); ?>" aria-describedby="ulp-policy-version-description" required>
                                        <p id="ulp-policy-version-description" class="description"><?php esc_html_e('Update this value when your uses change to request a new choice.', 'universal-legal-pages'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ulp-duration-days"><?php esc_html_e('Choice duration', 'universal-legal-pages'); ?></label></th>
                                    <td class="ulp-admin__inline-field"><input id="ulp-duration-days" min="30" max="365" step="1" type="number" name="<?php echo esc_attr(self::OPTION_NAME . '[duration_days]'); ?>" value="<?php echo esc_attr((string)$options['duration_days']); ?>"> <span><?php esc_html_e('days', 'universal-legal-pages'); ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Options', 'universal-legal-pages'); ?></th>
                                    <td class="ulp-admin__checkbox-stack">
                                        <div><?php self::render_checkbox('respect_gpc', $options['respect_gpc'], __('Respect the Global Privacy Control signal', 'universal-legal-pages'), __('When this signal is active, every service and integration with advertising and marketing behavior remains refused.', 'universal-legal-pages')); ?></div>
                                        <div>
                                            <?php self::render_checkbox('show_revisit_button', $options['show_revisit_button'], __('Display the permanent floating button', 'universal-legal-pages')); ?>
                                            <p class="description">
                                                <?php esc_html_e('You can disable it and open the same dialog from your own button by adding the attribute', 'universal-legal-pages'); ?>
                                                <code>data-ulc-open</code>.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="ulp-settings-panel-copy" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-copy-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-copy-title"><?php esc_html_e('Consent interface copy', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Edit the banner, buttons, preferences dialog, terms, and status messages.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <?php if(!empty($languages)) : ?>
                            <?php self::render_localized_consent_fields($options['consent_string_translations'], $languages, $has_translation_error); ?>
                        <?php else : ?>
                            <?php if($has_copy_error) : ?>
                                <p id="ulp-consent-copy-error" class="ulp-admin__language-error" role="alert">
                                    <?php esc_html_e('Used copy is invalid or incomplete; verify the previous values.', 'universal-legal-pages'); ?>
                                </p>
                            <?php endif; ?>
                            <?php self::render_consent_string_fields(
                                $options['consent_strings'],
                                'consent_strings',
                                'ulp-consent-copy',
                                '',
                                $has_copy_error
                            ); ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="ulp-settings-panel-categories" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-categories-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-categories-title"><?php esc_html_e('Consent categories', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Create and order the consent choices shown to visitors. Their names and descriptions define their meaning.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <div class="notice notice-info inline ulp-admin__notice"><p>
                            <?php esc_html_e('Strictly necessary remains enabled and cannot be removed. Every other category can be renamed, added, or removed.', 'universal-legal-pages'); ?>
                        </p></div>
                        <?php self::render_consent_categories($options, $languages); ?>
                    </div>
                </section>

                <section id="ulp-settings-panel-services" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-services-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-services-title"><?php esc_html_e('Detected services', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Name and classify detected domains before visitors may allow them individually.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <div class="notice notice-info inline ulp-admin__notice"><p>
                            <?php esc_html_e('Unclassified services remain blocked. A detected script is reported without being stopped or replayed; controlling it requires a trusted adapter.', 'universal-legal-pages'); ?>
                        </p></div>
                        <?php self::render_detected_services($options); ?>
                    </div>
                </section>

                <section id="ulp-settings-panel-pages" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-pages-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-pages-title"><?php esc_html_e('Consent module links', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Choose any documents that should be accessible from the banner and preferences dialog.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <?php if(!empty($languages)) : ?>
                            <?php self::render_localized_consent_links(
                                $options['consent_link_translations'],
                                $languages,
                                $pages,
                                $has_link_translation_error
                            ); ?>
                            <?php self::render_localized_terms_setting(
                                $options['terms_required'],
                                $options['consent_link_translations'],
                                $languages,
                                $pages
                            ); ?>
                        <?php else : ?>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Pages to display', 'universal-legal-pages'); ?></th>
                                        <td><?php self::render_consent_page_choices($options['consent_page_ids'], $pages); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Explicit acceptance', 'universal-legal-pages'); ?></th>
                                        <td class="ulp-admin__terms-setting">
                                            <div><?php self::render_checkbox('terms_required', $options['terms_required'], __('Request explicit acceptance in preferences', 'universal-legal-pages'), __('This browser-only choice does not by itself constitute a contractual record or proof of identity.', 'universal-legal-pages')); ?></div>
                                            <div class="ulp-admin__dependent-field">
                                                <label for="ulp-terms-page-id"><?php esc_html_e('Link to this confirmation', 'universal-legal-pages'); ?></label>
                                                <?php self::render_legal_page_select('terms_page_id', $options['terms_page_id'], $pages, 'ulp-terms-page-description'); ?>
                                                <p id="ulp-terms-page-description" class="description"><?php esc_html_e('Only used when explicit acceptance is enabled. This document will be linked directly in the confirmation checkbox.', 'universal-legal-pages'); ?></p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="ulp-settings-panel-integrations" class="ulp-admin-section ulp-admin-section--initially-hidden" aria-labelledby="ulp-section-integrations-title" data-ulp-section-panel>
                    <header class="ulp-admin-section__header">
                        <h2 id="ulp-section-integrations-title"><?php esc_html_e('Integrations', 'universal-legal-pages'); ?></h2>
                        <p><?php esc_html_e('Use only the public identifiers for the services in use. No password or private API key is required.', 'universal-legal-pages'); ?></p>
                    </header>
                    <div class="ulp-admin-section__body">
                        <table class="form-table ulp-admin__integrations" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="ulp-ga4-id"><?php esc_html_e('Google Analytics 4', 'universal-legal-pages'); ?></label></th>
                                    <td>
                                        <input id="ulp-ga4-id" class="regular-text code" maxlength="32" placeholder="G-XXXXXXXXXX" type="text" name="<?php echo esc_attr(self::OPTION_NAME . '[ga4_measurement_id]'); ?>" value="<?php echo esc_attr($options['ga4_measurement_id']); ?>" aria-describedby="ulp-ga4-description">
                                        <?php self::render_integration_category_field($options, 'ga4_category', 'ulp-ga4-category'); ?>
                                        <p id="ulp-ga4-description" class="description"><?php esc_html_e('Necessary loads the tag immediately; every other category waits until this service is accepted. The adapter keeps its internal analytics signal mapping.', 'universal-legal-pages'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ulp-gtm-id"><?php esc_html_e('Google Tag Manager', 'universal-legal-pages'); ?></label></th>
                                    <td>
                                        <input id="ulp-gtm-id" class="regular-text code" maxlength="32" placeholder="GTM-XXXXXXX" type="text" name="<?php echo esc_attr(self::OPTION_NAME . '[gtm_container_id]'); ?>" value="<?php echo esc_attr($options['gtm_container_id']); ?>" aria-describedby="ulp-gtm-description">
                                        <?php self::render_integration_category_field($options, 'gtm_category', 'ulp-gtm-category'); ?>
                                        <p id="ulp-gtm-description" class="description"><?php esc_html_e('Necessary loads the container immediately; every other category waits for acceptance. Google Consent Mode v2 still applies, and each container tag must declare its consent requirements.', 'universal-legal-pages'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ulp-google-ads-id"><?php esc_html_e('Google Ads', 'universal-legal-pages'); ?></label></th>
                                    <td>
                                        <input id="ulp-google-ads-id" class="regular-text code" maxlength="23" pattern="AW-[0-9]{5,20}" placeholder="AW-123456789" spellcheck="false" type="text" name="<?php echo esc_attr(self::OPTION_NAME . '[google_ads_id]'); ?>" value="<?php echo esc_attr($options['google_ads_id']); ?>" aria-describedby="ulp-google-ads-description">
                                        <?php self::render_integration_category_field($options, 'google_ads_category', 'ulp-google-ads-category'); ?>
                                        <p id="ulp-google-ads-description" class="description"><?php esc_html_e('Necessary loads the Google tag immediately; every other category waits until this service is accepted. The adapter keeps its internal marketing mapping for standard remarketing.', 'universal-legal-pages'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="ulp-meta-id"><?php esc_html_e('Meta Pixel', 'universal-legal-pages'); ?></label></th>
                                    <td>
                                        <input id="ulp-meta-id" class="regular-text code" maxlength="32" inputmode="numeric" placeholder="123456789012345" type="text" name="<?php echo esc_attr(self::OPTION_NAME . '[meta_pixel_id]'); ?>" value="<?php echo esc_attr($options['meta_pixel_id']); ?>" aria-describedby="ulp-meta-description">
                                        <?php self::render_integration_category_field($options, 'meta_pixel_category', 'ulp-meta-category'); ?>
                                        <p id="ulp-meta-description" class="description"><?php esc_html_e('Necessary loads the pixel immediately; every other category waits until this service is accepted. The adapter keeps its internal marketing signal mapping.', 'universal-legal-pages'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php self::render_custom_integrations($options); ?>
                    </div>
                </section>
                </div>

                <footer class="ulp-admin__actions">
                    <p><?php esc_html_e('Apply the changes by saving the settings.', 'universal-legal-pages'); ?></p>
                    <?php submit_button(__('Save settings', 'universal-legal-pages')); ?>
                </footer>
            </form>

            <footer class="ulp-admin__credit">
                <p>&copy; <a href="<?php echo esc_url('https://champgauche.studio'); ?>" target="_blank" rel="noopener noreferrer">Studio Champ Gauche</a></p>
            </footer>
        </div>
        <?php

    }

    public static function use_plugin_template($template){

        if(!is_singular(self::POST_TYPE)){
            return $template;
        }

        $plugin_template = __DIR__ . '/templates/single-legal-page.php';

        return is_readable($plugin_template) ? $plugin_template : $template;

    }

    public static function add_body_class($classes){

        if(is_singular(self::POST_TYPE)){
            $classes[] = 'universal-legal-page';
        }

        return array_values(array_unique($classes));

    }

    private static function get_public_legal_link($id, $fallback_label){

        $id = absint($id);

        if($id === 0 || get_post_type($id) !== self::POST_TYPE || get_post_status($id) !== 'publish'){
            return null;
        }

        $url = get_permalink($id);

        if(!is_string($url) || !preg_match('#\Ahttps?://#i', $url)){
            return null;
        }

        $label = trim(wp_strip_all_tags(get_the_title($id)));

        return [
            'url' => esc_url_raw($url),
            'label' => $label !== '' ? $label : $fallback_label,
        ];

    }

    private static function get_public_legal_links($page_ids){

        $links = [];

        foreach($page_ids as $page_id){
            $link = self::get_public_legal_link(
                $page_id,
                __('Legal document', 'universal-legal-pages')
            );

            if($link !== null){
                $links[] = $link;
            }
        }

        return $links;

    }

    private static function normalize_public_stylesheet_url($value){

        if(!is_string($value)){
            return '';
        }

        $value = trim($value);

        if($value === '' || strlen($value) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $value)){
            return '';
        }

        $parts = parse_url($value);

        if(
            !is_array($parts)
            || empty($parts['scheme'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ){
            return '';
        }

        return esc_url_raw($value);

    }

    private static function versioned_stylesheet_url($url, $path){

        if(!is_string($path) || $path === '' || !is_readable($path)){
            return $url;
        }

        $modified = filemtime($path);

        return is_int($modified)
            ? add_query_arg('ver', (string)$modified, $url)
            : $url;

    }

    private static function get_consent_stylesheet_url(){

        $source = 'plugin';
        $path = __DIR__ . '/assets/css/consent-manager.css';
        $url = plugins_url('assets/css/consent-manager.css', __FILE__);
        $theme_path = get_theme_file_path(self::CONSENT_THEME_STYLESHEET);

        if(is_string($theme_path) && $theme_path !== '' && is_readable($theme_path)){
            $theme_url = self::normalize_public_stylesheet_url(
                get_theme_file_uri(self::CONSENT_THEME_STYLESHEET)
            );

            if($theme_url !== ''){
                $source = 'theme';
                $path = $theme_path;
                $url = $theme_url;
            }
        }

        $url = self::versioned_stylesheet_url($url, $path);
        $filtered_url = apply_filters(
            'universal_legal_pages_consent_stylesheet_url',
            $url,
            [
                'source' => $source,
                'path' => $path,
                'themeRelativePath' => self::CONSENT_THEME_STYLESHEET,
            ]
        );
        $normalized_url = self::normalize_public_stylesheet_url($filtered_url);

        return $normalized_url !== '' ? $normalized_url : $url;

    }

    public static function get_public_consent_config(){

        $options = self::get_options();
        $cookie_path = defined('COOKIEPATH') && is_string(COOKIEPATH) && COOKIEPATH !== ''
            ? COOKIEPATH
            : '/';

        if(!preg_match('#\A/[A-Za-z0-9._~/-]*\z#', $cookie_path)){
            $cookie_path = '/';
        }

        $legal_links = self::get_public_legal_links($options['consent_page_ids']);

        $terms_link = self::get_public_legal_link(
            $options['terms_page_id'],
            __('Terms', 'universal-legal-pages')
        );
        $languages = self::reactwp_languages();
        $current_language = self::reactwp_language_code($languages);
        $resolved_strings = self::resolve_consent_strings($options);
        $categories = self::public_consent_categories($options, $current_language);
        $services = self::get_public_services($options);
        $custom_integrations = self::public_custom_integrations(
            $options['custom_integrations'],
            $options['consent_categories']
        );

        $config = [
            'version' => 4,
            'cookieName' => 'ulp_consent_' . absint(get_current_blog_id()),
            'cookiePath' => $cookie_path,
            'durationDays' => (int)$options['duration_days'],
            'policyVersion' => (string)$options['policy_version'],
            'respectGpc' => (bool)$options['respect_gpc'],
            'showRevisitButton' => (bool)$options['show_revisit_button'],
            'termsRequired' => (bool)$options['terms_required'],
            'stylesheetUrl' => self::get_consent_stylesheet_url(),
            'legalLinks' => $legal_links,
            'termsLink' => $terms_link,
            'integrations' => [
                'googleAnalytics' => $options['ga4_category'] !== '' ? (string)$options['ga4_measurement_id'] : '',
                'googleTagManager' => $options['gtm_category'] !== '' ? (string)$options['gtm_container_id'] : '',
                'googleAds' => $options['google_ads_category'] !== '' ? (string)$options['google_ads_id'] : '',
                'metaPixel' => $options['meta_pixel_category'] !== '' ? (string)$options['meta_pixel_id'] : '',
            ],
            'integrationCategories' => [
                'googleAnalytics' => (string)$options['ga4_category'],
                'googleTagManager' => (string)$options['gtm_category'],
                'googleAds' => (string)$options['google_ads_category'],
                'metaPixel' => (string)$options['meta_pixel_category'],
            ],
            'customIntegrations' => $custom_integrations,
            'categories' => $categories,
            'services' => $services,
            'serviceRegistryVersion' => self::service_registry_version(
                $services,
                $options['custom_integrations'],
                $options['consent_categories']
            ),
            'strings' => $resolved_strings,
        ];

        if(!empty($languages)){
            $link_translations = [];
            $category_translations = [];

            foreach($languages as $language){
                $code = $language['code'];
                $bundle = $options['consent_link_translations'][$code];
                $category_translations[$code] = self::public_consent_categories($options, $code);
                $link_translations[$code] = [
                    'legalLinks' => self::get_public_legal_links($bundle['consent_page_ids']),
                    'termsLink' => self::get_public_legal_link(
                        $bundle['terms_page_id'],
                        __('Terms', 'universal-legal-pages')
                    ),
                ];
            }

            $config['currentLanguage'] = $current_language;
            $config['bannerTranslations'] = $options['banner_translations'];
            $config['stringTranslations'] = $options['consent_string_translations'];
            $config['linkTranslations'] = $link_translations;
            $config['categoryTranslations'] = $category_translations;
        }

        return $config;

    }

    public static function enqueue_assets(){

        $is_legal_page = is_singular(self::POST_TYPE);

        if($is_legal_page){
            global $wp_styles;
            global $wp_scripts;

            if($wp_styles instanceof WP_Styles){
                foreach($wp_styles->queue as $handle){
                    if($handle !== 'admin-bar'){
                        wp_dequeue_style($handle);
                    }
                }
            }

            if($wp_scripts instanceof WP_Scripts){
                foreach($wp_scripts->queue as $handle){
                    if($handle !== 'admin-bar'){
                        wp_dequeue_script($handle);
                    }
                }
            }

            $stylesheet = __DIR__ . '/assets/css/legal-pages.css';
            $version = is_readable($stylesheet)
                ? (string)filemtime($stylesheet)
                : self::VERSION;

            wp_enqueue_style(
                self::STYLE_HANDLE,
                plugins_url('assets/css/legal-pages.css', __FILE__),
                [],
                $version
            );
        }

        $options = self::get_options();

        if(!$options['consent_enabled']){
            return;
        }

        $script = __DIR__ . '/assets/js/consent-manager.js';
        $script_version = is_readable($script)
            ? (string)filemtime($script)
            : self::VERSION;

        wp_enqueue_script(
            self::CONSENT_SCRIPT_HANDLE,
            plugins_url('assets/js/consent-manager.js', __FILE__),
            [],
            $script_version,
            true
        );

        $bootstrap = 'window.UniversalLegalConsent=window.UniversalLegalConsent||{queue:[],pendingLanguage:null,registerIntegration:function(definition){this.queue.push(definition);},setLanguage:function(language){this.pendingLanguage=language;}};';
        $config = wp_json_encode(
            self::get_public_consent_config(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if(!is_string($config)){
            $config = '{}';
        }

        wp_add_inline_script(self::CONSENT_SCRIPT_HANDLE, $bootstrap, 'before');
        wp_add_inline_script(
            self::CONSENT_SCRIPT_HANDLE,
            'window.UniversalLegalConsentConfig=' . $config . ';',
            'before'
        );

    }

    public static function activate(){

        self::register_post_type();

        if(get_option(self::OPTION_NAME, null) === null){
            add_option(self::OPTION_NAME, self::default_options(), '', false);
        }

        flush_rewrite_rules();

    }

    public static function deactivate(){

        if(post_type_exists(self::POST_TYPE)){
            unregister_post_type(self::POST_TYPE);
        }

        flush_rewrite_rules();

    }

}

Universal_Legal_Pages::boot();

register_activation_hook(__FILE__, ['Universal_Legal_Pages', 'activate']);
register_deactivation_hook(__FILE__, ['Universal_Legal_Pages', 'deactivate']);
