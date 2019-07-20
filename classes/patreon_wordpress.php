<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_WordPress {

	private static $Patreon_Routing;
	private static $Patreon_Frontend;
	private static $Patreon_Posts;
	private static $Patreon_Protect;
	private static $Patreon_Options;
	private static $Patron_Metabox;
	private static $Patron_Compatibility;
	private static $Patreon_User_Profiles;
	public static $current_user_pledge_amount = -1;
	public static $current_user_patronage_declined = -1;
	public static $current_user_is_patron = -1;
	public static $current_patreon_user = -1;
	public static $current_member_details = -1;
	public static $current_user_patronage_duration = -1;
	public static $current_user_lifetime_patronage = -1;
	public static $current_user_pledge_relationship_start = -1;
	public static $lock_or_not = array();

	function __construct() {

		include 'patreon_login.php';
		include 'patreon_routing.php';
		include 'patreon_frontend.php';
		include 'patreon_api.php';
		include 'patreon_oauth.php';
		include 'patreon_options.php';
		include 'patreon_metabox.php';
		include 'patreon_user_profiles.php';
		include 'patreon_protect.php';
		include 'patreon_compatibility.php';

		self::$Patreon_Routing       = new Patreon_Routing;
		self::$Patreon_Frontend      = new Patreon_Frontend;
		self::$Patreon_Options       = new Patreon_Options;
		self::$Patron_Metabox        = new Patron_Metabox;
		self::$Patreon_User_Profiles = new Patreon_User_Profiles;
		self::$Patreon_Protect       = new Patreon_Protect;
		self::$Patron_Compatibility  = new Patreon_Compatibility;

		add_action( 'wp_head', array( $this, 'updatePatreonUser' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorID' ) );
		add_action( 'init', array( $this, 'check_creator_tiers' ) );
		add_action( 'init', array( $this, 'check_plugin_activation_date_for_existing_installs' ) );
		add_action( 'admin_init', array( $this, 'post_credential_update_api_connectivity_check' ) );
		add_action( 'update_option_patreon-client-id', array( $this, 'toggle_check_api_credentials_on_setting_save' ), 10, 2 );
		add_action( 'update_option_patreon-client-secret', array( $this, 'toggle_check_api_credentials_on_setting_save' ), 10, 2 );
		add_action( 'update_option_patreon-creators-access-token', array( $this, 'toggle_check_api_credentials_on_setting_save' ), 10, 2 );
		add_action( 'update_option_patreon-creators-refresh-token', array( $this, 'toggle_check_api_credentials_on_setting_save' ), 10, 2 );
		add_action( 'init', array( $this, 'check_creator_token_expiration' ) );
		add_action( 'init', array( $this, 'checkPatreonCampaignID' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorURL' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorName' ) );
		add_action( 'init', 'Patreon_Login::checkTokenExpiration' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
		add_action( 'upgrader_process_complete', 'Patreon_WordPress::AfterUpdateActions', 10, 2 );
		add_action( 'admin_notices', array( $this, 'AdminMessages' ) );
		add_action( 'init', array( $this, 'transitionalImageOptionCheck' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_section' ), 20 ) ;
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_action( 'wp_ajax_patreon_wordpress_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ), 10, 1 );
		add_action( 'wp_ajax_patreon_wordpress_toggle_option', array( $this, 'toggle_option' ), 10, 1 );
		add_action( 'wp_ajax_patreon_wordpress_populate_patreon_level_select', array( $this, 'populate_patreon_level_select_from_ajax' ), 10, 1 );
		add_action( 'plugin_action_links_' . PATREON_WORDPRESS_PLUGIN_SLUG, array( $this, 'add_plugin_action_links' ), 10, 1 );

	}
	public static function getPatreonUser( $user ) {

		if ( self::$current_patreon_user != -1 ) {
			return self::$current_patreon_user;
		}
		
		/* get user meta data and query patreon api */
		$user_meta = get_user_meta( $user->ID );
		
		if ( isset( $user_meta['patreon_access_token'][0] ) ) {
			
			$api_client = new Patreon_API( $user_meta['patreon_access_token'][0] );

			// Below is a code that caches user object for 60 seconds. This can be commented out depending on the response from Infrastructure team about contacting api to check for user on every page load
			/*
			$cache_key = 'patreon_user_'.$user->ID;
			$user      = get_transient( $cache_key );
			if ( false === $user ) {
				$user = $api_client->fetch_user();
				set_transient( $cache_key, $user, 60 );
			}
			*/

			// For now we are always getting user from APi fresh:
			$user = $api_client->fetch_user();

			return self::$current_patreon_user = $user;
			
		}

		return self::$current_patreon_user = false;
		
	}
	
	static function updatePatreonUser() {

		/* check if current user is loggedin, get ID */

		if ( is_user_logged_in() == false ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( $user == false ) {
			return false;
		}
		
		// Temporarily introduced caching until calls are moved to webhooks #REVISIT
		
		$last_update = get_user_meta( $user->ID, 'patreon_user_details_last_updated', true );
		
		// If last update time is not empty and it is closer to time() than one day, dont update
		if ( !( $last_update == '' OR ( ( time() - $last_update ) > 86400 ) ) ) {
			return false;	
		}

		/* query Patreon API to get users patreon details */
		$user_response = self::getPatreonUser( $user );

		if ( isset( $user_response['errors'] ) && is_array( $user_response['errors'] ) ) {

			foreach ( $user_response['errors'] as $error ) {
				
				if( $error['code'] == 1 ) {
					
					/* refresh users token if error 1 */

					$refresh_token = get_user_meta($user->ID, 'patreon_refresh_token', true);

					$oauth_client = new Patreon_Oauth;
					$tokens = $oauth_client->refresh_token($refresh_token, site_url().'/patreon-authorization/');

					update_user_meta($user->ID, 'patreon_refresh_token', $tokens['refresh_token']);
					update_user_meta($user->ID, 'patreon_access_token', $tokens['access_token']);

					$user_response = self::getPatreonUser($user);
					
				}
				
			}

		}

		if ( $user_response == false ) {
			return false;
		}
		
		if ( isset( $user_response['data'] ) ) {
			
			// Set the update time
			update_user_meta( $user->ID, 'patreon_user_details_last_updated', time() );
			
			/* all the details you want to update on WordPress user account */
			update_user_meta( $user->ID, 'patreon_user', $user_response['data']['attributes']['vanity'] );
			update_user_meta( $user->ID, 'patreon_created', $user_response['data']['attributes']['created'] );
			update_user_meta( $user->ID, 'user_firstname', $user_response['data']['attributes']['first_name'] );
			update_user_meta( $user->ID, 'user_lastname', $user_response['data']['attributes']['last_name'] );
			
		}

	}
	public static function checkPatreonCreatorID() {
				
		// Check if creator id doesnt exist. Account for the case in which creator id was saved as empty by the Creator

		if ( !get_option( 'patreon-creator-id', false ) OR get_option( 'patreon-creator-id', false )== '' ) {

			// Making sure access credentials are there to avoid fruitlessly contacting the api:

			if ( get_option( 'patreon-client-id', false ) 
				&& get_option( 'patreon-client-secret', false ) 
				&& get_option( 'patreon-creators-access-token' , false )
			) {
				
				// Credentials are in. Go.
				
				$creator_id = self::getPatreonCreatorID();
				
			}
			if ( isset( $creator_id ) ) {
				// Creator id acquired. Update.
				update_option( 'patreon-creator-id', $creator_id );
			}
			
		}
		
	}
	public static function check_creator_tiers() {
				
		// Check if creator tier info doesnt exist. This will make sure the new version is compatible with existing installs and will show the tiers in locked interface text from the get go

		// When we move to webhooks, this code can be changed to read from the already present creator details

		if ( !get_option( 'patreon-creator-tiers', false ) OR get_option( 'patreon-creator-tiers', false ) == '' ) {
			
			// Trigger an update of credentials
			self::update_creator_tiers_from_api();
			
		}
	}
	public static function checkPatreonCreatorURL() {
		
		// Check if creator url doesnt exist.
		
		if ( !get_option( 'patreon-creator-url', false ) OR get_option( 'patreon-creator-url', false ) == '' ) {
			
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if ( get_option( 'patreon-client-id', false ) 
				&& get_option( 'patreon-client-secret', false ) 
				&& get_option( 'patreon-creators-access-token', false )
			) {
				
				// Credentials are in. Go.
				$creator_url = self::getPatreonCreatorURL();
				
			}
			if ( isset( $creator_url ) ) {
				
				// Creator id acquired. Update.				
				update_option( 'patreon-creator-url', $creator_url );
				
			}
			
		}
		
	}
	public static function check_plugin_activation_date_for_existing_installs() {
		
		// Checks if plugin first activation date is saved for existing installs. Its here for backwards compatibility for existing installs before this version (1.2.5), and in case this meta info is lost in the db for any reason
		
		$plugin_first_activated = get_option( 'patreon-plugin-first-activated', 0 );
				
		if ( $plugin_first_activated == 0 ) {
			// If no date was set, set it to now
			update_option( 'patreon-plugin-first-activated', time() );
			update_option( 'patreon-existing-installation', true );
		}
		
	}
	public static function checkPatreonCampaignID() {
		
		// Check if campaign id doesnt exist. 
	
		if ( !get_option( 'patreon-campaign-id', false ) OR get_option( 'patreon-campaign-id', false ) == '' ) {
			
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
						
			if ( get_option( 'patreon-client-id', false ) 
				&& get_option( 'patreon-client-secret', false )
				&& get_option( 'patreon-creators-access-token', false )
			) {
	
				// Credentials are in. Go.
				$campaign_id = self::getPatreonCampaignID();
				
			}
			if ( isset( $campaign_id ) ) {
				
				// Creator id acquired. Update.
				update_option( 'patreon-campaign-id', $campaign_id );
				
			}
			
		}
		
	}
	public static function checkPatreonCreatorName() {
		
		// This function checks and saves creator's full name, name and surname. These are used in post locking interface
		
		if ( !get_option( 'patreon-creator-full-name', false ) OR get_option( 'patreon-creator-full-name', false ) == '' ) {
			
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if ( get_option('patreon-client-id', false )  && get_option( 'patreon-client-secret', false ) && get_option('patreon-creators-access-token', false ) ) {

				// Credentials are in. Go.
				$creator_info = self::getPatreonCreatorInfo();
				
			}
			if ( isset( $creator_info['included'][0]['attributes']['full_name'] ) ) {
				
				// Creator id acquired. Update.
				update_option( 'patreon-creator-full-name', $creator_info['included'][0]['attributes']['full_name'] );
				
			}
			if ( isset( $creator_info['included'][0]['attributes']['first_name'] ) ) {
				
				// Creator id acquired. Update.
				update_option( 'patreon-creator-first-name', $creator_info['included'][0]['attributes']['first_name'] );
				
			}
			if ( isset( $creator_info['included'][0]['attributes']['last_name'] ) ) {
				
				// Creator id acquired. Update.
				update_option( 'patreon-creator-last-name', $creator_info['included'][0]['attributes']['last_name'] );
				
			}
		}
	}
	public static function getPatreonCreatorInfo() {

		$api_client    = new Patreon_API( get_option( 'patreon-creators-access-token' , false ) );
        $user_response = $api_client->fetch_creator_info();

        if ( empty( $user_response ) ) {
        	return false;
        }

        if( isset( $user_response['errors'] ) && is_array( $user_response['errors'] ) ) {

			foreach( $user_response['errors'] as $error ) {
				
				if ( $error['code'] == 1 ) {

					if( self::refresh_creator_access_token() ) {
						return $api_client->fetch_creator_info();
					}
					
				}
				
			}
			
		}
		
		return $user_response;
		
	}
	public static function refresh_creator_access_token() {
		/* refresh creators token if error 1 */
		$refresh_token = get_option( 'patreon-creators-refresh-token', false );

		if( $refresh_token == false ) {
			return false;
		}

		$oauth_client = new Patreon_Oauth;
		$tokens       = $oauth_client->refresh_token( $refresh_token, site_url() . '/patreon-authorization/' );

		if( isset( $tokens['refresh_token'] ) && isset( $tokens['access_token'] ) ) {
			
			update_option( 'patreon-creators-refresh-token', $tokens['refresh_token'] );
			update_option( 'patreon-creators-access-token', $tokens['access_token'] );
			
			return $tokens;
		}		
		
		return false;
	}
	public static function check_creator_token_expiration() {
		/* Checks if creator's token is expired or if expire date is missing. Then attempts refreshing the token */
		
		$refresh_token = get_option( 'patreon-creators-refresh-token', false );

		if ( $refresh_token == false ) {
			return false;
		}
		
		$expiration = get_option( 'patreon-creators-refresh-token-expiration', false );
		
		if ( !$expiration OR $expiration <= time() ) {
			if ( $tokens = self::refresh_creator_access_token() ) {
				
				update_option( 'patreon-creators-refresh-token-expiration', time() + $tokens['expires_in'] );
				update_option( 'patreon-creators-access-token-scope', $tokens['scope'] );
				
				return true;
			}
		}
		
		return false;
	}
	public static function getPatreonCreatorID() {

		$creator_info = self::getPatreonCreatorInfo();

		if ( isset( $creator_info['data'][0]['relationships']['creator']['data']['id'] ) ) {
			return $creator_info['data'][0]['relationships']['creator']['data']['id'];
		}

        return false;

	}
	public static function getPatreonCreatorURL() {

		$creator_info = self::getPatreonCreatorInfo();

		if ( isset( $creator_info['included'][0]['attributes']['url'] ) ) {
			return $creator_info['included'][0]['attributes']['url'];
		}

        return false;
		
	}
	public static function getPatreonCampaignID() {

		$creator_info = self::getPatreonCreatorInfo();

		if ( isset( $creator_info['data'][0]['id'] ) ) {
			return $creator_info['data'][0]['id'];
		}

        return false;
		
	}
	public static function getUserPatronage( $user = false ) {
		
		if ( self::$current_user_pledge_amount != -1 ) {
			return self::$current_user_pledge_amount;
		}

		// If user is not given, try to get the current user attribute ID will be 0 if there is no logged in user
		if ( $user == false ) {
			$user = wp_get_current_user();
		}
		// If still no user object, return false
		if ( $user->ID == 0 ) {
			return false;
		}

		$creator_id = get_option( 'patreon-creator-id', false );

		if ( $creator_id == false ) {
			return false;
		}

		/* get current users meta data */
		$user_meta     = get_user_meta( $user->ID ) ;
		$user_response = self::getPatreonUser( $user );

		if ( $user_response == false ) {
			return false;
		}

		$pledge = false;
		if ( array_key_exists( 'included', $user_response ) ) {
			
			foreach ( $user_response['included'] as $obj ) {
				
				if ( isset( $obj["type"] ) && $obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id ) {
					$pledge = $obj;
					break;
				}
				
			}
			
		}
		
		if ( isset( $pledge['attributes']['declined_since'])  && !is_null( $pledge['attributes']['declined_since'] ) ) {
			do_action('ptrn/declined_since', $pledge, $pledge['attributes']['declined_since']);
			return false;
		}
		
		if ( $pledge != false ) {
			return self::getUserPatronageLevel( $pledge );
		}

		return false;

	}
	public static function getUserPatronageDuration( $user = false ) {

		if ( self::$current_user_patronage_duration != -1 ) {
			return self::$current_user_patronage_duration;
		}
		
		if ( !$user ) {
			$user = wp_get_current_user();
		}
		
		$pledge_days = false;

		$user_response = self::getPatreonUser( $user );
		
		if ( isset( $user_response['included'][0]['attributes']['pledge_relationship_start'] ) ) {
			
			$pledge_days = floor( ( time() - strtotime( $user_response['included'][0]['attributes']['pledge_relationship_start'] ) ) / 60 / 60 / 24 );
			
		}

		return $pledge_days;

	}
	public static function get_user_pledge_relationship_start( $user = false ) {

		if ( self::$current_user_pledge_relationship_start != -1 ) {
			return self::$current_user_pledge_relationship_start;
		}
		
		if ( !$user ) {
			$user = wp_get_current_user();
		}
		
		$pledge_days   = false;
		$user_response = self::getPatreonUser( $user );
		
		return strtotime( $user_response['included'][0]['attributes']['pledge_relationship_start'] );
		
	}
	public static function get_user_lifetime_patronage( $user = false ) {

		if ( self::$current_user_lifetime_patronage != -1 ) {
			return self::$current_user_lifetime_patronage;
		}
		
		if ( !$user ) {
			$user = wp_get_current_user();
		}
		
		$lifetime_patronage = false;
		$user_response      = self::getPatreonUser( $user );

		if ( isset( $user_response['included'][0]['attributes']['lifetime_support_cents'] ) ) {
			$lifetime_patronage = $user_response['included'][0]['attributes']['lifetime_support_cents'];
		}

		return $lifetime_patronage;

	}
	public static function checkDeclinedPatronage( $user ) {
		
		if ( self::$current_user_patronage_declined != -1 ) {
			return self::$current_user_patronage_declined;
		}
		
		if ( !$user ) {
			$user = wp_get_current_user();
		}

		$user_response = self::getPatreonUser( $user );

		// If no user exists, the patronage cannot have been declined.
		if ( !$user_response ) {
			return self::$current_user_patronage_declined = false;
		}

		$creator_id = get_option( 'patreon-creator-id', false );

		$pledge = false;
		if ( array_key_exists( 'included', $user_response ) ) {
			
			foreach ( $user_response['included'] as $obj ) {
				
				if ( $obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id ) {
					$pledge = $obj;
					break;
				}
				
			}
			
		}		
	
		if ( isset( $pledge['attributes']['declined_since']) && !is_null( $pledge['attributes']['declined_since'] ) ) {
			
			do_action( 'ptrn/declined_since', $pledge, $pledge['attributes']['declined_since'] );
			return self::$current_user_patronage_declined = true;
			
		}
		else {
			return self::$current_user_patronage_declined = false;
		}
	}
	public static function getUserPatronageLevel( $pledge ) {
		
		if ( self::$current_user_pledge_amount != -1 ) {
			return self::$current_user_pledge_amount;
		}
		
		if( isset( $pledge['attributes']['amount_cents'] ) ) {
			return self::$current_user_pledge_amount = $pledge['attributes']['amount_cents'];
		}
	
		return 0;
		
	}
	public static function isPatron( $user = false ) {
		
		if( self::$current_user_is_patron != -1 ) {
			return self::$current_user_is_patron;
		}

		// If user is not given, try to get the current user attribute ID will be 0 if there is no logged in user
		if ( $user == false ) {
			$user = wp_get_current_user();
		}
		
		$user_patronage = self::getUserPatronage();

		if( is_numeric( $user_patronage ) && $user_patronage > 0 ) {
			return self::$current_user_is_patron = true;
		}
		else {
			return self::$current_user_is_patron = false;
		}

	}
	public static function enqueueAdminScripts() {
		
		wp_enqueue_script( 'patreon-admin-js', PATREON_PLUGIN_ASSETS . '/js/admin.js', array( 'jquery' ), PATREON_WORDPRESS_VERSION, true );

	}
	public static function AfterUpdateActions( $upgrader_object, $options = false ) {
		
		// In this function we perform actions after update.

		if ( !$options OR !is_array( $options ) ) {
			// Not an update.
			return;
		}
		
		// Check if this plugin was updated:
		if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
			
			if( isset( $options['plugins'] ) ) {
				
				// Multi plugin update. Iterate:
				// Iterate through the plugins being updated and check if ours is there
				foreach( $options['plugins'] as $plugin ) {
					
					if( $plugin == PATREON_WORDPRESS_PLUGIN_SLUG ) {
						$got_updated = true;
					}
					
				}
				
			}
			if( isset( $options['plugin'] ) ) {
				
				// Single plugin update
				if( $options['plugin'] == PATREON_WORDPRESS_PLUGIN_SLUG ) {
					$got_updated = true;
				}
				
			}

			if( $got_updated ) {
				
				// This section is used to do any tasks need doing after plugin updates. Any code changes to this part would take effect not immediately in the same version, but in the next update cycle since WP would use the new code only after plugin has been updated once.
								
			}

			
		}
		
	}
	public static function transitionalImageOptionCheck() {
	
		// This function is to enable a smooth transition for image locking option. It may be deleted in a few minor versions.
		
		// Check if transitional option is saved:
		
		if( !get_option( 'patreon-image-option-transition-done',false ) ) {
		
			// Doesnt exist.
			
			// Remove the htaccess rule
			
			Patreon_Protect::removePatreonRewriteRules();
			
			// This just disabled the image feature until it is 
			
			update_option( 'patreon-image-option-transition-done', true );
			
		}

	}
	public static function add_privacy_policy_section() {

		wp_add_privacy_policy_content( 'Patreon WordPress', PATREON_PRIVACY_POLICY_ADDENDUM );
		
	}
	public static function AdminMessages() {
				
		// This function processes any message or notification to display once after updates.
		
		$already_showed_non_system_notice = false;
		
		// Wp org wants non-error / non-functionality related notices to be shown infrequently and one per admin-wide page load, and be dismissable permanently. 		

		$addon_upsell_shown = get_option( 'patreon-addon-upsell-shown', false );
		$existing_install = get_option( 'patreon-existing-installation', false );
		$current_screen = get_current_screen();
		
		// The addon upsell must be admin wide, permanently dismissable, and must not appear in plugin manager page in admin
		
		if( !$addon_upsell_shown AND !self::check_plugin_exists('patron-plugin-pro') AND $current_screen->id != 'plugins' AND ( (self::check_days_after_last_non_system_notice( 7 ) AND self::calculate_days_after_first_activation( 30 ) ) OR $existing_install ) AND !$already_showed_non_system_notice ) {

			?>
				<div class="notice notice-success is-dismissible patreon-wordpress" id="patreon-addon-upsell-shown"><img class="addon_upsell" src="<?php echo PATREON_PLUGIN_ASSETS ?>/img/Patron-Plugin-Pro-128.png" style="float:left; margin-right: 20px;" alt="Patron Plugin Pro" />
					<p><h2 style="margin-top: 0px; font-size: 150%; font-weight: bold;">Boost your pledges and patrons at Patreon with Patron Pro!</h2><div style="font-size: 125% !important">Get Patron Pro third party addon for Patreon WordPress to increase your patrons and pledges! Enjoy powerful features like partial post locking, sneak peeks, advanced locking methods, login lock, vip users and more.<br /><br /><a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress" target="_blank">Check out all features here</a></div></p>
				</div>
			<?php	
			
			$already_showed_non_system_notice = true;
			
		}
		
		$mailing_list_notice_shown = get_option( 'patreon-mailing-list-notice-shown', false );

		// Queue this message immediately after activation if not already shown
		
		if( !$mailing_list_notice_shown ) {
			
			?>
				 <div class="notice notice-success is-dismissible  patreon-wordpress" id="patreon-mailing-list-notice-shown">
					<p>Would you like to receive notices, tips & tricks for Patreon WordPress? <a href="https://patreonforms.typeform.com/to/dPBVp1" target="_blank">Join our mailing list here!</a></p>
				</div>
			<?php	
			
			$already_showed_non_system_notice = true;
			
		}
		
		$rate_plugin_notice_shown = get_option( 'patreon-rate-plugin-notice-shown', false );
		
		// The below will trigger a rating notice once if it was not shown and the plugin was installed more than 37 days ago.
		// It will also trigger once for existing installs before this version. Show 30 days after the plugin was first installed, and 7 days after any last notice

		if( !$rate_plugin_notice_shown AND self::check_days_after_last_non_system_notice( 7 ) AND self::calculate_days_after_first_activation( 37 ) AND !$already_showed_non_system_notice ) {

			?>
				 <div class="notice notice-info is-dismissible patreon-wordpress" id="patreon-rate-plugin-notice-shown">
					<p>Did Patreon WordPress help your site? Help creators like yourself find out about it <a href="https://wordpress.org/support/plugin/patreon-connect/reviews/#new-post" target="_blank">by giving us a good rating!</a></p>
				</div>
			<?php	

			$already_showed_non_system_notice = true;
			
		}

		// This is a plugin system info notice. 
		if( get_option( 'patreon-wordpress-app-credentials-success', false ) ) {
			
			?>
				 <div class="notice notice-success is-dismissible patreon-wordpress" id="patreon-wordpress-update-available">
				 <h3>Your Patreon client details were successfully saved!</h3>
					<p>Patreon WordPress is now ready to go and your site is connected to Patreon! You can now lock any post by using the "Patreon Level" meta box in your post editor!</p>
				</div>
			<?php

			delete_option( 'patreon-wordpress-app-credentials-success' );
		}
		
		// This is a plugin system info notice. 
		if( get_option( 'patreon-wordpress-app-credentials-failure', false ) ) {
			
			?>
				 <div class="notice notice-error is-dismissible patreon-wordpress" id="patreon-wordpress-update-available">
				 <h3>Sorry - couldn't connect your site to Patreon</h3>
					<p>Patreon WordPress wasn't able to contact Patreon with the app details you provided. This may be because there is an error in the app details, or because there is something preventing proper connectivity in between your site/server and Patreon API. You can get help by visiting our support forum <a href="https://www.patreondevelopers.com/c/patreon-wordpress-plugin-support" target="_blank">here</a></p>
				</div>
			<?php
			
			delete_option( 'patreon-wordpress-app-credentials-failure' );
			
		}
		
	}
	public function check_for_update($plugin_check_data) {
		global $wp_version, $plugin_version, $plugin_base;

		if ( empty( $plugin_check_data->checked ) ) {
			return $plugin_check_data;
		}

		if ( isset( $plugin_check_data->response[PATREON_WORDPRESS_PLUGIN_SLUG] ) AND 
			version_compare( PATREON_WORDPRESS_VERSION, $plugin_check_data->response[PATREON_WORDPRESS_PLUGIN_SLUG]->new_version, '<' )
		) {

			update_option( 'patreon-wordpress-update-available', 1 );
		}

		return $plugin_check_data;
		
	}	
	public function dismiss_admin_notice() {
		
		if( !( is_admin() && current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		// Mapping what comes from REQUEST to a given value avoids potential security problems and allows custom actions depending on notice
		
		if ( $_REQUEST['notice_id'] == 'patreon-wordpress-update-available' ) {
			delete_option( 'patreon-wordpress-update-available' );
		}

		if ( $_REQUEST['notice_id'] == 'patreon-addon-upsell-shown' ) {
			update_option( 'patreon-addon-upsell-shown', true);
			
			// Set the last notice shown date
			self::set_last_non_system_notice_shown_date();
		}
		
		// Mapping what comes from REQUEST to a given value avoids potential security problems
		if ( $_REQUEST['notice_id'] == 'patreon-mailing-list-notice-shown' ) {
			update_option( 'patreon-mailing-list-notice-shown', true );
			
			// Set the last notice shown date
			self::set_last_non_system_notice_shown_date();
		}
		
		// Mapping what comes from REQUEST to a given value avoids potential security problems
		if ( $_REQUEST['notice_id'] == 'patreon-rate-plugin-notice-shown' ) {
			update_option( 'patreon-rate-plugin-notice-shown', true );
			
			// Set the last notice shown date
			self::set_last_non_system_notice_shown_date();
		}
		
	}
	public function toggle_check_api_credentials_on_setting_save(  $old_value, $new_value ) {
		
		// This function fires after any of the client details are updated. 
		
		if ( !( is_admin() AND current_user_can( 'manage_options' ) ) ) {
			return;			
		}

		// This filter only runs when settings are actually updated, but just in case:
		// Try contacting the api 
		if( $new_value != $old_value ) {

			// One of access credentials were updated. Set a flag to do an api connectivity check
			update_option( 'patreon-wordpress-do-api-connectivity-check', 1 );
			
		}
				
	}

	public function post_credential_update_api_connectivity_check() {

		// This function checks if the saved app credentials are valid if the check toggle is set
		
		if ( !( is_admin() AND current_user_can( 'manage_options' ) ) ) {
			return;			
		}

		if( get_option( 'patreon-wordpress-do-api-connectivity-check', false ) ) {
			
			$result = self::check_api_connection();
			delete_option( 'patreon-wordpress-do-api-connectivity-check' );
		}
				
	}
	
	public static function check_api_connection() {
		// Just attempts to connect to API with given credentials, and returns result
		
		$api_client    = new Patreon_API( get_option( 'patreon-creators-access-token' , false ) );
        $user_response = $api_client->fetch_creator_info();
		
		$creator_access = false;
		$client_access = false;
		
		if ( isset( $user_response['included'][0]['id'] ) AND $user_response['included'][0]['id'] != '' ) {
			// Got creator id. Credentials must be valid
			
			// Success - set flag
			// update_option( 'patreon-wordpress-app-credentials-success', 1 );
			
			$creator_access = true;
			
		}

		// Try to do a creator's token refresh
	
		if ( $tokens = self::refresh_creator_access_token() ) {
			
			update_option( 'patreon-creators-refresh-token-expiration', time() + $tokens['expires_in'] );
			update_option( 'patreon-creators-access-token-scope', $tokens['scope'] );
			
			// Try again:
			
			$api_client    = new Patreon_API( get_option( 'patreon-creators-access-token' , false ) );
			$user_response = $api_client->fetch_creator_info();
			
			if ( isset( $user_response['included'][0]['id'] ) AND $user_response['included'][0]['id'] != '' ) {
				
				// Got creator id. Credentials must be valid
				// Success - set flag
				
				$creator_access = true;
				
			}			
			
		}
		
		// Here some check for client id and secret may be entered in future - currently only checks creator access token 
		
		if ( $creator_access ) {
			
			update_option( 'patreon-wordpress-app-credentials-success', 1 );	
			return;
		}
		
		// All flopped. Set failure flag
		update_option( 'patreon-wordpress-app-credentials-failure', 1 );	
		
	}
	
	public function toggle_option() {
		
		if( !( is_admin() && current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		$option_to_toggle = $_REQUEST['toggle_id'];
		
		$current_value = get_user_meta( $current_user->ID, $option_to_toggle, true );
		
		$new_value = 'off';
		
		if( !$current_value OR $current_value == 'off' ) {
			$new_value = 'on';			
		}
		
		update_user_meta( $current_user->ID, $option_to_toggle, $new_value );
		
	}
	public static function add_to_lock_or_not_results( $post_id, $result ) {
		// Manages the lock_or_not post id <-> lock info var cache. The cache is run in a FIFO basis to prevent memory bloat in WP installs which may have long post listings. What it does is snip the first element in array and add the newly added var in the end
		
		// If the lock or not array is large than 50, snip the first item
		
		if ( count( self::$lock_or_not ) > 50  ) {
			array_shift( self::$lock_or_not );
		}
		
		// Add the sent element at the end:
		
		return self::$lock_or_not[$post_id] = $result;
		
	}
	public static function add_plugin_action_links( $links ) {
		
		// Adds action links to plugin listing in WP plugin admin
		
		$links = array_merge( array(
			'<a href="' . esc_url( admin_url('admin.php?page=patreon-plugin') ) . '">' . __( 'Settings', 'textdomain' ) . '</a>'), $links );
		
		// Check if the currently only available addon Patron Pro is installed, if so, dont add the link
		
		if ( self::check_plugin_exists('patron-plugin-pro') ) {
			return $links;
		}
		
		$links = array_merge( array(
			'<a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress" target="_blank">Upgrade to Pro</a>',
		), $links );
		return $links;
		
	}
	public static function lock_or_not( $post_id = false ) {
		
		// This function has the logic which decides if a post should be locked. It can be called inside or outside the loop
		
		// If the caching var is initialized, consider using it:
		if ( count( self::$lock_or_not ) > 0 ) {
			
			if( !$post_id ) {
				
				global $post;
				
				if ( isset( $post->ID ) ) {
					$post_id = $post->ID;
				}
				
			}
			
			// If post id could be acquired, check if this post's result was already cached:
			
			if ( $post_id AND isset( self::$lock_or_not[$post_id] ) ) {
				return self::$lock_or_not[$post_id];		
			}
			
		}

		$user                           = wp_get_current_user();
		$user_pledge_relationship_start = Patreon_WordPress::get_user_pledge_relationship_start( $user );
		$user_patronage                 = Patreon_WordPress::getUserPatronage( $user );
		$is_patron                      = Patreon_WordPress::isPatron( $user );
		$user_lifetime_patronage        = Patreon_WordPress::get_user_lifetime_patronage( $user );
		$declined                       = Patreon_WordPress::checkDeclinedPatronage( $user );
		$active_patron_at_post_date     = false;
		
		// Just bail out if this is not the main query for content and no post id was given
		if ( !is_main_query() AND !$post_id ) {
			
			return self::add_to_lock_or_not_results( $post_id, apply_filters( 
					'ptrn/lock_or_not', 
					array(
						'lock' => false,
						'reason' => 'no_post_id_no_main_query',
					),
					$post_id, 
					$declined,
					$user 
				)
			);			
			
		}
		
		// If post it received, get that post. If no post id received, try to get post from global
		if ( $post_id ) {
			$post = get_post( $post_id );
		}
		else {
			// If post could be acquired from global, 
			global $post;
		}
			
		$exclude = array(
		);
		
		// Enables 3rd party plugins to modify the post types excluded from locking
		$exclude = apply_filters( 'ptrn/filter_excluded_posts', $exclude );

		if ( in_array( get_post_type( $post->ID ), $exclude ) ) {
			
			return self::add_to_lock_or_not_results( $post_id, apply_filters( 
					'ptrn/lock_or_not', 
					array(
						'lock' => false,
						'reason' => 'post_type_excluded_from_locking',
					),
					$post_id, 
					$declined,
					$user 
				)
			);
			
		}
		
		// First check if entire site is locked, get the level for locking.
		
		$patreon_level = get_option( 'patreon-lock-entire-site', false );
		
		// Check if specific level is given for this post:
		
		$post_level = get_post_meta( $post->ID, 'patreon-level', true );
		
		// get post meta returns empty if no value is found. If so, set the value to 0.
		
		if ( $post_level == '' ) {
			$post_level = 0;				
		}

		// Check if both post level and site lock level are set to 0 or nonexistent. If so return normal content.
		
		if ( $post_level == 0 
			&& ( !$patreon_level
				|| $patreon_level == 0 )
		) {
			
			return self::add_to_lock_or_not_results( $post_id, apply_filters( 
					'ptrn/lock_or_not', 
					array(
						'lock' => false,
						'reason' => 'post_is_public',
					),
					$post_id, 
					$declined,
					$user 
				)
			);			
		}
		
		// If we are at this point, then this post is protected. 
		
		// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
		// It is independent of the plugin load order since it checks if it is defined.
		// It can be defined by any plugin until right before the_content filter is run.

		if ( apply_filters( 'ptrn/bypass_filtering', defined( 'PATREON_BYPASS_FILTERING' ) ) ) {
			
			return self::add_to_lock_or_not_results( $post_id, apply_filters( 
					'ptrn/lock_or_not', 
					array(
						'lock' => false,
						'reason' => 'lock_bypassed_by_filter',
					),
					$post_id, 
					$declined,
					$user 
				)
			);
		}
		 
		if ( current_user_can( 'manage_options' ) ) {
			
			// Here we need to put a notification to admins so they will know they can see the content because they are admin_login_with_patreon_disabled

			return self::add_to_lock_or_not_results( $post_id, apply_filters( 
					'ptrn/lock_or_not', 
					array(
						'lock' => false,
						'reason' => 'show_to_admin_users',
					),
					$post_id, 
					$declined,
					$user 
				)
			);
			
		}
							
		// Passed checks. If post level is not 0, override patreon level and hence site locking value with post's. This will allow Creators to lock entire site and then set a different value for individual posts for access. Ie, site locking is $5, but one particular post can be $10, and it will require $10 to see. 
		
		if ( $post_level !=0 ) {
			$patreon_level = $post_level;
		}
		
		// Check if post was set for active patrons only
		$patreon_active_patrons_only = get_post_meta( $post->ID, 'patreon-active-patrons-only', true );
		
		// Check if specific total patronage is given for this post:
		$post_total_patronage_level = get_post_meta( $post->ID, 'patreon-total-patronage-level', true );
		
		$hide_content = true;
		$reason = 'active_pledge_not_enough';
		
		// Check if user is logged in
		
		if ( !is_user_logged_in() ) {
			
			$hide_content = true;
			$reason = 'user_not_logged_in';
			
		}
		
		if ( $declined ) {
			
			$hide_content = true;
			$reason = 'payment_declined';
			
		}
		
		if ( is_user_logged_in() AND !$is_patron ) {
			
			$hide_content = true;
			$reason = 'not_a_patron';
			
		}
	
		if ( !( $user_patronage == false
			|| $user_patronage < ( $patreon_level * 100 )
			|| $declined ) AND is_user_logged_in() ) {
				
			$hide_content = false;
			$reason = 'valid_patron';
			// Seems valid patron. Lets see if active patron option was set and the user fulfills it
			
			if ( $patreon_active_patrons_only == '1'
			AND $user_pledge_relationship_start >= strtotime( get_the_date( '', $post->ID ) ) ) {
				$hide_content = true;
				$reason = 'not_active_patron_at_post_date';
				$active_patron_at_post_date = false;
			}
			else {
				$hide_content = false;
				$active_patron_at_post_date = true;
			}
			
		}

		if ( $post_total_patronage_level !='' AND $post_total_patronage_level > 0) {
			
			// Total patronage set if user has lifetime patronage over this level, we let him see the content
			if( $user_lifetime_patronage >= $post_total_patronage_level * 100 ) {
				$hide_content = false;
				$reason = 'patron_fulfills_total_historical_pledge_requirement';
			}
			
		}
		
		$result = array(
			'lock'                         => $hide_content,
			'reason'                       => $reason,
			'patreon_level'                => $patreon_level,
			'post_total_patronage_level'   => $post_total_patronage_level,
			'patreon_active_patrons_only'  => $patreon_active_patrons_only,
			'active_patron_at_post_date'   => $active_patron_at_post_date,
			'user_is_patron'               => $is_patron,
			'user_active_pledge'           => $user_patronage,
			'user_total_historical_pledge' => $user_lifetime_patronage,
		);
		
		return apply_filters( 'ptrn/lock_or_not', self::add_to_lock_or_not_results( $post_id, $result) , $post_id, $declined, $user );
		
	}

	public static function check_plugin_exists( $plugin_slug ) {
		// Simple function to check if a plugin is installed (may be active, or not active) in the WP instalation
		
		// Plugin slug is the wp's plugin dir together with the plugin's file which has the plugin header

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
			return true;			
		}
	}
	public function activate() {
		
		// This function kicks in after the plugin is activated. Used to perform tasks which need to be run during plugin activation
		
		// Checking if $plugin_first_activated value exists prevents resetting of this value on every plugin deactivation/activation

		$plugin_first_activated   = get_option( 'patreon-plugin-first-activated', 0 );
				
		if ( $plugin_first_activated == 0 ) {
			update_option( 'patreon-plugin-first-activated', time() );
		}
		
	}
	public static function check_days_after_last_non_system_notice( $days ) {
		// Calculates if $days many days passed after last non system notice was showed. Used in deciding if and when to show admin wide notices
		
		$last_non_system_notice_shown_date = get_option( 'patreon-last-non-system-notice-shown-date', 0 );
		
		// Calculate if $days days passed since last notice was shown		
		if ( ( time() - $last_non_system_notice_shown_date ) > ( $days * 24 * 3600 ) ) {
			// More than $days days. Set flag
			return true;
		}

		return false;
		
	}

	public static function calculate_days_after_first_activation( $days ) {
		
		// Used to calculate days passed after first plugin activation. 
		
		$plugin_first_activated   = get_option( 'patreon-plugin-first-activated', 0 );
				
		// Calculate if $days days passed since last notice was shown		
		if ( ( time() - $plugin_first_activated ) > ( $days * 24 * 3600 ) ) {
			// More than $days days. Set flag
			return true;
		}

		return false;
			
	}
	public static function set_last_non_system_notice_shown_date() {
		
		// Sets the last non system notice shown date to now whenever called. Used for decicing when to show admin wide notices that are not related to functionality. 
		
		update_option( 'patreon-last-non-system-notice-shown-date', time() );
			
	}

	
	public static function populate_patreon_level_select_from_ajax() {
		// This function accepts the ajax request from the metabox and calls the relevant function to populate the tiers select
		
		if( !( is_admin() && current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		// Just bail out if the action is not relevant, just in case
		if ( $_REQUEST['action'] != 'patreon_wordpress_populate_patreon_level_select' ) {
			return;
		}
		
		// If post id was not passed, exit with error
		if ( !isset( $_REQUEST['pw_post_id'] ) OR $_REQUEST['pw_post_id'] == '' ) {
			echo 'Error: Could not get post id';
			exit;
		}
		
		$post = get_post( $_REQUEST['pw_post_id'] );
				
		echo Patreon_WordPress::make_tiers_select( $post );
		exit;
		
	}
	public static function make_tiers_select( $post = false ) {
		
		if( !( is_admin() && current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		if ( !$post ) {
			global $post;
		}
		
		// This function makes a select box with rewards and reward ids from creator's campaign to be used in post locking and site locking
		
		// First force an update of creator tiers from the api in case they were changed.
		
		self::update_creator_tiers_from_api();
		
		// Get updated tiers from db
		$creator_tiers = get_option( 'patreon-creator-tiers', false );

		// Set the select to default
		$select_options = PATREON_TEXT_YOU_HAVE_NO_REWARDS_IN_THIS_CAMPAIGN;
		// 1st element is 'everyone' and 2nd element is 'Patrons' (with cent amount 1) in the rewards array.
		
		if ( is_array( $creator_tiers['included'] ) ) {
					
			$select_options = '';
			
			// Lets get the current Patreon level for the post:
			$patreon_level = get_post_meta( $post->ID, 'patreon-level', true );
			
			$tier_count = 1;
			
			// Flag for determining if the matching tier was found during iteration of tiers
			$matching_level_found = false;

			foreach( $creator_tiers['included'] as $key => $value ) {
				
				// If its not a reward element, continue, just to make sure
				
				if(	
					!isset( $creator_tiers['included'][$key]['type'] )
					OR $creator_tiers['included'][$key]['type'] != 'reward'
				)  {
					continue; 
				}
				
				$reward = $creator_tiers['included'][$key];
								
				// Special conditions for label for element 0, which is 'everyone' and '1, which is 'patron only'
				
				if ( $reward['id'] == -1 ) {
					$label = PATREON_TEXT_EVERYONE;
				}
				if ( $reward['id'] == 0 ) {
					$label = PATREON_TEXT_ANY_PATRON;
				}
				
				// Use title if exists, and cents amount converted to dollar for any other reward level
				if ( $reward['id'] > 0 ) {
					
					$tier_title = 'Tier ' . $tier_count;
					
					$tier_count++;
					
					if ( $reward['attributes']['title'] != '' ) {
						
						$tier_title = $reward['attributes']['title'];
						
						// If the title is too long, snip it
						if ( strlen( $tier_title ) > 23 ) {
							$tier_title = substr( $tier_title , 0 , 23 ) .'...';
						}
						
					}
					
					$label = $tier_title . ' - $' . ( $reward['attributes']['amount_cents'] / 100 );
				}
				
				$selected = '';
				
				if ( ( $reward['attributes']['amount_cents'] / 100 ) >= $patreon_level  AND !$matching_level_found ) {
					
					// Matching level was present, but now found. Set selected and toggle flag.
					// selected = selected for XHTML compatibility
					$selected = ' selected="selected"';
					
					$matching_level_found = true;
					
					// Check if a precise amount is set for this content. If so, add the actual locking amount in parantheses
					
					if ( ( $reward['attributes']['amount_cents'] / 100 ) != $patreon_level ) {
						
						$label .= ' ($'.$patreon_level.' exact)';
						
					}
					
				}
				
				$select_options .= '<option value="' . ( $reward['attributes']['amount_cents'] / 100 ) . '"'.$selected.'>'. $label . '</option>';
			}
			
		}
		
		return apply_filters( 'ptrn/post_locking_tier_select', $select_options, $post );
	
	}
	public static function update_creator_tiers_from_api() {
		
		// Does an update of creator tiers from the api
		
		if ( get_option( 'patreon-client-id', false ) 
				&& get_option( 'patreon-client-secret', false ) 
				&& get_option( 'patreon-creators-access-token' , false )
		) {
				// Credentials are in. Go.
				
				$api_client = new Patreon_API( get_option( 'patreon-creators-access-token', false ) );
				$creator_info = $api_client->fetch_tiers();
				
		}
		if ( is_array( $creator_info['included'] ) AND isset( $creator_info['included'][1]['type'] ) AND $creator_info['included'][1]['type'] == 'reward' ) {

			// Creator info acquired. Update.
			// We want to sort tiers according to their $ level.
			
			usort( $creator_info['included'], function( $a, $b ) {
				return $a['attributes']['amount_cents'] - $b['attributes']['amount_cents'];
			} );

			update_option( 'patreon-creator-tiers', $creator_info );
		}
		
		
	}
	
}


/**
 * Backwards compatibility
**/
class_alias('Patreon_WordPress','Patreon_Wordpress');
