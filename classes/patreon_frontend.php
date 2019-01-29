<?php


// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_Frontend {

	public static $messages_map 					= array();
	public static $current_user_logged_into_patreon = -1;
	
	function __construct() {
		
		add_action( 'login_enqueue_scripts', array( $this,'patreonEnqueueCss' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this,'patreonEnqueueCss' ) );
		add_action( 'wp_head', array( $this, 'patreonPrintCss' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'patreonEnqueueJs' ) );		
		add_action( 'admin_enqueue_scripts', array( $this, 'patreonEnqueueAdminCss' ) );		
		add_action( 'login_form', array( $this, 'showPatreonMessages' ) );
		add_action( 'login_form', array( $this, 'displayPatreonLoginButtonInLoginForm' ) );
		add_action( 'register_form', array( $this, 'showPatreonMessages' ) );
		add_action( 'register_form', array( $this, 'displayPatreonLoginButtonInLoginForm' ) );
		add_filter( 'the_content', array( $this, 'protectContentFromUsers'), PHP_INT_MAX - 5 );
		add_shortcode( 'patreon_login_button', array( $this,'LoginButtonShortcode' ) );
		add_filter('get_avatar', array( $this, 'show_patreon_avatar' ), 10, 5);

		self::$messages_map = array(
			'patreon_cant_login_strict_oauth'            => PATREON_CANT_LOGIN_STRICT_OAUTH,		
			'login_with_wordpress'                       => PATREON_LOGIN_WITH_WORDPRESS_NOW,		
			'patreon_cant_login_api_error'               => PATREON_CANT_LOGIN_DUE_TO_API_ERROR,		
			'patreon_cant_login_api_error_credentials'   => PATREON_CANT_LOGIN_DUE_TO_API_ERROR_CHECK_CREDENTIALS,
			'patreon_no_locking_level_set_for_this_post' => PATREON_NO_LOCKING_LEVEL_SET_FOR_THIS_POST,
			'patreon_no_post_id_to_unlock_post'          => PATREON_NO_POST_ID_TO_UNLOCK_POST,
			'patreon_weird_redirection_at_login'         => PATREON_WEIRD_REDIRECTION_AT_LOGIN,		
			'patreon_could_not_create_wp_account'        => PATREON_COULDNT_CREATE_WP_ACCOUNT,		
			'patreon_api_credentials_missing'            => PATREON_API_CREDENTIALS_MISSING,		
			'admin_login_with_patreon_disabled'          => PATREON_ADMIN_LOGIN_WITH_PATREON_DISABLED,		
			'email_exists_login_with_wp_first'           => PATREON_EMAIL_EXISTS_LOGIN_WITH_WP_FIRST,		
			'login_with_patreon_disabled'                => PATREON_LOGIN_WITH_PATREON_DISABLED,		
			'admin_bypass_filter_message'                => PATREON_ADMIN_BYPASSES_FILTER_MESSAGE,
			'no_code_receved_from_patreon'               => PATREON_NO_CODE_RECEIVED_FROM_PATREON,
			'no_patreon_action_provided_for_flow'        => PATREON_NO_FLOW_ACTION_PROVIDED,
			'patreon_direct_unlocks_not_turned_on'       => PATREON_DIRECT_UNLOCKS_NOT_ON,
		);
		
	}
	function patreonEnqueueJs() {
		
		wp_register_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS . '/js/app.js', array( 'jquery' ) );
		wp_enqueue_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS . '/js/app.js', array( 'jquery' ), '1.0', true );
		
	}
	function patreonEnqueueAdminCss() {
		
		wp_register_style( 'patreon-wordpress-admin-css', PATREON_PLUGIN_ASSETS . '/css/admin.css', false );
		wp_enqueue_style( 'patreon-wordpress-admin-css', PATREON_PLUGIN_ASSETS . '/css/admin.css' );
		
	}
	function patreonEnqueueCss() {
		
		wp_register_style( 'patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css', false );
		wp_enqueue_style('patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css' );
		
	}
	function patreonPrintCss() {
		
		// Why we print out css direclty in header is that we want to account for any potential WP content directory location than default
		echo '<style>';
		echo "@font-face {
			font-family: 'Libre Franklin Extra Bold';
			src: url('" . PATREON_PLUGIN_ASSETS . "/fonts/librefranklin-extrabold-webfont.woff2') format('woff2'),
				 url('" . PATREON_PLUGIN_ASSETS . "/fonts/librefranklin-extrabold-webfont.woff') format('woff');
			font-weight: bold;
			}";
		echo '</style>';
		
	}
	public static function displayPatreonCampaignBanner( $patreon_level = false, $args = false ) {

		global $wp;
		
		// Allow 3rd party plugins to override interface - this will abort interface generation and replace it with the code that returns from this filter, and also allow 3rd party code to still apply rest of this function's filters without causing recursion
		
		$override_interface = array();
		$override_interface = apply_filters( 'ptrn/override_interface_template', $patreon_level, $args );
		
		if ( is_array( $override_interface ) AND isset( $override_interface['override'] ) ) {
			return $override_interface['interface'];			
		}
		
		// Get the post from post id if it is supplied
		if ( isset( $args['post_id'] ) ) {
			$post = get_post( $args['post_id'] );			
		}
		
		if ( !$args OR !is_array( $args ) ) {
			global $post;
			$args = array();		
		}
		
		$login_with_patreon = get_option( 'patreon-enable-login-with-patreon', false );
		$client_id 			= get_option( 'patreon-client-id', false );
	
		// Check existence of a custom patreon banners as saved in plugin options
		$custom_universal_banner = get_option( 'patreon-custom-universal-banner', false );
		
		// Default custom text banner 
		
		$contribution_required   = PATREON_TEXT_LOCKED_POST;
		
        if ( $custom_universal_banner AND $custom_universal_banner !='' ) {
			// Custom banner exists and it is not empty. Override the message
			$contribution_required = $custom_universal_banner;
		}
		
        if ( $patreon_level != false ) {
			
        	$contribution_required = str_replace( '%%pledgelevel%%', $patreon_level, $contribution_required );
        	$contribution_required = apply_filters( 'ptrn/contribution_required', $contribution_required, 'main_banner_message',$patreon_level, $post );
			
		}
		
        if ( $client_id ) {
		
			// Wrap message and buttons in divs for responsive interface mechanics
			
			$contribution_required = '<div class="patreon-locked-content-message">' . $contribution_required . '</div>';
			
			// But hide the custom banner if no custom banner was saved
			
			if ( !$custom_universal_banner OR $custom_universal_banner =='' ) {
				$contribution_required = '';
			}
			
			// Still apply the filters so it can be modified by 3rd party code
			$contribution_required = apply_filters( 'ptrn/final_state_main_banner_message', $contribution_required, $patreon_level, $post );
			
			$button_args = array();
			
			if ( isset( $args['direct_unlock'] ) ) {
				
				// This is a direct unlock intent that does not seek to particularly unlock a post. It can be anything. Set the relevant vars for universal button function:
				$button_args['direct_unlock'] = $args['direct_unlock'];
				$button_args['redirect'] = $args['redirect'];
				
			}
			
			$universal_button	   = self::patreonMakeUniversalButton( $patreon_level, false, false, false, $button_args );
			$universal_button 	   = apply_filters( 'ptrn/final_state_universal_button', '<div class="patreon-universal-button">' . $universal_button . '</div>', $patreon_level, $post );
			
			$text_over_universal_button  = apply_filters( 'ptrn/final_state_label_over_universal_button', self::getLabelOverUniversalButton( $patreon_level, $args ), $patreon_level, $post );
			
			$text_under_universal_button = apply_filters( 'ptrn/final_state_label_under_universal_button', self::getLabelUnderUniversalButton( $patreon_level, false, false, $args ), $patreon_level, $post );
			
			// Wrap all of them in a responsive div
			
			$campaign_banner = '<div class="patreon-campaign-banner">'.
									$contribution_required.
									'<div class="patreon-patron-button-wrapper">'.
										'<div class="patreon-text-over-button">'.
											$text_over_universal_button.
										'</div>'.
										$universal_button.
										'<div class="patreon-text-under-button">'.
											$text_under_universal_button.
										'</div>'.
									'</div>'.
								'</div>';
			
			// This extra button is solely here to test whether new wrappers cause any design issues in different themes. For easy comparison with existing unwrapped button. Remove when confirmed.
			
        	$campaign_banner = apply_filters( 'ptrn/campaign_banner', $campaign_banner, $patreon_level, $post );

            return $campaign_banner;
        }
		
	}
	public static function getLabelOverUniversalButton( $patreon_level, $args = false ) {

		$label                    = PATREON_TEXT_OVER_BUTTON_1;
		$user_logged_into_patreon = self::isUserLoggedInPatreon();
		$is_patron                = Patreon_Wordpress::isPatron( wp_get_current_user() );
		$messages                 = self::processPatreonMessages();
		$user                     = wp_get_current_user();
		$declined                 = Patreon_Wordpress::checkDeclinedPatronage( $user );		
		$user_patronage           = Patreon_Wordpress::getUserPatronage();						
			
		// Get creator full name:
		$creator_full_name = get_option( 'patreon-creator-full-name', false );
		
		if ( !$creator_full_name OR $creator_full_name == '' ) {
			$creator_full_name = 'this creator';
		}
		
		// Get lock or not details if it is not given. If post id given, use it. 
		if ( !isset( $args['lock'] ) ) {
			
			if ( isset( $args['post_id'] ) ) {
				$lock_or_not = Patreon_Wordpress::lock_or_not( $args['post_id'] );
			}
			else {
				$lock_or_not = Patreon_Wordpress::lock_or_not();
			}
		}
		
		// If lock or not details were not given, merge the incoming args with what we just got.
		if( $args AND is_array( $args ) AND !isset( $args['lock'] ) ) {
			$args = $lock_or_not + $args;
		}
		
		// If no args given, just feed lock or not:
		
		if ( !isset( $args ) ) {
			$args = $lock_or_not;
		}			
		
		$post_id = false;
		
		if ( isset( $args['post_id'] ) ) {
			$post = get_post( $args['post_id'] );
		}
		else {
			global $post;
		}
		
		if ( $args['reason'] == 'user_not_logged_in' ) {
			
			$label = PATREON_TEXT_OVER_BUTTON_1;
			
			if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
				$label = PATREON_TEXT_OVER_BUTTON_7;
			}
			
			if( isset( $args['post_total_patronage_level'] ) AND $args['post_total_patronage_level'] > 0 ) {
				$label   = PATREON_TEXT_OVER_BUTTON_9;
				
				if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
					// Double condition - has both active patron and total patronage conditions. Override text
					$label = PATREON_TEXT_OVER_BUTTON_10;
				}
			}			
			
		}

		if ( $args['reason'] == 'not_a_patron' ) {
			
			$label = PATREON_TEXT_OVER_BUTTON_1;
			
			if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
				$label = PATREON_TEXT_OVER_BUTTON_7;
			}
			
			if( isset( $args['post_total_patronage_level'] ) AND $args['post_total_patronage_level'] > 0 ) {
				$label   = PATREON_TEXT_OVER_BUTTON_9;
				
				if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
					// Double condition - has both active patron and total patronage conditions. Override text
					$label = PATREON_TEXT_OVER_BUTTON_10;
				}
			}
			
		}
		
		if ( $args['reason'] == 'payment_declined' ) {
			$label = PATREON_TEXT_OVER_BUTTON_3;
		}
		
		if ( $args['reason'] == 'active_pledge_not_enough' ) {
			
			$label = PATREON_TEXT_OVER_BUTTON_1;
			
			if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
				$label = PATREON_TEXT_OVER_BUTTON_8;
			}
			
			if( isset( $args['post_total_patronage_level'] ) AND $args['post_total_patronage_level'] > 0 ) {
				$label   = PATREON_TEXT_OVER_BUTTON_9;
				
				// Double condition - override the text
				if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
					$label = PATREON_TEXT_OVER_BUTTON_10;
				}
				
			}
			
		}
		
		if ( $args['reason'] == 'not_active_patron_at_post_date' ) {
			
			$label = PATREON_TEXT_OVER_BUTTON_8;
			
			if( isset( $args['post_total_patronage_level'] ) AND $args['post_total_patronage_level'] > 0 ) {
				$label   = PATREON_TEXT_OVER_BUTTON_10;
			}
			
		}
		
		// We init vars so picky users will not see undefined var/index errors:
		
		$post_total_patronage_level = '';
		if( isset( $args['post_total_patronage_level'] ) ) {
			$post_total_patronage_level = $args['post_total_patronage_level'];
		}
		
		$label = str_replace( '%%creator%%', $creator_full_name, $label );
		$label = str_replace( '%%pledgelevel%%', $patreon_level, $label );
		$label = str_replace( '%%flow_link%%', self::patreonMakeCacheableFlowLink( $post ), $label );
		$label = str_replace( '%%total_pledge%%', $post_total_patronage_level, $label );
	
		return $messages . apply_filters( 'ptrn/label_text_over_universal_button', str_replace( '%%pledgelevel%%',$patreon_level, $label ), $args['reason'], $user_logged_into_patreon, $is_patron, $patreon_level, $args );
		
	}
	public static function getLabelUnderUniversalButton( $patreon_level,  $state = false, $post = false, $args = false ) {

		$label                    = '';
		$user_logged_into_patreon = self::isUserLoggedInPatreon();
		$is_patron                = Patreon_Wordpress::isPatron( wp_get_current_user() );
		$messages                 = self::processPatreonMessages();
		$user                     = wp_get_current_user();
		$declined                 = Patreon_Wordpress::checkDeclinedPatronage( $user );		
		$user_patronage           = Patreon_Wordpress::getUserPatronage();						
			
		// Get creator full name:
		$creator_full_name = get_option( 'patreon-creator-full-name', false );
		
		if ( !$creator_full_name OR $creator_full_name == '' ) {
			$creator_full_name = 'this creator';
		}
		
		// Get lock or not details if it is not given. If post id given, use it. 
		if ( !isset( $args['lock'] ) ) {
			
			if ( isset( $args['post_id'] ) ) {
				$lock_or_not = Patreon_Wordpress::lock_or_not( $args['post_id'] );
			}
			else {
				$lock_or_not = Patreon_Wordpress::lock_or_not();
			}
		}
		
		// If lock or not details were not given, merge the incoming args with what we just got.
		if( $args AND is_array( $args ) AND !isset( $args['lock'] ) ) {
			$args = $lock_or_not + $args;
		}
				
		if ( $args['reason'] == 'not_a_patron' ) {
			$label = PATREON_TEXT_UNDER_BUTTON_2;
		}

		if ( $args['reason'] == 'payment_declined' ) {
			$label = PATREON_TEXT_UNDER_BUTTON_3;
		}
		
		if ( $args['reason'] == 'active_pledge_not_enough' ) {
			$label = PATREON_TEXT_UNDER_BUTTON_2;			
		}
		
		if ( $args['reason'] == 'not_active_patron_at_post_date' ) {
			$label = PATREON_TEXT_UNDER_BUTTON_2;
		}
		
		$post_total_patronage_level = '';
		if( isset( $args['post_total_patronage_level'] ) ) {
			$post_total_patronage_level = $args['post_total_patronage_level'];
		}		
			
		$label = str_replace( '%%creator%%', $creator_full_name, $label );
		$label = str_replace( '%%pledgelevel%%', $patreon_level, $label );
		$label = str_replace( '%%flow_link%%', self::patreonMakeCacheableFlowLink(), $label );
		$label = str_replace( '%%total_pledge%%', $post_total_patronage_level, $label );
	
		return apply_filters( 'ptrn/label_text_under_universal_button', $label, $args['reason'], $user_logged_into_patreon, $is_patron, $patreon_level, $state, $args);
		
	}
	public static function showPatreonMessages() {
		
		echo self::processPatreonMessages();
		
	}
	public static function processPatreonMessages() {
		
		$patreon_error = '';
		if ( isset( $_REQUEST['patreon_error'] ) ) {
			
			// If any specific error message is sent from Patreon, prepare it
			$patreon_error = ' - Patreon returned: ' . $_REQUEST['patreon_error'];
			
		}

		if ( isset( $_REQUEST['patreon_message'] ) ) {
			
			return '<p class="patreon_message">' . apply_filters( 'ptrn/error_message', self::$messages_map[ $_REQUEST['patreon_message'] ] . $patreon_error ) . '</p>';
			
		}
		
		if ( isset( $GLOBALS['patreon_notice'] ) ) {
			
			return '<p class="patreon_message">' . apply_filters( 'ptrn/patreon_notice', $GLOBALS['patreon_notice'] ).'</p>';
			
		}
		
		return '';
		
	}
	public static function patreonMakeUniversalButton( $min_cents = false, $state = false, $post = false, $client_id = false, $args = false ) {
		
		// This very customizable function takes numerous parameters to customize universal flow links and creates the desired link

		// If no post is given, get the active post:
		
		if ( !$post ) {
			global $post;
		}
		
		// If it is a direct unlock request, unset the post
						
		if ( isset( $args['direct_unlock'] ) ) {
			
			unset($post);
			
			// Set the post to the id if it is given:
			if ( $args['post_id'] != '' ) {
				$post = get_post( $args['post_id'] );
			}
		
		}
		
		$send_pledge_level = 1;
		
		if ( $min_cents ) {
			$send_pledge_level = $min_cents * 100;;
		}
		
		if ( !$client_id ) {
			$client_id = get_option( 'patreon-client-id', false );
		}
		
		// If we werent given any state vars to send, initialize the array
		if ( !$state ) {
			$state = array();
		}

		// Get the address of the current page, and save it as final redirect uri.		
		// Start with home url for redirect. If post is valid, get permalink. 
		
		$final_redirect = home_url();
		
		if ( $post ) {
			$final_redirect = get_permalink( $post->ID );
		}
		
		$state['final_redirect_uri'] = $final_redirect;
		
		// $href = self::MakeUniversalFlowLink($send_pledge_level,$state,$client_id);
		
		// We changed the above universal flow link maker to a function which will create cache-able links
		// Some of the vars in current function which the earlier function used may not be needed now - clean up later #REVISIT
		
		$flow_link_args = array();
						
		if ( isset( $args['direct_unlock'] ) ) {
			
			// If direct unlock request is given, set cacheable flow link vars.
			$flow_link_args['direct_unlock'] = $args['direct_unlock'];
			$flow_link_args['redirect'] = $args['redirect'];
			$flow_link_args['post_id'] = $args['post_id'];
			
		}		
			
		$href       = self::patreonMakeCacheableFlowLink( $post, $flow_link_args );
		
		$label_text = self::patreonMakeUniversalButtonLabel();
		$button     = self::patreonMakeUniversalButtonImage( $label_text );
		
		return apply_filters( 'ptrn/patron_button', '<a href="' . $href . '">' . $button . '</a>', $min_cents );		
		
	}
	public static function patreonMakeCacheableLoginLink() {
		
		global $wp;
		
		$current_url = home_url( $wp->request );
		$flow_link   = site_url() . '/patreon-flow/?patreon-login=yes&patreon-final-redirect=' . urlencode( $current_url );
		return $flow_link;
		
	}
	public static function patreonMakeCacheableFlowLink( $post = false, $args = false ) {
		
		if ( !$post ) {
			global $post;
		}
		
		$unlock_post_id = '';
		
		if ( isset( $post ) AND isset( $post->ID ) ) {
			$unlock_post_id = $post->ID;
		}
		
		$flow_link = site_url() . '/patreon-flow/?patreon-unlock-post=' . $unlock_post_id;

		if ( isset( $args['direct_unlock'] ) ) {
			
			$append_post_id = '';
			// If direct unlock request is given, override all :
			
			if( isset( $args['post_id'] ) ) {
				$append_post_id = '&patreon-post-id=' . $args['post_id'];
			}
			
			$flow_link = site_url() . '/patreon-flow/?patreon-direct-unlock=' . $args['direct_unlock'] . $append_post_id . '&patreon-redirect=' .  urlencode( base64_encode( $args['redirect'] ) );
			
		}		
		
		return $flow_link;
		
	}
	public static function patreonMakeCacheableImageFlowLink( $attachment_id, $post_id = false ) {
	
		if ( !$post_id ) {
			global $post;
		}
		
		$unlock_post_id = $post_id;
		
		if ( !$unlock_post_id AND ( isset( $post ) AND isset( $post->ID ) ) ) {
			$unlock_post_id = $post->ID;
		}
		
		$flow_link = site_url() . '/patreon-flow/?patreon-unlock-post=' . $unlock_post_id . '&patreon-unlock-image=' . $attachment_id;
		
		return $flow_link;
		
	}
	public static function patreonMakeUniversalButtonImage( $label ) {
		return '<div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="' . PATREON_PLUGIN_ASSETS . '/img/patreon-logomark-on-coral.svg" alt="' . $label . '" /> ' . $label . '</div></div>';
		
	}
	public static function MakeUniversalFlowLink( $pledge_level, $state = false, $client_id = false, $post = false, $args = false ) {
		
		if ( !$post AND !isset( $args['direct_unlock'] ) ) {
			global $post;
		}
		
		if ( !$client_id ) {
			$client_id = get_option( 'patreon-client-id', false );
		}
		
		// If we werent given any state vars to send, initialize the array
		if ( !$state ) {
		
			$state = array();
		
			// Get the address of the current page, and save it as final redirect uri.		
			// Start with home url for redirect. If post is valid, get permalink. 
			
			$final_redirect = home_url();
			
			if ( $post ) {
				$final_redirect = get_permalink( $post->ID );
			}
			
			// We dont want to redirect people to login page. So check if we are there.
			if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
				$final_redirect = site_url();
			}			
			
			$state['final_redirect_uri'] = $final_redirect;			
			
		}		
		
		$redirect_uri           = site_url() . '/patreon-authorization/';
		$v2_params = '&scope=identity%20identity[email]';
		
		$send_post_id = false;
		
		if ( $post ) {
			$send_post_id = $post->ID;
		}
		 
		$pledge_level = apply_filters( 'ptrn/patron_link_pledge_level', $pledge_level, $send_post_id, $args );
		
		$href = 'https://www.patreon.com/oauth2/become-patron?response_type=code&min_cents=' . $pledge_level . '&client_id=' . $client_id . $v2_params . '&redirect_uri=' . $redirect_uri . '&state=' . urlencode( base64_encode( json_encode( $state ) ) );

		// 3rd party dev goodie! Apply custom filters so they can manipulate the url:
		
		$href        = apply_filters( 'ptrn/patron_link', $href );
		$utm_content = 'post_unlock_button';
		
		if ( isset( $args ) AND $args['link_interface_item'] == 'image_unlock_button' ) {
			$utm_content = 'image_unlock_button';
		}
		
		if ( isset( $args ) AND $args['link_interface_item'] == 'direct_unlock_button' ) {
			$utm_content = 'direct_unlock_button';
		}
		
		$filterable_utm_params = 'utm_term=&utm_content=' . $utm_content;
		$filterable_utm_params = apply_filters( 'ptrn/utm_params_for_patron_link', $filterable_utm_params );
		
		$utm_params = 'utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=' . get_option( 'patreon-campaign-id' ) . '&' . $filterable_utm_params;

		return $href . '&' . $utm_params;
		
	}
	public static function patreonMakeUniversalButtonLabel() {
		
		// Default label:
		
		$label                    = apply_filters( 'ptrn/universal_button_label', PATREON_TEXT_UNLOCK_WITH_PATREON );
		$user_logged_into_patreon = self::isUserLoggedInPatreon();
		$is_patron                = Patreon_Wordpress::isPatron();
		
		// Change this after getting info about which value confirms user's payment is declined. The only different button label is for that condition.
		
		return $label;
		
	}
	public static function isUserLoggedInPatreon() {
		
		if ( self::$current_user_logged_into_patreon != -1 ) {
			return self::$current_user_logged_into_patreon;
		}
		
		$user_logged_into_patreon = false;
		
		if ( is_user_logged_in() ) {
			
			// User is logged into WP. Check if user has valid patreon data :
			
			$user = wp_get_current_user();

			if ( $user ) {
				
				// REVISIT - whats below may be a concern - it connects to API to check for valid user for every generation of button. If we could cache it it would be better
				$user_response = Patreon_Wordpress::getPatreonUser( $user );

				if ( $user_response ) {
					// This is a user logged into Patreon. 
					$user_logged_into_patreon = true;
				}					
			}
		}
		
		return self::$current_user_logged_into_patreon = $user_logged_into_patreon;
	}
	public static function patreonMakeLoginLink( $client_id=false, $state=false, $post=false, $args=false ) {
		
		if ( !$post ) {
			global $post;
		}
		
		if ( !$client_id ) {
			$client_id = get_option( 'patreon-client-id', false );
		}
		
		$redirect_uri = site_url() . '/patreon-authorization/';
			
		// If we werent given any state vars to send, initialize the array

		if ( !$state ) {
			
			$state = array();
			
			// Get the address of the current page, and save it as final redirect uri.		
			// Start with home url for redirect. If post is valid, get permalink. 
			
			$final_redirect = home_url();
			
			if ( $post ) {
				$final_redirect = get_permalink( $post->ID );
			}
			
			// We dont want to redirect people to login page. So check if we are there.
			if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
				$final_redirect = site_url();
			}			
			
			$state['final_redirect_uri'] = $final_redirect;			
			
		}
		
		$redirect_uri           = site_url() . '/patreon-authorization/';
		$v2_params = '&scope=' . 'identity+' . urlencode( 'identity[email]' );
		
		$href                  = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' 
		. $client_id . $v2_params 
		. '&redirect_uri=' . urlencode($redirect_uri) 
		. '&state=' . urlencode( base64_encode( json_encode( $state ) ) );
		
		$href                  = apply_filters( 'ptrn/login_link', $href );
		$filterable_utm_params = 'utm_term=&utm_content=login_button';
		$filterable_utm_params = apply_filters( 'ptrn/utm_params_for_login_link', $filterable_utm_params );
		$utm_params            = 'utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=' . get_option( 'patreon-campaign-id' ) . '&' . $filterable_utm_params;
		
		return $href . '&' . $utm_params;
	}
	public static function patreonMakeLoginButton( $client_id = false ) {
		
		if ( !$client_id ) {
			$client_id = get_option( 'patreon-client-id', false );
		}
		
		// Check if user is logged in to WP, for determination of label text
		
		// Set login label to default
		$login_label =  apply_filters( 'ptrn/login_button_label', PATREON_TEXT_CONNECT );
		
		if ( is_user_logged_in() ) {
			// User is logged into WP. Check if user has valid patreon data :
			
			$user = wp_get_current_user();

			if ( $user ) {
				
				$user_response = Patreon_Wordpress::getPatreonUser( $user );
				// ^ REVISIT - whats above may be a concern - it connects to API to check for valid user for every generation of button. If we could cache it it would be better

				if ( $user_response ) {
					// This is a user logged into Patreon. use refresh text
					$login_label = PATREON_TEXT_REFRESH;
				}					
			}
		}
		
		$href = self::patreonMakeCacheableLoginLink( $client_id );

		return apply_filters( 'ptrn/login_button', '<a href="' . $href . '" class="ptrn-login"><div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="' . PATREON_PLUGIN_ASSETS . '/img/patreon-logomark-on-coral.svg" alt=""> ' . $login_label . '</div></div></a>', $href );

	}
	public static function lock_this_post( $post = false ) {
		
		if ( !$post ) {
			global $post;
		}
		
		// Just bail out if this is not the main query for content
		if ( !is_main_query() ) {
			return false;
		}		
		
		$post_types = get_post_types( array( 'public'=>true ), 'names' );
	
		if ( in_array( get_post_type( $post ), $post_types ) ) {
			
			$exclude = array(
			);
			
			// Enables 3rd party plugins to modify the post types excluded from locking
			$exclude = apply_filters( 'ptrn/filter_excluded_posts', $exclude );

			if ( in_array( get_post_type( $post ), $exclude ) ) {
				return false;
			}
			
			// First check if entire site is locked, get the level for locking.
			
			$patreon_level = get_option( 'patreon-lock-entire-site', false );
			
			// Check if specific level is given for this post:
			
			$post_level = get_post_meta( $post->ID, 'patreon-level', true );
			
			// get post meta returns empty if no value is found. If so, set the value to 0.
			
			if ( $post_level == '' ) {
				$post_level = 0;				
			}

			// Check if both post level and site lock level are set to 0 or nonexistent. If so return normal content.
			
			if( $post_level == 0 
				&& ( !$patreon_level
					|| $patreon_level == 0 )
			) {
				return false;
			}
			
			// If we are at this point, then this post is protected. 
			
			// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
			// It is independent of the plugin load order since it checks if it is defined.
			// It can be defined by any plugin until right before the_content filter is run.
	
			if ( apply_filters( 'ptrn/bypass_filtering', defined( 'PATREON_BYPASS_FILTERING' ) ) ) {
                return false;
            }
			 
			if ( current_user_can( 'manage_options' ) ) {
				// Here we need to put a notification to admins so they will know they can see the content because they are admin_login_with_patreon_disabled
				return false;
			}	
				
			// Passed checks. If post level is not 0, override patreon level and hence site locking value with post's. This will allow Creators to lock entire site and then set a different value for individual posts for access. Ie, site locking is $5, but one particular post can be $10, and it will require $10 to see. 
			
			if ( $post_level != 0 ) {
				$patreon_level = $post_level;
			}
			 
			$user                           = wp_get_current_user();
			$user_pledge_relationship_start = Patreon_Wordpress::get_user_pledge_relationship_start();
			$user_patronage                 = Patreon_Wordpress::getUserPatronage();
			$user_lifetime_patronage        = Patreon_Wordpress::get_user_lifetime_patronage();
			$declined                       = Patreon_Wordpress::checkDeclinedPatronage($user);

			// Check if post was set for active patrons only
			$patreon_active_patrons_only = get_post_meta( $post->ID, 'patreon-active-patrons-only', true );
			
			// Check if specific total patronage is given for this post:
			$post_total_patronage_level = get_post_meta( $post->ID, 'patreon-total-patronage-level', true );
		
			$hide_content = true;
		
			if ( !( $user_patronage == false
				|| $user_patronage < ( $patreon_level * 100 )
				|| $declined ) ) {
					
				$hide_content = false;

				// Seems valid patron. Lets see if active patron option was set and the user fulfills it
				
				if ( $patreon_active_patrons_only == '1'
				AND $user_pledge_relationship_start >= strtotime( get_the_date( '', $post->ID ) ) ) {
					
					$hide_content = true;
					
				}
			}			
		

			if ( $post_total_patronage_level !='' AND $post_total_patronage_level > 0 ) {
				
				// Total patronage set if user has lifetime patronage over this level, we let him see the content
				if ( $user_lifetime_patronage >= $post_total_patronage_level * 100 ) {
					$hide_content = false;
				}
				
			}
			
			
			if ( $hide_content ) {
				
				// protect content from user
				
				// Get client id
				
				$client_id = get_option( 'patreon-client-id', false );
				
				// // If client id exists. Do the banner. If not, no point in protecting since we wont be able to send people to patronage. If so dont modify normal content.
				
				if ( $client_id ) {
					return $patreon_level;
				}
				
			}
			
			// If we are here, it means post is protected, user is patron, patronage is valid. Slap the post footer:
			
			return false;
		}
				
		// Return content in all other cases
		return false;
		
	}
	public static function protectContentFromUsers( $content, $post_id = false ) {
		
		// This function receives content and optionally post id.
		
		// If content is received but no post id, the function acts to lock the existing post. In this case it can be hooked to the_content filter
		
		// If post id is given, then the function acts as a stand alone function to generate a lock interface and return the interface to whatever called it (another function or routine)

		// This way it can be used to lock posts if hooked to the_content and post_id is not passed and to decide lock and if so, generate lock interface and return it if post_id is passed. 
		
		// If post id is not given, try to get it from post global
		if ( !$post_id ) {
			
			global $post;
			$post_id = $post->ID;
			
		}
		
		// Allow addons to override this function - this will bypass this function, but also will allow addons to apply this function's filters in their own gating function to keep compatibility with other addons
		
		$override_content_filtering = array();
		
		$override_content_filtering = apply_filters( 'ptrn/override_content_filtering', $content, $post_id );
		
		if ( is_array($override_content_filtering) AND isset( $override_content_filtering['override'] ) ) {
			return $override_content_filtering['content'];			
		}
		
		// Just bail out if this is not the main query for content and there still isnt a post id
		if ( !is_main_query() AND !$post_id ) {
			return $content;
		}
		
		// Now send the post id to locking decision function 
		
		$lock_or_not = Patreon_Wordpress::lock_or_not($post_id);
		
		// An array with args should be returned
		$hide_content = false;
		$patreon_level = 0;
		$user_patronage = 0;
		
		if ( isset( $lock_or_not['lock'] ) ) {
			$hide_content = $lock_or_not['lock'];			
		}
		if ( isset( $lock_or_not['patreon_level'] ) ) {
			$patreon_level = $lock_or_not['patreon_level'];
		}
		if ( isset( $lock_or_not['user_active_pledge'] ) ) {
			$user_patronage = $lock_or_not['user_active_pledge'];
		}
		
		$lock_or_not['post_id'] = $post_id;

		if ( $hide_content ) {
			
			// protect content from user
			
			// Get client id
			
			$client_id = get_option( 'patreon-client-id', false );
			
			// // If client id exists. Do the banner. If not, no point in protecting since we wont be able to send people to patronage. If so dont modify normal content.
			
			if ( $client_id ) {
			
				$content = self::displayPatreonCampaignBanner( $patreon_level, $lock_or_not );
				$content = apply_filters( 'ptrn/post_content', $content, $patreon_level, $user_patronage, $lock_or_not );
				
				return $content;
				
			}
		}
		
		if ( !$hide_content AND $lock_or_not['reason'] == 'post_is_public' ) {
			// This is not a locked post. Return content without any footer
			return $content;
		}
		
		// If is an admin, just return the content with an admin-related notice
		
		if( current_user_can( 'manage_options' ) ) {
			return $content . self::MakeAdminPostFooter( $patreon_level );
		}
		
		// If we are here, it means post is protected, user is patron, patronage is valid. Slap the post footer:
		return $content . self::MakeValidPatronFooter( $patreon_level, $user_patronage, $lock_or_not );
		
	}
	public static function MakeAdminPostFooter( $patreon_level ) {
		
		return '<div class="patreon-valid-patron-message">' . 
			apply_filters( 'ptrn/admin_bypass_filter_message', PATREON_ADMIN_BYPASSES_FILTER_MESSAGE, $patreon_level ) .
		 '</div>';
		
	}
	public static function MakeValidPatronFooter( $patreon_level, $user_patronage, $args = false ) {
		
		// Creates conditional text for footer shown to valid patrons
		
		$label                    = PATREON_VALID_PATRON_POST_FOOTER_TEXT;
		$user_logged_into_patreon = self::isUserLoggedInPatreon();
		$is_patron                = Patreon_Wordpress::isPatron( wp_get_current_user() );
		$messages                 = self::processPatreonMessages();
		$user                     = wp_get_current_user();
		$declined                 = Patreon_Wordpress::checkDeclinedPatronage( $user );		
		$user_patronage           = Patreon_Wordpress::getUserPatronage();						
			
		// Get creator full name:
		$creator_full_name = get_option( 'patreon-creator-full-name', false );
		
		if ( !$creator_full_name OR $creator_full_name == '' ) {
			$creator_full_name = 'this creator';
		}
		
		// Get lock or not details if it is not given. If post id given, use it. 
		if ( !isset( $args['lock'] ) ) {
			
			if ( isset( $args['post_id'] ) ) {
				$lock_or_not = Patreon_Wordpress::lock_or_not( $args['post_id'] );
			}
			else {
				$lock_or_not = Patreon_Wordpress::lock_or_not();
			}
		}
		
		// If lock or not details were not given, merge the incoming args with what we just got.
		if( $args AND is_array( $args ) AND !isset( $args['lock'] ) ) {
			$args = $lock_or_not + $args;
		}
		
		// If no args given, just feed lock or not:
		
		if ( !isset( $args ) ) {
			$args = $lock_or_not;
		}	
	
		if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
			$label = PATREON_TEXT_OVER_BUTTON_11;
		}
		
		if( isset( $args['post_total_patronage_level'] ) AND $args['post_total_patronage_level'] > 0 ) {
			
			$label   = PATREON_TEXT_OVER_BUTTON_9;
			
			// Double condition - override the text
			if ( isset( $args['patreon_active_patrons_only'] ) AND $args['patreon_active_patrons_only'] == 1 ) {
				$label = PATREON_TEXT_OVER_BUTTON_10;
			}
			
		}
			
		$label = str_replace( '%%creator%%', $creator_full_name, $label );
		$label = str_replace( '%%pledgelevel%%', $patreon_level, $label );
		$label = str_replace( '%%flow_link%%', self::patreonMakeCacheableFlowLink(), $label );
		if ( isset( $args['post_total_patronage_level'] ) ) {
			$label = str_replace( '%%total_pledge%%', $args['post_total_patronage_level'], $label );
		}		
		
		// Get patreon creator url:
		$creator_profile_url = get_option( 'patreon-creator-url', false );
		$post_footer         = str_replace( '%%pledgelevel%%', $patreon_level,  apply_filters( 'ptrn/valid_patron_footer_text', $label , $patreon_level, $user_patronage ) );
		$post_footer         = apply_filters( 'ptrn/valid_patron_processed_message', str_replace( '%%creatorprofileurl%%',apply_filters( 'ptrn/valid_patron_creator_profile_url', '<a href="' . $creator_profile_url . '">Patreon</a>',$creator_profile_url ), $post_footer ), $patreon_level, $user_patronage );
		
		$post_footer = 
		'<div class="patreon-valid-patron-message">'.
			$post_footer . 
		'</div>';
		
		return apply_filters( 'ptrn/valid_patron_final_footer', $post_footer, $args['reason'], $patreon_level, $user_patronage, $args );		
		
	}
	public static function displayPatreonLoginButtonInLoginForm() {
		
		// For displaying login button in the form - wrapper
		echo '<div style="display:inline-block;width : 100%; text-align: center;">' . self::showPatreonLoginButton() . '</div>';
		
	}
	public static function showPatreonLoginButton() {

		$log_in_img = PATREON_PLUGIN_ASSETS . '/img/patreon login@1x.png';
		$client_id  = get_option( 'patreon-client-id', false );

		if ( $client_id == false ) {
			return '';
		}
		$button = '';
		/* inline styles - prevent themes from overriding */
		$button .= '
		<style type="text/css">
			.ptrn-button{display:block !important;;margin-top:20px !important;margin-bottom:20px !important;}
			.ptrn-button img {width: 272px; height:42px;}
			.patreon-msg {-webkit-border-radius: 6px;-moz-border-radius: 6px;-ms-border-radius: 6px;-o-border-radius: 6px;border-radius: 6px;padding:8px;margin-bottom:20px!important;display:block;border:1px solid #E6461A;background-color:#484848;color:#ffffff;}
		</style>';

		if ( isset( $_REQUEST['patreon-msg'] ) && $_REQUEST['patreon-msg'] == 'login_with_patreon' ) {
			$button .= '<p class="patreon-msg">You can now login with your WordPress username/password.</p>';
		} else {
			$button .= apply_filters( 'ptrn/login_button', '<a href="' . self::patreonMakeCacheableLoginLink( $client_id ) . '" class="ptrn-button"><img src="' . $log_in_img . '" width="272" height="42" /></a>' );
		}
		return $button;
		
	}
	public static function LoginButtonShortcode( $args ) {
		
		if ( !is_user_logged_in() ) {
			return Patreon_Frontend::showPatreonLoginButton();
		}
		
	}
	public static function show_patreon_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		
		// Checks if the user has a Patreon avatar saved, and returns that avatar in place of WP/site default
		$user = false;

		if ( is_numeric( $id_or_email ) ) {

			$id = (int) $id_or_email;
			$user = get_user_by( 'id' , $id );

		} elseif ( is_object( $id_or_email ) ) {

			if ( ! empty( $id_or_email->user_id ) ) {
				$id = (int) $id_or_email->user_id;
				$user = get_user_by( 'id' , $id );
			}

		} else {
			$user = get_user_by( 'email', $id_or_email );	
		}

		if ( $user && is_object( $user ) ) {
			
			// Get user's Patreon avatar meta:
			
			$user_patreon_avatar = get_user_meta( $user->ID, 'patreon-avatar-url', true );
			
			// Override avatar if there is a saved Patreon avatar
			if ( $user_patreon_avatar != '' ) {
				$avatar = '<img alt="'.$alt.'" src="'.$user_patreon_avatar.'" class="avatar avatar-"'.$size.' photo" height="'.$size.'" width="'.$size.'" />';
			}
		}
		
		return $avatar;

	}
	
}