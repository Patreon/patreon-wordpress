<?php 

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Posts {

	function __construct() {
		add_action( 'init', array($this, 'registerPatreonPost') );
		add_action( 'init', array($this, 'registerPatreonTier'), 0 );
		add_filter( 'the_content', array($this, 'protectContentFromUsers') );
	}

	function registerPatreonPost() {
		$labels = array(
			'name'               => _x( 'Patreon Content', 'post type general name' ),
			'singular_name'      => _x( 'Patreon Content', 'post type singular name' ),
			'add_new'            => _x( 'Add New Patreon Content', 'patreon-content' ),
			'add_new_item'       => __( 'Add New Patreon Content' ),
			'edit_item'          => __( 'Edit Patreon Content' ),
			'new_item'           => __( 'New Patreon Content' ),
			'all_items'          => __( 'All Patreon Content' ),
			'view_item'          => __( 'View Patreon Content' ),
			'search_items'       => __( 'Search Patreon Content' ),
			'not_found'          => __( 'No Patreon Content found' ),
			'not_found_in_trash' => __( 'No Patreon Content found in the Trash' ), 
			'parent_item_colon'  => '',
			'menu_name'          => 'Patreon Content'
		);
		$args = array(
			'labels'        => $labels,
			'description'   => 'Patreon Content',
			'public'        => true,
			'menu_position' => 4,
			'menu_icon' => 'dashicons-groups',
			'supports'      => array( 'title', 'editor'),
			'capability_type' => 'post',
			'has_archive'   => false,
			'taxonomies' 	=> array('patreon-tier'),
			'rewrite'       => array( 'slug' => 'patreon-content' ),
		);
		register_post_type( 'patreon-content', $args );	
	}

	function registerPatreonTier() {
		register_taxonomy(
	        'patreon-tier',
	        'patreon-tier',
	        array(
	            'labels' => array(
	                'name' => 'Patreon Tier',
	                'add_new_item' => 'Add New Patreon Tier',
	                'new_item_name' => 'New Patreon Tier'
	            ),
	            'show_ui' => true,
	            'show_tagcloud' => false,
	            'hierarchical' => false
	        )
	    );
	}

	function protectContentFromUsers($content) {

		if(is_singular('patreon-content') && get_post_type() == 'patreon-content') {

			$user = wp_get_current_user();

			$user_patronage = Patreon_Wordpress::checkUserPatronage($user);

			/* if user patronage > X amount etc */
			/* else show patreon campaign banner */
			$content = Patreon_Wordpress::displayPatreonCampaignBanner();


		}

		return $content;

	}

}


?>