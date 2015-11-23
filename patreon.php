<?php 

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define("PATREON_PLUGIN_URL", plugin_dir_url( __FILE__ ) );

include 'admin/patreon-options-page.php';
include 'admin/patreon-content-metabox.php';
include 'classes/patreon_wordpress.php';

$Patreon_Wordpress = new Patreon_Wordpress;

?>