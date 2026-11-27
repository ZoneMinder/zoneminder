<?php
// web/ajax/add_monitors.php
//  - The CSV import read lines with fgetcsv($handle, 1000), so a line over 1000
//    bytes (a long rtsp URL with credentials and a query) was split: the monitor
//    got a cut Path and the rest of the line became an extra row.
//  - The probe passed a url[] array to preg_match()/parse_url(), a TypeError (500).
//
// Runs the action part of the real file with stubbed helpers.
// php tests/php/test_add_monitors_ajax.php

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

  $src = file_get_contents(__DIR__.'/../../web/ajax/add_monitors.php');
  $start = strpos($src, "if (canEdit('Monitors')) {");
  $code = substr($src, $start);
  $code = str_replace('is_uploaded_file(', 'is_file(', $code);
  $code = preg_replace('/\?>\s*$/', '', $code);

  $passes = 0;
  $failures = 0;
  function check($name, $got, $want) {
    global $passes, $failures;
    if ($got === $want) { $passes++; echo "ok - $name\n"; }
    else { $failures++; echo "FAIL - $name\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n"; }
  }
  function run($code) {
    global $user;
    $user = null;
    try {
      eval($code);
    } catch (Responded $r) {
      return $r->data;
    } catch (\Throwable $e) {
      return array('throwable' => get_class($e));
    }
    return null;
  }

  // Import: one long line followed by a short one
  $long = 'rtsp://user:pass@192.0.2.10:554/stream?'.str_repeat('a=1&', 370).'end=1';
  $csv = tempnam(sys_get_temp_dir(), 'zmcsv');
  file_put_contents($csv, "Door,$long\nGate,rtsp://192.0.2.11/live\n");
  $_REQUEST = array('action' => 'import');
  $_FILES = array('import_file' => array('tmp_name' => $csv, 'error' => 0));
  $r = run($code);
  $streams = isset($r['Streams']) ? $r['Streams'] : array();
  check('a long CSV line imports as one monitor plus the next', count($streams), 2);
  check('the long URL is kept whole', isset($streams[0]) ? $streams[0]['url'] : null, $long);
  check('the following row is the next monitor', isset($streams[1]) ? $streams[1]['camera']['Name'] : null, 'Gate');
  unlink($csv);

  // Probe with an array url
  $_REQUEST = array('action' => 'probe', 'url' => array('x'));
  $r = run($code);
  check('probe with url[] does not throw', isset($r['throwable']) ? $r['throwable'] : null, null);

  // Probe without url still answers
  $_REQUEST = array('action' => 'probe', 'url' => '192.0.2.5');
  $r = run($code);
  check('probe with a host answers with streams', isset($r['Streams']), true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
