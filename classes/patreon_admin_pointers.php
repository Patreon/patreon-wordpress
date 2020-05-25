<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Houses the admin pointers class and added admin pointers

class Patreon_Admin_Pointers {

	public function __construct() {
		
		add_action( 'admin_enqueue_scripts',  array( $this, 'load_pointers' ) );
		add_filter( 'patreon-admin-pointers-dashboard', array( &$this, 'cache_option_pointer' ) );
		add_filter( 'patreon-admin-pointers-dashboard', array( &$this, 'pmp_compatibility_pointer' ) );
		add_filter( 'patreon-admin-pointers-dashboard', array( &$this, 'post_sync_pointer' ) );
	}
	
	public function load_pointers( $hook_suffix ) {

		// Taken from wptuts how to
		// Loads the code needed to display pointers
			 
		$screen = get_current_screen();
		$screen_id = $screen->id;
		
		// Get pointers for this screen
		$pointers = apply_filters( 'patreon-admin-pointers-' . $screen_id, array() );
		 
		if ( ! $pointers || ! is_array( $pointers ) ) {
			return;
		}
	 
		// Get dismissed pointers
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers =array();
	 
		// Check pointers and remove dismissed ones.
		foreach ( $pointers as $pointer_id => $pointer ) {
	 
			// Sanity check
			if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) ) {
				continue;
			}
				
			$pointer['pointer_id'] = $pointer_id;
	 
			// Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] =  $pointer;
			
		}
			
		// No valid pointers? Stop here.
		if ( empty( $valid_pointers ) ) {
			return;
		}

		// Add pointers style to queue.
		wp_enqueue_style( 'wp-pointer' );
	 
		// Add pointers script to queue. Add custom script.
		wp_enqueue_script( 'patreon-wordpress-pointer', PATREON_PLUGIN_ASSETS . '/js/pointers.js', array( 'wp-pointer' ), PATREON_WORDPRESS_VERSION, true );
	 
		// Add pointer options to script.
		wp_localize_script( 'patreon-wordpress-pointer', 'patreon_wordpress_pointer', $valid_pointers );
				
	}
	
	// Pointers start here
	
	public function cache_option_pointer( $pointers ) {
		
		// We want this pointer to appear only for existing installations at the date of publication of this version (1.3.9). 2 months after release of this version, this pointer can be removed.
		
		$plugin_activated =	get_option( 'patreon-plugin-first-activated' );
		
		// If the plugin activation was not before release date of this version, bail out. Time nudged 5 hours ahead to make sure
		
		if ( $plugin_activated > 1573870261 ) {
			return;
		}
		
		$pointers['patreon_test_pointer'] = array(
			'target' => '#toplevel_page_patreon-plugin',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
			'New Patreon setting',
					'Your Patreon integration now tries to prevent caching of your gated content. This will help users to access the content they unlocked easily instead of still seeing the locked version that was cached. If you need to turn off this feature you can set "Prevent caching of gated content" option to "No".'
				),
				'position' => array( 'edge' => 'top', 'align' => 'middle' )
			)
		);
		if ( $plugin_activated > 1583330333 ) {
			return;
		}
		
		$pointers['pmp_compatibility'] = array(
			'target' => '#toplevel_page_patreon-plugin',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
			'Patreon WordPress is now compatible with Paid Memberships Pro',
					'You can now use Patreon WordPress to gate content alongside Paid Memberships Pro. You can gate content with PW, PMP, or both, using monthly subscriptions/pledges. Any qualifying Patreon patron or PMP member can unlock content gated with either plugin.'
				),
				'position' => array( 'edge' => 'top', 'align' => 'middle' )
			)
		);
		return $pointers;
	}
	
	public function pmp_compatibility_pointer( $pointers ) {
		
		// We want this pointer to appear only for existing installations 
		
		$plugin_activated =	get_option( 'patreon-plugin-first-activated' );
		
		// Set to appear to installations completed until a week after date of this commit
		if ( $plugin_activated > 1583884800 ) {
			return;
		}
		
		$pointers['pmp_compatibility'] = array(
			'target' => '#menu-posts',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
			'Patreon WordPress is now compatible with Paid Memberships Pro',
					'You can now use Patreon WordPress to gate posts alongside Paid Memberships Pro. You can gate content with PW, PMP, or both. Any qualifying Patreon patron or PMP monthly member can unlock content gated with either plugin. Read details here <a href="https://www.patreondevelopers.com/t/patreon-wordpress-is-now-compatible-with-paid-memberships-pro/2823" target="_blank">here</a>.'
				),
				'position' => array( 'edge' => 'top', 'align' => 'middle' )
			)
		);
		
		return $pointers;
		
	}
	public function post_sync_pointer( $pointers ) {
		
		// We want this pointer to appear only for existing installations 
		
		$plugin_activated =	get_option( 'patreon-plugin-first-activated' );

		// Set to appear to installations completed until a week after date of this commit
		if ( $plugin_activated > 1590510554 ) {
			return;
		}
		
		$pointers['patreon_post_sync'] = array(
			'target' => '#toplevel_page_patreon-plugin',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					'You can now sync your Patreon posts!',
					'Turn on post sync to have your Patreon posts automatically synced to your WP site! Works totally hands off! <br /><br />Set up post sync <a href="' . admin_url( 'admin.php?page=patreon_wordpress_setup_wizard&setup_stage=post_sync_1' ) . '">here</a>. <br /><br />Read details here <a href="https://www.patreondevelopers.com/t/patreon-wordpress-post-sync-guide/3069" target="_blank">here</a>.'
				),
				'position' => array( 'edge' => 'top', 'align' => 'middle' )
			)
		);
		
		return $pointers;
		
	}
	
	
}