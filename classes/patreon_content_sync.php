<?php


if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Houses the admin pointers class and added admin pointers

class Patreon_Content_Sync {

	public function __construct() {
	}
	
	public function import_posts_from_patreon( $args = array() ) {
		
		// This function performs a full import of posts from Patreon using cron
		
		// Check if the site uses api v2. If not, abort.
		
		$api_version    = get_option( 'patreon-installation-api-version', '1' );

		if ( $api_version != '2' ) {
			// Cancel any ongoing imports.
			delete_option( 'patreon-post-import-in-progress' );
			return 'api_version_error';
		}
		
		// Bail out if this is not a manual request and a manual import was triggered in last minute
		
		$last_manual_import_triggered = get_option( 'patreon-manual-import-batch-last-triggered', 0 );
		
		if ( ( $last_manual_import_triggered + 60 ) > time() AND !isset( $args['manual_import'] ) ) {
			return 'manual_import_exists_within_last_60_seconds';
		}
		
		// Set a flag to use in checking whether any post import happened at all.
		$at_least_one_post_imported = false;
		
		// Set the flag for detecting end of post import
		$end_of_post_import = false;
		
		// Check if an import is going on
		
		$post_import_in_progress = get_option( 'patreon-post-import-in-progress', false );
		
		if ( !$post_import_in_progress ) {
			// No ongoing import. Return
			return 'no_ongoing_post_import';
		}
		
		$creator_access_token = get_option( 'patreon-creators-access-token', false );
		$client_id 			  = get_option( 'patreon-client-id', false );

		if ( $creator_access_token AND $client_id ) {
			
			// Create new api object

			$api_client = new Patreon_API( $creator_access_token );
			
			// Get if there is a saved cursor
			
			$cursor = get_option( 'patreon-post-import-next-cursor', null );
			
			$posts = $api_client->get_posts( false, 5, $cursor );

				      
			if ( isset( $posts['errors'][0]['code'] ) AND $posts['errors'][0]['code'] == 3 AND $posts['errors'][0]['source']['parameter'] == 'page[cursor]' ) {
				// Cursor expired. Delete the cursor for next run and return
				delete_option( 'patreon-post-import-next-cursor' );
				return 'expired_or_lost_cursor_deleted';
			}
			
			if ( !isset( $posts['data'] ) ) {
				// Couldnt get posts. Bail out
				return 'couldnt_get_posts';
			}		
			
			if ( isset( $posts['meta']['pagination']['cursors']['next'] ) ) {
				update_option( 'patreon-post-import-next-cursor', $posts['meta']['pagination']['cursors']['next'] );
			}
			
			// If we have a saved cursor, the return is legitimate, but there is no more cursor then we are at the last page - iteration over. Mark it:
			
			if ( isset( $cursor ) AND !isset( $posts['meta']['pagination']['cursors']['next'] ) ) {
				
				delete_option( 'patreon-post-import-in-progress' );
				delete_option( 'patreon-post-import-next-cursor' );
				$end_of_post_import = true;
			}
			
			foreach ( $posts['data'] as $key => $value ) {
				
				$patreon_post = $api_client->get_post( $posts['data'][$key]['id'] );
				
				if ( !isset( $patreon_post['data']['id'] ) OR $patreon_post['data']['id'] == '' ) {
					// Couldn't get this post. Skip
					continue;
				}
				
				$this->add_update_patreon_post( $patreon_post );
				$at_least_one_post_imported = true;
			}
			
		}
		
		if ( $at_least_one_post_imported OR $end_of_post_import) {
			
			// Post import ended
			if ( $end_of_post_import ) {
				return 'post_import_ended';
			}
			
			// Imported at least one post
			return 'imported_posts';
		}
		
		return 'did_not_import_any_post';
		
	}
	
	public function add_update_patreon_post( $patreon_post ) { 
		
		// Check if a matching WP post exists
		
		$matching_post_id = false;
		
		global $wpdb;
		
		$matching_posts = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = 'patreon-post-id' AND meta_value = '" . $patreon_post['data']['id'] . "' ", ARRAY_A );
						
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
			$result = $this->add_new_patreon_post( $patreon_post );
		}
		else {
			
			// Update if existing posts are set to be updated
			if ( get_option( 'patreon-update-posts', 'no' ) == 'yes' ) {
				$result = $this->update_patreon_post( $matching_post_id, $patreon_post );
			}
			else {
				// Updating posts disabled. Just return
				return false;
			}
			
		}
		
