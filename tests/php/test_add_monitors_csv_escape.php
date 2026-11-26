<?php
// web/ajax/add_monitors.php
// The CSV import called fgetcsv() without the $escape argument, which PHP 8.4 deprecates (the default
// changes in PHP 9), so every row read raised "fgetcsv(): the $escape parameter must be provided".
// The call must pass $escape explicitly and still parse quoted fields as before.
//
// Runs the action part of the real file with stubbed helpers.
// php tests/php/test_add_monitors_csv_escape.php

namespace ZM {
  function Info($s) {}
  function Debug($s) {}
  function Warning($s) {}
  function urlForLog($s) { return $s; }
  class Monitor { static function find($w) { return array(); } }
  class Group { static function find_one($w) { return null; } }
}

namespace {
  class Responded extends Exception { public $data; function __construct($d) { $this->data = $d; parent::__construct('responded'); } }
  function canEdit($p) { return true; }
  function ajaxResponse($d) { throw new Responded($d); }
  function ajaxError($m) { throw new Responded(array('error' => $m)); }
  function validHtmlStr($s) { return $s; }
  function probe($bits) { return array(); }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $name\n"; }
    else { $failures++; echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n"; }
  }

  $src = file_get_contents(__DIR__.'/../../web/ajax/add_monitors.php');

  // Count the arguments of every fgetcsv() call.
  $tokens = token_get_all($src);
  $argCounts = array();
  for ($i = 0, $n = count($tokens); $i < $n; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] != T_STRING || $tokens[$i][1] != 'fgetcsv') continue;
    $depth = 0;
    $args = 1;
    for ($j = $i + 1; $j < $n; $j++) {
      $t = $tokens[$j];
      if ($t === '(' || $t === '[') $depth++;
      elseif ($t === ')' || $t === ']') { if (--$depth == 0) break; }
      elseif ($t === ',' && $depth == 1) $args++;
    }
    $argCounts[] = $args;
  }
  check('every fgetcsv() call passes the $escape argument', $argCounts, array_fill(0, count($argCounts), 5));
  check('add_monitors.php still reads a CSV with fgetcsv()', count($argCounts) > 0, true);

  $start = strpos($src, "if (canEdit('Monitors')) {");
  $code = substr($src, $start);
  $code = str_replace('is_uploaded_file(', 'is_file(', $code);
  $code = preg_replace('/\?>\s*$/', '', $code);

  $deprecations = array();
  set_error_handler(function($errno, $errstr) use (&$deprecations) {
    $deprecations[] = $errstr;
    return true;
  });
  $csv = tempnam(sys_get_temp_dir(), 'zmcsv');
  file_put_contents($csv, "Name,URL,Group\n\"Door, front\",rtsp://192.0.2.10/a\nGate,rtsp://192.0.2.11/b\n");
  $_REQUEST = array('action' => 'import');
  $_FILES = array('import_file' => array('tmp_name' => $csv, 'error' => 0));
  $user = null;
  try {
    eval($code);
    $r = null;
  } catch (Responded $e) {
    $r = $e->data;
  }
  unlink($csv);
  restore_error_handler();
  $streams = isset($r['Streams']) ? $r['Streams'] : array();
  check('the import reads the header and both rows', count($streams), 2);
  check('a quoted name keeps its comma', isset($streams[0]) ? $streams[0]['camera']['Name'] : null, 'Door, front');
  check('the import raises no PHP deprecation', $deprecations, array());

  exit($failures ? 1 : 0);
}
