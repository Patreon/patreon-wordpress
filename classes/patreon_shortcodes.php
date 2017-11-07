<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Shortcodes {

	function __construct() {

		add_shortcode( 'patreon_register_button', array($this, 'renderPatreonButton') );
		add_shortcode( 'patrons_name', array($this, 'renderPatronsName') );
		add_shortcode( 'personal_message', array($this, 'renderPersonalMessage') );
		add_shortcode( 'patrons_only', array($this, 'patronsOnlyContent') );

	}

	public function patronsOnlyContent($args, $content = '') {

		$user_patronage = Patreon_Wordpress::getUserPatronage();

		$min_level = 0;
		if(isset($args['min_level']) && is_numeric($args['min_level'])) {
			$min_level = $args['min_level'];
		}
	
		if($user_patronage == false || $user_patronage < ($min_level*100)) {

			/* check if shortcode has slug parameter */
			if(isset($args['slug'])) {

				/* get patreon-content post with matching url slug */
				$patreon_content = get_page_by_path($args['slug'],OBJECT,'patreon-content');

				if($patreon_content == false) {
					$content = 'Patreon content not found.';
				} else {
					$content = Patreon_Frontend::returnPatreonEmbeddedContent($patreon_content->post_content);
				}

			} else {
				$content = Patreon_Frontend::displayPatreonCampaignBanner($min_level);
			}

		}

		return $content;

	}

	public function renderPersonalMessage($args) {

		$before = '';
		if(isset($args['before_name'])) {
			$before = $args['before_name'];
		}

		$after = '';
		if(isset($args['after_name'])) {
			$after = $args['after_name'];
		}

		$patrons_name = '';

		if(is_user_logged_in()) {

			$user = wp_get_current_user();
			$patrons_name = get_user_meta($user->ID, 'user_firstname', true);

			if($patrons_name) {
				$patrons_name = ucfirst($patrons_name);
			}

			return $before . $patrons_name . $after;

		}

		return '';

	}

	public function renderPatronsName() {

		if(is_user_logged_in()) {
			$user = wp_get_current_user();
			$patrons_name = get_user_meta($user->ID, 'user_firstname', true);

			if($patrons_name) {
				return ucfirst($patrons_name);
			}

		}

		return '';

	}

	public function renderPatreonButton() {

		global $post;

		$client_id = get_option('patreon-client-id', false);

		if($client_id == false) {
			return '';
		}
	
		return Patreon_Frontend::patreonMakeLoginButton($client_id);

	}


}


?>
