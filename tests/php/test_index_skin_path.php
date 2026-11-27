<?php
// Tests web/index.php skin selection: skin values that are not a directory
// name directly under web/skins/ (including '..', path traversal, and
// non-strings) must be replaced by the first real skin.
// Run as: php tests/php/test_index_skin_path.php [repo-root]

namespace ZM {
  function Error($m) { $GLOBALS['errors'][] = $m; }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');
  $indexPath = $root.'/web/index.php';
  $code = file_get_contents($indexPath);
  if ($code === false) {
    fwrite(STDERR, "cannot read $indexPath\n");
    exit(1);
  }

  $start = strpos($code, "if ( defined('ZM_FORCE_SKIN_DEFAULT') )");
  $end = strpos($code, 'global $navbar_type;');
  if ($start === false or $end === false or $end <= $start) {
    fwrite(STDERR, "could not extract skin/css block from $indexPath\n");
    exit(1);
  }
  $block = substr($code, $start, $end - $start);

  chdir($root.'/web');

  $real = array_map('basename', glob('skins/*', GLOB_ONLYDIR));
  $first = $real[0];

  function run($skinValue) {
    global $block;
    $_GET = array('skin' => $skinValue);
    $_COOKIE = array();
    eval($block);
    return array($skin, $css);
  }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
      $failures++;
    }
  }

  $cases = array(
    'classic' => 'classic',
    '..' => $first,
    '../includes' => $first,
    '../../../tmp' => $first,
    'nonexistent' => $first,
  );

  foreach ($cases as $name => $want) {
    $got = run($name);
    check($name, $got[0], $want);
  }

  $got = run(array('x'));
  check('array(x)', $got[0], $first);

  exit($failures ? 1 : 0);
}
