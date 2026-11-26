<?php
// The Source tab Method select only offers some methods. A saved Method outside
// that list (jpegTags, regexp without PCRE, an unknown Ffmpeg value) made the
// select show its first option, so the next save changed the Method. The view
// now adds the saved value to the list. Usage:
// php tests/php/test_monitor_method_saved_kept.php [root]

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
$view = file_get_contents($root.'/web/skins/classic/views/monitor.php');
$fns = file_get_contents($root.'/web/includes/functions.php');
foreach (array('htmlOptions' => $fns) as $fn => $src) {
  preg_match('/^function '.$fn.'\(.*?^}[^\n]*\n/ms', $src, $m);
  eval($m[0]);
}
eval('namespace ZM; function Warning($m) {}');
function htmlAttributes($a, $e = []) { return ''; }
function htmlSelect($name, $contents, $values) {
  return '<select name="'.$name.'">'.htmlOptions($contents, $values).'</select>';
}

$failures = 0;
function check($name, $ok) {
  global $failures;
  if (!$ok) $failures++;
  echo ($ok ? 'ok' : 'FAIL')." - $name\n";
}

// Build each select the way the view does, by evaluating the view's own
// Method select expressions with the view's method lists.
$httpMethods = array('simple' => 'Simple');
$rtspMethods = array('rtpUni' => 'RTP/Unicast', 'rtpRtsp' => 'RTP/RTSP');
$rtspFFMpegMethods = array('rtpRtsp' => 'TCP', 'rtpUni' => 'UDP');
if (preg_match('/^function methodsWithSaved\(.*?^}\n/ms', $view, $m))
  eval($m[0]);
$calls = array(array());
foreach (explode("\n", $view) as $line) {
  if (preg_match("/(htmlSelect\('newMonitor\[Method\]', .*Methods\b.*\))\s*(;|\?>)/", $line, $m)
      && !strpos($line, '$localMethods'))
    $calls[0][] = $m[1];
}
check('view has three Method selects (Remote http, Remote rtsp, Ffmpeg)', count($calls[0]) === 3);

class FakeMonitor { public $m; function Method() { return $this->m; } }
$monitor = new FakeMonitor();
$cases = array(
  array(0, 'jpegTags'),
  array(0, 'regexp'),
  array(1, 'someOldValue'),
  array(2, 'simple'),
);
foreach ($cases as $c) {
  $monitor->m = $c[1];
  $html = eval('return '.$calls[0][$c[0]].';');
  check($c[1].' stays selected in select '.$c[0],
    strpos($html, '<option value="'.$c[1].'" selected="selected"') !== false);
}
$monitor->m = 'simple';
$html = eval('return '.$calls[0][0].';');
check('offered method selected, no extra option', substr_count($html, '<option') === 1);
exit($failures ? 1 : 0);
