<?php
/*
Plugin Name: hashtagger
Plugin URI: http://smartware.cc/wp-hashtagger
Description: Tag your posts by using #hashtags
Version: 2.1
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

// set version
define( 'SWCC_HASHTAGGER_VERSION', '2.1' );

// set regex
define( 'SWCC_HASHTAGGER_REGEX', '/(^|[\s!\.:;\?(>])#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u' );
define( 'SWCC_HASHTAGGER_REGEX_NOTAG', '/(^|[\s!\.:;\?(>])\+#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u' );
define( 'SWCC_HASHTAGGER_REGEX_USERS', '/(^|[\s!\.:;\?(>])\@([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u' );

// this function extracts the hashtags from content and adds them as tags to the post
function swcc_htg_generate_tags( $postid ) {
  wp_set_post_tags( $postid, swcc_htg_get_hashtags_from_content( get_post_field('post_content', $postid) ), false );
}

// this function returns an array of hashtags from a given content - used by swcc_htg_generate_tags
function swcc_htg_get_hashtags_from_content( $content ) {
  preg_match_all( SWCC_HASHTAGGER_REGEX, $content, $matches );
  return implode( ', ', $matches[2] );
}

// replace hashtags with links when displaying content
function swcc_htg_content( $content ) {
  $content = str_replace( '##', '#', preg_replace_callback( SWCC_HASHTAGGER_REGEX_NOTAG, 'swcc_make_link_notag', preg_replace_callback( SWCC_HASHTAGGER_REGEX, 'swcc_make_link_tag', $content ) ) );
  if ( get_option( 'swcc_htg_usernames', 'NONE' ) != 'NONE' ) {
    $content = str_replace( '@@', '@', preg_replace_callback( SWCC_HASHTAGGER_REGEX_USERS, 'swcc_make_link_usernames', $content ) );
  }
  return $content;
}

// callback functions for preg_replace_callback use in swcc_htg_content
function swcc_make_link_tag( $match ) {
  return swcc_make_link( $match, true );
}
function swcc_make_link_notag( $match ) {
  return swcc_make_link( $match, false );
}
function swcc_make_link_usernames( $match ) {
  return swcc_make_link_users( $match, get_option( 'swcc_htg_usernames', 'PROFILE' ) );
}

// function to generate tag link
function swcc_make_link( $match, $mktag ) {
  $tag = get_term_by('name', $match[2], 'post_tag');
  if ( !$tag ) {
    $content = $match[0];
  } else {
    $slug = $tag->slug;
    if ( $mktag ) {
      $css = get_option( 'swcc_htg_cssclass' );
    } else {
      $css = get_option( 'swcc_htg_cssclass_notag' );
    }
    if ( $css != '' ) {
      $css = ' class="' . $css . '"';
    }
    $content = $match[1] . '<a' . $css . ' href="' . get_tag_link($tag->term_id) . '">#' . $match[2] . '</a>';
  }
  return $content;
}

// function to generate user link
function swcc_make_link_users( $match, $link ) {
  $user = false;
  $username = $match[2];
  // get by nickname or by login name
  if ( get_option( 'swcc_htg_usernamesnick', '0' ) == 0 ) {
    // get by login name - default
    $user = get_user_by( 'login', $username );
  } else {
    // get by nickname
    $users = get_users( array( 'meta_key' => 'nickname', 'meta_value' => $username ) );
    if ( count( $users ) == 1 ) {
      // should result in one user
      $user = $users[0];
    }
  }
  if ( !$user ) {
    $content = $match[0];
  } else {
    if ( $link != 'PROFILE' ) {
      $linkto = $user->user_url;
    } else {
      $linkto = '';
    }
    if ( $linkto == '' ) {
      $linkto = get_author_posts_url( $user->ID );
    }
    if ( $link == 'WEBSITE-NEW' ) {
      $target = ' target="_blank"';
    } else {
      $target = '';
    }
    $css = get_option( 'swcc_htg_usernamescssclass' );
    if ( $css != '' ) {
      $css = ' class="' . $css . '"';
    }
    $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>@' . $match[2] . '</a>';
  }
  return $content;
}

// adds the options page to admin menu
function swcc_htg_admin_menu() {
  add_options_page( 'hashtagger ' . __( 'Settings' ), '#hashtagger', 'manage_options', 'swcc_htg_settings', 'swcc_htg_admin_page' );
}

// creates the options page
function swcc_htg_admin_page() {
  ?>
  <div class="wrap swcchtgadmin">
    <?php screen_icon(); ?>
    <h2>hashtagger <?php _e( 'Settings' ); ?></h2>           
    <form method="post" action="options.php">
      <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
          <div id="post-body-content">
            <h3>#hashtag <?php _e( 'Permalinks' ); ?></h3>
            <p>#hashtags <?php _e( 'currently link to', 'hashtagger'); ?> <code style="white-space: nowrap"><?php echo swcc_htg_tag_base_url() . '[hashtag]'; ?></code>.</p>
            <p><?php printf( __( 'The <b>Tag base</b> for the Archive URL can be changed on %s page', 'hashtagger' ), '<a href="'. admin_url( 'options-permalink.php' ) .'">' . __( 'Permalink Settings' ) . '</a>' ); ?>.</p>
            <?php
            settings_fields( 'swcc_htg' );   
            do_settings_sections( 'swcc_htg_settings' );
            submit_button(); 
            ?>
          </div>
          <div id="postbox-container-1" class="postbox-container">
            <?php do_meta_boxes( 'swcc_htg', 'side', true ); ?>
          </div>
        </div>
      </div>    
    </form>
  </div>
  <?php
}

// funtion to get the base directory for tags
function swcc_htg_tag_base_url() {
  $url = get_site_url() . '/';
  $tagbase = get_option( 'tag_base' );
  if ( $tagbase != '' ) {
    $tagbase .= '/';
  }
  $url .= $tagbase;
  return trailingslashit($url);
}

// this function can be used in theme
// does all the hashtagger stuff on a string
function do_hashtagger( $content ) {
  return swcc_htg_content( $content );
}

// init the admin section
function swcc_htg_admin_init() {        
  register_setting( 'swcc_htg', 'swcc_htg_cssclass', 'swcc_htg_admin_cssclass_validate' );
  register_setting( 'swcc_htg', 'swcc_htg_cssclass_notag', 'swcc_htg_admin_cssclass_validate' );
  add_settings_section( 'hashtagger-settings-css', '#hashtag ' . __( 'Appearance' ), 'swcc_htg_admin_settings_css', 'swcc_htg_settings' );
  add_settings_field( 'swcc_htg_settings_cssclass', __( 'CSS class name(s) for #hashtags', 'hashtagger' ), 'swcc_htg_admin_cssclass', 'swcc_htg_settings', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cssclass' ) );
  add_settings_field( 'swcc_htg_settings_cssclass_notag', __( 'CSS class name(s) for +#hashtag links', 'hashtagger' ), 'swcc_htg_admin_cssclass_notag', 'swcc_htg_settings', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cssclass_notag' ) );
  
  register_setting( 'swcc_htg', 'swcc_htg_usernames', 'swcc_htg_usernames_validate' );
  register_setting( 'swcc_htg', 'swcc_htg_usernamescssclass', 'swcc_htg_admin_cssclass_validate' );
  register_setting( 'swcc_htg', 'swcc_htg_usernamesnick', 'swcc_htg_admin_nick_validate' );
  add_settings_section( 'hashtagger-settings-usernames', __( '@username links', 'hashtagger' ), 'swcc_htg_admin_settings_usernames', 'swcc_htg_settings' );
  add_settings_field( 'swcc_htg_settings_usernames', __( 'Link @usernames', 'hashtagger' ), 'swcc_htg_admin_usernames', 'swcc_htg_settings', 'hashtagger-settings-usernames', array( 'label_for' => 'swcc_htg_usernames' ) );
  add_settings_field( 'swcc_htg_settings_usernamesnick', __( '@nicknames', 'hashtagger' ), 'swcc_htg_admin_usernamesnick', 'swcc_htg_settings', 'hashtagger-settings-usernames', array( 'label_for' => 'swcc_htg_usernamesnick' ) );
  add_settings_field( 'swcc_htg_settings_usernamescssclass', __( 'CSS class name(s) for @usernames', 'hashtagger' ), 'swcc_htg_admin_usernamescssclass', 'swcc_htg_settings', 'hashtagger-settings-usernames', array( 'label_for' => 'swcc_htg_usernamescssclass' ) );
  
  //register_setting( 'swcc_htg', 'swcc_htg_cssclass_users', 'swcc_htg_admin_cssclass_validate' );
  
  add_meta_box( 'swcc_htg_meta_box_like', __( 'Like this Plugin?', 'hashtagger_general' ), 'swcc_htg_add_meta_box_like', 'swcc_htg', 'side' );
  add_meta_box( 'swcc_htg_meta_box_help', __( 'Need help?', 'hashtagger_general' ), 'swcc_htg_add_meta_box_help', 'swcc_htg', 'side' );
  
  wp_enqueue_script( 'postbox' );
}

// sttings group : appearance
function swcc_htg_admin_settings_css() {
  echo '<p>' . __( 'Specify CSS class(es) that should be added to the #hashtag links', 'hashtagger' ) . '.</p>';
}

// sttings group : user names
function swcc_htg_admin_settings_usernames() {
  echo '<p>' . __( 'Handling of @usernames', 'hashtagger' ) . '.</p>';
}

// handle the settings field : css class
function swcc_htg_admin_cssclass() {
  echo '<input class="regular-text" type="text" name="swcc_htg_cssclass" id="swcc_htg_cssclass" value="' . get_option( 'swcc_htg_cssclass' ) . '" />';
}

// handle the settings field : css class notag
function swcc_htg_admin_cssclass_notag() {
  echo '<input class="regular-text" type="text" name="swcc_htg_cssclass_notag" id="swcc_htg_cssclass_notag" value="' . get_option( 'swcc_htg_cssclass_notag' ) . '" />';
}

// handle the settings field : user names
function swcc_htg_admin_usernames() {
  $curvalue = get_option( 'swcc_htg_usernames', 'NONE' );
  echo '<select name="swcc_htg_usernames" id="swcc_htg_usernames">';
  echo '<option value="NONE"' . ( ( $curvalue == 'NONE' ) ? ' selected="selected"' : '' ) . '>' . __('Ignore @usernames', 'hashtagger' ) . '</option>';
  echo '<option value="PROFILE"' . ( ( $curvalue == 'PROFILE' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users profile page', 'hashtagger' ) . '</option>';
  echo '<option value="WEBSITE-SAME"' . ( ( $curvalue == 'WEBSITE-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in same browser tab', 'hashtagger') . '</option>';
  echo '<option value="WEBSITE-NEW"' . ( ( $curvalue == 'WEBSITE-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in new browser tab', 'hashtagger') . '</option>';
  echo '</select>';
}

// handle the settings field : use nicknames instead of usernames
function swcc_htg_admin_usernamesnick() {
  echo '<input type="checkbox" name="swcc_htg_usernamesnick" id="swcc_htg_usernamesnick" value="1"' . ( ( get_option( 'swcc_htg_usernamesnick', '0' ) == 1 ) ?  'checked="checked"' : '' ) . ' />' . __( 'Use @nicknames instead of @usernames', 'hashtagger' ) . '<br /><div class="dashicons dashicons-shield"></div><strong>' . __( 'Highly recommended to enhance WordPress security!', 'hashtagger') . ' <a href="http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/">' . __( 'Read more', 'hashtagger' ) . '</a>';
}

// handle the settings field : css class for usernames
function swcc_htg_admin_usernamescssclass() {
  echo '<input class="regular-text" type="text" name="swcc_htg_usernamescssclass" id="swcc_htg_usernamescssclass" value="' . get_option( 'swcc_htg_usernamescssclass' ) . '" />';
}

// validate input : css class + css class notag
function swcc_htg_admin_cssclass_validate( $input ) {
  $classes = explode(' ', $input);
  $css = '';
  foreach( $classes as $class ) {
    $css = $css . sanitize_html_class( $class ) . ' ';
  }
  return rtrim( $css );
}

// validate input : link user names - dummy
function swcc_htg_usernames_validate( $input ) {
  return $input;
}

// validate input : use nicknames - dummy
function swcc_htg_admin_nick_validate( $input ) {
  return $input;
}

// addd text domains
function swcc_htg_add_text_domains() {  
  load_plugin_textdomain( 'hashtagger_general', false, basename( dirname( __FILE__ ) ) . '/languages' );
  load_plugin_textdomain( 'hashtagger', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

// add like meta box 
function swcc_htg_add_meta_box_like() {
  ?>
  <ul>
    <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/extend/plugins/hashtagger/"><?php _e( 'Please rate the plugin', 'hashtagger_general' ); ?></a></li>
    <li><div class="dashicons dashicons-admin-home"></div>&nbsp;&nbsp;<a href="http://smartware.cc/wp-hashtagger/"><?php _e( 'Plugin homepage', 'hashtagger_general'); ?></a></li>
    <li><div class="dashicons dashicons-admin-home"></div>&nbsp;&nbsp;<a href="http://smartware.cc/"><?php _e( 'Author homepage', 'hashtagger_general' );?></a></li>
    <li><div class="dashicons dashicons-googleplus"></div>&nbsp;&nbsp;<a href="https://plus.google.com/+SmartwareCc"><?php _e( 'Authors Google+ Page', 'hashtagger_general' ); ?></a></li>
    <li><div class="dashicons dashicons-facebook-alt"></div>&nbsp;&nbsp;<a href="https://www.facebook.com/smartware.cc"><?php _e( 'Authors facebook Page', 'hashtagger_general' ); ?></a></li>
  </ul>
  <?php
}

// add help meta box 
function swcc_htg_add_meta_box_help() {
  ?>
  <ul>
    <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/plugins/hashtagger/faq/"><?php _e( 'Take a look at the FAQ section', 'hashtagger_general' ); ?></a></li>
    <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/support/plugin/hashtagger"><?php _e( 'Take a look at the Support section', 'hashtagger_general'); ?></a></li>
    <li><div class="dashicons dashicons-admin-comments"></div>&nbsp;&nbsp;<a href="http://smartware.cc/contact/"><?php _e( 'Feel free to contact the Author', 'hashtagger_general' ); ?></a></li>
  </ul>
  <?php
}

// add css
function swcc_htg_admin_head() {
  ?>
    <style type="text/css">
      .swcchtgadmin #post-body-content h3 {
        font-size: 1.3em !important;
        margin: 1em 0px !important;
        padding: 0 !important;
      }
    </style>
  <?php
}

// add jquery for meta boxes
function swcc_htg_add_footer_script() {
  ?>
  <script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles(pagenow); });</script>
  <?php
}

// *** main ***
add_action( 'init', 'swcc_htg_add_text_domains' );

add_action( 'save_post', 'swcc_htg_generate_tags', 9999 );
add_filter( 'the_content', 'swcc_htg_content', 9999 );

add_action( 'admin_menu', 'swcc_htg_admin_menu' );
add_action( 'admin_init', 'swcc_htg_admin_init' );

add_action( 'admin_head', 'swcc_htg_admin_head' );
add_action( 'admin_footer', 'swcc_htg_add_footer_script' );
?>