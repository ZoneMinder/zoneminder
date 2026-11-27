<?php
/*
 * Group::get_dropdown_options() can run more than once per request (the filter
 * editor calls it once per group term), so it must not declare a named function.
 * validParent() refuses parents that would make a loop in the hierarchy.
 *
 * Run: php tests/php/test_group_model.php
 */
namespace ZM {
  class ZM_Object {
    public function __construct($p = null) {
      if (is_array($p)) foreach ($p as $k => $v) $this->{$k} = $v;
    }
    public function Id() { return $this->{'Id'} ?? null; }
    public function Name() { return $this->{'Name'} ?? null; }
    public function ParentId() { return $this->{'ParentId'} ?? null; }
    public static function _find($class, $params = null, $options = null) {
      global $T_groups, $T_monitors;
      $rows = ($class === Group::class) ? $T_groups : $T_monitors;
      $out = array();
      foreach ($rows as $row) {
        $ok = true;
        foreach ((array)$params as $f => $v) {
          if (is_array($v)) { if (!in_array($row[$f], $v)) $ok = false; }
          else if ($v === null) { if ($row[$f] !== null) $ok = false; }
          else if ($row[$f] != $v) $ok = false;
        }
        if ($ok) $out[] = new $class($row);
      }
      return $out;
    }
    public static function _find_one($class, $params = array(), $options = array()) {
      $r = self::_find($class, $params, $options);
      return $r ? $r[0] : null;
    }
  }
  class Monitor extends ZM_Object {
    public static function find($p = array(), $o = array()) { return ZM_Object::_find(self::class, $p, $o); }
    public function canView($u = null) { return true; }
  }
  class Group_Permission extends ZM_Object {}
}
namespace {
  $T_groups = array(
    array('Id'=>1, 'Name'=>'Parent', 'ParentId'=>null),
    array('Id'=>2, 'Name'=>'Child', 'ParentId'=>1),
  );
  $T_monitors = array(
    array('Id'=>10, 'Name'=>'A', 'Deleted'=>0),
    array('Id'=>11, 'Name'=>'B', 'Deleted'=>0),
  );
  $T_gm = array(1=>array(10), 2=>array(11));
  function dbFetchAll($sql, $col = null, $params = array()) {
    global $T_gm;
    if (strpos($sql, 'Groups_Monitors') !== false) return $T_gm[$params[0]] ?? array();
    return array();
  }
  function dbQuery() {}
  function Warning() {}
  function Error() {}

  $tests = 0; $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
  }

  $src = file_get_contents(dirname(__DIR__, 2).'/web/includes/Group.php');
  eval('?>'.preg_replace('/^<\?php\s*namespace ZM;/', '<?php namespace ZM;', $src));

  $first = ZM\Group::get_dropdown_options();
  check('dropdown lists both groups', count($first) === 2);
  $second = ZM\Group::get_dropdown_options();
  check('dropdown can be built twice in one request', $second === $first);

  check('no parent is valid', ZM\Group::validParent(2, '') && ZM\Group::validParent(2, null));
  check('a descendant is not a valid parent', ZM\Group::validParent(1, 2) === false && ZM\Group::validParent(2, 1) === true);
  check('a group cannot be its own parent', ZM\Group::validParent(1, 1) === false);
  check('a missing group is not a valid parent', ZM\Group::validParent(1, 99) === false);
  check('a non numeric parent is rejected', ZM\Group::validParent(1, 'abc') === false);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
