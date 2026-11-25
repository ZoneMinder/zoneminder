<?php
if ( !canEdit('Control') ) {
  ZM\Warning('Need Control permissions to edit control capabilities');
  ajaxError('Insufficient permissions');
  return;
}

if ( empty($_REQUEST['action']) ) {
  ajaxError('Action Not Provided');
  return;
} else {
  $action = $_REQUEST['action'];
}

if ( isset($_REQUEST['cids']) ) {
  $cids = (array)$_REQUEST['cids'];
} else {
  ajaxError('At least one Control Id must be Provided.');
  return;
}

if ( $action == 'delete' ) {
  $failed = false;
  foreach ( $cids as $cid ) {
    if ( !is_scalar($cid) || !is_numeric($cid) )
      continue;
    $cid = validCardinal($cid);
    if ( $cid === '' )
      continue;
    if ( dbQuery('UPDATE Monitors SET Controllable = 0, ControlId = 0 WHERE ControlId = ?', array($cid)) === null )
      $failed = true;
    if ( dbQuery('DELETE FROM Controls WHERE Id = ?', array($cid)) === null )
      $failed = true;
  }
  if ( $failed ) {
    ajaxError('Failed to delete control capability: '.dbLastError());
    return;
  }
} else {
  ajaxError('Unrecognised action ' .$_REQUEST['action']);
  return;
}

ajaxResponse();
return;
?>
