<?php
// Security regression test: a redirect target taken from the request must stay on this server.
//
// The zone form posts back the Referer its page was loaded with, and actions/zone.php
// redirected to it after a save. A zone editor who reached the zone page from a link on
// another site was sent back to that site after saving (an open redirect).
// localRedirectUrl() accepts a query-only URL or an absolute http(s) URL on this host and
// port, and gives the fallback for anything else.
//
// functions.php can't be included without the rest of the web bootstrap, so the function is
// taken from the source.
//
// Run as: php tests/php/test_local_redirect_url.php

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

$source = file_get_contents(__DIR__.'/../../web/includes/functions.php');
if (!preg_match('/^function localRedirectUrl\(.*?^}\n/ms', $source, $m)) {
  echo "FAIL - could not find localRedirectUrl in functions.php\n";
  exit(1);
}
eval($m[0]);

$fallback = '?view=zones&mid=1';
$_SERVER['HTTP_HOST'] = 'zm.example.com';

check('same host absolute URL is kept',
  localRedirectUrl('http://zm.example.com/zm/index.php?view=zones&mid=1', $fallback),
  'http://zm.example.com/zm/index.php?view=zones&mid=1');
check('https same host is kept',
  localRedirectUrl('https://zm.example.com/zm/?view=montage', $fallback), 'https://zm.example.com/zm/?view=montage');
check('host match ignores case',
  localRedirectUrl('http://ZM.Example.com/zm/?view=zones', $fallback), 'http://ZM.Example.com/zm/?view=zones');
check('query-only URL is kept', localRedirectUrl('?view=watch&mid=1', $fallback), '?view=watch&mid=1');

check('other host gives the fallback', localRedirectUrl('https://evil.example/zm/', $fallback), $fallback);
check('suffix host gives the fallback',
  localRedirectUrl('http://zm.example.com.evil.example/', $fallback), $fallback);
check('protocol-relative URL gives the fallback', localRedirectUrl('//evil.example/', $fallback), $fallback);
check('backslash trick gives the fallback', localRedirectUrl('/\\evil.example/', $fallback), $fallback);
check('backslash after the scheme gives the fallback',
  localRedirectUrl('http:\\\\evil.example/', $fallback), $fallback);
check('userinfo host trick gives the fallback',
  localRedirectUrl('http://zm.example.com@evil.example/', $fallback), $fallback);
check('javascript URL gives the fallback', localRedirectUrl('javascript:alert(1)', $fallback), $fallback);
check('other scheme on this host gives the fallback',
  localRedirectUrl('ftp://zm.example.com/', $fallback), $fallback);
check('empty value gives the fallback', localRedirectUrl('', $fallback), $fallback);
check('missing value gives the fallback', localRedirectUrl(null, $fallback), $fallback);
check('array value gives the fallback', localRedirectUrl(array('x'), $fallback), $fallback);
check('header injection gives the fallback',
  localRedirectUrl("?view=zones\r\nSet-Cookie: x=1", $fallback), $fallback);

$_SERVER['HTTP_HOST'] = 'zm.example.com:8080';
check('same host and port is kept',
  localRedirectUrl('http://zm.example.com:8080/zm/?view=zones', $fallback), 'http://zm.example.com:8080/zm/?view=zones');
check('same host on another port gives the fallback',
  localRedirectUrl('http://zm.example.com:9090/zm/?view=zones', $fallback), $fallback);
check('same host without the port gives the fallback',
  localRedirectUrl('http://zm.example.com/zm/?view=zones', $fallback), $fallback);

unset($_SERVER['HTTP_HOST']);
check('without a Host header only query-only URLs are kept',
  localRedirectUrl('http://zm.example.com/zm/', $fallback), $fallback);
check('without a Host header a query-only URL is still kept',
  localRedirectUrl('?view=zones', $fallback), '?view=zones');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
