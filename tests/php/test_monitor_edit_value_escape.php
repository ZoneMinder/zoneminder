<?php
// The monitor edit page prints stored free-text monitor fields into the
// value="..." attribute of text inputs. If the printed value is not escaped, a
// stored value containing a double quote closes the attribute early and the
// rest of the value becomes markup.
//
// monitor.php cannot be rendered here: it needs the web bootstrap, a database
// and an authorised user. So this is a source scan. For each field it finds the
// line carrying name="newMonitor[FIELD]" and the value="<?php echo ... " part,
// part, and checks that the echoed expression is passed through validHtmlStr()
// or htmlspecialchars(). The "at least one line found" check makes a rename of
// the input visible instead of silently scanning nothing.
//
// Run as: php tests/php/test_monitor_edit_value_escape.php [repo-root]

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');
$viewPath = $repoRoot.'/web/skins/classic/views/monitor.php';

$tests = 0;
$failures = 0;

function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label\n";
  }
}

$fields = array(
  'LinkedMonitors',
  'MQTT_Subscriptions',
  'Janus_RTSP_Session_Timeout',
);

$source = is_readable($viewPath) ? file_get_contents($viewPath) : false;

foreach ($fields as $field) {
  if ($source === false) {
    check("$field: monitor.php is readable", false);
    continue;
  }

  $name = 'name="newMonitor['.$field.']"';
  $lines = preg_split('/\r\n|\n|\r/', $source);
  $matches = array();

  foreach ($lines as $offset => $line) {
    if (strpos($line, $name) === false) {
      continue;
    }
    if (!preg_match_all('/value="<\?php echo (.*?)\s*\?>/', $line, $values)) {
      continue;
    }
    foreach ($values[1] as $expr) {
      $matches[] = array('line' => $offset + 1, 'expr' => trim($expr));
    }
  }

  check("$field: a value=\"...\" input is found to scan", count($matches) > 0);

  foreach ($matches as $match) {
    $expr = $match['expr'];
    $escaped = strpos($expr, 'validHtmlStr(') === 0
      || strpos($expr, 'htmlspecialchars(') === 0;
    check("$field: the value at monitor.php:{$match['line']} is escaped", $escaped);
  }
}

echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
