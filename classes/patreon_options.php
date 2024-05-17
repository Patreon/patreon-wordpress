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

		add_submenu_page( '', 'Patreon Settings', 'Patreon Settings', 'administrator', 'patreon_wordpress_setup_wizard', array('Patreon_Wordpress', 'setup_wizard') );
		
		add_submenu_page( '', 'Patreon WordPress Admin Message', 'Admin message', 'manage_options', 'patreon-plugin-admin-message', array( $this, 'patreon_plugin_admin_message_page' ) );

		add_submenu_page( 'patreon-plugin', 'Patreon WordPress Post Sync', 'Post Sync', 'manage_options', 'patreon_wordpress_setup_wizard&setup_stage=post_sync_1', array( $this, 'patreon_plugin_post_sync_page' ) );
		
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
        register_setting( 'patreon-options', 'patreon-lock-entire-site', array(&$this, 'site_locking_value') );
        register_setting( 'patreon-options', 'patreon-custom-universal-banner' );
        register_setting( 'patreon-options', 'patreon-custom-page-name', array(&$this, 'sanitize_page_name') );
        register_setting( 'patreon-options', 'patreon-prevent-caching-gated-content' );
        register_setting( 'patreon-options', 'patreon-currency-sign' );
        register_setting( 'patreon-options', 'patreon-currency-sign-behind' );
        register_setting( 'patreon-options', 'patreon-sync-posts' );
        register_setting( 'patreon-options', 'patreon-remove-deleted-posts' );
        register_setting( 'patreon-options', 'patreon-update-posts' );
        register_setting( 'patreon-options', 'patreon-post-author-for-synced-posts' );
        register_setting( 'patreon-options', 'patreon-hide-login-button' );
        register_setting( 'patreon-options', 'patreon-set-featured-image' );
        register_setting( 'patreon-options', 'patreon-auto-publish-public-posts' );
        register_setting( 'patreon-options', 'patreon-auto-publish-patron-only-posts' );
        register_setting( 'patreon-options', 'patreon-override-synced-post-publish-date' );
		
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
                    
								<h2 class="handle patreon_wordpress_option_heading"><span>Patreon Connection</span></h2>
                                <div class="inside">
									
									<div id="patreon_options_app_details_main">
									
										<button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon-connection-details" aria-label="Patreon connection details">Connection details</button><?php // Immediately inserted here to not cause any funny html rendering
										
										if (   
											( !get_option( 'patreon-client-id', false ) OR get_option( 'patreon-client-id' , false ) == '' ) AND
											( !get_option( 'patreon-client-secret', false ) OR get_option( 'patreon-client-secret' , false ) == '' ) AND
											( !get_option( 'patreon-creators-access-token', false ) OR get_option( 'patreon-creators-access-token' , false ) == '' ) AND
											( !get_option( 'patreon-creators-refresh-token', false ) OR get_option( 'patreon-creators-refresh-token' , false ) == '' )
										) {
											?> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_connect patreon_options_app_details_main"  aria-label="Connect your site to Patreon">Connect site</button> 
											<?php
										
										}
																			
										
										if (   get_option( 'patreon-client-id', false ) 
											&& get_option( 'patreon-client-secret', false ) 
											&& get_option( 'patreon-creators-access-token' , false )
											&& get_option( 'patreon-creators-refresh-token' , false )
											&& get_option( 'patreon-client-id' , false ) != ''
											&& get_option( 'patreon-client-secret' , false ) != ''
											&& get_option( 'patreon-creators-access-token' , false ) != ''
											&& get_option( 'patreon-creators-refresh-token' , false ) != ''
										) {
											?> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_reconnect patreon_options_app_details_main" aria-label="Connect or Reconnect your site to Patreon">(re)Connect site</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_disconnect patreon_options_app_details_main" aria-label="Disconnect your site from Patreon">Disconnect site</button> <?php
										
										}
										
										?>
										
									</div>
									
									<div id="patreon_options_app_details_connect">
								
										We will now connect your site to Patreon by running connection wizard. Before starting, please make sure you deleted any existing app for this site in <a href="https://www.patreon.com/portal/registration/register-clients" target="_blank">this page at Patreon</a><br /><br />
										<button id="patreon_wordpress_reconnect_to_patreon" class="button button-primary button-large" target="<?php echo admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=0'); ?>"  aria-label="Start connection wizard">Start connection wizard</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_connect patreon_options_app_details_main">Cancel</button>
										
									</div>
									<div id="patreon_options_app_details_reconnect">
								
										We will now reconnect your site to Patreon. This will refresh your site's connection to Patreon. Your settings and content gating values will remain unchanged. Patron only content will become accessible to everyone until you finish reconnecting your site to Patreon.<br /><br />
										<button id="patreon_wordpress_disconnect_reconnect_to_patreon" class="button button-primary button-large" target="<?php echo admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_0&patreon_wordpress_reconnect_to_patreon_nonce=' . wp_create_nonce() ); ?>"  aria-label="Confirm connection">Confirm</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_reconnect patreon_options_app_details_main" aria-label="Cancel">Cancel</button>
										
									</div>
									
									<div id="patreon_options_app_details_disconnect">
									
										We will now remove all info related to currently linked creator account from your site. Post gating values in your posts will be left untouched. After this, you will be able to connect this site to another creator account you have. Gated posts should keep stay gated from the nearest tier you have in the creator account you connect to this site. Patron only content will become accessible to everyone until you reconnect your site to Patreon. <br /><br />
										<button id="patreon_wordpress_disconnect_from_patreon" class="button button-primary button-large"  aria-label="Confirm disconnection" target="<?php echo admin_url( 'admin.php?page=patreon-plugin&patreon_wordpress_action=disconnect_site_from_patreon&patreon_wordpress_disconnect_from_patreon_nonce=' . wp_create_nonce() ); ?>">Confirm disconnection</button> <button class="button button-primary button-large patreon_wordpress_interface_toggle" toggle="patreon_options_app_details_disconnect patreon_options_app_details_main" aria-label="Cancel">Cancel</button>
										
									</div>
									
                                    <table class="widefat" id="patreon-connection-details">

                                        <tr valign="top">
											<th scope="row"><strong></strong></th>
											<td>You can find the app settings at Patreon <a href="https://www.patreon.com/platform/documentation/clients?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_app_settings_link&utm_term=" target="_blank" aria-label="Visit app settings at Patreon. Not necessary if you already set up your connection">here</a></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><strong>Redirect URI</strong></th>
											<td><input type="text" value="<?php echo site_url() . '/patreon-authorization/'; ?>" disabled class="large-text"  aria-label="Redirect uri" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Client ID</strong></th>
											<td><input type="text" name="patreon-client-id" value="<?php echo esc_attr( get_option( 'patreon-client-id', '' ) ); ?>" class="large-text"  aria-label="Client ID" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Client Secret</strong></th>
											<td><input type="text" name="patreon-client-secret" value="<?php echo esc_attr( get_option( 'patreon-client-secret', '' ) ); ?>" class="large-text" /></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><strong>Creator's Access Token</strong></th>
											<td><input type="text" name="patreon-creators-access-token"  aria-label="Creator access token" value="<?php echo esc_attr( get_option( 'patreon-creators-access-token', '' ) ); ?>" class="large-text" /></td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row"><strong>Creator's Refresh Token</strong></th>
											<td><input type="text" name="patreon-creators-refresh-token"  aria-label="Creator refresh token" value="<?php echo esc_attr( get_option( 'patreon-creators-refresh-token', '' ) ); ?>" class="large-text" /></td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row"><?php submit_button( 'Update Settings', 'primary', 'submit_second', false ); ?></th>
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

                                <h2 class="handle patreon_wordpress_option_heading"><span>Getting support</span></h2>

                                <div class="inside">

									Get support at <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support/11" target="_blank"  aria-label="Visit the official forum to get support">the official forum</a> by copying your plugin info below and adding to your support thread by pasting to share with support team.<br /><div id="patreon_copied"></div>
									<button class="button button-primary button-large" id="patreon_copy_health_check_output">Copy</button>
									<div id="patreon_health_check_output_for_support">WP <?php echo get_bloginfo( 'version' ); ?> with PHP <?php echo phpversion(); echo "\r\n"; ?>Patreon WordPress <?php echo PATREON_WORDPRESS_VERSION ?> with API v<?php echo get_option( 'patreon-installation-api-version', false ); echo "\r\n";

											// Conditionals for any addons / other relevant Patreon plugins

											if ( array_key_exists( 'cb_p6_a1', $GLOBALS ) ) {
												global $cb_p6_a1;					
											}
											
											if ( isset( $cb_p6_a1 ) ) {
												?>Patron Plugin Pro <?php echo $cb_p6_a1->internal['version']; echo "\r\n";
											}
											
											if ( array_key_exists( 'cb_p6', $GLOBALS ) ) {
												global $cb_p6;					
											}
											
											if ( isset( $cb_p6 ) ) {
												?>Patreon Button, Widgets and Plugin <?php echo $cb_p6->internal['version']; echo "\r\n";
											}
											
											if ( isset( $health_info ) AND is_array( $health_info ) AND count( $health_info ) > 0 ) {
												foreach ( $health_info as $key => $value ) {
												 echo "\r\n";
												 echo '# '.$health_info[$key]['heading'].' #';
												 echo "\r\n";
												 echo str_replace( '<h3>', "\r\n# ", str_replace( '</h3>', " #\r\n", $health_info[$key]['notice'] ) );			
												}
											}
										?></div>
									
                                </div>
								<?php
								if ( !Patreon_Wordpress::check_plugin_exists('patron-plugin-pro') ) {
									?>
				
									<!-- .inside -->
									<h2 class="handle patreon_wordpress_option_heading"><span>Get Patron Pro addon to boost your patrons & pledges</span></h2>

									<div class="inside">

										Install <a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress?utm_source=<?php urlencode( site_url())?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=patreon_wordpress_settings_page_insert_link&utm_term="  aria-label="Upgrade to Patron Plugin Pro here">Patron Pro Addon</a> to boost your pledges and patrons by using advanced locking methods, sneak peeks, partial post locking, VIP and custom level members, login lock and many other powerful features. <br /> <br />
										<button class="button button-primary button-large" id="patreon_patron_pro_upsell" go_to_url="https://codebard.com/patron-pro-addon-for-patreon-wordpress?utm_source=<?php urlencode( site_url())?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=patreon_wordpress_settings_page_insert_button&utm_term="  aria-label="Download">Download</button>
										
									</div>
									<!-- .inside -->
				
									<?php
			
								}
								else {
									?>
									<!-- .inside -->
									<h2 class="handle patreon_wordpress_option_heading"><span>Use advanced locking methods</span></h2>

									<div class="inside">
										<p> Use <a href="https://codebard.com/patron-plugin-pro-documentation/category/manual?utm_source=<?php urlencode( site_url())?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=patreon_wordpress_patreon_options_callout&utm_term=" target="_blank" aria-label="Upgrade to Patron Plugin Pro here">Patron Pro's advanced locking methods</a> to fine-tune your content gating through your <a href="https://patron-plugin-pro-demo.codebard.com/" aria-label="Check out Patron Plugin Pro demo and see advanced locking settings">Patron Pro options admin menu.</a></p>
									</div>
									<?php
								}
								?>

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->
		
										
                        <!-- .meta-box-sortables .ui-sortable -->

                        <div class="meta-box-sortables ui-sortable">

                            <div class="postbox">

                                <!-- Toggle -->

                                <h2 class="handle patreon_wordpress_option_heading"><span>Patreon Wordpress Options</span></h2>

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
												<div class="patreon-options-info">If on, you will be able to lock your images and provide patron only images anywhere in your posts like webcomics or visual content. This may use noticeable server resources at your web host. If you aren't using image locking or having complications due to your web host infrastructure, you turn this feature off. Whenever you turn this feature on or off, you should visit 'Permalinks' settings in your WordPress site and save your permalinks just once by clicking 'Save'. Otherwise your images may appear broken.</div>
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
												<strong>Hide 'Login with Patreon' Button</strong>
												<div class="patreon-options-info">If on, 'Login with Patreon' button will be hidden from WP login page and WP login forms. Users can still log in or unlock via Patreon using unlock buttons. Recommended: off</div>
											</th>
											<td>
												<input type="checkbox" name="patreon-hide-login-button" value="1"<?php checked( get_option( 'patreon-hide-login-button', false ) ); ?> />
											</td>
                                        </tr>
                                    
										
                                        <tr valign="top">
											<th scope="row">
												<strong>Custom Patreon Page name</strong>
												<div class="patreon-options-info">Overrides the Patreon page name in the text on locked posts. You can set a custom text for your page name with this option. If empty, the name you set for your page at Patreon is used, if you did not set a name for your page at Patreon, your first name is used.</div>
											</th>
											<td>
												<input type="text" name="patreon-custom-page-name" value="<?php echo get_option( 'patreon-custom-page-name' ); ?>" />
											</td>
                                        </tr>

                                        <tr valign="top">
											<th scope="row">
												<strong>Prevent caching of gated content</strong>
												<div class="patreon-options-info">If Yes, PW will try to prevent gated content from being cached in order to prevent users from still seeing cached version of gated content after unlocking it. Recommended: Yes</div>
											</th>
											<td>
												<?php
																									
													$prevent_caching_selected = '';
													$do_not_prevent_caching_selected = '';
													
													if ( get_option( 'patreon-prevent-caching-gated-content', 'yes' ) == 'yes' ) {
														$prevent_caching_selected = " selected";
													}
													else {
														$do_not_prevent_caching_selected = " selected";
													}
												
												?>
												<select name="patreon-prevent-caching-gated-content">
													<option value="yes" <?php echo $prevent_caching_selected; ?>>Yes</option>
													<option value="no" <?php echo $do_not_prevent_caching_selected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Currency sign front</strong>
												<div class="patreon-options-info">You can set the currency sign to match your campaign currency here. This will be used in gated posts to show the pledge amount needed to unlock the post. You can put spaces if your currency needs spacing.</div>
											</th>
											<td>

												<input type="text" name="patreon-currency-sign" id="patreon-currency-sign" value="<?php echo esc_attr( get_option( 'patreon-currency-sign', '$' ) ); ?>" />
											</td>
                                        </tr>
										
                                        <tr valign="top">
											<th scope="row">
												<strong>Currency sign behind</strong>
												<div class="patreon-options-info">If your currency is one that has its sign behind the amount, you can set the currency sign here. This will be used in gated posts to show the pledge amount needed to unlock the post.  You can put spaces if your currency needs spacing.</div>
											</th>
											<td>

												<input type="text" name="patreon-currency-sign-behind" id="patreon-currency-sign-behind" value="<?php echo esc_attr( get_option( 'patreon-currency-sign-behind', '' ) ); ?>" />
											</td>
                                        </tr>
										
										<?php
										
											$api_version_warning = '';
											
											if ( get_option( 'patreon-installation-api-version', 2 ) == '1' ) {
												$api_version_warning = '<div id="patreon_api_version_warning" class="patreon_api_version_warning_inside_options"><div class="patreon_api_version_warning_important">' . PATREON_WARNING_IMPORTANT . '</div>' . PATREON_API_VERSION_WARNING . '</div>';
											}
										?>
										
                                        <tr valign="top">
											<th scope="row">
												<strong>Sync Patreon posts</strong>
												<div class="patreon-options-info">If Yes, the plugin will sync your posts from Patreon to your WP site on an ongoing basis. Recommended: Yes
												<?php echo $api_version_warning; ?></div>
											</th>
											<td>
												<?php
														
													$sync_posts_selected = '';
													$sync_posts_unselected = '';
													
													if ( get_option( 'patreon-sync-posts', 'no' ) == 'yes' ) {
														$sync_posts_selected = " selected";
													}
													else {
														$sync_posts_unselected = " selected";
													}
												
												?>
												<select name="patreon-sync-posts">
													<option value="yes" <?php echo $sync_posts_selected; ?>>Yes</option>
													<option value="no" <?php echo $sync_posts_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Post type and category for synced posts</strong>
												<div class="patreon-options-info">Set which post type and category/taxonomy will be used for synced posts.
												<div id="patreon_select_post_sync_category">
													<?php
														global $Patreon_Wordpress;
														
														$sync_post_type     = get_option( 'patreon-sync-post-type', 'post' );
														$sync_post_category = get_option( 'patreon-sync-post-category', 'category' );
														$sync_post_term     = get_option( 'patreon-sync-post-term', '1' );
															
														$post_type_select = $Patreon_Wordpress->make_post_type_select( $sync_post_type );
														$taxonomy_select  = $Patreon_Wordpress->make_taxonomy_select( $sync_post_type, $sync_post_category );
														$term_select      = $Patreon_Wordpress->make_term_select( $sync_post_type, $sync_post_category, $sync_post_term );
														$post_sync_category_status_color = "9d9d9d";
														
													
													?>
													<select name="patreon_sync_post_type" id="patreon_sync_post_type" style="display: inline-block; margin-right: 5px;">
														<?php echo $post_type_select ?>
													</select>
													<select  name="patreon_sync_post_category" id="patreon_sync_post_category" style="display: inline-block; margin-right: 5px;">
														<?php echo $taxonomy_select ?>
													</select>
													<select  name="patreon_sync_post_term" id="patreon_sync_post_term" style="display: inline-block; margin-right: 5px;">
														<?php echo $term_select ?>
													</select>
													<button id="patreon_wordpress_save_post_sync_category" patreon_wordpress_nonce_save_post_sync_options="<?php echo wp_create_nonce() ?>" class="button button-primary button-large" pw_input_target="#patreon_wordpress_post_import_category_status" target="">Save</button><div id="patreon_wordpress_post_import_category_status" style="color: #<?php echo $post_sync_category_status_color ?>;"></div>
												</div>
												
												</div>
											</th>
											<td>
												
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Auto publish public posts</strong>
												<div class="patreon-options-info">Whether to auto publish public posts or leave them as draft so you can control when and how they are published</div>
											</th>
											<td>
												<?php
													
													$auto_publish_public_posts_selected = '';
													$auto_publish_public_posts_unselected = '';
													
													if ( get_option( 'patreon-auto-publish-public-posts', 'yes' ) == 'yes' ) {
														$auto_publish_public_posts_selected = " selected";
													}
													else {
														$auto_publish_public_posts_unselected = " selected";
													}
												
												?>
												<select name="patreon-auto-publish-public-posts">
													<option value="yes" <?php echo $auto_publish_public_posts_selected; ?>>Yes</option>
													<option value="no" <?php echo $auto_publish_public_posts_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Auto publish patron only posts</strong>
												<div class="patreon-options-info">Whether to auto publish patron-only posts or leave them as draft so you can control when and how they are published</div>
											</th>
											<td>
												<?php
																										
													$auto_publish_patron_only_posts_selected = '';
													$auto_publish_patron_only_posts_unselected = '';
													
													if ( get_option( 'patreon-auto-publish-patron-only-posts', 'yes' ) == 'yes' ) {
														$auto_publish_patron_only_posts_selected = " selected";
													}
													else {
														$auto_publish_patron_only_posts_unselected = " selected";
													}
												
												?>
												<select name="patreon-auto-publish-patron-only-posts">
													<option value="yes" <?php echo $auto_publish_patron_only_posts_selected; ?>>Yes</option>
													<option value="no" <?php echo $auto_publish_patron_only_posts_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Author for synced posts</strong>
												<div class="patreon-options-info">Choose the author to be used in synced posts. This will only affect newly imported posts</div>
											</th>
											<td>
												<?php
													
													$post_author_for_synced_posts = get_option( 'patreon-post-author-for-synced-posts', 1 );
													$user_select = $Patreon_Wordpress->make_user_select( $post_author_for_synced_posts );
													
												?>
												<select id="patreon_post_author_for_synced_posts" name="patreon-post-author-for-synced-posts" pw_input_target="#patreon_wordpress_post_author_status" patreon_wordpress_set_post_author_for_post_sync_nonce="<?php echo wp_create_nonce() ?>">
													<?php echo $user_select ?>
												</select><div id="patreon_wordpress_post_author_status"></div>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Override imported post publish dates</strong>
												<div class="patreon-options-info">If 'Yes', this will override the local imported posts' publish dates with the publish dates from Patreon</div>
											</th>
											<td>
												<?php
																										
													$override_post_publish_dates_from_patreon_selected = '';
													$override_post_publish_dates_from_patreon_unselected = '';
													
													if ( get_option( 'patreon-override-synced-post-publish-date', 'no' ) == 'yes' ) {
														$override_post_publish_dates_from_patreon_selected = " selected";
													}
													else {
														$override_post_publish_dates_from_patreon_unselected = " selected";
													}
												
												?>
												<select name="patreon-override-synced-post-publish-date">
													<option value="yes" <?php echo $override_post_publish_dates_from_patreon_selected; ?>>Yes</option>
													<option value="no" <?php echo $override_post_publish_dates_from_patreon_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Update local posts from the ones at Patreon</strong>
												<div class="patreon-options-info">If Yes, the plugin will update local imported/matched posts with the post content at Patreon. If you have extra formatting in your <i>local</i> posts, they will be overwritten with the formatting of the Patreon posts. Recommended: Yes</div>
											</th>
											<td>
												<?php

													$update_posts_selected = '';
													$update_posts_unselected = '';
													
													if ( get_option( 'patreon-update-posts', 'no' ) == 'yes' ) {
														$update_posts_selected = " selected";
													}
													else {
														$update_posts_unselected = " selected";
													}
												
												?>
												<select name="patreon-update-posts">
													<option value="yes" <?php echo $update_posts_selected; ?>>Yes</option>
													<option value="no" <?php echo $update_posts_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Delete local posts when Patreon post is deleted</strong>
												<div class="patreon-options-info">If Yes, the plugin will delete local imported/matched posts when you delete a post at Patreon. Recommended: No</div>
											</th>
											<td>
												<?php
																									
													$delete_posts_selected = '';
													$delete_posts_unselected = '';
													
													if ( get_option( 'patreon-remove-deleted-posts', 'no' ) == 'yes' ) {
														$delete_posts_selected = " selected";
													}
													else {
														$delete_posts_unselected = " selected";
													}
												
												?>
												<select name="patreon-remove-deleted-posts">
													<option value="yes" <?php echo $delete_posts_selected; ?>>Yes</option>
													<option value="no" <?php echo $delete_posts_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<strong>Set featured image from imported images</strong>
												<div class="patreon-options-info">If Yes, the plugin will set the first image in an imported post as the featured image for the post. Recommended: Yes</div>
											</th>
											<td>
												<?php
																									
													$set_post_featured_image_selected = '';
													$set_post_featured_image_unselected = '';
													
													if ( get_option( 'patreon-set-featured-image', 'no' ) == 'yes' ) {
														$set_post_featured_image_selected = " selected";
													}
													else {
														$set_post_featured_image_unselected = " selected";
													}
												
												?>
												<select name="patreon-set-featured-image">
													<option value="yes" <?php echo $set_post_featured_image_selected; ?>>Yes</option>
													<option value="no" <?php echo $set_post_featured_image_unselected; ?>>No</option>
												</select>
											</td>
                                        </tr>
                                        <tr valign="top">
											<th scope="row">
												<?php
													
													$post_import_status = 'No post import ongoing';
													$post_import_status_color = "9d9d9d";
													$post_import_cancel = '';
													$post_import_button = '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="" patreon_wordpress_nonce_post_sync="' . wp_create_nonce('patreon_wordpress_nonce_post_sync') .'">Start an import</button>';
													$import_post_info_text = "Start an import of your posts from Patreon if you haven't done it before. After import of existing posts is complete, new posts will automatically be imported and existing posts automatically updated so you don't need to do this again.";
													$import_post_info_header = "Start a post import";
													
													if ( get_option( 'patreon-post-import-in-progress', false ) ) {
														$post_import_status = "There is an ongoing post import";
														$post_import_status_color = "129500";
														$post_import_button = '<button id="patreon_wordpress_import_next_batch_of_posts" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="" style="margin-right: 10px;">Import next batch</button>';
														$post_import_cancel = '<button id="patreon_wordpress_cancel_manual_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Cancel</button>';
														$import_post_info_text = "Posts will be imported automatically every 5 minutes. If they are not, or you want to do it faster, click to import next batch of posts. This will import the next batch of posts in the queue. You can do this every 10 seconds.";
														$import_post_info_header = "Ongoing post import";
													}
													
													$api_version    = get_option( 'patreon-installation-api-version', '1' );
													$sync_posts     = get_option( 'patreon-sync-posts', 'no' );
															
													if ( $api_version != '2' AND $sync_posts == 'yes' ) {
														$post_import_status = 'Cant import posts - Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>';
														$post_import_status_color = "f31d00";
													}	
													
												?>
												<strong id="post_import_status_heading"><?php echo $import_post_info_header; ?></strong>
												<div class="patreon-options-info"><div id="post_import_info_text"><?php echo $import_post_info_text; ?></div><div id="patreon_wp_post_import_status" style="color: #<?php echo $post_import_status_color ?>;"><?php echo $post_import_status; ?></div></div>
											</th>
											<td>
												
												<div id="patreon_post_import_button_container"><?php echo $post_import_button; ?><?php echo $post_import_cancel; ?></div>
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

                                <!-- Toggle -->

                                <h2 class="handle patreon_wordpress_option_heading">About Patreon Wordpress</h2>

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

                                <!-- Toggle -->

                                <h2 class="handle patreon_wordpress_option_heading">Featured Third Party Addon</h2>

                               <div class="inside">
									<p><strong>Patron Pro</strong></p>

                                    <p>Get Patron Pro addon for Patreon WordPress to increase your patrons and pledges!</p><p>Enjoy powerful features like partial post locking, sneak peeks, advanced locking methods, login lock, vip users and more.<br /><br /><a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress?utm_source=<?php urlencode( site_url() ) ?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=settings_screen_patron_pro_link&utm_term=" target="_blank">Check out all features here</a></p>
                                </div>
								<!-- .inside -->

                            </div>
							
                            <!-- .postbox -->
                            <div class="postbox">

                                <!-- Toggle -->

                                <h2 class="handle patreon_wordpress_option_heading">GDPR compliance</h2>

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
	
    function patreon_plugin_post_sync_page(){
		// For now, dud to prevent any PHP notices when going to post sync wizard from admi menu. Can be expanded later.
		return;		
	}
    function patreon_plugin_health_check_page(){

		?>
		<div class="patreon_admin_health_content_section">
			<h1>Health check of your Patreon integration</h1>
			Below are settings or issues which may affect your Patreon integration. Please check the recommendations and implement them to have your integration function better. You can get help for any of these items <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support" target="_blank">by visiting our support forum</a> and posting a thread. 
			<br><br>
			Your site is using:<br /><br /> WP <?php echo get_bloginfo( 'version' ); ?> with PHP <?php echo phpversion(); ?><br />
			Patreon WordPress <?php echo PATREON_WORDPRESS_VERSION ?> with API v<?php echo get_option( 'patreon-installation-api-version', false ) ?><br />
			
			<?php
			
				// Conditionals for any addons / other relevant Patreon plugins
				
				if ( array_key_exists( 'cb_p6_a1', $GLOBALS ) ) {
					global $cb_p6_a1;					
				}
				
				if ( isset( $cb_p6_a1 ) ) {
					
					?>Patron Plugin Pro <?php echo $cb_p6_a1->internal['version'] ?><br /><?php
					
				}
				
				if ( array_key_exists( 'cb_p6', $GLOBALS ) ) {
					global $cb_p6;					
				}
				
				if ( isset( $cb_p6 ) ) {
					
					?>Patreon Button, Widgets and Plugin <?php echo $cb_p6->internal['version'] ?><br /><?php
					
				}
			
			?>
			
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
		
		<div id="patreon_health_check_output_for_support">WP <?php echo get_bloginfo( 'version' ); ?> with PHP <?php echo phpversion(); echo "\r\n"; ?>Patreon WordPress <?php echo PATREON_WORDPRESS_VERSION ?> with API v<?php echo get_option( 'patreon-installation-api-version', false ); echo "\r\n";
		
			// Conditionals for any addons / other relevant Patreon plugins
		
			if ( array_key_exists( 'cb_p6_a1', $GLOBALS ) ) {
				global $cb_p6_a1;					
			}
			
			if ( isset( $cb_p6_a1 ) ) {
				?>Patron Plugin Pro <?php echo $cb_p6_a1->internal['version']; echo "\r\n";
			}
		
			if ( array_key_exists( 'cb_p6', $GLOBALS ) ) {
				global $cb_p6;					
			}
			
			if ( isset( $cb_p6 ) ) {
				?>Patreon Button, Widgets and Plugin <?php echo $cb_p6->internal['version']; echo "\r\n";
			}
			
			if ( isset( $health_info ) AND is_array( $health_info ) AND count( $health_info ) > 0 ) {
				foreach ( $health_info as $key => $value ) {
				 echo "\r\n";
				 echo '# '.$health_info[$key]['heading'].' #';
				 echo "\r\n";
				 echo str_replace( '<h3>', "\r\n# ", str_replace( '</h3>', " #\r\n", $health_info[$key]['notice'] ) );			
				}
			}
		?></div><?php
		
    }
	
	public function sanitize_page_name( $input ) {
		// Allow only permitted chars - Escape any potential special char among the allowed just in case
		$input = preg_replace("/[^A-Za-z0-9_\-\ \!\?\$\,\.\#\(\)\^\&\=\[\]\%\@\+]/", '', $input);
		// Further sanitization here if needed in future
		return $input;
	}

	public function site_locking_value( $input ) {
		$input = preg_replace("/[^0-9,.]/", '', $input);
		// Further sanitization here if needed in future
		return $input;
	}

}