<?php
$data = array();
$message = '';

//
// INITIALIZE AND CHECK SANITY
//

// uid must be set
if (empty($_REQUEST['uid'])) {
  $message = 'No user id supplied';
} else {
  $uid = validInt($_REQUEST['uid']);
  $User = new ZM\User($uid);
  if (!$User->Id()) {
    $message = 'Invalid user id supplied';
  }
}

// Check permissions
$selfEdit = ZM_USER_SELF_EDIT && (isset($uid) && $uid == $user->Id());
if (!canEdit('System') && !$selfEdit) {
  $message = 'Insufficient permissions for user '.$user->Username();
}

// task must be set
if (empty($_REQUEST['task'])) {
  $message = 'This request requires a task to be set';
// query is the only supported task at the moment
} else if ($_REQUEST['task'] != 'query') {
  $message = 'Unrecognised task '.$_REQUEST['task'];
} else {
  $task = $_REQUEST['task'];
}

if ($message) {
  ajaxError($message);
  return;
}

// Search contains a user entered string to search on
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';

// Advanced search contains an array of "column name" => "search text" pairs
// Bootstrap table sends json_encoded array, which we must decode
$advsearch = isset($_REQUEST['advsearch']) ? json_decode($_REQUEST['advsearch'], JSON_OBJECT_AS_ARRAY) : array();

// Sort specifies the name of the column to sort on
$sort = 'Sequence';
if (isset($_REQUEST['sort'])) {
  $sort = $_REQUEST['sort'];
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

// Order specifies the sort direction, either asc or desc
$order = (isset($_REQUEST['order']) and (strtolower($_REQUEST['order']) == 'asc')) ? 'ASC' : 'DESC';

// Limit specifies the number of rows to return
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

// Only one supported task at the moment
switch ($task) {
  case 'query':
    $data = queryRequest($User, $search, $advsearch, $sort, $offset, $order, $limit);
    break;
  default:
    ZM\Fatal("Unrecognised task '$task'");
} // end switch task

ajaxResponse($data);

//
// FUNCTION DEFINITIONS
//

function queryRequest($User, $search, $advsearch, $sort, $offset, $order, $limit) {
  global $dateTimeFormatter;
  
  $data = array(
    'total'   =>  0,
    'totalNotFiltered' => 0,
    'rows'    =>  array(),
    'updated' =>  $dateTimeFormatter->format(time())
  );

  // The names of the dB columns in the monitors table we are interested in
  $columns = array('Id', 'Name', 'Sequence');

  if (!in_array($sort, $columns)) {
    ZM\Error('Invalid sort field: ' . $sort);
    $sort = 'Sequence';
  }

  $values = array();
  $likes = array();
  $where = 'WHERE Deleted = false';

  // Build search query
  if ($search != '') {
    $search = '%' . $search . '%';
    $likes[] = '(M.Id LIKE ? OR M.Name LIKE ?)';
    array_push($values, $search, $search);
  }

  // Add advanced search
  foreach ($advsearch as $col => $text) {
    if (!in_array($col, $columns)) {
      ZM\Error("Skipping unrecognised search field '$col'");
      continue;
    }
    $text = '%' . $text . '%';
    $likes[] = 'M.'.$col.' LIKE ?';
    $values[] = $text;
  }

  if (count($likes)) {
    $where .= ' AND (' . implode(' OR ', $likes) . ')';
  }

  $col_str = 'M.Id, M.Name, M.Sequence';
  $sql = 'SELECT ' . $col_str . ' FROM Monitors AS M ' . $where;

  // Get total count without search filter (totalNotFiltered)
  $countSqlNoFilter = 'SELECT COUNT(*) AS total FROM Monitors AS M WHERE Deleted = false';
  $result = dbQuery($countSqlNoFilter);
  if ($result) {
    $row = dbFetchNext($result);
    $data['totalNotFiltered'] = $row['total'];
  }

  // Get total count with search filter (total)
  $countSql = 'SELECT COUNT(*) AS total FROM Monitors AS M ' . $where;
  $result = dbQuery($countSql, $values);
  if ($result) {
    $row = dbFetchNext($result);
    $data['total'] = $row['total'];
  }

  // Add sorting and pagination
  $sql .= ' ORDER BY M.' . $sort . ' ' . $order;
  if ($limit) {
    $sql .= ' LIMIT ' . intval($offset) . ', ' . intval($limit);
  }

  ZM\Debug('Calling the following sql query: ' . $sql);
  $result = dbQuery($sql, $values);
  if (!$result) {
    ajaxError(dbError($sql));
    return $data;
  }

  $rows = array();
  while ($row = dbFetchNext($result)) {
    $monitor = new ZM\Monitor($row);
    
    // Only include monitors the user can view
    if (!$monitor->canView()) {
      continue;
    }

    // Get the monitor permission for this user
    $monitorPermission = $User->Monitor_Permission($monitor->Id());
    $permission = $monitorPermission->Permission();
    
    // Get the effective permission
    $effectivePermission = $monitor->effectivePermission($User);

    $rows[] = array(
      'Id' => $monitor->Id(),
      'Name' => validHtmlStr($monitor->Name()),
      'Sequence' => $monitor->Sequence(),
      'Permission' => $permission,
      'EffectivePermission' => translate($effectivePermission)
    );
  }

  $data['rows'] = $rows;

  return $data;
}
?>
