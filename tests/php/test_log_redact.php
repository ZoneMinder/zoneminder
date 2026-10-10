<?php
// Tests web/includes/log_redact.php, the helpers that keep credentials out of
// the web log: logFingerprint(), urlForLog() and redactForLog().
//
// index.php logged the whole request at debug level (the login form's
// password, auth= hashes, token= JWTs, camera passwords from the monitor form)
// and the API's AppController logged the whole session (auth hashes and, with
// ZM_AUTH_RELAY=plain, the password). Both now log redactForLog() of it; the
// last block checks no raw dump of a superglobal is left in web/.
//
// Run as: php tests/php/test_log_redact.php

require_once __DIR__.'/../../web/includes/log_redact.php';

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

echo "logFingerprint\n";
// Same vector as tests/zm_crypt.cpp: web and daemon logs give the same tag.
check('first 8 hex of SHA-256', ZM\logFingerprint('abc'), 'fp:ba7816bf');
check('empty value', ZM\logFingerprint(''), 'fp:none');
check('null value', ZM\logFingerprint(null), 'fp:none');
$auth = md5('anything');
check('does not contain the value', strpos(ZM\logFingerprint($auth), $auth), false);
check('stable', ZM\logFingerprint($auth), ZM\logFingerprint($auth));

echo "\nurlForLog\n";
check('plain URL unchanged', ZM\urlForLog('https://zm2.example/zm/api/events/5.json'),
  'https://zm2.example/zm/api/events/5.json');
check('auth hash fingerprinted, other parameters kept',
  ZM\urlForLog('https://zm2.example/zm/index.php?view=filter&auth=0123abcd&user=admin'),
  'https://zm2.example/zm/index.php?view=filter&auth='.ZM\logFingerprint('0123abcd').'&user=admin');
// Event.php builds the plain relay as ?user=..?pass=.. (two '?').
check('plain relay password removed', ZM\urlForLog('http://zm2/zm/api/x.json?user=admin?pass=hunter2'),
  'http://zm2/zm/api/x.json?user=admin?pass=[redacted]');
check('token fingerprinted', ZM\urlForLog('http://zm2/zm/api/x.json?token=eyJa.b.c'),
  'http://zm2/zm/api/x.json?token='.ZM\logFingerprint('eyJa.b.c'));
check('userinfo removed', ZM\urlForLog('rtsp://admin:hunter2@192.0.2.5:554/s'), 'rtsp://192.0.2.5:554/s');
check('@ in the query does not cut the host', ZM\urlForLog('http://zm2/x?token=a@b'),
  'http://zm2/x?token='.ZM\logFingerprint('a@b'));
// Camera passwords with a raw @ work with cameras and ffmpeg (they split at
// the last @ of the authority), so the userinfo ends at the last one.
check('password with a raw @', ZM\urlForLog('rtsp://admin:p@ss@192.0.2.5:554/s'), 'rtsp://192.0.2.5:554/s');
check('ControlAddress without a scheme', ZM\urlForLog('admin:hunter2@192.0.2.5:8080'), '192.0.2.5:8080');
check('host:port alone unchanged', ZM\urlForLog('192.0.2.5:8080'), '192.0.2.5:8080');

echo "\nredactForLog\n";
$request = array(
  'action' => 'login',
  'username' => 'admin',
  'password' => 'hunter2',
  'auth' => $auth,
  'token' => 'eyJhbGciOiJIUzI1NiJ9.e30.sig',
  'refresh_token' => 'eyJhbGciOiJIUzI1NiJ9.e30.ref',
  '__csrf_magic' => 'sid:abc,123',
  'view' => 'console',
  'newUser' => array('Username' => 'bob', 'Password' => 'pw1', 'ConfirmPassword' => 'pw1'),
  'newMonitor' => array(
    'Name' => 'Front',
    'Pass' => 'campw',
    'ONVIF_Password' => 'onvifpw',
    'Path' => 'rtsp://cam:campw@192.0.2.7/stream',
  ),
);
$out = ZM\redactForLog($request);
check('non-secret values kept', array($out['action'], $out['username'], $out['view'], $out['newUser']['Username'], $out['newMonitor']['Name']),
  array('login', 'admin', 'console', 'bob', 'Front'));
