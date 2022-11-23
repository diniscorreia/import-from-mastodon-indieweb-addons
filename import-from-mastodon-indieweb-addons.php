<?php
/**
 * Plugin Name: Import from Mastodon IndieWeb Addions
 * Description: Add IndieWeb improvements to Import From Mastodon WordPress plugin.
 * Author:      Dinis Correia
 * Author URI:  https://diniscorreia.com/
 * License:     GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Version:     0.1
 */

/**
 * Build cite array
 */
if ( ! function_exists( 'build_toot_cite' ) ) {
	function build_toot_cite( $status ) {
		$cite            = array();
		$cite['name']    = 'Toot';
		$cite['url']     = $status->url;
		$cite['publication'] = wp_parse_url( $status->url, PHP_URL_HOST );
		$cite['published'] = $status->created_at;

		$author          = array();
		$author['name']  = $status->account->display_name;
		$author['url']   = $status->account->url;
		$author['photo'] = esc_url( $status->account->avatar_static );
		$author          = array_filter( $author );
		$author['type']  = 'card';
		$cite['author']  = jf2_to_mf2( $author );

		$build = array();
		foreach ( $cite as $key => $value ) {
			$build[ $key ] = is_array( $value ) ? $value : array( $value );
		}
		$cite = array( 'properties' => $build );
		$cite['type'] = array( 'h-cite' );

		return $cite;
	}
}

/**
 * Sets post kinds and store microformats data
 */
add_action( 'import_from_mastodon_after_import', function( $post_id, $status ) {
    // We need the Post Kinds plugi for this
    if ( ! function_exists( 'set_post_kind' ) ) {
        return;
    }  
    
    // Microblog posts/notes don't really need titles.
    // Necessary to plug to some hook for allowin to
    // save a post with no title and content.
    $post_data = array(
        'ID'           => $post_id,
        'post_title'   => ''
    );
    add_filter( 'wp_insert_post_empty_content', '__return_false' );
    wp_update_post( $post_data );
    remove_filter( 'wp_insert_post_empty_content', '__return_false' );

    // Add Mastodon URL to syndication links only to
    // toots and replies, boosts and faves shouldn't have
    // syndication links.
    if ( empty( $status->favourited ) && empty( $status->reblog ) ) {
        $urls[] = $status->url;
        update_post_meta( $post_id, 'mf2_syndication', $urls );
    }
    
    // Set default post kind for toots
    set_post_kind( $post_id, 'note' );
  
    if ( ! empty( $status->favourited ) ) {
        // Set "like" post kind for favourites
        set_post_kind( $post_id, 'like' );

        // Save microformats data
        update_post_meta( $post_id, 'mf2_like-of', build_toot_cite( $status ) );
    } elseif ( isset( $status->reblog->url ) && isset( $status->reblog->account->username ) ) {
        // Set "repost" post kind for boosts
        set_post_kind( $post_id, 'repost' );

        // Save microformats data
        update_post_meta( $post_id, 'mf2_repost-of', build_toot_cite( $status->reblog ) );
    } elseif ( isset( $status->in_reply_to_id ) ) {
        // Set "reply" post kind for... well, replies
        set_post_kind( $post_id, 'reply' );
        
        // Fetch parent toot to get some context
        $parent = wp_remote_get(
            esc_url_raw( 'https://' . wp_parse_url( $status->account->url, PHP_URL_HOST ) . '/api/v1/statuses/' . $status->in_reply_to_id ),
            array(
                'headers' => array( 'Accept' => 'application/json' ),
                'timeout' => 11,
            )
        );
  
        if ( is_wp_error( $parent ) ) {
            error_log( '[Import From Mastodon] Failed to get parent: ' . $parent->get_error_message() );
        } else {
            $parent = wp_remote_retrieve_body( $parent );
            $parent = json_decode( $parent );
            
            // If we are replying to our own toot, delete the post.
            // Replies to our own content should be backfed into the
            // site as comments on the original post (eg: using Brid.gy)
            if ( $status->account->url === $parent->account->url ) {
                wp_delete_post( $post_id, true );
                return;
            }
            
            // Save microformats data
            update_post_meta( $post_id, 'mf2_in-reply-to', build_toot_cite( $parent ) );
      }
    }
}, 1, 2 );

/**
 * Removes attachments from favourites and boosts
 */
add_action( 'import_from_mastodon_after_attachments', function( $post_id, $status ) {
    if ( ! empty( $status->favourited ) || ! empty( $status->reblog ) ) {
        delete_post_thumbnail( $post_id );

        $post_media = get_attached_media( 'image', $post_id );

        if ( $post_media ) {
            foreach ( $post_media as $media ) {
                wp_delete_attachment( $post_id, true );
            }
        }
    }
}, 1, 2 );

/**
 * Cleans up post content
 */
add_filter( 'import_from_mastodon_post_content', function( $content, $status ) {
    if ( ! empty( $status->favourited ) || ( isset( $status->reblog->url ) && isset( $status->reblog->account->username ) ) ) {
        // Opionated choice, but for favourites and boost we don't want
        // to store a copy of the content, just a likn to it.
        $content = '';
    } elseif ( isset( $status->in_reply_to_id ) ) {
        // For replies, context about the parent tweak will be stored in
        // the post meta, so no need to duplicate it in the content.
        $content = trim(
            wp_kses(
                $status->content,
                array(
                    'a'  => array(
                        'href' => array(),
                    ),
                    'br' => array(),
                    'p'  => array(),
                )
            )
        );
  
        $content = preg_replace('/^(<p><a href="\S+">@)[a-zA-Z0-9_]+?(<\/a> )/', '<p>', $content);
        $content = trim( $content );
    }
  
    return $content;
}, 1, 2 );
