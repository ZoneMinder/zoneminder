<?php
if ( !canEdit('Control') ) {
  ZM\Warning('Need Control permissions to edit control capabilities');
  return;
}

if ( empty($_REQUEST['action']) ) {
  ajaxError('Action Not Provided');
  return;
} else {
  $action = $_REQUEST['action'];
}

if ( isset($_REQUEST['cids']) ) {
  $cids = $_REQUEST['cids'];
} else {
  ajaxError('At least one Control Id must be Provided.');
  return;
}

if ( $action == 'delete' ) {
    foreach( $cids as $cid ) {
      dbQuery('UPDATE Monitors SET Controllable = 0, ControlId = 0 WHERE ControlId = ?', array($cid));
      dbQuery('DELETE FROM Controls WHERE Id = ?', array($cid));
    }
} else {
  ajaxError('Unrecognised action ' .$_REQUEST['action']);
  return;
}

ajaxResponse();
return;
?>
