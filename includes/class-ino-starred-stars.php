<?php
// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( '' );

class Ino_Starred_Stars {
    private static $stars = array(
      array(
        'label' => 'yellow-star',
        'id' => 1
      ),
      array(
        'label' => 'blue-star',
        'id' => 2
      ),
      array(
        'label' => 'purple-star',
        'id' => 3
      ),
      array(
        'label' => 'green-star',
        'id' => 4
      ),
      array(
        'label' => 'orange-star',
        'id' => 5
      ),
      array(
        'label' => 'red-star',
        'id' => 6
      ),
      array(
        'label' => 'green-check',
        'id' => 7
      ),
      array(
        'label' => 'red-bang',
        'id' => 8
      ),
      array(
        'label' => 'yellow-bang',
        'id' => 9
      ),
      array(
        'label' => 'blue-info',
        'id' => 10
      ),
      array(
        'label' => 'orange-guillemet',
        'id' => 11
      ),
      array(
        'label' => 'purple-question',
        'id' => 12
      )
    );


    public static function get_stars_count(){

      return count( self::$stars );
    }


    public static function get_stars(){

      return self::$stars;
    }


    public static function get_star_by_id( $id ){

      //this is available for php >= 5.5
      if( function_exists( 'array_column' ) ){
        $idx = array_search( (int)$id, array_column( self::$stars, 'id' ) );
        return self::$stars[ $idx ];
      //for everyone else
      }else{
        foreach( self::$stars as $key => $val ){
          if( $val['id'] === (int)$id ){
            return self::$stars[ $key ];
          }
        }
        return null;
      }
    }
}
