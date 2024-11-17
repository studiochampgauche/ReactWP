<?php

$table_prefix  = 'wp_';

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'];

define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');
define('AUTH_SALT', 'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT', 'put your unique phrase here');
define('NONCE_SALT', 'put your unique phrase here');

define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

if(!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__) . '/');

if(!defined('WP_HOME'))
    define('WP_HOME', $current_url);

if(!defined('WP_USE_THEMES'))
    define('WP_USE_THEMES', false);

if(!defined('WP_DEFAULT_THEME'))
    define('WP_DEFAULT_THEME', 'reactwp');

require_once(ABSPATH . 'wp-settings.php');