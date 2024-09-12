<?php
require_once('includes/Snapshot.php');
require_once('includes/Filter.php');
$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if (!canView('Snapshots'))
  $message = 'Insufficient permissions for user '.$user['Username'];

$task = '';
if (empty($_REQUEST['task'])) {
  $message = 'Must specify a task';
} else {
  $task = $_REQUEST['task'];
}

if (empty($_REQUEST['ids'])) {
  if ($task != 'query') $message = 'No snapshot id(s) supplied';
} else {
  $ids = $_REQUEST['ids'];
}

if ($message) {
  ajaxError($message);
  return;
}

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['advsearch']) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();

// Sort specifies the name of the column to sort on
$sort = 'Id';
if (isset($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
}

// Offset specifies the starting row to return, used for pagination
$offset = 0;
if (isset($_REQUEST['offset'])) {
  if ((!is_int($_REQUEST['offset']) and !ctype_digit($_REQUEST['offset']))) {
    ZM\Error('Invalid value for offset: '.$_REQUEST['offset']);
  } else {
    $offset = $_REQUEST['offset'];
  }
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

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
  case 'delete' :
		if (!canEdit('Snapshots'))  {
			ajaxError('Insufficient permissions for user '.$user['Username']);
			return;
		}

    foreach ($ids as $id) $data[] = deleteRequest($id);
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

function deleteRequest($id) {
  $message = array();
  $snapshot = new ZM\Snapshot($id);
  if ( !$snapshot->Id() ) {
    $message[] = array($id=>'Snapshot not found.');
  //} else if ( $snapshot->Archived() ) {
    //$message[] = array($id=>'Event is archived, cannot delete it.');
  } else {
    $snapshot->delete();
    $message[] = array($id=>'Snapshot deleted.');
  }
  
  return $message;
}

function queryRequest($search, $advsearch, $sort, $offset, $order, $limit) {

  global $dateTimeFormatter;
  $data = array(
    'total'   =>  0,
    'totalNotFiltered' => 0,
    'rows'    =>  array(),
    'updated' =>  $dateTimeFormatter->format(time())
  );

  // Put server pagination code here
  // The table we want our data from
  $table = 'Snapshots';

  // The names of the dB columns in the events table we are interested in
  $columns = array('Id', 'Name', 'Description', 'CreatedOn');

  if ( !in_array($sort, array_merge($columns)) ) {
    ZM\Error('Invalid sort field: ' . $sort);
    $sort = 'Id';
  }

  $values = array();
  $likes = array();
  $where = '';

  $col_str = '*';
  $sql = 'SELECT ' .$col_str. ' FROM `Snapshots`'.$where.' ORDER BY '.$sort.' '.$order;

  $unfiltered_rows = array();
  $snapshot_ids = array();

  ZM\Debug('Calling the following sql query: ' .$sql);
  $query = dbQuery($sql, $values);
  if ( $query ) {
    while ( $row = dbFetchNext($query) ) {
      $snapshot = new ZM\Snapshot($row);
      $snapshot->remove_from_cache();
      $snapshot_ids[] = $snapshot->Id();
      $unfiltered_rows[] = $row;
    } # end foreach row
  }

  ZM\Debug('Have ' . count($unfiltered_rows) . ' snapshots matching base filter.');

  $filtered_rows = null;

  if ( count($advsearch) or $search != '' ) {
    $search_filter = new ZM\Filter();

    if (count($snapshot_ids))
      $search_filter = $search_filter->addTerm(array('cnj'=>'and', 'attr'=>'Id', 'op'=>'IN', 'val'=>$snapshot_ids, 'tablename'=>'Snapshots'));

    // There are two search bars in the log view, normal and advanced
    // Making an exuctive decision to ignore the normal search, when advanced search is in use
    // Alternatively we could try to do both
    if ( count($advsearch) ) {
      $terms = array();
      foreach ( $advsearch as $col=>$text ) {
        $terms[] = array('cnj'=>'and', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$text, 'tablename'=>'Snapshots');
      } # end foreach col in advsearch
      $terms[0]['obr'] = 1;
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter->addTerms($terms);
    } else if ( $search != '' ) {
      $search = '%' .$search. '%';
      $terms = array();
      foreach ( $columns as $col ) {
        $terms[] = array('cnj'=>'or', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$search, 'tablename'=>'Snapshots');
      }
      $terms[0]['obr'] = 1;
      $terms[0]['cnj'] = 'and';
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter = $search_filter->addTerms($terms, array('obr'=>1, 'cbr'=>1, 'op'=>'OR'));
    } # end if search

    $sql = 'SELECT ' .$col_str. ' FROM `Snapshots` WHERE '.$search_filter->sql().' ORDER BY ' .$sort. ' ' .$order;
    ZM\Debug('Calling the following sql query: ' .$sql);
    $filtered_rows = dbFetchAll($sql);
    ZM\Debug('Have ' . count($filtered_rows) . ' snapshots matching search filter.');
  } else {
    $filtered_rows = $unfiltered_rows;
  } # end if search_filter->terms() > 1

  $returned_rows = array();
  foreach ( array_slice($filtered_rows, $offset, $limit) as $row ) {
    $snapshot = new ZM\Snapshot($row);

    $row['imgHtml'] = '';
    // Modify the row data as needed
    foreach ( $snapshot->Events() as $event ) {
      $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
      $imgSrc = $event->getThumbnailSrc(array(), '&amp;');

      $row['imgHtml'] .= '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" loading="lazy" />';
    }
    $row['Name'] = validHtmlStr($row['Name']);
    $row['Description'] = validHtmlStr($row['Description']);
    //$row['Archived'] = $row['Archived'] ? translate('Yes') : translate('No');
    //$row['Emailed'] = $row['Emailed'] ? translate('Yes') : translate('No');
    //$row['Cause'] = validHtmlStr($row['Cause']);
    $row['CreatedOn'] = $dateTimeFormatter->format(strtotime($row['CreatedOn']));
    //$row['StartDateTime'] = strftime(STRF_FMT_DATETIME_SHORTER, strtotime($row['StartDateTime']));
    //$row['EndDateTime'] = $row['EndDateTime'] ? strftime(STRF_FMT_DATETIME_SHORTER, strtotime($row['EndDateTime'])) : null;
    //$row['Length'] = gmdate('H:i:s', $row['Length'] );
    //$row['Storage'] = ( $row['StorageId'] and isset($StorageById[$row['StorageId']]) ) ? $StorageById[$row['StorageId']]->Name() : 'Default';
    //$row['Notes'] = nl2br(htmlspecialchars($row['Notes']));
    //$row['DiskSpace'] = human_filesize($event->DiskSpace());
    //
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
