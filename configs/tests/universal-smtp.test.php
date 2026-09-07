<?php

define('ABSPATH', __DIR__);

$actions = [];
$filters = [];
$activation_hooks = [];
$deactivation_hooks = [];
$registered_settings = [];
$settings_pages = [];
$settings_errors = [];
$options = [];
$option_autoload = [];
$enqueued_styles = [];
$enqueued_scripts = [];
$localized_scripts = [];
$can_manage_options = true;
$nonce_valid = true;
$nonce_checks = [];
$current_user_id = 7;
$mail_result = true;
$mail_calls = [];
$redirect_url = '';
$update_failures = [];
$wpdb_cas_before_query = null;
$wpdb_queries = [];

class Universal_SMTP_Test_JSON_Response extends RuntimeException{
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

class WP_Error{
    private $data;

    public function __construct($code = '', $message = '', $data = null){
        $this->data = $data;
    }

    public function get_error_data(){
        return $this->data;
    }
}

class Universal_SMTP_Test_WPDB{
    public $options = 'wp_options';
    private $prepared = [];

    public function prepare($query, ...$args){
        $id = count($this->prepared);
        $this->prepared[$id] = compact('query', 'args');
        return 'smtp-prepared-query-' . $id;
    }

    public function query($prepared_query){
        global $options, $wpdb_cas_before_query, $wpdb_queries;

        if(preg_match('/\Asmtp-prepared-query-([0-9]+)\z/', $prepared_query, $matches) !== 1){
            return false;
        }

        $prepared = $this->prepared[(int)$matches[1]];
        $wpdb_queries[] = $prepared;

        if(is_callable($wpdb_cas_before_query)){
            $callback = $wpdb_cas_before_query;
            $wpdb_cas_before_query = null;
            $callback();
        }

        $replacement = $prepared['args'][0];
        $key = $prepared['args'][1];
        $expected = $prepared['args'][2];
        $current = array_key_exists($key, $options) ? maybe_serialize($options[$key]) : null;

        if($current !== $expected){
            return 0;
        }

        $options[$key] = maybe_unserialize($replacement);
        return 1;
    }
}

$wpdb = new Universal_SMTP_Test_WPDB();

class Universal_SMTP_Test_Mailer{
    public $smtp = false;
    public $SMTPDebug = 9;
    public $SMTPKeepAlive = true;
    public $Timeout = 0;
    public $Host = '';
    public $Port = 0;
    public $SMTPAuth = false;
    public $AuthType = '';
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $From = '';
    public $FromName = '';
    public $Sender = '';

    public function isSMTP(){
        $this->smtp = true;
    }

