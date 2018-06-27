<?php

/*
Plugin Name: Patreon Wordpress
Plugin URI: https://www.patreon.com/apps/wordpress
Description: Patron-only content, directly on your website.
Version: 1.1.2
Author: Patreon <platform@patreon.com>
Author URI: https://patreon.com
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$patreon_wp_uploads_dir 		= 	wp_upload_dir();
$patreon_locked_image_cache_dir = 	$patreon_wp_uploads_dir['basedir'].'/patreon_locked_image_cache';

if( ! file_exists($patreon_locked_image_cache_dir ) ) {
	wp_mkdir_p( $patreon_locked_image_cache_dir );
}

define( "PATREON_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
define( "PATREON_PLUGIN_ASSETS", plugin_dir_url( __FILE__ ).'assets' );
define( "PATREON_PLUGIN_ASSETS_DIR", plugin_dir_path( __FILE__ ).'assets' );
define( "PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR", $patreon_locked_image_cache_dir);
define( "PATREON_TEXT_CONNECT", 'Connect with Patreon' );
define( "PATREON_TEXT_REFRESH", 'Refresh' );
define( "PATREON_TEXT_NOT_PATRON", 'Not a Patron?' );
define( "PATREON_TEXT_ALREADY_PATRON", 'Already a Patron?' );
define( "PATREON_TEXT_BECOME_PATRON", 'Become a Patron!' );
define( "PATREON_TEXT_SUPPORT_ON_PATREON", 'Support on Patreon' );
define( "PATREON_TEXT_MISTAKEN_PATRON", 'Patron but can\'t see?' );
define( "PATREON_TEXT_PLEDGE_NOT_ENOUGH", 'Upgrade your Pledge!' );
define( "PATREON_TEXT_UPGRADE_PLEDGE", 'Upgrade Pledge' );
define( "PATREON_TEXT_UNLOCK_WITH_PATREON", 'Unlock with Patreon' );
define( "PATREON_TEXT_UPDATE_PLEDGE", 'Update Pledge' );
define( "PATREON_TEXT_LOCKED_POST", 'Sorry! This content is for our Patrons who pledge $%%pledgelevel%% and over!' );
define( "PATREON_TEXT_OVER_BUTTON_1", 'This content is available to Patrons pledging $%%pledgelevel%% or more on Patreon' );
define( "PATREON_TEXT_OVER_BUTTON_2", 'Edit your pledge to %%creator%% to $%%pledgelevel%% or more to access this content. You\'re currently pledging $%%currentpledgelevel%%.' );
define( "PATREON_TEXT_OVER_BUTTON_3", 'Your Patreon payment method appears to be declined. Update your pledge to access this post.' );
define( "PATREON_VALID_PATRON_POST_FOOTER_TEXT", 'This content is for Patrons pledging $%%pledgelevel%% or more on %%creatorprofileurl%%' );
define( "PATREON_TEXT_UNDER_BUTTON_1", '' );
define( "PATREON_TEXT_UNDER_BUTTON_2", 'Already pledging $%%pledgelevel%% or more? %%flowlink%% to access this post.' );
define( "PATREON_TEXT_UNDER_BUTTON_3", 'Already updated? %%flowlink%% to access this post.' );
define( "PATREON_CANT_LOGIN_STRICT_OAUTH", 'Sorry, couldn\'t log you in with Patreon because you have to be logged in to '.get_bloginfo('NAME').' first' );
define( "PATREON_LOGIN_WITH_WORDPRESS_NOW", 'You can now login with your wordpress username/password.' );
define( "PATREON_CANT_LOGIN_NONCES_DONT_MATCH", 'Sorry. Aborted Patreon login for security because security cookies dont match.' );
define( "PATREON_CANT_LOGIN_DUE_TO_API_ERROR", 'Sorry. Login aborted due to an API error.' );
define( "PATREON_CANT_LOGIN_DUE_TO_API_ERROR_CHECK_CREDENTIALS", 'Sorry. Login aborted due to an API error. Please check API credentials.' );
define( "PATREON_WEIRD_REDIRECTION_AT_LOGIN", 'This redirect should not have happened. Please contact site administration.' );
define( "PATREON_COULDNT_CREATE_WP_ACCOUNT", 'Sorry. Could not create a WordPress account. Please contact site administration.' );
define( "PATREON_API_CREDENTIALS_MISSING", 'Sorry. Could not login because API credentials are missing or incorrect. Please contact site administration.' );
define( "PATREON_ADMIN_LOGIN_WITH_PATREON_DISABLED", 'Sorry. Logging in Administrators via Patreon is turned off in options. Please login with your WordPress account first' );
define( "PATREON_EMAIL_EXISTS_LOGIN_WITH_WP_FIRST", 'Sorry. A WordPress user with the same email you use at Patreon exists. Please log into this site with your WordPress user first, and then log in with Patreon to link these two accounts...' );
define( "PATREON_LOGIN_WITH_PATREON_DISABLED", 'Sorry. Logging in with Patreon is disabled in this Website. Please contact administrator.' );
define( "PATREON_ADMIN_BYPASSES_FILTER_MESSAGE", 'This content is for Patrons only, it\'s not locked for you because you are an Administrator' );
define( "PATREON_NO_LOCKING_LEVEL_SET_FOR_THIS_POST", 'Post is already public. If you would like to lock this post, please set a pledge level for it' );
define( "PATREON_NO_POST_ID_TO_UNLOCK_POST", 'Sorry - could not get the post id for this locked post' );
define( "PATREON_WORDPRESS_VERSION", '1.1.1' );
define( "PATREON_WORDPRESS_PLUGIN_SLUG", plugin_basename( __FILE__ ) );
define( "PATREON_PRIVACY_POLICY_ADDENDUM", '<h2>Patreon features in this website</h2>In order to enable you to use this website with Patreon services, we save certain functionally important Patreon information about you in this website if you log in with Patreon.
<br /><br />
These include your Patreon user id, Patreon username, your first, last names and your vanity name. Additionally, the id of your campaign at Patreon and your campaign\'s Patreon URL are also saved.
<br /><br />
If you request that your data be deleted from this website, this data will also be deleted and Patreon functionality will not work. You would need to register on this website and log in to this website with Patreon again in order to re-populate this data and have Patreon functionality working again.' );

include 'classes/patreon_wordpress.php';

$Patreon_Wordpress = new Patreon_Wordpress;