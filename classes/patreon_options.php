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
        }
    }

    function patreon_plugin_setup(){
        add_menu_page( 'Patreon Settings', 'Patreon Settings', 'manage_options', 'patreon-plugin', array($this, 'patreon_plugin_setup_page'), PATREON_PLUGIN_ASSETS.'/img/Patreon WordPress.png' );
    }

    function patreon_plugin_register_settings() { // whitelist options
        register_setting( 'patreon-options', 'patreon-client-id' );
        register_setting( 'patreon-options', 'patreon-client-secret' );
        register_setting( 'patreon-options', 'patreon-creators-access-token' );
        register_setting( 'patreon-options', 'patreon-creators-refresh-token' );
        register_setting( 'patreon-options', 'patreon-fetch-creator-id' );
        register_setting( 'patreon-options', 'patreon-paywall-img-url' );
        register_setting( 'patreon-options', 'patreon-paywall-blocked-img-url' );
        register_setting( 'patreon-options', 'patreon-rewrite-rules-flushed' );
        register_setting( 'patreon-options', 'patreon-can-use-api-v2' );
        register_setting( 'patreon-options', 'patreon-enable-register-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-login-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-allow-admins-login-with-patreon' );
        register_setting( 'patreon-options', 'patreon-enable-redirect-to-page-after-login' );
        register_setting( 'patreon-options', 'patreon-enable-redirect-to-page-id' );
        register_setting( 'patreon-options', 'patreon-protect-default-image-patreon-level' );
        register_setting( 'patreon-options', 'patreon-enable-file-locking');
        register_setting( 'patreon-options', 'patreon-enable-strict-oauth' );
        register_setting( 'patreon-options', 'patreon-lock-entire-site' );
        register_setting( 'patreon-options', 'patreon-custom-universal-banner' );
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

                                <h2 class="hndle"><span>API Settings</span></h2>

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

                                <h2 class="hndle"><span>Patreon Wordpress Options</span></h2>

                                <div class="inside">

                                    <table class="widefat">

                                        <tr valign="top">
											<th scope="row">
												<strong>Enable strict oAuth</strong>
												<br>
												<div class="patreon-options-info">If on, the plugin will only connect users who are already logged into your WordPress website. If off, new accounts will be created automatically for users who are logging in for the first time via Patreon. Recommended: off</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-strict-oauth" value="1"<?php checked( get_option('patreon-enable-strict-oauth', false) ); ?> />
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Enable image locking features</strong>
												<br>
												<div class="patreon-options-info">If on, you will be able to lock your images and provide patron only images anywhere in your posts like webcomics or visual content. If you aren't using image locking or having complications due to your web host infrastructure, you can keep this feature off. Whenever you turn this feature on or off, you should visit 'Permalinks' settings in your WordPress site and save your permalinks just once by clicking 'Save'. Otherwise your images may appear broken.</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-file-locking" value="1"<?php checked( get_option('patreon-enable-file-locking', false) ); ?> />
											</td>
                                        </tr>
										<?php
										
											$site_locking_info = '(Only Patrons at and over this pledge level will be able to see Posts)';
											$readonly = '';
											if(!get_option('patreon-creator-id', false))
											{
												$site_locking_info = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url("?page=patreon-plugin").'">here</a>';
												$readonly = " readonly";
											}
											?>										
                                        <tr valign="top">
											<th scope="row">
												<strong>Make entire site Patron-only with Pledge Level</strong>
												<br>
												<?php echo $site_locking_info ?>
											</th>
											<td>
												$<input type="text" name="patreon-lock-entire-site" value="<?php echo get_option('patreon-lock-entire-site'); ?>" <?php echo $readonly ?>/>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row" colspan="2">
												<strong>Custom Call to Action Banner</strong> <br>You can show a custom Call to Action Notification and Banner (ie, "Be our Patron to see this content!") to your visitors. You can use HTML too. Leave empty to disable.<br /><br />
												<?php wp_editor(get_option('patreon-custom-universal-banner'),'patreon_custom_universal_banner',array('textarea_name'=>'patreon-custom-universal-banner','textarea_rows'=>5)); ?>
											</th>
                                        </tr>

                                        <tr valign="top">
											<th scope="row">
												<strong>Enable Login with Patreon</strong>
												<div class="patreon-options-info">If on, users will be able to login to your website via Patreon and view patron only posts. If off, no one will be able to login to your website via Patreon and post locking will be disabled. Recommended: on</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-login-with-patreon" value="1"<?php checked( get_option('patreon-enable-login-with-patreon', true) ); ?> />
											</td>
                                        </tr>

                                        <?php if(get_option('patreon-enable-login-with-patreon', true)) { ?>
                                        <tr valign="top">
											<th scope="row">
												<strong>Allow Admins/Editors to 'Login with Patreon' Button</strong>
												<div class="patreon-options-info">If on, admins and editors will be able to login to your website login via Patreon. If off, only non admin/editor users will be able to login to your website via Patreon. Recommended: on</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-allow-admins-login-with-patreon" value="1"<?php checked( get_option('patreon-enable-allow-admins-login-with-patreon', false) ); ?> />
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
                                    We actively support this plugin on our <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support" target="_blank">Patreon Wordpress Support Forum</a>.</p>
                                    <p><strong>DOCUMENTATION</strong> <br>Technical documentation and code examples available @ <a href="https://patreon.com/apps/wordpress" target="_blank">https://patreon.com/apps/wordpress</a></p>
                                </div>
								<!-- .inside -->

                            </div>
							
                            <!-- .postbox -->
                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle">GDPR compliance</h2>

                               <div class="inside">
									<p>Please visit <a href="<?php echo admin_url('tools.php?wp-privacy-policy-guide=1#wp-privacy-policy-guide-patreon-wordpress'); ?>">the new WordPress privacy policy recommendation page</a> and copy & paste the section related to Patreon WordPress to your privacy policy page.<br><br>You can read our easy tutorial for GDPR compliance with Patreon WordPress <a href="https://patreon.zendesk.com/hc/en-us/articles/360004198011" target="_blank">by visiting our GDPR help page</a></p>
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
    }
}

?>
