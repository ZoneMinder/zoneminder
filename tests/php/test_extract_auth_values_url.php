<?php
// extract_auth_values_from_url() must not read an @ in the path, query or
// fragment as the end of the credentials, but must still accept raw / ? #
// characters in a password (rtsp://admin:pa#ss@host/stream).
//
// Run: php tests/php/test_extract_auth_values_url.php
$src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
if ($src === false || !preg_match('/^function extract_auth_values_from_url\(.*?^}$/ms', $src, $m)) {
  echo "FAIL extract_auth_values_from_url() not found\n";
  exit(1);
}
eval($m[0]);

$failures = 0;
function check($name, $got, $expected) {
  global $failures;
  if ($got === $expected) {
    echo "PASS $name\n";
  } else {
    echo "FAIL $name: got ".json_encode($got)." expected ".json_encode($expected)."\n";
    $failures++;
  }
}

$cases = array(
  'rtsp://user:pass@host:554/live' => array('user', 'pass'),
  'rtsp://us%40er:p%3Ass@host/live' => array('us@er', 'p:ss'),
  'rtsp://host:554/live' => array(),
  'rtsp://h/x' => array(),
  'rtsp://h:554/live?token=a@b' => array(),
  'rtsp://h/p@x/y' => array(),
  'rtsp://h/#a@b' => array(),
  'rtsp://h:554#a@b' => array(),
  'rtsp://h:554/a:b@c' => array(),
  'http://h/a:b@c' => array(),
  'rtsp://[::1]/x?t=a@b' => array(),
  'rtsp://admin:pa#ss@10.0.0.1/stream' => array('admin', 'pa#ss'),
  'rtsp://admin:a/b@h/x' => array('admin', 'a/b'),
  'rtsp://admin:a?b@h/x' => array('admin', 'a?b'),
  'rtsp://admin:p@ss@h/x' => array('admin', 'p@ss'),
  'rtsp://admin:pass@h:554/live?token=a@b' => array('admin', 'pass'),
  'rtsp://admin@h/x' => array(),
  'http://u:p@h' => array('u', 'p'),
  'host/live' => array(),
  'rtsp://admin:pa+ss@10.0.0.5:554/s' => array('admin', 'pa+ss'),
  'rtsp://us+er:p%2Bss@h/x' => array('us+er', 'p+ss'),
  'rtsp://us%20er:p%20ss@h/x' => array('us er', 'p ss'),
);
foreach ($cases as $url => $expected)
  check($url, extract_auth_values_from_url($url), $expected);

exit($failures ? 1 : 0);
