<?php

switch ( $_REQUEST['action'] ) {
  case 'getavailabletags' :
    $sql = 'SELECT * FROM Tags ORDER BY LastAssignedDate DESC';
    $tags = dbFetchAll($sql);

    // Re-sort so tags THIS user has applied recently come first, ahead of
    // the global "most recently applied by anyone" fallback order. The
    // recency list is stored server-side (User_Preferences), keyed by Tag
    // Id, so it's correct even on a device this user has never used before.
    require_once('includes/TagOrder.php');
    $tagOrderIds = \ZM\TagOrder::load($user->Id());
    if ($tagOrderIds) {
      $byId = array();
      foreach ($tags as $tag) {
        $byId[$tag['Id']] = $tag;
      }
      $personal = array();
      $used = array();
      foreach ($tagOrderIds as $tid) {
        if (isset($byId[$tid]) and !isset($used[$tid])) {
          $personal[] = $byId[$tid];
          $used[$tid] = true;
        }
      }
      $rest = array_values(array_filter($tags, function($tag) use ($used) {
        return !isset($used[$tag['Id']]);
      }));
      $tags = array_merge($personal, $rest);
    }

    ajaxResponse(array('response'=>$tags));
    break;
  case 'createtag' :
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
