<?php
// The user and role views index the group tree by ParentId, which is null for a top-level
// group. From PHP 8.5 that raises a deprecation notice on every load once a top-level group
// exists. views/groups.php already maps null to ''.
//
// Run: php tests/php/test_user_role_group_root_offset.php [root]
// The deprecation only fires on PHP 8.5 or later; the source checks apply on any version.

$root = $argv[1] ?? dirname(__DIR__, 2);

class StubGroup {
  private $id, $parent;
  function __construct($id, $parent) { $this->id = $id; $this->parent = $parent; }
  function Id() { return $this->id; }
  function ParentId() { return $this->parent; }
  function depth() { return $this->parent === null ? 1 : 2; }
}
function group_line($group) { return 'line'.$group->Id().';'; }

$failures = 0;
function check($name, $ok) {
  global $failures;
  if ($ok) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n";
  }
}

foreach (array('user.php', 'role.php') as $file) {
  $src = file_get_contents($root.'/web/skins/classic/views/'.$file);
  check("$file does not index an array with null", strpos($src, '$children[null]') === false);

  $ok = preg_match('/(\$children = array\(\);\n\s*foreach \(\$groups as \$id=>\$group\) \{.*?\n  \}\n)/s', $src, $build)
    && preg_match('/(if \(isset\(\$children\[[^\]]*\]\)\) \{\n\s*foreach \(\$children\[[^\]]*\] as \$group\) \{\n\s*echo group_line\(\$group\);\n\s*\}\n\s*\})/', $src, $walk);
  check("$file: found the group tree code", $ok);
  if (!$ok) continue;

  $groups = array(1 => new StubGroup(1, null), 2 => new StubGroup(2, 1), 3 => new StubGroup(3, null));
  $max_depth = 0;
  $children = array();
  $deprecations = array();
  set_error_handler(function($no, $str) use (&$deprecations) { $deprecations[] = $str; return true; });
  ob_start();
  eval($build[1]);
  eval($walk[1]);
  $out = ob_get_clean();
  restore_error_handler();
  check("$file: building the tree raises nothing", count($deprecations) == 0);
  check("$file: both top-level groups are listed", $out === 'line1;line3;');
  check("$file: the deepest level is counted", $max_depth == 2);
}
exit($failures ? 1 : 0);
