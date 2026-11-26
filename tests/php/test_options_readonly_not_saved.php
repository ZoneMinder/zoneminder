<?php
// Config rows with Readonly set (ZM_DYN_DB_VERSION and friends, written by
// zmupdate/the daemons) were rendered as editable inputs on the Options page and
// the save handler wrote whatever was posted back, so an admin could retype the
// DB version and break upgrade detection.
//
// The real web/includes/actions/options.php is included with the database layer
// stubbed, because the only thing that matters here is which UPDATEs it issues.
//
// Run as: php tests/php/test_options_readonly_not_saved.php [repo-root-or-web-root]
// Default root is this worktree (tests/php/../..).

namespace ZM {
  $GLOBALS['logged'] = array();
  function Debug($m) { $GLOBALS['logged'][] = $m; }
  function Warning($m) { $GLOBALS['logged'][] = $m; }
  function Error($m) { $GLOBALS['logged'][] = $m; }
  function AuditAction($a, $b, $c, $d) {}
}

namespace {
  $failures = 0;
  $tests = 0;
  function check($label, $cond, $detail = '') {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label".($detail === '' ? '' : " ($detail)")."\n";
    }
  }

  // argv[1] may be the repo root (/path/to/zm, web/ inside it) or web/ itself.
  $given = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__, 2);
  $web = $given;
  if (!is_file($web.'/includes/actions/options.php')) $web .= '/web';
  foreach (array('/includes/actions/options.php', '/skins/classic/views/options.php') as $needed) {
    if (!is_file($web.$needed)) {
      fwrite(STDERR, "Cannot find $web$needed\n");
      exit(2);
    }
  }

  // --- stubbed database layer ----------------------------------------------
  $GLOBALS['queries'] = array();
  $GLOBALS['pending'] = array();
  $GLOBALS['config'] = array(
    array('Name' => 'ZM_DYN_DB_VERSION', 'Value' => '1.39.0', 'Type' => 'string',
          'DefaultValue' => '', 'Requires' => '', 'System' => 0, 'Readonly' => 1,
          'Category' => 'version'),
    array('Name' => 'ZM_SOMETHING', 'Value' => 'a', 'Type' => 'string',
          'DefaultValue' => '', 'Requires' => '', 'System' => 0, 'Readonly' => 0,
          'Category' => 'version'),
  );

  // A real dbFetchNext() only returns the columns the query selected, so model
  // that: the unfixed action's SELECT has no Readonly and its rows have none.
  function selected_columns($sql) {
    $from = stripos($sql, ' FROM ');
    if ($from === false) return null;
    $list = substr($sql, 6, $from - 6);
    if (!preg_match_all('/`?([A-Za-z_][A-Za-z0-9_]*)`?/', $list, $m)) return array();
    return array_map('strtolower', $m[1]);
  }

  function dbQuery($sql, $params = array()) {
    $GLOBALS['queries'][] = array('sql' => $sql, 'params' => $params);
    if (preg_match('/^\s*SELECT/i', $sql)) {
      $cols = selected_columns($sql);
      $rows = array();
      foreach ($GLOBALS['config'] as $row) {
        if ($cols === null) { $rows[] = $row; continue; }
        $keep = array();
        foreach ($row as $k => $v) if (in_array(strtolower($k), $cols, true)) $keep[$k] = $v;
        $rows[] = $keep;
      }
      $GLOBALS['pending'] = $rows;
      return 'result';
    }
    return 1;
  }
  function dbFetchNext($result) { return array_shift($GLOBALS['pending']); }
  function dbFetchAll($sql) {
    // A separate result handle in the real code: record the query but do not
    // disturb the rows the main SELECT has queued for dbFetchNext().
    $GLOBALS['queries'][] = array('sql' => $sql, 'params' => array());
    $cols = selected_columns($sql);
    $rows = array();
    foreach ($GLOBALS['config'] as $row) {
      if ($cols === null) { $rows[] = $row; continue; }
      $keep = array();
      foreach ($row as $k => $v) if (in_array(strtolower($k), $cols, true)) $keep[$k] = $v;
      $rows[] = $keep;
    }
    return $rows;
  }
  function dbLastError() { return ''; }

  // --- stubbed web helpers -------------------------------------------------
  function canView($area) { return true; }
  function canEdit($area) { return true; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
  function loadConfigFiles() { return array(); }
  function configFileValue($value, $type) { return $value; }
  function loadConfig($defineConsts = true) {}

  // includes/config_values.php is required by the action; stub it out.
  $stubDir = '/tmp/hms-tests/zmstub';
  if (!is_dir($stubDir.'/includes')) mkdir($stubDir.'/includes', 0777, true);
  file_put_contents($stubDir.'/includes/config_values.php',
    "<?php\nfunction configValueIsValid(\$v, \$t, \$d) { return true; }\nfunction configRequirementsMet(\$r, \$e) { return true; }\n");
  set_include_path($stubDir.PATH_SEPARATOR.get_include_path());

  // --- drive the save handler ---------------------------------------------
  $action = 'options';
  $error_message = '';
  $_REQUEST = $_POST = array(
    'view' => 'options',
    'action' => 'options',
    'tab' => 'version',
    'newConfig' => array(
      'ZM_DYN_DB_VERSION' => '9.9.9',
      'ZM_SOMETHING' => 'b',
    ),
  );

  include($web.'/includes/actions/options.php');

  $updates = array();
  $selects = array();
  foreach ($GLOBALS['queries'] as $q) {
    if (preg_match('/^\s*UPDATE\s+Config/i', $q['sql'])) $updates[] = $q;
    if (preg_match('/^\s*SELECT.*FROM\s+Config.*WHERE\s+Category/i', $q['sql'])) $selects[] = $q['sql'];
  }

  $readonlyUpdate = false;
  $normalUpdate = false;
  foreach ($updates as $q) {
    $name = isset($q['params'][1]) ? $q['params'][1] : '';
    if (strpos($q['sql'], 'ZM_DYN_DB_VERSION') !== false || $name === 'ZM_DYN_DB_VERSION') $readonlyUpdate = true;
    if (strpos($q['sql'], 'ZM_SOMETHING') !== false || $name === 'ZM_SOMETHING') $normalUpdate = true;
  }

  check('a Readonly row in the version tab is not written', !$readonlyUpdate,
    'UPDATEs: '.json_encode(array_column($updates, 'params')));
  check('a normal row in the same save is still written', $normalUpdate,
    'UPDATEs: '.json_encode(array_column($updates, 'params')));

  $selectHasReadonly = false;
  foreach ($selects as $sql) {
    if (preg_match('/`?Readonly`?/i', $sql)) $selectHasReadonly = true;
  }
  check('the save query selects the Readonly column', $selectHasReadonly,
    'SELECTs: '.json_encode($selects));

  // The view must refuse to render a Readonly row as an editable input.
  $view = file_get_contents($web.'/skins/classic/views/options.php');
  check("the view gates \$optionCanEdit on empty(\$value['Readonly'])",
    (bool)preg_match('/\$optionCanEdit\s*=[^;]*empty\(\s*\$value\[\s*[\'"]Readonly[\'"]\s*\]\s*\)/s', $view));

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
