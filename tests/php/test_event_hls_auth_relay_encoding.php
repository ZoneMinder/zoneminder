<?php
// Tests the auth relay values web/skins/classic/views/event.php appends to
// the HLS source URL of an event.
//
// Under the plain relay the username and password went into the URL as typed,
// so & or # cut the query string, + became a space and %XX was decoded; the
// browser then requested the manifest without usable credentials. Both values
// are now URL-encoded, as get_auth_relay() does. The none relay's user= is
// encoded the same way for consistency, and the hashed relay is left alone.
//
// Run: php tests/php/test_event_hls_auth_relay_encoding.php [event.php path]
//      (from the repo root; defaults to the classic skin view)
//
// event.php needs a database and the full view context, so the block that
// builds the auth query is lifted out of the file and evaluated once per
// relay mode, each in its own namespace with its own ZM_AUTH_RELAY.

namespace EventHlsTestHashed {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'hashed';
  const ZM_AUTH_HASH_IPS = true;
  function generateAuthHash($useRemoteAddr) { return '0123456789abcdef0123456789abcdef'; }
}
namespace EventHlsTestPlain {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'plain';
}
namespace EventHlsTestNone {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'none';
}

namespace {
  $path = isset($argv[1])
    ? $argv[1]
    : __DIR__.'/../../web/skins/classic/views/event.php';
  $src = @file_get_contents($path);
  if ($src === false) {
    echo "FAIL cannot read $path\n";
    exit(1);
  }

  $src_pos = strpos($src, '$hlsSrc = $Server->PathToIndex()');
  $start = $src_pos === false ? false : strpos($src, 'if (ZM_OPT_USE_AUTH) {', $src_pos);
  if ($start === false) {
    echo "FAIL auth relay block not found in $path\n";
    exit(1);
  }
  $open = $start + strlen('if (ZM_OPT_USE_AUTH) ') ;
  if ($src[$open] != '{') {
    echo "FAIL auth relay block has no opening brace in $path\n";
    exit(1);
  }
  $depth = 0;
  $end = false;
  for ($i = $open; $i < strlen($src); $i++) {
    if ($src[$i] == '{') {
      $depth++;
    } else if ($src[$i] == '}') {
      $depth--;
      if ($depth == 0) {
        $end = $i;
        break;
      }
    }
  }
  if ($end === false) {
    echo "FAIL auth relay block has no matching close in $path\n";
    exit(1);
  }
  $block = substr($src, $start, $end - $start + 1);

  function hls_src($ns, $username, $password) {
    global $block;
    $_SESSION = array('username' => $username, 'password' => $password);
    $preamble = '$hlsSrc = \'x?view=view_hls&amp;eid=5\'; ';
    return eval("namespace $ns; ".$preamble.$block.' return $hlsSrc;');
  }

  // The query as index.php would see it: &amp; is HTML, and anything after a
  // # is a fragment that never reaches the server.
  function query_params($url) {
    parse_str(parse_url(str_replace('&amp;', '&', $url), PHP_URL_QUERY), $params);
    return $params;
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
  $url = hls_src('EventHlsTestPlain', 'admin', 'pw');
  check('plain, plain name url',
    $url, 'x?view=view_hls&amp;eid=5&amp;user=admin&amp;pass=pw');
  $url = hls_src('EventHlsTestNone', 'admin', 'pw');
  check('none, plain name url', $url, 'x?view=view_hls&amp;eid=5&amp;user=admin');
  $url = hls_src('EventHlsTestHashed', 'admin', 'pw');
  check('hashed url', $url,
    'x?view=view_hls&amp;eid=5&amp;auth=0123456789abcdef0123456789abcdef');

  $username = 'a&b#c';
  $password = 'p+w %41 &x=1';

  $url = hls_src('EventHlsTestPlain', $username, $password);
  $q = query_params($url);
  check('plain, awk user', $q['user'] ?? null, $username);
  check('plain, awk pass', $q['pass'] ?? null, $password);
  check('plain, no leaked x key', array_key_exists('x', $q), false);
  check('plain, no raw &x=1', strpos($url, '&x=1'), false);
  check('plain, view kept', $q['view'] ?? null, 'view_hls');
  check('plain, eid kept', $q['eid'] ?? null, '5');

  $url = hls_src('EventHlsTestNone', $username, $password);
  $q = query_params($url);
  check('none, awk user', $q['user'] ?? null, $username);
  check('none, no pass', array_key_exists('pass', $q), false);
  check('none, no leaked x key', array_key_exists('x', $q), false);
  check('none, no raw &x=1', strpos($url, '&x=1'), false);

  $url = hls_src('EventHlsTestHashed', $username, $password);
  $q = query_params($url);
  check('hashed, auth', $q['auth'] ?? null, '0123456789abcdef0123456789abcdef');
  check('hashed, no user', array_key_exists('user', $q), false);
  check('hashed, no raw &x=1', strpos($url, '&x=1'), false);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