check('password replaced', $out['password'], '[redacted]');
check('auth hash fingerprinted', $out['auth'], ZM\logFingerprint($auth));
check('token fingerprinted', $out['token'], ZM\logFingerprint($request['token']));
check('refresh token fingerprinted', $out['refresh_token'], ZM\logFingerprint($request['refresh_token']));
check('CSRF token fingerprinted', $out['__csrf_magic'], ZM\logFingerprint('sid:abc,123'));
check('nested user password replaced', array($out['newUser']['Password'], $out['newUser']['ConfirmPassword']),
  array('[redacted]', '[redacted]'));
check('camera passwords replaced', array($out['newMonitor']['Pass'], $out['newMonitor']['ONVIF_Password']),
  array('[redacted]', '[redacted]'));
check('credentials removed from a URL value', $out['newMonitor']['Path'], 'rtsp://192.0.2.7/stream');

// actions/monitor.php logs the changes a monitor save makes, and index.php the
// request: camera user:password@ in a ControlAddress has no scheme.
$changes = ZM\redactForLog(array(
  'Name' => 'Front',
  'ControlAddress' => 'admin:campw@192.0.2.7:80',
  'Path' => 'rtsp://admin:cam@pw@192.0.2.7:554/stream',
  'Pass' => 'campw',
  'ONVIF_Password' => 'onvifpw',
  'Function' => 'Modect',
));
check('monitor changes: credentials gone, the rest kept', $changes, array(
  'Name' => 'Front',
  'ControlAddress' => '192.0.2.7:80',
  'Path' => 'rtsp://192.0.2.7:554/stream',
  'Pass' => '[redacted]',
  'ONVIF_Password' => '[redacted]',
  'Function' => 'Modect',
));
check('an e-mail address is kept', ZM\redactForLog(array('Email' => 'bob@example.com'))['Email'], 'bob@example.com');


// index.php's login redirect carries the original query string, credentials
// included, as postLoginQuery; the next request's dump would log it.
$plq = ZM\redactForLog(array('postLoginQuery' => 'view=console&auth='.$auth.'&user=admin&password=hunter2&token=eyJx.y.z'));
check('a nested query string is redacted', $plq['postLoginQuery'],
  'view=console&auth='.ZM\logFingerprint($auth).'&user=admin&password=[redacted]&token='.ZM\logFingerprint('eyJx.y.z'));
check('redactQueryString on a redirect target',
  ZM\redactQueryString('?view=login&postLoginQuery=view=console&auth='.$auth),
  '?view=login&postLoginQuery=view=console&auth='.ZM\logFingerprint($auth));
check('plain text with = is left alone', ZM\redactForLog(array('q' => 'a=b and c'))['q'], 'a=b and c');

$dump = print_r($out, true);
foreach (array('hunter2', $auth, 'eyJ', 'pw1', 'campw', 'onvifpw', 'sid:abc') as $secret) {
  check("no '$secret' anywhere in the dump", strpos($dump, $secret), false);
}

$session = array(
  'username' => 'admin',
  'password' => 'hunter2',
  'remoteAddr' => '192.0.2.10',
  'AuthHash192.0.2.10' => $auth,
  'AuthHash' => $auth,
  'AuthHashGeneratedAt' => 1700000000,
);
$out = ZM\redactForLog($session);
check('session password replaced', $out['password'], '[redacted]');
check('session auth hashes fingerprinted', array($out['AuthHash192.0.2.10'], $out['AuthHash']),
  array(ZM\logFingerprint($auth), ZM\logFingerprint($auth)));
check('session timestamps and addresses kept', array($out['AuthHashGeneratedAt'], $out['remoteAddr'], $out['username']),
  array(1700000000, '192.0.2.10', 'admin'));
check('objects are not dumped', ZM\redactForLog(array('u' => new ArrayObject()))['u'], '[ArrayObject]');

echo "\nno raw superglobal dumps left in web/\n";
$raw = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../web', FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
  $path = $file->getPathname();
  if (substr($path, -4) !== '.php' or preg_match('#/(vendor|Vendor|lib/Cake)/#', $path)) continue;
  foreach (file($path) as $n => $line) {
    if (preg_match('#^\s*(//|\#)#', $line)) continue;
    if (preg_match('/(Debug|Info|Warning|Error|Dump)\s*\(\s*print_r\(\s*\$_(REQUEST|SESSION|POST|GET|COOKIE)\s*,/', $line)) {
      $raw[] = substr($path, strlen(__DIR__.'/../../')).':'.($n + 1);
    }
  }
}
check('none found', $raw, array());