    public function setFrom($email, $name = '', $auto = true){
        $this->From = $email;
        $this->FromName = $name;
        return true;
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

function do_action($hook, ...$args){
    global $actions;

    if(empty($actions[$hook])){
        return;
    }

    usort($actions[$hook], function($first, $second){
        return $first[1] <=> $second[1];
    });

    foreach($actions[$hook] as $registration){
        call_user_func_array($registration[0], array_slice($args, 0, $registration[2]));
    }
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
        $arguments = array_slice(array_merge([$value], $args), 0, $registration[2]);
        $value = call_user_func_array($registration[0], $arguments);
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

function esc_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_attr($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_url($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function esc_html__($value){
    return esc_html($value);
}

function esc_html_e($value){
    echo esc_html($value);
}

function sanitize_key($value){
    return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string)$value));
}

function wp_strip_all_tags($value){
    return strip_tags((string)$value);
}

function maybe_serialize($value){
    return is_array($value) || is_object($value) ? serialize($value) : $value;
}

function maybe_unserialize($value){
    if(!is_string($value)){
        return $value;
    }

    $result = @unserialize($value);
    return $result === false && $value !== 'b:0;' ? $value : $result;
}

function wp_cache_delete(){
    return true;
}

function wp_unslash($value){
    if(is_array($value)){
        return array_map('wp_unslash', $value);
    }

    return is_string($value) ? stripslashes($value) : $value;
}

function wp_salt($scheme = 'auth'){
    return 'test-' . $scheme . '-salt-that-is-long-and-never-secret-fixture';
}

function is_email($value){
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : false;
}

function get_option($name, $default = false){
    global $options;
    return array_key_exists($name, $options) ? $options[$name] : $default;
}

function add_option($name, $value, $deprecated = '', $autoload = null){
    global $options, $option_autoload;

    if(array_key_exists($name, $options)){
        return false;
    }

    $options[$name] = $value;
    $option_autoload[$name] = $autoload;
    return true;
}

function update_option($name, $value, $autoload = null){
    global $options, $option_autoload, $registered_settings, $update_failures;

    if(!empty($update_failures[$name])){
        return false;
    }

    $callback = isset($registered_settings[$name]['args']['sanitize_callback'])
        ? $registered_settings[$name]['args']['sanitize_callback']
        : null;

    if(is_callable($callback)){
        $value = call_user_func($callback, $value);
    }

    $changed = !array_key_exists($name, $options) || $options[$name] !== $value;
    $options[$name] = $value;

    if($autoload !== null){
        $option_autoload[$name] = $autoload;
    }

    return $changed;
}

function delete_option($name){
    global $options, $option_autoload;

    if(!array_key_exists($name, $options)){
        return false;
    }

    unset($options[$name], $option_autoload[$name]);
    return true;
}

function register_setting($group, $name, $args = []){
    global $registered_settings;
    $registered_settings[$name] = compact('group', 'args');
}

function add_settings_error($setting, $code, $message, $type = 'error'){
    global $settings_errors;
    $settings_errors[] = compact('setting', 'code', 'message', 'type');
}

function get_settings_errors($setting = ''){
    global $settings_errors;

    return array_values(array_filter($settings_errors, function($error) use ($setting){
        return $setting === '' || $error['setting'] === $setting;
    }));
}

function settings_errors($setting = ''){
    echo '<!-- settings-errors:' . esc_attr($setting) . ' -->';
}

function add_options_page($page_title, $menu_title, $capability, $slug, $callback){
    global $settings_pages;
    $settings_pages[$slug] = compact('page_title', 'menu_title', 'capability', 'slug', 'callback');
    return 'settings_page_' . $slug;
}

function settings_fields($group){
    echo '<input type="hidden" name="option_page" value="' . esc_attr($group) . '">';
}

function checked($checked, $current = true, $echo = true){
    $value = (bool)$checked === (bool)$current ? ' checked="checked"' : '';
    if($echo){
        echo $value;
    }
    return $value;
}

function selected($selected, $current = true, $echo = true){
    $value = (string)$selected === (string)$current ? ' selected="selected"' : '';
    if($echo){
        echo $value;
    }
    return $value;
}

function submit_button($text, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = []){
    $attributes = '';
    foreach($other_attributes as $key => $value){
        $attributes .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    echo '<button type="submit" name="' . esc_attr($name) . '" class="button ' . esc_attr($type) . '"' . $attributes . '>' . esc_html($text) . '</button>';
}

function wp_nonce_field($action, $name = '_wpnonce'){
    echo '<input type="hidden" name="' . esc_attr($name) . '" value="nonce-' . esc_attr($action) . '">';
}

function wp_create_nonce($action){
    return 'nonce-' . $action;
}

function current_user_can($capability){
    global $can_manage_options;
    return $capability === 'manage_options' && $can_manage_options;
}

function get_current_user_id(){
    global $current_user_id;
    return $current_user_id;
}

function wp_get_current_user(){
    return (object)['user_email' => 'admin@example.com'];
}

function check_ajax_referer($action, $query_arg = false, $die = true){
    global $nonce_valid, $nonce_checks;
    $nonce_checks[] = compact('action', 'query_arg', 'die');
    return $nonce_valid;
}

function check_admin_referer(){
    return true;
}

function wp_send_json_success($value = null, $status_code = null, $flags = 0){
    throw new Universal_SMTP_Test_JSON_Response(true, $value, $status_code ?: 200);
}

function wp_send_json_error($value = null, $status_code = null, $flags = 0){
    throw new Universal_SMTP_Test_JSON_Response(false, $value, $status_code ?: 200);
}

function wp_die($message){
    throw new RuntimeException((string)$message);
}

function admin_url($path = ''){
    return 'https://example.test/wp-admin/' . ltrim($path, '/');
}

function network_home_url(){
    return 'https://www.example.test/';
}

function wp_parse_url($url, $component = -1){
    return parse_url($url, $component);
}

function plugins_url($path, $file = ''){
    return 'https://example.test/wp-content/plugins/universal-smtp/' . ltrim($path, '/');
}

function add_query_arg($args, $url){
    return $url . '?' . http_build_query($args);
}

function wp_safe_redirect($url){
    global $redirect_url;
    $redirect_url = $url;
    return true;
}

function wp_enqueue_style($handle, $source, $dependencies = [], $version = false){
    global $enqueued_styles;
    $enqueued_styles[$handle] = compact('source', 'dependencies', 'version');
}

function wp_enqueue_script($handle, $source, $dependencies = [], $version = false, $footer = false){
    global $enqueued_scripts;
    $enqueued_scripts[$handle] = compact('source', 'dependencies', 'version', 'footer');
}

function wp_localize_script($handle, $name, $data){
    global $localized_scripts;
    $localized_scripts[$handle][$name] = $data;
}

function wp_mail($to, $subject, $message, $headers = '', $attachments = []){
    global $mail_result, $mail_calls;
    $mail_calls[] = compact('to', 'subject', 'message', 'headers', 'attachments');

    if($mail_result){
        do_action('wp_mail_succeeded', compact('to', 'subject', 'message', 'headers', 'attachments'));
    }else{
        do_action('wp_mail_failed', new WP_Error(
            'phpmailer_exception_code-that-must-not-escape',
            'Raw provider failure with secret smtp-pass',
            compact('to', 'subject', 'message', 'headers', 'attachments')
        ));
    }

    return $mail_result;
}

function smtp_assert($condition, $message){
    if(!$condition){
        throw new RuntimeException($message);
    }
}

function smtp_assert_same($expected, $actual, $message){
    if($expected !== $actual){
        throw new RuntimeException(
            $message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true)
        );
    }
}

function smtp_valid_input($overrides = []){
    return array_merge([
        '_present' => '1',
        'enabled' => '1',
        'host' => 'SMTP.Example.com',
        'port' => '587',
        'encryption' => 'tls',
        'authenticate' => '1',
        'auth_type' => 'auto',
        'username' => ' sender@example.com ',
        'password' => ' p@ss word! ',
        'from_email' => 'mail@example.com',
        'from_name' => 'Example & Company',
        'force_from_email' => '1',
        'force_from_name' => '1',
        'return_path' => '1',
        'timeout' => '15',
    ], $overrides);
}

function smtp_set_options($value){
    global $options;
    $options[Universal_SMTP::OPTION_NAME] = $value;
    Universal_SMTP::get_options(true);
}

function smtp_json_call($callback){
    try{
        call_user_func($callback);
    }catch(Universal_SMTP_Test_JSON_Response $response){
        return $response;
    }

    throw new RuntimeException('Expected a JSON response.');
}

require dirname(__DIR__, 2) . '/src/plugins/universal-smtp/template/init.php';

smtp_assert(isset($actions['admin_init']), 'The Settings API registration hook is missing.');
smtp_assert(isset($actions['admin_menu']), 'The SMTP settings-page hook is missing.');
smtp_assert(isset($actions['wp_ajax_' . Universal_SMTP::AJAX_SAVE_ACTION]), 'The authenticated save action is missing.');
smtp_assert(isset($actions['wp_ajax_' . Universal_SMTP::AJAX_TEST_ACTION]), 'The authenticated test action is missing.');
smtp_assert(!isset($actions['wp_ajax_nopriv_' . Universal_SMTP::AJAX_SAVE_ACTION]), 'The save action must not have a public AJAX route.');
smtp_assert(!isset($actions['wp_ajax_nopriv_' . Universal_SMTP::AJAX_TEST_ACTION]), 'The test action must not have a public AJAX route.');
smtp_assert(isset($actions['phpmailer_init']), 'The PHPMailer hook is missing.');
smtp_assert(isset($actions['wp_mail_succeeded']) && isset($actions['wp_mail_failed']), 'Mail status hooks are incomplete.');
smtp_assert(count($activation_hooks) === 1 && count($deactivation_hooks) === 1, 'Plugin lifecycle hooks must be registered once.');

Universal_SMTP::activate();
smtp_assert(isset($options[Universal_SMTP::OPTION_NAME]), 'Activation must create the settings option.');
smtp_assert_same(false, $option_autoload[Universal_SMTP::OPTION_NAME], 'SMTP settings must not autoload.');
smtp_assert_same(false, $option_autoload[Universal_SMTP::LAST_STATUS_OPTION], 'The delivery status must not autoload.');

Universal_SMTP::register_settings();
Universal_SMTP::register_settings_page();
smtp_assert(isset($registered_settings[Universal_SMTP::OPTION_NAME]), 'The settings option was not registered.');
smtp_assert_same('array', $registered_settings[Universal_SMTP::OPTION_NAME]['args']['type'], 'The settings option must be registered as an array.');
smtp_assert(isset($settings_pages[Universal_SMTP::SETTINGS_SLUG]), 'Settings > SMTP was not registered.');
smtp_assert_same('manage_options', $settings_pages[Universal_SMTP::SETTINGS_SLUG]['capability'], 'Settings > SMTP requires manage_options.');

$saved = Universal_SMTP::sanitize_options(smtp_valid_input());
smtp_assert_same('smtp.example.com', $saved['host'], 'DNS hostnames should use a lower-case canonical value.');
smtp_assert_same('sender@example.com', $saved['username'], 'Only outer username whitespace should be trimmed.');
smtp_assert_same('Example & Company', $saved['from_name'], 'Plain Unicode sender names should preserve meaningful punctuation.');
smtp_assert($saved['password_ciphertext'] !== '' && $saved['password_ciphertext'] !== ' p@ss word! ', 'SMTP passwords must be encrypted before storage.');
smtp_assert(strpos($saved['password_ciphertext'], 'sodium:v1:') === 0 || strpos($saved['password_ciphertext'], 'openssl:v1:') === 0, 'Stored passwords need an authenticated versioned cipher envelope.');
smtp_assert(strpos(json_encode($saved), 'p@ss word') === false, 'Canonical option storage must not contain the plaintext SMTP password.');
smtp_set_options($saved);

$ipv4_saved = Universal_SMTP::sanitize_options(smtp_valid_input(['host' => '192.0.2.10', 'password' => '']));
smtp_assert_same('192.0.2.10', $ipv4_saved['host'], 'A valid IPv4 SMTP host should be accepted.');
$ipv6_saved = Universal_SMTP::sanitize_options(smtp_valid_input(['host' => '2001:db8::25', 'password' => '']));
smtp_assert_same('2001:db8::25', $ipv6_saved['host'], 'A valid IPv6 SMTP host should be accepted.');
smtp_set_options($saved);

$mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($mailer);
smtp_assert($mailer->smtp, 'A complete enabled configuration must select SMTP transport.');
smtp_assert_same(0, $mailer->SMTPDebug, 'SMTP debug output must remain disabled.');
smtp_assert_same(false, $mailer->SMTPKeepAlive, 'SMTP connections must not be kept alive.');
smtp_assert_same('smtp.example.com', $mailer->Host, 'SMTP host mapping is incorrect.');
smtp_assert_same(587, $mailer->Port, 'SMTP port mapping is incorrect.');
smtp_assert_same(true, $mailer->SMTPAuth, 'SMTP authentication mapping is incorrect.');
smtp_assert_same('', $mailer->AuthType, 'Automatic authentication should let PHPMailer negotiate.');
smtp_assert_same('sender@example.com', $mailer->Username, 'SMTP username mapping is incorrect.');
smtp_assert_same(' p@ss word! ', $mailer->Password, 'The SMTP password must round-trip exactly.');
smtp_assert_same('tls', $mailer->SMTPSecure, 'STARTTLS mapping is incorrect.');
smtp_assert_same(false, $mailer->SMTPAutoTLS, 'Opportunistic auto-TLS must be disabled so the selected mode is honored.');
smtp_assert_same('mail@example.com', $mailer->From, 'Configured From email mapping is incorrect.');
smtp_assert_same('Example & Company', $mailer->FromName, 'Configured From name mapping is incorrect.');
smtp_assert_same('mail@example.com', $mailer->Sender, 'Return-Path must use the configured From email.');

$original_saved = $saved;
$malformed_cases = [
    ['payload' => null, 'code' => 'invalid_request'],
    ['payload' => smtp_valid_input(['administrator' => '1']), 'code' => 'unknown_field'],
    ['payload' => smtp_valid_input(['password_ciphertext' => 'plaintext']), 'code' => 'unknown_field'],
    ['payload' => smtp_valid_input(['_present' => '0']), 'code' => 'invalid_request'],
    ['payload' => smtp_valid_input(['host' => ['smtp.example.com']]), 'code' => 'invalid_host'],
    ['payload' => smtp_valid_input(['host' => 'https://smtp.example.com']), 'code' => 'invalid_host'],
    ['payload' => smtp_valid_input(['host' => 'user@smtp.example.com']), 'code' => 'invalid_host'],
    ['payload' => smtp_valid_input(['host' => 'smtp.example.com/path']), 'code' => 'invalid_host'],
    ['payload' => smtp_valid_input(['host' => 'smtp.exämple.com']), 'code' => 'invalid_host'],
    ['payload' => smtp_valid_input(['port' => '0']), 'code' => 'invalid_port'],
    ['payload' => smtp_valid_input(['port' => '65536']), 'code' => 'invalid_port'],
    ['payload' => smtp_valid_input(['port' => '587.0']), 'code' => 'invalid_port'],
    ['payload' => smtp_valid_input(['encryption' => 'starttls']), 'code' => 'invalid_encryption'],
    ['payload' => smtp_valid_input(['auth_type' => 'xoauth2']), 'code' => 'invalid_auth_type'],
    ['payload' => smtp_valid_input(['authenticate' => ['1']]), 'code' => 'invalid_authenticate'],
    ['payload' => smtp_valid_input(['username' => "mail\nuser"]), 'code' => 'invalid_username'],
    ['payload' => smtp_valid_input(['username' => '<script>alert(1)</script>']), 'code' => 'invalid_username'],
    ['payload' => smtp_valid_input(['username' => str_repeat('u', 321)]), 'code' => 'invalid_username'],
    ['payload' => smtp_valid_input(['from_email' => 'not-an-email']), 'code' => 'invalid_from_email'],
    ['payload' => smtp_valid_input(['from_email' => 'mail@example.com"><script>alert(1)</script>']), 'code' => 'invalid_from_email'],
    ['payload' => smtp_valid_input(['from_email' => ['mail@example.com']]), 'code' => 'invalid_from_email'],
    ['payload' => smtp_valid_input(['from_email' => str_repeat('a', 243) . '@example.com']), 'code' => 'invalid_from_email'],
    ['payload' => smtp_valid_input(['from_name' => '<b>Example</b>']), 'code' => 'invalid_from_name'],
    ['payload' => smtp_valid_input(['from_name' => '<script>alert(1)</script>']), 'code' => 'invalid_from_name'],
    ['payload' => smtp_valid_input(['from_name' => str_repeat('a', 121)]), 'code' => 'invalid_from_name'],
    ['payload' => smtp_valid_input(['password' => "line\nbreak"]), 'code' => 'invalid_password'],
    ['payload' => smtp_valid_input(['password' => "null\0byte"]), 'code' => 'invalid_password'],
    ['payload' => smtp_valid_input(['password' => str_repeat('x', 1025)]), 'code' => 'invalid_password'],
    ['payload' => smtp_valid_input(['timeout' => '4']), 'code' => 'invalid_timeout'],
    ['payload' => smtp_valid_input(['timeout' => '61']), 'code' => 'invalid_timeout'],
    ['payload' => smtp_valid_input(['enabled' => ['1']]), 'code' => 'invalid_enabled'],
    ['payload' => smtp_valid_input(['force_from_email' => ['1']]), 'code' => 'invalid_force_from_email'],
];

foreach($malformed_cases as $case){
    $settings_errors = [];
    $result = Universal_SMTP::sanitize_options($case['payload']);
    $codes = array_column(Universal_SMTP::validation_errors(), 'code');
    smtp_assert(in_array($case['code'], $codes, true), 'Expected validation error ' . $case['code'] . '.');
    smtp_assert_same($original_saved, $result, 'Every invalid request must preserve the complete previous configuration.');
}

$missing_host = smtp_valid_input();
unset($missing_host['host']);
$missing_result = Universal_SMTP::sanitize_options($missing_host);
smtp_assert(in_array('missing_field', array_column(Universal_SMTP::validation_errors(), 'code'), true), 'A missing required transport field must be rejected.');
smtp_assert_same($original_saved, $missing_result, 'A missing field must preserve the complete previous configuration.');

$missing_required = smtp_valid_input([
    'host' => '',
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => '',
]);
$result = Universal_SMTP::sanitize_options($missing_required);
$codes = array_column(Universal_SMTP::validation_errors(), 'code');
foreach(['host_required', 'username_required', 'from_email_required', 'from_name_required'] as $code){
    smtp_assert(in_array($code, $codes, true), 'Enabled SMTP must reject incomplete settings: ' . $code . '.');
}
smtp_assert_same($original_saved, $result, 'Cross-field validation errors must be atomic.');

$without_password = $original_saved;
$without_password['password_ciphertext'] = '';
smtp_set_options($without_password);
$password_required = Universal_SMTP::sanitize_options(smtp_valid_input(['password' => '']));
smtp_assert(in_array('password_required', array_column(Universal_SMTP::validation_errors(), 'code'), true), 'Enabled authenticated SMTP must require a password when none is stored.');
smtp_assert_same($without_password, $password_required, 'A missing SMTP password must preserve the previous configuration atomically.');
smtp_set_options($original_saved);

$disabled = smtp_valid_input([
    'enabled' => '0',
    'host' => '',
    'authenticate' => '0',
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => '',
]);
$disabled_saved = Universal_SMTP::sanitize_options($disabled);
smtp_assert(empty(Universal_SMTP::validation_errors()), 'Disabled SMTP must permit an incomplete staged configuration.');
smtp_assert_same(false, $disabled_saved['enabled'], 'Disabled SMTP was not stored as disabled.');
smtp_set_options($disabled_saved);
$status_before_disabled_mail = get_option(Universal_SMTP::LAST_STATUS_OPTION);
Universal_SMTP::record_mail_success(['to' => ['not-recorded@example.com']]);
smtp_assert_same($status_before_disabled_mail, get_option(Universal_SMTP::LAST_STATUS_OPTION), 'Mail events must not be presented as SMTP delivery while SMTP is disabled or incomplete.');

smtp_set_options($original_saved);
$preserved = Universal_SMTP::sanitize_options(smtp_valid_input(['password' => '']));
smtp_assert_same($original_saved['password_ciphertext'], $preserved['password_ciphertext'], 'An empty password field must preserve the existing secret.');
$replaced = Universal_SMTP::sanitize_options(smtp_valid_input(['password' => 'new exact password']));
smtp_assert($replaced['password_ciphertext'] !== $original_saved['password_ciphertext'], 'A supplied password must replace the encrypted secret.');
smtp_set_options($replaced);
$replaced_mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($replaced_mailer);
smtp_assert_same('new exact password', $replaced_mailer->Password, 'A replacement password must decrypt exactly.');

$clear_input = smtp_valid_input([
    'enabled' => '0',
    'authenticate' => '0',
    'password' => '',
    'clear_password' => '1',
]);
$cleared = Universal_SMTP::sanitize_options($clear_input);
smtp_assert_same('', $cleared['password_ciphertext'], 'Explicit password clearing must remove the encrypted secret.');
$conflict = Universal_SMTP::sanitize_options(smtp_valid_input([
    'password' => 'replacement',
    'clear_password' => '1',
]));
smtp_assert(in_array('password_conflict', array_column(Universal_SMTP::validation_errors(), 'code'), true), 'Replace-and-clear password requests must be rejected.');
smtp_assert_same($replaced, $conflict, 'A password conflict must preserve the previous configuration.');

$tampered = $original_saved;
$last_character = substr($tampered['password_ciphertext'], -1);
$tampered['password_ciphertext'] = substr($tampered['password_ciphertext'], 0, -1) . ($last_character === 'A' ? 'B' : 'A');
smtp_assert_same(false, Universal_SMTP::configuration_is_complete($tampered), 'Tampered ciphertext must fail closed.');
smtp_set_options($tampered);
$tampered_mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($tampered_mailer);
smtp_assert_same(false, $tampered_mailer->smtp, 'A tampered password must prevent SMTP configuration.');

$plain = $original_saved;
$plain['encryption'] = 'none';
$plain['auth_type'] = 'cram-md5';
$plain['return_path'] = false;
smtp_set_options($plain);
$plain_mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($plain_mailer);
smtp_assert_same('', $plain_mailer->SMTPSecure, 'The none mode must disable PHPMailer transport encryption.');
smtp_assert_same('CRAM-MD5', $plain_mailer->AuthType, 'CRAM-MD5 authentication mapping is incorrect.');
smtp_assert_same('', $plain_mailer->Sender, 'Return-Path must remain untouched when disabled.');

$no_auth = $plain;
$no_auth['authenticate'] = false;
$no_auth['username'] = '';
$no_auth['password_ciphertext'] = '';
smtp_set_options($no_auth);
$no_auth_mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($no_auth_mailer);
smtp_assert_same(false, $no_auth_mailer->SMTPAuth, 'Authentication must be disabled explicitly.');
smtp_assert_same('', $no_auth_mailer->Username, 'Disabled authentication must not send a username.');
smtp_assert_same('', $no_auth_mailer->Password, 'Disabled authentication must not send a password.');

$not_forced = $original_saved;
$not_forced['force_from_email'] = false;
$not_forced['force_from_name'] = false;
smtp_set_options($not_forced);
smtp_assert_same('mail@example.com', Universal_SMTP::filter_from_email_fallback('wordpress@example.test'), 'The configured From email should replace WordPress core\'s generated fallback.');
smtp_assert_same('customer@example.org', Universal_SMTP::filter_from_email_fallback('customer@example.org'), 'A caller-supplied From email must remain available when forcing is disabled.');
smtp_assert_same('Example & Company', Universal_SMTP::filter_from_name_fallback('WordPress'), 'The configured From name should replace WordPress core\'s fallback.');
smtp_assert_same('Transactional sender', Universal_SMTP::filter_from_name_fallback('Transactional sender'), 'A caller-supplied From name must remain available when forcing is disabled.');
add_filter('wp_mail_from', function(){ return 'plugin@example.com'; }, 10);
add_filter('wp_mail_from_name', function(){ return 'Another plugin'; }, 10);
smtp_assert_same('plugin@example.com', apply_filters('wp_mail_from', 'wordpress@example.test'), 'A later valid From email override should win when forcing is disabled.');
smtp_assert_same('Another plugin', apply_filters('wp_mail_from_name', 'WordPress'), 'A later From name override should win when forcing is disabled.');
$not_forced['force_from_email'] = true;
$not_forced['force_from_name'] = true;
smtp_set_options($not_forced);
smtp_assert_same('mail@example.com', apply_filters('wp_mail_from', 'wordpress@example.test'), 'Forced From email must win at very late priority.');
smtp_assert_same('Example & Company', apply_filters('wp_mail_from_name', 'WordPress'), 'Forced From name must win at very late priority.');

$enqueued_styles = [];
$enqueued_scripts = [];
$localized_scripts = [];
Universal_SMTP::enqueue_admin_assets('dashboard_page_other');
smtp_assert(empty($enqueued_styles) && empty($enqueued_scripts), 'SMTP admin assets must not load outside their own page.');
Universal_SMTP::enqueue_admin_assets('settings_page_' . Universal_SMTP::SETTINGS_SLUG);
smtp_assert(isset($enqueued_styles[Universal_SMTP::ADMIN_STYLE_HANDLE]), 'The SMTP admin stylesheet was not enqueued.');
smtp_assert(isset($enqueued_scripts[Universal_SMTP::ADMIN_SCRIPT_HANDLE]), 'The SMTP admin script was not enqueued.');
$localized = $localized_scripts[Universal_SMTP::ADMIN_SCRIPT_HANDLE]['universalSmtpAdmin'];
foreach(['ajaxUrl', 'optionName', 'saveNonce', 'testNonce', 'messages'] as $key){
    smtp_assert(array_key_exists($key, $localized), 'Missing localized admin key: ' . $key . '.');
}
smtp_assert(strpos(json_encode($localized), 'p@ss') === false, 'The localized admin payload must never expose the SMTP password.');

$can_manage_options = false;
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(false, $response->success, 'Unauthorized settings saves must fail.');
smtp_assert_same(403, $response->status, 'Unauthorized settings saves should return 403.');
$can_manage_options = true;
$nonce_valid = false;
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(403, $response->status, 'A missing or invalid save nonce should return 403.');
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(403, $response->status, 'A missing or invalid test nonce should return 403.');
$nonce_valid = true;
$can_manage_options = false;
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(403, $response->status, 'Unauthorized test-email requests must fail.');
$can_manage_options = true;
$nonce_valid = true;
$_POST = [Universal_SMTP::OPTION_NAME => smtp_valid_input(['unknown' => 'value'])];
$before_ajax = get_option(Universal_SMTP::OPTION_NAME);
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(422, $response->status, 'Unknown save fields should return 422.');
smtp_assert_same($before_ajax, get_option(Universal_SMTP::OPTION_NAME), 'A rejected AJAX request must not change stored settings.');
smtp_assert(strpos(json_encode($response->data), 'p@ss word') === false, 'Validation responses must not expose passwords.');

$update_failures[Universal_SMTP::OPTION_NAME] = true;
$_POST = [Universal_SMTP::OPTION_NAME => smtp_valid_input([
    'host' => 'write-failure.example.com',
    'password' => 'write-failure-secret',
])];
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(false, $response->success, 'A failed settings write must not report success.');
smtp_assert_same(500, $response->status, 'A failed settings write should return a generic server error.');
smtp_assert_same($before_ajax, get_option(Universal_SMTP::OPTION_NAME), 'A failed settings write must preserve the actually stored configuration.');
smtp_assert_same('persistence_failed', $response->data['errors'][0]['code'], 'A failed write needs a stable generic error code.');
smtp_assert(strpos(json_encode($response->data), 'write-failure-secret') === false, 'A persistence failure must not expose the attempted password.');
$update_failures[Universal_SMTP::OPTION_NAME] = false;

$_POST = [Universal_SMTP::OPTION_NAME => smtp_valid_input(['password' => 'ajax replacement'])];
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(true, $response->success, 'An authorized valid AJAX save should succeed.');
smtp_assert_same(200, $response->status, 'A valid AJAX save should return 200.');
smtp_assert_same(true, $response->data['passwordConfigured'], 'AJAX save should return only the password configured state.');
smtp_assert(strpos(json_encode($response->data), 'ajax replacement') === false, 'AJAX success must not expose the password.');

$active = get_option(Universal_SMTP::OPTION_NAME);
smtp_assert_same('smtp.example.com', $active['host'], 'AJAX save did not persist the canonical settings.');
smtp_assert_same(false, $option_autoload[Universal_SMTP::OPTION_NAME], 'AJAX updates must preserve non-autoload storage.');

$_POST = [Universal_SMTP::OPTION_NAME => smtp_valid_input(['password' => ''])];
$response = smtp_json_call(['Universal_SMTP', 'ajax_save_settings']);
smtp_assert_same(true, $response->success, 'An unchanged canonical configuration must remain a successful save even when update_option() returns false.');
smtp_assert_same($active, get_option(Universal_SMTP::OPTION_NAME), 'An unchanged save must preserve the stored canonical configuration.');

$rate_key = 'universal_smtp_test_rate_' . $current_user_id;
$rate_lock_key = $rate_key . '_lock';
unset($options[$rate_key], $options[$rate_lock_key]);
$mail_calls = [];
$mail_result = true;
$options[$rate_lock_key] = [
    'token' => 'another-request',
    'expires' => time() + Universal_SMTP::RATE_LIMIT_LOCK_SECONDS,
];
$_POST = ['recipient' => 'receiver@example.com'];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(429, $response->status, 'A concurrent test-email reservation must fail closed.');
smtp_assert_same(0, count($mail_calls), 'A request that cannot reserve the shared rate counter must not call wp_mail().');
smtp_assert(!isset($options[$rate_key]), 'A lock collision must not consume or overwrite another request\'s rate state.');

$options[$rate_lock_key] = [
    'token' => 'expired-observed-request',
    'expires' => time() - 1,
];
$wpdb_cas_before_query = function() use (&$options, $rate_lock_key){
    $options[$rate_lock_key] = [
        'token' => 'successor-request',
        'expires' => time() + Universal_SMTP::RATE_LIMIT_LOCK_SECONDS,
    ];
};
$_POST = ['recipient' => 'receiver@example.com'];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(429, $response->status, 'A stale-lock takeover must fail when another request replaces the observed lock first.');
smtp_assert_same('successor-request', $options[$rate_lock_key]['token'], 'Stale recovery must never delete or overwrite a successor request lock.');
smtp_assert_same(0, count($mail_calls), 'A lost compare-and-swap must not call wp_mail().');

$options[$rate_lock_key] = [
    'token' => 'expired-request',
    'expires' => time() - 1,
];
$_POST = ['recipient' => 'receiver@example.com'];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(true, $response->success, 'An expired reservation lock must be recovered without manual cleanup.');
smtp_assert_same('', $options[$rate_lock_key]['token'], 'A recovered reservation lock must be released without deleting a successor lock.');
smtp_assert_same(0, $options[$rate_lock_key]['expires'], 'A released reservation lock must be immediately available for atomic takeover.');
smtp_assert_same(false, $option_autoload[$rate_key], 'Per-user rate state must not autoload.');
smtp_assert(!empty($wpdb_queries), 'Expired-lock recovery must use prepared compare-and-swap queries.');
foreach($wpdb_queries as $query){
    smtp_assert(strpos($query['query'], 'option_name = %s') !== false, 'Rate-lock compare-and-swap must prepare the option name.');
    smtp_assert(strpos($query['query'], 'option_value = %s') !== false, 'Rate-lock compare-and-swap must prepare the observed option value.');
}

unset($options[$rate_key], $options[$rate_lock_key]);
$mail_calls = [];
for($attempt = 0; $attempt < Universal_SMTP::RATE_LIMIT_ATTEMPTS; $attempt++){
    $_POST = ['recipient' => 'receiver@example.com'];
    $response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
    smtp_assert_same(true, $response->success, 'Allowed test-email attempts should succeed.');
}
smtp_assert_same(Universal_SMTP::RATE_LIMIT_ATTEMPTS, count($mail_calls), 'The allowed test-email budget is incorrect.');
$_POST = ['recipient' => 'receiver@example.com'];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(429, $response->status, 'The sixth test email in ten minutes must be rate limited.');
smtp_assert_same(Universal_SMTP::RATE_LIMIT_ATTEMPTS, count($mail_calls), 'A rate-limited request must not call wp_mail().');

$last_status = Universal_SMTP::get_last_status();
smtp_assert_same(['status', 'timestamp', 'code', 'recipientCount'], array_keys($last_status), 'The stored delivery record must contain only bounded non-sensitive keys.');
smtp_assert_same('success', $last_status['status'], 'Successful delivery status is incorrect.');
smtp_assert_same('sent', $last_status['code'], 'Successful delivery code is incorrect.');
smtp_assert_same(1, $last_status['recipientCount'], 'Successful delivery recipient count is incorrect.');

unset($options[$rate_key], $options[$rate_lock_key]);
$mail_result = false;
$_POST = ['recipient' => 'receiver@example.com'];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(false, $response->success, 'A failed wp_mail() call must return an error.');
smtp_assert_same(502, $response->status, 'A mail dependency failure should return a generic 502 response.');
$failure_json = json_encode($response->data);
smtp_assert(strpos($failure_json, 'phpmailer_exception') === false, 'The test response must not expose an upstream error code.');
smtp_assert(strpos($failure_json, 'smtp-pass') === false, 'The test response must not expose an upstream error message.');
$failure_status = get_option(Universal_SMTP::LAST_STATUS_OPTION);
smtp_assert_same(['status', 'timestamp', 'code', 'recipientCount'], array_keys($failure_status), 'Failure storage must not retain raw WP_Error data.');
smtp_assert_same('mail_failed', $failure_status['code'], 'Failure storage must use a stable generic code.');

$_POST = ['recipient' => ['receiver@example.com']];
$response = smtp_json_call(['Universal_SMTP', 'ajax_send_test']);
smtp_assert_same(422, $response->status, 'A nested recipient value must be rejected.');
smtp_assert(isset($response->data['fieldErrors']['recipient']), 'Recipient validation should map to the recipient field.');

smtp_set_options(get_option(Universal_SMTP::OPTION_NAME));
ob_start();
Universal_SMTP::render_settings_page();
$markup = ob_get_clean();
foreach([
    'class="wrap usmtp-admin"',
    'data-usmtp-section-switcher',
    'data-usmtp-section-navigation',
    'data-usmtp-section-select',
    'id="usmtp-settings-form"',
    'id="usmtp-settings-panel-connection"',
    'id="usmtp-settings-panel-authentication"',
    'id="usmtp-settings-panel-sender"',
    'id="usmtp-settings-panel-test"',
    'data-usmtp-section-panel',
    'data-usmtp-settings-form',
    'data-usmtp-notice',
    'data-usmtp-notice-message',
    'data-usmtp-notice-dismiss',
    'data-usmtp-save',
    'class="usmtp-admin-section"',
    'data-usmtp-auth-fields',
    'data-usmtp-password-status',
    'data-usmtp-password-state="configured"',
    'data-usmtp-password-state="empty"',
    'data-usmtp-password-state="managed"',
    'data-usmtp-test-form',
    'name="recipient"',
    'data-usmtp-test',
    'universal_smtp_options[_present]',
    'universal_smtp_options[authenticate]',
    'universal_smtp_options[password]',
    'universal_smtp_options[clear_password]',
    'form="usmtp-settings-form"',
] as $needle){
    smtp_assert(strpos($markup, $needle) !== false, 'Missing required admin markup: ' . $needle . '.');
}
smtp_assert_same(4, substr_count($markup, 'data-usmtp-section-panel'), 'Every SMTP administration section must belong to the section selector.');
smtp_assert(strpos($markup, 'usmtp-admin__status') === false, 'The removed configuration-status block must not be rendered.');
smtp_assert(strpos($markup, 'ajax replacement') === false, 'The admin HTML must never display a stored password.');
smtp_assert(strpos($markup, $active['password_ciphertext']) === false, 'The admin HTML must never display encrypted password storage.');
smtp_assert(strpos($markup, 'https://champgauche.studio') !== false, 'The Studio Champ Gauche credit is missing.');

$malicious_stored = $active;
$malicious_stored['username'] = '"><script>alert(1)</script>';
$malicious_stored['from_name'] = '"><img src=x onerror=alert(1)>';
smtp_set_options($malicious_stored);
ob_start();
Universal_SMTP::render_settings_page();
$escaped_markup = ob_get_clean();
smtp_assert(strpos($escaped_markup, '<script>') === false, 'Stored username markup must never reach the admin HTML sink.');
smtp_assert(strpos($escaped_markup, '<img src=x') === false, 'Stored sender-name markup must never reach the admin HTML sink.');
smtp_assert(strpos($escaped_markup, '&lt;script&gt;') !== false, 'Stored username text must be escaped for its HTML attribute sink.');
smtp_assert(strpos($escaped_markup, '&lt;img src=x onerror=alert(1)&gt;') !== false, 'Stored sender-name text must be escaped for its HTML attribute sink.');
smtp_set_options($active);

define('UNIVERSAL_SMTP_PASSWORD', 'constant exact secret');
smtp_set_options($active);
$managed_state = Universal_SMTP::password_state();
smtp_assert_same(true, $managed_state['managed'], 'The password constant must be reported as managed.');
smtp_assert_same(true, $managed_state['configured'], 'A valid password constant must be reported as configured.');
$constant_mailer = new Universal_SMTP_Test_Mailer();
Universal_SMTP::configure_phpmailer($constant_mailer);
smtp_assert_same('constant exact secret', $constant_mailer->Password, 'The password constant must override encrypted storage.');
$managed_change = Universal_SMTP::sanitize_options(smtp_valid_input(['password' => 'should not store']));
smtp_assert(in_array('password_managed', array_column(Universal_SMTP::validation_errors(), 'code'), true), 'Direct password changes must be rejected while the constant manages the secret.');
smtp_assert_same($active, $managed_change, 'A managed-password mutation must preserve all stored settings.');
ob_start();
Universal_SMTP::render_settings_page();
$managed_markup = ob_get_clean();
smtp_assert(strpos($managed_markup, 'constant exact secret') === false, 'The constant password must never be rendered.');
smtp_assert(strpos($managed_markup, 'managed by the UNIVERSAL_SMTP_PASSWORD constant') !== false, 'The UI should report a managed password without displaying it.');

$before_deactivation = $options;
Universal_SMTP::deactivate();
smtp_assert_same($before_deactivation, $options, 'Deactivation must preserve SMTP settings and status.');

echo "Universal SMTP tests passed.\n";
