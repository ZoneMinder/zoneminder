<?php
ini_set('display_errors', '0');

if ( canView('Events') or canView('Snapshots') ) {
  switch ( $_REQUEST['action'] ) {
  case 'video' :
    if (empty($_REQUEST['videoFormat'])) {
      ajaxError('Video Generation Failure, no format given');
    } else if (empty($_REQUEST['rate'])) {
      ajaxError('Video Generation Failure, no rate given');
    } else if (empty($_REQUEST['scale'])) {
      ajaxError('Video Generation Failure, no scale given');
    } else {
      $sql = '
      SELECT E.*, M.Name AS MonitorName,M.DefaultRate,M.DefaultScale, GROUP_CONCAT(T.Name SEPARATOR ", ") AS Tags
      FROM Events AS E
      INNER JOIN Monitors AS M ON E.MonitorId = M.Id
      LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId
      LEFT JOIN Tags AS T ON T.Id = ET.TagId
      WHERE E.Id = ?'.monitorLimitSql().' GROUP BY E.Id';
      require_once('includes/Event.php');
      // The monitor limit used to follow GROUP BY, where it only changed the
      // grouping; check the event itself too, as the other event actions do.
      if (!($event = dbFetchOne($sql, NULL, array( $_REQUEST['id'])))
        or !($Event = new ZM\Event($event))->canView()) {
        ajaxError('Video Generation Failure, Unable to load event');
      } else {
        if ( $videoFile = $Event->createVideo( $_REQUEST['videoFormat'], $_REQUEST['rate'], $_REQUEST['scale'], $_REQUEST['transform'], !empty($_REQUEST['overwrite'])) ) {
          ajaxResponse(array('response'=>$videoFile));
        } else {
          ajaxError('Video Generation Failed');
        }
      }
    }
    $ok = true;
    break;
  case 'export' :
    require_once(ZM_SKIN_PATH.'/includes/export_functions.php');

    # We use session vars in here, so we need to restart the session
    # because we stopped it in index.php to improve concurrency.
    zm_session_start();

    $exportDetail = !empty($_REQUEST['exportDetail']) ? $_REQUEST['exportDetail'] : false;
    $_SESSION['export']['detail'] = $exportDetail;

    $exportFrames = !empty($_REQUEST['exportFrames']) ? $_REQUEST['exportFrames'] : false;
    $_SESSION['export']['frames'] = $exportFrames;

    $exportImages = !empty($_REQUEST['exportImages']) ? $_REQUEST['exportImages'] : false;
    $_SESSION['export']['images'] = $exportImages;

    $exportVideo = !empty($_REQUEST['exportVideo']) ? $_REQUEST['exportVideo'] : false;
    $_SESSION['export']['video'] = $exportVideo;

    $exportMisc = !empty($_REQUEST['exportMisc']) ? $_REQUEST['exportMisc'] : false;
    $_SESSION['export']['misc'] = $exportMisc;

    if ( !empty($_REQUEST['exportFormat']) )
      $exportFormat = $_SESSION['export']['format'] = $_REQUEST['exportFormat'];
    else
      $exportFormat = '';

    $exportCompress = !empty($_REQUEST['exportCompress']) ? $_REQUEST['exportCompress'] : false;
    $_SESSION['export']['compress'] = $exportCompress;

    if ( !empty($_REQUEST['exportStructure']) )
      $exportStructure = $_SESSION['export']['structure'] = $_REQUEST['exportStructure'];
    else
      $exportStructure = false;

    session_write_close();

    $exportIds = [];
    if (!empty($_REQUEST['eids'])) {
      $exportIds = array_map(function($eid) {return validCardinal($eid);}, (array)$_REQUEST['eids']);
    } else if (isset($_REQUEST['id'])) {
      $exportIds = [validCardinal($_REQUEST['id'])];
    }

    $exportRoot = !empty($_REQUEST['exportFile']) ? preg_replace('/[^\w\-.]/', '', $_REQUEST['exportFile']) : '';
    if (empty($exportRoot)) $exportRoot = 'zmExport';
    $exportConnkey = preg_replace('/[^\w\-.]/', '', isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : '');

    if ($exportFile = exportEvents(
      $exportIds,
      $exportConnkey,
      $exportDetail,
      $exportFrames,
      $exportImages,
      $exportVideo,
      $exportMisc,
      $exportFormat,
      $exportCompress,
      $exportStructure,
      $exportRoot
    )) {
      ajaxResponse(array('exportFile'=>$exportFile));
    } else {
      ajaxError('Export Failed');
    }
    break;
  case 'download' :
    require_once('includes/download_functions.php');
    $exportFormat = (isset($_REQUEST['exportFormat']) and in_array($_REQUEST['exportFormat'], ['tar', 'zip', 'noArchive'], true)) ? $_REQUEST['exportFormat'] : 'zip';
    $exportConnkey = preg_replace('/[^\w\-.]/', '', isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : '');
    $exportFileName = isset($_REQUEST['exportFileName']) ? $_REQUEST['exportFileName'] : '';

    if (!$exportFileName) $exportFileName = 'Export'.$exportConnkey;
    $exportFileName = preg_replace('/[^\p{L}\p{N}\-\.\(\)]/u', '', $exportFileName);

    $exportIds = [];
    if (!empty($_REQUEST['eids'])) {
      $exportIds = array_map(function($eid) {return validCardinal($eid);}, (array)$_REQUEST['eids']);
    } else if (isset($_REQUEST['id'])) {
      $exportIds = [validCardinal($_REQUEST['id'])];
    }
    ZM\Debug("Export IDS". print_r($exportIds, true));

    $filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : null;
    if ($filter and !count($exportIds)) {
      $eventsSql = 'SELECT E.Id FROM Events AS E WHERE ';
      $eventsSql .= $filter->sql();
      $results = dbQuery($eventsSql);
      while ($event_row = dbFetchNext($results)) {
        $exportIds[] = $event_row['Id'];
      }
    } else {
      ZM\Debug("No filter");
    }

    if ( $exportFile = downloadEvents(
      $exportIds,
      $exportFileName,
      $exportFormat,
      false#,#Compress
    ) ) {
    ajaxResponse(array(
      'exportFile'=>$exportFile,
      'exportFormat'=>$exportFormat,
      'connkey'=>$exportConnkey
    ));

    } else {
      ajaxError('Download generation Failed');
    }
    break;
  case 'getselectedtags' :
    // Tag names of events the user can see only; a hidden event lists none, as a
    // missing one does.
    $eventId = is_scalar($_REQUEST['id'] ?? null) ? validCardinal($_REQUEST['id']) : '';
    require_once('includes/Event.php');
    $Event = ($eventId === '') ? null : new ZM\Event($eventId);
    if (!$Event or !$Event->Id() or !visibleMonitor($Event->MonitorId())) ajaxResponse(array('response'=>array()));
    $sql = '
      SELECT 
        T.* 
      FROM Tags 
        AS T 
      INNER JOIN Events_Tags 
        AS ET 
        ON ET.TagId = T.Id 
      WHERE ET.EventId = ?
    ';
    $values = array($Event->Id());
    $response = dbFetchAll($sql, NULL, $values);
    ajaxResponse(array('response'=>$response));
    break;
  case 'file_existence_check' :
    require_once('includes/download_functions.php');
    $fileNameArray = $_REQUEST['file_name_array'] ?? [];
    if (!is_array($fileNameArray)) ajaxError('The "file_name_array" request parameter is not an array');
    if (count($fileNameArray) > 100) ajaxError('Too many files requested');
    $result = [];
    foreach ($fileNameArray as $fileName) {
      if (!is_string($fileName) || $fileName === '' || strlen($fileName) > 4096 || strpos($fileName, "\0") !== false) continue;

      // Only allow relative paths under DIR_EXPORTS_DOWNLOAD; reject traversal/absolute paths.
      $fileName = ltrim($fileName, "/\\");
      if (preg_match('#(^|[\\/])\.\.([\\/]|$)#', $fileName)) continue;
      $path = DIR_EXPORTS_DOWNLOAD . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileName);
      $first = explode(DIRECTORY_SEPARATOR, str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileName))[0];
      $result[] = [$fileName, exportNameBelongsToUser($first) and file_exists($path)];
    }
    ajaxResponse(array('response'=>$result));
    break;
  }
} // end if canView('Events')

