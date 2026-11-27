<?php
/*
 * The PTZ preset modal escaped each preset label and then handed it to
 * buildSelect(), which escapes option text itself, so a label such as
 * "Gate & Door" showed up in the dropdown as "Gate &amp; Door". The modal now
 * leaves the escaping to buildSelect().
 *
 * buildSelect() and validHtmlStr() are taken from web/includes/functions.php
 * so the test exercises the real escaping, not a copy of it.
 *
 * Run: php tests/php/test_controlpreset_modal_label_escape.php [root]
 */
namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
  $modalFile = $root.'/web/ajax/modals/controlpreset.php';

  $functions = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  foreach (array('validHtmlStr', 'buildSelect') as $name) {
    if (!preg_match('/^function '.$name.'\(.*?^}/ms', $functions, $m)) {
      echo "FAIL - cannot find $name in functions.php\n";
      exit(1);
    }
    eval($m[0]);
  }

  function canEdit($a, $mid = null) { return true; }
  function canView($a, $mid = null) { return true; }
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }
  function translate($s) { return $s; }
  function getCSRFinputHTML() { return ''; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return array('Id' => $params[0], 'NumPresets' => 2);
  }
  function dbFetchAll($sql, $col = null, $params = array()) {
    return array(
      array('Preset' => 1, 'Label' => 'Gate & <Door> "A"'),
      array('Preset' => 2, 'Label' => 'Garage'),
    );
  }

  $failures = 0;
  function check($label, $cond) {
    global $failures;
    if ($cond) {
      echo "ok - $label\n";
    } else {
      $failures++;
      echo "FAIL - $label\n";
    }
  }

  $_REQUEST = array('mid' => '5');
  ob_start();
  include $modalFile;
  $html = ob_get_clean();

  check('label is escaped once in the option text',
    strpos($html, '>Preset 1 (Gate &amp; &lt;Door&gt; &quot;A&quot;)</option>') !== false);
  check('label is not escaped twice', strpos($html, '&amp;amp;') === false and strpos($html, '&amp;lt;') === false);
  check('plain label is unchanged', strpos($html, '>Preset 2 (Garage)</option>') !== false);

  echo "\n".($failures ? "$failures failure(s)\n" : "all passed\n");
  exit($failures ? 1 : 0);
}
