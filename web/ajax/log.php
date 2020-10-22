<?php
global $Servers;

if ( !canView('System') ) {
  ajaxError('Insufficient permissions to view log entries');
  return;
}

// Only the query task is supported at the moment
if ( !isset($_REQUEST['task']) or $_REQUEST['task'] != 'query' ) {
  ajaxError('Unrecognised task '.(isset($_REQUEST['task'])?$_REQUEST['task']:''));
  return;
}
// The table we want our data from
$table = 'Logs';

// The names of the dB columns in the log table we are interested in
$columns = array('TimeKey', 'Component', 'ServerId', 'Pid', 'Code', 'Message', 'File', 'Line');

// The names of columns shown in the log view that are NOT dB columns in the database
$col_alt = array('DateTime', 'Server');

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_ecoded array, which we must decode
$advsearch = isset($_REQUEST['filter']) ? json_decode($_REQUEST['filter'], JSON_OBJECT_AS_ARRAY) : array();

// Sort specifies the name of the column to sort on
$sort = 'TimeKey';
if ( isset($_REQUEST['sort']) ) {
  if ( !in_array($_REQUEST['sort'], array_merge($columns, $col_alt)) ) {
    ZM\Error('Invalid sort field: ' . $_REQUEST['sort']);
  } else {
    $sort = $_REQUEST['sort'];
    if ( $sort == 'DateTime' ) $sort = 'TimeKey';
  }
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

//ZM\Warning('Calling the following sql query: ' .$query['sql']);

$data['totalNotFiltered'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table, 'Total');
if ( $search != '' || count($advsearch) ) {
  $data['total'] = dbFetchOne('SELECT count(*) AS Total FROM ' .$table.$where , 'Total', $wherevalues);
} else {
  $data['total'] = $data['totalNotFiltered'];
}

if ( !$Servers )
  $Servers = ZM\Server::find();
$servers_by_Id = array();
# There is probably a better way to do this.
foreach ( $Servers as $server ) {
  $servers_by_Id[$server->Id()] = $server;
}

$rows = array();
foreach ( dbFetchAll($query['sql'], NULL, $query['values']) as $row ) {
  $row['DateTime'] = strftime('%Y-%m-%d %H:%M:%S', intval($row['TimeKey']));
  $row['Server'] = ( $row['ServerId'] and isset($servers_by_Id[$row['ServerId']]) ) ? $servers_by_Id[$row['ServerId']]->Name() : '';
  // First strip out any html tags
  // Second strip out all characters that are not ASCII 32-126 (yes, 126)
  $row['Message'] = preg_replace('/[^\x20-\x7E]/', '', strip_tags($row['Message']));
  $rows[] = $row;
}
$data['rows'] = $rows;
$data['logstate'] = logState();
$data['updated'] = preg_match('/%/', DATE_FMT_CONSOLE_LONG) ? strftime(DATE_FMT_CONSOLE_LONG) : date(DATE_FMT_CONSOLE_LONG);

ajaxResponse($data);

