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
		add_action( 'wp', array( $this, 'set_do_not_cache_flag_for_gated_content' ) );
		add_action( 'admin_init', array( $this, 'check_wp_super_cache_settings' ) );
		add_action( 'admin_init', array( $this, 'check_permalinks' ) );
		// Hook to template_redirect filter so the $post object will be ready before sending headers - add_headers hook wont work
		add_filter( 'template_redirect', array( $this, 'modify_headers' ), 99 );
		
		if ( $this->check_if_plugin_active( 'jetpack/jetpack.php' ) ) {
			add_filter( 'jetpack_photon_skip_image', array( $this, 'jetpack_photon_skip_image' ), 99, 3 );
		}
		
		if ( $this->check_if_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) AND !is_admin() ) {
			
			add_filter('pmpro_has_membership_access_filter', array($this, 'override_pmp_gating_with_pw'), 10, 4);
			add_filter('ptrn/lock_or_not', array($this, 'override_pw_gating_with_pmp'), 10, 4);
			
		}
		
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
	
	public static function check_requirements() {
		
		// Checks if requirements for Patreon WordPress are being met
		
		// Check if permalinks are default (none). PW needs pretty permalinks of any sort
		
		$required = array();
		
		if ( get_option('permalink_structure') == '' ) {
			
			// Empty string - pretty permalinks are not enabled. This requirement fails
			
			$required[] = 'pretty_permalinks_are_required';
			
		}
		
		return $required;
		
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
		global $cache_enabled;

		$toggle_warning = false;
		
		if ( !is_plugin_active( 'wp-super-cache/wp-cache.php' ) OR !$cache_enabled ) {
			// WP Super Cache is not on. bail out
			return;
		}
		
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
	public function set_do_not_cache_flag_for_gated_content() {
		
		// This function checks if a singular content is being displayed and sets the do not cache flag if the content is gated. This is to help prevent caching plugins from caching this content.
		
		// Check if we are to try preventing caching of gated content
		
		if ( get_option( 'patreon-prevent-caching-gated-content', 'yes' ) != 'yes' ) {
			return;
		}

		global $post;

		// Bail out if no post object present
		if ( !$post ) {
			return;
		}
	
		// Bail out if not a singular page/post
		if ( !is_singular() ) {
			return;
		}

		// We are here, it means that this is singular content. Check if it is meant to be gated
		
		$gate_content = false;
		
		$lock_or_not = Patreon_Wordpress::lock_or_not( $post->ID );
			
		if ( isset( $lock_or_not['lock'] ) ) {
			$gate_content = $lock_or_not['lock'];			
		}
		
		if ( $gate_content ) {
			
			// General caching plugin define  - Prevents caching of non cached pages
			define( 'DONOTCACHEPAGE', true );
			
			// WP Super Cache - disables cache for already cached pages
			define( 'WPSC_SERVE_DISABLED', true );
			
			// W3 Total Cache - do not minify JS in gated page
			define( 'DONOTMINIFY', true );
			
			// W3 Total Caache - Do not serve gated page from CDN
			define( 'DONOTCDN', true );
			
			// W3 Total Cache - Do not use object cache for gated page
			define( 'DONOTCACHCEOBJECT', true );
			
			// Litespeed Cache - Equal to DONOTCACHEPAGE flag
			define('LSCACHE_NO_CACHE', true);
			
			// WP Fastest Cache compatibility - prevents page from being served from cache.
			
			if ( $this->check_if_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) AND function_exists( 'wpfc_exclude_current_page' ) ) {
				wpfc_exclude_current_page();
			}
			
		}
		
	}
	public function modify_headers() {
		
		// This function checks if a singular content is being displayed and sets cache control headers if the content is gated. This is to help prevent caching of this content.
		
		// Check if we are to try preventing caching of gated content
		
		if ( get_option( 'patreon-prevent-caching-gated-content', 'yes' ) != 'yes' ) {
			return;
		}

		global $post;

		// Bail out if no post object present
		if ( !$post ) {
			return;
		}

		// Bail out if not a singular page/post
		if ( !is_singular() ) {
			return;
		}

		// We are here, it means that this is singular content. Check if it is meant to be gated
		
		$gate_content = false;
		
		$lock_or_not = Patreon_Wordpress::lock_or_not( $post->ID );

		if ( isset( $lock_or_not['lock'] ) ) {
			$gate_content = $lock_or_not['lock'];			
		}

		if ( $gate_content ) {
			
			// Set the content to be revalidated if 30 seconds passed since the request and to only be cached by browsers/devices

			header( "Cache-control: private, max-age=30, no-cache" );
		
		}
		
	}
	public function check_if_plugin_active( $plugin_slug ) {
		
		// This function is used for checking for plugins without using is_plugin_active() or get_plugins() functions and having to include plugin.php WP include at early hooks. 
				
		// Including plugin.php at early hooks may cause issues with other plugins which include that WP file.  functions due to the fact that if we include wp-admin/includes/plugin.php at an early hook like wp hook, it may cause issues with other plugins which may be including that file.
		
		// Check if file exists in plugins folder first.
		
		if ( !file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
			
			// Not even installed. Bail out
			return false;			
			
		}
		
		// At this point we know plugin is installed. Check if it is active
		
		$active_plugins = get_option( 'active_plugins' );
		
		// Iterate and check for matching slug
		
		foreach ( $active_plugins as $key => $value ) {
			if ( $active_plugins[$key] == $plugin_slug ) {
				// Matches, active. Return true
				return true;
			}
		}
		
		// Here and no matching slug. Plugin is not active.
		
		return false;
		
	}
	
	public function match_pmp_tiers( $patreon_level, $args = array() ) {
		
		// Takes a $ level, matches that to the nearest highest Paid Memberships Pro level and returns the id of that tier
		
		$matching_levels = array();
		
		// Get all membership levels
		
		$pmp_levels = pmpro_getAllLevels( true );
		
		// Iterate membership levels if any exists, and find matching level
		
		
		if ( is_array( $pmp_levels ) AND count( $pmp_levels ) > 0 )  {
			
			foreach( $pmp_levels as $key => $value ) {
				
				$pmp_level = $pmp_levels[$key];
		
				// If its not a reward element, continue, just to make sure
				
				if(
					!isset( $pmp_level->cycle_period )
					OR $pmp_level->cycle_period != 'Month'
				)  {
					
					// Not a monthly cycle. Continue
					continue; 
				}
				
				if ( $patreon_level >= $pmp_level->billing_amount ) {
					
					// Matching level found return id of the tier
					
					$matching_levels[] = $pmp_level;
					
				}
				
			}
			
		}
		
		// Here and no result - no tier found.
		
		return $matching_levels;
		
	}
	
	public function pw_pmp_combined_gate( $user, $post_id ) {
		
		// This function checks whether user qualifies for content through PW or PMP
		
		// If the post is not gated by either PW or PMP, skip
		
		/*
		
		if ( !( $lock_or_not['lock'] OR !pmpro_has_membership_access( $post_id ) ) ) {	
			return $content;
		}
		
		*/
		
		// If user is not logged in, skip
		
		//if ( !is_user_logged_in() ) {
			//return $content;
		//}
		
		///////////////////////////////////////////////////
		// Set easy to recognize flags for conditions
		// Covers various states of gating and access
		///////////////////////////////////////////////////
		
		$pmp_gated = false;
		
		// Get pmp levels assigned to post.
		$post_membership_level_ids = $this->get_pmp_post_membership_level_ids( $post_id );
		
		// Set to true if content is gated with PMP
		if ( count( $post_membership_level_ids ) > 0 ) {
			$pmp_gated = true;
		}
		
		$pw_gated = false;
		
		// Set to true if content is gated by PW
		if ( $lock_or_not['lock'] ) {
			$pw_gated = true;
		}

		$user_qualifies = false;
		
		
		// Get PMP levels user has
		$user_pmp_levels = pmpro_getMembershipLevelsForUser( $user->ID );
		
		// Get user Patreon pledge if exists
		$user_patreon_level = Patreon_Wordpress::getUserPatronage( $user );
		
		$user_patreon_pledge_matching_pmp_levels = array();
		
		// If user has any Patreon pledge, get matching pmp levels
		if ( $user_patreon_level > 0 ) {
			$user_patreon_pledge_matching_pmp_levels = $this->match_pmp_tiers( $user_patreon_level );
		}
		
		// Get matching PMP levels for this post's Patreon level.
		
		$post_patreon_matching_pmp_levels = $this->match_pmp_tiers( $lock_or_not['patreon_level'] );
		
		// Check if content is gated with PMP and if the user has access:
		
		$user_has_pmp_access = pmpro_has_membership_access( $post_id );
		
		///////////////////////////////////////////////////
		// EOF - Set easy to recognize flags for conditions
		// Covers various states of gating and access
		///////////////////////////////////////////////////
		
		
		
		// Cases start here.
				
		// Gated only by Pmp
		
		if ( $pmp_gated AND !$pw_gated ) {
			
			// If user has access via PMP, allow the user
			if ( $user_has_pmp_access ) {
				
				// User qualifies via Pmp - allow user to see content
				
				$lock_or_not['lock'] = false;
				$lock_or_not['patreon_level'] = 0;
				
				return $content;
			
			}
			
			// Process to allow content over Patreon
			
			$user_qualifies_via_patreon = $this->user_qualifies_for_pmp_content_via_patreon( $user, $post_id );
			
			if ( $user_qualifies_via_patreon['user_qualifies'] ) {
				
				// User qualifies for PMP content via Patreon pledge
				
				$lock_or_not['lock'] = false;
				$lock_or_not['patreon_level'] = 0;
				
				
				
				return $content;
				
			}
			
			
		}
		
		// Gated only by PW
		
		if ( !$pmp_gated AND $pw_gated ) {
			
			// Process to allow content over PMP
			
			
			
		}
				
		// Gated by PW and PMP
		
		if ( $pmp_gated AND $pw_gated ) {
			
			// If user has access via PMP, allow the user
			if ( $user_has_pmp_access ) {
				// User qualifies via Pmp
				
				$lock_or_not['lock'] = false;
				$lock_or_not['patreon_level'] = 0;
				
				return $lock_or_not;
			
			}
		  
			
			
			// Process to allow content over PMP
			
			
		}
		
		
		
				
		// Case 2 - User has pmp levels. Post doesnt have pmp levels.
		
		if( count( $user_pmp_levels ) > 0 AND count( $post_membership_level_ids ) == 0 ) {
			
			// Check if user's pmp level has any level that matches the levels that match Patreon
								
			
		
		}				
	
		// Case 3 - User does not have pmp levels. Post has pmp levels. User has pmp levels matched from Patreon
		
		if( count( $user_patreon_pledge_matching_pmp_levels ) > 0 AND count( $user_pmp_levels ) == 0 AND count( $post_membership_level_ids ) > 0 ) {
			
			// Check if user has a matching pmp level over the Patreon pledge
		
			if( count( $user_patreon_pledge_matching_pmp_levels ) > 0 && count( array_intersect( $user_patreon_pledge_matching_pmp_levels, $post_membership_level_ids ) ) > 0 ) {
									
				$user_qualifies = true;
				$reason = 'user_has_pmp_level_matching_post_level';
				
			}
		
		}
		
		
		
		if ( $user_qualifies ) {
			
			$lock_or_not['lock'] = false;
			$lock_or_not['reason'] = 'user_qualifies_for_pmp_via_patreon';
			
			
		}
		
		
		return $lock_or_not;
	}
	
	public function get_pmp_post_membership_level_ids( $post_id = false ) {
		
		// Gets membership levels assigned to a post or post category
		
		global $wpdb;
		
		// Taken from PMP free version
		
		// No post id. Return false
		if ( !$post_id ) {
			return false;
		}
		
		$post = get_post( $post_id );
		
		if ( !$post OR !is_object( $post ) ) {
			return false;
		}
		
		if(isset($post->post_type) && $post->post_type == "post") {
			$post_categories = wp_get_post_categories($post->ID);

			if(!$post_categories) {
				//just check for entries in the memberships_pages table
				$sqlQuery = "SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $post->ID . "'";
			}
			else {
				//are any of the post categories associated with membership levels? also check the memberships_pages table
				$sqlQuery = "(SELECT m.id, m.name FROM $wpdb->pmpro_memberships_categories mc LEFT JOIN $wpdb->pmpro_membership_levels m ON mc.membership_id = m.id WHERE mc.category_id IN(" . implode(",", $post_categories) . ") AND m.id IS NOT NULL) UNION (SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $post->ID . "')";
			}
		}
		else {
			//are any membership levels associated with this page?
			$sqlQuery = "SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $post->ID . "'";
		}


		$post_membership_levels = $wpdb->get_results($sqlQuery);

		$post_membership_level_ids = array();	
		
		if ( $post_membership_levels ) {
		
			foreach($post_membership_levels as $level) {
				$post_membership_level_ids[] = $level->id;
			}
		
		}
		
		return $post_membership_level_ids;
		
	}
	
	
	public function override_pmp_gating_with_pw( $hasaccess, $post, $user, $post_membership_levels ) {
		
		// This function overrides PMP gating to allow users with qualifying Patreon pledges to access content
		
		// If the user has access or post is not gated with PMP, just return true
		if ( $hasaccess ) {
			return true;
		}
		
		// At this point, it means the post is gated with PMP and user does not have access.
		
		// Get user's Patreon pledge if s/he has any
		
		$user_patreon_level = Patreon_Wordpress::getUserPatronage( $user );
		
		$user_patreon_pledge_matching_pmp_levels = array();
		
		// If user has any Patreon pledge, get matching pmp levels
		if ( $user_patreon_level > 0 ) {
			$user_patreon_pledge_matching_pmp_levels = $this->match_pmp_tiers( $user_patreon_level / 100 );
		}

		// If PMP levels matched over user's Patreon pledge has any intersection with post PMP levels, allow access:
		
		foreach ( $post_membership_levels as $key => $value ) {
			
			foreach ( $user_patreon_pledge_matching_pmp_levels as $key_2 => $value_2 ) {
				
				// If there is any matching level, return true
				
				if ( $post_membership_levels[$key]->id == $user_patreon_pledge_matching_pmp_levels[$key_2]->id ) {
					// Match. Return true.
					return true;
				}
			
			}
		}
		
		// At this point user does not have access over matching PMP levels via Patreon pledge.
		
		
		// Check if post is gated with Patreon and user has access
		
		if ( Patreon_Wordpress::is_content_gated_with_pw( $post->ID ) ) {
			
			// It is. Check if user qualifies for this content for whatsoever reason.
			
			$lock_or_not = Patreon_Wordpress::lock_or_not( $post->id );
			
			if ( !$lock_or_not['lock'] ) {
				
				// Content is locked via Patreon, but is accessible to user. Allow PMP access
				return true;			
				
			}
			
		}
		
		// At this point user has no qualifying status. Return false.

		return false;
		
	}
	
	public function override_pw_gating_with_pmp( $lock_or_not, $post_id, $declined, $user) {
		
		// This function overrides PW gating to allow users with qualifying PMP tiers to access content
		
		// If the post is not gated for the user, just return
		
		if ( !$lock_or_not['lock'] ) {
			return $lock_or_not;
		}
		
		// At this point, it means the post is gated with PW and user does not have access.
		
		// Check if post is gated with PMP and if user has access.
		
		$hasaccess = false;
		
		// Temporarily remove filter we attached to PMP so it wont cause infinite loop
		remove_filter( 'pmpro_has_membership_access_filter', array($this, 'override_pmp_gating_with_pw'), 10 );
		
		$hasaccess = pmpro_has_membership_access( $post_id, $user->ID );

		if ( $hasaccess AND count( $this->get_pmp_post_membership_level_ids( $post_id ) ) > 0 ) {
			// Post is gated with PMP and user has access. Unlock the post.
		
			$lock_or_not['lock'] = false;
			$lock_or_not['reason'] = 'valid_patron';
			
			// Allow content
			return $lock_or_not;
		
		}
		
		// The other option - the content is gated with PW and not PMP. If PMP user has a matching membership $ level that matches PW's tier level, allow access.
				
		// Get the matching PMP levels for the $ value of PW gated post if there is any
		
		$matching_levels = $this->match_pmp_tiers( $lock_or_not['patreon_level'] );
		
		if ( is_array( $matching_levels) AND count( $matching_levels ) > 0 ) {
			
			// User has membership levels which have matching or greater $ value than the $ level of Patreon gating. Allow content.
			
			$lock_or_not['lock'] = false;
			$lock_or_not['reason'] = 'valid_patron';
			
			return $lock_or_not;
			
		}
		
		// Re-add pmp filter:
		
		add_filter( 'pmpro_has_membership_access_filter', array($this, 'override_pmp_gating_with_pw'), 10, 4 );		
	
		// Return unmodified result
		
		return $lock_or_not;
		
	}
	
	public function jetpack_photon_skip_image( $val, $src, $tag ) {
		
		// This function skips Jetpack's image functions for an image in case the image is a Patreon gated one
		
		// Skip if no source is given
		if ( !$src OR $src == '') {
			return $val;
		}
		
		$attachment_id = attachment_url_to_postid( $src );

		// Get Patreon level if there is:
		
		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );

		if ( $patreon_level > 0 ) {
			return true;			
		}
		
		return $val;
		
	}
	
	
}