<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// This file will include the v2 versin of Patreon_API class until v2 transition is complete

class Patreon_API {

	private $access_token;

	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}
	
	public function fetch_user() {

		// We construct the old return from the new returns by combining /me and pledge details

		$api_return = $this->__get_json( "identity?include=memberships&fields[user]=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified&fields[member]=currently_entitled_amount_cents,lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start" );
		
		$creator_id = get_option( 'patreon-creator-id', false );
		
		if ( isset( $api_return['included'][0] ) AND is_array( $api_return['included'][0] ) ) {
			
			$api_return['included'][0]["relationships"]["creator"]["data"]["id"] = $creator_id;
			$api_return['included'][0]['type']                                   = 'pledge';
			$api_return['included'][0]['attributes']['amount_cents']             = $api_return['included'][0]['attributes']['currently_entitled_amount_cents'];
			$api_return['included'][0]['attributes']['created_at']               = $api_return['included'][0]['attributes']['pledge_relationship_start'];
			
			if ( $api_return['included'][0]['attributes']['last_charge_status'] != 'Paid' ) {
				$api_return['included'][0]['attributes']['declined_since'] = $api_return['included'][0]['attributes']['last_charge_date'];
			}
			
		}
		
		return $api_return;
	}
	
	public function fetch_campaign_and_patrons() {
		return $this->__get_json( "campaigns" );
	}
		
	public function fetch_creator_info() {
			
		$api_return = $this->__get_json( "campaigns?include=creator&fields[campaign]=created_at,creation_name,discord_server_id,image_small_url,image_url,is_charged_immediately,is_monthly,is_nsfw,main_video_embed,main_video_url,one_liner,one_liner,patron_count,pay_per_name,pledge_url,published_at,summary,thanks_embed,thanks_msg,thanks_video_url,has_rss,has_sent_rss_notify,rss_feed_title,rss_artwork_url,patron_count,discord_server_id,google_analytics_id&fields[user]=about,created,email,first_name,full_name,image_url,last_name,social_connections,thumb_url,url,vanity,is_email_verified" );

		return $api_return;
	
	}

	public function fetch_campaign() {
		return $this->__get_json( "campaigns?include=tiers,creator,goals" );
	}
	
	public function fetch_tiers() {
	
		$result = $this->__get_json( "campaigns?include=tiers&fields[tier]=amount_cents,created_at,description,discord_role_ids,edited_at,image_url,patron_count,post_count,published,published_at,remaining,requires_shipping,title,unpublished_at,url,user_limit" );
		
		// v2 doesnt seem to return the default tiers. We have to add them manually:
		if ( isset( $result['included'] ) ) {
			
			array_unshift( 
				$result['included'], 
				array(
					'attributes' => array(
						'amount' => 1,
						'amount_cents' => 1,
						'created_at' => '',
						'description' => 'Patrons Only',
						'remaining' => 0,
						'requires_shipping' => null,
						'url' => '',
						'user_limit' => null,
						),
						'id' => 0,
						'type' => 'reward',
				)
			);
			
			array_unshift(
				$result['included'], 
				array(
					'attributes' => array(
						'amount' => 0,
						'amount_cents' => 0,
						'created_at' => '',
						'description' => 'Everyone',
						'remaining' => 0,
						'requires_shipping' => null,
						'url' => '',
						'user_limit' => null,
						),
						'id' => -1,
						'type' => 'reward',
				)
			);
			
		}
		
		return $result;
	}
	
	public function create_refresh_client( $params ) {
		
		// Contacts api to create or refresh client
		// Only uses v2
		
		$args = array(
			'method' => 'POST',
			'params' => $params,
		);
		
		return $this->__get_json( "clients?include=creator_token", $args );
	}
	
	public function delete_client( $params ) {
		
		// Contacts api to create or refresh client
		// Only uses v2 
		
		$client_id 			  = get_option( 'patreon-client-id', false );
		
		$args = array(
			'method' => 'DELETE',
			'params' => $params,
			'return_result_format' => 'full',
		);

		return $this->__get_json( "clients/".$client_id, $args );
	}
		
	private function __get_json( $suffix, $args = array() ) {
		
		// Defaults
		
		$method = 'GET';
		$params = false;
		$api_endpoint = "https://www.patreon.com/api/oauth2/v2/" . $suffix;
		$return_result_format = 'body';

		// Overrides
		
		if ( isset( $args['method'] ) AND $args['method'] != '' ) {
			$method = $args['method'];
		}
		
		if ( isset( $args['return_result_format'] ) AND $args['return_result_format'] != '' ) {
			$return_result_format = $args['return_result_format'];
		}
		
		if ( isset( $args['params'] ) ) {
			$params = $args['params'];
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'User-Agent' => 'Patreon-Wordpress, version ' . PATREON_WORDPRESS_VERSION . PATREON_WORDPRESS_BETA_STRING . ', platform ' . php_uname('s') . '-' . php_uname( 'r' ),
		);
		
		$api_request = array(
			'headers' => $headers,
			'method'  => $method,
		);

		if ( $params ) {
			$api_request['body'] = $params;
			$api_request['data_format'] = 'body';
			$api_request['headers']['content-type'] = 'application/json';
		}

		if ( $method == 'GET' ) {
			$response = wp_remote_request( $api_endpoint, $api_request );
		}
		
		if ( $method == 'POST' ) {
			$response = wp_remote_post( $api_endpoint, $api_request );
		}
		
		if ( $method == 'DELETE' ) {
			$response = wp_remote_post( $api_endpoint, $api_request );
		}
		
		$result   = $response;

		if ( is_wp_error( $response ) ) {
			
			$result                    = array( 'error' => $response->get_error_message() );
			$GLOBALS['patreon_notice'] = $response->get_error_message();
			return $result;
			
		}
		
		// Return full result if full result was requested
		if ( $return_result_format == 'full' ) {
			return $response;
		}
		
		// Return json decoded response body by default
		return json_decode( $response['body'], true );

	}
	
}