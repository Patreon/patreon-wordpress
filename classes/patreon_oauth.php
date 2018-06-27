<?php


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Patreon_OAuth {

	private $client_id;
	private $client_secret;

	public function __construct() {
		$this->client_id = get_option('patreon-client-id', false);
		$this->client_secret = get_option('patreon-client-secret', false);
	}

	public function get_tokens($code, $redirect_uri) {
		return $this->__update_token(array(
			"grant_type" => "authorization_code",
			"code" => $code,
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret,
			"redirect_uri" => $redirect_uri
		));
	}

	public function refresh_token($refresh_token, $redirect_uri) {
		return $this->__update_token(array(
			"grant_type" => "refresh_token",
			"refresh_token" => $refresh_token,
			"client_id" => $this->client_id,
			"client_secret" => $this->client_secret
		));
	}

	private function __update_token($params) {
		
		$api_endpoint = "https://api.patreon.com/oauth2/token";
	
		$api_request = array(
			'method' => 'POST',
			'body' => $params,
		);

		$response = wp_remote_post( $api_endpoint, $api_request );

		return json_decode($response['body'], true);

	}
}

?>
