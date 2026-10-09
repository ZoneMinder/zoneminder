<?php
// Tests what the API returns in error bodies:
//   ZmExceptionRenderer::_getErrorData() - with debug 0, a 5xx body must not
//                                          carry the exception's own message
//                                          (PDO text, SQL), and data.url must
//                                          not carry credentials
//
// The Cake and Crud base classes are stubbed, so this runs from a source
// checkout: php tests/php/test_api_error_body.php

class App {
  public static function uses($class, $package) {}
}

class Configure {
  public static $values = array('debug' => 0);
  public static function read($key) {
    return isset(self::$values[$key]) ? self::$values[$key] : null;
  }
}

function __d($domain, $msg) { return $msg; }

// ZmErrorHandler::redact() cleans data.url; its base class is not exercised.
class ErrorHandler {}

// What Crud's renderer returns for a 500 with debug 0: Cake's generic message,
// the HTML-escaped URL, and the raw exception.
class CrudExceptionRenderer {
  public $controller;
  public $data = array();
  protected function _getErrorData() {
    return $this->data;
  }
}

class StubResponse {
  public $code;
  public function statusCode() { return $this->code; }
}

require_once __DIR__.'/../../web/api/app/Lib/Error/ZmErrorHandler.php';
require_once __DIR__.'/../../web/api/app/Lib/Error/ZmExceptionRenderer.php';

class TestRenderer extends ZmExceptionRenderer {
  public function __construct($code, $data) {
    $this->controller = new stdClass();
    $this->controller->response = new StubResponse();
    $this->controller->response->code = $code;
    $this->data = $data;
  }
  public function errorData() { return $this->_getErrorData(); }
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

// ---- the error body ----

$pdo = array(
  'name' => 'An Internal Error Has Occurred.',
  'message' => 'An Internal Error Has Occurred.',
  'url' => '/zm/api/monitors/index/Nope:1.json?p=%2Fx&amp;token=eyJ0.eyJp.sig',
  'exception' => array(
    'class' => 'PDOException',
    'code' => '42S22',
    'message' => "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'Nope' in 'WHERE'",
  ),
);
$data = (new TestRenderer(500, $pdo))->errorData();
check('500 with debug 0: exception message is generic',
  $data['exception']['message'], 'An Internal Error Has Occurred.');
check('500 keeps the exception class and code',
  array($data['exception']['class'], $data['exception']['code']), array('PDOException', '42S22'));
check('500 body url has no token',
  $data['url'], '/zm/api/monitors/index/Nope:1.json?p=%2Fx&amp;token=[redacted]');

$notFound = array(
  'url' => '/zm/api/monitors/view/9.json',
  'exception' => array('class' => 'NotFoundException', 'code' => 404, 'message' => 'Invalid monitor'),
);
$data = (new TestRenderer(404, $notFound))->errorData();
check('4xx keeps its message', $data['exception']['message'], 'Invalid monitor');

Configure::$values['debug'] = 2;
$data = (new TestRenderer(500, $pdo))->errorData();
check('500 with debug on keeps the message', $data['exception']['message'], $pdo['exception']['message']);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
