<?php
// Tests what the API's error handler writes to cake_error.log:
//   ZmErrorHandler::redact()       - credential values in a URL or log text
//   ZmErrorHandler::_getMessage()  - the logged "Request URL:" must not carry
//                                    token=, auth=, pass= or password= values
//
// Cake's ErrorHandler is stubbed, so this runs from a source checkout:
// php tests/php/test_api_error_log_redaction.php

class App {
  public static function uses($class, $package) {}
}

// What Cake's ErrorHandler::_getMessage() builds: class, message, request URL.
class ErrorHandler {
  public static $url = '';
  protected static function _getMessage($exception) {
    return '['.get_class($exception).'] '.$exception->getMessage()
      ."\nRequest URL: ".static::$url."\nStack Trace:\n#0 {main}";
  }
}

require_once __DIR__.'/../../web/api/app/Lib/Error/ZmErrorHandler.php';

class TestErrorHandler extends ZmErrorHandler {
  public static function message($exception) { return static::_getMessage($exception); }
}

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

// ---- ZmErrorHandler::redact() ----

check('token value is redacted',
  ZmErrorHandler::redact('/zm/api/monitors.json?token=eyJ0.eyJp.sig'),
  '/zm/api/monitors.json?token=[redacted]');
check('user is kept, pass is redacted',
  ZmErrorHandler::redact('/zm/api/host/login.json?user=admin&pass=s3cret&x=1'),
  '/zm/api/host/login.json?user=admin&pass=[redacted]&x=1');
check('auth and password are redacted',
  ZmErrorHandler::redact('/a?auth=abc123&password=pw'),
  '/a?auth=[redacted]&password=[redacted]');
check('HTML-escaped separator is matched',
  ZmErrorHandler::redact('/a?p=%2Fa&amp;token=eyJ0.x.y'),
  '/a?p=%2Fa&amp;token=[redacted]');
check('names are matched whole, not as a suffix',
  ZmErrorHandler::redact('/a?mytoken=1&passive=2&bypass=3'),
  '/a?mytoken=1&passive=2&bypass=3');
check('names are matched whole, not as a prefix',
  ZmErrorHandler::redact('/a?authority=1&tokenize=2&password2=3&pass_hint=4&token_type=5&passwd=6'),
  '/a?authority=1&tokenize=2&password2=3&pass_hint=4&token_type=5&passwd=6');
check('a name sent as an array is redacted',
  ZmErrorHandler::redact('/a?token[]=eyJ0.x.y&pass%5B0%5D=s3cret&x=1'),
  '/a?token[]=[redacted]&pass%5B0%5D=[redacted]&x=1');
check('an empty value is left empty',
  ZmErrorHandler::redact('/a?token=&user=u'),
  '/a?token=&user=u');
check('text without credentials is unchanged',
  ZmErrorHandler::redact('/zm/api/monitors/view/1.json?p=%2Fmonitors'),
  '/zm/api/monitors/view/1.json?p=%2Fmonitors');

// ---- the logged message ----

ErrorHandler::$url = '/zm/api/monitors/view/9999.json?p=%2Fx&user=viewer&pass=s3cret&token=eyJ0.eyJp.sig';
$logged = TestErrorHandler::message(new Exception('Invalid monitor'));
check('logged message has no password', strpos($logged, 's3cret'), false);
check('logged message has no token', strpos($logged, 'eyJ0'), false);
check('logged message keeps the path and user',
  strpos($logged, 'Request URL: /zm/api/monitors/view/9999.json?p=%2Fx&user=viewer&pass=[redacted]') !== false, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
