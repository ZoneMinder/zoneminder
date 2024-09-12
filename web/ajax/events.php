<?php
ini_set('display_errors', '');
$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if (!canView('Events'))
  $message = 'Insufficient permissions for user '.$user['Username'].'<br/>';

if (empty($_REQUEST['task'])) {
  $message = 'Must specify a task<br/>';
} else {
  $task = $_REQUEST['task'];
}

if (empty($_REQUEST['eids'])) {
  if (isset($_REQUEST['task']) && $_REQUEST['task'] != 'query')
    $message = 'No event id(s) supplied<br/>';
} else {
  $eids = $_REQUEST['eids'];
}

if ($message) {
  ajaxError($message);
  return;
}

require_once('includes/Filter.php');
$filter = isset($_REQUEST['filter']) ? ZM\Filter::parse($_REQUEST['filter']) : new ZM\Filter();
if ($user['MonitorIds']) {
  $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user['MonitorIds']));
}

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['advsearch']) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();

// Order specifies the sort direction, either asc or desc
$order = $filter->sort_asc() ? 'ASC' : 'DESC';
if (isset($_REQUEST['order'])) {
  if (strtolower($_REQUEST['order']) == 'asc') {
    $order = 'ASC';
  } else if (strtolower($_REQUEST['order']) == 'desc') {
    $order = 'DESC';
  } else {
    Warning('Invalid value for order ' . $_REQUEST['order']);
  }
}

