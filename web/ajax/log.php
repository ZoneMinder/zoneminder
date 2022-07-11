<?php
$data = array();
$message = '';

//
// INITIALIZE AND CHECK SANITY
//

if ( !canView('System') )
  $message = 'Insufficient permissions to view log entries for user '.$user['Username'];

// task must be set
if ( !isset($_REQUEST['task']) ) {
  $message = 'This request requires a task to be set';
} else if ( $_REQUEST['task'] != 'query' && $_REQUEST['task'] != 'create' ) {
  // Only the query and create tasks are supported at the moment
  $message = 'Unrecognised task '.$_REQUEST['task'];
} else {
  $task = $_REQUEST['task'];
}

if ( $message ) {
  ajaxError($message);
  return;
}

//
// MAIN LOOP
//

switch ( $task ) {
  case 'create' :
    createRequest();
    break;
  case 'query' :
    $data = queryRequest();
    break;
  default :
    ZM\Fatal('Unrecognised task '.$task);
} // end switch task

ajaxResponse($data);

//
// FUNCTION DEFINITIONS
//

function createRequest() {
  if ( !empty($_POST['level']) && !empty($_POST['message']) ) {
    ZM\logInit(array('id'=>'web_js'));

    $string = $_POST['message'];

    $file = !empty($_POST['file']) ? preg_replace('/\w+:\/\/[\w.:]+\//', '', $_POST['file']) : '';
    if ( !empty($_POST['line']) ) {
      $line = validInt($_POST['line']);
    } else {
      $line = NULL;
    }

    $levels = array_flip(ZM\Logger::$codes);
    if ( !isset($levels[$_POST['level']]) ) {
      ZM\Panic('Unexpected logger level '.$_POST['level']);
    }
    $level = $levels[$_POST['level']];
    ZM\Logger::fetch()->logPrint($level, $string, $file, $line);
  } else {
    ZM\Error('Invalid log create: '.print_r($_POST, true));
  }
}

function queryRequest() {

  // Offset specifies the starting row to return, used for pagination
  $offset = 0;
  if ( isset($_REQUEST['offset']) ) {
    if ( ( !is_int($_REQUEST['offset']) and !ctype_digit($_REQUEST['offset']) ) ) {
      ZM\Error('Invalid value for offset: ' . $_REQUEST['offset']);
    } else {
      $offset = $_REQUEST['offset'];
    }
  }

  // Limit specifies the number of rows to return
  $limit = 100;
  if ( isset($_REQUEST['limit']) ) {
    if ( ( !is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']) ) ) {
      ZM\Error('Invalid value for limit: ' . $_REQUEST['limit']);
    } else {
      $limit = $_REQUEST['limit'];
    }
  }
  // The table we want our data from
  $table = 'Logs';

  // The names of the dB columns in the log table we are interested in
  $columns = array('TimeKey', 'Component', 'ServerId', 'Pid', 'Code', 'Message', 'File', 'Line');

  // The names of columns shown in the log view that are NOT dB columns in the database
  $col_alt = array('DateTime', 'Server');

  $sort = 'TimeKey';
  if ( isset($_REQUEST['sort']) ) {
    $sort = $_REQUEST['sort'];
    if ( $sort == 'DateTime' ) $sort = 'TimeKey';
  }
  if ( !in_array($sort, array_merge($columns, $col_alt)) ) {
    ZM\Error('Invalid sort field: ' . $sort);
    return;
  }

  // Order specifies the sort direction, either asc or desc
  $order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

  $col_str = implode(', ', $columns);
  $data = array();
  $query = array();
  $query['values'] = array();
  $likes = array();
  $where = '';
   // There are two search bars in the log view, normal and advanced
  // Making an exuctive decision to ignore the normal search, when advanced search is in use
  // Alternatively we could try to do both
  //
  // Advanced search contains an array of "column name" => "search text" pairs
  // Bootstrap table sends json_ecoded array, which we must decode
  $advsearch = isset($_REQUEST['filter']) ? json_decode($_REQUEST['filter'], JSON_OBJECT_AS_ARRAY) : array();
  // Search contains a user entered string to search on
  $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
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

  $rows = array();
  $results = dbFetchAll($query['sql'], NULL, $query['values']);

  global $dateTimeFormatter;
  foreach ( $results as $row ) {
    $row['DateTime'] = $dateTimeFormatter->format($row['TimeKey']);
    $Server = ZM\Server::find_one(array('Id'=>$row['ServerId']));

    $row['Server'] = $Server ? $Server->Name() : '';
    // First strip out any html tags
    // Second strip out all characters that are not ASCII 32-126 (yes, 126)
    $row['Message'] = preg_replace('/[^\x20-\x7E]/', '', strip_tags($row['Message']));
    $rows[] = $row;
  }
  $data['rows'] = $rows;
  $data['logstate'] = logState();
  $data['updated'] = $dateTimeFormatter->format(time());

  return $data;
}
