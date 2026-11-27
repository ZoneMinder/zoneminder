<?php
// views/js/event.js.php wrote the storage name into eventData through
// validHtmlStr(), and the event view's details table then escapes it again with
// escapeHTML(). A storage called Joe's NAS showed as Joe&#039;s NAS on the first
// page load, while an event reached through zms (whose status reply is raw) showed
// it correctly. The name has to reach the page as the name.
//
// Run as: php tests/php/test_event_js_storage_name.php   (from the repo root)

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

define('ZM_WEB_REFRESH_STATUS', 5);
define('ZM_WEB_LIST_THUMB_WIDTH', 100);
define('ZM_WEB_LIST_THUMB_HEIGHT', 75);
function validJsStr($v) { return is_null($v) ? '' : strip_tags(addslashes($v)); }
function validHtmlStr($v) { return is_null($v) ? '' : htmlspecialchars($v, ENT_QUOTES); }
function translate($s) { return $s; }
function canStreamNative() { return true; }
function human_filesize($v) { return (string)$v; }

class FakeName {
  private $n;
  public function __construct($n) { $this->n = $n; }
  public function Name() { return $this->n; }
  public function UrlToIndex() { return 'https://zm.example/zm/index.php'; }
}
class FakeFormatter {
  public function format($t) { return 'formatted'; }
}
class FakeEvent {
  public $Archived = 0;
  public $Emailed = 0;
  private $storage;
  private $secondary;
  public function __construct($storage, $secondary) {
    $this->storage = $storage;
    $this->secondary = $secondary;
  }
  public function Id() { return 5; }
  public function Name() { return 'Event 5'; }
  public function MonitorId() { return 1; }
  public function Cause() { return 'Motion'; }
  public function Tags() { return array(); }
  public function Notes() { return ''; }
  public function Width() { return 640; }
  public function Height() { return 480; }
  public function Length() { return 10; }
  public function StartDateTime() { return '2026-01-01 00:00:00'; }
  public function EndDateTime() { return '2026-01-01 00:00:10'; }
  public function Frames() { return 100; }
  public function AlarmFrames() { return 10; }
  public function TotScore() { return 50; }
  public function AvgScore() { return 5; }
  public function MaxScore() { return 9; }
  public function DiskSpace($x = null) { return 1024; }
  public function Storage() { return new FakeName($this->storage); }
  public function SecondaryStorageId() { return $this->secondary === null ? 0 : 2; }
  public function SecondaryStorage() { return new FakeName($this->secondary); }
  public function DefaultVideo() { return ''; }
  public function Path() { return '1/2026-01-01/5'; }
  public function Latitude() { return ''; }
  public function Longitude() { return ''; }
  public function Server() { return new FakeName(''); }
}
class FakeMonitor {
  public function Name() { return 'Front'; }
  public function WhatDisplay() { return 'Events'; }
  public function LabelFormat() { return ''; }
}

// The eventData.Storage the page script carries for these storage names, decoded
// the way the browser reads the JavaScript literal.
function storageInPage($storage, $secondary) {
  global $dateTimeFormatter, $connkey, $Event, $monitor, $filterQuery, $sortQuery, $rates, $rate, $scale, $streamMode, $popup;
  $dateTimeFormatter = new FakeFormatter();
  $connkey = 1234;
  $Event = new FakeEvent($storage, $secondary);
  $monitor = new FakeMonitor();
  $filterQuery = '';
  $sortQuery = '';
  $rates = array(100 => '1x');
  $rate = 100;
  $scale = 0;
  $streamMode = 'jpeg';
  $popup = 0;
  ob_start();
  require __DIR__.'/../../web/skins/classic/views/js/event.js.php';
  $js = ob_get_clean();
  if (!preg_match('/^\s*Storage: (.*),$/m', $js, $m)) return null;
  $lit = $m[1];
  if ($lit[0] == "'") {
    // A literal that does not close where the line ends is a syntax error in the browser
    return preg_match("/^'((?:[^'\\\\]|\\\\.)*)'$/", $lit, $q) ? stripcslashes($q[1]) : null;
  }
  return json_decode($lit);
}

check("apostrophe and ampersand", storageInPage("Joe's NAS & Co", null), "Joe's NAS & Co");
check("angle brackets and quotes", storageInPage('A <b> "x"', null), 'A <b> "x"');
check("trailing backslash", storageInPage('disk\\', null), 'disk\\');
check("primary and secondary", storageInPage("Joe's", 'R&D'), "Joe's, R&D");
check("plain name", storageInPage('Default', null), 'Default');

exit($failures ? 1 : 0);
