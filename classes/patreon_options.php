<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}

class Patreon_Options {
	
    function __construct() {
		
        if( is_admin() ) {
			
            add_action( 'admin_menu', array( $this, 'patreon_plugin_setup' ) );
            add_action( 'admin_init', array( $this, 'patreon_plugin_register_settings' ) );
			
        }
		
    }

    function patreon_plugin_setup(){
		
        add_menu_page( 'Patreon Settings', 'Patreon Settings', 'manage_options', 'patreon-plugin', array( $this, 'patreon_plugin_setup_page' ), PATREON_PLUGIN_ASSETS . '/img/Patreon WordPress.png' );

		add_submenu_page ( '', 'Patreon Settings', 'Patreon Settings', 'administrator', 'patreon_wordpress_setup_wizard', array('Patreon_Wordpress', 'setup_wizard'), '/img/Patreon WordPress.png' );
		add_submenu_page( null, 'Patreon WordPress Admin Message', 'Admin message', 'manage_options', 'patreon-plugin-admin-message', array( $this, 'patreon_plugin_admin_message_page' ) );

		add_submenu_page( 'patreon-plugin', 'Patreon WordPress Health Check', 'Health check', 'manage_options', 'patreon-plugin-health', array( $this, 'patreon_plugin_health_check_page' ) );

    }

    function patreon_plugin_register_settings() { 
	
		// whitelist options
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
        register_setting( 'patreon-options', 'patreon-custom-page-name' );
		
    }
	
    function patreon_plugin_setup_page(){

        $args = array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'sort_order'     => 'asc',
                'orderby'        => 'title'
        );
        $all_pages = get_pages( $args );

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

                                <div class="handlediv" title="Click to toggle"><br /></div>
                                <!-- Toggle -->

									<h2 class="handle"><span>Patreon Connection</span></h2>
                                <div class="inside">
								
										
									<div id="patreon_options_app_details_main">
									
										<button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon-connection-details">Connection details</button><?php // Immediately inserted here to not cause any funny html rendering
										
										if (   get_option( 'patreon-client-id', false ) 
											&& get_option( 'patreon-client-secret', false ) 
											&& get_option( 'patreon-creators-access-token' , false )
											&& get_option( 'patreon-creators-refresh-token' , false )
											&& get_option( 'patreon-client-id' , false ) != ''
											&& get_option( 'patreon-client-secret' , false ) != ''
											&& get_option( 'patreon-creators-access-token' , false ) != ''
											&& get_option( 'patreon-creators-refresh-token' , false ) != ''
										) {
											?> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_reconnect patreon_options_app_details_main">Reconnect site</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_disconnect patreon_options_app_details_main">Disconnect site</button> <?php
										
										}
										
										?>
										
									</div>
									
									<div id="patreon_options_app_details_reconnect">
								
										We will now reconnect your site to Patreon. This will refresh your site's connection to Patreon. Your settings and content gating values will remain unchanged. Patron only content will become accessible to everyone until you finish reconnecting your site to Patreon.<br /><br />
										<button id="patreon_wordpress_reconnect_to_patreon" class="button button-primary button-large" target="<?php echo admin_url( 'admin.php?page=patreon-plugin&patreon_wordpress_action=disconnect_site_from_patreon_for_reconnection' ); ?>">Confirm reconnection</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_reconnect patreon_options_app_details_main">Cancel</button>
										
									</div>
									
									<div id="patreon_options_app_details_disconnect">
									
										We will now remove all info related to currently linked creator account from your site. Post gating values in your posts will be left untouched. After this, you will be able to connect this site to another creator account you have. Gated posts should keep stay gated from the nearest tier you have in the creator account you connect to this site. Patron only content will become accessible to everyone until you reconnect your site to Patreon. <br /><br />
										<button id="patreon_wordpress_disconnect_from_patreon" class="button button-primary button-large" target="<?php echo admin_url( 'admin.php?page=patreon-plugin&patreon_wordpress_action=disconnect_site_from_patreon' ); ?>">Confirm disconnection</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_disconnect patreon_options_app_details_main">Cancel</button>
										
