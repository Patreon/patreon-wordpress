<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Houses the admin pointers class and added admin pointers

class Patreon_Content_Sync {

	public function __construct() {
		
		if ( get_option( 'patreon-sync-posts', false ) ) {
			
			add_filter( 'cron_schedules', array( &$this, 'add_patreon_cron_schedules' ) );
			
			// Schedule an action if it's not already scheduled
			if ( !wp_next_scheduled( 'patreon_five_minute_action' ) ) {
				wp_schedule_event( time(), 'patreon_five_minute_cron_schedule', 'patreon_five_minute_action' );
			}
		
			add_action( 'patreon_five_minute_action', array( &$this, 'patreon_five_minute_cron_job' ) );
		}

		// For debug - remove later
		update_option( 'patreon-sync-posts', true );
	}
	
	// Adds Patreon cron schedule if needed
	
	public function add_patreon_cron_schedules( $schedules ) {
		
		// If post sync is on and cron is not added, add cron schedule
		
		$schedules['patreon_five_minute_cron_schedule'] = array(
			'interval' => 300, // 5 min
			'display'  => __( 'Patreon cron - every five minutes' ),
		);
		
		return $schedules;
		
	}
	
	public function patreon_five_minute_cron_job() {
		
		// Check if post sync is on just in case if the cron job somehow persisted despite sync being disabled
		
		if ( get_option( 'patreon-sync-posts', false ) ) {
			$this->import_posts_from_patreon();
		}

	}
	
	public function import_posts_from_patreon() {
	
		$creator_access_token = get_option( 'patreon-creators-access-token', false );
		$client_id 			  = get_option( 'patreon-client-id', false );

		if ( $creator_access_token AND $client_id ) {
			
			// Create new api object

			$api_client = new Patreon_API( $creator_access_token );
			
			$posts = $api_client->get_posts();
			
			foreach ( $posts['data'] as $key => $value ) {
				
				$post = $api_client->get_post( $posts['data'][$key]['id'] );
				echo '<pre>';
				print_r($post);
				echo '</pre>';
				
			}
			
		}
		
	}
	
	
	
	
}