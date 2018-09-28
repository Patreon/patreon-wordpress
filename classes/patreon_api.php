<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Patreon_API {

	private $access_token;

	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}
	
	public function fetch_user( $v2 = false ) {

		// Only uses v2 starting from this version!

		// We construct the old return from the new returns by combining /me and pledge details

		$api_return = $this->__get_json( "identity?include=memberships&fields[user]=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified&fields[member]=currently_entitled_amount_cents,lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start", true );
		
		$creator_id = get_option( 'patreon-creator-id', false );
		
		if ( isset( $api_return['included'][0] ) AND is_array( $api_return['included'][0] ) ) {
			
			$api_return['included'][0]["relationships"]["creator"]["data"]["id"] = $creator_id;
			$api_return['included'][0]['type']                                   = 'pledge';
			$api_return['included'][0]['attributes']['amount_cents']             = $api_return['included'][0]['attributes']['currently_entitled_amount_cents'];
			$api_return['included'][0]['attributes']['created_at']               = $api_return['included'][0]['attributes']['pledge_relationship_start'];
			
		}
		
		if ( $api_return['included'][0]['attributes']['last_charge_status'] != 'Paid' ) {
			$api_return['included'][0]['attributes']['declined_since'] = $api_return['included'][0]['attributes']['last_charge_date'];
		}
			
		return $api_return;
	}
	
	public function fetch_campaign_and_patrons($v2 = false ) {
	
		// Below conditional and different endpoint can be deprecated to only use v2 api after transition period. Currently we are using v1 for creator/campaign related calls
		
		if ( $v2 ) {		
			// New call to campaigns doesnt return pledges in v2 api - currently this function is not used anywhere in plugin. If 3rd party devs are using it, it will need to be looked into
			
			// Requires having gotten permission for pledge scope during auth if used for a normal user instead of the creator

			return $this->__get_json( "campaigns" );
		}	

		return $this->__get_json( "current_user/campaigns?include=rewards,creator,goals,pledges" );
		
	}
		
	public function fetch_creator_info( $v2 = false ) {
	
		// Below conditional and different endpoint can be deprecated to only use v2 api after transition period. Currently we are using v1 for creator/campaign related calls
		
		if ( $v2 ) {
			
			// New call to campaigns doesnt return pledges in v2 api - currently this function is not used anywhere in plugin. If 3rd party devs are using it, it will need to be looked into
			
			$api_return                      = $this->__get_json( "identity" );
			$api_return['included'][0]['id'] = $api_return['data'][0]['id'];

			return $api_return;
		}
	
		return $this->__get_json( "current_user/campaigns?include=creator" );
		
	}

	public function fetch_campaign( $v2 = false ) {
		
		// Below conditional and different endpoint can be deprecated to only use v2 api after transition period
		
		if ( $v2 ) {
			return $this->__get_json( "campaigns?include=rewards,creator,goals" );
		}

		return $this->__get_json( "current_user/campaigns?include=rewards,creator,goals" );
		
	}

	private function __get_json( $suffix, $v2 = false ) {		

		$api_endpoint = "https://api.patreon.com/oauth2/api/" . $suffix;

		if ( $v2 ) {
			$api_endpoint = "https://www.patreon.com/api/oauth2/v2/" . $suffix;	
		}
		
		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'User-Agent' => 'Patreon-Wordpress, version ' . PATREON_WORDPRESS_VERSION . ', platform ' . php_uname('s') . '-' . php_uname( 'r' ),
		);
		
		$api_request = array(
			'headers' => $headers,
			'method'  => 'GET',
		);

		$response = wp_remote_request( $api_endpoint, $api_request );
		$result   = $response;

		if ( is_wp_error( $response ) ) {
			
			$result                    = array( 'error' => $response->get_error_message() );
			$GLOBALS['patreon_notice'] = $response->get_error_message();
			return $result;
			
		}
		
		return json_decode( $response['body'], true );
		
	}
	
}