<?php
// parseSort() rewrites the legacy sort fields StartTime, EndTime and DateTime in
// $_REQUEST to StartDateTime/EndDateTime. sortHeader() and sortTag() compared the
// rewritten request value with the field the view passes in (export.php passes
// 'StartTime'), so the Time column never showed the sort marker and its header link
// never toggled the direction.
//
// Run as: php tests/php/test_functions_sort_header_legacy.php

$failures = 0;
function check($name, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
foreach (array('currentSortField', 'sortHeader', 'sortTag', 'validInt', 'validCardinal', 'validHtmlStr') as $name) {
  if (!preg_match('/^function '.$name.'\(.*?^}\n/ms', $source, $m)) {
    echo "FAIL - could not find $name in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}
$view = 'export';

// What parseSort() leaves behind for ?sort_field=StartTime&sort_asc=1
$_REQUEST = array('sort_field'=>'StartDateTime', 'sort_asc'=>'1');
check('tag for legacy StartTime shows ascending', sortTag('StartTime'), '(^)');
check('tag for StartDateTime', sortTag('StartDateTime'), '(^)');
check('tag for other column', sortTag('Name'), false);
check('header toggles to descending', strpos(sortHeader('StartTime'), 'sort_asc=&amp;') !== false, true);
check('header of other column starts descending', strpos(sortHeader('Name'), 'sort_asc=0&amp;') !== false, true);

$_REQUEST = array('sort_field'=>'EndDateTime', 'sort_asc'=>'0');
check('tag for legacy EndTime', sortTag('EndTime'), '(v)');
check('header toggles to ascending', strpos(sortHeader('EndTime'), 'sort_asc=1&amp;') !== false, true);
check('DateTime is StartDateTime', sortTag('DateTime'), false);
$_REQUEST = array('sort_field'=>'StartDateTime', 'sort_asc'=>'0');
check('DateTime is StartDateTime (match)', sortTag('DateTime'), '(v)');

exit($failures ? 1 : 0);
