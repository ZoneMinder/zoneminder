<?php
/*
 * Detector: the Intel GPU view took the engine columns of the clients table
 * from the first client only, then printed each client's own engine-classes in
 * their own order, so a client with fewer or differently ordered engines landed
 * its numbers under the wrong heading.
 *
 * Run: php tests/php/test_intelgpu_clients.php
 */
$tests = 0;
$failures = 0;
function check($label, $cond) {
  global $tests, $failures;
  $tests++;
  if ($cond) echo "  ok  - $label\n"; else { $failures++; echo "  NOT OK - $label\n"; }
}
$src = file_get_contents(dirname(__DIR__, 2).'/web/skins/classic/views/intelgpu.php');
$a = strpos($src, '<!-- Clients/Processes');
$b = strpos($src, '<!-- Legend -->');
$frag = substr($src, $a, $b - $a);
$gpuData = ['clients' => [
  '1' => ['pid' => '10', 'name' => 'ffmpeg', 'engine-classes' => ['Render/3D' => ['busy' => '5.0'], 'Video' => ['busy' => '40.0']]],
  '2' => ['pid' => '11', 'name' => 'mpv', 'engine-classes' => ['Video' => ['busy' => '7.0']]],
]];
ob_start();
eval('?>'.$frag);
$html = ob_get_clean();
check('headings list both engines', substr_count($html, '<th>') === 4);
preg_match_all('#<tr>\s*<td>(\d+)</td>\s*<td>[^<]*</td>(.*?)</tr>#s', $html, $rows, PREG_SET_ORDER);
check('two client rows', count($rows) === 2);
preg_match_all('#<td>([\d.]+)%</td>#', $rows[1][2] ?? '', $m);
check('second client has a cell for each heading', count($m[1]) === 2);
check('second client Video value sits under Video', ($m[1][1] ?? null) === '7.0');
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
