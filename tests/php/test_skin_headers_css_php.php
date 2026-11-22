<?php
// Tests xhtmlHeadersStart(), which prints the <head> of every classic skin page.
//
// The skin specific stylesheet generator css/<css>/skin.css.php was looked up
// into $skinCssPhpFile but the line that includes it required $baseCssPhpFile
// again, which require_once() ignores. A skin or colour scheme that ships a
// skin.css.php never had it included in the page.
//
// Run as: php tests/php/test_skin_headers_css_php.php   (from the repo root)

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

eval('namespace ZM; function Error($m) {} function Warning($m) {} function Debug($m) {}');

function canView($area) { return true; }
function translate($string) { return $string; }
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}
function cache_bust($file) { return $file; }
// Resolve skin files inside a scratch skin directory.
function getSkinFile($file) {
  $path = $GLOBALS['skinDir'].'/'.ltrim($file, '/');
  return file_exists($path) ? $path : false;
}

define('ZM_WEB_TITLE_PREFIX', 'ZM');

$skinDir = sys_get_temp_dir().'/zm_skin_css_'.getmypid();
mkdir($skinDir.'/css/base', 0777, true);
mkdir($skinDir.'/css/dark', 0777, true);
file_put_contents($skinDir.'/css/base/skin.css.php', '/*BASE-CSS-PHP*/');
file_put_contents($skinDir.'/css/dark/skin.css.php', '/*DARK-CSS-PHP*/');

$skin = 'classic';
$view = 'console';
$css = 'dark';
$cspNonce = 'abc';
$navbar_type = 'normal';
require_once(__DIR__.'/../../web/skins/classic/includes/functions.php');

ob_start();
xhtmlHeadersStart('/web/skins/classic/views/console.php', 'Console');
$html = ob_get_clean();

check('base skin.css.php included', substr_count($html, '/*BASE-CSS-PHP*/'), 1);
check('selected skin.css.php included', substr_count($html, '/*DARK-CSS-PHP*/'), 1);

unlink($skinDir.'/css/base/skin.css.php');
unlink($skinDir.'/css/dark/skin.css.php');
rmdir($skinDir.'/css/base');
rmdir($skinDir.'/css/dark');
rmdir($skinDir.'/css');
rmdir($skinDir);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