									</div>
									
                                    <table class="widefat" id="patreon-connection-details">

                                        <tr valign="top">
											<th scope="row"><strong></strong></th>
											<td>You can find the app settings at Patreon <a href="https://www.patreon.com/platform/documentation/clients?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_app_settings_link&utm_term=" target="_blank">here</a></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><strong>Redirect URI</strong></th>
											<td><input type="text" value="<?php echo site_url() . '/patreon-authorization/'; ?>" disabled class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Client ID</strong></th>
											<td><input type="text" name="patreon-client-id" value="<?php echo esc_attr( get_option( 'patreon-client-id', '' ) ); ?>" class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Client Secret</strong></th>
											<td><input type="text" name="patreon-client-secret" value="<?php echo esc_attr( get_option( 'patreon-client-secret', '' ) ); ?>" class="large-text" /></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><strong>Creator's Access Token</strong></th>
											<td><input type="text" name="patreon-creators-access-token" value="<?php echo esc_attr( get_option( 'patreon-creators-access-token', '' ) ); ?>" class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Creator's Refresh Token</strong></th>
											<td><input type="text" name="patreon-creators-refresh-token" value="<?php echo esc_attr( get_option( 'patreon-creators-refresh-token', '' ) ); ?>" class="large-text" /></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><?php submit_button( 'Update Settings', 'primary', 'submit', false ); ?></th>
											<td></td>
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

                                <div class="handlediv" title="Click to toggle"><br /></div>
                                <!-- Toggle -->

                                <h2 class="handle"><span>Patreon Wordpress Options</span></h2>

                                <div class="inside">

                                    <table class="widefat">

                                        <tr valign="top">
											<th scope="row">
												<strong>Enable strict oAuth</strong>
												<br>
												<div class="patreon-options-info">If on, the plugin will only connect users who are already logged into your WordPress website. If off, new accounts will be created automatically for users who are logging in for the first time via Patreon. Recommended: off</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-strict-oauth" value="1"<?php checked( get_option( 'patreon-enable-strict-oauth', false ) ); ?> />
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Enable image locking features</strong>
												<br>
												<div class="patreon-options-info">If on, you will be able to lock your images and provide patron only images anywhere in your posts like webcomics or visual content. If you aren't using image locking or having complications due to your web host infrastructure, you can keep this feature off. Whenever you turn this feature on or off, you should visit 'Permalinks' settings in your WordPress site and save your permalinks just once by clicking 'Save'. Otherwise your images may appear broken.</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-file-locking" value="1"<?php checked( get_option( 'patreon-enable-file-locking', false ) ); ?> />
											</td>
                                        </tr>
										<?php
										
