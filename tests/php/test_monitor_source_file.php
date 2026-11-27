<?php
// Monitor::Source() fed the console Source column and the Source filter. A File
// or cURL monitor fell through every branch and showed "Monitor <id>" instead
// of the file it reads.
//
// Monitor.php can't be included without the web bootstrap and a database, so
// the method is taken from the source and run in a stand-in.
//
// Run as: php tests/php/test_monitor_source_file.php

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$source = file_get_contents(__DIR__.'/../../web/includes/Monitor.php');
if (!preg_match('/^  public function Source\(.*?^  }[^\n]*\n/ms', $source, $m)) {
  echo "FAIL - could not find Monitor::Source\n";
  exit(1);
}

define('ZM_WEB_FILTER_SOURCE', 'NoCredentials');

// The real one lives in functions.php; only the scheme, host and path matter here.
function unparse_url($p) {
  return $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '').($p['path'] ?? '');
}

eval('class SourceHarness { '
  .'public $Id = 7; public $Type = ""; public $Path = null; public $Device = ""; public $Channel = 0; public $Host = ""; public $Port = ""; '
  .$m[0].' }');

function sourceOf($type, $path) {
  $h = new SourceHarness();
  $h->Type = $type;
  $h->Path = $path;
  return $h->Source();
}

check('a File monitor shows the file name', sourceOf('File', '/var/lib/zm/frames/cam1.jpg'), 'cam1.jpg');
check('a File monitor with a bare name shows it', sourceOf('File', 'cam1.jpg'), 'cam1.jpg');
check('a cURL monitor shows the last path segment', sourceOf('cURL', 'http://host/snap/image.jpg'), 'image.jpg');
check('a File monitor with no path falls back to the id', sourceOf('File', null), 'Monitor 7');
check('a File monitor with an empty path falls back to the id', sourceOf('File', ''), 'Monitor 7');
check('an Ffmpeg monitor is unchanged', sourceOf('Ffmpeg', 'rtsp://user:pw@cam.local:554/stream?x=1'), 'rtsp://cam.local/stream');

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