		if ( is_wp_error( $result ) OR $result == 0 ) {
			// Flopped - return false
			return false;
		}
		
		// Success
		return true;
		
	}
	
	public function add_new_patreon_post( $patreon_post ) {
		
		$post_type         = get_option( 'patreon-sync-post-type', 'post' );
		$post_category     = get_option( 'patreon-sync-post-category', 'category' );
		$post_term_id      = get_option( 'patreon-sync-post-term', '1' );
		$post_author       = get_option( 'patreon-post-author-for-synced-posts', 1 );
		$patron_only_post  = false;
		
		if ( $patreon_post['data']['attributes']['is_paid'] ) {
			$patron_only_post = true;
		}
		else {
			
			// Not a pay per post - check tier level or patron only status
			// For now do this in else, when api returns tiers replace with proper logic
			
			if ( $patreon_post['data']['attributes']['is_public'] ) {
				$patron_only_post = false;
			}
			else {
				$patron_only_post = true;
			}

		}
		
		$post_date = date( 'Y-m-d H:i:s', time() );
		
		if ( get_option( 'patreon-override-synced-post-publish-date', 'no' ) == 'yes' ) {
			
			$utc_timezone = new DateTimeZone( "UTC" );
			$datetime = new DateTime( $patreon_post['data']['attributes']['published_at'], $utc_timezone );
			$post_date =  $datetime->format( 'Y-m-d H:i:s' );			
			
		}
		
		$post_status = 'publish';

		// Decide post status - publish or pending
		
		if ( $patron_only_post AND get_option( 'patreon-auto-publish-patron-only-posts', 'yes' ) == 'no' ) {
			$post_status = 'pending';
		}
		
		if ( !$patron_only_post AND get_option( 'patreon-auto-publish-public-posts', 'yes' ) == 'no' ) {
			$post_status = 'pending';
		}
		
		$post                  = array();
		$post['post_title']    = $patreon_post['data']['attributes']['title'];
		$post['post_content']  = $patreon_post['data']['attributes']['content'];
		$post['post_status']   = $post_status;
		$post['post_author']   = $post_author;
		$post['post_type']     = $post_type;
		$post['post_date']     = $post_date;
		
		// Parse and handle the images inside the post:
		
		global $Patreon_Wordpress;
		global $wpdb;
		
		$images = $Patreon_Wordpress->get_images_info_from_content( $post['post_content'] );
		
		if ( $images ) {
			
			$image_replacement = $this->check_replace_patreon_images_with_local_images( $post['post_content'], $images );

			$images               = $image_replacement['images'];
			$post['post_content'] = $image_replacement['post_content'];
			
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
		
		// Now get the attachment ids we added to the post from earlier filenames
		
		if ( $images ) {
			
			foreach ( $images as $key => $value ) {
		
				$inserted_attachment = $wpdb->get_results( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '" . $images[$key]['name'] ."'" ); 

				$inserted_attachment_id = $inserted_attachment[0]->ID;
				
				// Set Patreon post as parent of this attachment
				
				wp_update_post( array(
					'ID'          => $inserted_attachment_id,
					'post_parent' => $inserted_post_id
					)
				);
				
				// Set image to patron only since its in a patron only post - for those who use image locking featured
						
				// If post is not public - set the contained images to be patron only
				
				if ( $patreon_post['data']['attributes']['is_paid'] ) {
					// Pay per post set to patron only
					// Add only if not exists
					add_post_meta( $inserted_attachment_id, 'patreon-level', 1, true );
				}
				else {
					
					// Not a pay per post - check tier level or patron only status
					// For now do this in else, when api returns tiers replace with proper logic
					
					if ( $patreon_post['data']['attributes']['is_public'] ) {
						add_post_meta( $inserted_attachment_id, 'patreon-level', 0, true );
					}
					else {
						add_post_meta( $inserted_attachment_id, 'patreon-level', 1, true );
					}
				
				}
				
			}
		}	
		
		// Set featured image
		$this->set_featured_image_for_patreon_post( $inserted_post_id );

		// If post is not public - currently there is no $ value or tier returned by /posts endpoint, so just set it to $1 locally

		if ( $patron_only_post ) {
			// Pay per post set to patron only - update only if not exists
			add_post_meta( $inserted_post_id, 'patreon-level', 1, true );
		}
		else {
			add_post_meta( $inserted_post_id, 'patreon-level', 0, true );
		}
		
		// Set category/taxonomy
		
		$post_term      = get_term( $post_term_id, $post_category );
		$post_term_slug = $post_term->slug;

		wp_set_object_terms( $inserted_post_id, $post_term_slug, $post_category );
				
		update_post_meta( $inserted_post_id, 'patreon-post-id', $patreon_post['data']['id'] );
		update_post_meta( $inserted_post_id, 'patreon-post-url', $patreon_post['data']['attributes']['url'] );
		
	}
	
	public function update_patreon_post( $post_id, $patreon_post ) {
		
		$post                  = array();
		$post['ID']            = $post_id;
		$post['post_title']    = $patreon_post['data']['attributes']['title'];
		$post['post_content']  = $patreon_post['data']['attributes']['content'];
		
		// Parse and handle the images inside the post:
		
		global $Patreon_Wordpress;
		global $wpdb;
		
		$images = $Patreon_Wordpress->get_images_info_from_content( $post['post_content'] );

		if ( $images ) {
			
			$image_replacement = $this->check_replace_patreon_images_with_local_images( $post['post_content'], $images );
			
			$images               = $image_replacement['images'];
			$post['post_content'] = $image_replacement['post_content'];
			
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
		
		// Now get the attachment ids we added to the post from earlier filenames
		
		if ( $images ) {

			foreach ( $images as $key => $value ) {
		
				$inserted_attachment = $wpdb->get_results( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '" . $images[$key]['name'] ."'" ); 

				$inserted_attachment_id = $inserted_attachment[0]->ID;
				
				// Set Patreon post as parent of this attachment
				
				wp_update_post( array(
					'ID'          => $inserted_attachment_id,
					'post_parent' => $updated_post_id
					)
				);
				
				// Set image to patron only since its in a patron only post - for those who use image locking featured
						
				// If post is not public - set the contained images to be patron only
				
				if ( $patreon_post['data']['attributes']['is_paid'] ) {
					// Pay per post set to patron only
					update_post_meta( $inserted_attachment_id, 'patreon-level', 1 );
				}
				else {
					
					// Not a pay per post - check tier level or patron only status
					// For now do this in else, when api returns tiers replace with proper logic
					
					if ( $patreon_post['data']['attributes']['is_public'] ) {
						update_post_meta( $inserted_attachment_id, 'patreon-level', 0 );
					}
					else {
						update_post_meta( $inserted_attachment_id, 'patreon-level', 1 );
					}
				
				}
				
			}
		}

		// Set featured image for post
		$this->set_featured_image_for_patreon_post( $updated_post_id );

		// Repeating this as a bloc here since the logic for individual post vs logic for included images may change at any point
		
		// If post is not public - currently there is no $ value or tier returned by /posts endpoint, so just set it to $1 locally
		
		if ( $patreon_post['data']['attributes']['is_paid'] ) {
			// Pay per post set to patron only
			update_post_meta( $updated_post_id, 'patreon-level', 1 );
		}
		else {
			
			// Not a pay per post - check tier level or patron only status
			// For now do this in else, when api returns tiers replace with proper logic
			
			if ( $patreon_post['data']['attributes']['is_public'] ) {
				update_post_meta( $updated_post_id, 'patreon-level', 0 );
			}
			else {
				update_post_meta( $updated_post_id, 'patreon-level', 1 );
			}
		
		}
	
		update_post_meta( $updated_post_id, 'patreon-post-id', $patreon_post['data']['id'] );
		update_post_meta( $updated_post_id, 'patreon-post-url', $patreon_post['data']['attributes']['url'] );
		
	}
	
	public function delete_patreon_post( $post_id ) {
		
		// Deletes a Patreon linked post
		
		wp_delete_post( $post_id, true );
		
	}		
	
	public function check_replace_patreon_images_with_local_images( $post_content, $images ) {
		
		global $Patreon_Wordpress;
		
		$featured_image_set = false;		
		
		// There are inserted images in the post. Process.
		foreach ( $images as $key => $value ) {
			
			// Check if the image is from Patreon.
			$match_patreon_url = array();
						
			preg_match( '/https:\/\/.*\.patreonusercontent\.com/i', $images[$key]['url'], $match_patreon_url );
			
			if ( count( $match_patreon_url ) > 0 ) {
				
				// This image is at Patreon.
				
				// Check if image exists in media library:
				
				// Get the image hash
				
				$image_hash = $Patreon_Wordpress->get_remote_image_hash( $images[$key]['url'] );
				
				// Set the filename to local translated one with hash
				$images[$key]['filename'] = $image_hash . '.' . $images[$key]['extension'];
				$images[$key]['name'] = $image_hash;
				
				$attachment_id = $Patreon_Wordpress->get_file_id_from_media_library( $image_hash );

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
		
		return array( 
			'images' => $images,
			'post_content' => $post_content,
		);
	}
		
	public function process_post_embeds( $post_content, $patreon_post ) {
		
		if ( isset( $patreon_post['data']['attributes']['embed_data']['provider'] ) ) {
			
			if ( $patreon_post['data']['attributes']['embed_data']['provider'] == 'Patreon' ) {
				
				// Check if it is a link embed with an image pulled for it:
				
				$headers = array_change_key_case ( get_headers ( $patreon_post['data']['attributes']['embed_data']['html'] , 1 ) );

				if (substr ($headers ['content-type'], 0, 5) == 'image') {

					// Check if there's an url
					
					if ( isset( $patreon_post['data']['attributes']['embed_data']['url'] ) AND $patreon_post['data']['attributes']['embed_data']['url'] != '' ) {

						// Get the image if not present in local library:
						
						$path = parse_url( $patreon_post['data']['attributes']['embed_data']['html'], PHP_URL_PATH);
						
						$image_hash = $Patreon_Wordpress->get_remote_image_hash( $patreon_post['data']['attributes']['embed_data']['html'] );
						
						$filename_remote = basename($path['path']);
						$filename_remote_path = pathinfo( $filename_remote );
						
						// Set the filename to local translated one with hash
						$filename = $image_hash . '.' . $filename_remote_path['extension'];
						
						// This image checking and acquisition code can be bundled into a wrapper function later
						
						global $Patreon_Wordpress;
						
						$attachment_id = $Patreon_Wordpress->get_file_id_from_media_library( $image_hash );

						if ( !$attachment_id ) {
							
							// Not in media library. Download, insert.
							$attachment_id = $Patreon_Wordpress->download_insert_media( $patreon_post['data']['attributes']['embed_data']['html'], $filename );
							
						}

						// If attachment was successfully inserted, put it into the post:
							
						if ( $attachment_id ) {

							// Was able to acquire an attachment id for this Patreon image. Replace its url instead of the original:
							
							$attachment_info = wp_get_attachment_image_src( $attachment_id, 'full' );
							
							if ( $attachment_info ) {

								$post_content =  '<a href="' . $patreon_post['data']['attributes']['embed_data']['url'] . '" target="_blank"><img src="' . $attachment_info[0] . '" /></a>' . $post_content;
							}
							
						}
							
					}
					
				}
				
				
			}
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
	public function get_matching_post_by_patreon_post_id( $patreon_post_id ) {
		
		// Gets a WP post by a matching Patreon post id from its Patreon post id meta
		global $wpdb;
		
		$post = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'patreon-post-id' AND  meta_value = '" . $patreon_post_id . "' LIMIT 1", ARRAY_A);
		
		if ( isset( $post[0]['post_id'] ) ) {
			return $post[0]['post_id'];
		}
		
		return false;
		
	}
	public function set_featured_image_for_patreon_post( $post_id ) {
		
		// Gets gets an imported post, browses inserted images and sets the first as the featured image.
		
		if ( get_option( 'patreon-set-featured-image', 'no' ) == 'no' ) {
			return;			
		}

		$attachments = get_attached_media( 'image', $post_id );
		
		if ( !is_array( $attachments ) OR count( $attachments ) == 0 ) {
			return;
		}
		
		// Get the first attachment:
		
		$reversed_attachments = array_reverse( $attachments );
		
		$first_attachment = array_pop( $reversed_attachments );
		
		// Set the first attachment as featured image
		set_post_thumbnail( $post_id, $first_attachment->ID );
		
		return;		
	}
	
}