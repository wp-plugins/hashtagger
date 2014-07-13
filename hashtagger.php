<?php
/*
Plugin Name: hashtagger
Plugin URI: http://smartware.cc/wp-hashtagger
Description: Tag your posts by using #hashtags
Version: 1.0
Author: smartware.cc
Author URI: http://smartware.cc
License: GPL2
*/

/*  Copyright 2014  smartware.cc  (email : sw@smartware.cc)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function swcc_htg_generate_tags( $postid ) {
  wp_set_post_tags( $postid, swcc_htg_get_hashtags_from_content( get_post_field('post_content', $postid) ), false );
}

function swcc_htg_get_hashtags_from_content( $content ) {
  preg_match_all( '/(^|[^a-z0-9_"#&])#([a-z0-9_\-]+)(?=[^<>]*(?:<|$))/i', $content, $matches );
  return implode( ', ', $matches[2] );
}

function swcc_htg_content( $content ) {
  return str_replace( '##', '#', preg_replace( '/(^|[^a-z0-9_"#&])#([a-z0-9_\-]+)(?=[^<>]*(?:<|$))/i', '\1<a href="' . get_site_url() . '/' . get_option( 'tag_base' ) . '/\2">#\2</a>', $content ) );
}

add_action( 'save_post', 'swcc_htg_generate_tags', 9999 );
add_filter( 'the_content', 'swcc_htg_content', 9999 );

?>