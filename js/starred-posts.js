( function( $ ){
  $( function(){
    $( '.ino-star-clickable' ).click( function( e ){
      e.preventDefault();
      var $link = $( this ),
          post_id = $link.data( 'post_id' );

      $link.hide();

      jQuery.post(
        ajaxurl,
        {
          'action'   : 'ino_set_star',
          'post_id': post_id
        },
        function( result ){
          if( typeof result.val !== 'undefined' ){
            $link.attr( 'class', 'ino-star c'+result.val );
          }

          $link.fadeIn( 30 );
        },
        'json'
      );

    } );
  } );
} )( jQuery );
