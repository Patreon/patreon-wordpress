<?php


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Patreon_Protect {

	function __construct() {
		add_filter('attachment_fields_to_edit', array($this, 'gallery_item_patreon_level_edit'), 10, 2 );
		add_filter('attachment_fields_to_save', array($this, 'gallery_item_patreon_level_save'), 10, 2 );
		add_action('mod_rewrite_rules',  array($this, 'addPatreonRewriteRules'));
		add_action('plugins_loaded',  array($this, 'servePatronOnlyImage') );
		add_action('admin_init', array( $this, 'filterTinyMCEPlugins' ) );
		add_action("wp_ajax_patreon_save_attachment_patreon_level", array( $this, "saveAttachmentLevel") );
		add_action("wp_ajax_patreon_make_attachment_pledge_editor", array( $this, "makeAttachmentPledgeEditor") );
		
	}

	function gallery_item_patreon_level_edit( $form_fields, $post ) {

		$form_fields['patreon_level'] = array(
			'label' => 'Patreon Level &#36;',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'patreon_level', true ),
			'helps' => 'Patreon Contribution Requirement.',
		);

		return $form_fields;
	}

	function gallery_item_patreon_level_save( $post, $attachment ) {

		if( isset($attachment['patreon_level']) ) {

			if ($attachment['patreon_level'] == '') {
				$attachment['patreon_level'] = 0;
			}

			update_post_meta( $post['ID'], 'patreon_level',  $attachment['patreon_level']);
		}

		return $post;
	}


	public static function getMimeType($file) {
		$mime = wp_check_filetype($file);
		if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
			$mime[ 'type' ] = mime_content_type( $file );

		if( $mime[ 'type' ] ) {
			$mimetype = $mime[ 'type' ];
		} else {
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
		}

		return $mimetype;
	}

	public static function getAttachmentIDfromThumbnailURL( $attachment_url = '' ) {

		global $wpdb;
		$attachment_id = false;

		if ( '' == $attachment_url )
			return false;

		$upload_dir_paths = wp_upload_dir();

		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

			$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );


			$cache_key = 'thumb_attachment_id_url_'.md5($attachment_url);
			$attachment_id = get_transient( $cache_key );
			if ( false === $attachment_id ) {
				$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
				set_transient( $cache_key, $attachment_id, 60);
			}

		}

		return $attachment_id;
	}
	public static function readAndServeImage($image) {
		
		$file = ABSPATH.str_replace(site_url().'/','',$image);
		

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
			return;	
		}
	
		$upload_locations = wp_upload_dir();

		// We want the base upload location so we can account for any changes to date based subfolders in case there are

		$upload_dir = substr(wp_make_link_relative($upload_locations['baseurl']),1);	

		$image = get_site_url().'/'. $upload_dir . $image;
		
		if(current_user_can('manage_options')) {
			Patreon_Protect::readAndServeImage($image);	
		}			
		
		// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
		// It is independent of the plugin load order since it checks if it is defined.
		// It can be defined by any plugin until right before the_content filter is run.

		if(apply_filters('ptrn/bypass_image_filtering',defined('PATREON_BYPASS_IMAGE_FILTERING'))) {
			Patreon_Protect::readAndServeImage($image);
		}
		
		// Check if the image is protected:
		
		$attachment_id = attachment_url_to_postid($image);
		
		// attachment_url_to_postid returns 0 if it cant find the attachment post id
		
		if($attachment_id == 0) {
			// Couldnt determine attachment post id. Try to get id from thumbnail
			$attachment_id = Patreon_Protect::getAttachmentIDfromThumbnailURL($image);
			//No go. Have to get out and serve the image normally
			if($attachment_id == 0) {
				Patreon_Protect::readAndServeImage($image);
			}
		}
		
		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );
		
		// If no specific level is found for this image, it is not set. Then set the level to 0.
		if(!$patreon_level) {
			$patreon_level = 0;
		}
		
		// If no level was set for image or it was 0, just serve the image.
		if($patreon_level == 0) {
			Patreon_Protect::readAndServeImage($image);
		}
		
		// We are here, then we have a nonzero pledge level. Protect the image.
		
		$user_patronage = Patreon_Wordpress::getUserPatronage();
		
		$user = wp_get_current_user();
		
		$declined = Patreon_Wordpress::checkDeclinedPatronage($user);
			
		if($user_patronage == false 
			|| $user_patronage < ($patreon_level*100)
			|| $declined
		) {
		
			Patreon_Protect::generateBlockedImagePlaceholder($patreon_level);
			exit;
		}
		
		// At this point pledge checks are valid, and patron can see the image. Serve it:
		Patreon_Protect::readAndServeImage($image);
		
	}
	public static function generateBlockedImagePlaceholder($patreon_level) {

		// The text to draw
		$title = 'Patrons Only';
		$text = 'Minimum pledge to view this image is $'.$patreon_level;

		header('Content-Type: image/png');

		$im = imagecreatetruecolor(400, 400);

		$white = imagecolorallocate($im, 255, 255, 255);
		$grey = imagecolorallocate($im, 192, 192, 192);
		$black = imagecolorallocate($im, 0, 0, 0);

		imagefilledrectangle($im, 0, 0, 400, 400, $grey);

		imagefilledrectangle($im, 0, 0, 5, 400, $white);
		imagefilledrectangle($im, 0, 5, 400, 0, $white);
		imagefilledrectangle($im, 0, 395, 400, 400, $white);
		imagefilledrectangle($im, 395, 0, 400, 400, $white);

		$font = PATREON_PLUGIN_ASSETS_DIR.'/fonts/OpenSans-Bold.ttf';
		
		imagettftext($im, 20, 0, 20, 280, $white, $font, $title);
		imagettftext($im, 10, 0, 20, 300, $white, $font, $text);

		imagepng($im);
		imagedestroy($im);
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
		RewriteCond %{HTTP_REFERER} !/wp-admin/ [NC]
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
	public function filterTinyMCEPlugins(){
		if(!is_admin()) {
			return;
		}
		add_filter( 'mce_external_plugins', array( $this,'addTinyMCEPlugin' ) );
		
	}

	public function addTinyMCEPlugin($plugin_array) {
		$plugin_array['tinymce_patron_only_button_plugin'] = PATREON_PLUGIN_ASSETS .'/js/tinymce_button.js';
		return $plugin_array;
	}

	public function saveAttachmentLevel($attachment_id=false) {
		
		if(!(is_admin() AND current_user_can('manage_options'))) {
			return;
		}
		
		echo ' <form id="patreon_attachment_patreon_level_form" action="/wp-admin/admin-ajax.php" method="post">';
		
		if(update_post_meta( $_REQUEST['patreon_attachment_id'], 'patreon_level',  $_REQUEST['patreon_attachment_patreon_level'])) {
			echo 'Pledge level for the image was updated!<br><br>';
		}
		else {
			echo 'Pledge level for the image was updated! The value you posted may be same with the value already set!<br><br>';
		}
		
		echo 'Current pledge level required to see this image<br><br>';
		echo '$&nbsp;&nbsp;<input id="patreon_attachment_patreon_level" type="text" name="patreon_attachment_patreon_level" value="'.get_post_meta( $_REQUEST['patreon_attachment_id'], 'patreon_level', true ).'" />';
		
		echo '<br><br><input type="submit" class="button button-primary button-large" value=" Update " />';
		echo '<input type="hidden" name="patreon_attachment_id" value="'.$_REQUEST['patreon_attachment_id'].'" />';
		echo '<input type="hidden" name="action" value="patreon_save_attachment_patreon_level" />';
		echo '</form>';
		wp_die();
		
	}
	public function makeAttachmentPledgeEditor($attachment_id=false) {

		if(!(is_admin() AND current_user_can('manage_options'))) {
			return;
		}
	
		if(isset($_REQUEST['patreon_attachment_id']) AND $_REQUEST['patreon_attachment_id'] != '') {
			$attachment_id = $_REQUEST['patreon_attachment_id'];
		}
		
		if(!$attachment_id OR $attachment_id =='') {
			// This is not a rewritten image request. Exit.
			return;
		}		
		
		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );
		
		echo ' <form id="patreon_attachment_patreon_level_form" action="/wp-admin/admin-ajax.php" method="post">';
		echo 'Current pledge level required to see this image<br><br>';
		echo '$&nbsp;&nbsp;<input id="patreon_attachment_patreon_level" type="text" name="patreon_attachment_patreon_level" value="'.$patreon_level.'" />';
		
		echo '<br><br><input type="submit" class="button button-primary button-large" value=" Update " />';
		echo '<input type="hidden" name="patreon_attachment_id" value="'.$attachment_id.'" />';
		echo '<input type="hidden" name="action" value="patreon_save_attachment_patreon_level" />';
		echo '</form>';
		wp_die();
		
	}

}

?>
