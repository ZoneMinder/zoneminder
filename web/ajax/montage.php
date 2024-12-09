<?php
// Загрузка или Live просмотр или в записи

$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//
if (!canView('Stream')) $message = 'Insufficient permissions for user '.$user->Username();

// Mode
if ( empty($_REQUEST['montage_mode']) ) {
  $message = 'Must specify a mode (Live or In recording)';
} else {
  $mode = validStr($_REQUEST['montage_mode']);
}

// Action
if ( empty($_REQUEST['montage_action']) ) {
  $message = 'Must specify a action.';
} else {
  $action = validStr($_REQUEST['montage_action']);
}

$startDateTime = null;
if ( !empty($_REQUEST['StartDateTime']) ) {
  $startDateTime = validStr($_REQUEST['StartDateTime']); //Date
}

$endDateTime = null;
if ( !empty($_REQUEST['EndDateTime']) ) {
  $endDateTime = validStr($_REQUEST['EndDateTime']); //Date
}

$resolution = 0;
if ( !empty($_REQUEST['Resolution']) ) {
  $resolution = validInt($_REQUEST['Resolution']); //The number of seconds between two adjacent pixels of the displayed Timeline
}

$maxFPS = null;
if ( !empty($_REQUEST['maxFPS']) ) {
  $maxFPS = validStr($_REQUEST['maxFPS']);
}

$monitorsId = [];
if ( !empty($_REQUEST['MonitorsId']) ) {
  $monitorsId = $_REQUEST['MonitorsId'];
}

$dateTime = '';
if ( !empty($_REQUEST['dateTime']) ) {
  $dateTime = validStr($_REQUEST['dateTime']);
}

if ($message) {
  ZM\Error($message);
  ajaxError($message);
  return;
}

require_once('includes/Filter.php');
require_once(getSkinFile('views/class/montage_class.php'));

global $basename;
$basename = "montage";

$Montage = new Skin\Montage();
$resultMonitorFilters = $Montage::$resultMonitorFilters;
//$filterbar = $resultMonitorFilters['html'];
$displayMonitors = $resultMonitorFilters['displayMonitors'];

$result = '';

if ($mode == 'Live') {
  if ($action == 'filters') {
    $result = &$resultMonitorFilters;
  } else if ($action == 'grid') {
    //$result['monitors'] = &$buildGridMonitorsLive();
    $result = $Montage::buildGridMonitorsLive();
  }
} else if ($mode == 'inRecording') {
  if ($action == 'filters') {
    $filter = $Montage::buildGlobalFilters ();
    $resultMonitorFilters += ['filter'=>$filter->simple_widget()];
    $result = &$resultMonitorFilters;
  } else if ($action == 'grid') {
    //$result['monitors'] = &buildGridMonitorsInRecords();
    $result = $Montage::buildGridMonitorsInRecords($dateTime);
  } else if ($action == 'queryEventsForTimeline') {
    require_once('includes/Filter.php');
    //$filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : new ZM\Filter();
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
    $result = $Montage::queryEvents($filter, $monitorsId, $startDateTime, $endDateTime, $resolution, $action, $actionRange = 'range', $maxFPS);
  } else if ($action == 'queryEventsForMonitor') {
    require_once('includes/Filter.php');
    //$filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : new ZM\Filter();
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
    $result = $Montage::queryEvents($filter, $monitorsId, $startDateTime, $endDateTime, $resolution, $action, $actionRange = 'range', $maxFPS);
  } else if ($action == 'queryNextEventForMonitor') {
    require_once('includes/Filter.php');
    //$filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : new ZM\Filter();
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
    $result = $Montage::queryEvents($filter, $monitorsId, $startDateTime, $endDateTime, $resolution, $action, $actionRange = 'next', $maxFPS);
  } else if ($action == 'getMinData') {
    require_once('includes/Filter.php');
    //$filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : new ZM\Filter();
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
    $result = $Montage::queryMinData($filter, $monitorsId);
  }
} else if ($mode == 'queryEvents') {
  //Часть кода перенесена из \usr\share\zoneminder\www\ajax\events.php
  //  //Строка запроса примерно такая: http://XXX/zm/index.php?view=events&page=1&filter[Query][terms][0][attr]=Monitor&filter[Query][terms][0][op]=%3D&filter[Query][terms][0][val]=5&filter[Query][sort_asc]=1&filter[Query][sort_field]=StartDateTime&filter[Query][skip_locked]=&filter[Query][limit]=0
  //  //В JS файл добавить типа function filterEvents() { из "skins\classic\views\js\events.js"
  //  $result = $Montage::queryRequest($filter, $search, $advsearch, $sort, $offset, $order, $limit);
}
ajaxResponse($result);

/*
// Mid specifies the monitor id we are searching on
if ( empty($_REQUEST['mid']) ) {
  $message = 'Must specify a monitor Id';
} else {
  $mid = validCardinal($_REQUEST['mid']);
}

// Limit specifies the number of rows to return
if ( ( !is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']) ) ) {
  $message = 'Invalid value for limit: '.$_REQUEST['limit'];
} else {
  $limit = $_REQUEST['limit'];
}

if ($message) {
  ZM\Error($message);
  ajaxError($message);
  return;
}

// Sort specifies the name of the column to sort on
$sort = 'Id';
if (isset($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

//
// MAIN LOOP
//

function getEvents ($mid, $limit, $sort) {
// The names of the dB columns in the events table we are interested in
$columns = array('Id', 'MonitorId', 'StorageId', 'Name', 'Cause', 'StartDateTime', 'EndDateTime', 'Length', 'Frames', 'AlarmFrames', 'TotScore', 'AvgScore', 'MaxScore', 'Archived', 'Emailed', 'Notes', 'DiskSpace');

if ( $sort != 'Id' ) {
  if (!in_array($sort, $columns)) {
    ZM\Error('Invalid sort field: ' . $sort);
    $sort = 'Id';
  } else if ($sort == 'EndDateTime') {
    if ($order == 'ASC') {
      $sort = 'E.EndDateTime IS NULL, E.EndDateTime';
    } else {
      $sort = 'E.EndDateTime IS NOT NULL, E.EndDateTime';
    }
  } else {
    $sort = 'E.'.$sort;
  }
}
$where = 'WHERE MonitorId = '.$mid;

$col_str = ' E.*, GROUP_CONCAT(T.Name SEPARATOR ", ") AS Tags ';

$sql = 'SELECT ' .$col_str. ' FROM `Events` AS E
LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId
LEFT JOIN Tags AS T ON T.Id = ET.TagId
'.$where.' GROUP BY E.Id ORDER BY '.$sort.' '.$order.' LIMIT ?';

$rows = dbQuery($sql, array($limit));
$returned_rows = array();

if ($rows) {
  foreach ( $rows as $row ) {
    $event = new ZM\Event($row);
    $event->remove_from_cache();
    if (!$event->canView()) continue;
    if ($event->Monitor()->Deleted()) continue;

    $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
    $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
    $streamSrc = $event->getStreamSrc(array(
      'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

    // Modify the row data as needed
    $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '" loading="lazy" />';
    $row['Name'] = validHtmlStr($row['Name']);
    $row['Length'] = gmdate('H:i:s', intval($row['Length']));

    $returned_rows[] = $row;
  } # end foreach row matching search
}

$data['rows'] = &$returned_rows;
return $data;
}


ajaxResponse(getEvents ($mid, $limit, $sort));

*/
?>
