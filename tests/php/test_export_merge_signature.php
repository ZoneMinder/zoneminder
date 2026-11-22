<?php
// videoStreamSignature() tells downloadEvents() which event videos can be
// joined with ffmpeg -c copy: only files with the same codec and size.
// Empty and unreadable files must give '' so they are left out of the export.
//
// Run: php tests/php/test_export_merge_signature.php

define('ZM_PATH_FFMPEG', trim((string)shell_exec('command -v ffmpeg')));
require_once __DIR__.'/../../web/includes/download_functions.php';

if (ZM_PATH_FFMPEG === '') {
  echo "skip - ffmpeg not installed\n";
  exit(0);
}

$tests = 0;
$failures = 0;
function check($label, $got, $expected) {
  global $tests, $failures;
  $tests++;
  if ($got === $expected) {
    echo "  ok  - $label\n";
  } else {
    $failures++;
    echo "  NOT OK - $label (got ".var_export($got, true).", expected ".var_export($expected, true).")\n";
  }
}

$dir = sys_get_temp_dir().'/zm_sig_'.getmypid();
mkdir($dir);
function make($dir, $name, $size, $codec) {
  exec(ZM_PATH_FFMPEG.' -v error -y -f lavfi -i testsrc=size='.$size.':rate=5:duration=1 -c:v '.$codec.' '.escapeshellarg("$dir/$name").' 2>&1');
}
make($dir, 'a.mp4', '320x240', 'mpeg4');
make($dir, 'b.mp4', '160x120', 'mpeg4');
make($dir, 'c.mp4', '320x240', 'mpeg4');
touch("$dir/empty.mp4");
file_put_contents("$dir/junk.mp4", 'not a video');

$a = videoStreamSignature("$dir/a.mp4");
check('signature has codec and size', $a, 'mpeg4 320x240');
check('same stream gives the same signature', videoStreamSignature("$dir/c.mp4"), $a);
check('different size gives a different signature', videoStreamSignature("$dir/b.mp4"), 'mpeg4 160x120');
check('0-byte file gives an empty signature', videoStreamSignature("$dir/empty.mp4"), '');
check('unreadable file gives an empty signature', videoStreamSignature("$dir/junk.mp4"), '');
check('missing file gives an empty signature', videoStreamSignature("$dir/none.mp4"), '');

array_map('unlink', glob("$dir/*"));
rmdir($dir);
echo "\n$tests tests, $failures failures\n";
exit($failures ? 1 : 0);
