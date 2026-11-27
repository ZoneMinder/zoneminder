<?php
// A storage Name or Path with non-ASCII letters must not be stripped by the
// input filter, while punctuation outside the allowed set still is.
//
// Run as: php tests/php/test_storage_unicode_name.php

namespace ZM;

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

$stubdir = sys_get_temp_dir().'/zm_storage_unicode_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Event.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

define('ZM_DIR_EVENTS', '/var/cache/zoneminder/events');

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'Storage.php';

$storage = new Storage();
$changes = $storage->changes(array('Name' => "\u{0425}\u{0440}\u{0430}\u{043D}\u{0438}\u{043B}\u{0438}\u{0449}\u{0435}", 'Path' => '/mnt/josé/cam/'));
check('cyrillic name kept', $changes['Name'], "\u{0425}\u{0440}\u{0430}\u{043D}\u{0438}\u{043B}\u{0438}\u{0449}\u{0435}");
check('accented path kept, trailing slash removed', $changes['Path'], '/mnt/josé/cam');

$changes = $storage->changes(array('Name' => 'Cam #1', 'Path' => '/mnt/a+b'));
check('ascii punctuation still stripped from name', $changes['Name'], 'Cam 1');
check('ascii punctuation still stripped from path', $changes['Path'], '/mnt/ab');

array_map('unlink', glob($stubdir.'/*'));
rmdir($stubdir);
exit($failures ? 1 : 0);