											$site_locking_info = '(Only Patrons at and over this pledge level will be able to see Posts)';
											$readonly 		   = '';
											if( !get_option( 'patreon-creator-id', false ) )
											{
												$site_locking_info = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url( "?page=patreon-plugin" ).'">here</a>';
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
												$<input type="text" name="patreon-lock-entire-site" value="<?php echo get_option( 'patreon-lock-entire-site' ); ?>" <?php echo $readonly ?>/>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row" colspan="2">
												<strong>Custom Call to Action Banner</strong> <br>You can show a custom Call to Action Notification and Banner (ie, "Be our Patron to see this content!") to your visitors. You can use HTML too. Leave empty to disable.<br /><br />
												<?php wp_editor( get_option( 'patreon-custom-universal-banner' ),'patreon_custom_universal_banner',array( 'textarea_name' =>'patreon-custom-universal-banner', 'textarea_rows' => 5 ) ); ?>
											</th>
                                        </tr>

                                        <tr valign="top">
											<th scope="row">
												<strong>Enable Login with Patreon</strong>
												<div class="patreon-options-info">If on, users will be able to login to your website via Patreon and view patron only posts. If off, no one will be able to login to your website via Patreon and post locking will be disabled. Recommended: on</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-login-with-patreon" value="1"<?php checked( get_option( 'patreon-enable-login-with-patreon', true ) ); ?> />
											</td>
                                        </tr>

                                        <?php if( get_option( 'patreon-enable-login-with-patreon', true ) ) { ?>
                                        <tr valign="top">
											<th scope="row">
												<strong>Allow Admins/Editors to 'Login with Patreon' Button</strong>
												<div class="patreon-options-info">If on, admins and editors will be able to login to your website login via Patreon. If off, only non admin/editor users will be able to login to your website via Patreon. Recommended: on</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-enable-allow-admins-login-with-patreon" value="1"<?php checked( get_option( 'patreon-enable-allow-admins-login-with-patreon', false ) ); ?> />
											</td>
                                        </tr>
                                        <?php } ?>
										
                                        <tr valign="top">
											<th scope="row">
												<strong>Custom Patreon Page name</strong>
												<div class="patreon-options-info">Overrides the Patreon page name in the text on locked posts. You can set a custom text for your page name with this option. If empty, the name you set for your page at Patreon is used, if you did not set a name for your page at Patreon, your first name is used.</div>
											</th>
											<td>
												<input type="text" name="patreon-custom-page-name" value="<?php echo get_option( 'patreon-custom-page-name' ); ?>" />
											</td>
                                        </tr>

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
                                    We actively support this plugin on our <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_support_link&utm_term=" target="_blank">Patreon Wordpress Support Forum</a>.</p>
                                    <p><strong>DOCUMENTATION</strong> <br>Technical documentation and code examples available @ <a href="https://patreon.com/apps/wordpress?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_about_patreon_wordpress_link&utm_term=" target="_blank">https://patreon.com/apps/wordpress</a></p>
                                </div>
								<!-- .inside -->

                            </div>
							
                            <!-- .postbox -->
                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle">Featured Third Party Addon</h2>

                               <div class="inside">
									<p><strong>Patron Pro</strong></p>

                                    <p>Get Patron Pro addon for Patreon WordPress to increase your patrons and pledges!</p><p>Enjoy powerful features like partial post locking, sneak peeks, advanced locking methods, login lock, vip users and more.<br /><br /><a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_patron_pro_link&utm_term=" target="_blank">Check out all features here</a></p>
                                </div>
								<!-- .inside -->

                            </div>
							
                            <!-- .postbox -->
                            <div class="postbox">

                                <div class="handlediv" title="Click to toggle"><br></div>
                                <!-- Toggle -->

                                <h2 class="hndle">GDPR compliance</h2>

                               <div class="inside">
									<p>Please visit <a href="<?php echo admin_url('tools.php?wp-privacy-policy-guide=1#wp-privacy-policy-guide-patreon-wordpress'); ?>">the new WordPress privacy policy recommendation page</a> and copy & paste the section related to Patreon WordPress to your privacy policy page.<br><br>You can read our easy tutorial for GDPR compliance with Patreon WordPress <a href="https://patreon.zendesk.com/hc/en-us/articles/360004198011?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_gdpr_info_link&utm_term=" target="_blank">by visiting our GDPR help page</a></p>
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

             <?php submit_button( 'Update Settings', 'primary', 'submit', false ); ?>

        </div> <!-- .wrap -->


        </form>
		<?php
		
    }
	
    function patreon_plugin_admin_message_page(){
		
		echo '<div id="patreon_setup_screen">';
	
			echo '<div id="patreon_setup_logo"><img src="' . PATREON_PLUGIN_ASSETS . '/img/Patreon_Logo_100.png" /></div>';
			
			// Put some defaults so sites with warnings on will be fine
			$heading = 'patreon_admin_message_default_title';
			$content = 'patreon_admin_message_default_content';
			
			if ( isset( $_REQUEST['patreon_admin_message_title'] ) ) {
				$heading = $_REQUEST['patreon_admin_message_title'];
			}
			if ( isset( $_REQUEST['patreon_admin_message_content'] ) ) {
				$content = $_REQUEST['patreon_admin_message_content'];
			}

			$heading = Patreon_Frontend::$messages_map[ $heading ];
			$content = Patreon_Frontend::$messages_map[ $content ];
			
			echo '<div id="patreon_setup_content"><h1 style="margin-top: 0px;">' . $heading . '</h1><div id="patreon_setup_message">' . $content . '</div></div>';
		
			echo '</div>';
			
	}
	
