<?php
// Tests recaptchaAllowsLogin() in web/includes/auth.php: whether a password
// login that passed may go ahead, given Google's siteverify answer, when
// ZM_OPT_USE_GOOG_RECAPTCHA is on.
//
// The login was refused only when the answer held success == false together
// with an error-codes list. Anything else let it through: an empty object, a
// page that wasn't JSON, success:false without error-codes, success:"false".
// Now only success === true does, plus the existing allowance for a wrong
// secret key (invalid-input-secret), which keeps a mistyped key from locking
// everyone out.
//
// Run: php tests/php/test_recaptcha_allows_login.php
//
// Including auth.php needs a database, so the function is lifted out of the
// file, as test_auth_request_string.php does, and evaluated in a namespace
// with a stub logger.

namespace RecaptchaTest\ZM {
  $GLOBALS['errors'] = array();
  function Error($s) { $GLOBALS['errors'][] = $s; }
}

namespace {
  $auth_src = file_get_contents(__DIR__.'/../../web/includes/auth.php');
  if ($auth_src === false) {
    echo "FAIL could not read auth.php\n";
    exit(1);
  }
  if (!preg_match('/^function recaptchaAllowsLogin\(.*?^}/ms', $auth_src, $matches)) {
    echo "FAIL recaptchaAllowsLogin() not found in auth.php\n";
    exit(1);
  }
  eval('namespace RecaptchaTest; '.$matches[0]);

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

  // Each case is the body siteverify returned, as do_post_request() hands it over.
  function allows($body) {
    $GLOBALS['errors'] = array();
    return RecaptchaTest\recaptchaAllowsLogin(json_decode($body, true));
  }

  check('success true', allows('{"success": true, "hostname": "zm"}'), true);
  check('success false with a challenge error', allows('{"success": false, "error-codes": ["invalid-input-response"]}'), false);
  check('the refusal is logged', count($GLOBALS['errors']), 1);
  check('success false, timeout-or-duplicate', allows('{"success": false, "error-codes": ["timeout-or-duplicate"]}'), false);

  // A wrong secret key still lets the login through, with an Error to fix it.
  check('invalid secret key lets the login through', allows('{"success": false, "error-codes": ["invalid-input-secret"]}'), true);
  check('invalid secret key is logged', $GLOBALS['errors'], array('Invalid recaptcha secret detected'));

  // Answers that are not an explicit success.
  check('success false without error-codes', allows('{"success": false}'), false);
  check('empty object', allows('{}'), false);
  check('not JSON (an HTML page)', allows('<html><body>proxy</body></html>'), false);
  check('empty body', allows(''), false);
  check('success as the string "false"', allows('{"success": "false"}'), false);
  check('success as the string "true"', allows('{"success": "true"}'), false);
  check('success as 1', allows('{"success": 1}'), false);
  check('a JSON list', allows('[true]'), false);
  check('error-codes not a list', allows('{"success": false, "error-codes": "invalid-input-secret"}'), false);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
