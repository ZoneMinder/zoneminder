<?php

// The Name and Source filter cookies hold whatever was typed. A cookie that is
// also valid JSON ("[12]", "123") used to come back from getFilterSelection()
// as an array or integer, and preg_match() on an array is a TypeError that
// takes the whole filter bar down until the cookie is cleared.

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo '    got:  '.var_export($got, true)."\n";
    echo '    want: '.var_export($want, true)."\n";
  }
}

function zm_setcookie($name, $value) {
  $_COOKIE[$name] = $value;
}

$path = __DIR__.'/../../web/skins/classic/views/_monitor_filters.php';
$src = file_get_contents($path);
foreach (array('filterSelectionScalars', 'getFilterSelection', 'getFilterText', 'monitorNameRegexp') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find $fn() in $path\n";
    exit(1);
  }
  eval($m[0]);
}

function textWith($cookie, $request = null) {
  $_COOKIE = array();
  $_REQUEST = array();
  if ($cookie !== null) $_COOKIE['zmFilter_MonitorName'] = $cookie;
  if ($request !== null) $_REQUEST['MonitorName'] = $request;
  return getFilterText('MonitorName');
}

check('plain cookie', textWith('door'), 'door');
check('empty cookie', textWith(''), '');
check('no cookie', textWith(null), '');
check('cookie that is a JSON array', textWith('[12]'), '[12]');
check('cookie that is a JSON number', textWith('123'), '123');
check('cookie that is JSON true', textWith('true'), 'true');
check('cookie written by getFilterSelection', textWith(null, 'cam[12]'), 'cam[12]');
check('request that is a JSON array string', textWith(null, '[12]'), '[12]');
check('request array is ignored', textWith(null, array('x')), '');
check('JSON string cookie from the request path', textWith('"[12]"'), '[12]');

$text = textWith('[12]');
check('a bracket-only filter matches as a regexp', preg_match(monitorNameRegexp($text), 'cam1'), 1);

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
