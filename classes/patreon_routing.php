<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Routing {

	function __construct() {
		add_action( 'generate_rewrite_rules', array($this, 'add_rewrite_rules') );
		add_filter( 'query_vars', array($this, 'query_vars') );
		add_action( 'parse_request', array($this, 'parse_request') );
		add_action( 'init', array($this, 'force_rewrite_rules') );
		add_action( 'init', array($this,'set_patreon_nonce'), 1);
	}

	public static function activate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	public static function deactivate() {
		remove_action( 'generate_rewrite_rules','add_rewrite_rules' );
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function force_rewrite_rules() {
		global $wp_rewrite;
		if(get_option('patreon-rewrite-rules-flushed', false) == false) {
			$wp_rewrite->flush_rules();
			update_option( 'patreon-rewrite-rules-flushed', true );
		}
	}

	function add_rewrite_rules($wp_rewrite) {

		$rules = array(
			'patreon-authorization\/?$' => 'index.php?patreon-oauth=true',
		);

		$wp_rewrite->rules = $rules + (array)$wp_rewrite->rules;

	}

	function query_vars($public_query_vars) {
		array_push($public_query_vars, 'patreon-oauth');
		array_push($public_query_vars, 'code');
		array_push($public_query_vars, 'state');
		array_push($public_query_vars, 'patreon-redirect');
		return $public_query_vars;
	}

	function set_patreon_nonce() {

		if(isset($_COOKIE['patreon_nonce']) == false) {
			$nonce = md5(bin2hex(openssl_random_pseudo_bytes(32) . md5(time()) . openssl_random_pseudo_bytes(32)));
			setcookie('patreon_nonce',$nonce, 0, COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE['patreon_nonce'] = $nonce;
 		}

	}

	function parse_request( &$wp ) {

		if (strpos($_SERVER['REQUEST_URI'],'/patreon-authorization/')!==false) {
	
			if(array_key_exists( 'code', $wp->query_vars )) {
				

				// Get state vars if they exist
	
				if($wp->query_vars['state']!='') {
					$state = unserialize(base64_decode($wp->query_vars['state']));
				}

				$redirect = false;
				
				if(get_option('patreon-enable-redirect-to-page-after-login', false)) {
					$redirect = get_option('patreon-enable-redirect-to-page-id', get_option('page_on_front') );
				}
							
				// Check if final_redirect exists in state vars - if so, override redirect:
	
				if($state['final_redirect_uri']!='') {
					$redirect = $state['final_redirect_uri'];
				}		
			
				
				$redirect = apply_filters('ptrn/redirect', $redirect);		
	
				if($state['patreon_nonce'] != $_COOKIE['patreon_nonce']) {
					// Nonces do not match. Abort, show message.
				
					$redirect = add_query_arg( 'patreon_message', 'patreon_nonces_dont_match', $redirect);
					
					wp_redirect( $redirect );
					exit;
				}
				
				if(get_option('patreon-client-id', false) == false || get_option('patreon-client-secret', false) == false) {

					/* redirect to homepage because of oauth client_id or secure_key error  */
					$redirect = add_query_arg( 'patreon_message', 'patreon_api_credentials_missing', $redirect);
					wp_redirect( $redirect );
					exit;			

				} else {
					$oauth_client = new Patreon_Oauth;
				}

				$tokens = $oauth_client->get_tokens($wp->query_vars['code'], site_url().'/patreon-authorization/');

				if(array_key_exists('error', $tokens)) {

					/* redirect to homepage because of some error #HANDLE_ERROR */
					$redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_api_error', $redirect);
					wp_redirect( $redirect );
					exit;

				} else {

					$api_client = new Patreon_API($tokens['access_token']);
					
					$user_response = $api_client->fetch_user();
			
					if(apply_filters('ptrn/force_strict_oauth',get_option('patreon-enable-strict-oauth', false))) {

						$user = Patreon_Login::updateLoggedInUserForStrictoAuth($user_response, $tokens, $redirect);
					} else {
								
						$user = Patreon_Login::createOrLogInUserFromPatreon($user_response, $tokens, $redirect);
					}

					//shouldn't get here
					$redirect = add_query_arg( 'patreon_message', 'patreon_weird_redirection_at_login', $redirect);
					
					wp_redirect( $redirect );
					exit;

				}
			} else {

				wp_redirect( home_url() );
				exit;

			}
		}
	}
}


?>
