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
$mid = (!empty($_REQUEST['mid']) and is_scalar($_REQUEST['mid'])) ? validCardinal($_REQUEST['mid']) : '';
if ( !$mid ) {
  $message = 'Must specify a monitor Id';
}

// Limit specifies the number of rows to return
if ( !isset($_REQUEST['limit']) or !is_scalar($_REQUEST['limit']) or (!is_int($_REQUEST['limit']) and !ctype_digit((string)$_REQUEST['limit'])) ) {
  $message = 'Invalid value for limit: ' . (isset($_REQUEST['limit']) && is_scalar($_REQUEST['limit']) ? $_REQUEST['limit'] : '');
} else {
  $limit = (int)$_REQUEST['limit'];
}

if ($message) {
  ZM\Error($message);
  ajaxError($message);
  return;
}

// Sort specifies the name of the column to sort on
$sort = 'Id';
if (isset($_REQUEST['sort']) and is_string($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and is_string($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

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

    $scale = $event->Width() ? intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width()) : 100;
    $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
    $streamSrc = $event->getStreamSrc(array(
      'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

    $videoAttr = '';
    if ($event->DefaultVideo()) {
      $videoSrc = $event->getStreamSrc(array('mode'=>'mp4'), '&amp;');
      $videoHlsSrc = $event->getStreamSrc(array('mode'=>'mp4hls'), '&amp;');
      $videoDuration = htmlspecialchars((string)$event->Length());
      $videoAttr = ' video_src="' .$videoSrc. '" data-video-hls-src="' .$videoHlsSrc. '" data-video-duration-secs="' .$videoDuration. '" data-event-start="'.htmlspecialchars($event->StartDateTime()).'"';
    }

    // Modify the row data as needed
    $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '"' .$videoAttr. ' data-monitor-width="'.$event->Width().'" data-monitor-height="'.$event->Height().'" loading="lazy" />';
    $row['Name'] = validHtmlStr($row['Name']);
    // Text, not markup, as in ajax/events.php
    $row['Cause'] = validHtmlStr($row['Cause']);
    $row['Tags'] = validHtmlStr($row['Tags']);
    $row['Notes'] = $row['Notes'] ? nl2br(htmlspecialchars($row['Notes'])) : '';
    // Not gmdate(): its time of day wraps at 24 hours, and events can run longer.
    $length = intval($row['Length']);
    $row['Length'] = sprintf('%02d:%02d:%02d', intdiv($length, 3600), intdiv($length, 60) % 60, $length % 60);

    $returned_rows[] = $row;
  } # end foreach row matching search
}

$data['rows'] = &$returned_rows;
ajaxResponse($data);
?>
