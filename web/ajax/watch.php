<?php
//
// This is simplified version of the logic from ajax/events.php
// For efficiency, sorting and and pagination functionality have been excluded
//

$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if ( !canView('Events') ) $message = 'Insufficient permissions for user '.$user->Username();

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
ajaxResponse($data);
?>
