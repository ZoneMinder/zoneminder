<?php
$root = __DIR__.'/../..';
$modal = file_get_contents($root.'/web/ajax/modals/settings.php');

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

preg_match('/<input type="hidden" name="view" value="([^"]*)"/', $modal, $m);
check('the form names the view that has the settings action', $m[1] ?? null, 'settings');
check('the action file for that view exists', file_exists($root.'/web/includes/actions/settings.php'), true);

echo "$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
