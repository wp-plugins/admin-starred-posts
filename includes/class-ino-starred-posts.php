<?php
// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( '' );

class Ino_Starred_Posts {

  private $options;


  public function __construct() {
  }


  //should be call to start everything
  public function run() {
	   $this->define_hooks();
	}


  //add all actions, filters and other dependencies here
  private function define_hooks() {

    //plugin functionality only exists in the wp admin side.
    if(is_admin()){
      add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

      //ajax
      add_action( 'wp_ajax_ino_set_star', array( $this, 'set_star' ) );

    }

    //before adding the functionality, we should check what page we're on
    add_action( 'current_screen', array( $this, 'define_hooks_for_screen' ) );
  }


  public function register_scripts(){

    $plugin_url = trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) );

    //scripts
    wp_register_script( 'ino_starred_posts', $plugin_url .'js/starred-posts.js', array('jquery'), 1.0, false );
    wp_register_script( 'ino_starred_settings', $plugin_url .'js/settings.js', array('jquery-ui-sortable'), 1.0, false );

    //styles
    wp_register_style( 'ino_starred_posts', $plugin_url .'css/main.css', '1.0.0' );
  }


  public function enqueue_scripts(){

    wp_enqueue_style( 'ino_starred_posts' );
    wp_enqueue_script( 'ino_starred_posts' );
  }


  //check if current screen needs the plugin features
  public function define_hooks_for_screen( $screen ){

    //only add functionality on admin pages listing posts, pages, or custom posts
    if( $screen->base == 'edit' && isset( $screen->post_type ) && !empty( $screen->post_type ) ){

      $this->set_options();

      $post_types = ( isset( $this->options['post_types'] ) ) ? $this->options['post_types'] : array();

      //if current post type is in the list of enabled post types
      if( in_array( $screen->post_type, $post_types ) ){

        add_filter( 'manage_'.$screen->post_type.'_posts_columns' , array( $this, 'add_admin_column' ) );

        if( $screen->post_type == 'page' || is_post_type_hierarchical( $screen->post_type ) ){
          add_action( 'manage_pages_custom_column' , array( $this, 'display_admin_column' ), 10, 2 );
        }else{
          add_action( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'display_admin_column' ), 10, 2 );
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        //actions and filters used to add a filter select box in posts index page
        add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
        add_filter( 'parse_query', array( $this, 'parse_query' ) );
      }
    }
  }


  private function get_field_name(){

    $field_name = '_ino_star';
    if( 'user' == $this->options['save_type'] ){
      $field_name.= '_'.get_current_user_id();
    }
    return $field_name;
  }


  private function get_ids_list(){

    $ids_str       = ( isset( $this->options['enabled_stars'] ) && !empty( $this->options['enabled_stars'] ) ) ? $this->options['enabled_stars'] : '1';
    return explode( ',',$ids_str );
  }

  private function set_options(){
    $this->options = get_option('ino_starred_common');
  }


  function add_admin_column( $columns ) {

    if( !isset( $columns['ino_starred_posts'] ) ){
      //insert column on the left side, so it is easy to see and interact with
      $insert_at = 1;

      $columns = array_merge(
        array_slice( $columns, 0, $insert_at, true ),
        array( 'ino_starred_posts' => '<span class="ino-starred-column-header">Stars</span>' ),
        array_slice( $columns, $insert_at, null, true )
      );
    }
    return $columns;
  }


  //column values for posts list
  function display_admin_column( $column, $post_id ) {

    switch( $column ){
      case 'ino_starred_posts':
        $this->set_options();

        $item_template = '<a href="#" class="ino-star-clickable ino-star c%d" data-post_id="%d" tabindex="-1" title="%s">*</a>';
        $field_name    = $this->get_field_name();

        $star = get_post_meta( $post_id, $field_name, true );
        if( empty( $star ) ){
          $star = 0;
          $star_label = '';
        }else{
          $star_info  = Ino_Starred_Stars::get_star_by_id( $star );
          $star_label = ( $star_info == null )? 'star ' . $star : $star_info['label'];
          $star_label = 'starred with \'' . $star_label . '\'';
        }
        printf($item_template, $star, $post_id, $star_label);
      break;
    }
  }


  //called via ajax, toggles the post star to the next available value
  function set_star(){

    $this->set_options();

    $post_id       = $_POST['post_id'];
    $ids           = $this->get_ids_list();

    //if no available stars, just return the default 'off' value
    if( count( $ids ) <= 0 ){
      echo json_encode( array( 'val' => 0 ) );
      exit;
    }

    $field_name = $this->get_field_name();

    $star = get_post_meta( $post_id, $field_name, true );

    if( empty( $star ) || $star === 0 ){
      $star = $ids[0];
    }else{
      $idx = array_search( $star, $ids );

      //if current value is the last on the list, go back to 'off'
      if( $idx == count($ids)-1 ){
        $star = 0;
      //else if the value is not on the list, set to the first available
      }else if($idx === false ){
        $star = $ids[0];
      //just set the next value on the list
      }else{
        $star = $ids[$idx+1];
      }
    }

    $star_info = Ino_Starred_Stars::get_star_by_id( $star );
    $star_label = ( $star_info == null )? '' : $star_info['label'];

    update_post_meta($post_id, $field_name, $star);

    echo json_encode( array( 'val' => $star, 'label' => $star_label ) );
    exit;
  }


  //add filter select box above posts list
  public function restrict_manage_posts(){

    $this->set_options();

    $item_template = '<option value="%d" %s>%s</option>';
    $selected_item = ( isset( $_GET['ino_star'] ) ) ? $_GET['ino_star'] : '';
    $ids           = $this->get_ids_list();

    echo '<select name="ino_star" class="postform">';
    echo '<option value="">All Stars</option>';

    foreach( $ids as $id ){
      $star_info  = Ino_Starred_Stars::get_star_by_id( $id );
      $star_label = ( $star_info == null )? 'star ' . $id : $star_info['label'];

      $selected   = ( $id == $selected_item ) ? 'selected' : '';
      $item       = sprintf( $item_template, $id, $selected, $star_label );
      echo $item;
    }

    echo '</select>';
  }


  //add params to the query when a search filter has been applied
  public function parse_query( $query ){

    if( isset( $_GET['ino_star'] ) && !empty( $_GET['ino_star'] ) && is_admin()){
      $this->set_options();

      $field_name    = $this->get_field_name();

      $query->query_vars['meta_key']   = $field_name;
      $query->query_vars['meta_value'] = $_GET['ino_star'];
    }
  }

}
