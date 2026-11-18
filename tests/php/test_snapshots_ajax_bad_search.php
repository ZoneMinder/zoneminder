<?php
// ajax/snapshots.php task=query: an advsearch value that is not a JSON object
// decoded to null and count(null) threw a TypeError (HTTP 500); an array
// search or sort value was concatenated into a string, and an array order
// reached strtolower().
// Run: php tests/php/test_snapshots_ajax_bad_search.php   (from the repo root)

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  function Warning($m) {}
  class Filter {
    public function addTerms($t) { return $this; }
    public function sql() { return ''; }
  }
  class Snapshot {
    public function __construct($r = null) {}
  }
}

namespace {
  $cases = array(
    'garbage advsearch' => array('advsearch' => 'garbage'),
    'array search' => array('search' => array('a')),
    'array sort' => array('sort' => array('Id')),
    'array order' => array('order' => array('asc')),
  );
  // The ajax file declares functions, so each case runs in its own process.
  if (!isset($argv[1])) {
    $fail = 0;
    foreach (array_keys($cases) as $label) {
      passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($label), $rc);
      if ($rc) $fail++;
    }
    exit($fail ? 1 : 0);
  }

  $stub = sys_get_temp_dir().'/zm_snap_ajax_'.getmypid();
  mkdir($stub.'/includes', 0777, true);
  foreach (array('Snapshot', 'Filter') as $f) file_put_contents($stub.'/includes/'.$f.'.php', "<?php\n");
  set_include_path($stub.PATH_SEPARATOR.get_include_path());

  class StubUser { public function Username() { return 'u'; } }
  class StubFmt { public function format($t) { return 'x'; } }
  $user = new StubUser();
  $dateTimeFormatter = new StubFmt();
  function canView($a) { return true; }
  function ajaxError($m) { $GLOBALS['err'] = $m; }
  function ajaxResponse($d) { $GLOBALS['resp'] = $d; }
  function dbFetchOne($sql, $col = null, $v = null) { return 0; }
  function dbFetchAll($sql, $col = null, $v = null) { return array(); }
  function validHtmlStr($s) { return $s; }

  $label = $argv[1];
  $resp = null;
  $_REQUEST = array_merge(array('task' => 'query'), $cases[$label]);
  $threw = null;
  set_error_handler(function($n, $s) use (&$threw) { $threw = $s; return true; });
  try {
    include __DIR__.'/../../web/ajax/snapshots.php';
  } catch (\Throwable $e) {
    $threw = get_class($e).': '.$e->getMessage();
  }
  system('rm -rf '.escapeshellarg($stub));
  $ok = ($threw === null and is_array($resp));
  echo ($ok ? 'ok - ' : 'NOT OK - ')."$label answers without errors".($threw ? " ($threw)" : '')."\n";
  exit($ok ? 0 : 1);
}
