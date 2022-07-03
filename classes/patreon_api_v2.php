<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// This file will include the v2 versin of Patreon_API class until v2 transition is complete

class Patreon_API {

	public $access_token;

	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}
	
	public function fetch_user() {

		// We construct the old return from the new returns by combining /me and pledge details

		$api_return = $this->__get_json( "identity?include=memberships.currently_entitled_tiers,memberships.campaign&fields[user]=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified&fields[member]=currently_entitled_amount_cents,lifetime_support_cents,campaign_lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start,pledge_cadence" );

		$creator_id = get_option( 'patreon-creator-id', false );
		$campaign_id = get_option( 'patreon-campaign-id', false );

		
		if ( isset( $api_return['included'][0] ) AND is_array( $api_return['included'][0] ) ) {
			
			// Iterate through included memberships and find the one that matches the campaign.

			foreach ($api_return['included'] as $key => $value) {

				if ( $api_return['included'][$key]['type'] == 'member' AND $api_return['included'][$key]['relationships']['campaign']['data']['id'] == $campaign_id ) {
					
					// The below procedure will take take the matching membership out of the array, put it to the top and reindex numberic keys. This will allow backwards compatibility to be kept
					$membership = $api_return['included'][$key];
					unset( $api_return['included'][$key] );
					array_unshift( $api_return['included'], $membership);
					array_values( $api_return['included']);

					$api_return['included'][0]["relationships"]["creator"]["data"]["id"] = $creator_id;
					$api_return['included'][0]['type']                                   = 'pledge';
					$api_return['included'][0]['attributes']['amount_cents']             = $api_return['included'][0]['attributes']['currently_entitled_amount_cents'];
					$api_return['included'][0]['attributes']['created_at']               = $api_return['included'][0]['attributes']['pledge_relationship_start'];
					$api_return['included'][0]['attributes']['lifetime_support_cents']               = $api_return['included'][0]['attributes']['campaign_lifetime_support_cents'];
					
					if ( $api_return['included'][0]['attributes']['last_charge_status'] != 'Paid' ) {
						$api_return['included'][0]['attributes']['declined_since'] = $api_return['included'][0]['attributes']['last_charge_date'];
					}
				}
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

	public function get_posts( $campaign_id = false, $page_size = 1, $cursor = null ) {
		
		// Gets posts of relevant campaign
		
		if ( !$campaign_id ) {
			$campaign_id = get_option( 'patreon-campaign-id', false );
		}
		
		$request = 'campaigns/'. $campaign_id .'/posts?page%5Bcount%5D=' . $page_size;
		
		if ( isset( $cursor ) ) {
			$cursor = urlencode($cursor);
			$request .= '&page%5Bcursor%5D='. $cursor;
		}
		
		if ( $campaign_id ) {
			return $this->__get_json( $request );
		}
		
		return false;
		
	}
		
	public function get_post( $post_id ) {
		return $this->__get_json( 'posts/' . $post_id . '?fields[post]=title,content,is_paid,is_public,published_at,url,embed_data,embed_url,app_id,app_status' );
	}
	
	public function add_post_webhook( $params = array() ) {
		
		// Contacts api to create or refresh client
		// Only uses v2
		
		if ( !isset( $params['campaign_id'] ) ) {
			$params['campaign_id'] = get_option( 'patreon-campaign-id', false );
		}
	
		// Site url with forced https
		
		$webhook_response_uri = site_url( '', 'https' ) . '/patreon-webhooks/';

		// Check if this url is legitimate with https:
		
		$check_url = wp_remote_get( $webhook_response_uri );

		if ( is_wp_error( $check_url ) ) {
			return;
		}
		
		$postfields = array(
			'data' => array (
				'type' => 'webhook',
				'attributes' => array (
					'triggers' => array (
						'posts:publish',
						'posts:update',
						'posts:delete',
					),
					'uri' => $webhook_response_uri,
				),
				'relationships' => array (
					'campaign' => array (
						'data' => array (
							'type' => 'campaign',
							'id' => $params['campaign_id'],
						),
					),
				),
			),
		);

		$postfields= json_encode( $postfields );
		
		$args = array(
			'method' => 'POST',
			'params' => $postfields,
		);
		
		return $this->__get_json( "webhooks", $args );
	}
	public function delete_post_webhook( $webhook_id ) {
		
		// Deletes a webhook
				
		$args = array(
			'method' => 'DELETE',
			'return_result_format' => 'full',
		);
		
		return $this->__get_json( "webhooks/" . $webhook_id, $args );
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

	public function get_call_limits() {
		
		$campaign_id = get_option( 'patreon-campaign-id', '' );

		return array( 
		
			'campaigns'                              => array( 'limit' => 30,   'period' => 5 * 60 ),
			'campaigns/'. $campaign_id . '/members'  => array( 'limit' => 30, 'period' => 1 * 60 ),
			'campaigns/'. $campaign_id               => array( 'limit' => 30, 'period' => 1 * 60 ),
			'campaigns/'. $campaign_id . '/posts'    => array( 'limit' => 30, 'period' => 1 * 60 ),
			'posts'                                  => array( 'limit' => 30, 'period' => 1 * 60 ),
			'webhooks'                               => array( 'limit' => 12, 'period' => 1 * 60 ),
			'clients'                                => array( 'limit' => 12, 'period' => 1 * 60 ),
		
		);
	}
	
	public function throttle_call( $call ) {
		
		// Throttles the call

		$limits = $this->get_call_limits();
		
		$break_the_call_up = explode( '/', $call );
		
		// If the call is for webhooks/ or clients/, throttle it over the root endpoints:
		
		if ( $break_the_call_up[0] == 'webhooks' ) {
			$call = 'webhooks';
		}
		
		if ( $break_the_call_up[0] == 'clients' ) {
			$call = 'clients';
		}
		
		if ( !array_key_exists( $call, $limits ) ) {
			// Not in the least. Leave the throttling of this call to the api
			return false;
		}

		// Get the time of the last matching call
		$last_called = get_option( 'patreon_api_call_count_' . str_replace( '/', '_', $call ), false );

		if ( $last_called AND isset($last_called['counter_start']) AND $last_called['counter_start'] >= ( time() - $limits[$call]['period'] )) {
			
			// There is a counter that started in the last 5 minutes.
			
			if ( $last_called['count'] >= $limits[$call]['limit'] ) {
				// Throttle
				return true;
			}
			
		}
		
		// Either there is no counter, or the number of calls are within the limit. Don't throttle.
		
		return false;

	}
	
	public function increment_call_count( $call ) {
		
		$break_the_call_up = explode( '/', $call );
		
		// If the call is for webhooks/ or clients/, throttle it over the root endpoints:
		
		if ( $break_the_call_up[0] == 'webhooks' ) {
			$call = 'webhooks';
		}
		
		if ( $break_the_call_up[0] == 'clients' ) {
			$call = 'clients';
		}

		// Get the time of the last matching call
		$last_called = get_option( 'patreon_api_call_count_' . str_replace( '/', '_', $call ), false );

		$limits = $this->get_call_limits();

		if ( !array_key_exists( $call, $limits ) ) {
			// Not in the list. Leave the throttling of this call to the api
			return;
		}

		if ( !$last_called OR !isset($last_called['counter_start']) OR $last_called['counter_start'] < ( time() - $limits[$call]['period'] )) {
			
			// A call counter for this call does not exist or expired. Start a counter.

			$last_called = array( 'counter_start' => time(), 'count' => 1 );
			
			update_option( 'patreon_api_call_count_' . str_replace( '/', '_', $call ) , $last_called );

			return;
		}
		
		// A counter that started in the last 5 minutes exists. Increment the counter.
		
		$last_called['count']++;

		update_option( 'patreon_api_call_count_' . str_replace( '/', '_', $call ), $last_called );
		
	}
		
	public function __get_json( $suffix, $args = array() ) {
		
		// Defaults

		// Get the call endpoint

		$limits = $this->get_call_limits();

        $received_call = explode( '?', $suffix );

		if ( isset($received_call[0]) ) {
			$call = $received_call[0];
		}

		if ( $this->throttle_call( $call ) ) {
            return 'throttled_locally';
		}
		
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
			'User-Agent' => 'Patreon-Wordpress, version ' . PATREON_WORDPRESS_VERSION . PATREON_WORDPRESS_BETA_STRING . ', platform ' . php_uname('s') . '-' . php_uname( 'r' ) . ' PW-Site: ' . get_site_url() . ' PW-Campaign-Id: ' . get_option( 'patreon-campaign-id', '' ) . ' PW-WP-Version: '. get_bloginfo( 'version' ) . ' PW-PHP-Version: '. phpversion(),
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
			
			if ( isset( $args['force_get'] ) AND $args['force_get'] ) {
				$response = wp_remote_request( $api_endpoint, $api_request );
			}
			else {
				$response = wp_remote_post( $api_endpoint, $api_request );
			}
			
		}

		$this->increment_call_count( $call );
		
		$result   = $response;

		if ( is_wp_error( $response ) ) {
			
			$result                    = array( 'error' => $response->get_error_message() );
			$GLOBALS['patreon_notice'] = $response->get_error_message();
		
			$caller = 'none';
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			
			if ( isset( $backtrace[1]['function'] ) ) {
				$caller = $backtrace[1]['function'];
			}
			
			Patreon_Wordpress::log_connection_error( $caller . ' - API v2 Class - WP error message ' . $GLOBALS['patreon_notice'] );
			
			return $result;
			
		}

		// Log the connection as having error if the return is not 200
		
		if ( isset( $response['response']['code'] ) AND $response['response']['code'] != '200' AND $response['response']['code'] != '201' )  {
			
			$caller = 'none';
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			
			if ( isset( $backtrace[1]['function'] ) ) {
				$caller = $backtrace[1]['function'];
			}

			$uuid = wp_remote_retrieve_header( $response, 'x-patreon-uuid' );
			
			Patreon_Wordpress::log_connection_error( $caller . ' - API v2 Class - UUID ' .$uuid . ' - ' . 'Response code: ' . $response['response']['code'] . ' Response :' . $response['body'] );
			
		}
		
		// Return full result if full result was requested
		if ( $return_result_format == 'full' ) {
			return $response;
		}
		
		// Return json decoded response body by default
		return json_decode( $response['body'], true );

	}
	
}