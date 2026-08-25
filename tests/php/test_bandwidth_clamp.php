<?php
// Tests the zmBandwidth whitelist. web/skins/classic/includes/config.php defines
// the whole ZM_WEB_* constant set by switching on the cookie and has no default
// case, so a value outside the three profiles leaves every one of those
// constants undefined and each page fatals on the first one it uses. That makes
// isValidBandwidth() - and its agreement with the switch it guards - load
// bearing.
// Run: sudo -u www-data php tests/php/test_bandwidth_clamp.php   (from the repo root)
set_include_path(__DIR__.'/../../build/web/includes'.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');
require_once('functions.php');

$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
  if (!$ok) printf("        got:  %s\n        want: %s\n", var_export($got, true), var_export($want, true));
}

$skin_config = __DIR__.'/../../web/skins/classic/includes/config.php';
$skin_php = __DIR__.'/../../web/skins/classic/skin.php';

// The three profiles are accepted, everything else is not. The rejected values
// are the ones that actually reach the cookie: unset, a cleared cookie, a
// casing the switch would not match, and free-form junk.
foreach (array('high', 'medium', 'low') as $valid) {
  check("'$valid' is a valid bandwidth", isValidBandwidth($valid), true);
}
foreach (array('', 'High', 'LOW', 'auto', 'garbage', '0', 'high ') as $invalid) {
  check("'$invalid' is rejected", isValidBandwidth($invalid), false);
}
check('null is rejected', isValidBandwidth(null), false);

// The whitelist has to name exactly the cases the switch handles. If a profile
// is ever added to one and not the other, the constants go undefined again.
$config_src = file_get_contents($skin_config);
preg_match('/switch\s*\(\s*\$_COOKIE\[.zmBandwidth.\]\s*\)\s*\{(.*)\n\}/s', $config_src, $m);
check('found the bandwidth switch in the skin config', !empty($m), true);
$switch_body = $m[1] ?? '';
preg_match_all("/case\s*'([a-z]+)'\s*:/", $switch_body, $cases);
$switch_cases = $cases[1];
sort($switch_cases);
// Set equality in both directions: no case the clamp would reject as invalid,
// and no value the clamp accepts that the switch cannot handle.
check('the switch cases are exactly the accepted profiles', $switch_cases, array('high', 'low', 'medium'));
foreach ($switch_cases as $case) {
  check("switch case '$case' is accepted by isValidBandwidth", isValidBandwidth($case), true);
}

// skin.php's last-resort fallback must itself survive the whitelist, or the
// clamp would hand the switch a value it cannot match.
$skin_src = file_get_contents($skin_php);
preg_match("/isValidBandwidth\(ZM_BANDWIDTH_DEFAULT\)\s*\?\s*ZM_BANDWIDTH_DEFAULT\s*:\s*'([a-z]+)'/", $skin_src, $fb);
check('found the clamp fallback in skin.php', !empty($fb), true);
check("skin.php fallback '".($fb[1] ?? '')."' is valid", isValidBandwidth($fb[1] ?? ''), true);

// Each arm must define the same constants; a constant present in only one is
// undefined for the other two profiles, which is this bug in a narrower form.
$per_case = array();
foreach (preg_split("/case\s*'[a-z]+'\s*:/", $switch_body) as $i => $arm) {
  if ($i == 0) continue; // text before the first case
  preg_match_all("/define\(\s*'(ZM_WEB_[A-Z_]+)'/", $arm, $defs);
  $names = $defs[1];
  sort($names);
  $per_case[] = $names;
}
check('parsed all three switch arms', count($per_case), 3);
check('every arm defines the same ZM_WEB_* constants',
    $per_case[0] === ($per_case[1] ?? null) && $per_case[0] === ($per_case[2] ?? null), true);
check('arms define a non-empty constant set', count($per_case[0] ?? array()) > 0, true);

print("\n".($failures ? "$failures FAILED" : 'all passed')."\n");
exit($failures ? 1 : 0);
