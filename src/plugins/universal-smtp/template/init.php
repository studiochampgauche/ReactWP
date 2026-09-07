<?php
/*
 * Plugin Name: Universal SMTP
 * Description: Route WordPress email through a configurable SMTP server with protected credentials and sender controls.
 * Author: Studio Champ Gauche
 * Author URI: https://champgauche.studio
 * License: GPL-2.0-or-later
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Text Domain: universal-smtp
 * Domain Path: /languages
 * Update URI: false
 * Version: 1.0.0
 */

if(!defined('ABSPATH')){
    exit;
}

final class Universal_SMTP{

    const VERSION = '1.0.0';
    const OPTION_NAME = 'universal_smtp_options';
    const LAST_STATUS_OPTION = 'universal_smtp_last_status';
    const SETTINGS_GROUP = 'universal_smtp_settings';
    const SETTINGS_SLUG = 'universal-smtp';
    const AJAX_SAVE_ACTION = 'universal_smtp_save_settings';
    const AJAX_TEST_ACTION = 'universal_smtp_send_test';
    const ADMIN_STYLE_HANDLE = 'universal-smtp-admin';
    const ADMIN_SCRIPT_HANDLE = 'universal-smtp-admin';
    const MAX_USERNAME_CHARACTERS = 320;
    const MAX_PASSWORD_BYTES = 1024;
    const MAX_FROM_NAME_CHARACTERS = 120;
    const MAX_EMAIL_BYTES = 254;
    const MAX_HOST_BYTES = 253;
    const RATE_LIMIT_ATTEMPTS = 5;
    const RATE_LIMIT_WINDOW = 600;
    const RATE_LIMIT_LOCK_SECONDS = 5;

    private static $settings_page_hook = '';
    private static $options_cache = null;
    private static $validation_errors = [];
    private static $persisting_canonical = false;

