<?php


// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
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
	
		$current_user = wp_get_current_user();
		
		global $post;
		
		$label    = 'Require the below membership tier or higher to view this post. (Makes entire post patron only)  <a href="https://www.patreondevelopers.com/t/patreon-wordpress-locking-options-guide/1135#heading--section-1?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_link_1&utm_term=" target="_blank">(?)</a>';		
		
		// Override messaging for lite plans
		
		$creator_tiers = get_option( 'patreon-creator-tiers', false );
		
		$advanced_post_options_toggle_status = get_user_meta( $current_user->ID, 'patreon-wordpress-advanced-options-toggle', true );
		
		$no_tiers = false;
		
		if ( !$creator_tiers OR $creator_tiers == '' OR !is_array( $creator_tiers['included'][1] ) ) {
			$no_tiers = true;		
		}
		
		if ( $no_tiers ) {
			
			$label    = 'To use tiers, your Patreon account must be Pro Plan or above and your site must be connected to Patreon. <a href="https://www.patreon.com/edit/tiers?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_patreon_plan_upgrade_message&utm_term=" target="_blank">Upgrade your plan here</a> or force-reconnect your site <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_patreon_plan_upgrade_message&utm_term=" target="_blank">here</a>.';
			
			$advanced_post_options_toggle_status = 'on';
		
		}
		
		$readonly = '';
		$disabled = '';
		
		if ( !get_option( 'patreon-creator-id', false ) ) {
			
			$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url( "?page=patreon-plugin ").'">here</a>';
			$disabled = " disabled";
			
		}

			wp_nonce_field( basename( __FILE__ ), 'patreon_metabox_nonce' );
				
		?>
		<p>
			<label for="patreon-level"><?php _e( $label, '1' ); ?></label>
			<br><br>
			<div id="patreon_level_select_wrapper"><select id="patreon_level_select" name="patreon-level"<?php echo $disabled ?> pw_post_id="<?php echo $object->ID; ?>"><option value="<?php echo get_post_meta( $object->ID, 'patreon-level', true ); ?>"><?php echo Patreon_Wordpress::make_tiers_select( $post ); ?></option></select> <img id="patreon_level_refresh" src="<?php echo PATREON_PLUGIN_ASSETS; ?>/img/refresh_tiers_18.png" style="width: 18px; height: 18px;" patreon_wordpress_nonce_populate_tier_dropdown="<?php echo wp_create_nonce( 'patreon_wordpress_nonce_populate_tier_dropdown' ); ?>" /></div>
		</p>
		
		<?php
		
			if ( !Patreon_Wordpress::check_plugin_exists('patron-plugin-pro') ) {
			?>
			
				<p> Get <a href="https://codebard.com/patron-pro-addon-for-patreon-wordpress?utm_source=<?php urlencode( site_url())?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=patreon_wordpress_patreon_level_metabox_callout&utm_term=">Patron Pro Addon</a> for more advanced locking methods.</p>
			
			<?php
			
			}
			else {
			?>
				<p> Use <a href="https://codebard.com/patron-plugin-pro-documentation/category/manual?utm_source=<?php urlencode( site_url())?>&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=patreon_wordpress_patreon_level_metabox_callout&utm_term=" target="_blank">Patron Pro's advanced locking methods</a> to fine-tune your content gating.</p>
			
			<?php
			}
			
			$advanced_post_options_toggle_status_display = 'style=" display: block;" ';
			
			if ( $advanced_post_options_toggle_status == '' OR $advanced_post_options_toggle_status == 'off' ) {
				$advanced_post_options_toggle_status_display = 'style=" display: none;" ';
			}
		?> 		
		
		<div <?php echo $advanced_post_options_toggle_status_display ?>id="patreon-wordpress-advanced-options-toggle">
		<?php
		
			$label    = '(If you set a precise amount in advanced settings below, or had one set before, that will be used instead.) <br><br>Require the below precise $ monthly membership or over to view this post. (optional - overrides the above select box when used)  <a href="https://www.patreondevelopers.com/t/patreon-wordpress-locking-options-guide/1135#heading--section-11?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_link_2&utm_term=" target="_blank">(?)</a>';
			
			$readonly = '';
			
			if ( $no_tiers ) {
				
				$label = 'Gate this content for the below amount. <a href="https://www.patreondevelopers.com/t/patreon-wordpress-locking-options-guide/1135#heading--section-11?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_link_2&utm_term=" target="_blank">(?)</a> Patreon will default it to $5, but your patrons can enter a custom amount while pledging at Patreon';
				
			}
		
			if ( !get_option( 'patreon-creator-id', false ) ) {
				
				$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url( "?page=patreon-plugin ").'">here</a>';
				$readonly = " readonly";
				
			}
		?>
		
			<p>
				<label for="patreon-level-exact"><?php _e( $label, '1' ); ?></label>
				<br><br>
				<strong>&#36; </strong><input type="text" id="patreon-level-exact" name="patreon-level-exact" value="<?php echo get_post_meta( $object->ID, 'patreon-level', true ); ?>" <?php echo $readonly ?>>		
			</p>
		
			<?php
			
			$label    = 'Require a pledge active at the time of this post’s creation to view this post. (optional) This will make it so that only patrons who were patrons at or before the post date of this post can access this post. <a href="https://www.patreondevelopers.com/t/patreon-wordpress-locking-options-guide/1135#heading--section-2?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_link_3&utm_term=" target="_blank">(?)</a>';
			$readonly = '';
			
			if ( !get_option( 'patreon-creator-id', false ) ) {
				
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
			
			$label    = 'Require a lifetime pledge amount greater than this amount to view this post. (optional) <a href="https://www.patreondevelopers.com/t/patreon-wordpress-locking-options-guide/1135#heading--section-3?utm_source=' . urlencode( site_url() ) . '&utm_medium=patreon_wordpress_plugin&utm_campaign=&utm_content=post_locking_metabox_link_4&utm_term=" target="_blank">(?)</a>';
			$readonly = '';
			
			if ( !get_option( 'patreon-creator-id', false ) ) {
				
				$label    = 'Post locking won\'t work without Creator ID. Please confirm you have it <a href="'.admin_url("?page=patreon-plugin").'">here</a>';
				$readonly = " readonly";
				
			}

			?>
			<p>
				<label for="patreon-total-patronage-level"><?php _e( $label, '1' ); ?></label>
				<br><br>
				<strong>&#36; </strong><input type="text" id="patreon-total-patronage-level" name="patreon-total-patronage-level" value="<?php echo get_post_meta( $object->ID, 'patreon-total-patronage-level', true ); ?>" <?php echo $readonly ?>>
			</p>
		
		</div>
		<br />

		
		<?php 
			
			$advanced_post_options_toggle_text = 'Hide advanced';
			
			if( $advanced_post_options_toggle_status == '' OR $advanced_post_options_toggle_status == 'off' ) {
				$advanced_post_options_toggle_text = 'Show advanced';
			}
		?>
		
		<a href="" toggle="patreon-wordpress-advanced-options-toggle" patreon_wordpress_advanced_options_toggle_nonce="<?php echo wp_create_nonce() ?>" togglestatus="<?php echo $advanced_post_options_toggle_status ?>" ontext="Hide advanced" offtext="Show advanced" class="patreon-wordpress-admin-toggle"><?php echo $advanced_post_options_toggle_text ?></a>
		
		<?php
		
	}

	function patreon_plugin_save_post_class_meta( $post_id, $post ) {

		if ( !isset( $_POST['patreon_metabox_nonce'] ) || !wp_verify_nonce( $_POST['patreon_metabox_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}
	
		$post_type = get_post_type_object( $post->post_type );

		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}
		
		if( isset( $_POST['patreon-level'] ) && is_numeric( $_POST['patreon-level'] ) ) {
			$new_patreon_level = $_POST['patreon-level'];
		} else {
			$new_patreon_level = 0;
		}

		$patreon_level = get_post_meta( $post_id, 'patreon-level', true );
		
		// Now, an exception for the old metabox which was moved to patreon-level-exact - if it is different from the value already saved or from 0, override the select box with its value since it would mean a specific override initiated by user.

		if( isset( $_POST['patreon-level-exact'] ) && is_numeric( $_POST['patreon-level-exact'] ) ) {
			
			if ( $_POST['patreon-level-exact'] != $patreon_level ) {
				$new_patreon_level = $_POST['patreon-level-exact'];
			}
			
		}
		
		update_post_meta( $post_id, 'patreon-level', $new_patreon_level );
		
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

		update_post_meta( $post_id, 'patreon-total-patronage-level', $new_patreon_lifetime_patronage_level );
		
	}
}