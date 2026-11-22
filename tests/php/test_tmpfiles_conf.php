<?php
// Tests misc/zoneminder-tmpfiles.conf.in.
//
// The file tells systemd which of ZoneMinder's directories to create at boot
// and which to clean on a timer. Nothing exercises it during a build or a
// test run, and a mistake in it is quiet in both directions: a bad line is
// skipped with a log message nobody reads, and a missing age field simply
// means a directory grows forever, which is what prompted this.
//
// Run: php tests/php/test_tmpfiles_conf.php   (from the repo root)

$root = realpath(__DIR__.'/../..');
$conf_path = $root.'/misc/zoneminder-tmpfiles.conf.in';
$conf = file_get_contents($conf_path);

$passed = 0;
$failed = 0;
$skipped = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}
function skip($label, $why) {
  global $skipped;
  $skipped++;
  echo "  skip $label ($why)\n";
}

// Parse into entries. Fields are Type Path Mode User Group Age Argument, of
// which the first two are mandatory; "-" means "leave at the default".
$entries = array();
foreach (explode("\n", $conf) as $n => $line) {
  $line = trim($line);
  if ($line === '' or $line[0] === '#') continue;
  $fields = preg_split('/\s+/', $line);
  $entries[] = array(
    'line' => $n + 1,
    'type' => $fields[0],
    'path' => isset($fields[1]) ? $fields[1] : null,
    'mode' => isset($fields[2]) ? $fields[2] : null,
    'user' => isset($fields[3]) ? $fields[3] : null,
    'group' => isset($fields[4]) ? $fields[4] : null,
    'age' => isset($fields[5]) ? $fields[5] : null,
  );
}

echo "every line is a well formed entry\n";
check('there are some entries', count($entries) > 0, true);
$bad = array();
foreach ($entries as $entry) {
  // The type letters that create or manage a directory. Anything else in this
  // file would be a typo rather than a choice.
  if (!in_array($entry['type'], array('d', 'D', 'e', 'v', 'q', 'Q'), true)) {
    $bad[] = $entry['line'].': type "'.$entry['type'].'"';
    continue;
  }
  if ($entry['path'] === null or $entry['path'] === '') {
    $bad[] = $entry['line'].': no path';
  } else if (!preg_match('/^(@[A-Z_]+@|\/)/', $entry['path'])) {
    // Either a cmake variable or an absolute path; a relative one would be
    // silently ignored by systemd.
    $bad[] = $entry['line'].': path "'.$entry['path'].'" is neither @VAR@ nor absolute';
  }
  if ($entry['mode'] !== null and $entry['mode'] !== '-' and
      !preg_match('/^[0-7]{3,4}$/', $entry['mode'])) {
    $bad[] = $entry['line'].': mode "'.$entry['mode'].'"';
  }
  if ($entry['age'] !== null and $entry['age'] !== '-' and
      !preg_match('/^(~?[a-zA-Z]*:)?\d+(s|m|min|h|d|w|ms|us)?$/', $entry['age'])) {
    $bad[] = $entry['line'].': age "'.$entry['age'].'"';
  }
}
check('none of them is malformed', $bad, array());

echo "the cache busting directory is cleaned\n";
// The reason this file was touched. Without an age the directory is created
// and then grows for the life of the install: cache_bust() adds a symlink per
// source file version and removes nothing.
$by_path = array();
foreach ($entries as $entry) $by_path[$entry['path']] = $entry;
check('it has an entry', isset($by_path['@ZM_CACHEDIR@']), true);
if (isset($by_path['@ZM_CACHEDIR@'])) {
  $cache = $by_path['@ZM_CACHEDIR@'];
  check('with an age, so the timer cleans it',
        ($cache['age'] !== null and $cache['age'] !== '-'), true);
  // The age field is only honoured for certain types. On a type that ignores
  // it the line still loads, and still never cleans anything.
  check('on a type that honours an age',
        in_array($cache['type'], array('d', 'D', 'e', 'v', 'q', 'Q'), true), true);
  // d creates and cleans. D also empties the directory at boot, which for a
  // cache would throw away every current entry on every reboot.
  check('and is d rather than D, so a reboot does not empty it',
        $cache['type'], 'd');
}

