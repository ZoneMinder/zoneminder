<?php
ini_set('display_errors', '');
$message = '';
$data = array();

//
// INITIALIZE AND CHECK SANITY
//

if (!canView('Events'))
  $message = 'Insufficient permissions for user '.$user->Username().'<br/>';

if (empty($_REQUEST['task'])) {
  $message = 'Must specify a task<br/>';
} else {
  $task = $_REQUEST['task'];
}

if (empty($_REQUEST['ids'])) {
  if (isset($_REQUEST['task']) && $_REQUEST['task'] != 'query')
    $message = 'No id(s) supplied<br/>';
} else {
  $ids = $_REQUEST['ids'];
}

if ($message) {
  ajaxError($message);
  return;
}

require_once('includes/Filter.php');
require_once('includes/Report.php');

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['advsearch']) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();

$order = 'ASC';
// Order specifies the sort direction, either asc or desc
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
$sort = (isset($_REQUEST['sort'])) ? $_REQUEST['sort'] : '';

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
// Set the default to 0 for reports view, to prevent an issue with ALL pagination
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
		if (!canEdit('Events'))  {
			ajaxError('Insufficient permissions for user '.$user->Username());
			return;
		}
    foreach ($ids as $id) {
      $message = deleteRequest($id);
      if (count($message)) {
        $data[] = $message;
      }
    }
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
  $report = new ZM\Report($id);
  if ( !$report->Id() ) {
    $message[] = array($id=>'Report not found.');
  } else if (!$report->canEdit()) {
    $message[] = array($id=>'You do not have permission to delete report '.$report->Id());
  } else {
    $report->delete();
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
  $table = 'Reports';

  // The names of the dB columns in the reports table we are interested in
  $columns = array('Id', 'Name', 'FilterId', 'StartDateTime', 'EndDateTime', 'Interval');

  if ($sort != '') {
    if (!in_array($sort, $columns)) {
      ZM\Error('Invalid sort field: ' . $sort);
      $sort = '';
    } else if ($sort == 'EndDateTime') {
      if ($order == 'ASC') {
        $sort = 'EndDateTime IS NULL, E.EndDateTime';
      } else {
        $sort = 'EndDateTime IS NOT NULL, E.EndDateTime';
      }
    }
  }

  $values = array();
  $likes = array();
  $where = '';

  $col_str = '*';
  $sql = 'SELECT ' .$col_str. ' FROM `Reports` '.$where.($sort?' ORDER BY '.$sort.' '.$order:'');
  if ($limit) $sql .= ' LIMIT '.$limit;

  $unfiltered_rows = array();
  $ids = array();

  ZM\Debug('Calling the following sql query: ' .$sql);
  $query = dbQuery($sql, $values);
  if (!$query) {
    ajaxError(dbError($sql));
    return;
  }
  while ($row = dbFetchNext($query)) {
    $request = new ZM\Report($row);
    $request->remove_from_cache();
    $ids[] = $request->Id();
    $unfiltered_rows[] = $row;
  } # end foreach row

  # Filter limits come before pagination limits.
  if ($limit and ($limit > count($unfiltered_rows))) {
    ZM\Debug('Filtering rows due to filter->limit '.count($unfiltered_rows).' limit: '.$limit);
    $unfiltered_rows = array_slice($unfiltered_rows, 0, $limit);
  }

  ZM\Debug('Have ' . count($unfiltered_rows) . ' reports matching base filter.');
  $filtered_rows = $unfiltered_rows;

  if ($limit) {
    ZM\Debug("Filtering rows due to limit " . count($filtered_rows)." offset: $offset limit: $limit");
    $filtered_rows = array_slice($filtered_rows, $offset, $limit);
  }

  $returned_rows = array();
  foreach ($filtered_rows as $row) {
    $report = new ZM\Report($row);

    $row['Name'] = validHtmlStr($row['Name']);
    $row['StartDateTime'] = $dateTimeFormatter->format(strtotime($row['StartDateTime']));
    $row['EndDateTime'] = $row['EndDateTime'] ? $dateTimeFormatter->format(strtotime($row['EndDateTime'])) : null;
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
