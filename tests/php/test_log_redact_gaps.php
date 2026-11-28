<?php
// Gap checks for web/includes/log_redact.php: credential parameters in query
// strings, userinfo in plain text, credentials in JSON, credential-looking
// keys in arrays, and keys that must be left alone.
//
// Run as: php tests/php/test_log_redact_gaps.php .
// The optional argument is the repo root (defaults to two levels up).

$root = $argv[1] ?? __DIR__.'/../..';
require_once $root.'/web/includes/log_redact.php';

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

echo "redactQueryString\n";
$q = ZM\redactQueryString('a=1;auth=abc;b=2');
check('auth value not logged', strpos($q, 'abc'), false);
check('keeps a=1', strpos($q, 'a=1') !== false, true);
check('keeps b=2', strpos($q, 'b=2') !== false, true);

echo "\nredactForLog: userinfo in text\n";
$s = ZM\redactForLog('connect failed for http://cam:secretpw@10.0.0.5/stream here');
check('camera password not logged', strpos($s, 'secretpw'), false);
check('keeps URL without userinfo', strpos($s, 'http://10.0.0.5/stream') !== false, true);

echo "\nredactForLog: JSON\n";
$j = ZM\redactForLog('{"user":"bob","password":"hunter2","x":1}');
check('JSON password not logged', strpos($j, 'hunter2'), false);
check('JSON user kept', strpos($j, '"user":"bob"') !== false, true);

echo "\nredactForLog: credential-looking keys\n";
foreach (array('authToken', 'api_key', 'apikey', 'bearer', 'sid', 'Authorization_token') as $key) {
  $out = ZM\redactForLog(array($key => 'topsecretvalue'));
  check("$key value not logged", strpos($out[$key], 'topsecretvalue'), false);
}

echo "\nredactForLog: non-credential keys\n";
check('name and host untouched', ZM\redactForLog(array('name' => 'cam1', 'host' => '10.0.0.5')),
  array('name' => 'cam1', 'host' => '10.0.0.5'));

echo "\nredactForLog/urlForLog: existing behaviour\n";
check('password redacted', ZM\redactForLog(array('password' => 'x'))['password'], '[redacted]');
check('auth fingerprinted', substr(ZM\redactForLog(array('auth' => 'abc'))['auth'], 0, 3), 'fp:');
$u = ZM\urlForLog('https://u:p@h/x?token=abc&b=2');
check('URL userinfo not logged', strpos($u, 'u:p@'), false);
check('URL token not logged', strpos($u, 'abc'), false);
check('URL other params kept', strpos($u, 'b=2') !== false, true);

echo "\nsemicolon and JSON ordering\n";
check('raw ; in a password value is fully redacted', ZM\redactQueryString('password=pw12;tail99'), 'password=[redacted]');
check('; before a name= still ends the value', ZM\redactQueryString('password=pw12;b=2'), 'password=[redacted];b=2');
check('encoded ; in a redirect', ZM\redactQueryString(urldecode('pass=pw12%3Btail99')), 'pass=[redacted]');
$t = ZM\redactForLog('{"token":"c2VjcmV0dG9rZW4="}');
check('JSON token with = fully fingerprinted', preg_match('/^\{"token":"fp:[0-9a-f]{8}"\}$/', $t), 1);
check('JSON password with = fully redacted', ZM\redactForLog('{"password":"left55=right66"}'), '{"password":"[redacted]"}');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
