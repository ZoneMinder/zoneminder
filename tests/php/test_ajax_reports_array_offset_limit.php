<?php
// The reports list reads the table's offset and limit from the query string.
// A request that passes offset[]=x or limit[]=y (an array) must be rejected with a ZM\Error
// and leave the values at 0, without PHP emitting a deprecation or warning while comparing
// the array against the 'NaN' string. Plain scalar digits must still be accepted, and
// non-numeric offset with a negative limit must be rejected.
//
// php tests/php/test_ajax_reports_array_offset_limit.php [root]   (root defaults to the repo)

namespace ZM {
  function Error($m) { $GLOBALS['errors'][] = $m; }
}

namespace {
  set_error_handler(function($errno, $errstr) {
    $GLOBALS['php_errors'][] = $errstr;
    return true;
  });

  // Pull from "$offset = 0;" up to the closing brace of the if ( isset($_REQUEST['limit']) ...)
  // block, so both validations are exercised together.
  function extract_validation($src) {
    $offset_pos = strpos($src, '$offset = 0;');
    if ($offset_pos === false) return null;
    $limit_pos = strpos($src, '$limit = 0;', $offset_pos);
    if ($limit_pos === false) return null;
    $isset_pos = strpos($src, "isset(\$_REQUEST['limit'])", $limit_pos);
    if ($isset_pos === false) return null;
    $open = strpos($src, '{', $isset_pos);
    if ($open === false) return null;
    $depth = 0;
    $end = null;
    for ($i = $open; $i < strlen($src); $i++) {
      if ($src[$i] === '{') {
        $depth++;
      } elseif ($src[$i] === '}') {
        $depth--;
        if ($depth == 0) { $end = $i; break; }
      }
    }
    if ($end === null) return null;
    return substr($src, $offset_pos, $end - $offset_pos + 1);
  }

  // Eval in function scope so the block's $offset/$limit stay local.
  function run_validation($code) {
    eval($code);
    return array($offset, $limit);
  }

  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  $failures = 0;

  foreach (array('reports') as $name) {
    $src = file_get_contents($root.'/web/ajax/'.$name.'.php');
    $code = ($src === false) ? null : extract_validation($src);
    if ($code === null) {
      $failures++;
      echo "FAIL - $name: offset/limit validation block not found\n";
      continue;
    }

    // Case 1: arrays are rejected, values stay 0, no PHP warnings.
    $GLOBALS['errors'] = array();
    $GLOBALS['php_errors'] = array();
    $_REQUEST = array('offset' => array('x'), 'limit' => array('y'));
    list($offset, $limit) = run_validation($code);
    $problems = array();
    if (!empty($GLOBALS['php_errors'])) {
      $problems[] = 'php errors: ' . implode('; ', $GLOBALS['php_errors']);
    }
    if ($offset != 0) $problems[] = "\$offset is ".var_export($offset, true);
    if ($limit != 0) $problems[] = "\$limit is ".var_export($limit, true);
    if (count($GLOBALS['errors']) != 2) {
      $problems[] = count($GLOBALS['errors']).' ZM\\Error messages';
    }
    foreach ($GLOBALS['errors'] as $m) {
      if (strpos($m, 'Array') !== false) $problems[] = "message mentions Array: $m";
    }
    if ($problems) {
      $failures++;
      echo "FAIL - $name: array offset and limit rejected - ".implode(', ', $problems)."\n";
    } else {
      echo "ok - $name: array offset and limit rejected\n";
    }

    // Case 2: scalar digits are accepted without any errors.
    $GLOBALS['errors'] = array();
    $GLOBALS['php_errors'] = array();
    $_REQUEST = array('offset' => '5', 'limit' => '20');
    list($offset, $limit) = run_validation($code);
    $problems = array();
    if ($offset != 5) $problems[] = "\$offset is ".var_export($offset, true);
    if ($limit != 20) $problems[] = "\$limit is ".var_export($limit, true);
    if (!empty($GLOBALS['errors'])) {
      $problems[] = count($GLOBALS['errors']).' ZM\\Error messages';
    }
    if (!empty($GLOBALS['php_errors'])) {
      $problems[] = 'php errors: ' . implode('; ', $GLOBALS['php_errors']);
    }
    if ($problems) {
      $failures++;
      echo "FAIL - $name: scalar offset and limit accepted - ".implode(', ', $problems)."\n";
    } else {
      echo "ok - $name: scalar offset and limit accepted\n";
    }

    // Case 3: non-numeric offset and negative limit are rejected.
    $GLOBALS['errors'] = array();
    $GLOBALS['php_errors'] = array();
    $_REQUEST = array('offset' => 'abc', 'limit' => '-1');
    list($offset, $limit) = run_validation($code);
    $problems = array();
    if ($offset != 0) $problems[] = "\$offset is ".var_export($offset, true);
    if ($limit != 0) $problems[] = "\$limit is ".var_export($limit, true);
    if (count($GLOBALS['errors']) != 2) {
      $problems[] = count($GLOBALS['errors']).' ZM\\Error messages';
    }
    if ($problems) {
      $failures++;
      echo "FAIL - $name: non-numeric offset and negative limit rejected - ".implode(', ', $problems)."\n";
    } else {
      echo "ok - $name: non-numeric offset and negative limit rejected\n";
    }
  }

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
