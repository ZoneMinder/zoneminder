<?php
// Tests for the ZM_WEB_LOGIN_MESSAGE notice on the login view.
//
// The login page is served before anyone has authenticated, so the message is
// escaped rather than interpreted. Escaping has to happen BEFORE nl2br, so the
// only tags that reach the browser are the <br /> we add ourselves. Reversing
// that order would turn the setting into stored XSS on an unauthenticated page.
//
// Run as: php tests/php/test_login_message.php

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

// Same as web/includes/functions.php.
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}

// The rendering decision and expression used by the login view. Kept in step
// with web/skins/classic/views/login.php.
function renderLoginMessage($message) {
  if (trim($message) === '') return null;
  return '<div id="loginMessage">'.nl2br(validHtmlStr(trim($message))).'</div>';
}

echo "when the message is shown\n";
check('empty message renders nothing', renderLoginMessage(''), null);
check('whitespace-only message renders nothing', renderLoginMessage("  \n\t "), null);
check('a message renders a block',
  renderLoginMessage('Authorized users only.'),
  '<div id="loginMessage">Authorized users only.</div>');
check('surrounding whitespace is trimmed',
  renderLoginMessage("  Authorized users only.  "),
  '<div id="loginMessage">Authorized users only.</div>');

echo "\nmulti-line messages\n";
check('newlines become <br />',
  renderLoginMessage("Line one\nLine two"),
  '<div id="loginMessage">Line one<br />'."\n".'Line two</div>');

echo "\nhtml is escaped, not interpreted\n";
// If escaping and nl2br were the other way round, or escaping were dropped,
// these would emit live markup on a page shown to unauthenticated visitors.
check('script tag is inert',
  renderLoginMessage('<script>alert(1)</script>'),
  '<div id="loginMessage">&lt;script&gt;alert(1)&lt;/script&gt;</div>');
check('img onerror is inert',
  renderLoginMessage('<img src=x onerror=alert(1)>'),
  '<div id="loginMessage">&lt;img src=x onerror=alert(1)&gt;</div>');
check('formatting tags are shown literally',
  renderLoginMessage('Site: <b>HQ</b>'),
  '<div id="loginMessage">Site: &lt;b&gt;HQ&lt;/b&gt;</div>');
check('quotes and ampersands are escaped',
  renderLoginMessage('He said "hi" & \'bye\''),
  '<div id="loginMessage">He said &quot;hi&quot; &amp; &#039;bye&#039;</div>');

// The only tags in the output must be the <br /> nl2br adds.
$rendered = renderLoginMessage("<i>a</i>\n<u>b</u>");
preg_match_all('/<([a-z\/][^>]*)>/i', $rendered, $m);
$tags = array_values(array_filter($m[1], function($t) {
  return stripos($t, 'div') === false;
}));
check('no tags survive except the br we add', $tags, array('br /'));

echo "\nconfig option definition\n";
$cfg = file_get_contents(__DIR__.'/../../scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm.in');
check('ZM_WEB_LOGIN_MESSAGE is defined',
  (bool)preg_match("/name\s*=>\s*'ZM_WEB_LOGIN_MESSAGE'/", $cfg), true);
// An empty default keeps existing installs looking exactly as they do now.
check("defaults to empty so nothing is shown until configured",
  (bool)preg_match("/name\s*=>\s*'ZM_WEB_LOGIN_MESSAGE',\s*\n\s*default\s*=>\s*'',/", $cfg), true);
// text rather than string, so the DB column holds a multi-line notice.
check('uses the text type for multi-line content',
  (bool)preg_match("/name\s*=>\s*'ZM_WEB_LOGIN_MESSAGE'.*?type\s*=>\s*\\\$types\{text\}/s", $cfg), true);
check('is in the web category',
  (bool)preg_match("/name\s*=>\s*'ZM_WEB_LOGIN_MESSAGE'.*?category\s*=>\s*'web'/s", $cfg), true);

echo "\nlogin view wiring\n";
$view = file_get_contents(__DIR__.'/../../web/skins/classic/views/login.php');
// Guarded with defined() so code newer than the database still renders.
check('guarded by defined() for installs that have not run zmupdate yet',
  (bool)preg_match('/defined\(\x27ZM_WEB_LOGIN_MESSAGE\x27\)/', $view), true);
check('escaped before nl2br',
  (bool)preg_match('/nl2br\(validHtmlStr\(/', $view), true);
// Placement: after the heading, before the username field.
$posMsg = strpos($view, 'id="loginMessage"');
$posUser = strpos($view, 'id="inputUsername"');
$posH1 = strpos($view, '<h1>');
check('rendered after the title and before the username field',
  ($posH1 < $posMsg && $posMsg < $posUser), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
