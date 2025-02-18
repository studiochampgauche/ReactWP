<?php

/*
* Database connexion
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


/*
* Define environment type
* We'll use local, development, staging and production like wordpress defaults
* 
* See "development" as "local"
*
* Use SAVEQUERIES with caution: It stores all SQL queries, which can slow down performance and increase memory usage. Avoid using it in production.
* 
* In your themes and plugins, you can use wp_get_environment_type();
*/
define('WP_ENVIRONMENT_TYPE', 'local');

if(in_array(WP_ENVIRONMENT_TYPE, ['local', 'development', 'staging'])){

	define('WP_DEBUG', true);
	define('WP_DEBUG_LOG', true);
	define('WP_DEBUG_DISPLAY', true);
	// define('SCRIPT_DEBUG', true);
	// define('SAVEQUERIES', true);
	@ini_set('display_errors', 1);

} else if(WP_ENVIRONMENT_TYPE === 'production'){

	define('WP_DEBUG', false);

}


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