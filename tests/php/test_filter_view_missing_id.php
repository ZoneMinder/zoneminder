<?php
// ?view=filter&Id=N for a filter that no longer exists, or that the user may not
// see, rendered the blank new-filter form, and saving it created a new filter.
// The view shows the error page for such an id, as the report view does (#8146).
//
// Run as: php tests/php/test_filter_view_missing_id.php [web root]

namespace ZM {
  class ReachedForm extends \Exception {}
  function Warning($m) {}
  function Error($m) {}
  // The form is built right after the filter is chosen; stop there.
  function Debug($m) { if (strpos($m, 'filter: ') === 0) throw new ReachedForm(); }
  class Filter {
    public $id = null;
    public static $rows = array();
    function __construct($id = null) {}
    static function find($a = null, $b = null) {
      $out = array();
      foreach (self::$rows as $id => $own) { $f = new Filter(); $f->id = $id; $f->own = $own; $out[] = $f; }
      return $out;
    }
    static function find_one($p) { $f = new Filter(); $f->id = $p['Id']; return $f; }
    function canView() { return $this->own; }
    function Id($v = null) { if ($v !== null) $this->id = $v; return $this->id; }
    function Name() { return 'f'.$this->id; }
    function Background() { return 0; }
    function Concurrent() { return 0; }
    function addTerm($t) {}
    function set($a) {}
  }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web';
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }
  $stub = sys_get_temp_dir().'/zm_filter_missing_'.getmypid();
  @mkdir($stub.'/includes', 0777, true);
  foreach (array('Object', 'Storage', 'Filter', 'FilterTerm', 'Monitor', 'Zone', 'User', 'logpanel') as $f) {
    file_put_contents($stub."/includes/$f.php", "<?php\n");
  }
  set_include_path($stub.PATH_SEPARATOR.get_include_path());
  function canView($a) { return true; }
  function getSkinFile($f) { return $GLOBALS['stub'].'/'.$f; }
  function parseSort() {}
  function translate($s) { return $s; }
  function validInt($v) { return (int)$v; }

  // Filter 3 exists and is the user's, filter 4 is someone else's.
  ZM\Filter::$rows = array(3 => true, 4 => false);

  function open_filter($request) {
    $_REQUEST = $request;
    $view = 'filter';
    try {
      include $GLOBALS['root'].'/skins/classic/views/filter.php';
    } catch (ZM\ReachedForm $e) {
      return 'form';
    }
    return $view;
  }

  check('a filter that exists opens', open_filter(array('Id' => '3')), 'form');
  check('a deleted filter shows the error page', open_filter(array('Id' => '99')), 'error');
  check('another user\'s filter shows the error page', open_filter(array('Id' => '4')), 'error');
  check('no Id opens a new filter', open_filter(array()), 'form');

  foreach (glob($stub.'/includes/*') as $f) unlink($f);
  rmdir($stub.'/includes'); rmdir($stub);
  echo $failures ? "\n$failures check(s) FAILED\n" : "\nall checks passed\n";
  exit($failures ? 1 : 0);
}
