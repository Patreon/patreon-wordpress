<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patron_Metabox {

	function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'patreon_plugin_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'patreon_plugin_save_post_class_meta' ), 10, 2 );
	}

	function patreon_plugin_meta_boxes( $post_type ) {

		$post_types = get_post_types( array( 'public' => true ), 'names' );

	    $exclude = array(
	    );
		
		// Enables 3rd party plugins to modify the post types excluded from locking
		$exclude = apply_filters( 'ptrn/filter_excluded_posts_metabox', $exclude );

	    if ( in_array( $post_type, $exclude ) == false && in_array( $post_type, $post_types ) ) {
			
			add_meta_box(
				'patreon-level',      // Unique ID
				esc_html__( 'Patreon Level', 'Patreon Contribution Requirement' ),
				array( $this, 'patreon_plugin_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	function patreon_plugin_meta_box( $object, $box ) { 
			
		$label    = 'Add a minimum Patreon contribution required to access this content.  (Makes entire post patron only)';
		$readonly = '';
		
		if ( ! get_option( 'patreon-creator-id', false ) ) {
			$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url( "?page=patreon-plugin ").'">here</a>';
			$readonly = " readonly";
		}

			wp_nonce_field( basename( __FILE__ ), 'patreon_metabox_nonce' ); 
			
		?>
		<p>
			<label for="patreon-level"><?php _e( $label, '1' ); ?></label>
			<br><br>
			<strong>&#36; </strong><input type="text" id="patreon-level" name="patreon-level" value="<?php echo get_post_meta( $object->ID, 'patreon-level', true ); ?>" <?php echo $readonly ?>>
		</p>

		<?php
		
		if ( get_option( 'patreon-can-use-api-v2',false ) == 'yes' ) {
			
			$label    = 'Active patrons only (optional) - if on, only patrons active at and before this post\'s publishing date will be able to see this content )';
			$readonly = '';
			
			if ( ! get_option( 'patreon-creator-id', false ) ) {
				$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url( "?page=patreon-plugin" ).'">here</a>';
				$readonly = " readonly";
			}

			?>
			<p>
				<label for="patreon-active-patrons-only"><?php _e( $label, '1' ); ?></label>
				<br><br>
				<input type="checkbox" name="patreon-active-patrons-only" value="1" <?php checked( get_post_meta( $object->ID, 'patreon-active-patrons-only', true ),true,true ); ?> <?php echo $readonly ?> /> Yes
			</p>

			<?php
			
			$label    = 'Total patronage (optional) - any patron above total lifetime patronage $ value entered below can see this content';
			$readonly = '';
			
			if ( ! get_option( 'patreon-creator-id', false ) ) {
				$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url("?page=patreon-plugin").'">here</a>';
				$readonly = " readonly";
			}

			?>
			<p>
				<label for="patreon-total-patronage-level"><?php _e( $label, '1' ); ?></label>
				<br><br>
				<strong>&#36; </strong><input type="text" id="patreon-total-patronage-level" name="patreon-total-patronage-level" value="<?php echo get_post_meta( $object->ID, 'patreon-total-patronage-level', true ); ?>" <?php echo $readonly ?>>
			</p>

			<?php
		}
	}

	function patreon_plugin_save_post_class_meta( $post_id, $post ) {

		if ( ! isset( $_POST['patreon_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['patreon_metabox_nonce'], basename( __FILE__ ) ) )
			return $post_id;

		$post_type = get_post_type_object( $post->post_type );

		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;
		
		if( isset( $_POST['patreon-level'] ) && is_numeric( $_POST['patreon-level'] ) ) {
			$new_patreon_level = $_POST['patreon-level'];
		} else {
			$new_patreon_level = 0;
		}

		$patreon_level = get_post_meta( $post_id, 'patreon-level', true );

		if ( $new_patreon_level && $patreon_level == '' ) {

			add_post_meta( $post_id, 'patreon-level', $new_patreon_level, true );

		} else if ( ( $new_patreon_level || $new_patreon_level == 0 || $new_patreon_level == '0' ) && $new_patreon_level != $patreon_level ) {

			update_post_meta( $post_id, 'patreon-level', $new_patreon_level );

		} else if ( $new_patreon_level == '' && $patreon_level ) {

			delete_post_meta( $post_id, 'patreon-level', $patreon_level );

		}

		// Handles active patrons only toggle
		if ( isset( $_POST['patreon-active-patrons-only']) && $_POST['patreon-active-patrons-only'] != '') {
			update_post_meta( $post_id, 'patreon-active-patrons-only', 1 );
		} else {
			delete_post_meta( $post_id, 'patreon-active-patrons-only' );
		}
		
		// Handles lifetime patronage value
		if ( isset( $_POST['patreon-total-patronage-level'] ) && is_numeric( $_POST['patreon-total-patronage-level'] ) ) {
			$new_patreon_lifetime_patronage_level = $_POST['patreon-total-patronage-level'];
		} else {
			$new_patreon_lifetime_patronage_level = 0;
		}

		$patreon_lifetime_patronage_level = get_post_meta( $post_id, 'patreon-total-patronage-level', true );

		if ( $new_patreon_lifetime_patronage_level && $patreon_lifetime_patronage_level == '' ) {

			add_post_meta( $post_id, 'patreon-total-patronage-level', $new_patreon_lifetime_patronage_level, true );

		} else if ( ( $new_patreon_lifetime_patronage_level || $new_patreon_lifetime_patronage_level == 0 || $new_patreon_lifetime_patronage_level == '0') && $new_patreon_lifetime_patronage_level != $patreon_lifetime_patronage_level ) {

			update_post_meta( $post_id, 'patreon-total-patronage-level', $new_patreon_lifetime_patronage_level );

		} else if ( $new_patreon_lifetime_patronage_level == '' && $patreon_level ) {

			delete_post_meta( $post_id, 'patreon-total-patronage-level', $patreon_lifetime_patronage_level );

		}
		
		$patreon_lifetime_patronage_level = get_post_meta( $post_id, 'patreon-total-patronage-level', true );
	}
}