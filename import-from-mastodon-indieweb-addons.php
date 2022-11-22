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
