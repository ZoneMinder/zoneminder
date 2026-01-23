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
      WHERE E.Id = ? GROUP BY E.Id '.monitorLimitSql();
      if (!($event = dbFetchOne($sql, NULL, array( $_REQUEST['id'])))) {
        ajaxError('Video Generation Failure, Unable to load event');
      } else {
        require_once('includes/Event.php');
        $Event = new ZM\Event($event);

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

    if ( !empty($_REQUEST['exportDetail']) )
      $exportDetail = $_SESSION['export']['detail'] = $_REQUEST['exportDetail'];
    else
      $exportDetail = false;

    if ( !empty($_REQUEST['exportFrames']) )
      $exportFrames = $_SESSION['export']['frames'] = $_REQUEST['exportFrames'];
    else
      $exportFrames = false;

    if ( !empty($_REQUEST['exportImages']) )
      $exportImages = $_SESSION['export']['images'] = $_REQUEST['exportImages'];
    else
      $exportImages = false;

    if ( !empty($_REQUEST['exportVideo']) )
      $exportVideo = $_SESSION['export']['video'] = $_REQUEST['exportVideo'];
    else
      $exportVideo = false;

    if ( !empty($_REQUEST['exportMisc']) )
      $exportMisc = $_SESSION['export']['misc'] = $_REQUEST['exportMisc'];
    else
      $exportMisc = false;

    if ( !empty($_REQUEST['exportFormat']) )
      $exportFormat = $_SESSION['export']['format'] = $_REQUEST['exportFormat'];
    else
      $exportFormat = '';

    if ( !empty($_REQUEST['exportCompress']) )
      $exportCompress = $_SESSION['export']['compress'] = $_REQUEST['exportCompress'];
    else
      $exportCompress = false;

    if ( !empty($_REQUEST['exportStructure']) )
      $exportStructure = $_SESSION['export']['structure'] = $_REQUEST['exportStructure'];
    else
      $exportStructure = false;

    session_write_close();

    $exportIds = [];
    if (!empty($_REQUEST['eids'])) {
      $exportIds = array_map(function($eid) {return validCardinal($eid);}, $_REQUEST['eids']);
    } else if (isset($_REQUEST['id'])) {
      $exportIds = [validCardinal($_REQUEST['id'])];
    }

    if ($exportFile = exportEvents(
      $exportIds,
      (isset($_REQUEST['connkey'])?$_REQUEST['connkey']:''),
      $exportDetail,
      $exportFrames,
      $exportImages,
      $exportVideo,
      $exportMisc,
      $exportFormat,
      $exportCompress,
      $exportStructure,
      (!empty($_REQUEST['exportFile'])?$_REQUEST['exportFile']:'zmExport')
    )) {
      ajaxResponse(array('exportFile'=>$exportFile));
    } else {
      ajaxError('Export Failed');
    }
    break;
  case 'download' :
    require_once('includes/download_functions.php');
    $exportFormat = isset($_REQUEST['exportFormat']) ? $_REQUEST['exportFormat'] : 'zip';
    $exportFileName = isset($_REQUEST['exportFileName']) ? $_REQUEST['exportFileName'] : '';

    if (!$exportFileName) $exportFileName = 'Export'.(isset($_REQUEST['connkey'])?$_REQUEST['connkey']:'');
    $exportFileName = preg_replace('/[^\p{L}\p{N}\-\.\(\)]/u', '', $exportFileName);

    $exportIds = [];
    if (!empty($_REQUEST['eids'])) {
      $exportIds = array_map(function($eid) {return validCardinal($eid);}, $_REQUEST['eids']);
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
      'connkey'=>(isset($_REQUEST['connkey'])?$_REQUEST['connkey']:'')
    ));

    } else {
      ajaxError('Download generation Failed');
    }
    break;
  }
} // end if canView('Events')

if ( canEdit('Events') ) {
  switch ( $_REQUEST['action'] ) {
  case 'rename' :
    if ( !empty($_REQUEST['eventName']) )
      dbQuery('UPDATE Events SET Name = ? WHERE Id = ?', array($_REQUEST['eventName'], $_REQUEST['id']));
    else
      ajaxError('No new event name supplied');
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'eventdetail' :
    dbQuery(
      'UPDATE Events SET Cause = ?, Notes = ? WHERE Id = ?',
      array($_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $_REQUEST['id'])
    );
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'archive' :
  case 'unarchive' :
    $archiveVal = ($_REQUEST['action'] == 'archive')?1:0;
    dbQuery(
      'UPDATE Events SET Archived = ? WHERE Id = ?',
      array($archiveVal, $_REQUEST['id'])
    );
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>false));
    break;
  case 'delete' :
    $Event = new ZM\Event($_REQUEST['id']);
    if ( !$Event->Id() ) {
      ajaxResponse(array('refreshEvent'=>false, 'refreshParent'=>true, 'message'=> 'Event not found.'));
    } else {
      $Event->delete();
      ajaxResponse(array('refreshEvent'=>false, 'refreshParent'=>true));
    }
    break;
  case 'getselectedtags' :
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
    $values = array($_REQUEST['id']);
    $response = dbFetchAll($sql, NULL, $values);
    ajaxResponse(array('response'=>$response));
    break;
  case 'addtag' :
    $sql = 'INSERT INTO Events_Tags (TagId, EventId, AssignedBy) VALUES (?, ?, ?)';
    $values = array($_REQUEST['tid'], $_REQUEST['id'], $user->Id());
    $response = dbFetchAll($sql, NULL, $values);

    $sql = 'UPDATE Tags SET LastAssignedDate = NOW() WHERE Id = ?';
    $values = array($_REQUEST['tid']);
    dbFetchAll($sql, NULL, $values);

    ajaxResponse(array('response'=>$response));
    break;
  case 'removetag' :
    $tagId = validCardinal($_REQUEST['tid']);
    dbQuery('DELETE FROM Events_Tags WHERE TagId = ? AND EventId = ?', array($tagId, $_REQUEST['id']));
    $rowCount = dbNumRows('SELECT * FROM Events_Tags WHERE TagId=?', [ $tagId ]);
    if ($rowCount < 1) {
      $response = dbNumRows('DELETE FROM Tags WHERE Id=?', [$tagId]);
      ajaxResponse(array('response'=>$response));
    }
    ajaxResponse();
    break;
  } // end switch action
} // end if canEdit('Events')

ajaxError('Unrecognised action '.$_REQUEST['action'].' or insufficient permissions for user '.$user->Username());
?>
