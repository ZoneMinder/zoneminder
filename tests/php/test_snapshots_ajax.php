<?php
/*
 * ajax/snapshots.php: the list divided by the event Width, which is 0 for an
 * event that has not recorded a frame yet, and PHP 8 throws on that, so one
 * such snapshot broke the whole list. A scalar ids value also went straight
 * into foreach.
 *
 * Run: php tests/php/test_snapshots_ajax.php
 */
$src = file_get_contents(dirname(__DIR__, 2).'/web/ajax/snapshots.php');
$tests = 0; $failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}
check('source readable', $src !== false);
check('the list does not divide by the event width', !preg_match('#/\s*\$event->Width\(\)#', $src));
check('ids is always an array', (bool)preg_match('/\$ids\s*=\s*\(array\)\s*\$_REQUEST\[\'ids\'\]/', $src));
// A snapshot added through the API has no CreatedOn (the column is nullable);
// strtotime(NULL) is false and the formatter turned that into 1970.
check('a snapshot without CreatedOn is not formatted as 1970', (bool)preg_match('/\$row\[\'CreatedOn\'\]\s*=\s*\$row\[\'CreatedOn\'\]\s*\?[^;]*strtotime\(\$row\[\'CreatedOn\'\]\)[^;]*:\s*\'\'\s*;/', $src));
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
