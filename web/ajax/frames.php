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
  } else {
  $task = $_REQUEST['task'];
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
$limit = 0;
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
  global $dateTimeFormatter;
  
  $data = array(
    'total'   =>  0,
    'totalNotFiltered' => 0,
    'rows'    =>  array(),
    'updated' =>  $dateTimeFormatter->format(time())
  );

  // The names of the dB columns in the events table we are interested in
  $columns = array('FrameId', 'Type', 'TimeStamp', 'Delta', 'Score');

  if ( !in_array($sort, $columns) ) {
    ZM\Error('Invalid sort field: ' . $sort);
    $sort = 'FrameId';
  }

  $Event = new ZM\Event($eid);
  $Monitor = $Event->Monitor();
  $values = array();
  $likes = array();
  $where = 'WHERE EventId = '.$eid;

  $sql = 'SELECT * FROM `Frames` '.$where.' ORDER BY '.$sort.' '.$order;

  //ZM\Debug('Calling the following sql query: ' .$sql);

  $unfiltered_rows = array();
  $frame_ids = array();
  require_once('includes/Frame.php');
  foreach ( dbFetchAll($sql, NULL, $values) as $row ) {
    $frame = new ZM\Frame($row);
    $frame_ids[] = $frame->Id();
    $unfiltered_rows[] = $row;
  }

  ZM\Debug('Have ' . count($unfiltered_rows) . ' frames matching base filter.');

  $filtered_rows = null;
  require_once('includes/Filter.php');
  if ( count($advsearch) or $search != '' ) {
    $search_filter = new ZM\Filter();
    $search_filter = $search_filter->addTerm(array('cnj'=>'and', 'attr'=>'FrameId', 'op'=>'IN', 'val'=>$frame_ids));

    // There are two search bars in the log view, normal and advanced
    // Making an exuctive decision to ignore the normal search, when advanced search is in use
    // Alternatively we could try to do both
    if ( count($advsearch) ) {
      $terms = array();
      foreach ( $advsearch as $col=>$text ) {
        $terms[] = array('cnj'=>'and', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$text);
      } # end foreach col in advsearch
      $terms[0]['obr'] = 1;
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter->addTerms($terms);
    } else if ( $search != '' ) {
      $search = '%' .$search. '%';
      $terms = array();
      foreach ( $columns as $col ) {
        $terms[] = array('cnj'=>'or', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$search);
      }
      $terms[0]['obr'] = 1;
      $terms[0]['cnj'] = 'and';
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter = $search_filter->addTerms($terms, array('obr'=>1, 'cbr'=>1, 'op'=>'OR'));
    } # end if search

    $sql = 'SELECT * FROM `Frames` WHERE '.$search_filter->sql().' ORDER BY ' .$sort. ' ' .$order;
    $filtered_rows = dbFetchAll($sql);
    ZM\Debug('Have ' . count($filtered_rows) . ' frames matching search filter.');
  } else {
    $filtered_rows = $unfiltered_rows;
  } # end if search_filter->terms() > 1

  $returned_rows = array();
  foreach ( array_slice($filtered_rows, $offset, $limit) as $row ) {
    if ( ZM_WEB_LIST_THUMBS ) {

      # Build the path to the potential analysis image
      $analImage = sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d-analyse.jpg', $row['FrameId']);
      $analPath = $Event->Path().'/'.$analImage;
      $alarmFrame = $row['Type'] == 'Alarm';
      $hasAnalImage = $alarmFrame && file_exists($analPath) && filesize($analPath);

      # Our base img source component, which we will add on to
      $base_img_src = '?view=image&amp;fid=' .$row['Id'];

      # if an analysis images exists, use it as the thumbnail
      if ( $hasAnalImage ) $base_img_src .= '&amp;show=analyse';

      # Build the subcomponents needed for the image source
      $ratio_factor = $Monitor->ViewHeight() / $Monitor->ViewWidth();
      $thmb_width = ZM_WEB_LIST_THUMB_WIDTH ? 'width='.ZM_WEB_LIST_THUMB_WIDTH : '';
      $thmb_height = 'height="'.( ZM_WEB_LIST_THUMB_HEIGHT ? ZM_WEB_LIST_THUMB_HEIGHT : ZM_WEB_LIST_THUMB_WIDTH*$ratio_factor ) .'"';
      $thmb_fn = 'filename=' .$Event->MonitorId(). '_' .$row['EventId']. '_' .$row['FrameId']. '.jpg';

      # Assemble the scaled and unscaled image source image source components
      $img_src = join('&amp;', array_filter(array($base_img_src, $thmb_width, $thmb_height, $thmb_fn)));
      $full_img_src = join('&amp;', array_filter(array($base_img_src, $thmb_fn)));
      
      # finally, we assemble the the entire thumbnail img src structure, whew
      $row['Thumbnail'] = '<img src="' .$img_src. '" '.$thmb_width. ' ' .$thmb_height. 'img_src="' .$img_src. '" full_img_src="' .$full_img_src. '">';
    }
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