if ( canEdit('Events') ) {
  // Every action below changes or deletes one event. Events=Edit is per area, so
  // also require that the event's monitor is one the user can view, as the web
  // action (actions/event.php) and the API do; a missing and a hidden event get
  // the same answer. $_REQUEST values can be arrays, so non-scalars are rejected.
  $editActions = array('rename', 'eventdetail', 'archive', 'unarchive', 'addtag', 'removetag', 'delete');
  if (in_array($_REQUEST['action'] ?? null, $editActions, true)) {
    $eventId = is_scalar($_REQUEST['id'] ?? null) ? validCardinal($_REQUEST['id']) : '';
    require_once('includes/Event.php');
    $Event = ($eventId === '') ? null : new ZM\Event($eventId);
    if (!$Event or !$Event->Id() or !visibleMonitor($Event->MonitorId())) {
      if ($_REQUEST['action'] == 'delete')
        ajaxResponse(array('refreshEvent'=>false, 'refreshParent'=>true, 'message'=> 'Event not found.'));
      ajaxError('No such event');
    }
  }

  switch ( $_REQUEST['action'] ) {
  case 'rename' :
    // Events.Name is varchar(64), so reject anything that would not fit (in
    // characters, not bytes) rather than let strict SQL mode fail the UPDATE
    // silently and still report success.
    $eventName = is_scalar($_REQUEST['eventName'] ?? null) ? (string)$_REQUEST['eventName'] : '';
    if (trim($eventName) === '')
      ajaxError('No new event name supplied');
    if (function_exists('mb_strlen') ? mb_strlen($eventName, 'UTF-8') > 64 : strlen($eventName) > 64)
      ajaxError('Event name must be 64 characters or fewer');
    if (!dbQuery('UPDATE Events SET Name = ? WHERE Id = ?', array($eventName, $Event->Id())))
      ajaxError('Failed to rename event');
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'eventdetail' :
    $newEvent = (isset($_REQUEST['newEvent']) and is_array($_REQUEST['newEvent'])) ? $_REQUEST['newEvent'] : null;
    if (!$newEvent or !array_key_exists('Cause', $newEvent) or !array_key_exists('Notes', $newEvent)
      or !is_scalar($newEvent['Cause']) or !is_scalar($newEvent['Notes']))
      ajaxError('No event detail supplied');
    if (!dbQuery(
      'UPDATE Events SET Cause = ?, Notes = ? WHERE Id = ?',
      array($newEvent['Cause'], $newEvent['Notes'], $Event->Id())
    ))
      ajaxError('Failed to save event detail');
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'archive' :
  case 'unarchive' :
    $archiveVal = ($_REQUEST['action'] == 'archive')?1:0;
    if (!dbQuery(
      'UPDATE Events SET Archived = ? WHERE Id = ?',
      array($archiveVal, $Event->Id())
    ))
      ajaxError('Failed to '.$_REQUEST['action'].' event');
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>false));
    break;
  // Tagging changes the event's metadata, and filters select events by tag (a
  // purge filter can keep tagged events, or delete them), so it needs the same
  // Events edit permission as rename/archive. removetag also deletes the Tag
  // once no event uses it.
  case 'addtag' :
    // Validate once and reuse; $_REQUEST values are strings at best and can be
    // arrays, so anything non-scalar is rejected outright.
    // ($Event, the event on a visible monitor, was checked above.)
    $tagId = is_scalar($_REQUEST['tid'] ?? null) ? validCardinal($_REQUEST['tid']) : '';
    if ($tagId === '') ajaxError('addtag requires a numeric tid and id');
    $eventId = $Event->Id();

    if (!dbFetchOne('SELECT Id FROM Tags WHERE Id = ?', 'Id', array($tagId))) ajaxError('No such tag');

    $sql = 'INSERT IGNORE INTO Events_Tags (TagId, EventId, AssignedBy) VALUES (?, ?, ?)';
    $values = array($tagId, $eventId, $user->Id());
    if (!dbQuery($sql, $values)) ajaxError('Failed to add tag');
    $response = array();

    $sql = 'UPDATE Tags SET LastAssignedDate = NOW() WHERE Id = ?';
    $values = array($tagId);
    if (!dbQuery($sql, $values)) ajaxError('Failed to add tag');

    // Record this tag against the user's personal recency order so it
    // sorts to the top of THEIR dropdown next time, on any device.
    require_once('includes/TagOrder.php');
    \ZM\TagOrder::recordUsage($user->Id(), $tagId);

    ajaxResponse(array('response'=>$response));
    break;
  case 'removetag' :
    $tagId = is_scalar($_REQUEST['tid'] ?? null) ? validCardinal($_REQUEST['tid']) : '';
    if ($tagId === '') ajaxError('removetag requires a numeric tid and id');
    $eventId = $Event->Id();
    if (!dbQuery('DELETE FROM Events_Tags WHERE TagId = ? AND EventId = ?', array($tagId, $eventId)))
      ajaxError('Failed to remove tag');
    $rowCount = dbNumRows('SELECT * FROM Events_Tags WHERE TagId=?', [ $tagId ]);
    if ($rowCount < 1) {
      $response = dbNumRows('DELETE FROM Tags WHERE Id=?', [$tagId]);
      ajaxResponse(array('response'=>$response));
    }
    ajaxResponse();
    break;
  case 'delete' :
    if ($Event->delete()) {
      ajaxResponse(array('refreshEvent'=>false, 'refreshParent'=>true));
    } else {
      ajaxError('Event '.$Event->Id().' was not deleted' . ($Event->Archived() ? ': it is archived' : ''));
    }
    break;
  } // end switch action
} // end if canEdit('Events')

ajaxError('Unrecognised action '.validHtmlStr($_REQUEST['action']).' or insufficient permissions for user '.validHtmlStr($user->Username()));
?>
