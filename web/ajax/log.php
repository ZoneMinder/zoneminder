<?php
$data = array();
$message = '';

//
// INITIALIZE AND CHECK SANITY
//

// task must be set
if (!isset($_REQUEST['task'])) {
  $message = 'This request requires a task to be set';
} else if ($_REQUEST['task'] == 'query') {
  if (!canView('System')) {
    $message = 'Insufficient permissions to view log entries for user '.validHtmlStr($user->Username());
  } else {
    $data = queryRequest();
  }
} else if ($_REQUEST['task'] == 'create' ) {
  global $user;
  if (!$user or (!canEdit('System') and !ZM_LOG_INJECT)) {
    $message = 'Insufficient permissions to create log entries for user '.validHtmlStr($user->Username());
  } else {
    createRequest();
  }
} else if ($_REQUEST['task'] == 'delete') {
  global $user;
  if (!canEdit('System')) {
    $message = 'Insufficient permissions to delete log entries for user '.validHtmlStr($user->Username());
  } else {
    if (!empty($_REQUEST['ids'])) {
      $ids = array_map('intval', (array)$_REQUEST['ids']);
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      dbQuery('DELETE FROM Logs WHERE Id IN (' . $placeholders . ')', $ids);
    } else if (!empty($_REQUEST['all'])) {
      // Deleting by id needs one request per 100 rows, which is unusable at the
      // hundreds of thousands of rows people accumulate, and the table's
      // check-all only ever selects the current page. This clears the lot in one
      // statement. It is a separate parameter rather than "no ids given" so a
      // malformed request that lost its ids cannot empty the table by accident.
      $filter = logFilter();
      if ($filter['where'] !== '') {
        // A filter is applied, so clear what the view is showing rather than the
        // whole table. Same builder as the query, so the rows deleted are the
        // rows that were on screen.
        if (dbQuery('DELETE FROM Logs WHERE '.$filter['where'], $filter['values']) === null) {
          $message = 'Failed to delete the filtered log entries';
        }
      } else if ($filter['requested']) {
        // Filtering was asked for but none of it survived parsing, so we have no
        // idea which rows were meant. Truncating here would destroy the whole
        // table on the strength of a mistyped date, and the browser has already
        // told the user it was only clearing the filtered rows.
        $message = 'Cannot clear logs: the filter could not be interpreted. Check the date fields.';
      } else if (dbQuery('TRUNCATE TABLE Logs') === null) {
        // Nothing filtered, so empty the table outright. TRUNCATE beats a row by
        // row DELETE at this size and nothing has a foreign key onto Logs.
        // It needs DROP, which the packaged installs grant, so fall back for
        // installs that were granted DML only.
        if (dbQuery('DELETE FROM Logs') === null) {
          $message = 'Failed to clear the log table';
        }
      }
    }
  }
} else {
  // Only the query and create tasks are supported at the moment
  $message = 'Unrecognised task '.validHtmlStr($_REQUEST['task']);
}

if ($message) {
  ajaxError($message);
  return;
}
ajaxResponse($data);

//
// FUNCTION DEFINITIONS
//

function createRequest() {
  // Every field here is attacker-controlled. Coerce each to a scalar and strip
  // control characters (newlines, carriage returns, tabs, etc.) so nothing can
  // forge additional lines in the text log file, and so non-scalar input (e.g.
  // message[]=x) cannot trip a TypeError in logPrint(). A log message is a
  // single line; the Log view stores it as one Message column regardless.
  $sanitize = function($value) {
    return is_scalar($value) ? preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$value) : '';
  };

  $level = $sanitize($_POST['level'] ?? '');
  $message = $sanitize($_POST['message'] ?? '');
  // Strip the URL scheme/host from file, then the control characters.
  $file = (isset($_POST['file']) && is_scalar($_POST['file']))
    ? $sanitize(preg_replace('/\w+:\/\/[\w.:]+\//', '', (string)$_POST['file']))
    : '';

  if ($level === '' || $message === '') {
    ZM\Error('Invalid log create: level and message are required');
    return;
  }

  ZM\logInit(array('id'=>'web_js'));
  $line = empty($_POST['line']) ? NULL : validInt($_POST['line']);

  $levels = array_flip(ZM\Logger::$codes);
  if (!isset($levels[$level])) {
    ZM\Error('Unexpected logger level '.$level); // already sanitized above
    $level = 'ERR';
  }
  ZM\Logger::fetch()->logPrint($levels[$level], $message, $file, $line);
}

// The dB columns in the log table the view works with.
function logColumns() {
  return array('Id', 'TimeKey', 'Component', 'ServerId', 'Pid', 'Code', 'Message', 'File', 'Line');
}

// Columns shown in the log view that are not dB columns.
function logAltColumns() {
  return array('DateTime', 'Server');
}

