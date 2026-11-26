<?php

// Tags are attached to events: listing them needs what the event view needs (Events
// or Snapshots view, as ajax/event.php), creating one Events edit.
if (!(canView('Events') or canView('Snapshots'))) {
  ajaxError('Insufficient permissions to view tags');
}

switch ( $_REQUEST['action'] ) {
  case 'getavailabletags' :
    $sql = 'SELECT * FROM Tags ORDER BY LastAssignedDate DESC';
    $tags = dbFetchAll($sql);

    // A tag used only on events of monitors this user cannot see is left out, as in gettags.
    $usedTags = array();
    $visibleTags = array();
    foreach (dbFetchAll('SELECT ET.TagId, E.MonitorId FROM Events_Tags AS ET INNER JOIN Events AS E ON E.Id = ET.EventId GROUP BY ET.TagId, E.MonitorId') as $row) {
      $usedTags[$row['TagId']] = true;
      if (visibleMonitor($row['MonitorId'])) $visibleTags[$row['TagId']] = true;
    }
    $tags = array_values(array_filter($tags, function($tag) use ($usedTags, $visibleTags) {
      return !isset($usedTags[$tag['Id']]) or isset($visibleTags[$tag['Id']]);
    }));

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
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions to create tags');
    }
    $tagName = is_scalar($_REQUEST['tname'] ?? null) ? trim($_REQUEST['tname']) : '';
    if ($tagName === '') ajaxError('createtag requires a tag name');
    if (mb_strlen($tagName, 'UTF-8') > 64) ajaxError('Tag names are limited to 64 characters');

    // Tag names differing only in case would look the same in the tag list, so reuse the existing tag.
    $dbFetchResult = dbFetchAll('SELECT * FROM Tags WHERE LOWER(Name) = LOWER(?) ORDER BY Name = ? DESC LIMIT 1', NULL, array($tagName, $tagName));
    if (!count($dbFetchResult)) {
      $sql = 'INSERT INTO Tags (Name, CreatedBy) VALUES (?, ?)';
      $values = array($tagName, $user->Id());
      if (!dbQuery($sql, $values)) ajaxError('Failed to create tag');

      $sql = 'SELECT * FROM Tags WHERE Id = ?';
      $values = array(dbInsertId());
      $dbFetchResult = dbFetchAll($sql, NULL, $values);
    }

    ajaxResponse(array('response'=>$dbFetchResult));
    break;
  case 'gettags' :
    // Every tag with the number of events it is on, counting only events on
    // monitors the user can see. A tag whose events are all hidden from this
    // user is left out, as a hidden event lists none of its tags.
    $counts = array();
    $used = array();
    foreach (dbFetchAll('SELECT ET.TagId, E.MonitorId, COUNT(*) AS Events FROM Events_Tags AS ET INNER JOIN Events AS E ON E.Id = ET.EventId GROUP BY ET.TagId, E.MonitorId') as $row) {
      $used[$row['TagId']] = true;
      if (visibleMonitor($row['MonitorId'])) {
        $counts[$row['TagId']] = ($counts[$row['TagId']] ?? 0) + $row['Events'];
      }
    }
    $tags = array();
    foreach (dbFetchAll('SELECT Id, Name, CreateDate, LastAssignedDate FROM Tags ORDER BY Name') as $tag) {
      $tag['Events'] = $counts[$tag['Id']] ?? 0;
      if (!$tag['Events'] and isset($used[$tag['Id']])) continue;
      $tags[] = $tag;
    }
    ajaxResponse(array('response'=>$tags));
    break;
  case 'renametag' :
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions to rename tags');
    }
    $tagId = is_scalar($_REQUEST['tid'] ?? null) ? validCardinal($_REQUEST['tid']) : '';
    $tagName = is_scalar($_REQUEST['tname'] ?? null) ? trim($_REQUEST['tname']) : '';
    if ($tagId === '' or $tagName === '') ajaxError('renametag requires a numeric tid and a tag name');
    if (mb_strlen($tagName, 'UTF-8') > 64) ajaxError('Tag names are limited to 64 characters');
    if (!dbFetchOne('SELECT Id FROM Tags WHERE Id = ?', 'Id', array($tagId))) ajaxError('No such tag');
    // Tag names are shared, so don't let a rename reach events on monitors this user can't see.
    foreach (dbFetchAll('SELECT DISTINCT E.MonitorId FROM Events_Tags AS ET INNER JOIN Events AS E ON E.Id = ET.EventId WHERE ET.TagId = ?', NULL, array($tagId)) as $row) {
      if (!visibleMonitor($row['MonitorId'])) ajaxError('The tag is on events you cannot see and cannot be renamed');
    }
    if (dbFetchOne('SELECT Id FROM Tags WHERE LOWER(Name) = LOWER(?) AND Id != ?', 'Id', array($tagName, $tagId))) {
      ajaxError('A tag named '.validHtmlStr($tagName).' already exists');
    }
    if (!dbQuery('UPDATE Tags SET Name = ? WHERE Id = ?', array($tagName, $tagId))) ajaxError('Failed to rename tag');
    ajaxResponse();
    break;
  case 'deletetag' :
    // Only a tag that is on no event at all, whether the user can see it or not.
    if (!canEdit('Events')) {
      ajaxError('Insufficient permissions to delete tags');
    }
    $tagId = is_scalar($_REQUEST['tid'] ?? null) ? validCardinal($_REQUEST['tid']) : '';
    if ($tagId === '') ajaxError('deletetag requires a numeric tid');
    if (dbFetchOne('SELECT TagId FROM Events_Tags WHERE TagId = ? LIMIT 1', 'TagId', array($tagId))) {
      ajaxError('The tag is still on events and cannot be deleted');
    }
    if (!dbQuery('DELETE FROM Tags WHERE Id = ?', array($tagId))) ajaxError('Failed to delete tag');
    ajaxResponse();
    break;
} // end switch action

ajaxError('Unrecognised action '.$_REQUEST['action']);
?>
