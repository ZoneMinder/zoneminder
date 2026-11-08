<?php
// GHSA-xqfr-6v9x-cvwc follow-up (defense in depth): every
// FilterComponent::buildFilter() call in the REST API must pass a field
// allow-list as its second argument, so each field name is checked against a
// known column set instead of defaulting to null (no check). The numeric-field
// guard in buildFilter() already closes the injection; this keeps the twelve
// previously-unguarded index()/search()/consoleEvents() call sites filtering
// only on real columns, and turns an unknown field into a 400 rather than a
// MySQL 500.
//
// MonitorsController is the one exception: it runs qualifyFields() (which
// validates every field against the schema) before buildFilter().
//
// Pure source scan, so it runs from a checkout on master and release-1.38:
//   php tests/php/test_api_filter_fields_enforced.php

$dir = __DIR__.'/../../web/api/app/Controller';
$exempt = array('MonitorsController.php'); // guarded via qualifyFields()

$failures = 0; $passes = 0; $checked = 0;

foreach (glob($dir.'/*Controller.php') as $path) {
  $name = basename($path);
  $lines = explode("\n", file_get_contents($path));
  foreach ($lines as $i => $line) {
    $pos = strpos($line, '->buildFilter(');
    if ($pos === false) continue;
    $checked++;
    // Our call sites each pass a scalar/array-access first argument with no
    // comma of its own, so a comma after buildFilter( means a second argument.
    $call = substr($line, $pos + strlen('->buildFilter('));
    $hasAllowList = (strpos($call, ',') !== false);
    $label = $name.':'.($i + 1);
    if ($hasAllowList) {
      $passes++; echo "ok - $label passes a field allow-list\n";
    } else if (in_array($name, $exempt)) {
      $passes++; echo "ok - $label is qualifyFields-guarded (exempt)\n";
    } else {
      $failures++; echo "not ok - $label has no field allow-list -> ".trim($line)."\n";
    }
  }
}

echo "\n$checked buildFilter call(s): $passes ok, $failures failed\n";
exit($failures ? 1 : 0);
?>
