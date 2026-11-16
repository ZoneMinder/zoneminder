<?php

// Tags are attached to events: listing them needs what the event view needs (Events
// or Snapshots view, as ajax/event.php), creating one Events edit.
if (!(canView('Events') or canView('Snapshots'))) {
  ajaxError('Insufficient permissions to view tags');
}

switch ( $_REQUEST['action'] ) {
  case 'getavailabletags' :
    $sql = 'SELECT * FROM Tags ORDER BY LastAssignedDate DESC';
    $dbFetchResult = dbFetchAll($sql);
    ajaxResponse(array('response'=>$dbFetchResult));
    break;
  case 'createtag' :
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions to create tags');
    }
    $sql = 'INSERT INTO Tags (Name, CreatedBy) VALUES (?, ?)';
    $values = array($_REQUEST['tname'], $user->Id());
    $result = dbFetchAll($sql, NULL, $values);

    $sql = 'SELECT * FROM Tags WHERE Id = ?';
    $values = array(dbInsertId());
    $dbFetchResult = dbFetchAll($sql, NULL, $values);

    ajaxResponse(array('response'=>$dbFetchResult));
    break;
} // end switch action

ajaxError('Unrecognised action '.$_REQUEST['action']);
?>
