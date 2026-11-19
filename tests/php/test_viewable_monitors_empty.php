<?php
// A user with Events view but no viewable monitors had "MonitorId IN ()" built
// in four places, which MariaDB rejects as a syntax error:
//   - web/includes/functions.php monitorLimitSql() (used by ajax/event.php)
//   - web/skins/classic/views/timeline.php
//   - web/skins/classic/views/montagereview.php
//   - web/skins/classic/views/zones.php
// report_event_audit.php already emitted IN (0) for an empty list; the same is
// expected here. The SQL-building blocks are cut out of the views and run with
// stub users, so the check is behavioural as well as textual.
//
// Run as: php tests/php/test_viewable_monitors_empty.php   (from the repo root)

$root = dirname(__DIR__, 2);
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

function has_empty_in($sql) {
  return preg_match('/\bIN\s*\(\s*\)/i', $sql) === 1;
}

// True when every value in the IN list is 0/negative (or is a false expression
// such as SELECT NULL), so the condition cannot match a real monitor id.
function in_matches_nothing($sql) {
  if (has_empty_in($sql)) return false;
  if (!preg_match('/\bIN\s*\(([^)]*)\)/i', $sql, $m)) return false;
  $items = array_values(array_filter(array_map('trim', explode(',', $m[1])), 'strlen'));
  if (!$items) return false;
  foreach ($items as $item) {
    if (!preg_match('/^-?\d+$/', $item)) return true; // e.g. a false subquery
    if ((int)$item > 0) return false;
  }
  return true;
}

// Returns the { ... } body of the first occurrence of $needle in $path.
function guarded_block($path, $needle) {
  $src = file_get_contents($path);
  if ($src === false) return null;
  $pos = strpos($src, $needle);
  if ($pos === false) return null;
  $open = strpos($src, '{', $pos + strlen($needle));
  if ($open === false) return null;
  $depth = 0;
  for ($i = $open, $len = strlen($src); $i < $len; $i++) {
    if ($src[$i] === '{') $depth++;
    else if ($src[$i] === '}') {
      $depth--;
      if ($depth === 0) return substr($src, $open, $i - $open + 1);
    }
  }
  return null;
}

function run_block($block, $user, array $vars = array()) {
  $fn = function($block, $user, $vars) {
    extract($vars);
    eval($block);
    return get_defined_vars();
  };
  return $fn($block, $user, $vars);
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

$GLOBALS['zone_calls'] = array();
function dbFetchAll($sql, $key = null, $params = array()) {
  $GLOBALS['zone_calls'][] = array($sql, $params);
  return array(1 => 1, 2 => 2);
}

// --- monitorLimitSql() from the real functions.php --------------------------
// functions.php pulls in these three libraries at include time; empty stubs are
// enough because monitorLimitSql() only touches them inside other functions.
$stubdir = sys_get_temp_dir().'/zm_viewable_empty_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('Filter.php', 'FilterTerm.php', 'Network.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.get_include_path());
require_once $root.'/web/includes/functions.php';

$user = new StubUser(array(), array(3));
$sql = monitorLimitSql();
check('monitorLimitSql has no empty IN ()', !has_empty_in($sql), $sql);
check('monitorLimitSql matches nothing', in_matches_nothing($sql), $sql);

$user = new StubUser(array(1, 2), array(3));
check('monitorLimitSql unchanged with viewable monitors',
  monitorLimitSql() === ' AND MonitorId IN (1,2)', monitorLimitSql());

