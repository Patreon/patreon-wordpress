<?php

/*
Plugin Name: Patreon
Plugin URI:
Description: Stay close with the Artists & Creators you're supporting
Version: 1.1
Author: Patreon
Author URI: http://patreon.com
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'load-post.php', 'patreon_plugin_meta_boxes_setup' );
add_action( 'load-post-new.php', 'patreon_plugin_meta_boxes_setup' );


function patreon_plugin_meta_boxes_setup() {
	add_action( 'add_meta_boxes', 'patreon_plugin_meta_boxes' );
	add_action( 'save_post', 'patreon_plugin_save_post_class_meta', 10, 2 );
}

function patreon_plugin_meta_boxes() {
    $screens = ['post', 'patreon-content'];
    foreach ($screens as $screen) {
        add_meta_box(
            'patreon-level',      // Unique ID
            esc_html__( 'Patreon Level', 'Patreon Contribution Requirement' ),
            'patreon_plugin_meta_box',
            $screen,
            'side',
            'default'
        );
    }
}

function patreon_plugin_meta_box( $object, $box ) { ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'patreon_metabox_nonce' ); ?>
	<p>
		<label for="patreon-level"><?php _e( "Add a minimum Patreon contribution required to access this content.", '1' ); ?></label>
		<br><br>
		<strong>&#36; </strong><input type="text" id="patreon-level" name="patreon-level" value="<?php echo get_post_meta( $object->ID, 'patreon-level', true ); ?>">
	</p>

<?php }


function patreon_plugin_save_post_class_meta( $post_id, $post ) {

	// var_dump($_POST['patreon-level']);exit;

	if ( !isset( $_POST['patreon_metabox_nonce'] ) || !wp_verify_nonce( $_POST['patreon_metabox_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	$post_type = get_post_type_object( $post->post_type );

	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	if(isset( $_POST['patreon-level']) && is_numeric($_POST['patreon-level'])) {
		$new_patreon_level = $_POST['patreon-level'];
	} else if (isset( $_POST['patreon-level']) && ($_POST['patreon-level'] == 0 || $_POST['patreon-level'] == '0') ) {
		$new_patreon_level = 0;
	} else {

		if($post->post_type == 'patreon-content') {
			$new_patreon_level = 9999;
		} else {
			$new_patreon_level = 0;
		}

	}

	$patreon_level = get_post_meta( $post_id, 'patreon-level', true );

	if ( $new_patreon_level && '' == $patreon_level ) {

		add_post_meta( $post_id, 'patreon-level', $new_patreon_level, true );

	} else if ( ($new_patreon_level || $new_patreon_level == 0 || $new_patreon_level == '0') && $new_patreon_level != $patreon_level ) {

		update_post_meta( $post_id, 'patreon-level', $new_patreon_level );

	} else if ( '' == $new_patreon_level && $patreon_level ) {

		delete_post_meta( $post_id, 'patreon-level', $patreon_level );

	}

	$patreon_level = get_post_meta( $post_id, 'patreon-level', true );

}



?>
