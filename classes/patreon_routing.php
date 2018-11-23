<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_Routing {

	function __construct() {
		
		add_action( 'generate_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'init', array( $this, 'force_rewrite_rules' ) );
		
	}

	public static function activate() {
		
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		
	}

	public static function deactivate() {
		
		remove_action( 'generate_rewrite_rules', 'add_rewrite_rules' );
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		
	}

	function force_rewrite_rules() {
		
		global $wp_rewrite;
		
		if( get_option( 'patreon-rewrite-rules-flushed', false) == false ) {
			
			$wp_rewrite->flush_rules();
			// Refresh/add htaccess rules:
			Patreon_Protect::removePatreonRewriteRules();
			Patreon_Protect::addPatreonRewriteRules();
			update_option( 'patreon-rewrite-rules-flushed', true );
			
		}
		
	}

	function add_rewrite_rules( $wp_rewrite ) {

		$rules = array(
			'patreon-authorization\/?$' => 'index.php?patreon-oauth=true',
			'patreon-flow\/?$' => 'index.php?patreon-flow=true',
		);

		$wp_rewrite->rules = $rules + (array) $wp_rewrite->rules;

	}

	function query_vars( $public_query_vars ) {
		
		array_push( $public_query_vars, 'patreon-oauth' );
		array_push( $public_query_vars, 'patreon-flow' );
		array_push( $public_query_vars, 'patreon-unlock-post' );
		array_push( $public_query_vars, 'patreon-unlock-image' );
		array_push( $public_query_vars, 'patreon-direct-unlock' );
		array_push( $public_query_vars, 'patreon-post-id' );
		array_push( $public_query_vars, 'patreon-login' );
		array_push( $public_query_vars, 'patreon-final-redirect' );
		array_push( $public_query_vars, 'code' );
		array_push( $public_query_vars, 'state' );
		array_push( $public_query_vars, 'patreon-redirect' );
		return $public_query_vars;
		
	}

	function parse_request( &$wp ) {

		if ( strpos( $_SERVER['REQUEST_URI'],'/patreon-flow/' ) !== false ) {
			
			// First slap the noindex header so search engines wont index this page:
			header( 'X-Robots-Tag: noindex, nofollow' );
			 
			// Make sure browsers dont cache this
			header( 'cache-control: no-cache, must-revalidate, max-age=0' );			
			
			if( array_key_exists( 'patreon-login', $wp->query_vars ) ) {
				
				// Login intent. 
				
				$final_redirect = home_url();
				
				if( isset( $wp->query_vars['patreon-final-redirect'] ) ) {
					
					$final_redirect = $wp->query_vars['patreon-final-redirect'];
				}
				
				$state = array(
					'final_redirect_uri' => $final_redirect,
				);
				
				// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user + content before going to Patreon flow
				
				$filter_args = array(
					'state' => $state,
					'user' => wp_get_current_user(),
				);
				
				do_action( 'patreon_do_action_before_patreon_login', $filter_args );				
			
				$login_url = Patreon_Frontend::patreonMakeLoginLink( false, $state );

				wp_redirect( $login_url );
				exit;
			
			}
			
			if( array_key_exists( 'patreon-direct-unlock', $wp->query_vars ) ) {
				
				$final_redirect = wp_login_url();
				
				if( isset( $wp->query_vars['patreon-direct-unlock'] ) ) {
					
					if( !( isset($GLOBALS['patreon_enable_direct_unlocks']) AND $GLOBALS['patreon_enable_direct_unlocks'] )
					) {
						
						// No locking level set for this content or the site. No point in locking. Redirect to post.
						$final_redirect = add_query_arg( 'patreon_message', 'patreon_direct_unlocks_not_turned_on', $final_redirect );
						wp_redirect( $final_redirect );
						
						exit;	
					}					
					
					$patreon_level = $wp->query_vars['patreon-direct-unlock'];
					$redirect = base64_decode( urldecode( $wp->query_vars['patreon-redirect'] ) );
					
					if( !$patreon_level OR $patreon_level == '' OR $patreon_level == 0 ) {
						$patreon_level = 1;
					}
					
					$client_id = get_option( 'patreon-client-id', false );
				
					if( !$client_id ) {
						
						// No client id, no point in being here. Make it go with an error.
						
						$final_redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_api_error_credentials', $final_redirect );
						
						wp_redirect( $final_redirect );
						exit;
						
					}
					
					$post = false;
					
					// If post id set, get the post 
					if( isset( $wp->query_vars['patreon-post-id'] ) ) {
						$post = get_post( $wp->query_vars['patreon-post-id'] );
					}
					
					$link_interface_item         = 'direct_unlock_button';
					$state['final_redirect_uri'] = $redirect;	
					$send_pledge_level           = $patreon_level * 100;
					
					$flow_link = Patreon_Frontend::MakeUniversalFlowLink( $send_pledge_level, $state, $client_id, $post, array('link_interface_item' => $link_interface_item ) );

					wp_redirect( $flow_link );
					exit;
					
				}
			
			}
			
			if( array_key_exists( 'patreon-unlock-post', $wp->query_vars ) ) {
				
				// We have a login/flow request, Get the post id
				
				if( isset( $wp->query_vars['patreon-unlock-post'] ) ) {
					
					// First check if entire site is locked, get the level for locking.
					
					$patreon_level = get_option( 'patreon-lock-entire-site', false );
					
					// Account for any value the creator can put into this option, and also the default false					
					if( !$patreon_level OR $patreon_level == '' ) {
						$patreon_level = 0;
					}
					
					if( $wp->query_vars['patreon-unlock-post'] != '' ) {
							
						// Got post id. Get the post, and prepare necessary vars. Get the post first
						
						$post = get_post( $wp->query_vars['patreon-unlock-post'] );
						
						// If there is no post var, and entire site is not locked, no point in being here
						if( !$post AND $patreon_level == 0 ) {
							// No post, no point in being here.

							$final_redirect = add_query_arg( 'patreon_message', 'patreon_no_post_id_to_unlock_post', $final_redirect );							
							wp_redirect( home_url() );
							exit;
							
						}
						
					}
					
					// Start with home url for redirect. If post is valid, get permalink. 
					
					$final_redirect = home_url();
					
					if( $post ) {
						$final_redirect = get_permalink( $post->ID );
					}
						
					// Check if specific level is given for this post:
					
					$post_level = get_post_meta( $post->ID, 'patreon-level', true );
					
					// get post meta returns empty if no value is found. If so, set the value to 0.
					
					if( $post_level == '' ) {
						$post_level = 0;				
					}
					
					if( $post_level > 0 ) {
						$patreon_level = $post_level;
					}
					
					$link_interface_item = 'post_unlock_button';
					
					// If this is an image unlock request, override patreon level with image's:
					
					if( isset( $wp->query_vars['patreon-unlock-image'] ) AND $wp->query_vars['patreon-unlock-image'] != '' ) {
		
						$patreon_level = get_post_meta( $wp->query_vars['patreon-unlock-image'], 'patreon_level', true );
						
						if( !$patreon_level OR $patreon_level == 0) {
							$patreon_level = 0;
						}
						
						$link_interface_item = 'image_unlock_button';
						
					}
		
					if( $patreon_level == 0  
						AND !( isset($GLOBALS['patreon_enable_direct_unlocks']) AND $GLOBALS['patreon_enable_direct_unlocks'] )
					) {
						
						// No locking level set for this content or the site. No point in locking. Redirect to post.
						$final_redirect = add_query_arg( 'patreon_message', 'patreon_no_locking_level_set_for_this_post', $final_redirect );
						wp_redirect( $final_redirect );
						
						exit;	
					}
					
					$client_id = get_option( 'patreon-client-id', false );
				
					if( !$client_id ) {
						
						// No client id, no point in being here. Make it go with an error.
						
						$final_redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_api_error_credentials', $final_redirect );
						
						wp_redirect( $final_redirect );
						exit;
						
					}
					
					$state['final_redirect_uri'] = $final_redirect;	

					$send_pledge_level = $patreon_level * 100;
					
					// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user + content before going to Patreon flow
					
					$filter_args = array(
						'link_interface_item' => $link_interface_item, 
						'post' => $post,
						'post_level' => $post_level,
						'patreon_level' => $patreon_level,
						'state' => $state,
						'state' => $state,
						'user' => wp_get_current_user(),
					);
					
					do_action( 'patreon_do_action_before_universal_flow', $filter_args );
					
					$flow_link = Patreon_Frontend::MakeUniversalFlowLink( $send_pledge_level, $state, $client_id, $post, array('link_interface_item' => $link_interface_item ) );
				
					wp_redirect( $flow_link );
					exit;
					
				}
				
			}
			
			// Catch all
			$redirect = add_query_arg( 'patreon_message', 'no_patreon_action_provided_for_flow', wp_login_url() );
			wp_redirect( $redirect );
			exit;
			
		}
		if ( strpos( $_SERVER['REQUEST_URI'], '/patreon-authorization/' ) !== false ) {

			// First slap the noindex header so search engines wont index this page:
			header( 'X-Robots-Tag: noindex, nofollow' );
			 
			// Make sure browsers dont cache this
			header( 'cache-control: no-cache, must-revalidate, max-age=0' );			
	
			if( array_key_exists( 'code', $wp->query_vars ) ) {
				
				// Get state vars if they exist
	
				if( $wp->query_vars['state'] !='' ) {
					$state = json_decode( base64_decode( urldecode( $wp->query_vars['state'] ) ), true );
				}

				$redirect = false;
							
				// Check if final_redirect exists in state vars - if so, override redirect:
	
				if( $state['final_redirect_uri'] != '' ) {
					$redirect = $state['final_redirect_uri'];
				}		
			
				$redirect = apply_filters( 'ptrn/redirect', $redirect );		
					
				if( get_option( 'patreon-client-id', false ) == false || get_option( 'patreon-client-secret', false ) == false ) {

					/* redirect to homepage because of oauth client_id or secure_key error  */
					$redirect = add_query_arg( 'patreon_message', 'patreon_api_credentials_missing', $redirect );
					wp_redirect( $redirect );
					exit;			

				} else {
					$oauth_client = new Patreon_Oauth;
				}

				$tokens = $oauth_client->get_tokens( $wp->query_vars['code'], site_url() . '/patreon-authorization/' );

				if( array_key_exists( 'error', $tokens ) ) {

					if( $tokens['error']=='invalid_client' ) {
						
						// Credentials are wrong. Redirect with an informative message
						$redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_api_error_credentials', $redirect );
						
					}
					else {
						
						// Some other error from api. Append the message from Patreon too.
						$redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_api_error', $redirect );
						$redirect = add_query_arg( 'patreon_error', $tokens['error'], $redirect );
						
					}
						
					wp_redirect( $redirect );
					exit;

				} else {

					$api_client = new Patreon_API( $tokens['access_token'] );
					
					$user_response = $api_client->fetch_user();
					
					if( apply_filters( 'ptrn/force_strict_oauth', get_option( 'patreon-enable-strict-oauth', false ) ) ) {
						$user = Patreon_Login::updateLoggedInUserForStrictoAuth( $user_response, $tokens, $redirect );
					} else {
						$user = Patreon_Login::createOrLogInUserFromPatreon( $user_response, $tokens, $redirect );
					}
					
					//shouldn't get here
					$redirect = add_query_arg( 'patreon_message', 'patreon_weird_redirection_at_login', $redirect );
					
					wp_redirect( $redirect );
					exit;
					
				}
				
			} else {
				
				$redirect = add_query_arg( 'patreon_message', 'no_code_receved_from_patreon', wp_login_url() );
				wp_redirect( $redirect );
				exit;
				
			}
			
		}
		
	}
	
}