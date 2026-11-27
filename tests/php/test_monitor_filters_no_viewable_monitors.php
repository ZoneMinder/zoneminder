<?php
// The monitor filter bar (buildMonitorsFilters() in views/_monitor_filters.php,
// used by the console, watch, cycle, montage review and event audit views)
// restricted a user to their viewable monitors with "M.Id IN (" + one
// placeholder per id + ")". For a user who can view no monitor that is
// "IN ()", which MariaDB rejects, so every one of those pages logged an SQL
// error. The block is cut out of the view and run with stub users, as
// test_viewable_monitors_empty.php does for the other views.
//
// Run as: php tests/php/test_monitor_filters_no_viewable_monitors.php   (from the repo root)

$failures = 0;
function check($name, $ok, $detail = '') {
  global $failures;
  if ($ok) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name".($detail !== '' ? " ($detail)" : '')."\n";
  }
}

class StubUser {
  private $viewable;
  private $unviewable;
  public function __construct(array $viewable, array $unviewable) {
    $this->viewable = $viewable;
    $this->unviewable = $unviewable;
  }
  public function viewableMonitorIds() { return $this->viewable; }
  public function unviewableMonitorIds() { return $this->unviewable; }
}

$src = file_get_contents(__DIR__.'/../../web/skins/classic/views/_monitor_filters.php');
$start = strpos($src, 'if (count($user->unviewableMonitorIds()) ) {');
$end = ($start === false) ? false : strpos($src, '$monitorNameValue', $start);
if ($start === false or $end === false) {
  echo "FAIL - restriction block not found\n";
  exit(1);
}
$block = substr($src, $start, $end - $start);

function restrict($block, $user) {
  $conditions = array('M.`Deleted`=false');
  $values = array();
  eval($block);
  return array($conditions, $values);
}

// No viewable monitor at all
list($conditions, $values) = restrict($block, new StubUser(array(), array(3, 4)));
$sql = implode(' AND ', $conditions);
check('no empty IN ()', !preg_match('/\bIN\s*\(\s*\)/i', $sql), $sql);
check('matches no monitor', (bool)preg_match('/\bM\.Id IN \(0\)/', $sql), $sql);
check('placeholders and values agree', substr_count($sql, '?') === count($values),
  substr_count($sql, '?').' placeholders, '.count($values).' values');

// Some viewable monitors: same SQL and values as before
list($conditions, $values) = restrict($block, new StubUser(array(1, 2), array(3)));
$sql = implode(' AND ', $conditions);
check('viewable monitors listed as placeholders', $sql === 'M.`Deleted`=false AND M.Id IN (?,?)', $sql);
check('viewable monitor ids bound', $values === array(1, 2), json_encode($values));

// Unrestricted user: nothing added
list($conditions, $values) = restrict($block, new StubUser(array(1, 2, 3), array()));
check('unrestricted user adds no condition', $conditions === array('M.`Deleted`=false') and $values === array(),
  json_encode($conditions));

echo $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
