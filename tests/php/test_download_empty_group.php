<?php
/*
 * Downloading events with a group selected that has no monitors must match no
 * events. The Monitor term was added with an empty value, which FilterTerm
 * treats as no term, so every event was exported.
 *
 * Run: php tests/php/test_download_empty_group.php [root]
 */
namespace ZM {
  class Group {
    public static $rows = array(1=>array(), 2=>array(10, 11), 3=>array(11, 12));
    private $id;
    public function __construct($id) { $this->id = $id; }
    public static function find_one($p) { return isset(self::$rows[$p['Id']]) ? new Group($p['Id']) : null; }
    public function MonitorIds() { return self::$rows[$this->id]; }
    public function Name() { return 'G'.$this->id; }
  }
}
namespace {
  $root = $argv[1] ?? dirname(__DIR__, 2);
  class FakeFilter {
    public $terms = array();
    public function addTerm($t) { $this->terms[] = $t; return $this; }
  }
  $tests = 0; $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
  }

  $src = file_get_contents($root.'/web/ajax/modals/download.php');
  if (!preg_match('/if \(isset\(\$_REQUEST\[\'GroupId\'\]\).*?\n  \}\n/s', $src, $m)) {
    echo "group block not found\n"; exit(1);
  }
  function run_block($code, $groups) {
    $filter = new FakeFilter();
    $_REQUEST = array('GroupId'=>$groups);
    $exportFileName = '';
    eval($code);
    return $filter->terms;
  }
  function monitor_val($terms) {
    return count($terms) ? $terms[0]['val'] : null;
  }
  // FilterTerm::valid() drops a Monitor term whose value is the empty string.
  $empty = run_block($m[0], array('1'));
  check('an empty group adds a Monitor term', count($empty) === 1);
  check('the term value is not empty, so it is not dropped', count($empty) === 1 and $empty[0]['val'] !== '');
  $none = run_block($m[0], array('99'));
  check('a missing group adds a term that is not empty', count($none) === 1 and $none[0]['val'] !== '');
  $two = run_block($m[0], array('2', '3'));
  check('monitors of several groups are listed once', count($two) === 1 and $two[0]['val'] === '10,11,12');

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
