<?php
// Tests EncoderTemplates::paramsTextToObject(), which turns a stored template's
// Params text into the {key: value} map applied to a monitor.
//
// A # starts a comment that runs to the end of the line, as in zm_parse_options,
// so a commented-out option is not applied.
//
// Run as: php tests/php/test_encoder_template_params.php   (from the repo root)

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

require_once __DIR__ . '/../../web/includes/EncoderTemplates.php';

$parse = new ReflectionMethod('ZM\EncoderTemplates', 'paramsTextToObject');
$parse->setAccessible(true);

check('newline and comma separate pairs',
  $parse->invoke(null, "preset=fast\ncrf=23,g=30"),
  ['preset' => 'fast', 'crf' => '23', 'g' => '30']);
check('commented-out option is not applied',
  $parse->invoke(null, "preset=fast\n#crf=18\ng=30"),
  ['preset' => 'fast', 'g' => '30']);
check('comment line with = in it is dropped whole',
  $parse->invoke(null, "# set crf=23 here, or g=5\npreset=fast"),
  ['preset' => 'fast']);
check('comment after a value runs to the end of the line',
  $parse->invoke(null, "crf=30 # balanced, g=5\npreset=fast"),
  ['crf' => '30', 'preset' => 'fast']);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
