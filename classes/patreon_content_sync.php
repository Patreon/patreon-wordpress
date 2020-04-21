<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Houses the admin pointers class and added admin pointers

class Patreon_Content_Sync {

	public function __construct() {
		
		if ( get_option( 'patreon-sync-posts', false ) ) {
			
			add_filter( 'cron_schedules', array( &$this, 'add_patreon_cron_schedules' ) );
			
			// Schedule an action if it's not already scheduled
			if ( !wp_next_scheduled( 'patreon_five_minute_action' ) ) {
				wp_schedule_event( time(), 'patreon_five_minute_cron_schedule', 'patreon_five_minute_action' );
			}
		
			add_action( 'patreon_five_minute_action', array( &$this, 'patreon_five_minute_cron_job' ) );
			// add_action( 'wp_head', array( &$this, 'get_do' ) );
		}
		
	}
	
	// Adds Patreon cron schedule if needed
	
	public function add_patreon_cron_schedules( $schedules ) {
		
		// If post sync is on and cron is not added, add cron schedule
		
		$schedules['patreon_five_minute_cron_schedule'] = array(
			'interval' => 300, // 5 min
			'display'  => __( 'Patreon cron - every five minutes' ),
		);
		
		return $schedules;
		
	}
	
	public function patreon_five_minute_cron_job() {
		
		// Check if post sync is on just in case if the cron job somehow persisted despite sync being disabled
		
		if ( get_option( 'patreon-sync-posts', false ) ) {
			$this->import_posts_from_patreon();
		}

	}
	
	public function import_posts_from_patreon() {
		
		// This function performs a full import of posts from Patreon using cron
		
		// Check if an import is going on
		
		$post_import_in_progress = get_option( 'patreon-post-import-in-progress', false );
		
		if ( !$post_import_in_progress ) {
			// No ongoing import. Return
			return;
		}
			
		$creator_access_token = get_option( 'patreon-creators-access-token', false );
		$client_id 			  = get_option( 'patreon-client-id', false );

		if ( $creator_access_token AND $client_id ) {
			
			// Create new api object

			$api_client = new Patreon_API( $creator_access_token );
			
			// Get if there is a saved cursor
			
			$cursor = get_option( 'patreon-post-import-next-cursor', null );
			
			$posts = $api_client->get_posts( false, 5, $cursor );

			if ( isset( $posts['data']['errors'][0]['code'] ) AND $posts['data']['errors'][0]['code'] == 3 AND $posts['data']['errors'][0]['source']['parameter'][''] == 'page[cursor]' ) {
				// Cursor expired. Delete the cursor for next run and return
				delete_option( 'patreon-post-import-next-cursor' );
				return;				
			}
			
			if ( !isset( $posts['data'] ) ) {
				// Couldnt get posts. Bail out
				return;				
			}		
			
			if ( isset( $posts['meta']['pagination']['cursors']['next'] ) ) {
				update_option( 'patreon-post-import-next-cursor', $posts['meta']['pagination']['cursors']['next'] );
			}
			
			// If we have a saved cursor, the return is legitimate, but there is no more cursor then we are at the last page - iteration over. Mark it:
			
			if ( isset( $cursor ) AND !isset( $posts['meta']['pagination']['cursors']['next'] ) ) {
				
				delete_option( 'patreon-post-import-in-progress' );
				delete_option( 'patreon-post-import-next-cursor' );
				
			}
			
			foreach ( $posts['data'] as $key => $value ) {
				
				$patreon_post = $api_client->get_post( $posts['data'][$key]['id'] );
				
				if ( !isset( $patreon_post['data']['id'] ) OR $patreon_post['data']['id'] == '' ) {
					// Couldn't get this post. Skip
					continue;
				}
				
				// Check if a matching WP post exists
				
				$matching_post_id = false;
				
				global $wpdb;
				
				$matching_posts = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = 'patreon-post-id' AND meta_value = '" . $posts['data'][$key]['id'] . "' ", ARRAY_A );
								
				if ( count( $matching_posts ) > 0 ) {
					
					// Matching post found - just get the first one
					$matching_post_id = $matching_posts[0]['post_id'];				
					
				}
				
				// If no matching posts were found from query, try to find from title
			
				if ( count( $matching_posts ) == 0 ) {
					
					// no matching posts. Try checking from the title.
					$matching_post = get_page_by_title( $patreon_post['data']['attributes']['title'], OBJECT, 'post' );
					
					if ( isset( $matching_post ) ) {
						
						// A post matching from title was found.						
						$matching_post_id = $matching_post->ID;
						
					}
					
				}
		
				if ( !$matching_post_id ) {
					$this->add_new_patreon_post( $patreon_post );
				}
				else {
					
					// Update if existing posts are set to be updated 
					
					if ( get_option( 'patreon-update-posts', 'no' ) == 'yes' ) {
						$this->update_patreon_post( $matching_post_id, $patreon_post );
					}
					
					
				}
			
			}
			
		}
		
	}
	
