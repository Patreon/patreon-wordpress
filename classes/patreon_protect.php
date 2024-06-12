<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Patreon_Protect {

	function __construct() {
		
		// If image feature was not turned on before, or turned off, we skip activating image protection functions:

		if ( get_option( 'patreon-enable-file-locking', false ) ) {
		
			add_filter( 'attachment_fields_to_edit', array( $this, 'GalleryItemSavePatreonEdit' ), 10, 2 );
			add_filter( 'attachment_fields_to_save', array( $this, 'GalleryItemSavePatreonLevel' ), 10, 2 );
			add_filter( 'the_content', array( $this, 'ParseContentForProtectedImages' ), PHP_INT_MAX-4);
			add_action( 'wp_ajax_nopriv_patreon_catch_image_click', 'Patreon_Protect::CatchImageClick' );
			add_action( 'wp_ajax_patreon_catch_image_click', 'Patreon_Protect::CatchImageClick' );
			
		}
		// Only image-reader is left always on for backward compatibility in case a user already has images linked directly - it can be put into the conditional block above in later versions 
		add_action( 'plugins_loaded',  array( $this, 'servePatronOnlyImage' ) );
	}
	function GalleryItemSavePatreonEdit( $form_fields, $post ) {

		$form_fields['patreon_level'] = array(
			'label' => 'Minimum Patreon pledge amount​ &#36;',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'patreon_level', true ),
			'helps' => '​​Anyone who isn\'t your patron pledging at or above the minimum will not be able to see this image.',
		);

		return $form_fields;
	}
	function GalleryItemSavePatreonLevel( $post, $attachment ) {

		if ( isset( $attachment['patreon_level'] ) ) {

			if ( $attachment['patreon_level'] == '' ) {
				$attachment['patreon_level'] = 0;
			}

			update_post_meta( $post['ID'], 'patreon_level',  $attachment['patreon_level'] );
			
		}
		
		// Flush this item's cached file:
		
		self::deleteCachedAttachmentPlaceholders($post['ID']);

		return $post;
		
	}
	public static function getMimeType( $file ) {
		
		$mime = wp_check_filetype( $file );
		
		if( $mime[ 'type' ] === false && function_exists( 'mime_content_type' ) )
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

		if ( $attachment_url == '' ) {
			return false;
		}
		
		$upload_dir_paths = wp_upload_dir();

		$protocol_snipped_baseurl = str_replace( 'https://', '', $upload_dir_paths['baseurl'] );
		$protocol_snipped_baseurl = str_replace( 'http://', '', $protocol_snipped_baseurl );
		
		$protocol_snipped_attachment_url = str_replace( 'https://', '', $attachment_url );
		$protocol_snipped_attachment_url = str_replace( 'http://', '', $protocol_snipped_attachment_url );

		if ( strpos( $protocol_snipped_attachment_url, $protocol_snipped_baseurl ) !== false ) {

			$search_attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
			$search_attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $search_attachment_url );
	
			$cache_key     = 'thumb_attachment_id_url_' . md5( $attachment_url );
			$attachment_id = get_transient( $cache_key );

			if ( $attachment_id == false OR $attachment_id == '' ) {
				
				$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $search_attachment_url ) );
				set_transient( $cache_key, $attachment_id, 60 );
				
			}

			// If attachment is still false, try finding the attachment only through the bare image file
	
			if ( $attachment_id == false OR $attachment_id == '' ) {
				
				$search_attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $protocol_snipped_attachment_url );
				$search_attachment_url = str_replace( $protocol_snipped_baseurl . '/', '', $search_attachment_url );		

				$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $search_attachment_url ) );
				set_transient( $cache_key, $attachment_id, 60);
			
			}
			
			return $attachment_id;
			
		}
		
		return false;
		
	}
	public static function readAndServeImage( $image, $locked = false ) {
		
		// Remove site url from requested image url - force http case
		
		$image = str_replace( trailingslashit( site_url( '','http' ) ), '', $image );
		
		// Remove site url from requested image url - force https case
		
		$image = str_replace( trailingslashit( site_url( '', 'https' ) ), '', $image );

		$file = wp_normalize_path( trailingslashit( ABSPATH ) . $image );
		
		$mime = wp_check_filetype( $file );
	
		if ( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) ) {
			$mime[ 'type' ] = mime_content_type( $file );
		}
			
		if ( $mime[ 'type' ] ) {
			$mimetype = $mime[ 'type' ];
		}
		else {
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
		}

		header( 'Content-Type: ' . $mimetype ); // always send this
		
		if ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) === false ) {
			header( 'Content-Length: ' . filesize( $file ) );
		}
		
		// If the image is a gated image, send no-cache headers:
		
		
		if ( $locked ) {
		
			header( "Expires: " . gmdate("D, d M Y H:i:s") . " GMT" );
			header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
			header( "Cache-Control: no-store, no-cache, must-revalidate" ); 
			header( "Cache-Control: post-check=0, pre-check=0", false ); 
			header("Pragma: no-cache");
			
		}

		echo file_get_contents( $file );
		exit;
		
	}
	public static function servePatronOnlyImage( $image=false ) {

		if ( ( !isset( $image ) OR !$image ) AND isset( $_REQUEST['patron_only_image'] ) ) {
			$image = $_REQUEST['patron_only_image'];
		}
		
		if ( !$image OR $image == '') {
			// This is not a rewritten image request. Exit.
			return;
		}

		if ( !( isset( $_REQUEST['patreon_action'] ) AND $_REQUEST['patreon_action'] == 'serve_patron_only_image' ) ) {
			wp_die('No patreon_action provided with image request');
		}

		if ( !get_option( 'patreon-enable-file-locking', false ) ) {
			wp_die('Image/file locking with Patreon is not enabled in your WP admin -> Patreon settings');
		}

		$upload_locations = wp_upload_dir();

		// We want the base upload location so we can account for any changes to date based subfolders in case there are

		$upload_dir = substr( wp_make_link_relative( $upload_locations['baseurl'] ) , 1 );	

		$image = get_site_url() . '/' . $upload_dir . '/' . $image;
		
		// Check if image exists in media library
		
		$attachment_id = Patreon_Protect::get_attachment_id_from_url( $image );
	
		// The above returns 0 if it cant find the attachment post id
		
		if ( $attachment_id == 0 ) {
			
			// Couldnt determine attachment post id. Try to get id from thumbnail
			$attachment_id = Patreon_Protect::getAttachmentIDfromThumbnailURL( $image );
	
			//No go. Image cant be found in media library. bail out.
			if ( $attachment_id == 0 OR !$attachment_id ) {
				wp_die('Image not found in media library');
			}
			
		}
		
		if ( current_user_can( 'manage_options' ) ) {
			Patreon_Protect::readAndServeImage( $image );	
		}			
		
		// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
		// It is independent of the plugin load order since it checks if it is defined.
		// It can be defined by any plugin until right before the_content filter is run.

		if ( apply_filters( 'ptrn/bypass_image_filtering', defined( 'PATREON_BYPASS_IMAGE_FILTERING' ) ) ) {
			Patreon_Protect::readAndServeImage( $image );
		}
	
		// Check if the image is protected:

		
		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );
		
		// If no specific level is found for this image, it is not set. Then set the level to 0.
		if ( !$patreon_level ) {
			$patreon_level = 0;
		}
		
		// If no level was set for image or it was 0, just serve the image.
		if ( $patreon_level == 0 ) {
			Patreon_Protect::readAndServeImage( $image );
		}
		
		// We are here, then we have a nonzero pledge level. Protect the image.
		
		$user_patronage = Patreon_Wordpress::getUserPatronage();

		$user = wp_get_current_user();
		
		$declined = Patreon_Wordpress::checkDeclinedPatronage( $user );
			
		if ($user_patronage == false 
			|| $user_patronage < ( $patreon_level * 100 )
			|| $declined
		) {
		
			Patreon_Protect::generateBlockedImagePlaceholder( $patreon_level, $attachment_id, $image );
			exit;
			
		}
		
		// At this point pledge checks are valid, and patron can see the image. Serve it:
		Patreon_Protect::readAndServeImage( $image, true );
	}
	public static function deleteCachedAttachmentPlaceholders( $attachment_id ) {
		
		// Iterate attachment and delete all cached placeholder images for all sizes
		
		$attachment_metadata = wp_get_attachment_metadata( $attachment_id, true );

		foreach ( $attachment_metadata['sizes'] as $key => $value ) {
			
			$cached_filename = $attachment_id . '-' . $key;
			if ( file_exists( PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename ) ) {
				wp_delete_file(PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
			}
			
		}
		
		// Original image
		
		wp_delete_file( PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $attachment_id );
		
	}
	public static function generateBlockedImagePlaceholder( $patreon_level, $attachment_id, $image, $refresh_cache = false ) {
		
		// Check if GDlib is installed
		
		if ( !( extension_loaded( 'gd' ) AND function_exists( 'gd_info' ) ) ) {
			
			// Not installed we have to serve a static image:
			header( 'Content-Type: image/png' );
			echo file_get_contents( PATREON_PLUGIN_ASSETS_DIR . '/img/patreon-300x300-locked-image-placeholder.png' );
			exit;
			
		}

		// The text to draw
		$title              = 'FOR $' . $patreon_level . '+ PATRONS ONLY';
		$unlock_button_text = 'UNLOCK IT NOW';
	
		$image_type = wp_check_filetype( $image );

		$image_ext = $image_type['ext'];
		$mime_type = $image_type['type'];
		
		$force_mime_type = 'image/png';
		
		// Get attachment metadata to check if this is a smaller version of full image:
		
		$attachment_metadata   = wp_get_attachment_metadata( $attachment_id, true );
		$full_version_filename = basename( $attachment_metadata['file'] );
		
		foreach ( $attachment_metadata['sizes'] as $key => $value ) {
			
			if ( $attachment_metadata['sizes'][$key]['file'] == basename( $image ) ) {
				
				// Matches this size
				$cached_filename    = $attachment_id . '-' . $key;
				$attachment_version = $key;
				$version_filename   = $attachment_metadata['sizes'][$key]['file'];
				
			}
			
		}
		if ( !isset( $cached_filename ) ) {
			
			// The file was not matched in lower sizes. Then treat it as the original image
			$cached_filename    = $attachment_id;
			$attachment_version = 'full';
			$version_filename   = basename( $image );
			
		}
		
		// first, check if cached image exists:
		
		if ( file_exists( PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/'. $cached_filename ) AND isset( $go ) ) {
			
			// Exists - serve the cached image:
		
			header( 'Content-Type: '.$mime_type );
			// Readfile to avoid higher memory usage. Can be modified to echo file_get_contents for small files in future
			echo file_get_contents( PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
			exit;
			
		}	
	
		$image_details = wp_get_attachment_image_src( $attachment_id, $attachment_version );

		$image_path = get_attached_file( $attachment_id );
		
		// If requested one is not the full image, replace the filename with the smaller version:
		
		if ( $attachment_version != 'full' ) {
			$image_path = str_replace( $full_version_filename, $version_filename, $image_path );
		}
		
		$width  = $image_details[1];
		$height = $image_details[2];
		
		// Because WP does thumbnails close to 1:1 ratio, we have to check whether what's requested is a thumbnail first. So that we can serve a closer-ratio image:

		// Get the thumbnail url to check against the url requested:
		$thumbnail = wp_get_attachment_thumb_url( $attachment_id );
		
		if ( $thumbnail == $image OR ( $width<=150 AND $height <= 150 ) ) {
			// Thumbnail is requested or a very small image. Override the image details with thumbnail's and double the size for better resolution in retina devices
			
			// If this is a very small image, it wont have a thumbnail. Check if WP created a thumbnail to use, if not, skip
			
			if ( isset( $attachment_metadata['sizes']['thumbnail'] ) ) {
			
				$image_path = str_replace( basename( $image_path ), '', $image_path ) . $attachment_metadata['sizes']['thumbnail']['file'];
				$width      = $attachment_metadata['sizes']['thumbnail']['width'];
				$height     = $attachment_metadata['sizes']['thumbnail']['height'];
				
			}
			
		}
		
		// For images that are very small, we wont render entire interface. So turn on some flags if the image is smaller than a certain size
		if ( $width <= 200 OR $height <= 150 ) {
			$hide_text = true;
		}
		
		// For images that are very small, (<150) hide the button too 
		if ( $height <= 150 ) {
			$hide_button = true;
		}
		
		if ( $mime_type == 'image/png' ) {
			$image = imagecreatefrompng( $image_path );
		}
		if ( $mime_type == 'image/gif' ) {
			$image = imagecreatefromgif( $image_path );
		}
		if ($mime_type =='image/jpeg') {
			$image = imagecreatefromjpeg( $image_path );
		}
		if ( $mime_type == 'image/bmp' ) {
			$image = imagecreatefrombmp( $image_path );
		}

		$size = array(
			'sm' => array( 
				'w' => intval( $width / 4 ),
				'h' => intval( $height / 4 )
			),
		   'md' => array(
				'w' => intval( $width / 2 ),
				'h' => intval( $height / 2 )
			)
		);

		$gaussian_blur_value = apply_filters( 'ptrn/image_locking_gaussian_blur_value', 999 );

		/* Scale by 25% and apply Gaussian blur */
		$sm = imagecreatetruecolor( $size['sm']['w'], $size['sm']['h'] );
		imagecopyresampled( $sm, $image, 0, 0, 0, 0, $size['sm']['w'], $size['sm']['h'], $width, $height );

		for ( $x=1; $x <=30; $x++ ){
			imagefilter( $sm, IMG_FILTER_GAUSSIAN_BLUR, $gaussian_blur_value );
		} 

		imagefilter( $sm, IMG_FILTER_SMOOTH,99 );
		imagefilter( $sm, IMG_FILTER_BRIGHTNESS, 10 );        

		/* Scale result by 200% and blur again */
		$md = imagecreatetruecolor( $size['md']['w'], $size['md']['h'] );
		imagecopyresampled( $md, $sm, 0, 0, 0, 0, $size['md']['w'], $size['md']['h'], $size['sm']['w'], $size['sm']['h'] );
		imagedestroy($sm);

		for ( $x=1; $x <=64; $x++ ) {
			imagefilter( $md, IMG_FILTER_GAUSSIAN_BLUR, $gaussian_blur_value );
		} 

		imagefilter( $md, IMG_FILTER_SMOOTH,99 );
		imagefilter( $md, IMG_FILTER_BRIGHTNESS, -50 );
	 
		/* Scale result back to original size */
		imagecopyresampled( $image, $md, 0, 0, 0, 0, $width, $height, $size['md']['w'], $size['md']['h'] );
		imagedestroy( $md );
		
		$font = apply_filters( 'ptrn/locked_image_interface_font', PATREON_PLUGIN_ASSETS_DIR . '/fonts/LibreFranklin-ExtraBold.ttf',$patreon_level, $attachment_id, $image );
		
		$white = imagecolorallocate( $image, 255, 255, 255 );
		$grey  = imagecolorallocate( $image, 192, 192, 192 );
		$black = imagecolorallocate( $image, 0, 0, 0 );	
		
		$lock_icon   = PATREON_PLUGIN_ASSETS_DIR . '/img/patreon-wp-image-lock-icon-2x.png';
		$lock_width  = 64;
		$lock_height = 80;

		$lock_icon = imagecreatefrompng( $lock_icon );
		
		// Use width or height depending on which is larger:
		$dimension_to_use_for_scaling = $width;
		
		if ( $width >= $height ) {
			$dimension_to_use_for_scaling = $height;
		}
		
		// Below filter allows modders and 3rd party devs to modify the interface size
		$dimension_to_use_for_scaling = apply_filters( 'ptrn/locked_image_interface_scaling_value', 176, $patreon_level, $attachment_id, $image, $width, $height );

		$target_lock_width  = apply_filters( 'ptrn/locked_image_interface_target_lock_height', ceil( ceil( $dimension_to_use_for_scaling / 5 ) * 90 / 100 ), $patreon_level, $attachment_id, $image, $width, $height );
		$target_lock_height = ceil( $target_lock_width * $lock_height / $lock_width );
		$res                = imagecreatetruecolor( $target_lock_width, $target_lock_height );
		
		imagealphablending( $res, false );
		imagesavealpha( $res, true );
		imagecopyresampled( $res, $lock_icon, 0, 0, 0, 0, $target_lock_width, $target_lock_height, $lock_width, $lock_height );
		
		$lock_icon   = apply_filters( 'ptrn/locked_image_interface_lock_icon', $res, $patreon_level, $attachment_id, $image, $width, $height );
		$lock_width  = $target_lock_width;
		$lock_height = $target_lock_height;
	
		// Copy over the lock icon with transparency:
		
		// Locate vertical center of image:

		$half_height = apply_filters( 'ptrn/locked_image_interface_vertical_center', ceil( $height / 2 ),$patreon_level, $attachment_id, $image, $width, $height );
		
		$place_at_y  = $half_height - $lock_height - 10;
		
		if ( !isset( $hide_text ) ) {
			
			// If the width is larger than the dimension set for scaling, then use width to fit the text, and use a percentage:

			$usable_width = $dimension_to_use_for_scaling;

			if ( $width >= $dimension_to_use_for_scaling ) {
				
				// It means that we have a larger space horizontally than vertically, and we can bump up font weight some more:
				$usable_width = $usable_width + ceil( ( $width - $usable_width ) * 22 / 100 );
			}
					
			$font_size = apply_filters( 'ptrn/locked_image_interface_vertical_center', 14, $patreon_level, $attachment_id, $image, $width, $height);
			
			// Override vertical placement of the icon since we have text:
			
			$place_at_y = $half_height - $lock_height - ceil( $font_size / 2 ) - 20;
			
		}
		
		if ( isset( $hide_button ) ) {
			$place_at_y = $half_height - ceil( $lock_height / 2 ) - 10;
		}

		imagecopy( $image, $lock_icon, ceil( $width / 2 ) - ceil( $lock_width / 2 ), $place_at_y, 0, 0, $lock_width, $lock_height );
		
		// Dont show text if too small
		if ( !isset( $hide_text ) ) {
		
			// Determine font dimension:
			
			// If the width is larger than the dimension set for scaling, then use width to fit the text, and use a percentage:

			$usable_width = $dimension_to_use_for_scaling;

			if ( $width >= $dimension_to_use_for_scaling ) {
				
				// It means that we have a larger space horizontally than vertically, and we can bump up font weight some more:
				$usable_width = $usable_width + ceil( ( $width - $usable_width ) * 20 / 100 );
				
			}
					
			$dimensions = imagettfbbox( $font_size, 0, $font, $title );
			$margin     = $font_size / 2;
			$text       = explode( "\n", wordwrap( $title, ceil( $font_size * 250 / 100 ) ) );

			//Centering y
			// $y = (imagesy($image) - (($dimensions[1] - $dimensions[7]) + $margin)*count($text)) / 2;
			$y = ceil( ( imagesy( $image ) / 2 ) - $font_size );

			$delta_y = -$margin;

			foreach ( $text as $line ) {
				
				$dimensions = imagettfbbox( $font_size, 0, $font, $line );
				$delta_y    =  $delta_y + ( $dimensions[1] - $dimensions[7]) + $margin;
				//centering x:
				$x = imagesx($image) / 2 - ( $dimensions[4] - $dimensions[6] ) / 2;

				imagettftext( $image, $font_size, 0, $x, $y + $delta_y , $white, $font, $line );
				
			}
			
		}

		// Arrange unlock button :
		if ( !isset( $hide_button ) ) {
			
			$rectangle_width = apply_filters( 'ptrn/locked_image_interface_button_width', 130, $patreon_level, $attachment_id, $image, $width, $height);
			$rectangle_height = $rectangle_width / 130 * 32;

			$x1_coord = ( $width - $rectangle_width ) / 2;
			
			$y1_coord = $half_height + ceil( $font_size / 2 ) + 25;
			
			$x2_coord = $width-( $width - $rectangle_width ) / 2;
			$y2_coord = $y1_coord + $rectangle_height;
			
			// Nudge up if it text is hidden

			if ( isset( $hide_text ) ) {
				$y1_coord = $half_height + 10;
				$y2_coord = $y1_coord + $rectangle_height;
			}
			
			$thickness = apply_filters( 'ptrn/locked_image_interface_button_border', 3, $patreon_level, $attachment_id, $image, $width, $height);

			imagesetthickness( $image, $thickness );

			imagerectangle( $image, $x1_coord, $y1_coord, $x2_coord, $y2_coord, $white );

			$button_font_size = apply_filters( 'ptrn/locked_image_interface_unlock_button_font_size', 9, $patreon_level, $attachment_id, $image, $width, $height );

			$dimensions  = imagettfbbox( $button_font_size, 0, $font, $unlock_button_text );

			$text_width  = $dimensions[2] - $dimensions[0];
			$text_height = $dimensions[7] - $dimensions[1];

			$x_coord = ( $width - $text_width ) / 2;

			$y_coord = $y1_coord + ( ( $rectangle_height / 2 ) - ( $text_height / 2 ) );

			imagettftext( $image, $button_font_size, 0, $x_coord, $y_coord, $white, $font, $unlock_button_text );
			
		}
		
		header( 'Content-Type: '.$force_mime_type );
		
		// Always send no cache headers since this is a gated image placeholder
	
		header( "Expires: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header( "Cache-Control: no-store, no-cache, must-revalidate" ); 
		header( "Cache-Control: post-check=0, pre-check=0", false ); 

		if ( $force_mime_type=='image/png' ) {
			imagepng( $image,PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
		}
		if ( $force_mime_type=='image/gif' ) {
			imagegif( $image,PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
		}
		if ( $force_mime_type=='image/jpeg' ) {
			imagejpeg( $image,PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
		}
		if ( $force_mime_type=='image/bmp' ) {
			imagebmp( $image, PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
		}
	
		// Readfile below for lower memory usage. Can be changed to echo file_get_contents for small images in future
		echo file_get_contents( PATREON_PLUGIN_LOCKED_IMAGE_CACHE_DIR . '/' . $cached_filename );
			
		imagedestroy( $image );
		
	}
	public static function addPatreonRewriteRules() {
			
		// File locking not enabled. Return
		if ( get_option( 'patreon-enable-file-locking', false ) == false ) {
			return;
		}
		
		// Check if htaccess exists, bail out if not
		
		if ( !file_exists( ABSPATH . '.htaccess' ) ) {
			return;
		}
				
		$htaccess = file_get_contents( ABSPATH . '.htaccess' );
		
		// First remove the rules.
		
		self::removePatreonRewriteRules();
		
		// Move on to adding the rules, this will refresh the rules as well
		
		$upload_locations = wp_upload_dir();

		// We want the base upload location so we can account for any changes to date based subfolders in case there are

		$upload_dir = substr( wp_make_link_relative( $upload_locations['baseurl'] ) , 1 );

		$append = PHP_EOL . "# BEGIN Patreon WordPress Image Protection
RewriteEngine On
RewriteBase /		
RewriteCond %{REQUEST_FILENAME} (\.png|\.jpg|\.gif|\.jpeg|\.bmp) [NC]
RewriteRule ^" . $upload_dir . "/(.*)$ index.php?patreon_action=serve_patron_only_image&patron_only_image=$1 [QSA,L]
# END Patreon WordPress".PHP_EOL;
		
    	file_put_contents( ABSPATH .'.htaccess', $htaccess . $append );
		
	}
	public static function removePatreonRewriteRules() {
		
		// Check if htaccess exists, bail out if not
		
		if ( !file_exists( ABSPATH . '.htaccess' ) ) {
			return;
		}		
		
		$htaccess = file_get_contents( ABSPATH . '.htaccess' );
		
		$start_marker = '# BEGIN Patreon WordPress Image Protection';
		$end_marker   = '# END Patreon WordPress';
		
		// Check if rules are in htaccess
		if ( strpos( $htaccess, $start_marker ) === false  ) {
			return;
		}
		
		$start = strpos( $htaccess, $start_marker );
		$end   = strpos( $htaccess, $end_marker );
		
		$snipped = preg_replace( '/' . PHP_EOL . $start_marker . '.+?' . $end_marker . PHP_EOL . '/is', '', $htaccess );
		
		file_put_contents( ABSPATH . '.htaccess', $snipped );
		
	}
	public function saveAttachmentLevel( $attachment_id = false ) {
		
		if ( !( is_admin() AND current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		$_REQUEST['patreon_attachment_patreon_level'] = preg_replace("/[^0-9.]/", '', $_REQUEST['patreon_attachment_patreon_level'] );
		
		if ( update_post_meta( $_REQUEST['patreon_attachment_id'], 'patreon_level',  $_REQUEST['patreon_attachment_patreon_level'] ) ) {
			$update_status = 'updated';
		}
		
		if ( $update_status == 'updated' ) {
			$message = 'Pledge level for the image was updated!';
		}
		else {
			$message = 'Pledge level for the image was updated! The value you posted may be same with the value already set!';
		}
		
		
		$args = array (
			'attachment_id' => $attachment_id,
			'patreon_level' => $_REQUEST['patreon_attachment_patreon_level'],
			'message' => $message,
		);
		
		echo self::make_image_lock_interface( $args	);
		
		// Delete all cached images for this attachment
		self::deleteCachedAttachmentPlaceholders( $_REQUEST['patreon_attachment_id'] );
		
		wp_die();		
		
	}
	public function makeAttachmentPledgeEditor( $attachment_id = false ) {

		if ( !( is_admin() AND current_user_can( 'manage_options' ) ) ) {
			echo 'Not in admin or without admin capabilities!';
			wp_die();
		}
	
		if ( isset( $_REQUEST['pw_image_source'] ) AND $_REQUEST['pw_image_source'] != '' ) {
			$attachment_url = $_REQUEST['pw_image_source'];
		}
		
		$message = '';
		
		if ( !$attachment_url OR $attachment_url == '' ) {
			// This is not a rewritten image request.
			$message = 'No attachment url provided. Cannot lock.';
			
		}
		// Get attachment from attachment url.
		
		$attachment_id = Patreon_Wordpress::get_attachment_id_from_url( $attachment_url );
		
		// The above returns 0 if it cant find the attachment post id
		
		if ( $attachment_id == 0 ) {
			
			// Couldnt determine attachment post id. Try to get id from thumbnail
			$attachment_id = Patreon_Protect::getAttachmentIDfromThumbnailURL( $attachment_url );
			
			if( $attachment_id == 0 ) {
				$message = 'Can not find attachment id.  Cannot lock.';
			}
			
		}
		
		// Check if image locking is enabled
		
		if ( !get_option( 'patreon-enable-file-locking', false ) ) {
			// Give a message if the image locking feature is not enabled
			$message = 'Image locking is not enabled in <a href="'. admin_url( 'admin.php?page=patreon-plugin' ) . '" target="_blank">settings</a>. Locking will not work';
		}

		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );
		
		$args = array (
			'attachment_id' => $attachment_id,
			'patreon_level' => $patreon_level,
			'message' => $message,
		);
		
		echo self::make_image_lock_interface( $args	);
		
		wp_die();
		
	}
	public function make_image_lock_interface( $args = array() ) {
		
		$interface = '';
		
		$interface .=  '<div class="patreon_image_lock_modal_content">';
		$interface .=  '<span class="patreon_image_lock_modal_close">&times;</span>';

		$interface .=  ' <form id="patreon_attachment_patreon_level_form" action="/wp-admin/admin-ajax.php" method="post">';
		$interface .=  '<h1 class="patreon_image_locking_interface_heading">Lock Image</h1>';
		$interface .=  '<div class="patreon_image_locking_interface_level">';
		$interface .=  '<span class="patreon_image_locking_interface_input_prefix">$<input id="patreon_attachment_patreon_level" type="text" name="patreon_attachment_patreon_level" value="' . $args['patreon_level'] . '" / ></span>';
		$interface .=  '</div>';
		$interface .=  '<div class="patreon_image_locking_interface_info">';
		$interface .=  'Minimum Patreon pledge amount required to see this image';
		$interface .=  '</div>';
		$interface .=  '<input type="hidden" name="patreon_attachment_id" value="' . $args['attachment_id'] . '" />';
		$interface .=  '<div class="patreon-image-locking-update-button"><input type="submit" class="button button-primary button-large" value=" Update " /></div>';
		$interface .=  '<div id="patreon_image_locking_interface_message">';
		$interface .=  $args['message'];
		$interface .=  '</div>';
		$interface .=  '<input type="hidden" name="action" value="patreon_save_attachment_patreon_level" />';
		$interface .=  '</form>';
		$interface .=  '</div>';
		
		return $interface;
		
	}
	public function ParseContentForProtectedImages( $content ) {
		
		global $post;
		
		if ( !is_singular() OR is_admin() ) {
			// Currently we only support single post/page/custom posts
			return $content;			
		}

		// This function parses the content to check for protected images and tag them with a css class for click-catching to send the user to Patreon flow
		
		// Below define can be defined in any plugin to bypass core locking function and use a custom one from plugin
		// It is independent of the plugin load order since it checks if it is defined.
		// It can be defined by any plugin until right before the_content filter is run.

		if ( apply_filters( 'ptrn/bypass_image_filtering', defined( 'PATREON_BYPASS_IMAGE_FILTERING' ) ) ) {
			return $content;
		}
		
		// Check if user is an admin, if so dont tag images 
		
		if ( current_user_can( 'manage_options' ) ) {
			return $content;
		}
		
		// Here we are either in an unlocked post, or we are in a post that was locked but now displayed due to Patron having a sufficient pledge level. Now we have to parse and tag images.
		
		// Use regex to get image src's and process them to save memory and cpu. This tolerates more any content which has broken html. A comparison of regex vs dom for this process is referenced below in Autoptimizer plugin's blog
		// https://blog.futtta.be/2014/05/01/php-html-parsing-performance-shootout-regex-vs-dom/
		
		// Get image srcs - we have to check pledge level and tag every image
		// We want entire image tag:
		
		$start = microtime( true );
		
		preg_match_all( '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $matches );
		// preg_match_all("/(<img [^>]*>)/",$content,$matches);
		$images = $matches[1];
	
		$time_elapsed_secs = microtime( true ) - $start;
		
		foreach ( $images as $key => $value ) {

			$attachment_id = Patreon_Protect::get_attachment_id_from_url( $images[$key] );
			
			// The above returns 0 if it cant find the attachment post id
			
			if ( $attachment_id == 0 ) {
				
				// Couldnt determine attachment post id. Try to get id from thumbnail
				$attachment_id = Patreon_Protect::getAttachmentIDfromThumbnailURL( $images[$key] );
				
				if( $attachment_id == 0 ) {
					//No go. skip processing this image
					continue;
				}
				
			}
			
			$lock_the_image = self::checkPatronPledgeForImage( $attachment_id );
			
			if( $lock_the_image > 0 ) {
				
				// Valid pledge not found, not admin, image is locked. Add the class:
				$replace = str_replace( 'class="','class="patreon-locked-image ', $matches[0][$key] );
		
				// Make universal flow link with pledge level:
				$flow_link = Patreon_Frontend::patreonMakeCacheableImageFlowLink( $attachment_id, $post->ID );
		
				// Encode the link enough to make sure no url sensitive chars will remain
				$flow_link = base64_encode( $flow_link );

				// Place the link in an attribute to image tag 				
				$replace = str_replace( 'class="','data-patreon-flow-url="' . $flow_link . '" class="', $replace );
				
				// Put back to content:				
				$content = str_replace( $matches[0][$key], $replace, $content );
				
			}
			
		}
		
		return $content; 
		
	}
	public static function addCustomCSSinAdmin() {
		
		echo "<style>
				#patreon-image-toolbar {
					background-image: url( '".PATREON_PLUGIN_ASSETS."/img/patreon-image-lock-button-for-toolbar-bg.png' );
					background-repeat: no-repeat;
				}
			  </style>";
			
	}
	public static function addImageToolbar() {
		
		// Adds the hidden floating image toolbar

		$screen = get_current_screen();
		if ( $screen->parent_base != 'edit' ) {
			return;
		}
		
		?>
		
		<div id="patreon-image-toolbar">
			<div id="patreon-image-lock-icon"><img src="<?php echo PATREON_PLUGIN_ASSETS . '/img/patreon-image-lock-icon.png'; ?>" /></div>
		</div>

		<?php
	
	}
	public static function checkPatronPledgeForImage( $attachment_id, $user = false ) {
	
		// Checks a user's pledges against an image pledge level
		if ( !$user ) {
			$user = wp_get_current_user();
		}

		// If user is an admin, show the image
		if ( user_can( $user, 'manage_options' ) ) {
			return 0;
		}		
		
		$declined_patron = Patreon_Wordpress::checkDeclinedPatronage($user);

		$patron_pledge = Patreon_Wordpress::getUserPatronage();
		
		$patreon_level = get_post_meta( $attachment_id, 'patreon_level', true );
		
		// If no specific level is found for this image, it is not set. Then set the level to 0.
		if ( !$patreon_level ) {
			return 0;
		}

		if ( $patron_pledge == false 
			|| $patron_pledge == 0 
			|| $patron_pledge < ( $patreon_level * 100 )
			|| $declined_patron	
		) {
			return $patreon_level;
		}
		
		return 0;
		
	}
	// Taken and modified from https://wordpress.stackexchange.com/questions/6645/turn-a-url-into-an-attachment-post-id/7094#7094
	// wp attachment_url_to_postid fails for intermediate size images at the time of this commit - this is a replacement
	public static function get_attachment_id_from_url( $url ) {

        $dir = wp_upload_dir();

        // baseurl never has a trailing slash
        if ( false === strpos( $url, $dir['baseurl'] . '/' ) ) {
            // URL points to a place outside of upload directory
            return 0;
        }

        $file  = basename( $url );
        $query = array(
            'post_type'  => 'attachment',
            'fields'     => 'ids',
            'meta_query' => array(
                array(
                    'key'     => '_wp_attached_file',
                    'value'   => $file,
                    'compare' => 'LIKE',
                ),
            )
        );

        // query attachments
        $ids = get_posts( $query );

        if ( ! empty( $ids ) ) {

            foreach ( $ids as $id ) {

                // first entry of returned array is the URL
                if ( $url === array_shift( wp_get_attachment_image_src( $id, 'full' ) ) )
                    return $id;
            }
        }

        $query['meta_query'][0]['key'] = '_wp_attachment_metadata';

        // query attachments again
        $ids = get_posts( $query );

        if ( empty( $ids) )
            return 0;

        foreach ( $ids as $id ) {

            $meta = wp_get_attachment_metadata( $id );

            foreach ( $meta['sizes'] as $size => $values ) {

                if ( $values['file'] === $file && $url === array_shift( wp_get_attachment_image_src( $id, $size ) ) )
                    return $id;
            }
        }

        return 0;
    }
	
}
