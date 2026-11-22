<?php
// GET /api/configs.json is meant to answer the effective value of each option: the DB row
// overlaid with any value set in /etc/zm/conf.d ($zm_config). index() built its name map
// with references to the foreach copy of each row, so the overlay changed the copies and
// the response kept the database values. An option overridden in conf.d was reported with
// the wrong value.
//
// ConfigsController can't be loaded without CakePHP, so index() is taken from the source
// and run against stubs.
// Run as: php tests/php/test_api_configs_index_confd.php [root]   (root defaults to the repo)

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$source = file_get_contents($root.'/web/api/app/Controller/ConfigsController.php');
$start = strpos($source, 'public function index(');
$depth = 0;
for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
  if ($source[$i] == '{') $depth++;
  else if ($source[$i] == '}' && --$depth == 0) break;
}
$method = substr($source, $start, $i - $start + 1);

class FakeConfig {
  public $recursive;
  public $rows = array();
  function find($type, $options = array()) { return $this->rows; }
  function schema() { return array('Name' => 1, 'Value' => 1); }
}
class FakeRequest { public $params = array('named' => array()); }

eval('class ConfigsHarness { public $request, $Config, $vars;
  function set($v) { $this->vars = $v; }
  function requireSystemView() {}
  function filterReferencesValue($p) { return false; }
  function isSecretConfig($c, $n = null) { return false; }
  function redactSecret($c, $n = null) { return $c; }
  '.$method.' }');

$failures = 0;
function check($n, $got, $want) {
  global $failures;
  if ($got === $want) echo "ok - $n\n";
  else { $failures++; echo "FAIL - $n (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
}

$zm_config = array(
  'ZM_WEB_TITLE' => array('Name' => 'ZM_WEB_TITLE', 'Value' => 'From conf.d'),
  'ZM_LANG_DEFAULT' => array('Name' => 'ZM_LANG_DEFAULT', 'Value' => 'en_gb'),
);
$h = new ConfigsHarness();
$h->request = new FakeRequest();
$h->Config = new FakeConfig();
$h->Config->rows = array(
  array('Config' => array('Name' => 'ZM_WEB_TITLE', 'Value' => 'From the database')),
  array('Config' => array('Name' => 'ZM_LANG_DEFAULT', 'Value' => 'en_gb')),
);
$h->index();
$byName = array();
foreach ($h->vars['configs'] as $c) $byName[$c['Config']['Name']] = $c['Config']['Value'];

check('an option overridden in conf.d is answered with the conf.d value', $byName['ZM_WEB_TITLE'] ?? null, 'From conf.d');
check('an option not overridden keeps its value', $byName['ZM_LANG_DEFAULT'] ?? null, 'en_gb');
check('each option is answered once', count($h->vars['configs']), 2);

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
