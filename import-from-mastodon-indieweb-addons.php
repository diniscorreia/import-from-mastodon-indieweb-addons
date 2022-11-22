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

/**
 * Adds Mastodon URL to syndication links
 */
add_filter( 'syn_add_links', function( $urls, $object_id ) {
    if ( ! empty( $get_post_meta( $object_id, 'mf2_like-of' ) ) || ! empty( $get_post_meta( $object_id, 'mf2_repost-of' ) ) ) {
        // Favourites and boosts shouldn't get syndication links.
        return;
    }

    $mastodon_url = get_post_meta( $object_id, '_import_from_mastodon_url', true );
  
    if ( ! empty( $mastodon_url ) ) {
        $urls[] = $mastodon_url;
    }
  
    return $urls;
}, 1, 2 );