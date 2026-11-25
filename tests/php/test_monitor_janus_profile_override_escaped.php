<?php
// The Janus Profile-ID Override input on the monitor edit page printed the
// stored value straight into value="...". A value holding a double quote cut
// the attribute short, so the field showed a truncated value and the next save
// stored it. It must be escaped like the other fields, and the field is a
// VARCHAR(30).
//
// Run as: php tests/php/test_monitor_janus_profile_override_escaped.php <repo root>

$root = $argv[1] ?? __DIR__.'/../..';
$source = file_get_contents($root.'/web/skins/classic/views/monitor.php');

function validHtmlStr($input) {
  if (is_null($input) or is_array($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}

if (!preg_match('/<input type="text" name="newMonitor\[Janus_Profile_Override\]"[^\n]*\/>/', $source, $m)) {
  echo "FAIL - could not find the Janus_Profile_Override input\n";
  exit(1);
}
$monitor = new class {
  public function Janus_Profile_Override() { return 'a" onfocus="x'; }
};
ob_start();
eval('?>'.$m[0]);
$html = ob_get_clean();

$doc = new DOMDocument();
@$doc->loadHTML($html);
$input = $doc->getElementsByTagName('input')->item(0);
$failures = 0;
if ($input->getAttribute('value') !== 'a" onfocus="x') {
  echo "FAIL - value is ".var_export($input->getAttribute('value'), true)."\n";
  $failures++;
} else {
  echo "ok - value round-trips\n";
}
if ($input->hasAttribute('onfocus')) {
  echo "FAIL - stored value injected an onfocus attribute\n";
  $failures++;
} else {
  echo "ok - no injected attribute\n";
}
if ($input->getAttribute('maxlength') !== '30') {
  echo "FAIL - no maxlength=30\n";
  $failures++;
} else {
  echo "ok - maxlength 30\n";
}
exit($failures ? 1 : 0);
