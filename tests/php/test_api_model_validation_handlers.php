<?php
// Every validation rule in the API models must name a handler Cake can find:
// a Validation method, a Model method or one defined on the model. Cake logs
// "Could not find validation handler" for any other name on every save, and
// the rule then validates nothing.
//
// Pure source scan:
//   php tests/php/test_api_model_validation_handlers.php

class App { public static function uses($class, $package) {} }

$api = __DIR__.'/../../web/api';
require_once $api.'/lib/Cake/Utility/Validation.php';
$modelSource = file_get_contents($api.'/lib/Cake/Model/Model.php');
$appModel = file_get_contents($api.'/app/Model/AppModel.php');

$passes = 0;
$failures = 0;

foreach (glob($api.'/app/Model/*.php') as $path) {
  $name = basename($path);
  $content = file_get_contents($path);
  foreach (explode("\n", $content) as $n => $line) {
    if (preg_match('/^\s*(\/\/|#|\*)/', $line)) continue;
    if (!preg_match("/'rule'\s*=>\s*(?:array\(\s*)?'([A-Za-z_]\w*)'/", $line, $m)) continue;
    $rule = $m[1];
    $pattern = '/function\s+'.$rule.'\s*\(/i';
    if (method_exists('Validation', $rule) or preg_match($pattern, $content)
      or preg_match($pattern, $appModel) or preg_match($pattern, $modelSource)) {
      $passes++;
      echo "ok - $name:".($n+1)." rule '$rule' has a handler\n";
    } else {
      $failures++;
      echo "not ok - $name:".($n+1)." rule '$rule' has no validation handler\n";
    }
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
