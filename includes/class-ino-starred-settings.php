<?php
// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( '' );

class Ino_Starred_Settings
{
    private $options;


    public function __construct(){
    }


    public function run(){

      add_action( 'admin_menu', array( $this, 'set_admin_menu' ) );
      add_action( 'admin_init', array( $this, 'page_init' ) );
    }


    public function set_admin_menu(){

      add_options_page(
          'Starred Posts Settings',
          'Starred Posts',
          'manage_options',
          'ino-starred-settings',
          array( $this, 'display_settings_page' )
      );
    }


    public function page_init(){

      register_setting(
        'ino_starred_group1',
        'ino_starred_common',
        array( $this, 'sanitize' )
      );

      add_settings_section(
        'stars_section',
        null,
        array( $this, 'stars_section_info' ),
        'ino-starred-posts-admin'
      );

      add_settings_field(
        'enabled_stars',
        'Stars',
        array( $this, 'do_field_enabled_stars' ),
        'ino-starred-posts-admin',
        'stars_section'
      );

      add_settings_field(
        'post_types',
        'Post Types',
        array( $this, 'do_field_post_types' ),
        'ino-starred-posts-admin',
        'stars_section'
      );

      add_settings_field(
        'save_type',
        'Stars Availability',
        array( $this, 'do_field_save_type' ),
        'ino-starred-posts-admin',
        'stars_section'
      );
    }


    public function display_settings_page(){

      wp_enqueue_style( 'ino_starred_posts' );
      wp_enqueue_script( 'ino_starred_settings' );

      // Set class property
      $this->options = get_option( 'ino_starred_common' );

      ?>
      <div class="wrap">
        <h2>Starred Posts - Settings</h2>

        <p>Use stars to mark posts, pages and custom posts in your Wordpress admin. Mark a post with a star so you
          remember it's important, or just because you want to highlight it.</p>

        <form method="post" action="options.php">
        <?php
          settings_fields( 'ino_starred_group1' );
          do_settings_sections( 'ino-starred-posts-admin' );
          submit_button();
          ?>
        </form>
        <div class="ino-starred-settings-footer">Thank you for using the Starred Posts plugin. v<?php echo INO_STARRED_POSTS_VERSION; ?></div>
      </div>
      <?php
    }


    //register and add settings
    public function sanitize( $input ){

      $new_input = array();
      if( isset( $input['enabled_stars'] ) ){
        $new_input['enabled_stars'] = sanitize_text_field( $input['enabled_stars'] );
      }

      if( isset( $input['post_types'] ) ){
        $new_input['post_types'] = $this->sanitize_custom_pt( $input['post_types'] );
      }

      if( isset( $input['save_type'] ) ){
        $new_input['save_type'] = sanitize_text_field( $input['save_type'] );
      }

      return $new_input;
    }


    public function stars_section_info(){

    }


    public function do_field_enabled_stars(){

      $max_num       = 12;
      $ids           = $this->get_ids_list();
      $item_template = '<div id="star_%1$d" class="ino-star c%1$d" title="star %1$d"></div>';
      $enabled       = array_fill( 0, count( $ids ), null );
      $disabled      = array();

      for( $i = 1; $i <= $max_num; $i++ ){
        $item = sprintf( $item_template, $i );
        $enabled_idx = array_search( $i, $ids );

        if( $enabled_idx !== false ){
          $enabled[$enabled_idx] = $item;
        }else{
          $disabled[] = $item;
        }
      }
      ?>
      <strong>Drag the stars between the lists.</strong>  The stars will rotate in the order shown below when you click successively.
      <div class="ino-stars-row">
        <div class="ino-stars-list-label">In Use</div>
        <div class="ino-stars-enabled ino-stars-connected">
          <?php echo implode( "\n", $enabled ); ?>

        </div>
      </div>
      <div class="ino-stars-row">
        <div class="ino-stars-list-label">Not In Use</div>
        <div class="ino-stars-disabled ino-stars-connected">
          <?php echo implode( "\n", $disabled ); ?>
        </div>
      </div>
      <?php

      printf(
        '<input type="hidden" id="ino_enabled_stars" name="ino_starred_common[enabled_stars]" value="%s" />',
        isset( $this->options['enabled_stars'] ) ? esc_attr( $this->options['enabled_stars'] ) : ''
      );
    }


    public function do_field_post_types(){

      $pts  = get_post_types(
        array( 'show_ui' => true , '_builtin' => false),
        'objects'
      );

      $option_pts   = ( isset( $this->options['post_types'] ) ) ? $this->options['post_types'] : array();
      $item_template = '<li><label><input type="checkbox" name="ino_starred_common[post_types][%s]" %s>%s</label></li>';

      $builtin = array(
        'post' => 'Posts',
        'page' => 'Pages'
      );
      ?>
      <p>Select the post types where you'd like Starred Posts to be enabled.
      <?php
      echo '<h4>WP Post types</h4>';
      echo '<ul class="ino-star-builtin-pt">';
      foreach( $builtin as $pt_id=>$pt ){
        $checked = ( in_array( $pt_id, $option_pts ) )? 'checked' : '';
        $item    = sprintf( $item_template, $pt_id, $checked, $pt );
        echo $item;
      }
      echo '</ul>';

      echo '<h4>Custom Post types</h4>';
      echo '<ul class="ino-star-custtom-pt">';
      foreach( $pts as $pt_id=>$pt ){
        $checked = ( in_array( $pt_id, $option_pts ) ) ? 'checked' : '';
        $item    = sprintf( $item_template, $pt_id, $checked, $pt->labels->name );
        echo $item;
      }
      echo '</ul>';
    }


    public function do_field_save_type(){

      $options_save  = ( isset( $this->options['save_type'] ) ) ? $this->options['save_type'] : 'site';
      $item_template = '<option value="%s" %s>%s</option>';
      $item_template = <<<EOD
      <tr>
        <th>
          <label>
            <input name="ino_starred_common[save_type]" type="radio" value="%s" %s> %s
          </label>
        </th>
        <td>
          <em>%s</em>
        </td>
      </tr>
EOD;
      $types = array(
        'user' => array(
          'label' => 'By User',
          'help' => 'Stars added by a user are only visible to that user.'
        ),
        'site' => array(
          'label' => 'Site wide',
          'help'=>'Stars added by a user are visible to all users.'
        )
      );
      ?>
        <table class="form-table ino-starred-setting-table">
          <tbody>

      <?php
            foreach( $types as $option_id=>$option ){
              $selected = ( $option_id == $options_save )? 'checked' : '';
              $item     = sprintf( $item_template,
                $option_id,
                $selected,
                $option['label'],
                $option['help']
              );
              echo $item;
            }
      ?>
          </tbody>
        </table>

      <?php
    }


    private function get_ids_list(){

      $ids_str  = ( isset( $this->options['enabled_stars'] ) && !empty( $this->options['enabled_stars'] ) ) ? $this->options['enabled_stars'] : '1';
      return explode( ',',$ids_str );
    }


    private function sanitize_custom_pt( $pts = array() ){

      return array_keys( $pts );
    }
}
