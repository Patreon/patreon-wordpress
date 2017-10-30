<?php

define('WP_USE_THEMES', false);

require_once('../../../wp-load.php');

$admin_user = false;
if( current_user_can('editor') || current_user_can('administrator') ) {
	$admin_user = true;
}

$wp_upload_dir = wp_upload_dir();

$basedir = $wp_upload_dir['basedir'].'/patreon_protect';
$baseurl = $wp_upload_dir['baseurl'].'/patreon_protect';

$requested_file = '';
if(isset($_GET['file'])) {
	$requested_file = $_GET['file'];
}

$file =  rtrim($basedir,'/').'/'.str_replace('..', '', $requested_file);
$file_url = rtrim($baseurl,'/').'/'.str_replace('..', '', $requested_file);


if (!$basedir || !is_file($file) || !class_exists('Patreon_Wordpress') ) {
	status_header(404);
	die('404 &#8212; File not found.');
}

$wp_attachment = attachment_url_to_postid($file_url);

if($wp_attachment == false) {
	$wp_attachment = Patreon_Protect::getAttachmentIDfromThumbnailURL($file_url);
}

if($wp_attachment) {
	$attachment_meta = wp_get_attachment_metadata($wp_attachment);
} else {
	status_header(404);
	die('404 &#8212; File not found.');
}

$patreon_level = get_post_field('patreon_level', $wp_attachment);

if(empty($patreon_level)) {
	$patreon_level = get_option('patreon-protect-default-image-patreon-level', 0);
}

$user_patronage = Patreon_Wordpress::getUserPatronage();

if( (float)$patreon_level != 0 && $admin_user == false && ($user_patronage == false || $user_patronage < ($patreon_level*100)) ) {

	// $protected_image_placeholder = get_option('patreon-paywall-blocked-img-url', false);

	Patreon_Protect::generateBlockedImagePlaceholder($patreon_level);
	
	exit;
}

$mimetype = Patreon_Protect::getMimeType($file);
	
header( 'Content-Type: ' . $mimetype );
if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
	header( 'Content-Length: ' . filesize( $file ) );

$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
$etag = '"' . md5( $last_modified ) . '"';
header( "Last-Modified: $last_modified GMT" );
header( 'ETag: ' . $etag );
header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

// Support for Conditional GET
$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
	$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;
$modified_timestamp = strtotime($last_modified);

if ( ( $client_last_modified && $client_etag )
	? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
	: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
	) {
	status_header( 304 );
	exit;
}

readfile( $file );