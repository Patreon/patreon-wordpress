<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Frontend {

	function __construct() {

		add_action( 'login_enqueue_scripts', array($this,'patreonEnqueueCss'), 10 );
		add_action( 'login_enqueue_scripts', array($this,'patreonEnqueueJs'), 1 );
		add_action( 'wp_enqueue_scripts', array($this,'patreonEnqueueCss') );
		add_action( 'wp_enqueue_scripts', array($this,'patreonEnqueueJs') );

		if(get_option('patreon-enable-register-with-patreon', false)) {
			add_action( 'register_form', array($this, 'showPatreonButton' ) );
			add_action( 'woocommerce_register_form_end', array($this, 'showPatreonButton') );
		}
		if(get_option('patreon-enable-login-with-patreon', false)) {
			add_action( 'login_form', array($this, 'showPatreonButton' ) );
			add_action( 'woocommerce_login_form_end', array($this, 'showPatreonButton' ) );
		}

		add_filter( 'the_content', array($this, 'protectContentFromUsers'), PHP_INT_MAX );

	
	}

	public function showPatreonButton() {

		global $wp;

		$client_id = get_option('patreon-client-id', false);

		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);
		$admins_editors_login_with_patreon = get_option('patreon-enable-allow-admins-login-with-patreon', false);

		if($client_id == false) {
			return '';
		}

		$redirect_uri = site_url().'/patreon-authorization/';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.$redirect_uri;

		if($login_with_patreon == true && $admins_editors_login_with_patreon == false && isset($_REQUEST['patreon-msg']) && $_REQUEST['patreon-msg'] == 'login_with_wordpress') {
			echo '<p class="patreon-msg">You can now login with your wordpress username/password.</p>';
		} else if( $login_with_patreon == false && isset($_REQUEST['patreon-msg']) && $_REQUEST['patreon-msg'] == 'login_with_patreon') {
			echo '<p class="patreon-msg">You can now login with your wordpress username/password.</p>';
		} else {
			echo '<div class="patreon-login-refresh-button">'.self::patreonMakeLoginButton().'</div>';
		}

	}

	function patreonEnqueueJs() {
		wp_register_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS.'/js/app.js', array( 'jquery' ) );
		wp_enqueue_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS.'/js/app.js', false );
	}

	function patreonEnqueueCss() {
		wp_register_style( 'patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css', false );
		wp_enqueue_style('patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css' );
	}

	public static function displayPatreonCampaignBanner($patreon_level = false) {

		global $wp;
		global $post;

		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);

        $creator_id = get_option('patreon-creator-id', false);

        $contribution_required = '';
		
        if($patreon_level != false) {
			
        	$contribution_required = str_replace('%%pledgelevel%%',$patreon_level,PATREON_TEXT_LOCKED_POST);
        	$contribution_required = apply_filters('ptrn/contribution_required',$contribution_required,$patreon_level);

        }
		 
		if($login_with_patreon)
		{
			$login_with_patreon_button = self::patreonMakeLoginButton();
		}
        if ($creator_id) {
			
			$be_a_patron_button = self::patreonMakePatronButton($creator_id);
	
			// Wrap message and buttons in divs for responsive interface mechanics
			
			$contribution_required = '<div class="patreon-locked-content-message">'.$contribution_required.'</div>';
			
			$be_a_patron_button = '<div class="patreon-be-patron-button">'.$be_a_patron_button.'</div>';
			
			if(isset($login_with_patreon_button))
			{
				$login_with_patreon_button = '<div class="patreon-login-refresh-button">'.$login_with_patreon_button.'</div>';
			}
			
			$label_over_patron_button = self::getLabelOverPatronButton();
			$label_over_login_button = self::getLabelOverLoginButton();
			
			// Wrap all of them in a responsive div
			
			$campaign_banner = '<div class="patreon-campaign-banner">'.
									$contribution_required.
									'<div class="patreon-patron-button-wrapper">'.$label_over_patron_button.$be_a_patron_button.'</div>'.
									'<div class="patreon-login-button-wrapper">'.$label_over_login_button.$login_with_patreon_button.'</div>'.
								'</div>';
			
			// This extra button is solely here to test whether new wrappers cause any design issues in different themes. For easy comparison with existing unwrapped button. Remove when confirmed.
			

        	$campaign_banner = apply_filters('ptrn/campaign_banner', $campaign_banner, $patreon_level);

            return $campaign_banner;
        }
		

	}
	
	function getLabelOverPatronButton() {
		
		
		$user_logged_into_patreon = self::isUserLoggedInPatreon();

		$is_patron = Patreon_Wordpress::isPatron();
		
		if($is_patron AND $user_logged_into_patreon)
		{
			// Patron logged in and patron, but we are still showing the banner. This means pledge level is not enough.
			return PATREON_TEXT_PLEDGE_NOT_ENOUGH;
			
		}
		if(!$is_patron AND $user_logged_into_patreon)
		{
			// Patron logged in and not patron
			
			return PATREON_TEXT_BECOME_PATRON;
			
		}
	
		// User not logged in
		
		
		return PATREON_TEXT_BECOME_PATRON;
		
		
	}
	function getLabelOverLoginButton() {
		

		if(!self::isUserLoggedInPatreon())
		{
			return PATREON_TEXT_ALREADY_PATRON;			
		}

	
		return PATREON_TEXT_MISTAKEN_PATRON;			
			
		
	}
	
	function patreonMakePatronButton($creator_id=false) {
		global $post;
		
		if(!$creator_id)
		{
			$creator_id = get_option('patreon-creator-id', false);
		}
		
		$label_text = PATREON_TEXT_SUPPORT_ON_PATREON;
		
		/* patreon banner when user patronage not high enough */
					
		
		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);

		if($login_with_patreon) {
			$redirect_uri = wp_login_url().'?patreon-msg=login_with_patreon&patreon-redirect='.$post->ID;
		} else {
			$redirect_uri = wp_login_url().'?patreon-user-redirect='.$post->ID;
		}
		
		$user_logged_into_patreon = self::isUserLoggedInPatreon();

		$is_patron = Patreon_Wordpress::isPatron();
		
		if($is_patron AND isset($post)) {
			$redirect_uri = get_permalink($post->ID);
		}	
		if($is_patron) {
			$label_text = PATREON_TEXT_UPGRADE_PLEDGE;
		}
		
		if(!$is_patron AND $user_logged_into_patreon) {
			$label_text = PATREON_TEXT_SUPPORT_ON_PATREON;
		}
		
		$paywall_img = get_option('patreon-paywall-img-url', false);
		
        if ($paywall_img == false) {
        	$paywall_img = '<div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> '.$label_text.'</div></div>';
        } else {
        	$paywall_img = '<img src="'.$paywall_img.'" />';
        }
		
		$href = 'https://www.patreon.com/bePatron?u='.$creator_id.'&redirect_uri='.urlencode($redirect_uri);
		
		return apply_filters('ptrn/patron_button', '<a href="'.$href.'">'.$paywall_img.'</a>');
	
	
	}
	function isUserLoggedInPatreon() {
		 
		$user_logged_into_patreon = false;
		
		if(is_user_logged_in()){
			// User is logged into WP. Check if user has valid patreon data :
			
			$user = wp_get_current_user();

			if($user) {
				
				$user_response = Patreon_Wordpress::getPatreonUser($user);
				// ^ REVISIT - whats above may be a concern - it connects to API to check for valid user for every generation of button. If we could cache it it would be better

				if($user_response) {
					// This is a user logged into Patreon. 
					$user_logged_into_patreon = true;
				}					
			}
			
		}		
		return $user_logged_into_patreon;
	}
	function patreonMakeLoginButton($client_id=false) {
		
		if(!$client_id)
		{
			$client_id = get_option('patreon-client-id', false);
		}
		
		// Check if user is logged in to WP, for determination of label text
		
		// Set login label to default
		$login_label = PATREON_TEXT_CONNECT;
		
		if(is_user_logged_in()){
			// User is logged into WP. Check if user has valid patreon data :
			
			$user = wp_get_current_user();

			if($user) {
				
				$user_response = Patreon_Wordpress::getPatreonUser($user);
				// ^ REVISIT - whats above may be a concern - it connects to API to check for valid user for every generation of button. If we could cache it it would be better

				if($user_response) {
					// This is a user logged into Patreon. use refresh text
					$login_label = PATREON_TEXT_REFRESH;
				}					
			}
			
			
		}
		
		$redirect_uri = site_url().'/patreon-authorization/';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.$redirect_uri;
	
		return apply_filters('ptrn/login_button', '<a href="'.$href.'" class="ptrn-login" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'"><div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> '.$login_label.'</div></div></a>', $href);

	}	

	function protectContentFromUsers($content) {

		global $post;

		$post_types = get_post_types(array('public'=>true),'names');

		if(in_array(get_post_type(),$post_types)) {

			if(current_user_can('manage_options')) {
				return $content;
			}
		
			// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
			// It is independent of the plugin load order since it checks if it is defined.
			// It can be defined by any plugin until right before the_content filter is run.
			
			if(defined('PATREON_BYPASS_FILTERING')) {
                return $content;
            }
			
			$patreon_level = get_post_meta( $post->ID, 'patreon-level', true );

			if($patreon_level == 0 AND (!get_option('patreon-lock-entire-site',false) OR get_option('patreon-lock-entire-site',false)==0)) {
				return $content;
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();
	
			if( $user_patronage == false || $user_patronage < ($patreon_level*100) || get_option('patreon-lock-entire-site',false)>0 ) {

				//protect content from user
				
				// Check if creator id exists. 
				
				$creator_id = get_option('patreon-creator-id', false);
				
				// // IF creator id exists. Do the banner. If not, no point in protecting since we wont be able to send people to patronage. If so dont modify normal content.
				
				if($creator_id) {
					
					$content = self::displayPatreonCampaignBanner($patreon_level);

					$content = apply_filters('ptrn/post_content', $content, $user_patronage);				
					
				}
				
			}

		}

		return $content;

	}


	public static function returnPatreonEmbeddedContent($the_content) {

		$safety_embeds = get_option('patreon-enable-safe-patreon-embeds', false);

		if($safety_embeds) {

			$the_content = wptexturize($the_content);
			$the_content = convert_smilies($the_content);
			$the_content = convert_chars($the_content);
			$the_content = wpautop($the_content);
			$the_content = shortcode_unautop($the_content);
			$the_content = prepend_attachment($the_content);
			$the_content = do_shortcode($the_content);

			return $the_content;
		}

		return apply_filters('the_content', $the_content );

	}


}

?>
