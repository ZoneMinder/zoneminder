<?php
// The Events, Frames and Snapshots lists build their table search as one LIKE term per
// column. FilterTerm splits a non-regex value on commas into alternatives, so a search
// for "front door, back" became LIKE '%front door' OR LIKE 'back%' and missed the row
// that holds the text. Each list now turns a comma into the single-character wildcard.
//
// php tests/php/test_ajax_list_search_comma.php [root]   (root defaults to the repo)

namespace ZM {
  function Debug($m) {}
  function Error($m) {}
  function Warning($m) {}
  function translate($s) { return $s; }
}

namespace {
  $root = isset($argv[1]) ? $argv[1] : dirname(__DIR__, 2);
  require_once $root.'/web/includes/FilterTerm.php';
  if (!function_exists('dbEscape')) { function dbEscape($s) { return "'".addslashes($s)."'"; } }

  $failures = 0;
  foreach (array('events', 'frames', 'snapshots') as $list) {
    $src = file_get_contents($root.'/web/ajax/'.$list.'.php');
    // The statement that wraps the search text in % for the LIKE terms.
    if (!preg_match('/\n\s*(\$search = \'%\' \..*?\. \'%\';)/', $src, $m)) {
      $failures++;
      echo "FAIL - $list: search statement not found\n";
      continue;
    }
    $search = 'front door, back';
    eval($m[1]);
    $term = new ZM\FilterTerm(null, array('attr' => 'Name', 'op' => 'LIKE', 'val' => $search), 0);
    $n = count($term->sql_values());
    if ($n == 1) {
      echo "ok - $list: a search with a comma is one LIKE value\n";
    } else {
      $failures++;
      echo "FAIL - $list: a search with a comma became $n LIKE values\n";
    }
  }
  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
