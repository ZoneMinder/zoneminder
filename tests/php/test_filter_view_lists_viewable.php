<?php
/**
 * The filter view built its list of filters, and so the filters it could load
 * by Id, from every saved filter with only the Events view permission, so a
 * user could open another user's filter (its command and email address); it
 * now lists only filters the user can view.
 */
namespace ZM {
  class Filter {
    public static function find($p = null, $o = null) {
      return $GLOBALS['filters'];
    }
  }
}

namespace {
  class FakeFilter {
    private $id;
    private $name;
    private $viewable;
    private $bg;
    private $conc;

    public function __construct($id, $name, $viewable, $bg = false, $conc = false) {
      $this->id = $id;
      $this->name = $name;
      $this->viewable = $viewable;
      $this->bg = $bg;
      $this->conc = $conc;
    }

    public function Id() { return $this->id; }
    public function Name() { return $this->name; }
    public function Background() { return $this->bg; }
    public function Concurrent() { return $this->conc; }
    public function canView() { return $this->viewable; }
  }

  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $path = $root.'/web/skins/classic/views/filter.php';
  $src = file_get_contents($path);
  if ($src === false) {
    fwrite(STDERR, "FAIL - could not read $path\n");
    exit(1);
  }

  if (!preg_match('/^# Build filter names list.*?\n\}\n/ms', $src, $m)) {
    fwrite(STDERR, "FAIL - could not find the filter names list loop in $path\n");
    exit(1);
  }

  $GLOBALS['filters'] = array(
    new FakeFilter(1, 'mine', true),
    new FakeFilter(2, 'theirs', false),
    new FakeFilter(3, 'shared', true, true),
  );

  function runFilterNamesLoop($code) {
    $filterNames = array();
    eval($code);
    return $filterNames;
  }

  $filterNames = runFilterNamesLoop($m[0]);

  $failed = false;

  $keys = array_keys($filterNames);
  if ($keys === array(1, 3)) {
    echo "ok - only viewable filters are listed\n";
  } else {
    echo "FAIL - only viewable filters are listed (got " . var_export($keys, true) . ")\n";
    $failed = true;
  }

  if (!isset($filterNames[2])) {
    echo "ok - another user's filter is not listed\n";
  } else {
    echo "FAIL - another user's filter is not listed (got " . var_export($filterNames[2], true) . ")\n";
    $failed = true;
  }

  if (isset($filterNames[3]) && $filterNames[3] === 'shared*') {
    echo "ok - background filter name gets '*'\n";
  } else {
    echo "FAIL - background filter name gets '*' (got " . var_export(isset($filterNames[3]) ? $filterNames[3] : null, true) . ")\n";
    $failed = true;
  }

  exit($failed ? 1 : 0);
}
