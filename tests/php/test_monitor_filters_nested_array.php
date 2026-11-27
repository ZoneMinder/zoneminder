<?php
// The monitor filter bar reads Status, ServerId, StorageId, Capturing, Analysing, Recording,
// GroupId and MonitorId from the request or from the zmFilter_* cookie it set from the request.
// Each is a list of values. An element that is itself an array (Status[][]=x) went on into
// array_diff() and into the bound values of the monitor query, each an "Array to string
// conversion" warning (the test turns warnings into exceptions), the query matched "Array"
// instead of a value, and the cookie kept the array. Flat lists, single values and an empty
// list are kept.
//
// Run as: php tests/php/test_monitor_filters_nested_array.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$cookies_set = array();
function zm_setcookie($name, $value, $options = array()) { $GLOBALS['cookies_set'][$name] = $value; }

$source = file_get_contents(__DIR__.'/../../web/skins/classic/views/_monitor_filters.php');
foreach (array('filterSelectionScalars', 'getFilterSelection', 'monitorStatusFilterSql') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $source, $m)) {
    if ($fn === 'filterSelectionScalars') continue;
    echo "FAIL - could not find $fn\n";
    exit(1);
  }
  eval($m[0]);
}

set_error_handler(function($no, $str) { throw new ErrorException($str); });

// What the monitor query would be given for a Status filter taken from the request or the cookie.
function status_values($request, $cookie) {
  $_REQUEST = $request;
  $_COOKIE = $cookie;
  try {
    $values = array();
    monitorStatusFilterSql(getFilterSelection('Status'), $values, 'S.`Status`');
    return $values;
  } catch (Throwable $e) {
    return get_class($e).': '.$e->getMessage();
  }
}

function selection($request, $cookie) {
  $_REQUEST = $request;
  $_COOKIE = $cookie;
  try {
    return getFilterSelection('ServerId');
  } catch (Throwable $e) {
    return get_class($e).': '.$e->getMessage();
  }
}

check('a flat list is kept', status_values(array('Status' => array('Running', 'Connected')), array()), array('Running', 'Connected'));
check('a single value is kept', status_values(array('Status' => 'Running'), array()), array('Running'));
check('no value matches every status', status_values(array(), array()), array());
check('a list from the cookie is kept', status_values(array(), array('zmFilter_Status' => '["Running"]')), array('Running'));
check('a nested list is dropped', status_values(array('Status' => array(array('x'))), array()), array());
check('a nested element beside a value is dropped', status_values(array('Status' => array('Running', array('x'))), array()), array('Running'));
check('a nested list in the cookie is dropped', status_values(array(), array('zmFilter_Status' => '[["x"]]')), array());

check('a list of ids is kept', selection(array('ServerId' => array('1', '2')), array()), array('1', '2'));
check('an empty list is kept', selection(array(), array('zmFilter_ServerId' => '[]')), array());
check('a nested id list is dropped', selection(array('ServerId' => array(array('1'))), array()), array());
$cookies_set = array();
selection(array('ServerId' => array('1', array('2'))), array());
check('the cookie keeps only the ids', $cookies_set, array('zmFilter_ServerId' => '["1"]'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
