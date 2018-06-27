<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_User_Profiles {

	function __construct() {
		add_action( 'show_user_profile', array($this, 'patreon_user_profile_fields') );
		add_action( 'edit_user_profile', array($this, 'patreon_user_profile_fields') );
		add_action( 'personal_options_update', array($this, 'save_patreon_user_profile_fields') );
		add_action( 'edit_user_profile_update', array($this, 'save_patreon_user_profile_fields') );
		add_action( 'user_profile_update_errors', array($this, 'prevent_email_change'), 10, 3 );
	}

	function patreon_user_profile_fields( $user ) {

		if(current_user_can('manage_options')) {

			?>

			<br>

			<h3><?php _e("Patreon Profile", "blank"); ?></h3>

			<table class="form-table">
				<tr>
					<th><label for="patreon_user"><?php _e("Patreon User"); ?></label></th>
					<td>
						<input type="text" name="patreon_user" id="patreon_user" disabled value="<?php echo esc_attr( get_the_author_meta( 'patreon_user', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="patreon_created"><?php _e("Patreon Created"); ?></label></th>
					<td>
						<input type="text" name="patreon_created" id="patreon_created" disabled value="<?php echo esc_attr( get_the_author_meta( 'patreon_created', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="user_firstname"><?php _e("Patreon First name"); ?></label></th>
					<td>
						<input type="text" name="user_firstname" id="user_firstname" disabled value="<?php echo esc_attr( get_the_author_meta( 'user_firstname', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="user_lastname"><?php _e("Patreon Last name"); ?></label></th>
					<td>
						<input type="text" name="user_lastname" id="user_lastname" disabled value="<?php echo esc_attr( get_the_author_meta( 'user_lastname', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
			</table>

			<?php

		}
	}

	function save_patreon_user_profile_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// if ( is_email( $_POST['patreon_email'] ) ) {

			//update_user_meta( $user_id, 'patreon_email', $_POST['patreon_email'] );

		// }

		// update_user_meta( $user_id, 'patreon_user', $_POST['patreon_user'] );
		// update_user_meta( $user_id, 'patreon_created', $_POST['patreon_created'] );
		// update_user_meta( $user_id, 'user_firstname', $_POST['province'] );
		// update_user_meta( $user_id, 'user_lastname', $_POST['postalcode'] );
	}

	function prevent_email_change( $errors, $update, $user ) {

		$old = get_user_by('id', $user->ID);

		if( $user->user_email != $old->user_email   && (!current_user_can('create_users')) )
			$user->user_email = $old->user_email;

	}

}


?>
