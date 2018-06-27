<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Wordpress {

	private static $Patreon_Routing;
	private static $Patreon_Frontend;
	private static $Patreon_Posts;
	private static $Patreon_Protect;
	private static $Patreon_Options;
	private static $Patron_Metabox;
	private static $Patreon_User_Profiles;
	public static $current_user_pledge_amount = -1;
	public static $current_user_patronage_declined = -1;
	public static $current_user_is_patron = -1;
	public static $current_patreon_user = -1;
	public static $current_member_details = -1;
	public static $current_user_patronage_duration = -1;
	public static $current_user_lifetime_patronage = -1;
	public static $current_user_pledge_relationship_start = -1;

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

		self::$Patreon_Routing       = new Patreon_Routing;
		self::$Patreon_Frontend      = new Patreon_Frontend;
		self::$Patreon_Options       = new Patreon_Options;
		self::$Patron_Metabox        = new Patron_Metabox;
		self::$Patreon_User_Profiles = new Patreon_User_Profiles;
		self::$Patreon_Protect       = new Patreon_Protect;

		add_action( 'wp_head', array( $this, 'updatePatreonUser' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorID' ) );
		add_action( 'init', array( $this, 'checkv2APIAccess' ) );
		add_action( 'init', array( $this, 'checkPatreonCampaignID' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorURL' ) );
		add_action( 'init', array( $this, 'checkPatreonCreatorName' ) );
		add_action( 'init', 'Patreon_Login::checkTokenExpiration' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
		add_action( 'upgrader_process_complete', 'Patreon_Wordpress::AfterUpdateActions', 10, 2 );
		add_action( 'admin_notices', array( $this, 'AdminMessages' ) );
		add_action( 'init', array( $this, 'transitionalImageOptionCheck' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_section' ), 20 ) ;

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
			/* all the details you want to update on wordpress user account */
			update_user_meta( $user->ID, 'patreon_user', $user_response['data']['attributes']['vanity'] );
			update_user_meta( $user->ID, 'patreon_created', $user_response['data']['attributes']['created'] );
			update_user_meta( $user->ID, 'user_firstname', $user_response['data']['attributes']['first_name'] );
			update_user_meta( $user->ID, 'user_lastname', $user_response['data']['attributes']['last_name'] );
		}

	}
	public static function checkPatreonCreatorID() {
	
		// Check if creator id doesnt exist. Account for the case in which creator id was saved as empty by the Creator

		if ( ! get_option( 'patreon-creator-id', false ) OR get_option( 'patreon-creator-id', false )== '' ) {	
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
	public static function checkv2APIAccess() {
		
		// Check if we can contact API v2 with the creator access token we have. Account for the case in which creator id was saved as empty by the Creator
		
		if ( ! get_option('patreon-can-use-api-v2', false ) ) {	
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if ( get_option( 'patreon-client-id', false ) 
				&& get_option( 'patreon-client-secret', false ) 
				&& get_option( 'patreon-creators-access-token', false )
			) {
				
				// Credentials are in. Go.
				
				$api_client = new Patreon_API( get_option( 'patreon-creators-access-token' , false ) );

				$api_response = $api_client->check_api_v2();
		
			}
			$can_use = 'no';
			if ( $api_response['data'][0]['type']=='campaign' ) {
				
				// Got a valid result. Update.
				$can_use = 'yes';
			}
			
			update_option( 'patreon-can-use-api-v2', $can_use );
		}
	}
	public static function checkPatreonCreatorURL() {
		
		// Check if creator url doesnt exist. 
		$creator_url = self::getPatreonCreatorURL();
		if ( ! get_option( 'patreon-creator-url', false ) OR get_option( 'patreon-creator-url', false ) == '' ) {	
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
	public static function checkPatreonCampaignID() {
		
		// Check if campaign id doesnt exist. 
	
		if ( ! get_option( 'patreon-campaign-id', false ) OR get_option( 'patreon-campaign-id', false ) == '' ) {
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
		
		if ( ! get_option( 'patreon-creator-full-name', false ) OR get_option( 'patreon-creator-full-name', false ) == '' ) {
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
					}

					$user_response = $api_client->fetch_creator_info();
				}
			}
		}
		
		return $user_response;
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
	public static function getUserPatronage() {
		
		if ( self::$current_user_pledge_amount != -1 ) {
			return self::$current_user_pledge_amount;
		}

		if ( is_user_logged_in() == false ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( $user == false ) {
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
				if ( $obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id ) {
					$pledge = $obj;
					break;
				}
			}
		}
		
		if ( isset( $pledge['attributes']['declined_since'])  && ! is_null( $pledge['attributes']['declined_since'] ) ) {
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
		
		if ( ! $user ) {
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
		
		if ( ! $user ) {
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
		
		if ( ! $user ) {
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
		
		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		$user_response = self::getPatreonUser( $user );

		// If no user exists, the patronage cannot have been declined.
		if ( ! $user_response ) {
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
	
		if ( isset( $pledge['attributes']['declined_since']) && ! is_null( $pledge['attributes']['declined_since'] ) ) {
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
	public static function isPatron() {

		if( self::$current_user_is_patron != -1 ) {
			return self::$current_user_is_patron;
		}
		
		$user_patronage = self::getUserPatronage();

		if( is_numeric( $user_patronage ) && $user_patronage > 0 ) {
			self::$current_user_is_patron = true;
		}
		else {
			self::$current_user_is_patron = false;
		}

	}
	public static function enqueueAdminScripts() {

		wp_enqueue_script( 'patreon-admin-js', PATREON_PLUGIN_ASSETS . '/js/admin.js', array( 'jquery' ), '1.0', true );

	}
	public static function AfterUpdateActions( $upgrader_object, $options = false ) {
		
		// In this function we perform actions after update.

		if ( ! $options OR ! is_array( $options ) ) {
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
				// Yep, this plugin was updated. Do whatever necessary post-update action:
				
				// Flush permalinks (htaccess rules) for Apache servers to make image protection rules active
				flush_rewrite_rules();
				
				// Now remove the flags for regular notifications:
	
				delete_option( 'patreon-mailing-list-notice-shown' );
				delete_option( 'patreon-rate-plugin-notice-shown' );
				delete_option( 'patreon-file-locking-feature-notice-shown' );
			}

			// Transitional code to fix creator id pulling bug - campaign id was being pulled instead. Can be removed in 1-2 versions
			
			delete_option( 'patreon-creator-id' );
			delete_option( 'patreon-campaign-id' );
		}	
	}
	public static function transitionalImageOptionCheck() {
	
		// This function is to enable a smooth transition for image locking option. It may be deleted in a few minor versions.
		
		// Check if transitional option is saved:
		
		if( ! get_option( 'patreon-image-option-transition-done',false ) ) {
		
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
		
		$mailing_list_notice_shown = get_option( 'patreon-mailing-list-notice-shown', false );
		
		if( ! $mailing_list_notice_shown ) {
			?>
				 <div class="notice notice-success is-dismissible">
					<p>Would you like to receive update notices, tips & tricks for Patreon WordPress? <a href="https://patreonforms.typeform.com/to/dPBVp1" target="_blank">Join our mailing list here!</a></p>
				</div>
			<?php			
			update_option('patreon-mailing-list-notice-shown',1);
		}
		
		$rate_plugin_notice_shown = get_option('patreon-rate-plugin-notice-shown',false);
		
		if( ! $rate_plugin_notice_shown ) {
			?>
				 <div class="notice notice-info is-dismissible">
					<p>Did Patreon WordPress plugin transform your membership business? Help creators like yourself find out about this plugin <a href="https://wordpress.org/support/plugin/patreon-connect/reviews/#new-post" target="_blank">by rating and giving your brutally honest thoughts!</a></p>
				</div>
			<?php	
			update_option( 'patreon-rate-plugin-notice-shown', 1 );
		}
		$file_feature_notice_shown = get_option( 'patreon-file-locking-feature-notice-shown', false );
		
		if( ! $file_feature_notice_shown AND ! get_option( 'patreon-enable-file-locking', false ) ) {
			?>
				 <div class="notice notice-info is-dismissible">
				 <h3>The Patreon Wordpress plugin now supports image locking!</h3>
					<p>If you were using or would like to use image locking feature that Patreon WordPress offers, now you must turn it on in your <a href="<?php echo admin_url('admin.php?page=patreon-plugin'); ?>">plugin settings</a> and visit 'Permalinks' settings of your WordPress site and click 'Save'. Otherwise image locking feature will be disabled or your images may appear broken. <br /><br />Want to learn more about why image locking could be useful for you? <a href="https://www.patreondevelopers.com/t/how-to-use-image-locking-feature-in-patreon-wordpress-plugin/461" target="_blank">Read more about image locking here</a>.</p>
				</div>
			<?php	
			update_option( 'patreon-file-locking-feature-notice-shown', 1 );
		}
			
		if( ! get_option( 'patreon-gdpr-notice-shown', false ) ) {
			?>
				 <div class="notice notice-info is-dismissible">
				 <h3>Making your site GDPR compliant with Patreon WordPress</h3>
					<p>Please visit <a href="<?php echo admin_url('tools.php?wp-privacy-policy-guide=1#wp-privacy-policy-guide-patreon-wordpress'); ?>">the new WordPress privacy policy recommendation page</a> and copy & paste the section related to Patreon WordPress to your privacy policy page.<br><br>You can read our easy tutorial for GDPR compliance with Patreon WordPress <a href="https://patreon.zendesk.com/hc/en-us/articles/360004198011" target="_blank">by visiting our GDPR help page</a></p>
				</div>
			<?php	
			update_option( 'patreon-gdpr-notice-shown', 1 );
		}
	}
	
}