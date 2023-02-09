<?php
//
// This is simplified version of the logic from ajax/events.php
// For efficency, sorting and and pagination functionality have been exlcuded
//

$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if ( !canView('Events') ) $message = 'Insufficient permissions for user '.$user['Username'];

// Mid specifies the monitor id we are searching on
if ( empty($_REQUEST['mid']) ) {
  $message = 'Must specify a monitor Id';
} else {
  $mid = $_REQUEST['mid'];
}

// Limit specifies the number of rows to return
if ( ( !is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']) ) ) {
  $message = 'Invalid value for limit: '.$_REQUEST['limit'];
} else {
  $limit = $_REQUEST['limit'];
}

if ( $message ) {
  ZM\Error($message);
  ajaxError($message);
  return;
}

// Sort specifies the name of the column to sort on
$sort = 'Id';
if ( isset($_REQUEST['sort']) ) {
  $sort = $_REQUEST['sort'];
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

//
// MAIN LOOP
//

$where = 'WHERE MonitorId = '.$mid;
$col_str = 'E.*';
$sql = 'SELECT ' .$col_str. ' FROM `Events` AS E '.$where.' ORDER BY '.$sort.' '.$order. ' LIMIT ?';
ZM\Debug('Calling the following sql query: ' .$sql);
$rows = dbQuery($sql, array($limit));

$returned_rows = array();
foreach ( $rows as $row ) {
  $event = new ZM\Event($row['Id']);

  $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
  $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
  $streamSrc = $event->getStreamSrc(array(
    'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

  // Modify the row data as needed
  $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '" loading="lazy" />';
  $row['Name'] = validHtmlStr($row['Name']);
  $row['StartDateTime'] = $dateTimeFormatter->format(strtotime($row['StartDateTime']));
    $row['EndDateTime'] = $row['EndDateTime'] ? $dateTimeFormatter->format(strtotime($row['EndDateTime'])) : null;
  $row['Length'] = gmdate('H:i:s', intval($row['Length']));

  $returned_rows[] = $row;
} # end foreach row matching search

$data['rows'] = $returned_rows;
ajaxResponse($data);
?>
