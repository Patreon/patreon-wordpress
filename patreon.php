<?php

/*
Plugin Name: Patreon
Plugin URI:
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/
namespace patreon;
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If this file is called directly, abort.
// The class that contains the plugin info.
require_once plugin_dir_path(__FILE__) . 'includes/class-info.php';

/**
 * The code that runs during plugin activation.
 */
function activation() {
    add_option( 'patreon_activation_redirect' , true );
    require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
    Activator::activate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\activation');

/**
 * Run the plugin.
 */
function run() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-plugin.php';
    $plugin = new Plugin();
    $plugin->run();
}
run();

// $Patreon_Wordpress = new Patreon_Wordpress;

?>
