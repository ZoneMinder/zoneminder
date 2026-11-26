<?php
// view=blank is a bare page. csrf-magic injects the csrf-magic.js script tag
// before </head> and a CsrfMagic.end() call before </body>; with no <head> the
// call ran without the library and threw "CsrfMagic is not defined".
// Run: php tests/php/test_blank_view_head.php   (from the repo root)
$cspNonce = 'n';
foreach (array(array(), array('refreshParent' => 1)) as $vars) {
  extract($vars);
  ob_start();
  include __DIR__.'/../../web/skins/classic/views/blank.php';
  $out = ob_get_clean();
  unset($refreshParent);
  $label = $vars ? 'blank with refreshParent' : 'blank';
  if (stripos($out, '</head>') === false) {
    echo "[FAIL] $label has no </head> for csrf-magic to inject into\n";
    exit(1);
  }
  echo "[PASS] $label has a head\n";
}
