<?php

/*
* Prefix table
*/
$table_prefix  = 'wp_';


/*
* Database connexion
*/
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');


/*
* Secure cookies and nonces
*/
define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');
define('AUTH_SALT', 'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT', 'put your unique phrase here');
define('NONCE_SALT', 'put your unique phrase here');

$reactwp_security_keys = [
	AUTH_KEY,
	SECURE_AUTH_KEY,
	LOGGED_IN_KEY,
	NONCE_KEY,
	AUTH_SALT,
	SECURE_AUTH_SALT,
	LOGGED_IN_SALT,
	NONCE_SALT,
];


/*
* Define environment type
* We'll use local, development, staging and production like wordpress defaults
* 
* Use SAVEQUERIES with caution: It stores all SQL queries, which can slow down performance and increase memory usage. Avoid using it in production.
* 
* In your themes and plugins, you can use wp_get_environment_type();
*/
$environment_type = getenv('WP_ENVIRONMENT_TYPE') ?: 'production';
$environment_type = in_array($environment_type, ['local', 'development', 'staging', 'production'], true)
	? $environment_type
	: 'production';

define('WP_ENVIRONMENT_TYPE', $environment_type);

if(in_array(WP_ENVIRONMENT_TYPE, ['local', 'development', 'staging'], true)){

	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);
	define('WP_DEBUG_DISPLAY', in_array(WP_ENVIRONMENT_TYPE, ['local', 'development'], true));
	// define('SCRIPT_DEBUG', true);
	// define('SAVEQUERIES', true);
	@ini_set('display_errors', WP_DEBUG_DISPLAY ? 1 : 0);

} else if(WP_ENVIRONMENT_TYPE === 'production'){

	define('WP_DEBUG', false);
	define('WP_DEBUG_LOG', false);
	define('WP_DEBUG_DISPLAY', false);
	define('DISALLOW_FILE_EDIT', true);
	define('FORCE_SSL_ADMIN', true);
	@ini_set('display_errors', 0);

}

if(
	WP_ENVIRONMENT_TYPE === 'production'
	&& (
		count(array_unique($reactwp_security_keys)) !== count($reactwp_security_keys)
		|| count(array_filter($reactwp_security_keys, static function($key){
			return !is_string($key)
				|| strlen($key) < 32
				|| $key === 'put your unique phrase here';
		})) > 0
	)
){
	throw new RuntimeException('Use eight unique WordPress authentication keys and salts of at least 32 characters before running ReactWP in production.');
}

unset($reactwp_security_keys);


/*
* Define absolute path
*/
if(!defined('ABSPATH')){
	define('ABSPATH', dirname(__FILE__) . '/');
}


/*
* Change default wordpress theme
*/
define('WP_DEFAULT_THEME', 'reactwp');


/*
* Call WordPress Core Settings
*/
require_once(ABSPATH . 'wp-settings.php');
