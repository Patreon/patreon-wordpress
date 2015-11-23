<?php

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Routing {

	function __construct() {
		register_activation_hook( __FILE__, array($this, 'activate') );
		register_deactivation_hook( __FILE__, array($this, 'deactivate') );
		add_action( 'generate_rewrite_rules', array($this, 'add_rewrite_rules') );
		add_filter( 'query_vars', array($this, 'query_vars') );
		add_action( 'parse_request', array($this, 'parse_request') );
	}

	function activate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function deactivate() {
		remove_action( 'generate_rewrite_rules','add_rewrite_rules' );
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
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
		return $public_query_vars;
	}

	function parse_request( &$wp ) {

		if (array_key_exists( 'patreon-oauth', $wp->query_vars )) {

			if(array_key_exists( 'code', $wp->query_vars )) {

				if(get_option('patreon-client-id', false) == false || get_option('patreon-client-secret', false) == false) {

					/* redirect to homepage because of oauth client_id or secure_key error #HANDLE_ERROR */
					wp_redirect( home_url() );
					exit;
				} else {
					$oauth_client = new Patreon_Oauth;
				}

				$tokens = $oauth_client->get_tokens($wp->query_vars['code'], site_url().'/patreon-authorization/');

				if(array_key_exists('error', $tokens)) {

					/* redirect to homepage because of some error #HANDLE_ERROR */
					wp_redirect( home_url() );
					exit;

				} else {

					/* redirect to homepage successfully #HANDLE_SUCCESS */
					$api_client = new Patreon_API($tokens['access_token']);
					$user_response = $api_client->fetch_user();
					$user = Patreon_Login::createUserFromPatreon($user_response, $tokens);

					wp_redirect( home_url(), 302 );
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