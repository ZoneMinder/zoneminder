<?php
// Plain PHP CLI test, no DB. Verifies Monitor::Path() strips credentials by
// position so that URL-decoded credentials do not have to match the raw path.

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

$functions = file_get_contents($root.'/web/includes/functions.php');
if ($functions === false) {
  fwrite(STDERR, "cannot read functions.php\n");
  exit(1);
}
if (!preg_match('/^function extract_auth_values_from_url\(.*?^}$/ms', $functions, $m)) {
  fwrite(STDERR, "cannot extract extract_auth_values_from_url\n");
  exit(1);
}
eval($m[0]);

$monitor = file_get_contents($root.'/web/includes/Monitor.php');
if ($monitor === false) {
  fwrite(STDERR, "cannot read Monitor.php\n");
  exit(1);
}
if (!preg_match('/^  public function Path\(\$new=null\) \{.*?^  \}$/ms', $monitor, $m)) {
  fwrite(STDERR, "cannot extract Monitor::Path()\n");
  exit(1);
}
eval('#[AllowDynamicProperties] class FakeMon { '.$m[0].' }');

$failures = 0;

function check($label, $got, $want) {
  global $failures;
  if ($got === $want) {
    echo "ok   $label\n";
  } else {
    $failures++;
    echo "FAIL $label: got ".var_export($got, true).", want ".var_export($want, true)."\n";
  }
}

// 1) percent-encoded password: decoded $ps ('p@ss') does not appear in the path
$mon = new FakeMon();
$mon->{'Path'} = 'rtsp://admin:p%40ss@host/x';
$path = $mon->Path();
check('encoded password path', $path, 'rtsp://host/x');
check('encoded password Pass', $mon->{'Pass'}, 'p@ss');
check('encoded password User', $mon->{'User'}, 'admin');

// 2) plain credentials
$mon = new FakeMon();
$mon->{'Path'} = 'rtsp://admin:secret@host/x';
check('plain credentials path', $mon->Path(), 'rtsp://host/x');

// 3) no credentials, host:port unchanged
$mon = new FakeMon();
$mon->{'Path'} = 'rtsp://host:554/x';
check('host:port unchanged', $mon->Path(), 'rtsp://host:554/x');

// 4) both credentials and port, encoded user and password
$mon = new FakeMon();
$mon->{'Path'} = 'rtsp://us%3Aer:pa%2Fss@h:554/live';
check('encoded user+pass with port', $mon->Path(), 'rtsp://h:554/live');

if ($failures) {
  echo "$failures failure(s)\n";
  exit(1);
}
echo "all passed\n";
exit(0);
