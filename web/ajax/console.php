<?php
if ( canEdit('Monitors') ) {
  switch ( $_REQUEST['action'] ) {
  case 'sort' :
  {
    $monitor_ids = $_POST['monitor_ids'];
    # Two concurrent sorts could generate odd sortings... so lock the table.
    global $dbConn;
    $dbConn->beginTransaction();
    $dbConn->exec('LOCK TABLES Monitors WRITE');
    for ( $i = 0; $i < count($monitor_ids); $i += 1 ) {
      $monitor_id = $monitor_ids[$i];
      $monitor_id = preg_replace('/^monitor_id-/', '', $monitor_id);
      if ( ( !$monitor_id ) or ! ( is_integer($monitor_id) or ctype_digit($monitor_id) ) ) {
        Warning('Got '.$monitor_id.' from '.$monitor_ids[$i]);
        continue;
      }
      dbQuery('UPDATE Monitors SET Sequence=? WHERE Id=?', array($i, $monitor_id));
    } // end for each monitor_id
    $dbConn->commit();
    $dbConn->exec('UNLOCK TABLES');

    return;
  } // end case sort
  default:
    ZM\Warning('unknown action '.$_REQUEST['action']);
  }
} else {
  ZM\Warning('Cannot edit monitors');
}

ajaxError('Unrecognised action '.$_REQUEST['action'].' or insufficient permissions for user ' . $user['Username']);
?>
