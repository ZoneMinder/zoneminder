<?php

function check($name, $got, $want) {
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    echo "FAIL - $name (got " . var_export($got, true) . ", want " . var_export($want, true) . ")\n";
    $GLOBALS['failures']++;
  }
}

$GLOBALS['failures'] = 0;

function run($request, $canEditMap) {
  global $view, $perm;
  $view = 'monitor';
  $perm = $canEditMap;
  $_REQUEST = $request;

  if (!function_exists('canEdit')) {
    function canEdit($p, $id=0) {
      global $perm;
      return $id == 0 ? true : !empty($perm[$id]);
    }
  }
  if (!function_exists('validCardinal')) {
    function validCardinal($v) {
      return (int)$v;
    }
  }

  $root = isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : __DIR__.'/../..';
  $path = "$root/web/skins/classic/views/monitor.php";
  $text = file_get_contents($path);
  $pos = strpos($text, '$Server = null;');
  if ($pos === false) {
    echo "FAIL - could not find \$Server = null; in $path\n";
    $GLOBALS['failures']++;
    return;
  }
  $prefix = substr($text, 0, $pos);
  if (strpos($prefix, '<?php') === 0) {
    $prefix = substr($prefix, 5);
  }
  $lines = preg_split('/\R/', $prefix);
  $filtered = array();
  foreach ($lines as $line) {
    if (strpos(ltrim($line), 'require_once(') === 0 || strpos($line, 'require_once(') === 0) {
      continue;
    }
    $filtered[] = $line;
  }
  $prefix = implode("\n", $filtered);

  eval($prefix);
}

run(array('dupId' => '9'), array(9 => false));
check('restricted user cloning monitor 9 denied', $view, 'error');

run(array('dupId' => '9'), array(9 => true));
check('user cloning monitor 9 allowed', $view, 'monitor');

run(array(), array());
check('no dupId and no mid defaults to monitor', $view, 'monitor');

run(array('mid' => '5'), array(5 => false));
check('existing mid gate denies restricted monitor 5', $view, 'error');

exit($GLOBALS['failures'] > 0 ? 1 : 0);
