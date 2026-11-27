<?php
// Every delete() action in the REST API controllers must return a JSON response
// (via CakePHP's '_serialize') and not call ->flash(), so that API clients can
// distinguish successful deletions from failures.
//
// Pure source scan:
//   php tests/php/test_api_delete_json_response.php

$dir = __DIR__.'/../../web/api/app/Controller';

$passes = 0;
$failures = 0;

function extract_delete_body($content) {
  $tokens = token_get_all($content);
  $inDelete = false;
  $depth = 0;
  $body = '';
  $started = false;

  for ($i = 0; $i < count($tokens); $i++) {
    $t = $tokens[$i];
    if (!$inDelete) {
      if (is_array($t) && $t[0] === T_FUNCTION) {
        for ($j = $i + 1; $j < count($tokens); $j++) {
          if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) continue;
          if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && $tokens[$j][1] === 'delete') {
            $inDelete = true;
          }
          break;
        }
      }
    } else {
      $str = is_array($t) ? $t[1] : $t;
      if (!$started) {
        if ($str === '{') {
          $started = true;
          $depth = 1;
        }
      } else {
        if ($str === '{') {
          $depth++;
        } else if ($str === '}') {
          $depth--;
          if ($depth === 0) {
            return $body;
          }
        }
        $body .= $str;
      }
    }
  }
  return $body;
}

foreach (glob($dir.'/*Controller.php') as $path) {
  $name = basename($path);
  $content = file_get_contents($path);
  if (strpos($content, 'function delete(') === false) continue;

  $body = extract_delete_body($content);
  $hasFlash = (strpos($body, '->flash(') !== false);
  $hasSerialize = (strpos($body, "'_serialize'") !== false || strpos($body, '"_serialize"') !== false);

  if (!$hasFlash && $hasSerialize) {
    $passes++;
    echo "ok - $name: delete() has no ->flash() and contains '_serialize'\n";
  } else {
    $failures++;
    $reasons = array();
    if ($hasFlash) $reasons[] = 'contains ->flash()';
    if (!$hasSerialize) $reasons[] = "missing '_serialize'";
    echo "not ok - $name: delete() ".implode(', ', $reasons)."\n";
  }
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
