<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_Compatibility {
	
	// Carries site health information - errors, warnings, notices, solutions
	public static $site_health_info = array();
	public static $toggle_warning = false;

	function __construct() {
		
		add_action( 'init', array( $this, 'set_cache_exceptions' ) );
		add_action( 'admin_init', array( $this, 'check_wp_super_cache_settings' ) );
		add_action( 'admin_init', array( $this, 'check_permalinks' ) );
		
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
	
	public function check_permalinks() {
		
		// Checks if pretty permalinks are enabled. PW requires pretty permalinks (any). Default link format wont work.
				
		if ( !get_option( 'permalink_structure' ) ) {
			
			// The link structure is default. This will break flow redirections Queue warning.
			
			self::$toggle_warning = true;
			
			self::$site_health_info['pretty_permalinks_are_off'] = array(
				'notice' => PATREON_PRETTY_PERMALINKS_ARE_OFF,
				// We can use this for ordering notices on health page
				'heading' => PATREON_PRETTY_PERMALINKS_ARE_OFF_HEADING,
				'order' => 1,
				'level' => 'critical',
			);
			
		}
		
	}
	
	public function check_wp_super_cache_settings() {
		
		// Checks any important settings of WP super cache which may affect Patreon behavior if WP super cache is installed
		
		// Return if its not admin page and no one is going to see the notices
		if ( !is_admin() ) {
			return;
		}
	
		// Bail out if WP super cache is not installed
		if ( !Patreon_Wordpress::check_plugin_exists( 'wp-super-cache' ) ) {
			return;			
		}
		// Bail out if WP super cache is not active
		if ( !Patreon_Wordpress::check_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
			return;			
		}
		
		// Wp super cache loads its options into globals
		global $wp_cache_not_logged_in;
		global $wp_cache_make_known_anon;
	
		// echo admin_url('options-general.php?page=wpsupercache');

		$toggle_warning = false;
		
		// Check for cache not logged in being not set - if its not set, logged in users are served cached files
		
		if ( !$wp_cache_not_logged_in ) {
	
			self::$toggle_warning = true;

			self::$site_health_info['wp_super_cache_caches_pages_for_known_users'] = array(
				'notice' => PATREON_WP_SUPER_CACHE_LOGGED_IN_USERS_ENABLED,
				'heading' => PATREON_WP_SUPER_CACHE_LOGGED_IN_USERS_ENABLED_HEADING,
				// We can use this for ordering notices on health page
				'order' => 2,
				'level' => 'important',
			);
			
		}
		
		// Check if Make all anon is set - if its set, logged in users are served cached files
		
		if ( $wp_cache_make_known_anon ) {
			
			self::$toggle_warning = true;
			
			self::$site_health_info['wp_super_cache_makes_logged_in_anonymous'] = array(
				'notice' => PATREON_WP_SUPER_CACHE_MAKE_KNOWN_ANON_ENABLED,
				'heading' => PATREON_WP_SUPER_CACHE_MAKE_KNOWN_ANON_ENABLED_HEADING,
				// We can use this for ordering notices on health page
				'order' => 3,
				'level' => 'important',
			);
			
		}
		
	}
	
}