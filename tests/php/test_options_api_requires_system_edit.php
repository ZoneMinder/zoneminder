<?php
// Security regression test (release-1.36): the API tab of Options renders for
// System=View, but its two POST handlers (updateSelected: set Users.APIEnabled
// for every user; revokeAllTokens: bump every Users.TokenMinExpiry) must only
// run for System=Edit.
//
// On 1.36 the tab is inline in the monolithic web/skins/classic/views/options.php
// (there is no _options_api.php), so the two handler functions and the dispatch
// that calls them are lifted from the source and run in a stand-in with a
// stubbed dbQuery(), for System=View and System=Edit. The per-user checkboxes
// and the two buttons are checked for the disabled attribute in the source.
//
// Run as: php tests/php/test_options_api_requires_system_edit.php

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
}

namespace {
  $GLOBALS['queries'] = array();
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = $sql; return true; }
  function translate($s) { return $s; }

  $src = file_get_contents(__DIR__.'/../../web/skins/classic/views/options.php');

  // Lift the two handler functions and the dispatch block that calls them.
  $fns = '';
  foreach (array('revokeAllTokens', 'updateSelected') as $fn) {
    if (!preg_match('/\n      function '.$fn.'\(\) \{.*?\n      \}/s', $src, $m)) {
      echo "FAIL - could not find function $fn in options.php\n";
      exit(1);
    }
    $fns .= $m[0]."\n";
  }
  eval($fns);

  if (!preg_match('/(\n      \/\/ Both actions change.*?\n      \})\n    \?>/s', $src, $m)) {
    echo "FAIL - could not find the guarded dispatch block in options.php\n";
    exit(1);
  }
  $dispatch = $m[1];

  $failures = 0;
  $passes = 0;
  function check($name, $got, $want) {
    global $failures, $passes;
    if ($got === $want) {
      $passes++;
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  // Run the dispatch block with a given $canEdit and $_POST, capturing the SQL
  // it sends and the warnings it logs.
  function run_dispatch($canEdit, array $post, $dispatch) {
    $GLOBALS['queries'] = array();
    $GLOBALS['warnings'] = array();
    $_POST = $post;
    $_REQUEST = $post;
    ob_start();
    eval($dispatch);
    ob_end_clean();
  }

  $writes = array(
    'updateSelected' => array('updateSelected' => '', 'apiUids' => array('1')),
    'revokeAllTokens' => array('revokeAllTokens' => ''),
  );

  foreach ($writes as $name => $post) {
    run_dispatch(false, $post, $dispatch);
    check("System=View $name sends no SQL", $GLOBALS['queries'], array());
    check("System=View $name logs a warning", count($GLOBALS['warnings']), 1);

    run_dispatch(true, $post, $dispatch);
    check("System=Edit $name updates Users", count($GLOBALS['queries']) > 0 &&
      strpos($GLOBALS['queries'][0], 'UPDATE `Users`') === 0, true);
  }

  run_dispatch(true, array('revokeAllTokens' => '', 'updateSelected' => ''), $dispatch);
  check('System=Edit with both buttons runs both handlers',
    count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'TokenMinExpiry') !== false; })) >= 1 &&
    count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'APIEnabled') !== false; })) >= 1, true);

  // The two buttons and the two per-user checkboxes carry the System-edit gate.
  check('Update and RevokeAllTokens buttons are gated on $canEdit',
    preg_match_all('/name="(updateSelected|revokeAllTokens)"[^\n]*\$canEdit \? \'\' : \' disabled="disabled"\'/', $src), 2);
  check('the per-user checkboxes are gated on $canEdit',
    preg_match_all('/name="(tokenUids|apiUids)\[\]"[^\n]*\$canEdit \? \'\' : \' disabled="disabled"\'/', $src), 2);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
