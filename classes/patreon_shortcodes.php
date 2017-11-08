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