echo "it names the same directory the web ui writes to\n";
// cache_bust() writes to ZM_DIR_CACHE, which config.php.in defines from a
// cmake variable. If the two drift, systemd cleans a directory nothing uses
// and the one that grows is left alone -- and nothing would report it.
$config_in = file_get_contents($root.'/web/includes/config.php.in');
check('config.php.in defines ZM_DIR_CACHE from a cmake variable',
      (bool)preg_match("/define\(\s*'ZM_DIR_CACHE'\s*,\s*'(@[A-Z_]+@)'\s*\)/", $config_in, $m),
      true);
if (isset($m[1])) {
  check('and the tmpfiles entry uses that same variable',
        isset($by_path[$m[1]]), true);
}

echo "the runtime directories are still there\n";
// Not what this file was changed for, but they are the reason it exists and a
// careless edit could drop one.
foreach (array('@ZM_RUNDIR@', '@ZM_TMPDIR@', '@ZM_SOCKDIR@') as $var) {
  check($var.' is present', isset($by_path[$var]), true);
}

echo "systemd itself accepts the file\n";
// The checks above are this test's reading of tmpfiles.d(5). This is the real
// parser's opinion, which is the one that counts. Every path is rewritten into
// a temporary directory first, so running a clean cannot touch anything real.
$systemd = trim((string)@shell_exec('command -v systemd-tmpfiles 2>/dev/null'));
if ($systemd === '') {
  skip('systemd-tmpfiles accepts every line', 'systemd-tmpfiles not installed');
} else {
  $tmp = sys_get_temp_dir().'/zm_tmpfiles_test_'.getmypid();
  @mkdir($tmp.'/cache', 0755, true);
  $substituted = str_replace(
      array('@ZM_RUNDIR@', '@ZM_TMPDIR@', '@ZM_SOCKDIR@', '@ZM_CACHEDIR@',
            '@WEB_USER@', '@WEB_GROUP@'),
      array($tmp.'/run', $tmp.'/tmp', $tmp.'/sock', $tmp.'/cache',
            trim((string)shell_exec('id -un')), trim((string)shell_exec('id -gn'))),
      $conf);
  $conf_file = $tmp.'/zm.conf';
  file_put_contents($conf_file, $substituted);

  // A symlink older than the configured age, to show the clean reaches the
  // kind of entry cache_bust() leaves behind. Its ctime cannot be backdated,
  // and an age is only met when atime, mtime and ctime are all older than it,
  // so this one is expected to survive -- the point here is the parse.
  symlink('/etc/hostname', $tmp.'/cache/stale.js');

  $err = $tmp.'/stderr';
  @shell_exec(escapeshellcmd($systemd).' --clean '.escapeshellarg($conf_file).
              ' 2>'.escapeshellarg($err));
  $stderr = (string)@file_get_contents($err);
  // Complaints about the file's contents. Anything about creating or removing
  // a path is this sandbox, not the syntax.
  $complaints = array();
  foreach (explode("\n", $stderr) as $msg) {
    if ($msg === '') continue;
    if (preg_match('/(Invalid|Unknown|Failed to parse|Syntax|unrecognized|'.
                   'Line references path below|Ignoring invalid)/i', $msg)) {
      $complaints[] = trim($msg);
    }
  }
  check('systemd-tmpfiles accepts every line', $complaints, array());

  foreach (glob($tmp.'/cache/*') as $f) @unlink($f);
  foreach (array('/cache', '/run', '/tmp', '/sock') as $d) @rmdir($tmp.$d);
  @unlink($conf_file);
  @unlink($err);
  @rmdir($tmp);
}

echo "\n$passed passed, $failed failed".($skipped ? ", $skipped skipped" : '')."\n";
exit($failed ? 1 : 0);
