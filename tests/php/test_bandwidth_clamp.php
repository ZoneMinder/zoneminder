<?php
// Tests the zmBandwidth whitelist and the ZM_WEB_* aliases it guards.
//
// web/skins/classic/includes/config.php aliases one bandwidth profile's
// ZM_WEB_<H|M|L>_* settings to the plain ZM_WEB_* names the rest of the skin
// reads. It used to do that with a switch that had no default case, so an
// unrecognised profile left all of them undefined and every page fatally
// stopped on the first one it used - on PHP 8 an undefined constant is an
// Error. Both the whitelist that keeps the profile recognisable and the alias
// loop that no longer depends on it are checked here.
//
// The skin config is loaded in a child process, once per profile under test:
// constants cannot be redefined or unset, and loading it is itself what can
// fail, so a fatal there should be a reported check rather than the end of the
// run.
//
// Run: sudo -u www-data php tests/php/test_bandwidth_clamp.php   (from the repo root)
set_include_path(__DIR__.'/../../build/web/includes'.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');
require_once('functions.php');

$skin_config = __DIR__.'/../../web/skins/classic/includes/config.php';
$skin_php = __DIR__.'/../../web/skins/classic/skin.php';

// ---------------------------------------------------------------- child mode
if (isset($argv[1]) && $argv[1] == '--probe') {
  // lang.php is not loaded here, so translate() has to be stubbed.
  if (!function_exists('translate')) { function translate($string) { return $string; } }
  $_COOKIE['zmBandwidth'] = $argv[2];
  require_once($skin_config);

  $aliases = array_merge($bandwidth_settings, array_keys($bandwidth_optional_settings));
  $undefined = array();
  foreach ($aliases as $setting) {
    if (!defined('ZM_WEB_'.$setting)) $undefined[] = 'ZM_WEB_'.$setting;
  }
  // A required setting with no value under some profile makes constant() throw
  // as soon as that profile is selected - the same blank page by another route.
  $missing = array();
  foreach ($bandwidth_prefixes as $profile => $prefix) {
    foreach ($bandwidth_settings as $setting) {
      if (!defined('ZM_WEB_'.$prefix.'_'.$setting)) $missing[] = 'ZM_WEB_'.$prefix.'_'.$setting;
    }
  }
  // Independent of the lists above: the per-profile config options are the
  // authority on which settings exist, so every ZM_WEB_H_<X> has to end up
  // aliased as ZM_WEB_<X>. Dropping a name from $bandwidth_settings would
  // otherwise go unnoticed here while leaving that alias undefined in the skin.
  $unaliased = array();
  foreach (get_defined_constants() as $name => $value) {
    if (preg_match('/^ZM_WEB_H_(.+)$/', $name, $m) && !defined('ZM_WEB_'.$m[1])) $unaliased[] = $name;
  }

  print(json_encode(array(
    'profiles' => array_keys($bandwidth_prefixes),
    'alias_count' => count($aliases),
    'undefined_aliases' => $undefined,
    'missing_sources' => $missing,
    'unaliased_settings' => $unaliased,
  )));
  exit(0);
}

// --------------------------------------------------------------- parent mode
$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

// Load the skin config under $profile and report what it defined. Anything the
// child printed that is not the expected JSON - a fatal, a warning - is handed
// back as an error string so the caller can fail a check with it.
function probe($profile) {
  $out = trim(shell_exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --probe '.escapeshellarg($profile).' 2>&1'));
  $decoded = json_decode($out, true);
  return is_array($decoded) ? $decoded : array('error' => $out);
}

$high = probe('high');
check('the skin config loads for a valid profile', $high['error'] ?? 'ok', 'ok');
if (isset($high['error'])) {
  print("\ncannot continue without a loadable skin config\n");
  exit(1);
}

// The three profiles are accepted, everything else is not. The rejected values
// are the ones that actually reach the cookie: unset, a cleared cookie, a
// casing the lookup would not match, and free-form junk.
foreach (array('high', 'medium', 'low') as $valid) {
  check("'$valid' is a valid bandwidth", isValidBandwidth($valid), true);
}
foreach (array('', 'High', 'LOW', 'auto', 'garbage', '0', 'high ') as $invalid) {
  check("'$invalid' is rejected", isValidBandwidth($invalid), false);
}
check('null is rejected', isValidBandwidth(null), false);

// Set equality in both directions between the whitelist and the profiles the
// skin actually has settings for. A profile in one and not the other is how
// the cookie ends up selecting something the skin cannot resolve.
$profiles = $high['profiles'];
sort($profiles);
check('the skin profiles are exactly the accepted profiles', $profiles, array('high', 'low', 'medium'));
foreach ($profiles as $profile) {
  check("skin profile '$profile' is accepted by isValidBandwidth", isValidBandwidth($profile), true);
}

// skin.php's last-resort fallback must itself survive the whitelist, or the
// clamp would hand the skin a profile it does not have settings for.
$skin_src = file_get_contents($skin_php);
preg_match("/isValidBandwidth\(ZM_BANDWIDTH_DEFAULT\)\s*\?\s*ZM_BANDWIDTH_DEFAULT\s*:\s*'([a-z]+)'/", $skin_src, $fb);
check('found the clamp fallback in skin.php', !empty($fb), true);
check("skin.php fallback '".($fb[1] ?? '')."' is valid", isValidBandwidth($fb[1] ?? ''), true);

// Every alias resolves. This is what the page footer reads on every request.
check('loading the skin config defines every ZM_WEB_ alias', $high['undefined_aliases'], array());
check('there are aliases to define', $high['alias_count'] > 0, true);
check('every profile has a value for every required setting', $high['missing_sources'], array());
check('every per-profile setting is aliased', $high['unaliased_settings'], array());

// The regression itself: even with a profile the whitelist would have rejected,
// the aliases must all still be defined, so a bad cookie can never blank a page.
$junk = probe('not-a-profile');
check('an unrecognised profile loads the skin config', $junk['error'] ?? 'ok', 'ok');
check('an unrecognised profile still defines every alias', $junk['undefined_aliases'] ?? null, array());

print("\n".($failures ? "$failures FAILED" : 'all passed')."\n");
exit($failures ? 1 : 0);
