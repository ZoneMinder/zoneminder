<?php
// The files view served any file below any Storage path to a user with only Events=View,
// including events of monitors the user may not view and the Storage root listing.
// Without System=View it now serves only paths inside an event folder of a viewable monitor.
//
// Run as: php tests/php/test_files_view_monitor_acl.php [web root]
// Each case runs in a child process because the view declares functions.

namespace ZM {
  class Storage {
    public $p;
    function __construct($p) { $this->p = $p; }
    function Path() { return $this->p; }
    static function find() { return array(new Storage($GLOBALS['store'])); }
  }
  class Monitor {
    public $id;
    function __construct($id) { $this->id = $id; }
    static function find_one($f) { return new Monitor($f['Id']); }
    function canView() { return $this->id == 1; }
  }
  function Error($s) {}
  function Warning($s) {}
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  class Stop extends Exception {}
  $GLOBALS['system'] = false;
  function canView($r) { return $r == 'System' ? $GLOBALS['system'] : true; }
  function detaintPathAllowAbsolute($path) {
    if (!is_scalar($path)) return '';
    do { $path = str_replace('://', '', $path, $count); } while($count);
    do { $path = str_replace('../', '', $path, $count); } while($count);
    return $path;
  }
  function array_to_hash_by_key($k, $a) { return array(); }
  function output_file($p) { $GLOBALS['served'][] = $p; return true; }
  function translate($s) { return $s; }
  function xhtmlHeaders() { throw new Stop(); }

  if (isset($argv[2])) {
    $store = $argv[3];
    $path = $argv[2];
    $system = isset($argv[4]);
    $served = array(); $view = 'files';
    $_REQUEST = array('path' => $path);
    $GLOBALS['system'] = $system;
    try { include $root.'/skins/classic/views/files.php'; echo "RESULT ".($view == 'error' ? 'denied' : ($served ? 'served' : 'none'))."\n"; }
    catch (Stop $e) { echo "RESULT page\n"; }
    exit(0);
  }

  $store = sys_get_temp_dir().'/zm_files_acl_'.getmypid();
  mkdir("$store/1/e", 0777, true);
  mkdir("$store/2/e", 0777, true);
  file_put_contents("$store/1/e/a.jpg", 'a');
  file_put_contents("$store/2/e/b.jpg", 'b');
  file_put_contents("$store/secret.txt", 's');

  function run($root, $path, $system = false) {
    global $store;
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' '
      .escapeshellarg($path).' '.escapeshellarg($store).($system ? ' system' : '').' 2>/dev/null');
    return preg_match('/^RESULT (.*)$/m', (string)$out, $m) ? $m[1] : 'no result';
  }
  check('file of a viewable monitor is served', run($root, "$store/1/e/a.jpg"), 'served');
  check('file of a denied monitor is refused', run($root, "$store/2/e/b.jpg"), 'denied');
  check('file in the Storage root is refused', run($root, "$store/secret.txt"), 'denied');
  check('Storage root listing is refused', run($root, $store), 'denied');
  check('a denied monitor folder listing is refused', run($root, "$store/2/e"), 'denied');
  check('folder of a viewable monitor lists', run($root, "$store/1/e"), 'page');
  check('a final dot segment inside a viewable monitor folder lists', run($root, "$store/1/."), 'page');
  check('a final dot dot after a viewable monitor folder (the Storage root) is refused', run($root, "$store/1/.."), 'denied');
  check('dot then dot dot after a viewable monitor folder is refused', run($root, "$store/1/./.."), 'denied');
  check('a dot segment before a denied monitor folder is refused', run($root, "$store/./2/e/b.jpg"), 'denied');
  check('a trailing slash and dot on the Storage root is refused', run($root, "$store/."), 'denied');
  check('System view may read other monitors', run($root, "$store/2/e/b.jpg", true), 'served');

  shell_exec('rm -rf '.escapeshellarg($store));
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