// Build the WHERE clause for whatever the Log view is currently filtered to.
// queryRequest() and the delete task both go through this so a "clear all" with
// a filter applied removes exactly the rows the user is looking at. Rebuilding
// the conditions separately would risk the two drifting apart, and the failure
// mode there is deleting rows the user never saw.
// Returns where (no leading WHERE), values, and the parsed component/level
// selections queryRequest stashes in the session.
function logFilter() {
  $columns = logColumns();
  $col_alt = logAltColumns();
  $query = array('values' => array());
  $likes = array();
  $where = '';
  // Whether the request asked to filter at all, which is deliberately not the
  // same as whether the filter produced any SQL. Values get dropped here when
  // they do not parse (an unparseable date, an unknown level code), and the
  // delete task must not read the resulting empty clause as "nothing was
  // filtered" and empty the whole table. See #4727.
  $requested = false;
   // There are two search bars in the log view, normal and advanced
  // Making an exuctive decision to ignore the normal search, when advanced search is in use
  // Alternatively we could try to do both
  //
  // Advanced search contains an array of "column name" => "search text" pairs
  // Bootstrap table sends json_ecoded array, which we must decode
  $advsearch = isset($_REQUEST['filter']) ? json_decode($_REQUEST['filter'], JSON_OBJECT_AS_ARRAY) : array();
  // Search contains a user entered string to search on
  $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
  if (count($advsearch)) {
    $requested = true;
    foreach ($advsearch as $col=>$text) {
      // Real columns only. DateTime and Server are built in PHP after the rows
      // come back, so matching on them produces SQL against columns the Logs
      // table does not have. The advanced search form offers them because the
      // view declares them as fields, and the resulting statement fails.
      if (!in_array($col, $columns)) {
        ZM\Error("'$col' is not a searchable column name");
        continue;
      }
      // Don't use wildcards on advanced search
      //$text = '%' .$text. '%';
      array_push($likes, $col.' LIKE ?');
      array_push($query['values'], $text);
    }
    // Every requested column was rejected above. That used to build the
    // malformed 'WHERE ()', which the query errored out on. Now that the delete
    // shares this clause it matters much more: an empty $where here would read
    // as "no filter applied" and truncate the whole table. Match nothing
    // instead, which is what a search for a column that does not exist means.
    $where = $likes ? '(' .implode(' OR ', $likes). ')' : '1=0';

  } else if ($search != '') {
    $requested = true;
    $search = '%' .$search. '%';
    foreach ( $columns as $col ) {
      array_push($likes, $col.' LIKE ?');
      array_push($query['values'], $search);
    }
    $where = '(' .implode(' OR ', $likes). ')';
  }

  // Component is a multi-select: accept a scalar (legacy) or an array of
  // component names and match any of them.
  $requestComponents = array();
  if (isset($_REQUEST['Component'])) {
    foreach ((array)$_REQUEST['Component'] as $component) {
      if (is_scalar($component) && (string)$component !== '') $requestComponents[] = (string)$component;
    }
  }
  if (count($requestComponents)) {
    $requested = true;
    if ($where) $where .= ' AND ';
    $placeholders = implode(', ', array_fill(0, count($requestComponents), '?'));
    $where .= 'Component IN (' . $placeholders . ')';
    foreach ($requestComponents as $component) $query['values'][] = $component;
  }

  if (!empty($_REQUEST['ServerId'])) {
    $requested = true;
    if ($where) $where .= ' AND ';
    $where .= 'ServerId = ?';
    $query['values'][] = $_REQUEST['ServerId'];
  }
/* We have an indexed 'Level', not 'Code'.
  if (!empty($_REQUEST['level'])) {
    if ($where) $where .= ' AND ';
    $where .= 'Code = ?';
    $query['values'][] = $_REQUEST['level'];
  }
*/
  // Level is a multi-select: accept a scalar (legacy) or an array of level codes
  // ('DBG', 'INF', ...). Keep only recognized codes and match any of them.
  $level_codes = array_flip(ZM\Logger::$codes);
  $requestLevels = array();
  if (isset($_REQUEST['level'])) {
    foreach ((array)$_REQUEST['level'] as $level) {
      if (!is_scalar($level) or (string)$level === '') continue;
      // Asked for, whether or not we recognise it.
      $requested = true;
      if (isset($level_codes[(string)$level])) {
        $requestLevels[(string)$level] = $level_codes[(string)$level];
      }
    }
  }
  if (count($requestLevels)) {
    if ($where) $where .= ' AND ';
    $placeholders = implode(', ', array_fill(0, count($requestLevels), '?'));
    $where .= ' Level IN (' . $placeholders . ')';
    foreach ($requestLevels as $code) $query['values'][] = $code;
  }

  if (!empty($_REQUEST['StartDateTime'])) {
    $requested = true;
    $start_time = strtotime($_REQUEST['StartDateTime']);
    if ($start_time) {
      if ($where) $where .= ' AND ';
      $where .= 'TimeKey >= ?';
      $query['values'][] = $start_time;
    } else {
      ZM\Warning("Unable to parse StartDateTime ".$_REQUEST['StartDateTime']. " into a timestamp");
    }
  }
  if (!empty($_REQUEST['EndDateTime'])) {
    $requested = true;
    $end_time = strtotime($_REQUEST['EndDateTime']);
    if ($end_time) {
      if ($where) $where .= ' AND ';
      $where .= 'TimeKey <= ?';
      $query['values'][] = $end_time;
    } else {
      ZM\Warning("Unable to parse EndDateTime ".$_REQUEST['EndDateTime']. " into a timestamp");
    }
  }


  return array(
    'where' => $where,
    'values' => $query['values'],
    'requested' => $requested,
    'components' => $requestComponents,
    'levels' => $requestLevels,
  );
}