// --- the three views -------------------------------------------------------
$views = array(
  'timeline.php' => array(
    'needle' => 'if ( count($user->unviewableMonitorIds()) )',
    'vars' => array('rangeSql' => 'R', 'eventsSql' => 'E', 'eventIdsSql' => 'I'),
    'keys' => array('rangeSql', 'eventsSql', 'eventIdsSql'),
    'old' => '/IN\s*\(\s*[\'"]\s*\.\s*implode\(\s*[\'"],\s*[\'"],\s*\$user->viewableMonitorIds\(\)/',
  ),
  'montagereview.php' => array(
    'needle' => 'if (count($user->unviewableMonitorIds()))',
    'vars' => array('eventsSql' => 'E', 'selected_monitor_ids' => array()),
    'keys' => array('eventsSql'),
    'old' => '/IN\s*\(\s*[\'"]\s*\.\s*implode\(\s*[\'"],\s*[\'"],\s*\$user->viewableMonitorIds\(\)/',
  ),
  'zones.php' => array(
    'needle' => '$user->unviewableMonitorIds()',
    'vars' => array('mids' => null),
    'keys' => array(),
    'old' => null,
  ),
);

foreach ($views as $name => $spec) {
  $path = $root.'/web/skins/classic/views/'.$name;
  $src = file_get_contents($path);
  $block = guarded_block($path, $spec['needle']);
  check("$name SQL block found", $block !== null);

  if ($name === 'zones.php') {
    // Empty viewable list: no query (or at least no "IN ()") and no monitors.
    $GLOBALS['zone_calls'] = array();
    $vars = run_block($block, new StubUser(array(), array(3)), $spec['vars']);
    $badSql = '';
    foreach ($GLOBALS['zone_calls'] as $call) {
      if (has_empty_in($call[0])) $badSql = $call[0];
    }
    check('zones.php builds no empty IN ()', $badSql === '', $badSql);
    check('zones.php has no monitors for an empty viewable list',
      isset($vars['mids']) && $vars['mids'] === array(),
      var_export(isset($vars['mids']) ? $vars['mids'] : null, true));

    // Non-empty list keeps the existing placeholders and params.
    $GLOBALS['zone_calls'] = array();
    $vars = run_block($block, new StubUser(array(1, 2), array(3)), $spec['vars']);
    $want = 'SELECT Id FROM Monitors WHERE Deleted=false AND Id IN (?,?)';
    check('zones.php query unchanged with viewable monitors',
      count($GLOBALS['zone_calls']) === 1
      && $GLOBALS['zone_calls'][0][0] === $want
      && $GLOBALS['zone_calls'][0][1] === array(1, 2),
      json_encode($GLOBALS['zone_calls']));
  } else {
    // Empty viewable list: the appended filter must still match nothing.
    $emptyVars = run_block($block, new StubUser(array(), array(3)), $spec['vars']);
    foreach ($spec['keys'] as $key) {
      $got = isset($emptyVars[$key]) ? $emptyVars[$key] : null;
      check("$name $key has no empty IN ()", is_string($got) && !has_empty_in($got), (string)$got);
      check("$name $key matches nothing", is_string($got) && in_matches_nothing($got), (string)$got);
    }

    // Non-empty list: the SQL is unchanged from the original.
    $fullVars = run_block($block, new StubUser(array(1, 2), array(3)), $spec['vars']);
    if ($name === 'timeline.php') {
      check('timeline.php filter unchanged with viewable monitors',
        $fullVars['monFilterSql'] === ' AND E.MonitorId IN (1,2)', $fullVars['monFilterSql']);
    } else {
      $got = $fullVars['eventsSql'];
      check('montagereview.php filter unchanged with viewable monitors',
        substr($got, -strlen(' AND E.MonitorId IN (1,2)')) === ' AND E.MonitorId IN (1,2)', $got);
    }
  }

  // Textual guard: the old unguarded "IN ('.implode(',', ids)" is gone.
  if ($spec['old'] !== null) {
    check("$name has no unguarded viewableMonitorIds implode",
      preg_match($spec['old'], $src) === 0);
  }
}

foreach (glob($stubdir.'/*') as $f) @unlink($f);
@rmdir($stubdir);

echo "\n".($failures ? "$failures FAILED" : 'all passed')."\n";
exit($failures ? 1 : 0);
