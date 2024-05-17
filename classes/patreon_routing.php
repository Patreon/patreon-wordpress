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
			'patreon-setup\/?$' => 'index.php?patreon-setup=true',
			'patreon-webhooks\/?$' => 'index.php?patreon-webhooks=true',
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
		array_push( $public_query_vars, 'patreon-webhooks' );
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
				
				$final_redirect = wp_login_url();
				
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
	
				if( isset( $state['final_redirect_uri'] ) AND $state['final_redirect_uri'] != '' ) {
					$redirect = $state['final_redirect_uri'];
				}
	
				// Check if this code was sent for a site connect request
				
				if ( isset( $state['patreon_action'] ) AND $state['patreon_action'] == 'connect_site' ) {
					
					// This code was given for setup process to allow request of credentials. Go ahead:
					
					if ( !current_user_can( 'manage_options' ) ) {
						// If user is not an admin, abort
						echo 'Sorry - to connect your site to Patreon you need to be an admin user.';
						exit;
						
					}
										
					$oauth_client = new Patreon_Oauth;
										
					// Set the client id to plugin wide client id one for setup process
					
					$oauth_client->client_id = PATREON_PLUGIN_CLIENT_ID;
					
					$tokens = $oauth_client->get_tokens( $wp->query_vars['code'], site_url() . '/patreon-authorization/', array( 'scopes' => 'w:identity.clients' ) );
										
					if ( isset( $tokens['access_token'] ) ) {
						
						// Exception - If we are here with a legit access token, re-mark this installation as v2 - can be removed when all installations are using v2
						
						update_option( 'patreon-installation-api-version', '2' );
						update_option( 'patreon-can-use-api-v2', true );
						
						// We got auth. Proceed with creating the client
						
						// Create new api object
						
						$api_client = new Patreon_API( $tokens['access_token'] );
						
						$params = array(
							'data' => array(
								'type' => 'oauth-client',
								'attributes' => Patreon_Wordpress::collect_app_info(),
							)
						);
						
						$client_result = $api_client->create_refresh_client( json_encode( $params ) );

						if ( isset( $client_result['data']['type'] ) AND $client_result['data']['type'] == 'oauth-client' ) {
							
							$client_id = $client_result['data']['id'];
							$client_secret = $client_result['data']['attributes']['client_secret'];
							$creator_access_token = $client_result['included'][0]['attributes']['access_token'];
							$creator_refresh_token = $client_result['included'][0]['attributes']['refresh_token'];
										
							// Some error handling here - later to be updated
							
							if ( !isset( $client_id ) OR $client_id == '' OR
								!isset( $client_secret ) OR $client_secret == '' OR
								!isset( $creator_access_token ) OR $creator_access_token == '' OR
								!isset( $creator_refresh_token ) OR $creator_refresh_token == ''		
							)
							{
								// One or more of the app details is kaput. Redirect with an error message.
								
								wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=0&patreon_message=error_missing_credentials') );
								exit;
								
							}
							
							// All good. Update the client details locally

							$existing_client_id = get_option( 'patreon-client-id', false );

							if ( $existing_client_id != $client_id ) {
								$client_id_updated = update_option('patreon-client-id', sanitize_text_field( $client_id ) );
							}
							else {
								$client_id_updated = true;
							}
							
							
							if ( $client_id_updated AND
								update_option('patreon-client-secret', sanitize_text_field( $client_secret ) ) AND
								update_option('patreon-creators-access-token', sanitize_text_field( $creator_access_token ) ) AND
								update_option('patreon-creators-refresh-token', sanitize_text_field( $creator_refresh_token ) )
							) {
								// All succeeded. 

								// Save entire return to options
								
								update_option( 'patreon-installation-api-version', '2' );
								update_option( 'patreon-setup-done', true );
								update_option( 'patreon-redirect_to_setup_wizard', false );
								update_option( 'patreon-setup-wizard-last-call-result', $client_result );
								
								// Redirect to success screen
								
								// First apply a filter so that 3rd party addons can redirect to a custom final screen
								
								// Check if post syncing is set up, if not, redirect to post sync page.
								
								$setup_final_redirect = apply_filters( 'ptrn/setup_wizard_final_redirect', admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=final') );
								
								if ( !get_option( 'patreon-post-sync-set-up', false ) ) {
									
									// Post sync not set up. Redirect it to relevant page
									
									$setup_final_redirect = apply_filters( 'ptrn/setup_wizard_post_sync_redirect', admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=post_sync_0') );
									
								}
								

								wp_redirect( $setup_final_redirect );
								exit;				
								
							}
							
						}
						
						// If we are here, something else is wrong. Come out with an error
						
						wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=0&patreon_message=failure_obtaining_credentials') );
						exit;
						
					}
					else {
						
						// No auth. Error handling here.
						
						wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=0&patreon_message=no_auth_for_client_creation') );
						exit;
					
					}
				
				}
				
				// Check if this code was sent for a site reconnect request
				// This block is separate from the site connect block to allow for potential differentiation in connect and reconnect flow
				
				if ( isset( $state['patreon_action'] ) AND $state['patreon_action'] == 'reconnect_site' ) {
					
					// This code was given for setup process to allow request of credentials. Go ahead:
					
					if ( !current_user_can( 'manage_options' ) ) {
						// If user is not an admin, abort
						echo 'Sorry - to reconnect your site to Patreon you need to be an admin user.';
						exit;
						
					}
					
					$oauth_client = new Patreon_Oauth;
										
					// Set the client id to plugin wide client id one for setup process
					
					$oauth_client->client_id = PATREON_PLUGIN_CLIENT_ID;
					
					$tokens = $oauth_client->get_tokens( $wp->query_vars['code'], site_url() . '/patreon-authorization/', array( 'scopes' => 'w:identity.clients' ) );
										
					if ( isset( $tokens['access_token'] ) ) {
						
						// We got auth. Proceed with creating the client
						
						// Exception - If we are here with a legit access token, re-mark this installation as v2 - can be removed when all installations are using v2
						
						update_option( 'patreon-installation-api-version', '2' );
						update_option( 'patreon-can-use-api-v2', true );						
						
						// Create new api object
						
						$api_client = new Patreon_API( $tokens['access_token'] );
						
						$params = array(
							'data' => array(
								'type' => 'oauth-client',
								'attributes' => Patreon_Wordpress::collect_app_info(),
							)
						);
						
						$client_result = $api_client->create_refresh_client( json_encode( $params ) );

						if ( isset( $client_result['data']['type'] ) AND $client_result['data']['type'] == 'oauth-client' ) {
							
							$client_id = $client_result['data']['id'];
							$client_secret = $client_result['data']['attributes']['client_secret'];
							$creator_access_token = $client_result['included'][0]['attributes']['access_token'];
							$creator_refresh_token = $client_result['included'][0]['attributes']['refresh_token'];
										
							// Some error handling here - later to be updated
							
							if ( !isset( $client_id ) OR $client_id == '' OR
								!isset( $client_secret ) OR $client_secret == '' OR
								!isset( $creator_access_token ) OR $creator_access_token == '' OR
								!isset( $creator_refresh_token ) OR $creator_refresh_token == ''		
							)
							{
								// One or more of the app details is kaput. Redirect with an error message.
								
								wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_0&patreon_message=error_missing_credentials') );
								exit;
								
							}
							
							// All good. Update the client details locally
							
							$existing_client_id = get_option( 'patreon-client-id', false );
							
							if ( $existing_client_id == $client_id AND
								update_option('patreon-client-secret', sanitize_text_field( $client_secret ) ) AND
								update_option('patreon-creators-access-token', sanitize_text_field( $creator_access_token ) ) AND
								update_option('patreon-creators-refresh-token', sanitize_text_field( $creator_refresh_token ) )
							) {
								// All succeeded. 

								// Save entire return to options
								
								update_option( 'patreon-installation-api-version', '2' );
								update_option( 'patreon-setup-done', true );
								update_option( 'patreon-redirect_to_setup_wizard', false );
								update_option( 'patreon-setup-wizard-last-call-result', $client_result );
								
								// Redirect to success screen
								
								// First apply a filter so that 3rd party addons can redirect to a custom final screen
								
								$setup_final_redirect = apply_filters( 'ptrn/setup_wizard_final_redirect', admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_final') );

								wp_redirect( $setup_final_redirect );
								exit;				
								
							}
							
						}
						
						// If we are here, something else is wrong. Come out with an error
						
						wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_0&patreon_message=failure_obtaining_credentials') );
						exit;
						
						
					}
					else {
						
						// No auth. Error handling here.
						
						wp_redirect( admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=reconnect_0&patreon_message=no_auth_for_client_creation') );
						exit;
					
						
					}
				
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
					
					// Check out if there is a proper user return. 
					
					if( !is_array( $user_response ) OR !isset( $user_response['data']['id'] ) ) {
						
						// We didnt get user info back from the API. Cancel with a message
							
						$redirect = add_query_arg( 'patreon_message', 'patreon_couldnt_acquire_user_details', $redirect );
						
						wp_redirect( $redirect );
						exit;						
					
					}
					
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
		
		if ( strpos( $_SERVER['REQUEST_URI'], '/patreon-webhooks/' ) !== false ) {
			
			// First slap the noindex header so search engines wont index this page:
			header( 'X-Robots-Tag: noindex, nofollow' );
			 
			// Make sure browsers dont cache this
			header( 'cache-control: no-cache, must-revalidate, max-age=0' );

			// Abort if apiv ersion used is not v2
			$api_version    = get_option( 'patreon-installation-api-version', '1' );
					
			if ( $api_version != '2' ) {
				return;
			}			

			if( array_key_exists( 'patreon-webhooks', $wp->query_vars ) ) {
				
				$webhook_info = get_option( 'patreon-post-sync-webhook', false );
				
				if ( !$webhook_info ) {
					return;
				}
				
				global $Patreon_Wordpress;
							
				// Parts taken from FB's webhook example
				$secret = $webhook_info['data']['attributes']['secret'];
				$raw_post_data = file_get_contents('php://input');
				//$header_signature = $_SERVER['X-Patreon-Signature'];
				
				$header_signature = '';
				$event            = '';		
				$headers          = $Patreon_Wordpress->get_all_headers();
				
				// If this is not an event from Patreon bail out
				
				if ( !isset( $headers['X-Patreon-Signature'] ) OR !isset( $headers['X-Patreon-Event'] ) ) {
					return;
				}
				
				$header_signature = $headers['X-Patreon-Signature'];
				$event            = $headers['X-Patreon-Event'];
					
				// Signature matching
				$expected_signature = hash_hmac( 'md5', $raw_post_data, $secret );
				
				$verified = false;
				
				if ( is_string( $header_signature ) AND hash_equals( $header_signature, $expected_signature ) ) {
					$verified = true;
				}
				
				if ( !$verified ) {
					return;
				}
				
				// This is a verified post from Patreon - process
				
				// Check if raw post data exists - if not bail out
				if ( strlen( $raw_post_data ) == 0 ) {
					return;
				}
				
				$event_info = json_decode( $raw_post_data, true );
				
				// Check if event is a legitimate array - if not bail out
				if ( !( is_array( $event_info ) AND count( $event_info ) > 0 ) ) {
					return;
				}
				
				// This is a legitimate Patreon event - process
				
				if( $event == 'posts:publish' ) {
					
					// Add post.

					$patreon_post_id = $event_info['data']['id'];
					
					// Get Patreon post
					
					$creator_access_token = get_option( 'patreon-creators-access-token', false );
					$client_id 			  = get_option( 'patreon-client-id', false );

					$patreon_post = false;
					
					if ( $creator_access_token AND $client_id ) {
						
						// Create new api object
						$api_client = new Patreon_API( $creator_access_token );
						
						$patreon_post = $api_client->get_post( $patreon_post_id );
						
					}
					
					if ( !$patreon_post OR !isset( $patreon_post['data']['id'] ) OR $patreon_post['data']['id'] == '' ) {
						// Couldn't get this post. Skip
						return;
					}

					$result = $Patreon_Wordpress::$patreon_content_sync->add_update_patreon_post( $patreon_post );
					
					if ( !$result ) {
						// Failure. Error handling if necessary
						
					}
					
				}
				
				if( $event == 'posts:update' ) {
					
					// Update relevant post.
					
					$patreon_post_id = $event_info['data']['id'];
					
					// Get Patreon post
					
					$creator_access_token = get_option( 'patreon-creators-access-token', false );
					$client_id 			  = get_option( 'patreon-client-id', false );

					$patreon_post = false;
					
					if ( $creator_access_token AND $client_id ) {
						
						// Create new api object
						$api_client = new Patreon_API( $creator_access_token );
						
						$patreon_post = $api_client->get_post( $patreon_post_id );
						
					}
					
					if ( !$patreon_post OR !isset( $patreon_post['data']['id'] ) OR $patreon_post['data']['id'] == '' ) {
						// Couldn't get this post. Skip
						return;
					}
					
					if ( get_option( 'patreon-update-posts', 'no' ) == 'yes' ) {
						
						$result = $Patreon_Wordpress::$patreon_content_sync->add_update_patreon_post( $patreon_post );
					
						if ( !$result ) {
							// Failure. Error handling if necessary - not needed for now
						}
					}
					
				}				
				
				if( $event == 'posts:delete' ) {
					
					// Delete relevant post.
					
					// Get matching WP post from post meta:
					
					$patreon_post_id = $event_info['data']['id'];
					
					if ( get_option( 'patreon-remove-deleted-posts', 'no' ) == 'yes' ) {
					
						$wp_post_id = $Patreon_Wordpress::$patreon_content_sync->get_matching_post_by_patreon_post_id( $patreon_post_id );
						
						$result = $Patreon_Wordpress::$patreon_content_sync->delete_patreon_post( $wp_post_id );
											
						if ( !$result OR is_null( $result ) ) {
							// Delete failed - this may be a local issue. Can be used to give error to Patreon via header in future
						}
					}
				
				}
				
				status_header( 200 );
				nocache_headers();
				exit;
			
			}
		
		}
		
	}
	
}