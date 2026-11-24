<?php
// views/export.php ran each listed event's monitor DefaultScale through reScale() into a
// $scale nothing used. DefaultScale can be fit_to_width, which reScale() multiplies, so on
// PHP 8 the Export page died with a TypeError as soon as it listed an event from such a
// monitor, and a 640px style value raised a non-numeric warning per row.
//
// Run as: php tests/php/test_export_view_default_scale.php

namespace ZM {
  class Event {
    private $row;
    function __construct($row) { $this->row = $row; }
    function Monitor() { return new Monitor(); }
    function __call($name, $args) { return isset($this->row[$name]) ? $this->row[$name] : ''; }
  }
  class Monitor {
    function DefaultScale() { return $GLOBALS['defScale']; }
  }
}

namespace {
  define('ZM_WEB_DEFAULT_SCALE', 100);
  define('ZM_WEB_EVENT_DISK_SPACE', 0);
  define('SCALE_BASE', 100);
  class User {
    function unviewableMonitorIds() { return array(); }
    function viewableMonitorIds() { return array(1); }
  }
  class Results {
    function rowCount() { return 1; }
  }
  class Formatter {
    function format($t) { return 'time'; }
  }
  $user = new User();
  $dateTimeFormatter = new Formatter();
  function translate($s) { return $s; }
  function canView($a) { return true; }
  function canEdit($a) { return true; }
  function validInt($v) { return (int)$v; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s); }
  function generateConnKey() { return 1; }
  function xhtmlHeaders() {}
  function xhtmlFooter() {}
  function getBodyTopHTML() {}
  function getNavBarHTML() {}
  function sortHeader($s) { return ''; }
  function sortTag($s) { return ''; }
  function makeLink($url, $label) { return $label; }
  function html_radio() { return ''; }
  function human_filesize($n) { return $n; }
  function dbQuery($sql, $values) { $GLOBALS['rows'] = array(array('Id'=>5, 'MonitorId'=>1, 'Name'=>'Event-5', 'StartDateTime'=>'2026-11-21 10:00:00', 'Length'=>10)); return new Results(); }
  function dbFetchNext($r) { return array_shift($GLOBALS['rows']); }
  function parseSort($saveToSession=false, $querySep='&amp;') { global $sortQuery; $sortQuery = ''; }

  // The real helper
  $fn = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  preg_match('/^function reScale\(.*?^}/ms', $fn, $m);
  eval($m[0]);

  set_error_handler(function ($no, $str) { throw new ErrorException($str); });

  $failures = 0;
  function check($n, $got, $want) {
    global $failures;
    if ($got === $want) { echo "ok - $n\n"; } else { $failures++; echo "FAIL - $n (got ".var_export($got, true).")\n"; }
  }
  function view() {
    global $user, $dateTimeFormatter, $sortQuery;
    $_REQUEST = array('eids' => array('5'));
    $view = 'export';
    ob_start();
    try {
      include __DIR__.'/../../web/skins/classic/views/export.php';
    } catch (Throwable $e) {
      ob_end_clean();
      return 'threw: '.$e->getMessage();
    }
    return strpos(ob_get_clean(), 'Event-5') !== false ? 'ok' : 'event not listed';
  }

  foreach (array('0', '100', 'fit_to_width', '640px') as $defScale) {
    check("event from a monitor with DefaultScale $defScale is listed", view(), 'ok');
  }
  exit($failures ? 1 : 0);
}