function queryRequest() {
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
  $limit = 100;
  if (isset($_REQUEST['limit'])) {
    if ((!is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']))) {
      ZM\Error('Invalid value for limit: ' . $_REQUEST['limit']);
    } else {
      $limit = $_REQUEST['limit'];
    }
  }
  // The table we want our data from
  $table = 'Logs';

  $nameMainQuery = 't1'; # To optimize queries using a subquery
  $nameSubQuery = 't2';

  $columns = logColumns();
  $columnsContext = $columns;
  $col_alt = logAltColumns();

  $sort = 'TimeKey';
  if (isset($_REQUEST['sort'])) {
    $sort = $_REQUEST['sort'];
    if ($sort == 'DateTime') $sort = 'TimeKey';
    if ($sort == 'Server') $sort = 'ServerId';
  }
  if (!in_array($sort, array_merge($columns, $col_alt))) {
    ZM\Error('Invalid sort field: ' . $sort);
    return;
  }

  // Order specifies the sort direction, either asc or desc
  $order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

  if ($nameMainQuery !== '' && $nameSubQuery !== '') {
    array_walk($columnsContext, function(&$value, $key, $nameMainQuery) {
      $value = $nameMainQuery . '.' . $value;
    }, $nameMainQuery);
  }

  $col_str = implode(', ', $columns);
  $col_str_context = implode(', ', $columnsContext);
  $data = array();
  $query = array();
  $query['values'] = array();
  $filter = logFilter();
  $where = $filter['where'];
  $query['values'] = $filter['values'];
  $requestComponents = $filter['components'];
  $requestLevels = $filter['levels'];

  zm_session_start();
  $_SESSION['zmLogComponent'] = $requestComponents;
  $_SESSION['zmLogFilterLevel'] = array_keys($requestLevels);
  session_write_close();

  if ($where) $where = ' WHERE '.$where;

  $data['totalNotFiltered'] = dbFetchOne('SELECT count(*) AS Total FROM `' .$table.'`', 'Total');
  if ($where) {
    $data['total'] = dbFetchOne('SELECT count(*) AS Total FROM `' .$table.'` '.$where, 'Total', $query['values']);
  } else {
    $data['total'] = $data['totalNotFiltered'];
  }

  if ($nameMainQuery !== '' && $nameSubQuery !== '') { # Optimized query
    $query['sql'] = '
      SELECT ' .$col_str_context. ' 
      FROM `' .$table. '` ' .$nameMainQuery. ' 
      JOIN (
        SELECT Id 
        FROM `'.$table.'` '.$where. ' 
        ORDER BY ' .$sort. ' ' .$order. ' 
        LIMIT ?, ?
      ) AS ' .$nameSubQuery. ' 
      ON ' .$nameMainQuery. '.Id=' .$nameSubQuery. '.Id 
      ORDER BY ' .$nameMainQuery. '.' .$sort. ' ' .$order;
  } else {
    $query['sql'] = 'SELECT ' .$col_str. ' FROM `' .$table. '` ' .$where. ' ORDER BY ' .$sort. ' ' .$order. ' LIMIT ?, ?';
  }

  array_push($query['values'], $offset, $limit);

  $rows = array();
  $results = dbFetchAll($query['sql'], NULL, $query['values']);

  global $dateTimeFormatter;
  foreach ($results as $row) {
    $row['DateTime'] = empty($row['TimeKey']) ? '' : $dateTimeFormatter->format(intval($row['TimeKey']));
    $Server = $row['ServerId'] ? ZM\Server::find_one(array('Id'=>$row['ServerId'])) : null;

    $row['Server'] = $Server ? $Server->Name() : '';
    // Strip out all characters that are not ASCII 32-126 (yes, 126)
    $row['Message'] = preg_replace('/[^\x20-\x7E]/', '', htmlspecialchars($row['Message']));
    $row['File'] = preg_replace('/[^\x20-\x7E]/', '', strip_tags($row['File']));
    $rows[] = $row;
  }
  $data['rows'] = $rows;
  $data['logstate'] = logState();
  $data['updated'] = $dateTimeFormatter->format(time());

  return $data;
}
