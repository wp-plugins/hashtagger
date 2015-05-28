<?php
/*
Plugin Name: hashtagger
Plugin URI: http://smartware.cc/wp-hashtagger
Description: Tag your posts by using #hashtags
Version: 3.2
Author: smartware.cc
Author URI: http://smartware.cc
License: GPL2
*/

/*  Copyright smartware.cc (email : sw@smartware.cc)

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

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Hashtagger {
  public $plugin_name;
  public $version;
  public $settings;
  protected $regex_general;
  protected $regex_notag;
  protected $regex_users;
  protected $admin;
  
	public function __construct() {
		$this->plugin_name = 'hashtagger';
		$this->version = '3.1';
    $this->get_settings();
    $this->init();
    $this->init_admin();
	} 
  
  private function init() {
    $this->regex_general = '/(^|[\s!\.:;\?(>])#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
    $this->regex_notag = '/(^|[\s!\.:;\?(>])\+#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
    $this->regex_users = '/(^|[\s!\.:;\?(>])\@([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
    
    add_action( 'init', array( $this, 'add_text_domains' ) );

    add_action( 'save_post', array( $this, 'generate_tags' ), 9999 );
    
    // *** For Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/ (since v 3.2)
    //     had to use filter usp_new_post insetad of action usp_insert_after because tags are created AFTER usp_insert_after
    add_filter( 'usp_new_post', array( $this, 'process_content_for_user_submitted_posts' ), 9999 );
    
    // *** For Barley - Inline Editing Plugin for WordPress (since v 3.2)
    //     had to override their save function...
    add_action( 'wp_ajax_barley_update_post',  array( $this, 'process_content_for_barely' ), 0 );
	
    
    if ( ! is_admin() ) {
      add_filter( 'the_content', array( $this, 'process_content' ), 9999 );
      
      if ( $this->settings['sectiontype_title'] ) {
        add_filter( 'the_title', array( $this, 'process_title' ), 9999 );
      }
      
      if ( $this->settings['sectiontype_excerpt'] ) {
        add_filter( 'the_excerpt', array( $this, 'process_excerpt' ), 9999 );
      }
    }
    
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) ); 
    
  }
  
  private function init_admin() {
    if ( is_admin() ) {
      $this->admin = new Hashtagger_Admin( $this );
    }
  }
  
  // addd text domains
  function add_text_domains() {  
    load_plugin_textdomain( 'hashtagger_general', false, basename( dirname( __FILE__ ) ) . '/languages' );
    load_plugin_textdomain( 'hashtagger', false, basename( dirname( __FILE__ ) ) . '/languages' );
  }
  
  // get all settings
  private function get_settings() {
    $this->settings = array();
    $this->settings['posttype_page'] = ( get_option( 'swcc_htg_posttype_page', '1' ) == 1 ) ?  true : false;
    $this->settings['posttype_custom'] = ( get_option( 'swcc_htg_posttype_custom', '1' ) == 1 ) ?  true : false;
    $this->settings['sectiontype_title'] = ( get_option( 'swcc_htg_sectiontype_title', '0' ) == 1 ) ?  true : false;
    $this->settings['sectiontype_excerpt'] = ( get_option( 'swcc_htg_sectiontype_excerpt', '0' ) == 1 ) ?  true : false;
    $this->settings['advanced_nodelete'] = ( get_option( 'swcc_htg_advanced_nodelete', '0' ) == 0 ) ?  false : true;
    $this->settings['usernames'] = get_option( 'swcc_htg_usernames', 'NONE' );
    if ( ! in_array( $this->settings['usernames'], array( 'NONE', 'PROFILE', 'WEBSITE-SAME', 'WEBSITE-NEW' ) ) ) {
      $this->settings['usernames'] = 'NONE';
    }
    $this->settings['usernamesnick'] = ( get_option( 'swcc_htg_usernamesnick', '0' ) == 0 ) ?  false : true;
    $this->settings['cssclass'] = get_option( 'swcc_htg_cssclass', '' ); 
    $this->settings['cssclass_notag'] = get_option( 'swcc_htg_cssclass_notag', '' );
    $this->settings['usernamescssclass'] = get_option( 'swcc_htg_usernamescssclass', '' );
    $tagbase = get_option( 'tag_base' );
    if ( $tagbase != '' ) {
      $tagbase .= '/';
    }
    $this->settings['tagbase'] = $tagbase;
  }
  
  // this function extracts the hashtags from content and adds them as tags to the post
  // since v 3.0 option to not delete unused tags
  function generate_tags( $postid ) {
    $post_type = get_post_type( $postid );
    $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
    if ( ( 'post' == $post_type ) || ( 'page' == $post_type && $this->settings['posttype_page'] ) || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
      $content = get_post_field('post_content', $postid);
      if ( $this->settings['sectiontype_title'] ) {
        $content = $content . ' ' . get_post_field('post_title', $postid);
      }
      if ( $this->settings['sectiontype_excerpt'] ) {
        $content = $content . ' ' . get_post_field('post_excerpt', $postid);
      }
      wp_set_post_tags( $postid, $this->get_hashtags_from_content( $content ), $this->settings['advanced_nodelete'] );
    }
  }
  
  // this function returns an array of hashtags from a given content - used by generate_tags()
  function get_hashtags_from_content( $content ) {
    preg_match_all( $this->regex_general, $content, $matches );
    return implode( ', ', $matches[2] );
  }
  
  // general function to process content
  function work( $content ) { 
    $content = str_replace( '##', '#', preg_replace_callback( $this->regex_notag, array( $this, 'make_link_notag' ), preg_replace_callback( $this->regex_general, array( $this, 'make_link_tag' ), $content ) ) );
    if ( $this->settings['usernames'] != 'NONE' ) {
      $content = str_replace( '@@', '@', preg_replace_callback( $this->regex_users, array( $this, 'make_link_usernames' ), $content ) );
    }
    return $content;
  }
  
  // replace hashtags with links when displaying content
  // since v 3.0 post type depending
  function process_content( $content ) {
    global $post;
    $post_type = get_post_type();
    $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
    if ( ( 'post' == $post_type ) || ( 'page' == $post_type && $this->settings['posttype_page'] ) || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
      $content = $this->work( $content );
    }
    return $content;
  }
  
  // function to process title (since v 3.0) - calls process_content
  function process_title( $title, $id = null ) {
    return $this->process_content( $title );
  }
  
  // function to process excerpt (since v 3.0) - calls process_content
  function process_excerpt( $excerpt ) {
    return $this->process_content( $excerpt );
  }
  
  // callback functions for preg_replace_callback used in content()
  function make_link_tag( $match ) {
    return $this->make_link( $match, true );
  }
  function make_link_notag( $match ) {
    return $this->make_link( $match, false );
  }
  function make_link_usernames( $match ) {
    return $this->make_link_users( $match, $this->settings['usernames'] );
  }
  
  // function to generate tag link
  private function make_link( $match, $mktag ) {
    $tag = get_term_by('name', $match[2], 'post_tag');
    if ( !$tag ) {
      $content = $match[0];
    } else {
      $slug = $tag->slug;
      if ( $mktag ) {
        $css = $this->settings['cssclass'];
      } else {
        $css = $this->settings['cssclass_notag'];
      }
      if ( $css != '' ) {
        $css = ' class="' . $css . '"';
      }
      $content = $match[1] . '<a' . $css . ' href="' . get_tag_link($tag->term_id) . '">#' . $match[2] . '</a>';
    }
    return $content;
  }

  // function to generate user link
  private function make_link_users( $match, $link ) {
    $user = false;
    $username = $match[2];
    // get by nickname or by login name
    if ( ! $this->settings['usernamesnick'] ) {
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
      $css = $this->settings['usernamescssclass'];
      if ( $css != '' ) {
        $css = ' class="' . $css . '"';
      }
      $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>@' . $match[2] . '</a>';
    }
    return $content;
  }
  
  // *** For Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/ (since v 3.2) 
  function process_content_for_user_submitted_posts( $new_user_post ) {
    $this->generate_tags( $new_user_post['id'] );
    return $new_user_post;
  }
  
  // *** For Barley - Inline Editing Plugin for WordPress (since v 3.2)
  function process_content_for_barely() {
    // this function overrides barley_update_post in functions_posts.php of the Barely plugin
    
    // -- Taken from Barely
    $json            = array();
    $json['success'] = false;
    $columns         = array(
                      'the_title'   => 'post_title',
                      'the_content' => 'post_content');

    // Only proceed if we have a post_id
    if ( isset($_POST['p']) && ! empty($_POST['p']) ) {
      $k               = trim(urldecode($_POST['k']));
      $v               = trim(urldecode($_POST['v']));
      $pid             = trim(urldecode($_POST['p']));

      // Strip trailing BR tag in FireFox
      if ( $k === 'the_title' ) {
        $v = preg_replace('/(.*)<br[^>]*>/i', '$1', $v);
      }

      // For the_title and the_content only
      if (array_key_exists($k, $columns)) {
          $res = wp_update_post(array(
              'ID'         => $pid,
              $columns[$k] => $v
          ));
      }

      // Save an Advanced Custom Field
      if ( strpos($k, 'field_') !== false ) {
        $res = update_field($k,$v,$pid);
      }

      // Save a WordPress Custom Field
      if ( strpos($k, 'field_') === false && !array_key_exists($k, $columns) ) {
        $res = update_post_meta($pid,$k,$v);
      }
      
      // -- ** added for hashtagger **
      if ( $k === 'the_content' ) {
        $this->generate_tags( $pid );
      }
      // -- ** added for hashtagger **

      // Good? No? Yes?
      $json['success'] = ($res > 0) ? true : false;

    } // end post_id

    header('Content-Type: application/json');
    print json_encode($json);
    exit();
    // -- Taken from Barely
    
  }

  // uninstall plugin
  function uninstall() {
    if( is_multisite() ) {
      $this->uninstall_network();
    } else {
      $this->uninstall_single();
    }
  }
  
  // uninstall network wide
  function uninstall_network() {
    global $wpdb;
    $activeblog = $wpdb->blogid;
    $blogids = $wpdb->get_col( esc_sql( 'SELECT blog_id FROM ' . $wpdb->blogs ) );
    foreach ($blogids as $blogid) {
      switch_to_blog( $blogid );
      $this->uninstall_single();
    }
    switch_to_blog( $activeblog );
  }
  
  // uninstall single blog
  function uninstall_single() {
    foreach ( $this->settings as $key => $value) {
      if ( $key != 'tagbase' ) {
        delete_option( 'swcc_htg_' . $key );
      }
    }
  }
  
  // add a link to settings page in plugin list
  function add_settings_link( $links ) {
    return array_merge( $links, array( '<a href="' . admin_url( 'options-general.php?page=hashtaggersettings' ) . '">' . __( 'Settings' ) . '</a>') );
  }
  
}

class Hashtagger_Admin {

  protected $version;
  protected $settings;
  protected $caller;
  protected $regnonce;
  
  public function __construct( Hashtagger $caller ) {
    $this->caller = $caller;
    $this->version = $caller->version;
    $this->settings = $caller->settings;
    $this->regnonce = 'hashtagger_regenerate';
    $this->init();
  }
  
  private function init() {
    add_action( 'admin_head', array( $this, 'admin_style' ) );
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'wp_ajax_hashtagger_regenerate', array( &$this, 'admin_hashtagger_regenerate' ) );
  }
  
  // adds the options page to admin menu
  function admin_menu() {
    add_options_page( 'hashtagger ' . __( 'Settings' ), '#hashtagger', 'manage_options', 'hashtaggersettings', array( $this, 'admin_page' ) );
  }
  
  // add css
  function admin_style() {
    if ( get_current_screen()->id == 'settings_page_hashtaggersettings' ) { 
      ?>
      <style type="text/css">
        .hashtagger_settings_form input[type="text"], .hashtagger_settings_form select { 
          border-width:2px; 
          padding:10px; 
          border-style:solid; 
          border-radius:5px; 
          height: auto !important;
        }
        .hashtagger_settings_form input[type="text"]:not(:focus), .hashtagger_settings_form select:not(:focus) { 
          box-shadow: 0px 0px 5px 0px rgba(42,42,42,.75); 
        }
        .hashtagger_settings_form input[type="checkbox"], #hashtagger_ajax_area input[type="checkbox"] {
            display: none;
        }
        .hashtagger_settings_form input[type="checkbox"] + label.check, #hashtagger_ajax_area input[type="checkbox"] + label.check {
          display: inline-block;  
          border: 2px solid #DDD;
          box-shadow: 0px 0px 5px 0px rgba(42,42,42,.75); 
          border-style:solid; 
          border-radius:5px; 
          width: 30px;
          height: 30px;
          line-height: 30px;
          text-align: center;
          font-family: dashicons;
          font-size: 2em;
          margin-right: 10px;
        }
        .hashtagger_settings_form input[type="checkbox"]:disabled + label.check, #hashtagger_ajax_area input[type="checkbox"]:disabled + label.check {
          background-color: #DDD;
        }
        .hashtagger_settings_form input[type="checkbox"] + label.check:before, #hashtagger_ajax_area input[type="checkbox"] + label.check:before {
          content: "";  
        }
        .hashtagger_settings_form input[type="checkbox"]:checked + label.check:before, #hashtagger_ajax_area input[type="checkbox"]:checked + label.check:before {
          content: "\f147";
        }
      </style>
      <?php
    }
  }
  
  // creates the options page
  function admin_page() {
    $url = admin_url( 'options-general.php?page=' . $_GET['page'] . '&tab=' );
    $current_tab = 'general';
    if ( isset( $_GET['tab'] ) ) {
      $current_tab = $_GET['tab'];
    }
    if ( ! in_array( $current_tab, array('general', 'advanced', 'posttype', 'sectiontype', 'css', 'regenerate') ) ) {
      $current_tab = 'general';
    }
    ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2 class="nav-tab-wrapper">
        <a href="<?php echo $url . 'general'; ?>" class="nav-tab<?php if ( 'general' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'General' ); ?></a>
        <a href="<?php echo $url . 'advanced'; ?>" class="nav-tab<?php if ( 'advanced' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Advanced' ); ?></a>
        <a href="<?php echo $url . 'posttype'; ?>" class="nav-tab<?php if ( 'posttype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Post Types', 'hashtagger' ); ?></a>
        <a href="<?php echo $url . 'sectiontype'; ?>" class="nav-tab<?php if ( 'sectiontype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Section Types', 'hashtagger' ); ?></a>
        <a href="<?php echo $url . 'css'; ?>" class="nav-tab<?php if ( 'css' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'CSS Style', 'hashtagger' ); ?></a>
        <a href="<?php echo $url . 'regenerate'; ?>" class="nav-tab<?php if ( 'regenerate' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Regnerate', 'hashtagger' ); ?></a>
      </h2>
      <div id="poststuff">
        <div id="post-body" class="metabox-holder<?php if ( 'regenerate' != $current_tab ) { echo ' columns-2'; } ?>">
          <div id="post-body-content">
            <div class="meta-box-sortables ui-sortable">
              <?php if ( 'regenerate' == $current_tab ) { ?>
                <?php 
                  $objects = $this->get_objects();
                ?>
                <div class="postbox">
                  <div class="inside">
                    <p><strong><?php _e( 'Process all existing objects using the current settings', 'hashtagger' ); ?></strong></p><hr />
                    <?php 
                      if ( count( $objects ) == 0 ) {
                        echo '<p>' . __( 'No objects to process!', 'hashtagger' ) . '</p>';
                      } else {
                        echo '<div id="hashtagger_ajax_area"><p><span class="form-invalid">' . __( 'JavaScript must be enabled to use this feature.' ) . '</span></p></div>';
                        add_action( 'admin_print_footer_scripts', array( $this, 'add_regenerate_js' ) );
                      }
                    ?>
                  </div>
                </div>
              <?php } else { ?>
                <form method="post" action="options.php" class="hashtagger_settings_form">
                  <?php if ( 'general' == $current_tab ) { ?>
                      <div class="postbox">
                        <div class="inside">
                          <p style="line-height: 32px; padding-left: 40px; background-image: url(<?php echo plugins_url( 'pluginicon.png', __FILE__ ); ?>); background-repeat: no-repeat;">hashtagger Version <?php echo $this->version; ?></p>
                        </div>
                      </div>
                      <div class="postbox">
                        <div class="inside">
                          <p><strong>#hashtag <?php _e( 'Permalinks' ); ?></strong></p>
                          <hr />
                          <p>#hashtags <?php _e( 'currently link to', 'hashtagger'); ?> <code style="white-space: nowrap"><?php echo $this->tag_base_url() . '[hashtag]'; ?></code>.</p>
                          <p><?php printf( __( 'The <b>Tag base</b> for the Archive URL can be changed on %s page', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_general_tagbase"></a>', '<a href="'. admin_url( 'options-permalink.php' ) .'">' . __( 'Permalink Settings' ) . '</a>' ); ?>.</p>
                        </div>
                      </div>
                    <?php } ?>
                    <div class="postbox">
                      <div class="inside">
                        <?php
                          settings_fields( 'hashtagger_settings_' . $current_tab );   
                          do_settings_sections( 'hashtagger_settings_section_' . $current_tab );
                          submit_button(); 
                        ?>
                      </div>
                  </div>
                </form>
              <?php } ?>
            </div>
          </div>
          <?php if ( 'regenerate' != $current_tab ) { $this->show_meta_boxes(); } ?>
        </div>
        <br class="clear">
      </div>    
    </div>
    <?php
  }
  
  // funtion to get the base directory for tags
  private function tag_base_url() {
    return trailingslashit( get_site_url() . '/' . $this->settings['tagbase'] );
  }
  
  // init the admin section
  function admin_init() {
    
    add_settings_section( 'hashtagger-settings-general', '', array( $this, 'admin_section_general_title' ), 'hashtagger_settings_section_general' );
    register_setting( 'hashtagger_settings_general', 'swcc_htg_usernames' ) ;
    register_setting( 'hashtagger_settings_general', 'swcc_htg_usernamesnick' );
    add_settings_field( 'swcc_htg_settings_usernames', __( 'Link @usernames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_general_usernames"></a>', array( $this, 'admin_usernames' ), 'hashtagger_settings_section_general', 'hashtagger-settings-general', array( 'label_for' => 'swcc_htg_usernames' ) );
    add_settings_field( 'swcc_htg_settings_usernamesnick', __( '@nicknames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_general_nicknames"></a>', array( $this, 'admin_usernamesnick' ), 'hashtagger_settings_section_general', 'hashtagger-settings-general', array( 'label_for' => 'swcc_htg_usernamesnick' ) );
    
    add_settings_section( 'hashtagger-settings-advanced', '', array( $this, 'admin_section_advanced_title' ), 'hashtagger_settings_section_advanced' );
    register_setting( 'hashtagger_settings_advanced', 'swcc_htg_advanced_nodelete' );
    add_settings_field( 'swcc_htg_settings_advanced_nodelete', __( 'Do not delete unused Tags', 'hashtagger') . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_advanced_nodelete"></a>' , array( $this, 'admin_advanced_nodelete' ), 'hashtagger_settings_section_advanced', 'hashtagger-settings-advanced', array( 'label_for' => 'swcc_htg_advanced_nodelete' ) );
    
    add_settings_section( 'hashtagger-settings-posttype', '', array( $this, 'admin_section_posttype_title' ), 'hashtagger_settings_section_posttype' );
    register_setting( 'hashtagger_settings_posttype', 'swcc_htg_posttype_page' );
    register_setting( 'hashtagger_settings_posttype', 'swcc_htg_posttype_custom' );
    add_settings_field( 'swcc_htg_settings_posttype_post', __( 'Posts' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_posttypes_posts"></a>', array( $this, 'admin_posttype_post' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_post' ) );
    add_settings_field( 'swcc_htg_settings_posttype_page', __( 'Pages' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_posttypes_pages"></a>', array( $this, 'admin_posttype_page' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_page' ) );
    add_settings_field( 'swcc_htg_settings_posttype_custom', __( 'Custom Post Types', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_posttypes_custom"></a>', array( $this, 'admin_posttype_custom' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_custom' ) );
    
    add_settings_section( 'hashtagger-settings-sectiontype', '', array( $this, 'admin_section_sectiontype_title' ), 'hashtagger_settings_section_sectiontype' );
    register_setting( 'hashtagger_settings_sectiontype', 'swcc_htg_sectiontype_title' );
    register_setting( 'hashtagger_settings_sectiontype', 'swcc_htg_sectiontype_excerpt' );
    add_settings_field( 'swcc_htg_sectiontype_title', __( 'Title' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_sectiontypes_title"></a>', array( $this, 'admin_sectiontype_title' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_title' ) );
    add_settings_field( 'swcc_htg_sectiontype_excerpt', __( 'Excerpt' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_sectiontypes_excerpt"></a>', array( $this, 'admin_sectiontype_excerpt' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_excerpt' ) );
    add_settings_field( 'swcc_htg_sectiontype_content', __( 'Content' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_sectiontypes_content"></a>', array( $this, 'admin_sectiontype_content' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_content' ) );
    
    add_settings_section( 'hashtagger-settings-css', '', array( $this, 'admin_section_css_title' ), 'hashtagger_settings_section_css' );
    register_setting( 'hashtagger_settings_css', 'swcc_htg_cssclass', array( $this, 'admin_cssclass_validate' ) );
    register_setting( 'hashtagger_settings_css', 'swcc_htg_cssclass_notag', array( $this, 'admin_cssclass_validate' ) );
    register_setting( 'hashtagger_settings_css', 'swcc_htg_usernamescssclass', array( $this, 'admin_cssclass_validate' ) );
    add_settings_field( 'swcc_htg_settings_cssclass', __( 'CSS class name(s) for #hashtags', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_css_hashtags"></a>', array( $this, 'admin_cssclass' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cssclass' ) );
    add_settings_field( 'swcc_htg_settings_cssclass_notag', __( 'CSS class name(s) for +#hashtag links', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_css_hashtaglinks"></a>', array( $this, 'admin_cssclass_notag' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cssclass_notag' ) );
    add_settings_field( 'swcc_htg_settings_usernamescssclass', __( 'CSS class name(s) for @usernames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_css_usernames"></a>', array( $this, 'admin_usernamescssclass' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_usernamescssclass' ) );
    
  }
  
  // * 
  // * ADMIN SECTIONS
  // * 
  
  // * General Section

  // echo title for general settings section
  function admin_section_general_title() {
    echo '<p><strong>' . __( 'Handling of @usernames', 'hashtagger' ) . ':</strong></p><hr />';
  }
  
  // handle the settings field : user names
  function admin_usernames() {
    $curvalue = $this->settings['usernames'];
    echo '<select name="swcc_htg_usernames" id="swcc_htg_usernames">';
    echo '<option value="NONE"' . ( ( $curvalue == 'NONE' ) ? ' selected="selected"' : '' ) . '>' . __('Ignore @usernames', 'hashtagger' ) . '</option>';
    echo '<option value="PROFILE"' . ( ( $curvalue == 'PROFILE' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users profile page', 'hashtagger' ) . '</option>';
    echo '<option value="WEBSITE-SAME"' . ( ( $curvalue == 'WEBSITE-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in same browser tab', 'hashtagger') . '</option>';
    echo '<option value="WEBSITE-NEW"' . ( ( $curvalue == 'WEBSITE-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in new browser tab', 'hashtagger') . '</option>';
    echo '</select>';
  }
  
  // handle the settings field : use nicknames instead of usernames
  function admin_usernamesnick() {
    echo '<input type="checkbox" name="swcc_htg_usernamesnick" id="swcc_htg_usernamesnick" value="1"' . ( ( $this->settings['usernamesnick'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_usernamesnick" class="check"></label>' . __( 'Use @nicknames instead of @usernames', 'hashtagger' ) . '<br /><div class="dashicons dashicons-shield"></div><strong>' . __( 'Highly recommended to enhance WordPress security!', 'hashtagger') . ' <a href="http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/">' . __( 'Read more', 'hashtagger' ) . '</a>';
  }

  // * Advanced Section
  
  // echo title for advanced settings section
  function admin_section_advanced_title() {
    echo '<p><strong>' . __( 'Handling of existing Tags', 'hashtagger' ) . ':</strong></p><hr />';
  }
  
  // handle the settings field : no delete
  function admin_advanced_nodelete() {
    echo '<input type="checkbox" name="swcc_htg_advanced_nodelete" id="swcc_htg_advanced_nodelete" value="1"' . ( ( $this->settings['advanced_nodelete'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_advanced_nodelete" class="check"></label>';
  }
  
  // * Post Type Section
  
  // echo title for posttype settings section
  function admin_section_posttype_title() {
    echo '<p><strong>' . __( 'Post Types to process', 'hashtagger' ) . ':</strong></p><hr />';
  }
  
  // handle the settings field : posttype 'post' - this is only a dummy, maybe for future use, currently posts are always on
  function admin_posttype_post() {
    echo '<input type="checkbox" name="swcc_htg_posttype_post" id="swcc_htg_posttype_post" value="1" checked="checked" disabled="disabled" /><label for="swcc_htg_posttype_post" class="check"></label>';
  }
  
  // handle the settings field : posttype 'page'
  function admin_posttype_page() {
    echo '<input type="checkbox" name="swcc_htg_posttype_page" id="swcc_htg_posttype_page" value="1"' . ( ( $this->settings['posttype_page'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_posttype_page" class="check"></label>';
  }
  
  // handle the settings field : posttype 'custom post types'
  function admin_posttype_custom() {
    echo '<input type="checkbox" name="swcc_htg_posttype_custom" id="swcc_htg_posttype_custom" value="1"' . ( ( $this->settings['posttype_custom'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_posttype_custom" class="check"></label>';
  }
  
  // * Section Type Section
  
  // echo title for sectiontype settings section
  function admin_section_sectiontype_title() {
    echo '<p><strong>' . __( 'Section Types to process', 'hashtagger' ) . ':</strong></p><hr />';
  }
  
  // handle the settings field : sectiontype 'title'
  function admin_sectiontype_title() {
    echo '<input type="checkbox" name="swcc_htg_sectiontype_title" id="swcc_htg_sectiontype_title" value="1"' . ( ( $this->settings['sectiontype_title'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_sectiontype_title" class="check"></label>';
  }
  
  // handle the settings field : sectiontype 'excerpt'
  function admin_sectiontype_excerpt() {
    echo '<input type="checkbox" name="swcc_htg_sectiontype_excerpt" id="swcc_htg_sectiontype_excerpt" value="1"' . ( ( $this->settings['sectiontype_excerpt'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_sectiontype_excerpt" class="check"></label>';
  }
  
  // handle the settings field : sectiontype 'content' ' - this is only a dummy, maybe for future use, currently content is always on
  function admin_sectiontype_content() {
    echo '<input type="checkbox" name="swcc_htg_sectiontype_content" id="swcc_htg_sectiontype_content" value="1" checked="checked" disabled="disabled" /><label for="swcc_htg_sectiontype_content" class="check"></label>';
  }
  
  // * CSS Style Section

  // echo title for css settings section
  function admin_section_css_title() {
    echo '<p><strong>' . __( 'CSS Classes to style links', 'hashtagger' ) .':</strong></p><hr />';
  }
  
  // handle the settings field : css class
  function admin_cssclass() {
    echo '<input class="regular-text" type="text" name="swcc_htg_cssclass" id="swcc_htg_cssclass" value="' . $this->settings['cssclass'] . '" />';
  }

  // handle the settings field : css class notag
  function admin_cssclass_notag() {
    echo '<input class="regular-text" type="text" name="swcc_htg_cssclass_notag" id="swcc_htg_cssclass_notag" value="' . $this->settings['cssclass_notag'] . '" />';
  }
  
  // handle the settings field : css class for usernames
  function admin_usernamescssclass() {
    echo '<input class="regular-text" type="text" name="swcc_htg_usernamescssclass" id="swcc_htg_usernamescssclass" value="' . $this->settings['usernamescssclass'] . '" />';
  }
  
  // validate input : css class
  function admin_cssclass_validate( $input ) {
    $classes = explode(' ', $input);
    $css = '';
    foreach( $classes as $class ) {
      $css = $css . sanitize_html_class( $class ) . ' ';
    }
    return rtrim( $css );
  }
  
  // this function returns an array of all objects to process depending on settings
  function get_objects() {
    $post_types = array();
    if ( $this->settings['posttype_custom'] ) {
      $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
    }
    if ( $this->settings['posttype_page'] ) {
      $post_types[] = 'page';
    }
    $post_types[] = 'post';
    return get_posts( array( 'post_type' => $post_types, 'posts_per_page' => -1 ) );
  }
    
  // add JS to regenerate all objets to footer
  function add_regenerate_js() {
    $objects = $this->get_objects();
    $ids = array();
    foreach( $objects as $object ) {
      $ids[] = $object->ID;
    }
    $ids = implode( ',', $ids );
    ?>
      <script type='text/javascript'>
        var object_ids = [<?php echo $ids; ?>];
        var objects = <?php echo count( $objects ); ?>;
        var counter = 0;
        var abort = false;
        jQuery( '#hashtagger_ajax_area' ).html( '<p><input type="checkbox" name="hashtagger_regenerate_confirmation" id="hashtagger_regenerate_confirmation" value="ok" /><label for="hashtagger_regenerate_confirmation" class="check"></label><span id="hashtagger_regenerate_confirmation_hint"><?php _e( 'Please confirm to regenerate', 'hashtagger' ); ?> <a class="dashicons dashicons-editor-help" href="http://smartware.cc/docs/hashtagger/#settings_regeneration"></a></span></p><p><input type="button" name="sumbit_regnerate" id="sumbit_regnerate" class="button button-primary button-large" value="<?php _e( 'Process all objects', 'hashtagger' ); ?> (<?php echo count( $objects ); ?>)"  /></p>' );
        jQuery( '#sumbit_regnerate' ).click( function() { 
          if ( jQuery( '#hashtagger_regenerate_confirmation' ).prop( 'checked' ) ) {
            jQuery( '#hashtagger_ajax_area' ).html( '<p><?php _e( 'Please be patient while objects are processed. Do not close or leave this page.', 'hashtagger' ); ?></p><p><div style="width: 100%; height: 40px; border: 2px solid #222; border-radius: 5px; background-color: #FFF"><div id="hashtagger_regnerate_progressbar" style="width: 0; height: 100%; background-image: url(<?php echo plugins_url( 'progress.png', __FILE__ ); ?>); background-repeat: repeat-x" ></div></div></p><p id="hashtagger_abort_area"><input type="button" name="cancel_regnerate" id="cancel_regnerate" class="button button-secondary button-large" value="<?php _e( 'Abort regeneration', 'hashtagger' ); ?>" /></p>' );
            jQuery( '#cancel_regnerate' ).click( function() { 
              abort = true;
              jQuery( '#hashtagger_abort_area' ).html( '<strong><?php _e( 'Aborting process...', 'hashtagger' ); ?></strong>' );
            });
            regenerate_object();
          } else {
            jQuery( '#hashtagger_regenerate_confirmation_hint' ).addClass( 'form-invalid' );
          }
        });
        function regenerate_object() {
          var object_id = object_ids[0];
          jQuery.ajax( { 
            type: 'POST', 
            url: ajaxurl, 
            data: { 'action': 'hashtagger_regenerate', 'id': object_id }, 
            success: function(response) {  
              counter++;
              jQuery( '#hashtagger_regnerate_progressbar' ).width( ( counter * 100 / objects ) + '%' );
              if ( abort ) {
                abortstring = '<?php _e( 'Process aborted. {COUNTER} of {OBJECTS} objects have been processed.', 'hashtagger'); ?>';
                abortstring = abortstring.replace( '{COUNTER}', counter );
                abortstring = abortstring.replace( '{OBJECTS}', objects );
                jQuery( '#hashtagger_abort_area' ).html( abortstring );
              } else {
                object_ids.shift();
                if ( object_ids.length > 0 ) {
                  regenerate_object();
                } else {
                  donestring = '<?php _e( 'All done. {OBJECTS} objects have been processed.', 'hashtagger' ); ?>';
                  donestring = donestring.replace( '{OBJECTS}', objects );
                  jQuery( '#hashtagger_abort_area' ).html( donestring );
                }
              }
            }
          } );
        }
      </script>
    <?php
  }
  
  // handle ajax call for one object
  function admin_hashtagger_regenerate() {
    $id = (int) $_REQUEST['id'];
    $this->caller->generate_tags( $id );
    echo $id;
    die();
  }
  
  // * 
  // * ADMIN SECTIONS END
  // * 
  
  // * 
  // * META BOXES 
  // * 
  
  // show meta boxes
  function show_meta_boxes() {
    ?>
    <div id="postbox-container-1" class="postbox-container">
      <div class="meta-box-sortables">
        <div class="postbox">
          <h3><span><?php _e( 'Like this Plugin?', 'hashtagger_general' ); ?></span></h3>
          <div class="inside">
            <ul>
              <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/extend/plugins/hashtagger/"><?php _e( 'Please rate the plugin', 'hashtagger_general' ); ?></a></li>
              <li><div class="dashicons dashicons-admin-home"></div>&nbsp;&nbsp;<a href="http://smartware.cc/wp-hashtagger/"><?php _e( 'Plugin homepage', 'hashtagger_general'); ?></a></li>
              <li><div class="dashicons dashicons-admin-home"></div>&nbsp;&nbsp;<a href="http://smartware.cc/"><?php _e( 'Author homepage', 'hashtagger_general' );?></a></li>
              <li><div class="dashicons dashicons-googleplus"></div>&nbsp;&nbsp;<a href="https://plus.google.com/+SmartwareCc"><?php _e( 'Authors Google+ Page', 'hashtagger_general' ); ?></a></li>
              <li><div class="dashicons dashicons-facebook-alt"></div>&nbsp;&nbsp;<a href="https://www.facebook.com/smartware.cc"><?php _e( 'Authors facebook Page', 'hashtagger_general' ); ?></a></li>
            </ul>
          </div>
        </div>
        <div class="postbox">
          <h3><span><?php _e( 'Need help?', 'hashtagger_general' ); ?></span></h3>
          <div class="inside">
            <ul>
              <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/plugins/hashtagger/faq/"><?php _e( 'Take a look at the FAQ section', 'hashtagger_general' ); ?></a></li>
              <li><div class="dashicons dashicons-wordpress"></div>&nbsp;&nbsp;<a href="http://wordpress.org/support/plugin/hashtagger"><?php _e( 'Take a look at the Support section', 'hashtagger_general'); ?></a></li>
              <li><div class="dashicons dashicons-admin-comments"></div>&nbsp;&nbsp;<a href="http://smartware.cc/contact/"><?php _e( 'Feel free to contact the Author', 'hashtagger_general' ); ?></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

}

$hashtagger = new Hashtagger();

// this function can be used in theme
// does all the hashtagger stuff on a string
function do_hashtagger( $content ) {
  $htg = new Hashtagger();
  return $htg->work( $content );
}