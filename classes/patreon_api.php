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

	public function fetch_campaign() {
		return $this->__get_json("current_user/campaigns?include=rewards,creator,goals");
	}

}

?>