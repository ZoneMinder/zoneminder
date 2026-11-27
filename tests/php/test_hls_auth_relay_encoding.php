<?php
// Tests the segment URLs web/views/view_hls.php writes into an event's HLS
// manifest, with the auth relay appended.
//
// Under the plain relay the username and password went into the URLs as
// typed, so & or # cut the query string, + became a space and %XX was
// decoded; segment requests then failed to log in. A $1 or \1 in the
// password was also read by preg_replace() as a back reference. Both values
// are now URL-encoded, as get_auth_relay() does. The none relay's user= is
// encoded the same way for consistency.
//
// Run: php tests/php/test_hls_auth_relay_encoding.php   (from the repo root)
//
// view_hls.php needs a database and exits after printing, so the block that
// builds the manifest is lifted out of the file and evaluated once per relay
// mode, each in its own namespace with its own ZM_AUTH_RELAY.

namespace HlsTestHashed {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'hashed';
  const ZM_AUTH_HASH_IPS = true;
  function generateAuthHash($useRemoteAddr) { return '0123456789abcdef0123456789abcdef'; }
}
namespace HlsTestPlain {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'plain';
}
namespace HlsTestNone {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'none';
}

namespace {
  $src = file_get_contents(__DIR__.'/../../web/views/view_hls.php');
  $start = strpos($src, '$auth_query = \'\';');
  $end = strpos($src, "header('Content-Type: application/vnd.apple.mpegurl');");
  if ($start === false or $end === false or $end < $start) {
    echo "FAIL manifest block not found in view_hls.php\n";
    exit(1);
  }
  $block = substr($src, $start, $end - $start);

  class FakeServer { public function PathToIndex() { return '/zm/index.php'; } }
  class FakeEvent { public function Server() { return new FakeServer(); } }

  $m3u8_path = tempnam(sys_get_temp_dir(), 'hls');
  file_put_contents($m3u8_path,
    "#EXTM3U\n".
    "#EXT-X-MAP:URI=\"index.php?view=view_video&eid=5&file=init.mp4\"\n".
    "#EXTINF:2.0,\n".
    "index.php?view=view_video&eid=5&file=seg1.m4s\n");

  function manifest($ns, $username, $password) {
    global $block, $m3u8_path;
    $_SESSION = array('username' => $username, 'password' => $password);
    $Event = new FakeEvent();
    return eval("namespace $ns; ".$block.' return $content;');
  }

  // The query of every URL in the manifest, as the receiving index.php sees
  // it: anything after a # is a fragment and never reaches the server.
  function segment_params($content) {
    preg_match_all('#/zm/index\.php\?[^"\n]+#', $content, $m);
    $out = array();
    foreach ($m[0] as $url) {
      parse_str(parse_url($url, PHP_URL_QUERY), $params);
      $out[] = $params;
    }
    return $out;
  }

  $failures = 0;
  $passes = 0;
  function check($name, $got, $expected) {
    global $failures, $passes;
    if ($got === $expected) {
      $passes++;
      echo "ok   $name\n";
    } else {
      $failures++;
      echo "FAIL $name: expected ".var_export($expected, true).', got '.var_export($got, true)."\n";
    }
  }

  // Ordinary names are unchanged on the wire.
  $content = manifest('HlsTestPlain', 'admin', 'pw');
  check('plain, plain name, segment line',
    strpos($content, "\n/zm/index.php?view=view_video&eid=5&file=seg1.m4s&user=admin&pass=pw\n") !== false, true);
  check('plain, plain name, map uri',
    strpos($content, 'URI="/zm/index.php?view=view_video&eid=5&file=init.mp4&user=admin&pass=pw"') !== false, true);
  $content = manifest('HlsTestHashed', 'admin', 'pw');
  check('hashed, segment line',
    strpos($content, "\n/zm/index.php?view=view_video&eid=5&file=seg1.m4s&auth=0123456789abcdef0123456789abcdef\n") !== false, true);

  $cases = array(
    'ampersand and equals' => array('ad&min', 'a&b=c'),
    'hash' => array('admin', 'pa#ss'),
    'plus, percent and space' => array('ad min', '1+1 100%'),
    'back reference' => array('admin', 'x$1y\\1z'),
  );
  foreach ($cases as $label => $creds) {
    list($u, $p) = $creds;
    $params = segment_params(manifest('HlsTestPlain', $u, $p));
    check("plain, $label, two urls", count($params), 2);
    foreach ($params as $i => $q) {
      check("plain, $label, url $i user", $q['user'] ?? null, $u);
      check("plain, $label, url $i pass", $q['pass'] ?? null, $p);
      check("plain, $label, url $i file kept", isset($q['file']), true);
    }
    $params = segment_params(manifest('HlsTestNone', $u, $p));
    foreach ($params as $i => $q) {
      check("none, $label, url $i user", $q['user'] ?? null, $u);
    }
  }

  unlink($m3u8_path);
  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
