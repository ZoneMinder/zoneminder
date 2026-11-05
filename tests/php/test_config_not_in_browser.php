<?php
// GHSA-jv9c-pg2m-5m6m: the database credentials and other values that live only
// in zm.conf / conf.d must never reach the browser. On this branch two things
// keep them out, and this test locks both so a later change can't quietly undo
// them:
//
//   1. config.php.in loadConfig() builds $config purely from the Config table
//      (SELECT Name,Value,Private FROM Config). It never merges the parsed
//      config-file values ($configvals) into $config, so file-only keys such as
//      ZM_DB_USER / ZM_DB_PASS are plain PHP constants and are simply not in the
//      array skin.js.php walks. (The master leak came from a later commit that
//      merged the config files into loadConfig()'s result; that merge is absent
//      here, and if it is ever added the file-only values must be marked Private
//      the way master does.)
//
//   2. skin.js.php only exports a $config entry that is neither a password Type
//      nor flagged Private, so even a Config row holding a secret stays out.
//
// Both source files touch neither ZM's config nor its database, so this runs
// from a source checkout: php tests/php/test_config_not_in_browser.php

$failures = 0;
$passes = 0;

function check($name, $cond) {
  global $failures, $passes;
  if ($cond) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n";
  }
}

// ---------------------------------------------------------------------------
// Part A - behavioural: run skin.js.php's real config-export loop over a
// synthetic $config and check what it emits.
// ---------------------------------------------------------------------------

$skin = file_get_contents(__DIR__.'/../../web/skins/classic/js/skin.js.php');
if ($skin === false) {
  echo "not ok - could not read skin.js.php\n";
  exit(1);
}

// Pull the real `foreach ($config as $name=>$c) { ... }` statement out of the
// file by matching its braces, so the test exercises the shipped logic verbatim
// rather than a copy that could drift from it.
$start = strpos($skin, 'foreach ($config as $name=>$c) {');
check('skin.js.php has the config-export foreach', $start !== false);
$export_loop = '';
if ($start !== false) {
  $brace = strpos($skin, '{', $start);
  $depth = 0;
  $end = $brace;
  for ($i = $brace; $i < strlen($skin); $i++) {
    if ($skin[$i] === '{') $depth++;
    else if ($skin[$i] === '}') {
      $depth--;
      if ($depth === 0) { $end = $i; break; }
    }
  }
  $export_loop = substr($skin, $start, $end - $start + 1);
}

// The two guards that keep secrets out must both be present in that loop.
check('export loop skips password-Type options',
  strpos($export_loop, "\$c['Type'] == 'password'") !== false &&
  strpos($export_loop, 'continue') !== false);
check('export loop honours the Private flag',
  preg_match('/!\s*\(\s*isset\(\$c\[.Private.\]\)\s*and\s*\$c\[.Private.\]\s*\)/', $export_loop) === 1);

// Run the real loop over a crafted $config and capture the emitted JavaScript.
function run_export_loop($export_loop, $config) {
  ob_start();
  eval($export_loop);
  return ob_get_clean();
}

$config = array(
  // A DB credential never has a Config row, but prove that even if one appeared
  // with the password Type (how ZM stores secrets) it would not be exported.
  'ZM_DB_PASS'          => array('Value' => 's3cr3t',        'Type' => 'password', 'Private' => 0),
  // A secret marked Private is withheld whatever its Type.
  'ZM_AUTH_HASH_SECRET' => array('Value' => 'topsecret',     'Type' => 'string',   'Private' => 1),
  // An ordinary option is exported so we know the loop actually runs.
  'ZM_DIR_SOUNDS'       => array('Value' => 'sounds',        'Type' => 'string',   'Private' => 0),
  'ZM_WEB_REFRESH_MAIN' => array('Value' => '60',            'Type' => 'integer',  'Private' => 0),
);
$out = run_export_loop($export_loop, $config);

check('password-Type ZM_DB_PASS is not exported',
  strpos($out, 'ZM_DB_PASS') === false && strpos($out, 's3cr3t') === false);
check('Private ZM_AUTH_HASH_SECRET is not exported',
  strpos($out, 'ZM_AUTH_HASH_SECRET') === false && strpos($out, 'topsecret') === false);
check('ordinary ZM_DIR_SOUNDS is exported',
  strpos($out, "const ZM_DIR_SOUNDS = \"sounds\";") !== false);
check('integer option is exported unquoted',
  strpos($out, 'const ZM_WEB_REFRESH_MAIN = 60;') !== false);

// ---------------------------------------------------------------------------
// Part B - source: loadConfig() in config.php.in is DB-only and does not fold
// the config files into the array it returns.
// ---------------------------------------------------------------------------

$cfg = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
if ($cfg === false) {
  echo "not ok - could not read config.php.in\n";
  exit(1);
}

// Isolate the loadConfig() function body by brace matching.
$fstart = strpos($cfg, 'function loadConfig(');
check('config.php.in has loadConfig()', $fstart !== false);
$body = '';
if ($fstart !== false) {
  $brace = strpos($cfg, '{', $fstart);
  $depth = 0;
  for ($i = $brace; $i < strlen($cfg); $i++) {
    if ($cfg[$i] === '{') $depth++;
    else if ($cfg[$i] === '}') {
      $depth--;
      if ($depth === 0) { $body = substr($cfg, $brace, $i - $brace + 1); break; }
    }
  }
}
// Strip line comments so a comment mentioning $configvals can't fool the checks.
$body_nc = preg_replace('/^\s*(#|\/\/).*$/m', '', $body);

check('loadConfig selects Name,Value,Private from the Config table',
  preg_match('/SELECT\s+Name,\s*Value,\s*Private\s+FROM\s+Config/i', $body_nc) === 1);
check('loadConfig does not read the parsed config files ($configvals)',
  strpos($body_nc, '$configvals') === false);
check('loadConfig does not array_merge the config files into its result',
  strpos($body_nc, 'array_merge') === false);
// $config entries are built only from the DB row, so Private stays the DB value.
check('loadConfig stores the DB row (with its Private flag) as the config entry',
  preg_match('/\$config\[\$row\[.Name.\]\]\s*=\s*\$row/', $body_nc) === 1);

// ---------------------------------------------------------------------------
echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
