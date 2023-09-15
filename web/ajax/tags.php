<?php

switch ( $_REQUEST['action'] ) {
  case 'getavailabletags' :
    $sql = 'SELECT * FROM Tags ORDER BY LastAssignedDate DESC';
    $dbFetchResult = dbFetchAll($sql);
    ajaxResponse(array('response'=>$dbFetchResult));
    break;
  case 'createtag' :
    $sql = 'INSERT INTO Tags (Name, CreatedBy) VALUES (?, ?) RETURNING Id';
    $values = array($_REQUEST['tname'], $user->Id());
    $result = dbFetchAll($sql, NULL, $values);
    $r = $result[0];

    $sql = 'SELECT * FROM Tags WHERE Id = ?';
    $values = array($r['Id']);
    $dbFetchResult = dbFetchAll($sql, NULL, $values);

    ajaxResponse(array('response'=>$dbFetchResult));
    break;
} // end switch action

ajaxError('Unrecognised action '.$_REQUEST['action']);
?>
