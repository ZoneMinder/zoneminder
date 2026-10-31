<?php
ini_set('display_errors', '0');

if ( empty($_REQUEST['id']) && empty($_REQUEST['eids']) ) {
  ajaxError('No event id(s) supplied');
}

if ( canView('Events') or canView('Snapshots') ) {
  switch ( $_REQUEST['action'] ) {
  case 'video' :
    if ( empty($_REQUEST['videoFormat']) ) {
      ajaxError('Video Generation Failure, no format given');
    } elseif ( empty($_REQUEST['rate']) ) {
      ajaxError('Video Generation Failure, no rate given');
    } elseif ( empty($_REQUEST['scale']) ) {
      ajaxError('Video Generation Failure, no scale given');
    } else {
      $sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?'.monitorLimitSql();
      require_once('includes/Event.php');
      // The per-monitor limit is already in the WHERE clause here; also check
      // the event itself, as the other event actions do, so a Snapshots-only
      // user cannot encode events outside their snapshots.
      if ( !($event = dbFetchOne($sql, NULL, array( $_REQUEST['id'])))
        or !($Event = new ZM\Event($event))->canView() ) {
        ajaxError('Video Generation Failure, Unable to load event');
      } else {
        if ( $videoFile = createVideo($event, $_REQUEST['videoFormat'], $_REQUEST['rate'], $_REQUEST['scale'], !empty($_REQUEST['overwrite'])) )
          ajaxResponse(array('response'=>$videoFile));
        else
          ajaxError('Video Generation Failed');
      }
    }
    $ok = true;
    break;
  case 'deleteVideo' :
    unlink($videoFiles[$_REQUEST['id']]);
    unset($videoFiles[$_REQUEST['id']]);
    ajaxResponse();
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

    $exportIds = !empty($_REQUEST['eids']) ? $_REQUEST['eids'] : $_REQUEST['id'];
    if ($exportFile = exportEvents(
      $exportIds,
      preg_replace('/[^\w\-.]/', '', isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : ''),
      $exportDetail,
      $exportFrames,
      $exportImages,
      $exportVideo,
      $exportMisc,
      $exportFormat,
      $exportCompress,
      $exportStructure,
      (!empty($_REQUEST['exportFile']) ? preg_replace('/[^\w\-.]/', '', $_REQUEST['exportFile']) : 'zmExport')
    )) {
      ajaxResponse(array('exportFile'=>$exportFile));
    } else {
      ajaxError('Export Failed');
    }
    break;
  case 'download' :
    require_once(ZM_SKIN_PATH.'/includes/export_functions.php');
    $exportVideo = 1;
    $exportFormat = $_REQUEST['exportFormat'];
    $exportStructure = 'flat';
    $exportIds = !empty($_REQUEST['eids'])?$_REQUEST['eids']:$_REQUEST['id'];
    if ( $exportFile = exportEvents(
      $exportIds,
      preg_replace('/[^\w\-.]/', '', isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : ''),
      false,#detail
      false,#frames
      false,#images
      true, #$exportVideo,
      false,#Misc
      $exportFormat,
      false#,#Compress
      #$exportStructure
    ) ) {
    ajaxResponse(array(
      'exportFile'=>$exportFile,
      'exportFormat'=>$exportFormat,
      'connkey'=>preg_replace('/[^\w\-.]/', '', isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : '')
    ));
    } else {
      ajaxError('Export Failed');
    }
    break;
  }
} // end if canView('Events')

if ( canEdit('Events') ) {
  // Every action below changes or deletes one event. Events=Edit is per area, so
  // also require that the event's monitor is one the user can view, as the web
  // action (actions/event.php) does; a missing and a hidden event get the same
  // answer. $_REQUEST values can be arrays, so non-scalars are rejected.
  $editActions = array('rename', 'eventdetail', 'archive', 'unarchive', 'delete');
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
    if ( !empty($_REQUEST['eventName']) and is_scalar($_REQUEST['eventName']) )
      dbQuery('UPDATE Events SET Name = ? WHERE Id = ?', array($_REQUEST['eventName'], $Event->Id()));
    else
      ajaxError('No new event name supplied');
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'eventdetail' :
    dbQuery(
      'UPDATE Events SET Cause = ?, Notes = ? WHERE Id = ?',
      array($_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $Event->Id())
    );
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>true));
    break;
  case 'archive' :
  case 'unarchive' :
    $archiveVal = ($_REQUEST['action'] == 'archive')?1:0;
    dbQuery(
      'UPDATE Events SET Archived = ? WHERE Id = ?',
      array($archiveVal, $Event->Id())
    );
    ajaxResponse(array('refreshEvent'=>true, 'refreshParent'=>false));
    break;
  case 'delete' :
    $Event->delete();
    ajaxResponse(array('refreshEvent'=>false, 'refreshParent'=>true));
    break;
  } // end switch action
} // end if canEdit('Events')

ajaxError('Unrecognised action '.$_REQUEST['action'].' or insufficient permissions for user '.$user['Username']);
?>
