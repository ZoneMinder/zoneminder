<?php

if ( canEdit( 'Monitors' ) ) {
    switch ( $_REQUEST['action'] ) {
      case 'sort' :
        {
          $monitor_ids = $_POST['monitor_ids'];
          for ( $i = 0; $i < count($monitor_ids); $i += 1 ) {
            $monitor_id = $monitor_ids[$i];
            $monitor_id = preg_replace( '/^monitor_id-/', '', $monitor_id );
            if ( ( ! $monitor_id ) or ! ( is_integer( $monitor_id ) or ctype_digit( $monitor_id ) ) ) {
              Warning( "Got $monitor_id from " . $monitor_ids[$i] );
              continue;
            }
Error("Updating monitor ".$monitor_ids[$i] . " to position $i" );
            dbQuery( 'update Monitors set Sequence=? where Id=?', array( $i, $monitor_ids[$i] ) );
          } // end for each monitor_id
          break;
Warning("unknown action");
      } // end ddcase sort
      default:
      {
Warning("unknown action " . $_REQUEST['action'] );
      } // end ddcase default
    }
} else {
Warning("Cannot edit monitors" );
}

ajaxError( 'Unrecognised action or insufficient permissions' );

?>
