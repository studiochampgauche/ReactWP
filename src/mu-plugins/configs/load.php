<?php
/*
* Plugin Name: Core plugins
* Description: ACF + ReactWP
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
Version: 1.0.0
*/

if(!is_blog_installed()) return;

require_once 'advanced-custom-fields-pro/acf.php';

require_once 'reactwp/init.php';