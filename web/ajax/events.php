<?php
ini_set('display_errors', '0');

if ( empty($_REQUEST['eids']) ) {
  ajaxError('No event id(s) supplied');
}

if ( canView('Events') ) {
} // end if canView('Events')

if ( canEdit('Events') ) {
  $message = array();

  foreach ( $_REQUEST['eids'] as $eid ) {

    switch ( $_REQUEST['action'] ) {
    case 'archive' :
    case 'unarchive' :
      $archiveVal = ($_REQUEST['action'] == 'archive')?1:0;
      dbQuery(
        'UPDATE Events SET Archived = ? WHERE Id = ?',
        array($archiveVal, $_REQUEST['id'])
      );
      break;
    case 'delete' :
      $event = new ZM\Event($eid);
      if ( !$event->Id() ) {
        $message[] = array($eid=>'Event not found.');
      } else {
        $event->delete();
      }
      break;
    } // end switch action
  } // end foreach
  ajaxResponse($message);
} // end if canEdit('Events')

ajaxError('Unrecognised action '.$_REQUEST['action'].' or insufficient permissions for user '.$user['Username']);
?>
