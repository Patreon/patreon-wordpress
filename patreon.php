<?php

/*
Plugin Name: Patreon Wordpress
Plugin URI: https://www.patreon.com/apps/wordpress
Description: Patron-only content, directly on your website.
Version: 1.9.1
Author: Patreon <platform@patreon.com>
Author URI: https://patreon.com
*/

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

$patreon_wp_uploads_dir 		= 	wp_upload_dir();
$patreon_locked_image_cache_dir = 	$patreon_wp_uploads_dir['basedir'].'/patreon_locked_image_cache';

if( !file_exists($patreon_locked_image_cache_dir ) ) {
	wp_mkdir_p( $patreon_locked_image_cache_dir );
}

// Register activation hook for the plugin

register_activation_hook( __FILE__, array( 'Patreon_Wordpress', 'activate' ) );

// Register activation hook for the plugin

register_deactivation_hook( __FILE__, array( 'Patreon_Wordpress', 'deactivate' ) );

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
define( "PATREON_TEXT_LOCKED_POST", 'This content is available exclusively to Patreon members.' );
define( "PATREON_TEXT_OVER_BUTTON_1", 'To view this content, you must be a member of <b><a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%currency_sign_front%%%%pledgelevel%%%%currency_sign_behind%%</b> or more' );
define( "PATREON_TEXT_OVER_BUTTON_1A", 'To view this content, you must upgrade your tier to <b>%%currency_sign_front%%%%pledgelevel%%%%currency_sign_behind%% or higher at <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a></b>. Upgrade below to unlock this content.' );
define( "PATREON_TEXT_OVER_BUTTON_2", 'Edit your pledge to %%creator%% to %%currency_sign_front%%%%pledgelevel%%%%currency_sign_behind%% or more to access this content. You\'re currently pledging %%currency_sign_front%%%%currentpledgelevel%%%%currency_sign_behind%%.' );
define( "PATREON_TEXT_OVER_BUTTON_3", 'Please <a href="https://www.patreon.com/settings/payment?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=declined_payment_info_link&utm_term=" target="_blank" ref="nofollow">update</a> your Patreon payment method to access this content.' );
define( "PATREON_VALID_PATRON_POST_FOOTER_TEXT", 'This content is available exclusively to members of <b><a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%currency_sign_front%%%%pledgelevel%%%%currency_sign_behind%%</b> or more.' );
define( "PATREON_TEXT_UNDER_BUTTON_1", '' );
define( "PATREON_TEXT_UNDER_BUTTON_2", 'Already a qualifying Patreon member? <a href="%%flow_link%%" rel="nofollow">Refresh</a> to access this content.' );
define( "PATREON_TEXT_UNDER_BUTTON_3", 'Already updated? <a href="%%flow_link%%" rel="nofollow">Refresh</a> to access this content.' );
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
define( "PATREON_CREATOR_BYPASSES_FILTER_MESSAGE", 'This content is for Patrons only, it\'s not locked for you because you are logged in as the Patreon creator' );
define( "PATREON_NO_LOCKING_LEVEL_SET_FOR_THIS_POST", 'Post is already public. If you would like to lock this post, please set a pledge level for it' );
define( "PATREON_NO_POST_ID_TO_UNLOCK_POST", 'Sorry - could not get the post id for this locked post' );
define( "PATREON_WORDPRESS_VERSION", '1.9.1' );
define( "PATREON_WORDPRESS_BETA_STRING", '' );
define( "PATREON_WORDPRESS_PLUGIN_SLUG", plugin_basename( __FILE__ ) );
define( "PATREON_PRIVACY_POLICY_ADDENDUM", '<h2>Patreon features in this website</h2>In order to enable you to use this website with Patreon services, we save certain functionally important Patreon information about you in this website if you log in with Patreon.
<br /><br />
These include your Patreon user id, Patreon username, your first, last names and your vanity name. Additionally, the id of your campaign at Patreon and your campaign\'s Patreon URL are also saved.
<br /><br />
If you request that your data be deleted from this website, this data will also be deleted and Patreon functionality will not work. You would need to register on this website and log in to this website with Patreon again in order to re-populate this data and have Patreon functionality working again.' );
define( "PATREON_NO_CODE_RECEIVED_FROM_PATREON", "Sorry -  No authorization code received from Patreon." );
define( "PATREON_NO_FLOW_ACTION_PROVIDED", "Nothing to do since no Patreon action was requested." );
define( "PATREON_DIRECT_UNLOCKS_NOT_ON", 'In order to use this feature, direct unlocks need to be turned on. Please refer to <a href="https://www.patreon.com/apps/wordpress?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=direct_unlock_setting_notification_link&utm_term=">the developer documentation</a>. ' );
define( "PATREON_TEXT_LOCKED_POST_ACTIVE_PATRONS", 'while having been active patrons on %%post_date%%' );
define( "PATREON_TEXT_LOCKED_POST_ACTIVE_PATRONS_WITH_TOTAL_PLEDGE", 'or Patrons with total pledge of %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%%' );
define( "PATREON_CONTRIBUTION_REQUIRED_STOP_MARK", '!' );
define( "PATREON_TEXT_OVER_BUTTON_4", ' while having been active patrons on %%post_date%%' );
define( "PATREON_TEXT_OVER_BUTTON_5", ' or Patrons with total %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% or more pledge' );
define( "PATREON_TEXT_OVER_BUTTON_6", ' on Patreon' );
define( "PATREON_TEXT_OVER_BUTTON_7", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at the time of posting. <b><a href="%%flow_link%%" rel="nofollow">Become a patron at %%currency_sign_front%%%%pledgelevel%%%%currency_sign_fbehind%% or more</a></b> to get exclusive content like this in the future.' );
define( "PATREON_TEXT_OVER_BUTTON_8", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at the time of posting.  Your account does not fulfill the requirements. Become a patron to get exclusive content like this in the future.' );
define( "PATREON_TEXT_OVER_BUTTON_9", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%tier_level%% or higher tier, or having at least %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% pledged in total. Upgrade below to unlock this content.' );
define( "PATREON_TEXT_OVER_BUTTON_10", 'This content is available exclusively to members of <b><a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a></b> <b>at %%tier_level%%</b> or higher tier at the time this content was posted, or having at least %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% pledged in total.' );
define( "PATREON_TEXT_OVER_BUTTON_11", 'This content is available exclusively to members <b>at %%tier_level%%</b> or higher tier <b>at <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a></b> at the time this content was posted.' );
define( "PATREON_TEXT_OVER_BUTTON_12", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%tier_level%% or higher tier, or having at least %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% pledged in total.' );
define( "PATREON_TEXT_OVER_BUTTON_13", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%tier_level%% or higher tier at the time this content was posted, or having at least %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% pledged in total.' );
define( "PATREON_TEXT_OVER_BUTTON_14", 'This content is available exclusively to members of <a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a> at %%tier_level%% or higher tier at the time this content was posted, or having at least %%currency_sign_front%%%%total_pledge%%%%currency_sign_behind%% pledged in total. Your account currently does not qualify.' );
define( "PATREON_TEXT_OVER_BUTTON_15", 'To view this content, you must be a member of <b><a href="%%creator_link%%" target="_blank">%%creator%% Patreon</a></b>' );
define( "PATREON_COULDNT_ACQUIRE_USER_DETAILS", 'Sorry. Could not acquire your info from Patreon. Please try again later.' );
define( "PATREON_PRETTY_PERMALINKS_NEED_TO_BE_ENABLED", 'Pretty permalinks are required for Patreon WordPress to work. Please visit <a href="'.admin_url('options-permalink.php').'" target="_blank">permalink options page</a> and set an option that is different from "Plain"' );
define( "PATREON_ENSURE_REQUIREMENTS_MET", '<h3>Please ensure following requirements are met before starting setup:</h3>' );
define( "PATREON_ERROR_MISSING_CREDENTIALS", 'One or more of app credentials were not received. Please try again.' );
define( "PATREON_SETUP_INITIAL_MESSAGE", 'By <a href="https://support.patreon.com/hc/en-us/articles/360032404632-How-your-WordPress-site-will-be-connected-to-Patreon?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=setup_wizard_screen_1_how_to_connect_website_to_patreon_link&utm_term="  style="text-decoration: none;" target="_blank">connecting your site to Patreon</a>, you can bring Patreon features to your website & post member-only content at your website to <a href="https://blog.patreon.com/patreon-wordpress-plugin/?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=setup_wizard_screen_1_link_patreon_blog_article_for_patreon_wordpress&utm_term=" target="_blank" style="text-decoration: none;">increase your patrons and monthly revenue</a>. We will now take you to Patreon in order to automatically connect your site. Please make sure you are <a href="https://www.patreon.com/login?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=setup_wizard_screen_1_creator_login_link&utm_term=" target="_blank"  style="text-decoration: none;">logged in</a> to your creator account at Patreon before starting, or <a href="https://www.patreon.com/signup?ru=%2Fcreate?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=setup_wizard_screen_1_creator_signup_link&utm_term=" target="_blank" style="text-decoration: none;">register here</a> if you don\'t already have a creator account.' );
define( "PATREON_SETUP_SUCCESS_MESSAGE", 'Great! Your site is now connected to Patreon!' );
define( "PATREON_RECONNECT_SUCCESS_MESSAGE", 'Great! We successfully reconnected your site to Patreon!' );
define( "PATREON_TEXT_EVERYONE", 'Everyone' );
define( "PATREON_TEXT_ANY_PATRON", 'Any patron' );
// Common identificator for WP installations - just for reference, does not do anything
define( "PATREON_PLUGIN_CLIENT_ID", '40otjXLPiUL023m_FAX5XRkRYVRF0DT62cHKH7NjyNsYYFZMYHxqzWoqbEtt-22l' );
define( "PATREON_SITE_DISCONNECTED_FROM_PATREON_HEADING", 'Disconnection successful!' );
define( "PATREON_SITE_DISCONNECTED_FROM_PATREON_TEXT", 'You successfully disconnected your site from Patreon. Now you can connect another creator account to your site.' );
define( "PATREON_NO_AUTH_FOR_CLIENT_CREATION", 'We weren\'t able to get the go ahead from Patreon while attempting to connect your site. Please wait a minute and try again. If this situation persists, <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=no_auth_for_client_creation_warning_link&utm_term=" target="_blank">contact support</a>.'  );
define( "PATREON_NO_ACQUIRE_CLIENT_DETAILS", 'We weren\'t able to get to get the token for connecting your site to Patreon for the time being. Please wait a while and try again and <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=client_details_not_acquired_info_link&utm_term=" target="_blank">contact support</a> if this situation persists.' );
define( "PATREON_NO_CREDENTIALS_RECEIVED", 'We weren\'t able to connect your site to Patreon because Patreon sent back empty credentials. Please wait a while and try again and <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=no_credentials_received_info_link&utm_term=" target="_blank">contact support</a> if this situation persists.' );
define( "PATREON_RECONNECT_INITIAL_MESSAGE", 'We will now (re)connect your site to Patreon. This will refresh your site\'s connection and all app credentials. Patron only content in your website will be accessible to everyone until reconnection is complete. We will now take you to Patreon in order to automatically reconnect your site. Please make sure you are logged into your creator account at Patreon before starting.' );
define( "PATREON_ADMIN_MESSAGE_DEFAULT_TITLE", 'All\'s cool' );
define( "PATREON_ADMIN_MESSAGE_DEFAULT_CONTENT", 'Pretty much nothing to report.' );
define( "PATREON_ADMIN_MESSAGE_CLIENT_DELETE_ERROR_TITLE", 'Sorry, couldn\'t disconnect your site' );
define( "PATREON_ADMIN_MESSAGE_CLIENT_DELETE_ERROR_CONTENT", 'Please wait a few minutes and <a href="' . admin_url( 'admin.php?page=patreon-plugin&patreon_wordpress_action=disconnect_site_from_patreon' ) . '">try again</a>. If this issue persists, you can visit your <a href="https://www.patreon.com/portal/registration/register-clients?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=client_delete_error_info_link&utm_term=" target="_blank">your app/clients page</a> and delete the app/client for this site. Then you can save empty values for details in "Connection details" tab in "Patreon settings" menu at your site. This would manually disconnect your site from Patreon. Then, you can reconnect your site to another Patreon account or to the same account.' );
define( "PATREON_ADMIN_MESSAGE_CLIENT_RECONNECT_DELETE_ERROR_TITLE", 'Sorry, couldn\'t disconnect your site before reconnecting it' );
define( "PATREON_TEXT_YOU_HAVE_NO_REWARDS_IN_THIS_CAMPAIGN", 'You have no tiers in this campaign' );
define( "PATREON_ADMIN_MESSAGE_CLIENT_RECONNECT_DELETE_ERROR_CONTENT", 'Please wait a few minutes and <a href="' . admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_0' ) . '">try again</a>. If this issue persists, you can visit your <a href="https://www.patreon.com/portal/registration/register-clients?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=client_reconnect_delete_error_info_link&utm_term=" target="_blank">your app/clients page</a> and delete the app/client for this site. Then you can save empty values for details in "Connection details" tab in "Patreon settings" menu at your site. This would manually disconnect your site from Patreon. Then, you can reconnect your site to another Patreon account or to the same account.' );
define( "PATREON_WP_SUPER_CACHE_LOGGED_IN_USERS_ENABLED_HEADING", 'WP Super Cache caches pages for logged in users' );
define( "PATREON_WP_SUPER_CACHE_LOGGED_IN_USERS_ENABLED", 'This could cause logged in patrons to see a content they unlocked still as locked because they may be served a cached version of the page.<h3>Solution</h3>Please visit <a target="_blank" href="' . admin_url( 'options-general.php?page=wpsupercache&tab=settings' ) . '">this WP Super Cache settings page</a> and turn the option <b>"Disable caching for visitors who have a cookie set in their browser."</b> or <b>"Disable caching for logged in visitors. (Recommended)"</b> on and click "Update Status" button to save WP Super Cache settings' );
define( "PATREON_WP_SUPER_CACHE_MAKE_KNOWN_ANON_ENABLED_HEADING", 'WP Super Cache caches treats logged in users as anonymous' );
define( "PATREON_WP_SUPER_CACHE_MAKE_KNOWN_ANON_ENABLED", 'This could cause logged in patrons to be treated anonymous and cause them to get served a cached version of the unlocked content, therefore preventing them from accessing the content they unlocked.<h3>Solution</h3>Please visit <a target="_blank" href="' . admin_url( 'options-general.php?page=wpsupercache&tab=settings' ) . '">this WP Super Cache settings page</a> and turn the option <b>"Make known users anonymous so they’re served supercached static files."</b> off and click "Update Status" button to save WP Super Cache settings' );
define( "PATREON_PRETTY_PERMALINKS_ARE_OFF_HEADING", 'Pretty permalinks are turned off' );
define( "PATREON_PRETTY_PERMALINKS_ARE_OFF", 'Pretty permalinks are off in your WP installation. This would cause content unlock flows to fail when the user is returning from Patreon.<h3>Solution</h3>Please visit <a target="_blank" href="' . admin_url( 'options-permalink.php' ) . '">permalink settings page</a> and select any option other than "Plain" and click "Save Changes". Please note that this will change the link structure of your site.' );
define( "PATREON_LAST_50_CONNECTION_ERRORS", 'These are the last 50 connection issues encountered by your site when contacting Patreon API. These are here for general info on health of the connection of your WP site to Patreon API. They only constitute an error if there are a lot of recent ones. Healthiest integrations should have a number of them (up to 50) in the long run.' );
define( "PATREON_LAST_50_CONNECTION_ERRORS_HEADING", 'Last 50 connection errors' );
define( "PATREON_FEED_ACTION_TEXT", ' - Click "Read more" to unlock this content at the source' );
define( "PATREON_LOGIN_WIDGET_NAME", 'Login with Patreon' );
define( "PATREON_LOGIN_WIDGET_DESC", 'Have your users login with Patreon or connect their Patreon account.' );
define( "PATREON_LOGIN_WIDGET_LOGOUT", 'You are logged in. %%click_here%% to logout.' );
define( "PATREON_CLICK_HERE", 'Click here' );
define( "PATREON_ADMIN_MESSAGE_V1_CLIENT_ATTEMPTING_V2_SETUP", 'Your site is using the old v1 version of Patreon connection. Since v1 apps can\'t be reconnected automatically, please visit <a href="https://www.patreon.com/portal/registration/register-clients?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=v1_client_attempting_v2_setup&utm_term=" target="_blank">your app/clients page</a> at Patreon and delete the app/client that shows up for this site. After this, you can continue with setup.' );
define( "PATREON_POST_SYNC_0", 'If you want, you can sync your posts from Patreon to your WP site and simplify your workflow. You can import all your existing posts to your site and import new and updated posts on the go.' );
define( "PATREON_POST_SYNC_1", 'Choose how you want to sync your posts from Patreon' );
define( "PATREON_POST_SYNC_2", 'This will overwrite content and formatting in your local posts with the ones in your Patreon posts. Recommended: Yes' );
define( "PATREON_POST_SYNC_3", 'Delete local post if when you delete matching post at Patreon. Recommended: No' );
define( "PATREON_POST_SYNC_4", 'Your post sync choices were saved! Existing posts will be imported every 5 minutes until all are imported, and new/updated posts will also be synced. You can change these settings, start a new import or turn post sync on/off in "Patreon Settings" menu.' );
define( "PATREON_POST_SYNC_5", 'Choose a post type and category to put synced posts in - this will only affect existing posts that will be imported or posts you make in future' );
define( "PATREON_POST_SYNC_6", 'Choose the author to be used in synced posts. This will only affect newly imported posts' );
define( "PATREON_ALL_POST_CATEGORY_FIELDS_MUST_BE_SELECTED", 'Please select all post category fields. All 3 post category fields must be present and selected' );
define( "PATREON_API_VERSION_WARNING", 'Your plugin is still using API v1! This will cause errors when you use post sync feature! Please read <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">this guide</a> to upgrade your plugin to API v2 before activating post sync.' );
define( "PATREON_WARNING_IMPORTANT", 'Important: ' );
define( "PATREON_WARNING_POST_SYNC_SET_WITHOUT_API_V2", 'Important: Post syncing from Patreon is set to on, but your site is using API v1. Post sync wont work without API v2. Follow <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">this guide</a> to upgrade your site to API v2 or disable post sync <a href="' . admin_url( 'admin.php?page=patreon-plugin' ) .'">here in settings</a>'  );

require( 'classes/patreon_wordpress.php' );

register_activation_hook( __FILE__, array( 'Patreon_Wordpress', 'activate' ) );

$Patreon_Wordpress = new Patreon_Wordpress;

require( 'includes/patreon_widgets.php' );

