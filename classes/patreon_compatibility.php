<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_Compatibility {

	function __construct() {
		
		add_action( 'init', array( $this, 'set_cache_exceptions' ) );
		
	}

	public function set_cache_exceptions() {
		// Sets exceptions for caching to prevent important pages from being cached
		
		// Check for flow or authorization pages which shouldnt be cached
		if ( strpos( $_SERVER['REQUEST_URI'],'/patreon-flow/' ) !== false 
			OR strpos( $_SERVER['REQUEST_URI'], '/patreon-authorization/' ) !== false
		) {
			
			// We are in either of these pages. Set do not cache page constant
			define( 'DONOTCACHEPAGE', true );
			// This constant is used in many plugins - wp super cache, w3 total cache, woocommerce etc and it should disable caching for this page
		
		}
	}
	
}