echo "\nmulti-server request URLs are logged through urlForLog()\n";
// Event.php, Server.php and Filter.php append ?auth=<hash> (or, with
// ZM_AUTH_RELAY=plain, the user's password) to the URL they call on another
// server, and logged that URL: at Error level, which reaches the Logs table by
// default, when the other server did not answer.
$raw = array();
foreach (array('Event.php', 'Server.php', 'Filter.php') as $name) {
  foreach (file(__DIR__.'/../../web/includes/'.$name) as $n => $line) {
    if (preg_match('/\b(Debug|Info|Warning|Error)\s*\(.*\$url\b/', $line) and strpos($line, 'urlForLog($url)') === false) {
      $raw[] = "$name:".($n + 1);
    }
  }
}
check('no raw $url in a log call', $raw, array());

echo "\nfailed remote requests do not put their URL in PHP warnings\n";
// file_get_contents($url) and fopen($url) raise a warning whose text starts
// with the call and its URL (?auth=, user:password@); PHP writes it to the web
// server's error log. The multi-server calls now use @ and log
// lastErrorForLog(), which keeps only the reason.
$r = @file_get_contents('http://admin:hunter2@127.0.0.1:1/zm/index.php?view=filter&auth='.$auth, false,
  stream_context_create(array('http' => array('timeout' => 2))));
check('the request failed', $r, false);
$reason = ZM\lastErrorForLog();
check('reason kept', (bool)preg_match('/^Failed to open stream/i', $reason), true);
check('no URL, password or auth hash in the reason',
  array(strpos($reason, 'hunter2'), strpos($reason, $auth), strpos($reason, '127.0.0.1')), array(false, false, false));
$raw = array();
foreach (array('includes/Event.php', 'includes/Filter.php', 'includes/Server.php', 'includes/functions.php', 'views/image.php') as $name) {
  foreach (file(__DIR__.'/../../web/'.$name) as $n => $line) {
    if (preg_match('#^\s*(//|\#)#', $line)) continue;
    if (preg_match('/(?<![@\w>:])(file_get_contents|get_headers)\(\s*\$(url|src)\b|(?<![@\w>:])fopen\(\s*\$(url|fetch_url)\b/', $line)) {
      $raw[] = "$name:".($n + 1).': '.trim($line);
    }
  }
}
check('remote URLs are fetched with @', $raw, array());

echo "\ncamera credentials are not logged\n";
// The monitor probe, ONVIF probe, CSV import, image proxy and monitor save
// logged camera passwords and URLs with user:password@ in them.
$raw = array();
$files = array(
  'includes/monitor_probe.php', 'includes/actions/monitor.php', 'ajax/add_monitors.php', 'views/image.php',
  'skins/classic/views/monitorprobe.php', 'skins/classic/views/onvifprobe.php',
  'skins/classic/views/_options_dnsmasq.php',
);
foreach ($files as $name) {
  foreach (file(__DIR__.'/../../web/'.$name) as $n => $line) {
    if (preg_match('#^\s*(//|\#)#', $line)) continue;
    if (!preg_match('/\b(Debug|Info|Warning|Error|Fatal)\s*\(/', $line)) continue;
    $bare = preg_replace('/\b(urlForLog|redactForLog)\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)/', '', $line);
    if (preg_match('/\$password\b|->Pass\(\)|\$(url|stream_uri)\b|\$stream\[.url.\]|\$monitor->Path\(\)|\$monitor\[.Path.\]|print_r\(\$(changes|probe|meta_data)\b|\$request\)|\$shell_command/', $bare)) {
      $raw[] = "$name:".($n + 1).': '.trim($line);
    }
  }
}
check('no camera password or credential-bearing URL in a log call', $raw, array());
// execONVIF() logs the failing zmonvif-probe command at Error level; for
// 'profiles' that command line carries the camera password.
$onvif = file_get_contents(__DIR__.'/../../web/skins/classic/views/onvifprobe.php');
check('the ONVIF probe does not log the command with the password',
  preg_match('/Error\((?:(?!\);).)*\$shell_command/s', $onvif), 0);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
