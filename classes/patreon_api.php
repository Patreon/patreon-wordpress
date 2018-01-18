<?php


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Patreon_API {

	private $access_token;

	public function __construct($access_token) {
		$this->access_token = $access_token;
	}
	
	public function fetch_user() {
		return $this->__get_json("current_user");
	}
	public function fetch_campaign_and_patrons() {
		return $this->__get_json("current_user/campaigns?include=rewards,creator,goals,pledges");
	}
		
	public function fetch_creator_info() {
		return $this->__get_json("current_user/campaigns?include=creator");
	}

	public function fetch_campaign() {
		return $this->__get_json("current_user/campaigns?include=rewards,creator,goals");
	}

	private function __get_json($suffix) {
		
		$api_endpoint = "https://api.patreon.com/oauth2/api/" . $suffix;
	
		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
		);
		
		$api_request = array(
			'headers' => $headers,
			'method'  => 'GET',
		);
		
		$response = wp_remote_request( $api_endpoint, $api_request );

		return json_decode($response['body'], true);

	}

}

?>
