<?php
// Tests get_auth_relay() in web/includes/auth.php: the query string the web UI
// appends to stream, AJAX and multi-server URLs so zms and the other servers
// know who is asking.
//
// The username went into it as typed. A username holding & or = split into
// more parameters: under the plain relay zms read "a" as the user and ignored
// the rest, so the user's streams did not load, and under none the stream ran
// as whichever user the first part named. Every value is now URL-encoded;
// zms (UriDecode) and PHP ($_REQUEST) decode them, so plain names are
// unchanged on the wire.
//
// Run: php tests/php/test_auth_relay_encoding.php
//
// Including auth.php needs a database, so the function is lifted out of the
// file, as test_auth_request_string.php does, and evaluated once per relay
// mode, each in its own namespace with its own ZM_AUTH_RELAY.

namespace RelayTestHashed\ZM { function Warning($s) {} function Error($s) {} }
namespace RelayTestHashed {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'hashed';
  const ZM_AUTH_HASH_IPS = true;
  function generateAuthHash($useRemoteAddr) { return '0123456789abcdef0123456789abcdef'; }
}
namespace RelayTestPlain\ZM { function Warning($s) {} function Error($s) {} }
namespace RelayTestPlain {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'plain';
}
namespace RelayTestNone\ZM { function Warning($s) {} function Error($s) {} }
namespace RelayTestNone {
  const ZM_OPT_USE_AUTH = true;
  const ZM_AUTH_RELAY = 'none';
}

namespace {
  $auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
  if ($auth_src === false) {
    echo "FAIL could not read auth.php\n";
    exit(1);
  }
  if (!preg_match('/^function get_auth_relay\(.*?^}/ms', $auth_src, $matches)) {
    echo "FAIL get_auth_relay() not found in auth.php\n";
    exit(1);
  }
  foreach (array('RelayTestHashed', 'RelayTestPlain', 'RelayTestNone') as $ns) {
    eval("namespace $ns; ".$matches[0]);
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

  // What a receiver sees: PHP's own query parser, which decodes like zms does.
  function relay_params($relay) {
    parse_str($relay, $out);
    return $out;
  }

  $hashed = 'RelayTestHashed\get_auth_relay';
  $plain = 'RelayTestPlain\get_auth_relay';
  $none = 'RelayTestNone\get_auth_relay';

  // Ordinary names are unchanged on the wire.
  $_SESSION = array('username' => 'admin', 'password' => 'pw');
  check('hashed, plain name', $hashed(), 'auth=0123456789abcdef0123456789abcdef&user=admin');
  check('plain, plain name', $plain(), 'username=admin&password=pw');
  check('none, plain name', $none(), 'username=admin');

  // A name with the query string's own delimiters comes back whole.
  $name = 'a&user=admin=x';
  $_SESSION = array('username' => $name, 'password' => 'p&w=1 +%');
  $p = relay_params($hashed());
  check('hashed: user is the session name', $p['user'], $name);
  check('hashed: auth is the hash', $p['auth'], '0123456789abcdef0123456789abcdef');
  check('hashed: no extra parameters', array_keys($p), array('auth', 'user'));

  $p = relay_params($plain());
  check('plain: username is the session name', $p['username'], $name);
  check('plain: password is the session password', $p['password'], 'p&w=1 +%');
  check('plain: no extra parameters', array_keys($p), array('username', 'password'));

  $p = relay_params($none());
  check('none: username is the session name', $p['username'], $name);
  check('none: no extra parameters', array_keys($p), array('username'));

  // A space, which urlencode() writes as + and both receivers read back.
  $_SESSION = array('username' => 'front door');
  check('none: a space round-trips', relay_params($none())['username'], 'front door');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
