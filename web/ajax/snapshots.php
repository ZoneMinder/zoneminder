<?php
require_once('includes/Snapshot.php');
require_once('includes/Filter.php');
$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if (!canView('Snapshots'))
  $message = 'Insufficient permissions for user '.$user->Username();

$task = '';
if (empty($_REQUEST['task'])) {
  $message = 'Must specify a task';
} else {
  $task = $_REQUEST['task'];
}

if (empty($_REQUEST['ids'])) {
  if ($task != 'query') $message = 'No snapshot id(s) supplied';
} else {
  $ids = (array)$_REQUEST['ids'];
}

if ($message) {
  ajaxError($message);
  return;
}

// Search contains a user entered string to search on
$search = (isset($_REQUEST['search']) and is_scalar($_REQUEST['search'])) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = (isset($_REQUEST['advsearch']) and is_string($_REQUEST['advsearch'])) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();
if (!is_array($advsearch)) $advsearch = array();

// Sort specifies the name of the column to sort on
$sort = 'Id';
if (isset($_REQUEST['sort']) and is_string($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
}

// Offset specifies the starting row to return, used for pagination
$offset = 0;
if (isset($_REQUEST['offset'])) {
  if (!is_scalar($_REQUEST['offset']) or (!is_int($_REQUEST['offset']) and !ctype_digit((string)$_REQUEST['offset']))) {
    ZM\Error('Invalid value for offset: ' . (is_scalar($_REQUEST['offset']) ? $_REQUEST['offset'] : gettype($_REQUEST['offset'])));
  } else {
    $offset = $_REQUEST['offset'];
  }
}

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and is_string($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

// Limit specifies the number of rows to return
// Set the default to 0 for events view, to prevent an issue with ALL pagination
$limit = 0;
if (isset($_REQUEST['limit'])) {
  if (!is_scalar($_REQUEST['limit']) or (!is_int($_REQUEST['limit']) and !ctype_digit((string)$_REQUEST['limit']))) {
    ZM\Error('Invalid value for limit: ' . (is_scalar($_REQUEST['limit']) ? $_REQUEST['limit'] : gettype($_REQUEST['limit'])));
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
			ajaxError('Insufficient permissions for user '.validHtmlStr($user->Username()));
			return;
		}

    // Only the problems are reported; snapshots.js shows them.
    $messages = array();
    foreach ($ids as $id) $messages = array_merge($messages, deleteRequest($id));
    $data = array('message' => $messages);
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
  $id = validCardinal($id);
  if ($id === '') {
    $message[] = 'Invalid snapshot id.';
    return $message;
  }
  $snapshot = new ZM\Snapshot($id);
  if ( !$snapshot->Id() ) {
    $message[] = 'Snapshot '.$id.' not found.';
  } else if ( !$snapshot->delete() ) {
    $message[] = 'Snapshot '.$id.' could not be deleted.';
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

  $data['totalNotFiltered'] = (int)dbFetchOne('SELECT COUNT(*) AS Total FROM Snapshots', 'Total');

  $where = '';

  if ( count($advsearch) or $search != '' ) {
    $search_filter = new ZM\Filter();

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
      // FilterTerm splits a value on commas into alternatives, so match a comma as any one character.
      $search = '%' .str_replace(',', '_', $search). '%';
      $terms = array();
      foreach ( $columns as $col ) {
        $terms[] = array('cnj'=>'or', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$search, 'tablename'=>'Snapshots');
      }
      $terms[0]['obr'] = 1;
      $terms[0]['cnj'] = 'and';
      $terms[count($terms)-1]['cbr'] = 1;
      $search_filter = $search_filter->addTerms($terms, array('obr'=>1, 'cbr'=>1, 'op'=>'OR'));
    } # end if search

    $search_sql = $search_filter->sql();
    if ($search_sql) $where = ' WHERE '.$search_sql;
    $data['total'] = (int)dbFetchOne('SELECT COUNT(*) AS Total FROM Snapshots'.$where, 'Total');
  } else {
    $data['total'] = $data['totalNotFiltered'];
  }

  $sql = 'SELECT * FROM `Snapshots`'.$where.' ORDER BY '.$sort.' '.$order.($sort != 'Id' ? ', Id' : '');
  if ($limit) $sql .= ' LIMIT '.(int)$offset.', '.(int)$limit;
  ZM\Debug('Calling the following sql query: ' .$sql);

  $returned_rows = array();
  foreach ( dbFetchAll($sql) as $row ) {
    $snapshot = new ZM\Snapshot($row);

    $row['imgHtml'] = '';
    // Modify the row data as needed
    foreach ( $snapshot->Events() as $event ) {
      $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
      $row['imgHtml'] .= '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" loading="lazy" />'.PHP_EOL;
    }
    $row['Name'] = validHtmlStr($row['Name']);
    $row['Description'] = validHtmlStr($row['Description']);
    //$row['Archived'] = $row['Archived'] ? translate('Yes') : translate('No');
    //$row['Emailed'] = $row['Emailed'] ? translate('Yes') : translate('No');
    //$row['Cause'] = validHtmlStr($row['Cause']);
    $row['CreatedOn'] = $row['CreatedOn'] ? $dateTimeFormatter->format(strtotime($row['CreatedOn'])) : '';
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

  return $data;
}
?>
