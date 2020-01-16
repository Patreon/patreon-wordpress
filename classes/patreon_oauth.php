<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Patreon_OAuth {

	public $client_id;
	public $client_secret;

	public function __construct() {
		
		$this->client_id     = get_option( 'patreon-client-id', false );
		$this->client_secret = get_option( 'patreon-client-secret', false );
		
	}

	public function get_tokens( $code, $redirect_uri, $params = array() ) {
		
		return $this->__update_token( 
			array_merge( 
				array(

				"grant_type" 	=> "authorization_code",
				"code" 	     	=> $code,
				"client_id"  	=> $this->client_id,
				"client_secret" => $this->client_secret,
				"redirect_uri"  => $redirect_uri
				),
				$params 
			)
		);
		
	}

	public function refresh_token( $refresh_token, $redirect_uri ) {
		
		return $this->__update_token( array(
			"grant_type" 	=> "refresh_token",
			"refresh_token" => $refresh_token,
			"client_id" 	=> $this->client_id,
			"client_secret" => $this->client_secret
		) );
		
	}

	private function __update_token( $params ) {
		
		$api_endpoint = "https://api.patreon.com/oauth2/token";
		
		$headers = array(
			'User-Agent' => 'Patreon-Wordpress, version ' . PATREON_WORDPRESS_VERSION . PATREON_WORDPRESS_BETA_STRING . ', platform ' . php_uname('s') . '-' . php_uname( 'r' ),
		);		
	
		$api_request = array(
			'method' => 'POST',
			'body'   => $params,
			'headers' => $headers,
		);

		$response = wp_remote_post( $api_endpoint, $api_request );
				
		if ( is_wp_error( $response ) ) {
			
			$result                    = array( 'error' => $response->get_error_message() );
			$GLOBALS['patreon_notice'] = $response->get_error_message();
			
			Patreon_Wordpress::log_connection_error( $GLOBALS['patreon_notice'] );
			
			return $result;
			
		}

		$response_decoded = json_decode( $response['body'], true );
		
		// Log the connection as having error if the return is not 200
		
		if ( isset( $response['response']['code'] ) AND $response['response']['code'] != '200' )  {
			
			Patreon_Wordpress::log_connection_error( 'Response code: ' . $response['response']['code'] . ' Response :' . $response['body'] );
			
		}
		
		if ( is_array( $response_decoded ) ) {
			return $response_decoded;
		}
	
		// Commented out to address issues caused by Patreon's maintenance in between 01 - 02 Feb 2019 - the plugin was showing Patreon's maintenance page at WP sites yin certain cases
		// echo $response['body'];
		// wp_die();
	}
	
}