    public static function boot(){

        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_menu', [__CLASS__, 'register_settings_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('wp_ajax_' . self::AJAX_SAVE_ACTION, [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_' . self::AJAX_TEST_ACTION, [__CLASS__, 'ajax_send_test']);
        add_action('admin_post_' . self::AJAX_TEST_ACTION, [__CLASS__, 'admin_post_send_test']);
        add_action('phpmailer_init', [__CLASS__, 'configure_phpmailer'], PHP_INT_MAX);
        add_action('wp_mail_succeeded', [__CLASS__, 'record_mail_success']);
        add_action('wp_mail_failed', [__CLASS__, 'record_mail_failure']);

        add_filter('wp_mail_from', [__CLASS__, 'filter_from_email_fallback'], 1);
        add_filter('wp_mail_from_name', [__CLASS__, 'filter_from_name_fallback'], 1);
        add_filter('wp_mail_from', [__CLASS__, 'filter_from_email_force'], PHP_INT_MAX);
        add_filter('wp_mail_from_name', [__CLASS__, 'filter_from_name_force'], PHP_INT_MAX);

    }

    public static function load_textdomain(){

        load_plugin_textdomain(
            'universal-smtp',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

    }

    public static function default_options(){

        return [
            'enabled' => false,
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'authenticate' => true,
            'auth_type' => 'auto',
            'username' => '',
            'password_ciphertext' => '',
            'from_email' => '',
            'from_name' => '',
            'force_from_email' => true,
            'force_from_name' => true,
            'return_path' => false,
            'timeout' => 15,
        ];

    }

    private static function normalize_stored_options($stored){

        $defaults = self::default_options();

        if(!is_array($stored)){
            return $defaults;
        }

        $stored = array_intersect_key($stored, $defaults);
        $options = array_merge($defaults, $stored);

        foreach(['enabled', 'authenticate', 'force_from_email', 'force_from_name', 'return_path'] as $key){
            $options[$key] = (bool)$options[$key];
        }

        $options['host'] = is_string($options['host']) ? $options['host'] : '';
        $options['port'] = is_numeric($options['port']) ? (int)$options['port'] : 587;
        $options['encryption'] = in_array($options['encryption'], ['tls', 'ssl', 'none'], true)
            ? $options['encryption']
            : 'tls';
        $options['auth_type'] = in_array($options['auth_type'], ['auto', 'login', 'plain', 'cram-md5'], true)
            ? $options['auth_type']
            : 'auto';
        $options['username'] = is_string($options['username']) ? $options['username'] : '';
        $options['password_ciphertext'] = is_string($options['password_ciphertext'])
            && strlen($options['password_ciphertext']) <= 4096
                ? $options['password_ciphertext']
                : '';
        $options['from_email'] = is_string($options['from_email']) ? $options['from_email'] : '';
        $options['from_name'] = is_string($options['from_name']) ? $options['from_name'] : '';
        $options['timeout'] = is_numeric($options['timeout']) ? (int)$options['timeout'] : 15;

        return $options;

    }

    public static function get_options($refresh = false){

        if(!$refresh && is_array(self::$options_cache)){
            return self::$options_cache;
        }

        self::$options_cache = self::normalize_stored_options(
            get_option(self::OPTION_NAME, [])
        );

        return self::$options_cache;

    }

    private static function character_length($value){

        if(function_exists('mb_strlen')){
            return mb_strlen($value, 'UTF-8');
        }

        $matched = preg_match_all('/./us', $value, $characters);

        return $matched === false ? PHP_INT_MAX : count($characters[0]);

    }

    private static function has_plain_text_controls($value){

        return preg_match('//u', $value) !== 1
            || preg_match('/[\x00-\x1F\x7F]/u', $value) === 1;

    }

    private static function scalar_string($value, &$valid){

        if(!is_string($value)){
            $valid = false;
            return '';
        }

        $valid = true;

        return $value;

    }

    private static function boolean_value($value, &$valid){

        if($value === true || $value === 1 || $value === '1'){
            $valid = true;
            return true;
        }

        if($value === false || $value === 0 || $value === '0'){
            $valid = true;
            return false;
        }

        $valid = false;

        return false;

    }

    private static function integer_value($value, $minimum, $maximum, &$valid){

        if(is_int($value)){
            $integer = $value;
        }elseif(is_string($value) && preg_match('/\A[0-9]{1,5}\z/', $value) === 1){
            $integer = (int)$value;
        }else{
            $valid = false;
            return $minimum;
        }

        $valid = $integer >= $minimum && $integer <= $maximum;

        return $integer;

    }

    private static function host_is_valid($host){

        if(
            !is_string($host)
            || $host === ''
            || strlen($host) > self::MAX_HOST_BYTES
            || preg_match('/[\x00-\x20\x7F]/', $host) === 1
            || preg_match('~[\\/@?#]~', $host) === 1
        ){
            return false;
        }

        if(filter_var($host, FILTER_VALIDATE_IP) !== false){
            return true;
        }

        if(preg_match('/\A[A-Za-z0-9.-]+\z/', $host) !== 1 || substr($host, -1) === '.'){
            return false;
        }

        foreach(explode('.', $host) as $label){
            if(
                $label === ''
                || strlen($label) > 63
                || preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\z/', $label) !== 1
            ){
                return false;
            }
        }

        return true;

    }

    private static function email_is_valid($email){

        if(
            !is_string($email)
            || $email === ''
            || strlen($email) > self::MAX_EMAIL_BYTES
            || preg_match('/[\x00-\x20\x7F]/', $email) === 1
        ){
            return false;
        }

        if(function_exists('is_email')){
            return is_email($email) !== false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

    }

    private static function password_is_valid($password){

        return is_string($password)
            && $password !== ''
            && strlen($password) <= self::MAX_PASSWORD_BYTES
            && preg_match('/[\x00\r\n]/', $password) !== 1;

    }

    private static function encryption_key(){

        if(!function_exists('wp_salt')){
            return null;
        }

        $auth_salt = wp_salt('auth');
        $secure_auth_salt = wp_salt('secure_auth');

        if(!is_string($auth_salt) || !is_string($secure_auth_salt)){
            return null;
        }

        return hash(
            'sha256',
            'universal-smtp:v1|' . $auth_salt . "\0" . $secure_auth_salt,
            true
        );

    }

    private static function encrypt_password($password){

        $key = self::encryption_key();

        if(!is_string($key) || strlen($key) !== 32){
            return null;
        }

        try{
            if(
                function_exists('sodium_crypto_secretbox')
                && defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')
            ){
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $ciphertext = sodium_crypto_secretbox($password, $nonce, $key);

                return 'sodium:v1:' . base64_encode($nonce . $ciphertext);
            }

            if(function_exists('openssl_encrypt')){
                $iv = random_bytes(12);
                $tag = '';
                $ciphertext = openssl_encrypt(
                    $password,
                    'aes-256-gcm',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag,
                    'universal-smtp:v1',
                    16
                );

                if(is_string($ciphertext) && strlen($tag) === 16){
                    return 'openssl:v1:' . base64_encode($iv . $tag . $ciphertext);
                }
            }
        }catch(Throwable $error){
            return null;
        }

        return null;

    }

    private static function decrypt_password($stored){

        if(!is_string($stored) || $stored === ''){
            return null;
        }

        $key = self::encryption_key();

        if(!is_string($key) || strlen($key) !== 32){
            return null;
        }

        try{
            if(strpos($stored, 'sodium:v1:') === 0){
                if(
                    !function_exists('sodium_crypto_secretbox_open')
                    || !defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')
                ){
                    return null;
                }

                $decoded = base64_decode(substr($stored, strlen('sodium:v1:')), true);
                $minimum = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 16;

                if(!is_string($decoded) || strlen($decoded) < $minimum){
                    return null;
                }

                $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $password = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

                return is_string($password) && self::password_is_valid($password)
                    ? $password
                    : null;
            }

            if(strpos($stored, 'openssl:v1:') === 0){
                if(!function_exists('openssl_decrypt')){
                    return null;
                }

                $decoded = base64_decode(substr($stored, strlen('openssl:v1:')), true);

                if(!is_string($decoded) || strlen($decoded) < 29){
                    return null;
                }

                $iv = substr($decoded, 0, 12);
                $tag = substr($decoded, 12, 16);
                $ciphertext = substr($decoded, 28);
                $password = openssl_decrypt(
                    $ciphertext,
                    'aes-256-gcm',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag,
                    'universal-smtp:v1'
                );

                return is_string($password) && self::password_is_valid($password)
                    ? $password
                    : null;
            }
        }catch(Throwable $error){
            return null;
        }

        return null;

    }

    private static function runtime_password($options){

        if(defined('UNIVERSAL_SMTP_PASSWORD')){
            $password = constant('UNIVERSAL_SMTP_PASSWORD');

            return self::password_is_valid($password) ? $password : null;
        }

        return self::decrypt_password($options['password_ciphertext'] ?? '');

    }

    public static function password_state($options = null){

        $options = is_array($options) ? self::normalize_stored_options($options) : self::get_options();

        return [
            'managed' => defined('UNIVERSAL_SMTP_PASSWORD'),
            'configured' => self::runtime_password($options) !== null,
        ];

    }

    private static function error_item($field, $code, $message){

        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];

    }

    private static function validation_result($input, $previous){

        $previous = self::normalize_stored_options($previous);
        $errors = [];

        if(!is_array($input)){
            return [
                $previous,
                [self::error_item('', 'invalid_request', __('The submitted settings are invalid.', 'universal-smtp'))],
            ];
        }

        $allowed = [
            '_present',
            'enabled',
            'host',
            'port',
            'encryption',
            'authenticate',
            'auth_type',
            'username',
            'password',
            'clear_password',
            'from_email',
            'from_name',
            'force_from_email',
            'force_from_name',
            'return_path',
            'timeout',
        ];

        foreach(array_keys($input) as $key){
            if(!is_string($key) || !in_array($key, $allowed, true)){
                $errors[] = self::error_item('', 'unknown_field', __('The request contains a field that cannot be changed here.', 'universal-smtp'));
                break;
            }
        }

        if(!isset($input['_present']) || $input['_present'] !== '1'){
            $errors[] = self::error_item('', 'invalid_request', __('The complete settings form is required.', 'universal-smtp'));
        }

        foreach(['host', 'port', 'encryption', 'from_email', 'from_name', 'timeout'] as $required_key){
            if(!array_key_exists($required_key, $input)){
                $errors[] = self::error_item($required_key, 'missing_field', __('A required settings field is missing.', 'universal-smtp'));
            }
        }

        $candidate = $previous;
        $valid = false;

        $candidate['enabled'] = array_key_exists('enabled', $input)
            ? self::boolean_value($input['enabled'], $valid)
            : false;
        if(array_key_exists('enabled', $input) && !$valid){
            $errors[] = self::error_item('enabled', 'invalid_enabled', __('The SMTP enabled setting is invalid.', 'universal-smtp'));
        }

        if(array_key_exists('host', $input)){
            $host = self::scalar_string($input['host'], $valid);

            if($valid){
                $host = trim($host);
                $candidate['host'] = filter_var($host, FILTER_VALIDATE_IP) !== false
                    ? $host
                    : strtolower($host);
            }

            if(!$valid || ($host !== '' && !self::host_is_valid($host))){
                $errors[] = self::error_item('host', 'invalid_host', __('Enter a valid SMTP hostname or IP address without a scheme, path, or credentials.', 'universal-smtp'));
            }elseif($candidate['enabled'] && $host === ''){
                $errors[] = self::error_item('host', 'host_required', __('An SMTP host is required when SMTP delivery is enabled.', 'universal-smtp'));
            }
        }

        if(array_key_exists('port', $input)){
            $candidate['port'] = self::integer_value($input['port'], 1, 65535, $valid);

            if(!$valid){
                $errors[] = self::error_item('port', 'invalid_port', __('Enter an SMTP port from 1 to 65535.', 'universal-smtp'));
            }
        }

        if(array_key_exists('encryption', $input)){
            $encryption = self::scalar_string($input['encryption'], $valid);

            if(!$valid || !in_array($encryption, ['tls', 'ssl', 'none'], true)){
                $errors[] = self::error_item('encryption', 'invalid_encryption', __('Choose STARTTLS, implicit TLS, or no encryption.', 'universal-smtp'));
            }else{
                $candidate['encryption'] = $encryption;
            }
        }

        $candidate['authenticate'] = array_key_exists('authenticate', $input)
            ? self::boolean_value($input['authenticate'], $valid)
            : false;
        if(array_key_exists('authenticate', $input) && !$valid){
            $errors[] = self::error_item('authenticate', 'invalid_authenticate', __('The SMTP authentication setting is invalid.', 'universal-smtp'));
        }

        if(array_key_exists('auth_type', $input)){
            $auth_type = self::scalar_string($input['auth_type'], $valid);

            if(!$valid || !in_array($auth_type, ['auto', 'login', 'plain', 'cram-md5'], true)){
                $errors[] = self::error_item('auth_type', 'invalid_auth_type', __('Choose a supported SMTP authentication type.', 'universal-smtp'));
            }else{
                $candidate['auth_type'] = $auth_type;
            }
        }elseif($candidate['authenticate']){
            $errors[] = self::error_item('auth_type', 'missing_field', __('Choose an SMTP authentication type.', 'universal-smtp'));
        }

        if(array_key_exists('username', $input)){
            $username = self::scalar_string($input['username'], $valid);

            if($valid){
                $username = trim($username);
            }

            if(
                !$valid
                || self::character_length($username) > self::MAX_USERNAME_CHARACTERS
                || self::has_plain_text_controls($username)
                || (function_exists('wp_strip_all_tags') && wp_strip_all_tags($username) !== $username)
            ){
                $errors[] = self::error_item('username', 'invalid_username', __('Enter a plain-text SMTP username without HTML or control characters.', 'universal-smtp'));
            }else{
                $candidate['username'] = $username;
            }
        }

        if($candidate['enabled'] && $candidate['authenticate'] && $candidate['username'] === ''){
            $errors[] = self::error_item('username', 'username_required', __('An SMTP username is required when authentication is enabled.', 'universal-smtp'));
        }

        $clear_password = array_key_exists('clear_password', $input)
            ? self::boolean_value($input['clear_password'], $valid)
            : false;
        if(array_key_exists('clear_password', $input) && !$valid){
            $errors[] = self::error_item('clear_password', 'invalid_clear_password', __('The clear-password setting is invalid.', 'universal-smtp'));
        }

        $submitted_password = '';
        $password_submitted = array_key_exists('password', $input);

        if($password_submitted){
            if(!is_string($input['password'])){
                $errors[] = self::error_item('password', 'invalid_password', __('The SMTP password is invalid.', 'universal-smtp'));
            }else{
                $submitted_password = $input['password'];

                if(
                    $submitted_password !== ''
                    && !self::password_is_valid($submitted_password)
                ){
                    $errors[] = self::error_item('password', 'invalid_password', __('The SMTP password must be no more than 1024 bytes and cannot contain line breaks or null bytes.', 'universal-smtp'));
                }
            }
        }

        if($clear_password && $submitted_password !== ''){
            $errors[] = self::error_item('password', 'password_conflict', __('Enter a new password or clear the stored password, but not both.', 'universal-smtp'));
        }elseif(defined('UNIVERSAL_SMTP_PASSWORD') && ($clear_password || $submitted_password !== '')){
            $errors[] = self::error_item('password', 'password_managed', __('The SMTP password is managed by the UNIVERSAL_SMTP_PASSWORD constant.', 'universal-smtp'));
        }elseif($clear_password){
            $candidate['password_ciphertext'] = '';
        }elseif($submitted_password !== ''){
            $ciphertext = self::encrypt_password($submitted_password);

            if($ciphertext === null){
                $errors[] = self::error_item('password', 'password_crypto_unavailable', __('The password could not be encrypted. The previous password was preserved.', 'universal-smtp'));
            }else{
                $candidate['password_ciphertext'] = $ciphertext;
            }
        }

        if(
            $candidate['enabled']
            && $candidate['authenticate']
            && self::runtime_password($candidate) === null
        ){
            $errors[] = self::error_item('password', 'password_required', __('A valid SMTP password is required when authentication is enabled.', 'universal-smtp'));
        }

        if(array_key_exists('from_email', $input)){
            $from_email = self::scalar_string($input['from_email'], $valid);

            if($valid){
                $from_email = trim($from_email);
            }

            if(!$valid || ($from_email !== '' && !self::email_is_valid($from_email))){
                $errors[] = self::error_item('from_email', 'invalid_from_email', __('Enter a valid From email address.', 'universal-smtp'));
            }else{
                $candidate['from_email'] = $from_email;
            }

            if($candidate['enabled'] && $from_email === ''){
                $errors[] = self::error_item('from_email', 'from_email_required', __('A From email address is required when SMTP delivery is enabled.', 'universal-smtp'));
            }
        }

        if(array_key_exists('from_name', $input)){
            $from_name = self::scalar_string($input['from_name'], $valid);

            if($valid){
                $from_name = trim($from_name);
            }

            if(
                !$valid
                || self::character_length($from_name) > self::MAX_FROM_NAME_CHARACTERS
                || self::has_plain_text_controls($from_name)
                || (function_exists('wp_strip_all_tags') && wp_strip_all_tags($from_name) !== $from_name)
            ){
                $errors[] = self::error_item('from_name', 'invalid_from_name', __('Enter a plain-text From name of no more than 120 characters.', 'universal-smtp'));
            }else{
                $candidate['from_name'] = $from_name;
            }

            if($candidate['enabled'] && $from_name === ''){
                $errors[] = self::error_item('from_name', 'from_name_required', __('A From name is required when SMTP delivery is enabled.', 'universal-smtp'));
            }
        }

        foreach([
            'force_from_email' => __('The force From email setting is invalid.', 'universal-smtp'),
            'force_from_name' => __('The force From name setting is invalid.', 'universal-smtp'),
            'return_path' => __('The Return-Path setting is invalid.', 'universal-smtp'),
        ] as $key => $message){
            $candidate[$key] = array_key_exists($key, $input)
                ? self::boolean_value($input[$key], $valid)
                : false;

            if(array_key_exists($key, $input) && !$valid){
                $errors[] = self::error_item($key, 'invalid_' . $key, $message);
            }
        }

        if(array_key_exists('timeout', $input)){
            $candidate['timeout'] = self::integer_value($input['timeout'], 5, 60, $valid);

            if(!$valid){
                $errors[] = self::error_item('timeout', 'invalid_timeout', __('Enter a timeout from 5 to 60 seconds.', 'universal-smtp'));
            }
        }

        return [$candidate, array_slice($errors, 0, 30)];

    }

    private static function add_validation_notices($errors){

        if(!function_exists('add_settings_error')){
            return;
        }

        foreach($errors as $index => $error){
            add_settings_error(
                self::OPTION_NAME,
                isset($error['code']) ? $error['code'] : 'invalid_settings_' . $index,
                isset($error['message']) ? $error['message'] : __('The settings could not be saved.', 'universal-smtp'),
                'error'
            );
        }

    }

    public static function sanitize_options($input){

        if(self::$persisting_canonical){
            return self::normalize_stored_options($input);
        }

        $previous = self::normalize_stored_options(get_option(self::OPTION_NAME, []));
        list($candidate, $errors) = self::validation_result($input, $previous);
        self::$validation_errors = $errors;

        if(!empty($errors)){
            self::add_validation_notices($errors);
            self::$options_cache = $previous;
            return $previous;
        }

        self::$options_cache = $candidate;

        return $candidate;

    }

    public static function validation_errors(){

        return self::$validation_errors;

    }

    public static function register_settings(){

        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default' => self::default_options(),
        ]);

    }

    private static function persist_options($options){

        self::$persisting_canonical = true;

        try{
            update_option(self::OPTION_NAME, $options, false);
        }finally{
            self::$persisting_canonical = false;
        }

        $stored = self::normalize_stored_options(get_option(self::OPTION_NAME, []));
        self::$options_cache = $stored;

        return $stored === self::normalize_stored_options($options);

    }

    private static function errors_payload($errors){

        $items = [];
        $field_errors = [];

        foreach(array_slice(is_array($errors) ? $errors : [], 0, 30) as $error){
            if(!is_array($error)){
                continue;
            }

            $field = isset($error['field']) && is_string($error['field'])
                ? $error['field']
                : '';
            $code = isset($error['code']) && is_string($error['code'])
                ? sanitize_key($error['code'])
                : 'invalid_settings';
            $message = isset($error['message']) && is_string($error['message'])
                ? $error['message']
                : __('The settings are invalid.', 'universal-smtp');

            $items[] = [
                'field' => $field,
                'code' => $code !== '' ? $code : 'invalid_settings',
                'message' => $message,
            ];

            if($field !== '' && !isset($field_errors[$field])){
                $field_errors[$field] = $message;
            }
        }

        return [
            'fieldErrors' => $field_errors,
            'errors' => $items,
        ];

    }

    private static function unauthorized_ajax_response(){

        wp_send_json_error([
            'message' => __('You do not have permission to manage these settings.', 'universal-smtp'),
            'fieldErrors' => [],
            'errors' => [],
        ], 403);

    }

    public static function ajax_save_settings(){

        if(!current_user_can('manage_options')){
            self::unauthorized_ajax_response();
            return;
        }

        if(!check_ajax_referer(self::AJAX_SAVE_ACTION, 'nonce', false)){
            wp_send_json_error([
                'message' => __('Your session has expired. Reload the page and try again.', 'universal-smtp'),
                'fieldErrors' => [],
                'errors' => [],
            ], 403);
            return;
        }

        $input = array_key_exists(self::OPTION_NAME, $_POST)
            ? wp_unslash($_POST[self::OPTION_NAME])
            : null;
        $previous = self::normalize_stored_options(get_option(self::OPTION_NAME, []));
        list($candidate, $errors) = self::validation_result($input, $previous);
        self::$validation_errors = $errors;

        if(!empty($errors)){
            $payload = self::errors_payload($errors);
            $payload['message'] = __('The settings could not be saved. Review the highlighted fields.', 'universal-smtp');
            wp_send_json_error($payload, 422);
            return;
        }

        if(!self::persist_options($candidate)){
            wp_send_json_error([
                'message' => __('The settings could not be saved. Try again.', 'universal-smtp'),
                'fieldErrors' => [],
                'errors' => [[
                    'field' => '',
                    'code' => 'persistence_failed',
                    'message' => __('The settings could not be saved. Try again.', 'universal-smtp'),
                ]],
            ], 500);
            return;
        }

        $stored = self::get_options();
        $password_state = self::password_state($stored);

        wp_send_json_success([
            'message' => __('Settings saved.', 'universal-smtp'),
            'passwordConfigured' => $password_state['configured'],
            'passwordManaged' => $password_state['managed'],
            'fieldErrors' => [],
            'errors' => [],
        ], 200);

    }

    public static function configuration_is_complete($options = null){

        $options = is_array($options) ? self::normalize_stored_options($options) : self::get_options();

        if(
            !$options['enabled']
            || !self::host_is_valid($options['host'])
            || $options['port'] < 1
            || $options['port'] > 65535
            || !in_array($options['encryption'], ['tls', 'ssl', 'none'], true)
            || !self::email_is_valid($options['from_email'])
            || $options['from_name'] === ''
            || self::character_length($options['from_name']) > self::MAX_FROM_NAME_CHARACTERS
            || self::has_plain_text_controls($options['from_name'])
            || (function_exists('wp_strip_all_tags') && wp_strip_all_tags($options['from_name']) !== $options['from_name'])
            || $options['timeout'] < 5
            || $options['timeout'] > 60
        ){
            return false;
        }

        if(!$options['authenticate']){
            return true;
        }

        return $options['username'] !== ''
            && self::character_length($options['username']) <= self::MAX_USERNAME_CHARACTERS
            && !self::has_plain_text_controls($options['username'])
            && (!function_exists('wp_strip_all_tags') || wp_strip_all_tags($options['username']) === $options['username'])
            && in_array($options['auth_type'], ['auto', 'login', 'plain', 'cram-md5'], true)
            && self::runtime_password($options) !== null;

    }

    private static function active_options(){

        $options = self::get_options();

        return self::configuration_is_complete($options) ? $options : null;

    }

    private static function wordpress_default_from_email(){

        if(!function_exists('network_home_url')){
            return '';
        }

        $home_url = network_home_url();
        $host = function_exists('wp_parse_url')
            ? wp_parse_url($home_url, PHP_URL_HOST)
            : parse_url($home_url, PHP_URL_HOST);

        if(!is_string($host) || $host === ''){
            return '';
        }

        if(strpos($host, 'www.') === 0){
            $host = substr($host, 4);
        }

        $email = 'wordpress@' . $host;

        return self::email_is_valid($email) ? strtolower($email) : '';

    }

    public static function filter_from_email_fallback($email){

        $options = self::active_options();

        if($options === null){
            return $email;
        }

        $default_email = self::wordpress_default_from_email();
        $incoming = is_string($email) ? strtolower(trim($email)) : '';

        return !self::email_is_valid($incoming)
            || ($default_email !== '' && $incoming === $default_email)
                ? $options['from_email']
                : $email;

    }

    public static function filter_from_name_fallback($name){

        $options = self::active_options();

        if($options === null){
            return $name;
        }

        return !is_string($name) || trim($name) === '' || trim($name) === 'WordPress'
            ? $options['from_name']
            : $name;

    }

    public static function filter_from_email_force($email){

        $options = self::active_options();

        return $options !== null && $options['force_from_email']
            ? $options['from_email']
            : $email;

    }

    public static function filter_from_name_force($name){

        $options = self::active_options();

        return $options !== null && $options['force_from_name']
            ? $options['from_name']
            : $name;

    }

    private static function phpmailer_auth_type($auth_type){

        $types = [
            'auto' => '',
            'login' => 'LOGIN',
            'plain' => 'PLAIN',
            'cram-md5' => 'CRAM-MD5',
        ];

        return isset($types[$auth_type]) ? $types[$auth_type] : '';

    }

    public static function configure_phpmailer($phpmailer){

        $options = self::active_options();

        if($options === null || !is_object($phpmailer) || !method_exists($phpmailer, 'isSMTP')){
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->SMTPDebug = 0;
        $phpmailer->SMTPKeepAlive = false;
        $phpmailer->Timeout = $options['timeout'];
        $phpmailer->Host = $options['host'];
        $phpmailer->Port = $options['port'];
        $phpmailer->SMTPAuth = $options['authenticate'];
        $phpmailer->AuthType = $options['authenticate']
            ? self::phpmailer_auth_type($options['auth_type'])
            : '';
        $phpmailer->Username = $options['authenticate'] ? $options['username'] : '';
        $phpmailer->Password = $options['authenticate'] ? self::runtime_password($options) : '';
        $phpmailer->SMTPSecure = $options['encryption'] === 'none'
            ? ''
            : $options['encryption'];
        $phpmailer->SMTPAutoTLS = false;

        $current_from = isset($phpmailer->From) && self::email_is_valid($phpmailer->From)
            ? $phpmailer->From
            : $options['from_email'];
        $current_name = isset($phpmailer->FromName)
            && is_string($phpmailer->FromName)
            && $phpmailer->FromName !== ''
                ? $phpmailer->FromName
                : $options['from_name'];

        if($options['force_from_email']){
            $current_from = $options['from_email'];
        }

        if($options['force_from_name']){
            $current_name = $options['from_name'];
        }

        if(method_exists($phpmailer, 'setFrom')){
            try{
                $phpmailer->setFrom($current_from, $current_name, false);
            }catch(Throwable $error){
                return;
            }
        }

        if($options['return_path']){
            $phpmailer->Sender = $options['from_email'];
        }

    }

    private static function recipient_count($mail_data){

        if(!is_array($mail_data) || !array_key_exists('to', $mail_data)){
            return 0;
        }

        if(is_array($mail_data['to'])){
            return min(1000, count($mail_data['to']));
        }

        return is_string($mail_data['to']) && trim($mail_data['to']) !== '' ? 1 : 0;

    }

    private static function persist_last_status($status, $code, $recipient_count){

        $record = [
            'status' => $status === 'success' ? 'success' : 'error',
            'timestamp' => gmdate('c'),
            'code' => $code === 'sent' ? 'sent' : 'mail_failed',
            'recipientCount' => max(0, min(1000, (int)$recipient_count)),
        ];

        update_option(self::LAST_STATUS_OPTION, $record, false);

    }

    public static function record_mail_success($mail_data){

        if(self::active_options() === null){
            return;
        }

        self::persist_last_status('success', 'sent', self::recipient_count($mail_data));

    }

    public static function record_mail_failure($error){

        if(self::active_options() === null){
            return;
        }

        $recipient_count = 0;

        if(is_object($error) && method_exists($error, 'get_error_data')){
            $data = $error->get_error_data();
            $recipient_count = self::recipient_count(is_array($data) ? $data : []);
        }

        self::persist_last_status('error', 'mail_failed', $recipient_count);

    }

    public static function get_last_status(){

        $record = get_option(self::LAST_STATUS_OPTION, []);

        if(!is_array($record)){
            return [];
        }

        $status = isset($record['status']) && in_array($record['status'], ['success', 'error'], true)
            ? $record['status']
            : '';
        $code = isset($record['code']) && in_array($record['code'], ['sent', 'mail_failed'], true)
            ? $record['code']
            : '';
        $timestamp = isset($record['timestamp']) && is_string($record['timestamp'])
            && strlen($record['timestamp']) <= 32
            && preg_match('/\A[0-9T:+-]+\z/', $record['timestamp']) === 1
                ? $record['timestamp']
                : '';
        $recipient_count = isset($record['recipientCount']) && is_numeric($record['recipientCount'])
            ? max(0, min(1000, (int)$record['recipientCount']))
            : 0;

        if($status === '' || $code === '' || $timestamp === ''){
            return [];
        }

        return [
            'status' => $status,
            'timestamp' => $timestamp,
            'code' => $code,
            'recipientCount' => $recipient_count,
        ];

    }

    private static function test_rate_key(){

        return 'universal_smtp_test_rate_' . max(0, (int)get_current_user_id());

    }

    private static function test_rate_lock_key(){

        return self::test_rate_key() . '_lock';

    }

    private static function test_rate_lock_token(){

        try{
            return bin2hex(random_bytes(16));
        }catch(Throwable $error){
            return hash('sha256', uniqid('universal-smtp-', true) . '|' . microtime(true));
        }

    }

    private static function compare_exchange_test_rate_lock($expected, $replacement){

        global $wpdb;

        if(
            !is_object($wpdb)
            || !isset($wpdb->options)
            || !is_string($wpdb->options)
            || !method_exists($wpdb, 'prepare')
            || !method_exists($wpdb, 'query')
            || !function_exists('maybe_serialize')
        ){
            return false;
        }

        $key = self::test_rate_lock_key();
        $query = $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            maybe_serialize($replacement),
            $key,
            maybe_serialize($expected)
        );

        if(!is_string($query) || $wpdb->query($query) !== 1){
            return false;
        }

        if(function_exists('wp_cache_delete')){
            wp_cache_delete($key, 'options');
        }

        return true;

    }

    private static function acquire_test_rate_lock(){

        $key = self::test_rate_lock_key();
        $now = time();
        $token = self::test_rate_lock_token();
        $record = [
            'token' => $token,
            'expires' => $now + self::RATE_LIMIT_LOCK_SECONDS,
        ];

        if(add_option($key, $record, '', false)){
            return $token;
        }

        $existing = get_option($key, []);
        $expired = !is_array($existing)
            || !isset($existing['expires'])
            || !is_numeric($existing['expires'])
            || (int)$existing['expires'] <= $now;

        if(!$expired){
            return null;
        }

        return self::compare_exchange_test_rate_lock($existing, $record)
            ? $token
            : null;

    }

    private static function release_test_rate_lock($token){

        $key = self::test_rate_lock_key();
        $record = get_option($key, []);

        if(
            is_array($record)
            && isset($record['token'])
            && is_string($record['token'])
            && hash_equals($record['token'], $token)
        ){
            self::compare_exchange_test_rate_lock($record, [
                'token' => '',
                'expires' => 0,
            ]);
        }

    }

    private static function consume_test_rate_limit(){

        // add_option() creates the first lock atomically; expired takeover and
        // release use a prepared compare-and-swap against the complete stored
        // value. Multi-node installs must keep every node on the same database.
        $lock_token = self::acquire_test_rate_lock();

        if(!is_string($lock_token)){
            return false;
        }

        try{
            $now = time();
            $rate_key = self::test_rate_key();
            $attempts = get_option($rate_key, []);
            $attempts = is_array($attempts) ? array_slice($attempts, -self::RATE_LIMIT_ATTEMPTS) : [];
            $attempts = array_values(array_filter($attempts, function($attempt) use ($now){
                return is_int($attempt) && $attempt > ($now - self::RATE_LIMIT_WINDOW) && $attempt <= $now;
            }));

            if(count($attempts) >= self::RATE_LIMIT_ATTEMPTS){
                return false;
            }

            $attempts[] = $now;
            $exists = get_option($rate_key, null) !== null;
            $written = $exists
                ? update_option($rate_key, $attempts, false)
                : add_option($rate_key, $attempts, '', false);

            if(!$written && get_option($rate_key, []) !== $attempts){
                return false;
            }

            return true;
        }finally{
            self::release_test_rate_lock($lock_token);
        }

    }

    private static function test_email_result($recipient){

        if(!is_string($recipient) || strlen($recipient) > self::MAX_EMAIL_BYTES){
            return [
                false,
                422,
                __('Enter a valid recipient email address.', 'universal-smtp'),
                [self::error_item('recipient', 'invalid_recipient', __('Enter a valid recipient email address.', 'universal-smtp'))],
            ];
        }

        $recipient = trim($recipient);

        if(!self::email_is_valid($recipient)){
            return [
                false,
                422,
                __('Enter a valid recipient email address.', 'universal-smtp'),
                [self::error_item('recipient', 'invalid_recipient', __('Enter a valid recipient email address.', 'universal-smtp'))],
            ];
        }

        if(!self::configuration_is_complete()){
            return [
                false,
                409,
                __('Enable SMTP and complete the required settings before sending a test email.', 'universal-smtp'),
                [],
            ];
        }

        if(!self::consume_test_rate_limit()){
            return [
                false,
                429,
                __('Too many test emails were requested. Try again later.', 'universal-smtp'),
                [],
            ];
        }

        $sent = wp_mail(
            $recipient,
            __('SMTP configuration test', 'universal-smtp'),
            __('This message confirms that WordPress reached the configured SMTP mail flow.', 'universal-smtp'),
            ['Content-Type: text/plain; charset=UTF-8']
        );

        if(!$sent){
            return [
                false,
                502,
                __('The test email could not be sent. Verify the settings and try again.', 'universal-smtp'),
                [],
            ];
        }

        return [
            true,
            200,
            __('Test email sent. Confirm that it reached the intended mailbox.', 'universal-smtp'),
            [],
        ];

    }

    public static function ajax_send_test(){

        if(!current_user_can('manage_options')){
            self::unauthorized_ajax_response();
            return;
        }

        if(!check_ajax_referer(self::AJAX_TEST_ACTION, 'nonce', false)){
            wp_send_json_error([
                'message' => __('Your session has expired. Reload the page and try again.', 'universal-smtp'),
                'fieldErrors' => [],
                'errors' => [],
            ], 403);
            return;
        }

        $recipient = array_key_exists('recipient', $_POST)
            ? wp_unslash($_POST['recipient'])
            : null;
        list($success, $status, $message, $errors) = self::test_email_result($recipient);
        $payload = self::errors_payload($errors);
        $payload['message'] = $message;
        $payload['lastStatus'] = self::get_last_status();

        if($success){
            wp_send_json_success($payload, $status);
            return;
        }

        wp_send_json_error($payload, $status);

    }

    public static function admin_post_send_test(){

        if(!current_user_can('manage_options')){
            wp_die(esc_html__('You do not have permission to send a test email.', 'universal-smtp'));
        }

        check_admin_referer(self::AJAX_TEST_ACTION, 'usmtp_test_nonce');

        $recipient = array_key_exists('recipient', $_POST)
            ? wp_unslash($_POST['recipient'])
            : null;
        list($success, $status) = self::test_email_result($recipient);
        $result = $success ? 'sent' : ($status === 429 ? 'rate' : ($status === 422 ? 'invalid' : 'failed'));
        $url = add_query_arg(
            ['page' => self::SETTINGS_SLUG, 'usmtp_test' => $result],
            admin_url('options-general.php')
        );

        wp_safe_redirect($url);
        exit;

    }

    public static function register_settings_page(){

        self::$settings_page_hook = add_options_page(
            __('SMTP', 'universal-smtp'),
            __('SMTP', 'universal-smtp'),
            'manage_options',
            self::SETTINGS_SLUG,
            [__CLASS__, 'render_settings_page']
        );

    }

    public static function enqueue_admin_assets($hook_suffix){

        if(!is_string($hook_suffix) || $hook_suffix !== self::$settings_page_hook){
            return;
        }

        $stylesheet = __DIR__ . '/assets/css/admin-smtp.css';
        $script = __DIR__ . '/assets/js/admin-smtp.js';
        $style_version = is_readable($stylesheet) ? (string)filemtime($stylesheet) : self::VERSION;
        $script_version = is_readable($script) ? (string)filemtime($script) : self::VERSION;

        wp_enqueue_style(
            self::ADMIN_STYLE_HANDLE,
            plugins_url('assets/css/admin-smtp.css', __FILE__),
            [],
            $style_version
        );
        wp_enqueue_script(
            self::ADMIN_SCRIPT_HANDLE,
            plugins_url('assets/js/admin-smtp.js', __FILE__),
            [],
            $script_version,
            true
        );
        wp_localize_script(self::ADMIN_SCRIPT_HANDLE, 'universalSmtpAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'optionName' => self::OPTION_NAME,
            'saveNonce' => wp_create_nonce(self::AJAX_SAVE_ACTION),
            'testNonce' => wp_create_nonce(self::AJAX_TEST_ACTION),
            'messages' => [
                'saving' => __('Saving…', 'universal-smtp'),
                'testing' => __('Sending…', 'universal-smtp'),
                'saved' => __('Settings saved.', 'universal-smtp'),
                'testSent' => __('Test email sent.', 'universal-smtp'),
                'genericError' => __('The request could not be completed. Try again.', 'universal-smtp'),
            ],
        ]);

    }

