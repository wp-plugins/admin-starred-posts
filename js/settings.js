( function( $ ){
  var $list, $enabled_field;

  $( function(){
    $( ".ino-stars-enabled, .ino-stars-disabled" ).sortable( {
      connectWith : ".ino-stars-connected",
      items       : "> div.ino-star",
      cursor      : "move"
    } ).disableSelection();

    $list          = $( ".ino-stars-enabled" );
    $disabled_list = $( ".ino-stars-disabled" );
    $enabled_field = $( "#ino_enabled_stars" );

    $list.on(
      'sortstop sortupdate',
      function( event, ui ){
        var ids  = $list.sortable( 'toArray' ),
            nums = $.map( ids, function( n, i ){
              return n.split('_')[1];
            } );

        $enabled_field.val( nums.join( ',' ) );
    });

    $disabled_list.on(
      'sortreceive',
      function( e, ui ){
          if( ui.sender != null && ui.sender.sortable( 'toArray' ).length == 0 ){
            ui.sender.sortable( 'cancel' );
          }
    } );
  } );
})(jQuery);
