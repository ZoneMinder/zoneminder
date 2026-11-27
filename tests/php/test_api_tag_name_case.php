<?php
// The API refused a tag only when its name matched an existing one exactly. Tags.Name is a case
// sensitive column, so adding "Dog" next to "dog" or renaming a tag to a name that differs only in
// case succeeded, and the tag list then showed two tags that look the same. The web tag actions
// refuse both. The Cake model is stubbed over an in-memory Tags table and its Name rules are run
// the way Cake runs them, so this works from a source checkout:
//   php tests/php/test_api_tag_name_case.php [repo root]

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Tag';
  public $id = false;
  public $validate = array();
  public static $tags = array();
  public function query($sql, $params = array()) {
    if (strpos($sql, 'SELECT Name') === 0) {
      return isset(self::$tags[$params[0]]) ? array(array('Tags' => array('Name' => self::$tags[$params[0]]))) : array();
    }
    $rows = array();
    foreach (self::$tags as $id => $name) {
      if ($id != $params[1] and mb_strtolower($name) === mb_strtolower($params[0])) $rows[] = array('Tags' => array('Id' => $id));
    }
    return $rows;
  }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/api/app/Model/AppModel.php';
require_once $root.'/web/api/app/Model/Tag.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

// Whether the Name rules accept $name for the tag $id (false for a new tag), as Cake's validation does.
function accepted($name, $id) {
  $tag = new Tag();
  $tag->id = $id;
  foreach ($tag->validate['Name'] as $rule) {
    $method = $rule['rule'][0];
    if ($method == 'notBlank') {
      $ok = trim($name) !== '';
    } else if ($method == 'isUnique') {
      // Cake compares in SQL with the column's case sensitive collation.
      $ok = true;
      foreach (Model::$tags as $tid => $existing) if ($tid != $id and $existing === $name) $ok = false;
    } else {
      $ok = $tag->$method(array('Name' => $name));
    }
    if (!$ok) return false;
  }
  return true;
}

Model::$tags = array(1 => 'dog', 2 => 'Cat');

check('a new tag with an unused name is accepted', accepted('bird', false), true);
check('a new tag with an existing name is refused', accepted('dog', false), false);
check('a new tag differing only in case is refused', accepted('Dog', false), false);
check('a new tag differing only in case from a capitalised name is refused', accepted('cAT', false), false);
check('renaming a tag to another tag name differing in case is refused', accepted('CAT', 1), false);
check('changing only the case of a tag own name is accepted', accepted('Dog', 1), true);
check('keeping a tag own name is accepted', accepted('dog', 1), true);
Model::$tags = array(1 => 'dog', 2 => 'Dog');
check('a tag that already has a twin keeps its own name', accepted('Dog', 2), true);
check('that tag still cannot take another name differing in case', accepted('DOG', 2), false);
Model::$tags = array(1 => 'dog', 2 => 'Cat');
check('a blank name is still refused', accepted('  ', false), false);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
