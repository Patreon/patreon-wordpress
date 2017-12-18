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

	function __construct() {

		include 'patreon_login.php';
		include 'patreon_routing.php';
		include 'patreon_frontend.php';
		include 'patreon_api.php';
		include 'patreon_oauth.php';
		include 'patreon_options.php';
		include 'patreon_metabox.php';
		include 'patreon_user_profiles.php';

		self::$Patreon_Routing = new Patreon_Routing;
		self::$Patreon_Frontend = new Patreon_Frontend;
		self::$Patreon_Options = new Patreon_Options;
		self::$Patron_Metabox = new Patron_Metabox;
		self::$Patreon_User_Profiles = new Patreon_User_Profiles;

		add_action('wp_head', array($this, 'updatePatreonUser') );
		add_action('init', array($this, 'checkPatreonCreatorID'));
		add_action('init', array($this, 'checkPatreonCreatorURL'));
		add_action('init', array($this, 'checkPatreonCreatorName'));
		add_action('init', 'Patreon_Login::checkTokenExpiration');
		add_action('mod_rewrite_rules',  array($this, 'addPatreonRewriteRules'));
		add_action('init',  array($this, 'servePatronOnlyImage'));
		add_action('save_post',  array($this, 'parseImagesInPatronOnlyPost'));

	}
	
	static function getPatreonUser($user) {

		/* get user meta data and query patreon api */
		$user_meta = get_user_meta($user->ID);
		if(isset($user_meta['patreon_access_token'][0])) {
			$api_client = new Patreon_API($user_meta['patreon_access_token'][0]);

			// Below is a code that caches user object for 60 seconds. This can be commented out depending on the response from Infrastructure team about contacting api to check for user on every page load
			/*
			$cache_key = 'patreon_user_'.$user->ID;
			$user = get_transient( $cache_key );
			if ( false === $user ) {
				$user = $api_client->fetch_user();
				set_transient( $cache_key, $user, 60);
			}
			*/
			// For now we are always getting user from APi fresh:
			$user = $api_client->fetch_user();
	
			return $user;
		}

		return false;

	}
	
	static function updatePatreonUser() {

		/* check if current user is loggedin, get ID */

		if(is_user_logged_in() == false) {
			return false;
		}

		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		/* query Patreon API to get users patreon details */
		$user_response = self::getPatreonUser($user);

		if(isset($user_response['errors']) && is_array($user_response['errors'])) {

			foreach($user_response['errors'] as $error) {
				if($error['code'] == 1) {
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

		if($user_response == false) {
			return false;
		}

		if(isset($user_response['data'])) {
			/* all the details you want to update on wordpress user account */
			update_user_meta($user->ID, 'patreon_user', $user_response['data']['attributes']['vanity']);
			update_user_meta($user->ID, 'patreon_created', $user_response['data']['attributes']['created']);
			update_user_meta($user->ID, 'user_firstname', $user_response['data']['attributes']['first_name']);
			update_user_meta($user->ID, 'user_lastname', $user_response['data']['attributes']['last_name']);
		}

	}
	
	public static function checkPatreonCreatorID() {
		
		// Check if creator id doesnt exist. Account for the case in which creator id was saved as empty by the Creator
		
		if(!get_option('patreon-creator-id', false) OR get_option('patreon-creator-id', false)=='') {	
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) 
				&& get_option('patreon-client-secret', false) 
				&& get_option('patreon-creators-access-token', false)
			) {
				
				// Credentials are in. Go.
				
				$creator_id = self::getPatreonCreatorID();
			}
			if(isset($creator_id)) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-id', $creator_id );
			}
		}
	}
	public static function checkPatreonCreatorURL() {
		
		// Check if creator url doesnt exist. 
		
		if(!get_option('patreon-creator-url', false) OR get_option('patreon-creator-url', false)=='') {	
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) 
				&& get_option('patreon-client-secret', false) 
				&& get_option('patreon-creators-access-token', false)
			) {
				
				// Credentials are in. Go.
				
				$creator_url = self::getPatreonCreatorURL();
			}
			if(isset($creator_url)) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-url', $creator_url );
			}
		}
		
	}
	public static function checkPatreonCreatorName() {
		
		// This function checks and saves creator's full name, name and surname. These are used in post locking interface
		
		if(!get_option('patreon-creator-full-name', false) OR get_option('patreon-creator-full-name', false)=='') {
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) && get_option('patreon-client-secret', false) && get_option('patreon-creators-access-token', false)) {
				
				// Credentials are in. Go.
				
				$creator_info = self::getPatreonCreatorInfo();
			}
			if(isset($creator_info['included'][0]['attributes']['full_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-full-name', $creator_info['included'][0]['attributes']['full_name'] );
			}
			if(isset($creator_info['included'][0]['attributes']['first_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-first-name', $creator_info['included'][0]['attributes']['first_name'] );
			}
			if(isset($creator_info['included'][0]['attributes']['last_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-last-name', $creator_info['included'][0]['attributes']['last_name'] );
			}
		}
	}
	
	public static function getPatreonCreatorInfo() {
	
		$api_client = new Patreon_API(get_option('patreon-creators-access-token', false));

        $user_response = $api_client->fetch_creator_info();

        if(empty($user_response)) {
        	return false;
        }

        if(isset($user_response['errors']) && is_array($user_response['errors'])) {

			foreach($user_response['errors'] as $error) {
				if($error['code'] == 1) {

					/* refresh creators token if error 1 */

					$refresh_token = get_option('patreon-creators-refresh-token', false);

					if($refresh_token == false) {
						return false;
					}

					$oauth_client = new Patreon_Oauth;
					$tokens = $oauth_client->refresh_token($refresh_token, site_url().'/patreon-authorization/');

					if(isset($tokens['refresh_token']) && isset($tokens['access_token'])) {
						update_option('patreon-creators-refresh-token', $tokens['refresh_token']);
						update_option('patreon-creators-access-token', $tokens['access_token']);
					}

					$user_response = $api_client->fetch_creator_info();
				}
			}

		}
		
		return $user_response;
	}
	
	public static function getPatreonCreatorID() {

		$creator_info = self::getPatreonCreatorInfo();

		if(isset($creator_info['included'][0]['id']))
		{
			return $creator_info['included'][0]['id'];
		}

        return false;

	}
	public static function getPatreonCreatorURL() {

		$creator_info = self::getPatreonCreatorInfo();

		if(isset($creator_info['included'][0]['attributes']['url'])) {
			return $creator_info['included'][0]['attributes']['url'];
		}

        return false;

	}
	
	public static function getUserPatronage() {

		if(is_user_logged_in() == false) {
			return false;
		}

		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		$creator_id = get_option('patreon-creator-id', false);

		if($creator_id == false) {
			return false;
		}

		/* get current users meta data */
		$user_meta = get_user_meta($user->ID);

		$user_response = self::getPatreonUser($user);

		if($user_response == false) {
			return false;
		}

		$pledge = false;
		if (array_key_exists('included', $user_response)) {
			foreach ($user_response['included'] as $obj) {
				if ($obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id) {
					$pledge = $obj;
					break;
				}
			}
		}

		if(isset($pledge['attributes']['declined_since']) && !is_null($pledge['attributes']['declined_since'])) {
			do_action('ptrn/declined_since', $pledge, $pledge['attributes']['declined_since']);
			return false;
		}

		if($pledge != false) {
			return self::getUserPatronageLevel($pledge);
		}

		return false;

	}
	
	public static function getUserPatronageDuration($pledge) {

		$user_response = self::getPatreonUser($user);

		$patronage_age = 0;

	}
	
	public static function checkDeclinedPatronage($user) {
		
		if(!$user) {
			$user = wp_get_current_user();
		}

		$user_response = self::getPatreonUser($user);
		
		// If no user exists, the patronage cannot have been declined.
		if(!$user_response) {
			return false;
		}
		
		$creator_id = get_option('patreon-creator-id', false);

		$pledge = false;
		if (array_key_exists('included', $user_response)) {
			foreach ($user_response['included'] as $obj) {
				if ($obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id) {
					$pledge = $obj;
					break;
				}
			}
		}		
			
		if(isset($pledge['attributes']['declined_since']) && !is_null($pledge['attributes']['declined_since'])) {
			do_action('ptrn/declined_since', $pledge, $pledge['attributes']['declined_since']);
			return true;
		}
		return false;
	}
	
	public static function getUserPatronageLevel($pledge) {

		$patronage_level = 0;

		if(isset($pledge['attributes']['amount_cents'])) {
			$patronage_level = $pledge['attributes']['amount_cents'];
		}

		return $patronage_level;

	}
	
	public static function isPatron() {

		$user_patronage = self::getUserPatronage();

		if(is_numeric($user_patronage) && $user_patronage > 0) {
			return true;
		}

		return false;

	}
	function addPatreonRewriteRules($rules) {

		$upload_locations = wp_upload_dir();

		// We want the base upload location so we can account for any changes to date based subfolders in case there are

		$upload_dir = substr(wp_make_link_relative($upload_locations['baseurl']),1);

		$append = "
		\n # BEGIN Patreon WordPress Image Protection
		RewriteEngine On
		RewriteBase /		
		RewriteCond %{REQUEST_FILENAME} (\.png|\.jpg|\.gif|\.jpeg|\.bmp)
		RewriteRule ^".$upload_dir."(.*)$ index.php?patreon_action=serve_patron_only_image&patron_only_image=$1 [QSA,L]
		# END Patreon WordPress\n
		";
		
    	return $rules.$append;
	}
	function parseImagesInPatronOnlyPost($post_id) {
		
		// Parses post content and saves images in db marked as patron only
			 
		$post_level = get_post_meta( $post_id, 'patreon-level', true );
		
		// get post meta returns empty if no value is found. If so, set the value to 0.
		
		if($post_level == '') {
			$post_level = 0;				
		}

		// Check if both post level and site lock level are set to 0 or nonexistent. If so return normal content.
		
		if($post_level == 0 
			&& (!get_option('patreon-lock-entire-site',false)
				|| get_option('patreon-lock-entire-site',false)==0)
		) {
			return;
		}
		
		// If we are at this point, then this post is protected. Parse images.
		
		// Get only post content
		
		$post_content = get_post_field('post_content', $post_id);
		
		$dom = new domDocument;
		$dom->loadHTML($post_content);
		$dom->preserveWhiteSpace = false;
		$imgs  = $dom->getElementsByTagName("img");
		$links = array();
		for($i = 0; $i < $imgs->length; $i++) {
			
		   $image = basename($imgs->item($i)->getAttribute("src"));
		   
		   // Save the image into db:
		   
		   update_post_meta($post_id,'patreon_protected_image',$image);
		}

	}
	public static function servePatronOnlyImage($image=false) {

		if((!isset($image) OR !$image) AND isset($_REQUEST['patron_only_image'])) {
			$image = $_REQUEST['patron_only_image'];
		}
		if(!$image OR $image=='') {
			// This is not a rewritten image request. Exit.
			return;
		}

		if(!(isset($_REQUEST['patreon_action']) AND $_REQUEST['patreon_action'] == 'serve_patron_only_image')) {
			Patreon_Wordpress::readAndServeImage(basename($image));		
		}
		
		if(current_user_can('manage_options')) {
			Patreon_Wordpress::readAndServeImage(basename($image));	
		}			

		// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
		// It is independent of the plugin load order since it checks if it is defined.
		// It can be defined by any plugin until right before the_content filter is run.

		if(apply_filters('ptrn/bypass_image_filtering',defined('PATREON_BYPASS_IMAGE_FILTERING'))) {
			Patreon_Wordpress::readAndServeImage(basename($image));
		}
		
		// Check if the image is protected:
		global $wpdb;

		$protect_check = $wpdb->get_results( $wpdb->prepare("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1" , array('patreon_protected_image',basename($image))),ARRAY_A );

		if(!isset($protect_check[0]['meta_value'])) {
			// No match. Just serve image
			Patreon_Wordpress::readAndServeImage(basename($image));
		}
		
		// We are here, meaning we have a match. From this point we have to go into pledge checks
		
		// Check if we have a post id
		
		if(isset($protect_check[0]['post_id']) AND is_numeric($protect_check[0]['post_id'])) {
			
			// This is an image attached to a post. Try to get post's pledge level to decide what level to use
			
			// First check if entire site is locked, get the level for locking.
			
			$patreon_level = get_option('patreon-lock-entire-site',false);
			
			// Check if specific level is given for this post:
			
			$post_level = get_post_meta( $protect_check[0]['post_id'], 'patreon-level', true );
			
			// If post level is not returned empty string, then override site locking level
			
			if($post_level != '') {
				$patreon_level = $post_level;			
			}
			
			// If we didnt get any level for this post, and yet the image needs to be protected, set the pledge level to 1
			if(!($patreon_level > 0)) {
				
				$patreon_level = 1;
			}

		}
			
		$user_patronage = Patreon_Wordpress::getUserPatronage();
		
		$user = wp_get_current_user();
		
		$declined = Patreon_Wordpress::checkDeclinedPatronage($user);
			
		if($user_patronage == false 
			|| $user_patronage < ($patreon_level*100)
			|| $declined
		) {
		
			echo Patreon_Frontend::displayPatreonCampaignBanner($patreon_level);
			exit;
		}
		
		// At this point pledge checks are valid, and patron can see the image. Serve it:
		Patreon_Wordpress::readAndServeImage(basename($image));
		
	}
	public static function readAndServeImage($image) {

		$upload_locations = wp_upload_dir();

		// We want the base upload location so we can account for any changes to date based subfolders in case there are

		$upload_dir = wp_make_link_relative($upload_locations['basedir']);
		
		// Construct full path to the image:
		
		$file = $upload_locations['path'].'/'.$image;

		$mime = wp_check_filetype($file);
	
		if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) ) {
			$mime[ 'type' ] = mime_content_type( $file );
		}
			
		if( $mime[ 'type' ] ) {
			$mimetype = $mime[ 'type' ];
		}
		else {
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
		}
		header( 'Content-Type: ' . $mimetype ); // always send this
		if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
			header( 'Content-Length: ' . filesize( $file ) );
		}
			
		readfile( $file );
		exit; 		
		
	}

}

?>
