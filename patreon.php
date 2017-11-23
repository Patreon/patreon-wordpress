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
define("PATREON_PLUGIN_ASSETS", plugin_dir_url( __FILE__ ).'/assets' );
define("PATREON_TEXT_CONNECT", 'Connect with Patreon' );
define("PATREON_TEXT_REFRESH", 'Refresh' );
define("PATREON_TEXT_NOT_PATRON", 'Not a Patron?' );
define("PATREON_TEXT_ALREADY_PATRON", 'Already a Patron?' );
define("PATREON_TEXT_BECOME_PATRON", 'Become a Patron!' );
define("PATREON_TEXT_SUPPORT_ON_PATREON", 'Support on Patreon' );
define("PATREON_TEXT_MISTAKEN_PATRON", 'Patron but can\'t see?' );
define("PATREON_TEXT_PLEDGE_NOT_ENOUGH", 'Upgrade your Pledge!' );
define("PATREON_TEXT_UPGRADE_PLEDGE", 'Upgrade Pledge' );
define("PATREON_TEXT_UNLOCK_WITH_PATREON", 'Unlock with Patreon' );
define("PATREON_TEXT_UPDATE_PLEDGE", 'Update Pledge' );
define("PATREON_TEXT_LOCKED_POST", 'Sorry! This content is for our Patrons who pledge $%%pledgelevel%% and over!' );
define("PATREON_TEXT_OVER_BUTTON_1", 'This content is available to Patrons pledging $%%pledgelevel%% or more on Patreon' );
define("PATREON_TEXT_OVER_BUTTON_2", 'Edit your pledge to %%creator%% to $%%pledgelevel%% or more to access this content. You\'re currently pledging $%%currentpledgelevel%%.' );
define("PATREON_TEXT_OVER_BUTTON_3", 'Your Patreon payment method appears to be declined. Update your pledge to access this post.' );
define("PATREON_VALID_PATRON_POST_FOOTER_TEXT", 'This content is for Patrons pledging $%%pledgelevel%% or more on %%creatorprofileurl%%' );
define("PATREON_TEXT_UNDER_BUTTON_1", '' );
define("PATREON_TEXT_UNDER_BUTTON_2", 'Already pledging $%%pledgelevel%% or more? %%flowlink%% to access this post.' );
define("PATREON_TEXT_UNDER_BUTTON_3", 'Already updated? %%flowlink%% to access this post.' );

include 'classes/patreon_wordpress.php';

$Patreon_Wordpress = new Patreon_Wordpress;

?>