    function patreon_plugin_health_check_page(){

		?>
		<div class="patreon_admin_health_content_section">
			<h1>Health check of your Patreon integration</h1>
			Below are settings or issues which may affect your Patreon integration. Please check the recommendations and implement them to have your integration function better. You can get help for any of these items <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support" target="_blank">by visiting our support forum</a> and posting a thread. 
			<br><br>
			You can <a href="" id="patreon_copy_health_check_output">click here</a> to copy the output of this page to share with support team or post it in the forum. <div id="patreon_copied"></div>
			
		</div>
		<?php
		
		if ( count( Patreon_Compatibility::$site_health_info ) == 0 ) {
		?>
		
		<div class="patreon_admin_health_content_box">
		<h2>Your Patreon integration health is great!</h2>
		</div>
		
		<?php
			
		}
		
		if ( count( Patreon_Compatibility::$site_health_info ) > 0 ) {
			
			$health_info = Patreon_Compatibility::$site_health_info;
			
			// Sort according to priority
			
			usort( $health_info, function($a, $b ) {
				return $a['order'] - $b['order'];
			} );
			
			// Add last 50 connection errors at the end.
			
	
			foreach ( $health_info as $key => $value ) {
			?>
			
				<div class="patreon_admin_health_content_box">
					<div class="patreon_toggle_admin_sections" target=".patreon_admin_health_content_box_hidden"><h3><?php echo $health_info[$key]['heading'] ?><span class="dashicons dashicons-arrow-down-alt2 patreon_setting_section_toggle_icon"></span></h3></div>
					<div class="patreon_admin_health_content_box_hidden"><?php echo $health_info[$key]['notice'] ?></div>
				</div>
			
			<?php
		
			}
		
		}

		// Print out the last 50 connection errors if they exist
		?>
		
		<div class="patreon_admin_health_content_box">
			<div class="patreon_toggle_admin_sections" target=".patreon_admin_health_content_box_hidden"><h3><?php echo PATREON_LAST_50_CONNECTION_ERRORS_HEADING ?><span class="dashicons dashicons-arrow-down-alt2 patreon_setting_section_toggle_icon"></span></h3></div>
			<div class="patreon_admin_health_content_box_hidden"><?php echo PATREON_LAST_50_CONNECTION_ERRORS ?>
				<?php 
					$last_50_conn_errors = get_option( 'patreon-last-50-conn-errors', array() );
					
					if ( count( $last_50_conn_errors ) > 0 ) {
						
						foreach ( $last_50_conn_errors as $key => $value ) {
							
				
							$days = abs( time() - $last_50_conn_errors[$key]['date']  ) / 86400 ;

							echo '<br /><br /><b>' . round( $days, 2 ) . ' days ago</b><br /><br />';
							echo $last_50_conn_errors[$key]['error'];
							
						}

					}
					else {
						echo '<br /><br />No recent connection errors<br />';
					}
				?>
			</div>
			</div>
			
		<?php
		
				
		// Output a hidden, non formatted version of the health info to be used by the users to c/p to support
		?>
		
		<div id="patreon_health_check_output_for_support">
			<?php			
			if ( isset( $health_info ) AND is_array( $health_info ) AND count( $health_info ) > 0 ) {
				foreach ( $health_info as $key => $value ) {
				 echo "\r\n";
				 echo '# '.$health_info[$key]['heading'].' #';
				 echo "\r\n";
				 echo str_replace( '<h3>', "\r\n# ", str_replace( '</h3>', " #\r\n", $health_info[$key]['notice'] ) );			
				}
			}
		?>
		</div>
		
		<?php
		
    }
	
}