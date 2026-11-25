<?php
// "Ignore this version" in the version dropdown (includes/actions/version.php)
// stores the latest version in ZM_DYN_CURR_VERSION, but getZMVersionHTML() only
// compared ZM_DYN_LAST_VERSION with ZM_VERSION, so the update notice came back
// on every page after choosing Ignore.
//
// Run as: php tests/php/test_version_ignore_hides_notice.php   (from the repo root)

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

function canView($area) { return true; }
function canEdit($area) { return true; }
function translate($string) { return $string; }
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}
// Same as web/includes/functions.php.
function verNum($version) {
  $vNum = '';
  $vFields = explode('.', $version);
  array_splice($vFields, 3);
  while (count($vFields) < 3) $vFields[] = 0;
  foreach ($vFields as $vField) $vNum .= sprintf('%02d', $vField);
  return $vNum;
}

define('ZM_DYN_DB_VERSION', '1.37.0');
define('ZM_VERSION', '1.37.0');
define('ZM_CHECK_FOR_UPDATES', 1);
define('ZM_DYN_NEXT_REMINDER', 0);
define('ZM_DYN_LAST_VERSION', '1.38.0');
// The version the user chose to ignore.
define('ZM_DYN_CURR_VERSION', '1.38.0');

$skin = 'classic';
$view = 'console';
require_once(__DIR__.'/../../web/skins/classic/includes/functions.php');

$html = getZMVersionHTML();
check('no update notice once the latest version is ignored', strpos($html, 'dropdown_reminder'), false);

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