    private static function render_checkbox($key, $checked, $label, $description = ''){

        $id = 'usmtp-' . str_replace('_', '-', $key);

        ?>
        <div class="usmtp-admin__checkbox">
            <input
                id="<?php echo esc_attr($id); ?>"
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $key . ']'); ?>"
                value="1"
                <?php checked($checked); ?>
            >
            <label class="usmtp-admin__checkbox-label" for="<?php echo esc_attr($id); ?>">
                <span><?php echo esc_html($label); ?></span>
                <?php if($description !== '') : ?>
                    <small><?php echo esc_html($description); ?></small>
                <?php endif; ?>
            </label>
        </div>
        <?php

    }

    private static function render_no_js_test_notice(){

        if(!isset($_GET['usmtp_test']) || !is_string($_GET['usmtp_test'])){
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['usmtp_test']));
        $messages = [
            'sent' => ['success', __('Test email sent. Confirm that it reached the intended mailbox.', 'universal-smtp')],
            'rate' => ['error', __('Too many test emails were requested. Try again later.', 'universal-smtp')],
            'invalid' => ['error', __('Enter a valid recipient email address.', 'universal-smtp')],
            'failed' => ['error', __('The test email could not be sent. Verify the settings and try again.', 'universal-smtp')],
        ];

        if(!isset($messages[$status])){
            return;
        }

        ?>
        <div class="notice notice-<?php echo esc_attr($messages[$status][0]); ?> is-dismissible">
            <p><?php echo esc_html($messages[$status][1]); ?></p>
        </div>
        <?php

    }

    public static function render_settings_page(){

        if(!current_user_can('manage_options')){
            wp_die(esc_html__('You do not have permission to manage these settings.', 'universal-smtp'));
        }

        $options = self::get_options(true);
        $password_state = self::password_state($options);
        $last_status = self::get_last_status();
        $test_email = function_exists('wp_get_current_user')
            ? (string)wp_get_current_user()->user_email
            : '';
        $test_email = self::email_is_valid($test_email) ? $test_email : '';

        ?>
        <div class="wrap usmtp-admin">
            <header class="usmtp-admin__hero">
                <div class="usmtp-admin__hero-copy">
                    <p class="usmtp-admin__context"><?php esc_html_e('Universal SMTP', 'universal-smtp'); ?></p>
                    <h1><?php esc_html_e('SMTP delivery', 'universal-smtp'); ?></h1>
                    <p class="usmtp-admin__lead"><?php esc_html_e('Connect WordPress to the mail server that should deliver its messages, while keeping credentials and delivery details out of the browser.', 'universal-smtp'); ?></p>
                </div>
            </header>

            <?php settings_errors(self::OPTION_NAME); ?>
            <?php self::render_no_js_test_notice(); ?>

            <div class="usmtp-admin__notice" data-usmtp-notice role="status" aria-live="polite" hidden>
                <p data-usmtp-notice-message></p>
                <button type="button" data-usmtp-notice-dismiss aria-label="<?php echo esc_attr(__('Dismiss this notice', 'universal-smtp')); ?>">&times;</button>
            </div>

            <div class="usmtp-admin__settings" data-usmtp-section-switcher>
                <div class="usmtp-admin__section-navigation" data-usmtp-section-navigation>
                    <label for="usmtp-settings-section"><?php esc_html_e('Settings section', 'universal-smtp'); ?></label>
                    <select id="usmtp-settings-section" data-usmtp-section-select>
                        <option value="usmtp-settings-panel-connection"><?php esc_html_e('Connection', 'universal-smtp'); ?></option>
                        <option value="usmtp-settings-panel-authentication"><?php esc_html_e('Authentication', 'universal-smtp'); ?></option>
                        <option value="usmtp-settings-panel-sender"><?php esc_html_e('Sender identity', 'universal-smtp'); ?></option>
                        <option value="usmtp-settings-panel-test"><?php esc_html_e('Test delivery', 'universal-smtp'); ?></option>
                    </select>
                </div>

                <form id="usmtp-settings-form" class="usmtp-admin__form" method="post" action="<?php echo esc_url(admin_url('options.php')); ?>" data-usmtp-settings-form>
                    <?php settings_fields(self::SETTINGS_GROUP); ?>
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME . '[_present]'); ?>" value="1">

                <section id="usmtp-settings-panel-connection" class="usmtp-admin-section" aria-labelledby="usmtp-connection-title" data-usmtp-section-panel>
                    <header class="usmtp-admin-section__header">
                        <h2 id="usmtp-connection-title"><?php esc_html_e('Connection', 'universal-smtp'); ?></h2>
                        <p><?php esc_html_e('Choose the SMTP server, transport security, and maximum time WordPress may wait for the connection.', 'universal-smtp'); ?></p>
                    </header>
                    <div class="usmtp-admin-section__body">
                        <?php self::render_checkbox(
                            'enabled',
                            $options['enabled'],
                            __('Enable SMTP delivery', 'universal-smtp'),
                            __('WordPress continues to use its normal mail flow while PHPMailer sends through this server.', 'universal-smtp')
                        ); ?>
                        <div class="usmtp-admin__field-grid">
                            <div class="usmtp-admin__field usmtp-admin__field--wide">
                                <label for="usmtp-host"><?php esc_html_e('SMTP host', 'universal-smtp'); ?></label>
                                <input id="usmtp-host" class="regular-text code" type="text" maxlength="253" autocomplete="off" spellcheck="false" name="<?php echo esc_attr(self::OPTION_NAME . '[host]'); ?>" value="<?php echo esc_attr($options['host']); ?>" aria-describedby="usmtp-host-description">
                                <p id="usmtp-host-description" class="description"><?php esc_html_e('Enter a hostname or IP address only, such as smtp.example.com. Do not include https://, a path, or credentials.', 'universal-smtp'); ?></p>
                            </div>
                            <div class="usmtp-admin__field">
                                <label for="usmtp-port"><?php esc_html_e('Port', 'universal-smtp'); ?></label>
                                <input id="usmtp-port" class="small-text" type="number" min="1" max="65535" step="1" inputmode="numeric" name="<?php echo esc_attr(self::OPTION_NAME . '[port]'); ?>" value="<?php echo esc_attr((string)$options['port']); ?>">
                            </div>
                            <div class="usmtp-admin__field">
                                <label for="usmtp-encryption"><?php esc_html_e('Encryption', 'universal-smtp'); ?></label>
                                <select id="usmtp-encryption" name="<?php echo esc_attr(self::OPTION_NAME . '[encryption]'); ?>" aria-describedby="usmtp-encryption-description">
                                    <option value="tls" <?php selected($options['encryption'], 'tls'); ?>><?php esc_html_e('STARTTLS (recommended)', 'universal-smtp'); ?></option>
                                    <option value="ssl" <?php selected($options['encryption'], 'ssl'); ?>><?php esc_html_e('Implicit TLS', 'universal-smtp'); ?></option>
                                    <option value="none" <?php selected($options['encryption'], 'none'); ?>><?php esc_html_e('None', 'universal-smtp'); ?></option>
                                </select>
                                <p id="usmtp-encryption-description" class="description"><?php esc_html_e('Use the exact mode required by the provider. No encryption sends mail-server credentials and content without transport protection.', 'universal-smtp'); ?></p>
                            </div>
                            <div class="usmtp-admin__field">
                                <label for="usmtp-timeout"><?php esc_html_e('Connection timeout', 'universal-smtp'); ?></label>
                                <div class="usmtp-admin__input-suffix">
                                    <input id="usmtp-timeout" class="small-text" type="number" min="5" max="60" step="1" inputmode="numeric" name="<?php echo esc_attr(self::OPTION_NAME . '[timeout]'); ?>" value="<?php echo esc_attr((string)$options['timeout']); ?>">
                                    <span><?php esc_html_e('seconds', 'universal-smtp'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="usmtp-settings-panel-authentication" class="usmtp-admin-section usmtp-admin-section--initially-hidden" aria-labelledby="usmtp-authentication-title" data-usmtp-section-panel>
                    <header class="usmtp-admin-section__header">
                        <h2 id="usmtp-authentication-title"><?php esc_html_e('Authentication', 'universal-smtp'); ?></h2>
                        <p><?php esc_html_e('Provide credentials only when the SMTP server requires them. The stored password is encrypted and never returned to this page.', 'universal-smtp'); ?></p>
                    </header>
                    <div class="usmtp-admin-section__body">
                        <?php self::render_checkbox(
                            'authenticate',
                            $options['authenticate'],
                            __('Authenticate with the SMTP server', 'universal-smtp')
                        ); ?>
                        <div class="usmtp-admin__dependent" data-usmtp-auth-fields>
                            <div class="usmtp-admin__field-grid">
                                <div class="usmtp-admin__field">
                                    <label for="usmtp-auth-type"><?php esc_html_e('Authentication type', 'universal-smtp'); ?></label>
                                    <select id="usmtp-auth-type" name="<?php echo esc_attr(self::OPTION_NAME . '[auth_type]'); ?>">
                                        <option value="auto" <?php selected($options['auth_type'], 'auto'); ?>><?php esc_html_e('Automatic', 'universal-smtp'); ?></option>
                                        <option value="login" <?php selected($options['auth_type'], 'login'); ?>>LOGIN</option>
                                        <option value="plain" <?php selected($options['auth_type'], 'plain'); ?>>PLAIN</option>
                                        <option value="cram-md5" <?php selected($options['auth_type'], 'cram-md5'); ?>>CRAM-MD5</option>
                                    </select>
                                </div>
                                <div class="usmtp-admin__field">
                                    <label for="usmtp-username"><?php esc_html_e('Username', 'universal-smtp'); ?></label>
                                    <input id="usmtp-username" class="regular-text" type="text" maxlength="320" autocomplete="username" spellcheck="false" name="<?php echo esc_attr(self::OPTION_NAME . '[username]'); ?>" value="<?php echo esc_attr($options['username']); ?>">
                                </div>
                                <div class="usmtp-admin__field usmtp-admin__field--wide">
                                    <label for="usmtp-password"><?php esc_html_e('Password', 'universal-smtp'); ?></label>
                                    <?php if($password_state['managed']) : ?>
                                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_NAME . '[password]'); ?>" value="">
                                    <?php endif; ?>
                                    <input
                                        id="usmtp-password"
                                        class="regular-text"
                                        type="password"
                                        maxlength="1024"
                                        autocomplete="new-password"
                                        name="<?php echo esc_attr(self::OPTION_NAME . '[password]'); ?>"
                                        value=""
                                        aria-describedby="usmtp-password-description usmtp-password-status"
                                        <?php if($password_state['managed']) : ?>disabled aria-disabled="true"<?php endif; ?>
                                    >
                                    <p id="usmtp-password-description" class="description"><?php esc_html_e('Leave this field empty to preserve the current password. Its value is never displayed.', 'universal-smtp'); ?></p>
                                    <?php $password_status = $password_state['managed'] ? 'managed' : ($password_state['configured'] ? 'configured' : 'empty'); ?>
                                    <p id="usmtp-password-status" class="usmtp-admin__password-status" data-usmtp-password-status data-status="<?php echo esc_attr($password_status); ?>">
                                        <span data-usmtp-password-state="configured" <?php if($password_status !== 'configured') : ?>hidden<?php endif; ?>><?php esc_html_e('A stored password is configured.', 'universal-smtp'); ?></span>
                                        <span data-usmtp-password-state="empty" <?php if($password_status !== 'empty') : ?>hidden<?php endif; ?>><?php esc_html_e('No password is configured.', 'universal-smtp'); ?></span>
                                        <span data-usmtp-password-state="managed" <?php if($password_status !== 'managed') : ?>hidden<?php endif; ?>><?php esc_html_e('The password is managed by the UNIVERSAL_SMTP_PASSWORD constant.', 'universal-smtp'); ?></span>
                                    </p>
                                    <?php if(!$password_state['managed']) : ?>
                                        <div class="usmtp-admin__password-options">
                                            <?php self::render_checkbox(
                                                'clear_password',
                                                false,
                                                __('Clear the stored password when saving', 'universal-smtp')
                                            ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="usmtp-settings-panel-sender" class="usmtp-admin-section usmtp-admin-section--initially-hidden" aria-labelledby="usmtp-sender-title" data-usmtp-section-panel>
                    <header class="usmtp-admin-section__header">
                        <h2 id="usmtp-sender-title"><?php esc_html_e('Sender identity', 'universal-smtp'); ?></h2>
                        <p><?php esc_html_e('Set the default address and name shown as the sender, then decide whether other WordPress code may replace them.', 'universal-smtp'); ?></p>
                    </header>
                    <div class="usmtp-admin-section__body">
                        <div class="usmtp-admin__field-grid">
                            <div class="usmtp-admin__field">
                                <label for="usmtp-from-email"><?php esc_html_e('From email', 'universal-smtp'); ?></label>
                                <input id="usmtp-from-email" class="regular-text" type="email" maxlength="254" autocomplete="email" name="<?php echo esc_attr(self::OPTION_NAME . '[from_email]'); ?>" value="<?php echo esc_attr($options['from_email']); ?>">
                            </div>
                            <div class="usmtp-admin__field">
                                <label for="usmtp-from-name"><?php esc_html_e('From name', 'universal-smtp'); ?></label>
                                <input id="usmtp-from-name" class="regular-text" type="text" maxlength="120" autocomplete="organization" name="<?php echo esc_attr(self::OPTION_NAME . '[from_name]'); ?>" value="<?php echo esc_attr($options['from_name']); ?>">
                            </div>
                        </div>
                        <div class="usmtp-admin__checkbox-list">
                            <?php self::render_checkbox(
                                'force_from_email',
                                $options['force_from_email'],
                                __('Force the From email address', 'universal-smtp'),
                                __('Prevent themes and other plugins from replacing the configured address.', 'universal-smtp')
                            ); ?>
                            <?php self::render_checkbox(
                                'force_from_name',
                                $options['force_from_name'],
                                __('Force the From name', 'universal-smtp'),
                                __('Prevent themes and other plugins from replacing the configured name.', 'universal-smtp')
                            ); ?>
                            <?php self::render_checkbox(
                                'return_path',
                                $options['return_path'],
                                __('Use the From email as the Return-Path', 'universal-smtp'),
                                __('Delivery failure notices may be returned to this address when the provider supports it.', 'universal-smtp')
                            ); ?>
                        </div>
                    </div>
                </section>

                </form>

            <section id="usmtp-settings-panel-test" class="usmtp-admin-section usmtp-admin-section--initially-hidden" aria-labelledby="usmtp-test-title" data-usmtp-section-panel>
                <header class="usmtp-admin-section__header">
                    <h2 id="usmtp-test-title"><?php esc_html_e('Test delivery', 'universal-smtp'); ?></h2>
                    <p><?php esc_html_e('Send one plain-text message through the saved configuration. This confirms the WordPress mail flow, not inbox placement.', 'universal-smtp'); ?></p>
                </header>
                <div class="usmtp-admin-section__body">
                    <?php if(!empty($last_status)) : ?>
                        <div class="usmtp-admin__last-status">
                            <h3><?php esc_html_e('Most recent delivery status', 'universal-smtp'); ?></h3>
                            <p>
                                <strong><?php echo esc_html($last_status['status'] === 'success' ? __('Sent', 'universal-smtp') : __('Failed', 'universal-smtp')); ?></strong>
                                <span><?php echo esc_html($last_status['timestamp']); ?></span>
                                <?php $recipient_label = $last_status['recipientCount'] === 1
                                    ? __('%d recipient', 'universal-smtp')
                                    : __('%d recipients', 'universal-smtp'); ?>
                                <span><?php echo esc_html(sprintf($recipient_label, $last_status['recipientCount'])); ?></span>
                            </p>
                        </div>
                    <?php endif; ?>
                    <form class="usmtp-admin__test-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-usmtp-test-form>
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::AJAX_TEST_ACTION); ?>">
                        <?php wp_nonce_field(self::AJAX_TEST_ACTION, 'usmtp_test_nonce'); ?>
                        <div class="usmtp-admin__test-fields">
                            <div class="usmtp-admin__field usmtp-admin__test-field">
                                <label for="usmtp-test-recipient"><?php esc_html_e('Recipient email', 'universal-smtp'); ?></label>
                                <input id="usmtp-test-recipient" class="regular-text" type="email" maxlength="254" autocomplete="email" name="recipient" value="<?php echo esc_attr($test_email); ?>" required>
                            </div>
                            <button type="submit" class="button button-secondary" data-usmtp-test><?php esc_html_e('Send test email', 'universal-smtp'); ?></button>
                        </div>
                    </form>
                </div>
            </section>

                <footer class="usmtp-admin__actions">
                    <p><?php esc_html_e('You may move between sections before saving. All settings are saved together.', 'universal-smtp'); ?></p>
                    <?php submit_button(__('Save settings', 'universal-smtp'), 'primary', 'submit', false, ['data-usmtp-save' => '', 'form' => 'usmtp-settings-form']); ?>
                </footer>
            </div>

            <footer class="usmtp-admin__credit">
                <p>&copy; <a href="<?php echo esc_url('https://champgauche.studio'); ?>" target="_blank" rel="noopener noreferrer">Studio Champ Gauche</a></p>
            </footer>
        </div>
        <?php

    }

    public static function activate(){

        if(get_option(self::OPTION_NAME, null) === null){
            add_option(self::OPTION_NAME, self::default_options(), '', false);
        }

        if(get_option(self::LAST_STATUS_OPTION, null) === null){
            add_option(self::LAST_STATUS_OPTION, [], '', false);
        }

    }

    public static function deactivate(){

        // Settings intentionally remain available after deactivation.

    }

}

Universal_SMTP::boot();

register_activation_hook(__FILE__, ['Universal_SMTP', 'activate']);
register_deactivation_hook(__FILE__, ['Universal_SMTP', 'deactivate']);
