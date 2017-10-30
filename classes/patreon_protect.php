<?php


if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Patreon_Protect {

	function __construct() {
		add_filter('mod_rewrite_rules', array($this, 'appendPatreonProtectHtaccess'));
		add_filter( 'attachment_fields_to_edit', array($this, 'gallery_item_patreon_level_edit'), 10, 2 );
		add_filter( 'attachment_fields_to_save', array($this, 'gallery_item_patreon_level_save'), 10, 2 );
	}

	function gallery_item_patreon_level_edit( $form_fields, $post ) {

		$form_fields['patreon_level'] = array(
			'label' => 'Patreon Level &#36;',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'patreon_level', true ),
			'helps' => 'Patreon Contribution Requirement. Only valid for images saved under the patreon_protect folder.',
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


	public static function createProtectedUploadDirectory() {
		$upload = wp_upload_dir();
	    $upload_dir = $upload['basedir'];
	    $upload_dir = $upload_dir . '/patreon_protect';
	    if (! is_dir($upload_dir)) {
	       mkdir( $upload_dir, 0755 );
	    }
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

	public static function generateBlockImagePlaceholderFromPlaceholdIt($patreon_level) {

		$title = 'Patrons Only';
		$text = 'Minimum Patreon Contribution to see view this image is $'.$patreon_level;

		return 'https://placehold.it/400x400?text='.urlencode($text);

	}

	public static function generateBlockedImagePlaceholder($patreon_level) {

		// The text to draw
		$title = 'Patrons Only';
		$text = 'Minimum Patreon Contribution to view this image is $'.$patreon_level;

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


		$font = 'assets/fonts/OpenSans-Regular.ttf';
		imagettftext($im, 20, 0, 20, 280, $white, $font, $title);
		imagettftext($im, 10, 0, 20, 300, $white, $font, $text);

		imagepng($im);
		imagedestroy($im);
	}

	function appendPatreonProtectHtaccess( $rules ) {

$append = <<<EOD
\n # BEGIN Patreon_Connect
RewriteCond %{REQUEST_FILENAME} -s
RewriteRule ^wp-content/uploads/patreon_protect/(.*)$ wp-content/plugins/patreon-wordpress/protect.php?file=$1 [QSA,L]
# END Patreon_Connect\n
EOD;

    	return $rules.$append;
	}


}

?>
