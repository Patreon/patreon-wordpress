<?php
namespace patreon;

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

	public function fetch_campaign() {
		return $this->__get_json("current_user/campaigns?include=rewards,creator,goals");
	}

	private function __get_json($suffix) {
		$api_endpoint = "https://api.patreon.com/oauth2/api/" . $suffix;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$authorization_header = "Authorization: Bearer " . $this->access_token;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization_header));
		return json_decode(curl_exec($ch), true);
	}

}

?>
