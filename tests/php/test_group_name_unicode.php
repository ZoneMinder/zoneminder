<?php
// A Group Name with non-ASCII letters must not be stripped by the input
// filter, while punctuation outside the allowed set still is.
//
// Run as: php tests/php/test_group_name_unicode.php [group_include_dir]

namespace ZM;

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_group_unicode_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}

$group_override = isset($argv[1]) ? $argv[1] : null;
$include = $stubdir.PATH_SEPARATOR;
if ($group_override) {
  $include .= $group_override.PATH_SEPARATOR;
}
$include .= __DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path();
set_include_path($include);

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
// Copied from web/includes/functions.php; Object.php changes() calls it.
function scalar_values_differ($old, $new) {
  if (is_array($old) or is_array($new)) {
    return $old != $new;
  }
  if (is_bool($old)) $old = (int)$old;
  if (is_bool($new)) $new = (int)$new;
  return (string)($old === null ? '' : $old) !== (string)($new === null ? '' : $new);
}

require_once 'Object.php';
require_once 'Group.php';

$g = new Group();
$g->set($g->changes(array('Name' => "Café")));
check('accented name kept', $g->Name(), "Café");

$g = new Group();
$g->set($g->changes(array('Name' => "カメラ")));
check('japanese name kept', $g->Name(), "カメラ");

$g = new Group();
$g->set($g->changes(array('Name' => "a<b>")));
check('disallowed punctuation stripped', $g->Name(), "ab");

array_map('unlink', glob($stubdir.'/*'));
rmdir($stubdir);
exit($failures ? 1 : 0);