	public function add_new_patreon_post( $patreon_post ) {
		
		$post_category = get_option( 'patreon-sync-post-term', '1' );
		$post_author = get_option( 'patreon-post-author-for-synced-posts', 1 );
		
		$post                  = array();
		$post['post_title']    = $patreon_post['data']['attributes']['title'];
		$post['post_content']  = $patreon_post['data']['attributes']['content'];
		$post['post_status']   = 'publish';
		$post['post_author']   = $post_author;
		$post['post_category'] = array( $post_category );
		
		// Parse and handle the images inside the post:
		
		global $Patreon_Wordpress;
		
		$images = $Patreon_Wordpress->get_images_info_from_content( $post['post_content'] );
		
		if ( $images ) {
			$post['post_content'] = $this->check_replace_patreon_images_with_local_images( $post['post_content'], $images );
		}

		if ( isset( $patreon_post['data']['attributes']['embed_data']['url'] ) ) {
			
			// Process embeds:
			
			$post['post_content'] = $this->process_post_embeds( $post['post_content'], $patreon_post );
			
			// Temporarily remove wp post filters to allow iframes to be accepted into post 
			
			remove_filter('content_save_pre', 'wp_filter_post_kses');
			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		}
		
		$inserted_post_id = wp_insert_post( $post );
		
		if ( isset( $patreon_post['data']['attributes']['embed_data']['url'] ) ) {
			
			// Re add post filters

			add_filter('content_save_pre', 'wp_filter_post_kses');
			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
			
		}
					
		if ( is_wp_error( $inserted_post_id ) ) {
			// Handle error_get_last
			return;
		}

		// Post is not public - currently there is no $ value or tier returned by /posts endpoint, so just set it to $1 locally
		if ( isset( $patreon_post['data']['attributes']['is_public'] ) AND !$patreon_post['data']['attributes']['is_public'] ){
			update_post_meta( $inserted_post_id, 'patreon-level', 1 );
		}		
		
		update_post_meta( $inserted_post_id, 'patreon-post-id', $patreon_post['data']['id'] );
		update_post_meta( $inserted_post_id, 'patreon-post-url', $patreon_post['data']['attributes']['url'] );
		
	}
	public function get_do() {
		
		// Debug function. Unused.
		
		if ( !isset( $_REQUEST['key'] ) ) {
			return;
		}
	
		$creator_access_token = get_option( 'patreon-creators-access-token', false );
		
		$api_client = new Patreon_API( $creator_access_token );
		
		// Get if there is a saved cursor
		
		$cursor = get_option( 'patreon-post-import-next-cursor', null );
		
		$post = $api_client->get_post( 36223386 );

		// $this->add_new_patreon_post( $post );
		$this->update_patreon_post( 367, $post );
		wp_die();
		
	}
	
