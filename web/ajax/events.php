<?php

$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if ( !canEdit('Events') ) $message = 'Insufficient permissions for user '.$user['Username'];

if ( empty($_REQUEST['task']) ) {
  $message = 'Must specify a task';
} else {
  $task = $_REQUEST['task'];
}

if ( empty($_REQUEST['eids']) ) {
  if ( isset($_REQUEST['task']) && $_REQUEST['task'] != "query" ) $message = 'No event id(s) supplied';
} else {
  $eids = $_REQUEST['eids'];
}

if ( $message ) {
  ajaxError($message);
  return;
}

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['filter']) ? json_decode($_REQUEST['filter'], JSON_OBJECT_AS_ARRAY) : array();

// Sort specifies the name of the column to sort on
$sort = 'StartTime';
if ( isset($_REQUEST['sort']) ) {
  $sort = $_REQUEST['sort'];
}

// Offset specifies the starting row to return, used for pagination
$offset = 0;
if ( isset($_REQUEST['offset']) ) {
  if ( ( !is_int($_REQUEST['offset']) and !ctype_digit($_REQUEST['offset']) ) ) {
    ZM\Error('Invalid value for offset: ' . $_REQUEST['offset']);
  } else {
    $offset = $_REQUEST['offset'];
  }
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

// Limit specifies the number of rows to return
$limit = 100;
if ( isset($_REQUEST['limit']) ) {
  if ( ( !is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']) ) ) {
    ZM\Error('Invalid value for limit: ' . $_REQUEST['limit']);
  } else {
    $limit = $_REQUEST['limit'];
  }
}

//
// MAIN LOOP
//

switch ( $task ) {
  case 'archive' :
  case 'unarchive' :
    foreach ( $eids as $eid ) archiveRequest($task, $eid);
    break;
  case 'delete' :
    foreach ( $eids as $eid ) $data[] = deleteRequest($eid);
    break;
  case 'query' :
    $data = queryRequest($search, $advsearch, $sort, $offset, $order, $limit);
    break;
  default :
    ZM\Fatal("Unrecognised task '$task'");
} // end switch task

ajaxResponse($data);

//
// FUNCTION DEFINITIONS
//

function archiveRequest($task, $eid) {
  $archiveVal = ($task == 'archive') ? 1 : 0;
  dbQuery(
    'UPDATE Events SET Archived = ? WHERE Id = ?',
    array($archiveVal, $eid)
  );
}

function deleteRequest($eid) {
  $message = array();
  $event = new ZM\Event($eid);
  if ( !$event->Id() ) {
    $message[] = array($eid=>'Event not found.');
  } else if ( $event->Archived() ) {
    $message[] = array($eid=>'Event is archived, cannot delete it.');
  } else {
    $event->delete();
  }
  
  return $message;
}

function queryRequest($search, $advsearch, $sort, $offset, $order, $limit) {
  // Put server pagination code here
  // The table we want our data from
  $table = 'Events';

  // The names of the dB columns in the log table we are interested in
  $columns = array('Id', 'MonitorId', 'StorageId', 'Name', 'Cause', 'StartTime', 'EndTime', 'Length', 'Frames', 'AlarmFrames', 'TotScore', 'AvgScore', 'MaxScore', 'Archived', 'Emailed', 'Notes', 'DiskSpace');

  // The names of columns shown in the event view that are NOT dB columns in the database
  $col_alt = array('Monitor', 'Storage');

  if ( !in_array($sort, array_merge($columns, $col_alt)) ) {
    ZM\Fatal('Invalid sort field: ' . $sort);
  }

  $col_str = implode(', ', $columns);
  $data = array();
  $query = array();
  $query['values'] = array();
  $likes = array();
  $where = '';
  // There are two search bars in the log view, normal and advanced
  // Making an exuctive decision to ignore the normal search, when advanced search is in use
  // Alternatively we could try to do both
  if ( count($advsearch) ) {

    foreach ( $advsearch as $col=>$text ) {
      if ( !in_array($col, array_merge($columns, $col_alt)) ) {
        ZM\Error("'$col' is not a sortable column name");
        continue;
      }
      $text = '%' .$text. '%';
      array_push($likes, $col.' LIKE ?');
      array_push($query['values'], $text);
    }
    $wherevalues = $query['values'];
    $where = ' WHERE (' .implode(' OR ', $likes). ')';

  } else if ( $search != '' ) {

    $search = '%' .$search. '%';
    foreach ( $columns as $col ) {
      array_push($likes, $col.' LIKE ?');
      array_push($query['values'], $search);
    }
    $wherevalues = $query['values'];
    $where = ' WHERE (' .implode(' OR ', $likes). ')';
  }  

  $query['sql'] = 'SELECT ' .$col_str. ' FROM `' .$table. '` ' .$where. ' ORDER BY ' .$sort. ' ' .$order. ' LIMIT ?, ?';
  array_push($query['values'], $offset, $limit);

  ZM\Warning('Calling the following sql query: ' .$query['sql']);

  $data['totalNotFiltered'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table, 'Total');
  if ( $search != '' || count($advsearch) ) {
    $data['total'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table.$where , 'Total', $wherevalues);
  } else {
    $data['total'] = $data['totalNotFiltered'];
  }

  $storage_areas = ZM\Storage::find();
  $StorageById = array();
  foreach ( $storage_areas as $S ) {
    $StorageById[$S->Id()] = $S;
  }

  $monitor_names = ZM\Monitor::find();
  $MonitorById = array();
  foreach ( $monitor_names as $S ) {
    $MonitorById[$S->Id()] = $S;
  }

  $rows = array();
  foreach ( dbFetchAll($query['sql'], NULL, $query['values']) as $row ) {
    $event = new ZM\Event($row['Id']);
    $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
    $imgSrc = $event->getThumbnailSrc(array(),'&amp;');
    $streamSrc = $event->getStreamSrc(array(
                        'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

    // Modify the row data as needed
    $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="' .validHtmlStr('Event ' .$event->Id()). '" style="width:' .validInt($event->ThumbnailWidth()). 'px;height:' .validInt($event->ThumbnailHeight()).'px;" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '"/>';
    $row['Name'] = validHtmlStr($row['Name']);
    $row['Archived'] = $row['Archived'] ? translate('Yes') : translate('No');
    $row['Emailed'] = $row['Emailed'] ? translate('Yes') : translate('No');
    $row['Monitor'] = ( $row['MonitorId'] and isset($MonitorById[$row['MonitorId']]) ) ? $MonitorById[$row['MonitorId']]->Name() : '';
    $row['Cause'] = validHtmlStr($row['Cause']);
    $row['StartTime'] = strftime(STRF_FMT_DATETIME_SHORTER, strtotime($row['StartTime']));
    $row['EndTime'] = strftime(STRF_FMT_DATETIME_SHORTER, strtotime($row['StartTime']));
    $row['Length'] = gmdate('H:i:s', $row['Length'] );
    $row['Storage'] = ( $row['StorageId'] and isset($StorageById[$row['StorageId']]) ) ? $StorageById[$row['StorageId']]->Name() : 'Default';
    $row['Notes'] = htmlspecialchars($row['Notes']);
    $row['DiskSpace'] = human_filesize($row['DiskSpace']);
    $rows[] = $row;
  }
  $data['rows'] = $rows;
  $data['updated'] = preg_match('/%/', DATE_FMT_CONSOLE_LONG) ? strftime(DATE_FMT_CONSOLE_LONG) : date(DATE_FMT_CONSOLE_LONG);

  return $data;
}
?>