// Sort specifies the name of the column to sort on
$sort = $filter->sort_field();
if (isset($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
  if ($sort == 'EndDateTime') {
    if ($order == 'ASC') {
      $sort = 'EndDateTime IS NULL, EndDateTime';
    } else {
      $sort = 'EndDateTime IS NOT NULL, EndDateTime';
    }
  }
}

// Offset specifies the starting row to return, used for pagination
$offset = 0;
if (isset($_REQUEST['offset'])) {
  if ((!is_int($_REQUEST['offset']) and !ctype_digit($_REQUEST['offset']))) {
    ZM\Error('Invalid value for offset: ' . $_REQUEST['offset']);
  } else {
    $offset = $_REQUEST['offset'];
  }
}

// Limit specifies the number of rows to return
// Set the default to 0 for events view, to prevent an issue with ALL pagination
$limit = 0;
if (isset($_REQUEST['limit'])) {
  if ((!is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']))) {
    ZM\Error('Invalid value for limit: ' . $_REQUEST['limit']);
  } else {
    $limit = $_REQUEST['limit'];
  }
}

//
// MAIN LOOP
//

switch ($task) {
  case 'archive' :
    foreach ($eids as $eid) archiveRequest($task, $eid);
    break;
  case 'unarchive' :
		# The idea is that anyone can archive, but only people with Event Edit permission can unarchive..
		if (!canEdit('Events'))  {
			ajaxError('Insufficient permissions for user '.$user['Username']);
			return;
		}
    foreach ($eids as $eid) archiveRequest($task, $eid);
    break;
  case 'delete' :
		if (!canEdit('Events'))  {
			ajaxError('Insufficient permissions for user '.$user['Username']);
			return;
		}
    foreach ($eids as $eid) {
      $message = deleteRequest($eid);
      if (count($message)) {
        $data[] = $message;
      }
    }
    break;
  case 'query' :
    $data = queryRequest($filter, $search, $advsearch, $sort, $offset, $order, $limit);
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
  } else if (!$event->canEdit()) {
    $message[] = array($eid=>'You do not have permission to delete event '.$event->Id());
  } else {
    $event->delete();
  }
  
  return $message;
}

function queryRequest($filter, $search, $advsearch, $sort, $offset, $order, $limit) {
  global $dateTimeFormatter;
  $data = array(
    'total'   =>  0,
    'totalNotFiltered' => 0,
    'rows'    =>  array(),
    'updated' =>  $dateTimeFormatter->format(time())
  );

  if (!$filter->test_pre_sql_conditions()) {
    ZM\Debug('Pre conditions failed, not doing sql');
    return $data;
  }

  // Put server pagination code here
  // The table we want our data from
  $table = 'Events';

  // The names of the dB columns in the events table we are interested in
  $columns = array('Id', 'MonitorId', 'StorageId', 'Name', 'Cause', 'StartDateTime', 'EndDateTime', 'Length', 'Frames', 'AlarmFrames', 'TotScore', 'AvgScore', 'MaxScore', 'Archived', 'Emailed', 'Notes', 'DiskSpace');

  // The names of columns shown in the event view that are NOT dB columns in the database
  $col_alt = array('Monitor', 'MonitorName', 'Storage');

  if ( $sort != '' ) {
    if (!in_array($sort, array_merge($columns, $col_alt))) {
      ZM\Error('Invalid sort field: ' . $sort);
      $sort = '';
    } else if ( $sort == 'Monitor' or $sort == 'MonitorName' ) {
      $sort = 'M.Name';
    } else {
      $sort = 'E.'.$sort;
    }
  }

  $values = array();
  $likes = array();
  $where = $filter->sql()?' WHERE ('.$filter->sql().')' : '';

  $col_str = 'E.*, M.Name AS Monitor';
  $sql = 'SELECT ' .$col_str. ' FROM `Events` AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id'.$where.($sort?' ORDER BY '.$sort.' '.$order:'');
  if ($filter->limit() and !count($filter->post_sql_conditions())) {
    $sql .= ' LIMIT '.$filter->limit();
  }

  $storage_areas = ZM\Storage::find();
  $StorageById = array();
  foreach ($storage_areas as $S) {
    $StorageById[$S->Id()] = $S;
  }

  $unfiltered_rows = array();
  $event_ids = array();

  ZM\Debug('Calling the following sql query: ' .$sql);
  $query = dbQuery($sql, $values);
  if (!$query) {
    ajaxError(dbError($sql));
    return;
  }
  while ($row = dbFetchNext($query)) {
    $event = new ZM\Event($row);
    $event->remove_from_cache();
    if (!$filter->test_post_sql_conditions($event)) {
      continue;
    }
    $event_ids[] = $event->Id();
    $unfiltered_rows[] = $row;
  } # end foreach row

  # Filter limits come before pagination limits.
  if ($filter->limit() and ($filter->limit() > count($unfiltered_rows))) {
    ZM\Debug("Filtering rows due to filter->limit " . count($unfiltered_rows)." limit: ".$filter->limit());
    $unfiltered_rows = array_slice($unfiltered_rows, 0, $filter->limit());
  }

  ZM\Debug('Have ' . count($unfiltered_rows) . ' events matching base filter.');

  $filtered_rows = null;

  if (count($advsearch) or $search != '') {
    $search_filter = new ZM\Filter();
    $search_filter = $search_filter->addTerm(array('cnj'=>'and', 'attr'=>'Id', 'op'=>'IN', 'val'=>$event_ids));

    // There are two search bars in the log view, normal and advanced
    // Making an exuctive decision to ignore the normal search, when advanced search is in use
    // Alternatively we could try to do both
    if (count($advsearch)) {
      $terms = array();
      foreach ($advsearch as $col=>$text) {
        $terms[] = array('cnj'=>'and', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$text);
      } # end foreach col in advsearch
      $terms[0]['obr'] = 1;
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter->addTerms($terms);
    } else if ($search != '') {
      $search = '%' .$search. '%';
      $terms = array();
      foreach ($columns as $col) {
        $terms[] = array('cnj'=>'or', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$search);
      }
      $terms[0]['obr'] = 1;
      $terms[0]['cnj'] = 'and';
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter = $search_filter->addTerms($terms, array('obr'=>1, 'cbr'=>1, 'op'=>'OR'));
    } # end if search

    $sql = 'SELECT ' .$col_str. ' FROM `Events` AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE '.$search_filter->sql().' ORDER BY ' .$sort. ' ' .$order;
    $filtered_rows = dbFetchAll($sql);
    ZM\Debug('Have ' . count($filtered_rows) . ' events matching search filter: '.$sql);
  } else {
    $filtered_rows = $unfiltered_rows;
  } # end if search_filter->terms() > 1

  if ($limit) {
    ZM\Debug("Filtering rows due to limit " . count($filtered_rows)." offset: $offset limit: $limit");
    $filtered_rows = array_slice($filtered_rows, $offset, $limit);
  }

  $returned_rows = array();
  foreach ($filtered_rows as $row) {
    $event = new ZM\Event($row);

    $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
    $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
    $streamSrc = $event->getStreamSrc(array(
      'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

    // Modify the row data as needed
    $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '" loading="lazy" />';
    $row['Name'] = validHtmlStr($row['Name']);
    $row['Archived'] = $row['Archived'] ? translate('Yes') : translate('No');
    $row['Emailed'] = $row['Emailed'] ? translate('Yes') : translate('No');
    $row['Cause'] = validHtmlStr($row['Cause']);
    $row['StartDateTime'] = $dateTimeFormatter->format(strtotime($row['StartDateTime']));
    $row['EndDateTime'] = $row['EndDateTime'] ? $dateTimeFormatter->format(strtotime($row['EndDateTime'])) : null;
    $row['Storage'] = ( $row['StorageId'] and isset($StorageById[$row['StorageId']]) ) ? $StorageById[$row['StorageId']]->Name() : 'Default';
    $row['Notes'] = nl2br(htmlspecialchars($row['Notes']));
    $row['DiskSpace'] = human_filesize($event->DiskSpace());
    $returned_rows[] = $row;
  } # end foreach row matching search

  $data['rows'] = $returned_rows;

  # totalNotFiltered must equal total, except when either search bar has been used
  $data['totalNotFiltered'] = count($unfiltered_rows);
  if ( $search != '' || count($advsearch) ) {
    $data['total'] = count($filtered_rows);
  } else {
    $data['total'] = $data['totalNotFiltered'];
  }

  return $data;
}
?>
