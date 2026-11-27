<?php
// Options > Servers showed the "no stats yet" placeholder (-1, which Server::ReadStats
// leaves in place when zmstats has no recent row) as a CPU load of -1 and as
// "-1.00B / -1.00B" for memory and swap. The navbar already shows n/a for this.
//
// Run: php tests/php/test_servers_list_no_stats.php [repo root]   (default: this repo)
namespace ZM {
  class Server {
    public static $rows = array();
    public $s;
    public function __construct($s) { $this->s = $s; }
    public static function find() { return array_map(function($s) { return new Server($s); }, self::$rows); }
    public function ReadStats() {}
    public function __call($n, $a) { return $this->s[$n] ?? ''; }
  }
}
namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  function translate($s) { return $s; }
  function validHtmlStr($s) { return $s; }
  function makeLink($u, $t, $c, $o = '') { return $t; }
  function dbFetchAssoc() { return array(1 => 0); }
  $fsrc = file_get_contents($root.'/web/includes/functions.php');
  preg_match('/^function human_filesize\(.*?\n\}/ms', $fsrc, $m);
  eval($m[0]);
  $view = 'options'; $tab = 'servers'; $canEdit = true; $cspNonce = '';

  function render($s) {
    global $root, $view, $tab, $canEdit, $cspNonce;
    \ZM\Server::$rows = array($s);
    ob_start();
    include $root.'/web/skins/classic/views/_options_servers.php';
    return ob_get_clean();
  }
  function cell($html, $class) {
    preg_match('/<td class="'.$class.'[^"]*">(.*?)<\/td>/s', $html, $m);
    return trim($m[1] ?? 'MISSING');
  }
  $fail = 0;
  function check($name, $got, $want) {
    global $fail;
    if ($got === $want) { echo "ok - $name\n"; return; }
    $fail++; echo "FAIL - $name: got '$got' want '$want'\n";
  }
  $base = array('Id' => 1, 'Name' => 'a', 'Status' => 'Running');
  $h = render($base + array('CpuLoad' => -1, 'TotalMem' => -1, 'FreeMem' => -1, 'TotalSwap' => -1, 'FreeSwap' => -1));
  check('no stats cpu', cell($h, 'colCpuLoad'), 'n/a');
  check('no stats memory', cell($h, 'colMemory'), 'n/a');
  check('no stats swap', cell($h, 'colSwap'), 'n/a');
  $h = render($base + array('CpuLoad' => 0.5, 'TotalMem' => 2048, 'FreeMem' => 1024, 'TotalSwap' => 2048, 'FreeSwap' => 1024));
  check('stats cpu', cell($h, 'colCpuLoad'), '0.5');
  check('stats memory', cell($h, 'colMemory'), '1.00kB / 2.00kB');
  check('stats swap', cell($h, 'colSwap'), '1.00kB / 2.00kB');
  exit($fail ? 1 : 0);
}
