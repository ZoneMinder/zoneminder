<?php
// The watch view must render the #events table for every monitor type, hiding
// it for a WebSite monitor, so watch.js can show it after moving to a camera.
//
// Run: php tests/php/test_watch_events_markup.php [repo root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/watch.php');
$fail = 0;
function check($ok, $msg) {
  global $fail;
  echo ($ok ? 'ok     ' : 'FAILED ').$msg."\n";
  if (!$ok) $fail++;
}

$start = strpos($src, '<!-- START table Events -->');
$end = strpos($src, '<!-- END table Events -->');
check($start !== false && $end > $start, 'events block found');
$block = substr($src, $start, $end - $start);

check(strpos($block, "canView('Events')") !== false, 'block is gated on the Events permission');
check(!preg_match("/Type\(\)\s*!=\s*'WebSite'\s*\)\s*\)\s*\{/", $block),
    'block is not skipped for a WebSite monitor');
check(preg_match("/id=\"events\".*?'WebSite'.*?display:none/s", $block) === 1,
    'events div is hidden for a WebSite monitor');
exit($fail ? 1 : 0);
