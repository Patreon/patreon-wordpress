<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Houses the admin pointers class and added admin pointers

class Patreon_Admin_Pointers {

	public $access_token;

	public function __construct() {
		
		add_action( 'admin_enqueue_scripts',  array( $this, 'load_pointers' ) );
		add_filter( 'patreon-admin-pointers-dashboard', array( &$this, 'widgets_pointer' ) );
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
	
	public function widgets_pointer( $pointers ) {
		
		$pointers['patreon_test_pointer'] = array(
			'target' => '#menu-appearance',
			'options' => array(
				'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
					'Pointer Title',
					'Pointer Message'
				),
				'position' => array( 'edge' => 'top', 'align' => 'middle' )
			)
		);
		return $pointers;
	}
	
	
}