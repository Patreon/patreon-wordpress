<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Patreon_Options {


    function __construct() {

        if ( is_admin() ){
            add_action('admin_menu', array($this, 'patreon_plugin_setup') );
            add_action('admin_init', array($this, 'patreon_plugin_register_settings') );
            add_action('admin_notices', array($this, 'patreon_plugin_login_warning') );
        }

    }

    function patreon_plugin_setup(){
        add_menu_page( 'Patreon Settings', 'Patreon Settings', 'manage_options', 'patreon-plugin', array($this, 'patreon_plugin_setup_page'), 'dashicons-admin-network' );
    }

    function patreon_plugin_register_settings() { // whitelist options
        register_setting( 'patreon-options', 'patreon-client-id' );
        register_setting( 'patreon-options', 'patreon-client-secret' );
        register_setting( 'patreon-options', 'patreon-creators-access-token' );
        register_setting( 'patreon-options', 'patreon-creators-refresh-token' );
        register_setting( 'patreon-options', 'patreon-creator-id' );
        register_setting( 'patreon-options', 'patreon-fetch-creator-id' );
        register_setting( 'patreon-options', 'patreon-paywall-img-url' );
        register_setting( 'patreon-options', 'patreon-paywall-blocked-img-url' );
        register_setting( 'patreon-options', 'patreon-rewrite-rules-flushed' );
        register_setting( 'patreon-options', 'patreon-enable-register-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-login-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-connect-with-patreon-in-userprofile' );
        register_setting( 'patreon-options', 'patreon-enable-allow-admins-login-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-safe-patreon-embeds' );
        register_setting( 'patreon-options', 'patreon-enable-redirect-to-page-after-login' );
        register_setting( 'patreon-options', 'patreon-enable-redirect-to-page-id' );
        register_setting( 'patreon-options', 'patreon-protect-default-image-patreon-level' );
        register_setting( 'patreon-options', 'patreon-enable-strict-oauth' );
        register_setting( 'patreon-options', 'patreon-lock-entire-site' );
        register_setting( 'patreon-options', 'patreon-custom-universal-banner' );
    }

    function patreon_plugin_login_warning() {

        $patreon_login = get_option('patreon-enable-login-with-patreon', false);
        $patreon_admin_login = get_option('patreon-enable-allow-admins-login-with-patreon', false);

        $anchor_url = site_url().'/wp-admin/admin.php?page=patreon-plugin#danger-users';

        if($patreon_login && $patreon_admin_login) {
            echo '<br><div class="notice notice-warning is-dismissible">
                 <p>Please make sure all your Admins/Editors have Patreon accounts with the correct email addresses. For a list of your <a href="'.$anchor_url.'">admins and editors click here</a></p>
             </div>';
        }

    }

    function fetch_creator_id() {

        if(is_admin() && current_user_can('manage_options') && isset($_POST[''])) {
            $creator_id = Patreon_Wordpress::getPatreonCreatorID();

            if($creator_id != false) {
                update_option( 'patreon-creator-id', $creator_id );
            }
        }

    }

    function patreon_plugin_setup_page(){

        $args = array(
                'post_type'=>'page',
                'post_status'=>'publish',
                'posts_per_page'=>-1,
                'sort_order'=>'asc',
                'orderby'=>'title'
            );
        $all_pages = get_pages($args);

        $danger_user_list = Patreon_Login::getDangerUserList();

        ?>

       <form method="post" action="options.php">

            <?php settings_fields( 'patreon-options' ); ?>
            <?php do_settings_sections( 'patreon-options' ); ?>

        <div class="wrap">

            <div id="icon-options-general" class="icon32"></div>
            <h1>Patreon Wordpress Settings</h1>

            <div id="poststuff">

                <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">

                        <div class="meta-box-sortables ui-sortable">

                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle"><span>API Settings</span>
                                </h2>

                                <div class="inside">

                                    <p>You can find the oAuth client settings on Patreon <a href="https://www.patreon.com/platform/documentation/clients" target="_blank">here</a>.</p>

                                    <table class="widefat">

                                        <tr valign="top">
                                        <th scope="row"><strong>Redirect URI</strong></th>
                                        <td><input type="text" value="<?php echo site_url().'/patreon-authorization/'; ?>" disabled class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Client ID</strong></th>
                                        <td><input type="text" name="patreon-client-id" value="<?php echo esc_attr( get_option('patreon-client-id', '') ); ?>" class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Client Secret</strong></th>
                                        <td><input type="text" name="patreon-client-secret" value="<?php echo esc_attr( get_option('patreon-client-secret', '') ); ?>" class="large-text" /></td>
                                        </tr>
                                        <tr valign="top">
                                        <th scope="row"><strong>Creator's Access Token</strong></th>
                                        <td><input type="text" name="patreon-creators-access-token" value="<?php echo esc_attr( get_option('patreon-creators-access-token', '') ); ?>" class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Creator's Refresh Token</strong></th>
                                        <td><input type="text" name="patreon-creators-refresh-token" value="<?php echo esc_attr( get_option('patreon-creators-refresh-token', '') ); ?>" class="large-text" /></td>
                                        </tr>

                                    </table>


                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->



                        <div class="meta-box-sortables ui-sortable">

                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle"><span>Creator Settings</span>
                                </h2>

                                <div class="inside">

                                    <p>Get your Creator ID <a href="https://www.patreon.com/dashboard/widgets" target="_blank">here</a>, in the url for the button you can find your creator ID.</p>
                                    <p><em>https://www.patreon.com/bePatron?u=<strong>XXXXXXXXXX</strong></em></p>

                                    <table class="widefat">
                                        <tr valign="top">
                                        <th scope="row"><strong>Creator ID</strong></th>
                                        <td><input type="text" name="patreon-creator-id" value="<?php echo esc_attr( get_option('patreon-creator-id', '') ); ?>" class="large-text" /></td>
                                        </tr>
                                    </table>

                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->


                        <div class="meta-box-sortables ui-sortable">

                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle"><span>Patreon Wordpress Options</span>
                                </h2>

                                <div class="inside">

                                    <table class="widefat">

                                        <tr valign="top">
                                        <th scope="row"><strong>Enable strict oAuth</strong> <br>(Only connects logged in users)</th>
                                        <td><input type="checkbox" name="patreon-enable-strict-oauth" value="1"<?php checked( get_option('patreon-enable-strict-oauth', true) ); ?> /></td>
                                        </tr>
										
                                        <tr valign="top">
                                        <th scope="row"><strong>Make entire site Patron-only with Pledge Level</strong> <br>(Only Patrons at and over this pledge level will be able to see Posts)</th>
                                        <td>$<input type="text" name="patreon-lock-entire-site" value="<?php echo get_option('patreon-lock-entire-site'); ?>" /></td>
                                        </tr>
                                        <tr valign="top">
                                        <th scope="row" colspan="2"><strong>Custom Call to Action Banner</strong> <br>Instead of default text, you can show a custom Call to Action Notification and Banner (ie, "Be our Patron to see this content!") to your visitors. You can use HTML too. Leave empty to disable.<br /><br />
										<?php wp_editor(get_option('patreon-custom-universal-banner'),'patreon_custom_universal_banner',array('textarea_name'=>'patreon-custom-universal-banner','textarea_rows'=>5)); ?></th>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Image to show when user is not yet a patron</strong> <br>(Or not yet paying enough. Include full URL)</th>
                                        <td><input type="text" name="patreon-paywall-img-url" value="<?php echo esc_attr( get_option('patreon-paywall-img-url', '') ); ?>" class="large-text" /></td>
                                        </tr>

                                         <tr valign="top">
                                        <th scope="row"><strong>Enable Safe Patreon Embeds</strong> <br>(Will only run generic content filters over embedded content)</th>
                                        <td><input type="checkbox" name="patreon-enable-safe-patreon-embeds" value="1"<?php checked( get_option('patreon-enable-safe-patreon-embeds', false) ); ?> /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Enable 'Login with Patreon' Button in User Profile</strong></th>
                                        <td><input type="checkbox" name="patreon-enable-connect-with-patreon-in-userprofile" value="1"<?php checked( get_option('patreon-enable-connect-with-patreon-in-userprofile', false) ); ?> /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Enable 'Login with Patreon' Button on Register Page</strong></th>
                                        <td><input type="checkbox" name="patreon-enable-register-with-patreon" value="1"<?php checked( get_option('patreon-enable-register-with-patreon', false) ); ?> /></td>
                                        </tr>

                                        <tr valign="top">
                                        <th scope="row"><strong>Enable 'Login with Patreon' Button on Login Page</strong></th>
                                        <td><input type="checkbox" name="patreon-enable-login-with-patreon" value="1"<?php checked( get_option('patreon-enable-login-with-patreon', false) ); ?> /></td>
                                        </tr>

                                        <?php if(get_option('patreon-enable-login-with-patreon', false)) { ?>
                                        <tr valign="top">
                                        <th scope="row"><strong>Allow Admins/Editors to 'Login with Patreon' Button</strong></th>
                                        <td><input type="checkbox" name="patreon-enable-allow-admins-login-with-patreon" value="1"<?php checked( get_option('patreon-enable-allow-admins-login-with-patreon', false) ); ?> /></td>
                                        </tr>
                                        <?php } ?>

                                        <tr valign="top">
                                        <th scope="row"><strong>After Login/Register redirect user to specific page</strong></th>
                                        <td><input type="checkbox" name="patreon-enable-redirect-to-page-after-login" value="1"<?php checked( get_option('patreon-enable-redirect-to-page-after-login', false) ); ?> /></td>
                                        </tr>

                                        <?php if(get_option('patreon-enable-redirect-to-page-after-login', false)) { ?>
                                        <tr valign="top">
                                        <th scope="row"><strong>Page to redirect user to after Login/Register</strong></th>
                                        <td>
                                            <select name="patreon-enable-redirect-to-page-id">
                                                <?php foreach($all_pages as $page) {

                                                    $selected = ( $page->ID == get_option('patreon-enable-redirect-to-page-id', false) ? 'selected="selected"' : '' );
                                                    echo '<option value="'.$page->ID.'" '.$selected.'>'.$page->post_title.'</option>';

                                                } ?>
                                            </select>
                                        </td>
                                        </tr>
                                        <?php } ?>

                                    </table>

                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->




                    </div>
                    <!-- post-body-content -->

                    <!-- sidebar -->
                    <div id="postbox-container-1" class="postbox-container">

                        <div class="meta-box-sortables">

                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle">About Patreon Wordpress</h2>

                                <div class="inside">

                                    <p>Patreon Wordpress developed by Patreon</p>

                                    <p><strong>SUPPORT &amp; TECHNICAL HELP</strong> <br>
                                    If you require support for this plugin, you can go to <a href="https://patreon.zendesk.com/hc/en-us" target="_blank">https://patreon.zendesk.com/hc/en-us</a> and submit a ticket.</p>
                                    <p><strong>DOCUMENTATION</strong> <br>Technical documentation and code examples available @ <a href="https://patreon.com/apps/wordpress" target="_blank">https://patreon.com/apps/wordpress</a></p>
                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables -->


                    </div>
                    <!-- #postbox-container-1 .postbox-container -->

                </div>
                <!-- #post-body .metabox-holder .columns-2 -->

                <br class="clear">
            </div>
            <!-- #poststuff -->

             <?php submit_button('Update Settings', 'primary', 'submit', false); ?>

        </div> <!-- .wrap -->


        </form>

        <?php

        if(get_option('patreon-enable-login-with-patreon', false) && get_option('patreon-enable-allow-admins-login-with-patreon', false)) {

        ?>

        <div class="danger-users">
            <a name="danger-users"></a>
            <h2>DANGER USERS:</h2>
            <p>If you have the 'Login with Patreon' enabled, and allowing Admins/Editors to login, please ensure the following users have a Patreon account. <br>This will prevent your website being 'hijacked' via an authoritive Patreon account.</p>

            <table class="widefat fixed" cellspacing="0">
                <tr>
                    <th><strong>User ID</strong></th>
                    <th><strong>Username</strong></th>
                    <th><strong>Role</strong></th>
                    <th><strong>Email Address</strong></th>
                </tr>
                <tbody>
            <?php

            if(!empty($danger_user_list)) {
                $looper = 1;
                foreach($danger_user_list as $danger_username=>$danger_user) {

                    if($looper % 2 == 0) {
                        $class = 'alternate';
                    } else {
                        $class = '';
                    }

                    ?>

                    <tr class="<?php echo $class; ?>">
                        <td class="column-columnname"><?php echo $danger_user->data->ID; ?></td>
                        <td class="column-columnname"><?php echo $danger_username; ?></td>
                        <td class="column-columnname"><?php foreach($danger_user->roles as $role) { echo $role . ' '; } ?></td>
                        <td class="column-columnname"><?php echo $danger_user->data->user_email; ?></td>
                    </tr>

                    <?php

                    $looper++;

                }
            }

            ?>

                </tbody>
            </table>

        </div>

        <?php

        }


    }


}

?>
