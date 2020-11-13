<?php
$data = array();
$message = '';

//
// INITIALIZE AND CHECK SANITY
//

if ( !canView('Events') ) $message = 'Insufficient permissions to view frames for user '.$user['Username'];

// task must be set
if ( empty($_REQUEST['task']) ) {
  $message = 'This request requires a task to be set';
// query is the only supported task at the moment
} else if ( $_REQUEST['task'] != 'query' ) {
  $message = 'Unrecognised task '.$_REQUEST['task'];
}

if ( empty($_REQUEST['eid']) ) {
  $message = 'No event id supplied';
} else {
  $eid = validInt($_REQUEST['eid']);
}

if ( $message ) {
  ajaxError($message);
  return;
}

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['advsearch']) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();

// Sort specifies the name of the column to sort on
$sort = 'FrameId';
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

// Only one supported task at the moment
switch ( $task ) {
  case 'query' :
    $data = queryRequest($eid, $search, $advsearch, $sort, $offset, $order, $limit);
    break;
  default :
    ZM\Fatal("Unrecognised task '$task'");
} // end switch task

ajaxResponse($data);

//
// FUNCTION DEFINITIONS
//

function queryRequest($eid, $search, $advsearch, $sort, $offset, $order, $limit) {

  // The table we want our data from
  $table = 'Frames';

  // The names of the dB columns in the events table we are interested in
  $columns = array('EventId', 'FrameId', 'Type', 'TimeStamp', 'Delta', 'Score');

  if ( !in_array($sort, $columns) ) {
    ZM\Error('Invalid sort field: ' . $sort);
    $sort = 'FrameId';
  }

  $Event = new ZM\Event($eid);
  $Monitor = $Event->Monitor();
  $values = array();
  $likes = array();
  $where = 'EventId ='.$eid;

  // There are two search bars in the log view, normal and advanced
  // Making an exuctive decision to ignore the normal search, when advanced search is in use
  // Alternatively we could try to do both
  if ( count($advsearch) ) {

    foreach ( $advsearch as $col=>$text ) {
      if ( !in_array($col, array_merge($columns, $col_alt)) ) {
        ZM\Error("'$col' is not a searchable column name");
        continue;
      }
      // Don't use wildcards on advanced search
      //$text = '%' .$text. '%';
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

  $data['totalNotFiltered'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table, 'Total');
  if ( $search != '' || count($advsearch) ) {
    $data['total'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table.$where , 'Total', $wherevalues);
  } else {
    $data['total'] = $data['totalNotFiltered'];
  }

  $returned_rows = array();
  $results = dbFetchAll($query['sql'], NULL, $query['values']);
  if ( !$results ) {
    return $data;
  }
  
  foreach ( $results as $row ) {
      $base_img_src = '?view=image&amp;fid=' .$row['FrameId'];
			$ratio_factor = $Monitor->ViewHeight() / $Monitor->ViewWidth();
      $thmb_width = ZM_WEB_LIST_THUMB_WIDTH ? 'width='.ZM_WEB_LIST_THUMB_WIDTH : '';
      $thmb_height = 'height="'.( ZM_WEB_LIST_THUMB_HEIGHT ? ZM_WEB_LIST_THUMB_HEIGHT : ZM_WEB_LIST_THUMB_WIDTH*$ratio_factor ) .'"';
      $thmb_fn = 'filename=' .$Event->MonitorId(). '_' .$row['EventId']. '_' .$row['FrameId']. '.jpg';
      $img_src = join('&amp;', array_filter(array($base_img_src, $thmb_width, $thmb_height, $thmb_fn)));
      $full_img_src = join('&amp;', array_filter(array($base_img_src, $thmb_fn)));
      $frame_src = '?view=frame&amp;eid=' .$row['EventId']. '&amp;fid=' .$row['FrameId'];
      
      $row['imgHtml'] = '<td class="colThumbnail zoom"><img src="' .$img_src. '" '.$thmb_width. ' ' .$thmb_height. 'img_src="' .$img_src. '" full_img_src="' .$full_img_src. '"></td>'.PHP_EOL;
      $returned_rows[] = $row;
  }
  $data['rows'] = $returned_rows;

  return $data;
}