	public function update_patreon_post( $post_id, $patreon_post ) {
		
		$post                  = array();
		$post['ID']            = $post_id;
		$post['post_title']    = $patreon_post['data']['attributes']['title'];
		$post['post_content']  = $patreon_post['data']['attributes']['content'];
		
		// Parse and handle the images inside the post:
		
		global $Patreon_Wordpress;
		
		$images = $Patreon_Wordpress->get_images_info_from_content( $post['post_content'] );
		
		if ( $images ) {
			$post['post_content'] = $this->check_replace_patreon_images_with_local_images( $post['post_content'], $images );
		}
		
		if ( isset( $patreon_post['data']['attributes']['embed_data']['url'] ) ) {
			
			// Process embeds:
			
			$post['post_content'] = $this->process_post_embeds( $post['post_content'], $patreon_post );
						
			// Temporarily remove wp post filters to allow iframes to be accepted into post 
			
			remove_filter('content_save_pre', 'wp_filter_post_kses');
			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		}
		
		$updated_post_id = wp_update_post( $post );
		
		if ( isset( $patreon_post['data']['attributes']['embed_data']['url'] ) ) {
			
			// Re add post filters

			add_filter('content_save_pre', 'wp_filter_post_kses');
			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
			
		}			
		
		if ( is_wp_error( $updated_post_id ) OR $updated_post_id == 0 ) {
			// Handle error_get_last
			return;
		}
		
		// Post is not public - currently there is no $ value or tier returned by /posts endpoint, so just set it to $1 locally
		if ( isset( $post['data']['attributes']['is_public'] ) AND !$post['data']['attributes']['is_public'] ){
			update_post_meta( $updated_post_id, 'patreon-level', 1 );
		}
		
		update_post_meta( $updated_post_id, 'patreon-post-id', $patreon_post['data']['id'] );
		update_post_meta( $updated_post_id, 'patreon-post-url', $patreon_post['data']['attributes']['url'] );
		
	}
	
	public function check_replace_patreon_images_with_local_images( $post_content, $images ) {
		
		global $Patreon_Wordpress;
	
		// There are inserted images in the post. Process.
		foreach ( $images as $key => $value ) {
			
			// Check if the image is from Patreon.
			$match_patreon_url = array();
						
			preg_match( '/https:\/\/.*\.patreonusercontent\.com/i', $images[$key]['url'], $match_patreon_url );
			
			if ( count( $match_patreon_url ) > 0 ) {
				
				// This image is at Patreon.
				
				// Check if image exists in media library:

				$attachment_id = $Patreon_Wordpress->get_file_id_from_media_library( $images[$key]['filename'] );

				if ( !$attachment_id ) {
					
					// Not in media library. Download, insert.
					$attachment_id = $Patreon_Wordpress->download_insert_media( $images[$key]['url'], $images[$key]['filename'] );
					
				}
				
				// If attachment was successfully inserted, put it into the post:
					
				if ( $attachment_id ) {
					
					// Was able to acquire an attachment id for this Patreon image. Replace its url instead of the original:
					
					$attachment_info = wp_get_attachment_image_src( $attachment_id, 'full' );
					
					if ( $attachment_info ) {
						
						// Got a url for local attachment. Replace into the src of Patreon image:

						$post_content = str_replace( $images[$key]['url'], $attachment_info[0], $post_content );

					}
					
				}
				
			}
			
		}
		
		return $post_content;
		
	}
	
	public function process_post_embeds( $post_content, $patreon_post ) {
		
		if ( isset( $patreon_post['data']['attributes']['embed_data']['provider'] ) ) {
			
			if ( $patreon_post['data']['attributes']['embed_data']['provider'] == 'YouTube' ) {
				
				// Get Youtube embed info for WP
				
				$embed_info = wp_remote_get( 'http://www.youtube.com/oembed?url=' . urlencode( $patreon_post['data']['attributes']['embed_data']['url'] ) . '&format=json' );
			
				
				if ( !is_wp_error( $embed_info ) ) {
					
					if ( isset( $embed_info['body'] ) ) {
						
						$response = json_decode( $embed_info['body'], true );
						
						if ( isset( $response['html'] ) AND strlen( $response['html'] ) > 0 ) {
							$post_content = $response['html'] . $post_content;
						}
						
					}
					
				}				
				
			}
			if ( $patreon_post['data']['attributes']['embed_data']['provider'] == 'Vimeo' ) {
				
				// Get Youtube embed info for WP
				
				$embed_info = wp_remote_get( 'https://vimeo.com/api/oembed.json?url=' . urlencode( $patreon_post['data']['attributes']['embed_data']['url'] ) );
			
				if ( !is_wp_error( $embed_info ) ) {
					
					if ( isset( $embed_info['body'] ) ) {
						
						$response = json_decode( $embed_info['body'], true );
						
						if ( isset( $response['html'] ) AND strlen( $response['html'] ) > 0 ) {
							$post_content = $response['html'] . $post_content;
						}
						
					}
					
				}				
				
			}
			
		}
		
		return $post_content;
	
	}
	
}