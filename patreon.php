<?php

/*
Plugin Name: Patreon Wordpress
Plugin URI: https://www.patreon.com/apps/wordpress
Description: Serve patron-only posts - and give ad-free experiences - directly on your website.
Version: 1.2.4
Author: Patreon <platform@patreon.com>
Author URI: https://patreon.com
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define("PATREON_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
define("PATREON_PLUGIN_ASSETS", plugin_dir_url( __FILE__ ) .'assets/');

include 'classes/patreon_wordpress.php';

$Patreon_Wordpress = new Patreon_Wordpress;

register_activation_hook( __FILE__, array('Patreon_Welcome', 'welcome_screen_activate') );
register_activation_hook( __FILE__, array('Patreon_Protect', 'createProtectedUploadDirectory') );
register_activation_hook( __FILE__, array('Patreon_Routing', 'activate') );
register_deactivation_hook( __FILE__, array('Patreon_Routing', 'deactivate') );

?>
