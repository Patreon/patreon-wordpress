<?php

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() ){
  add_action('admin_menu', 'patreon_plugin_setup');
  add_action('admin_init', 'patreon_plugin_register_settings' );
}

function patreon_plugin_register_settings() { // whitelist options
	register_setting( 'patreon-options', 'patreon-client-id' );
    register_setting( 'patreon-options', 'patreon-client-secret' );
}

function patreon_plugin_setup(){
    add_menu_page( 'Patreon Settings', 'Patreon Settings', 'manage_options', 'patreon-plugin', 'patreon_plugin_setup_page' );
}
 
function patreon_plugin_setup_page(){
    
?>

<h1>Patreon API Settings</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'patreon-options' ); ?>
    <?php do_settings_sections( 'patreon-options' ); ?> 

    <br>

    <h2>API Settings</h2>
    <table class="form-table">

        <tr valign="top">
        <th scope="row">Redirect URI</th>
        <td><input type="text" value="<?php echo site_url().'/patreon-authorization/'; ?>" disabled class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Client ID</th>
        <td><input type="text" name="patreon-client-id" value="<?php echo esc_attr( get_option('patreon-client-id', '') ); ?>" class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Client Secret</th>
        <td><input type="text" name="patreon-client-secret" value="<?php echo esc_attr( get_option('patreon-client-secret', '') ); ?>" class="large-text" /></td>
        </tr>

    </table>

    <?php submit_button(); ?>

</form>

<?php
}

?>