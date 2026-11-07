<?php
// Security regression test: the API tab of Options renders for System=View, but
// its two POST handlers (updateSelected: set Users.APIEnabled for every user;
// revokeAllTokens: bump every Users.TokenMinExpiry) must only run for System=Edit.
//
// Includes the real web/skins/classic/views/_options_api.php with the web
// helpers it calls stubbed, and records the SQL it sends to dbQuery().
//
// Run as: php tests/php/test_options_api_requires_system_edit.php

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  class User {
    public static function find($a = array(), $b = array()) { return array(new User()); }
    public function Id() { return 1; }
    public function Username() { return 'admin'; }
    public function APIEnabled() { return 1; }
  }
}

namespace {
  define('ZM_OPT_USE_API', '1');

  $GLOBALS['perms'] = array();
  $GLOBALS['queries'] = array();
  function canView($area) { return in_array($GLOBALS['perms'][$area] ?? 'None', array('View', 'Edit'), true); }
  function canEdit($area) { return ($GLOBALS['perms'][$area] ?? 'None') === 'Edit'; }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = $sql; return true; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s); }

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

  // The view declares its handler functions, so it can only be included once per
  // process: each case runs in a child php process that prints what it saw.
  if (isset($argv[1])) {
    $case = json_decode($argv[1], true);
    $GLOBALS['perms'] = array('System' => $case['perm']);
    $_POST = $case['post'];
    $_REQUEST = $case['post'];
    $view = 'options';
    $tab = 'API';
    ob_start();
    include __DIR__.'/../../web/skins/classic/views/_options_api.php';
    $html = ob_get_clean();
    echo json_encode(array('queries' => $GLOBALS['queries'], 'warnings' => $GLOBALS['warnings'], 'html' => $html));
    exit(0);
  }

  function render_api_tab($systemPerm, array $post) {
    $arg = json_encode(array('perm' => $systemPerm, 'post' => (object)$post));
    $out = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($arg));
    $r = json_decode($out, true);
    if (!is_array($r)) {
      echo "child failed: $out\n";
      exit(1);
    }
    $GLOBALS['queries'] = $r['queries'];
    $GLOBALS['warnings'] = $r['warnings'];
    return $r['html'];
  }

  $writes = array(
    'updateSelected' => array('updateSelected' => '', 'apiUids' => array('1')),
    'revokeAllTokens' => array('revokeAllTokens' => ''),
  );

  foreach ($writes as $name => $post) {
    $html = render_api_tab('View', $post);
    check("System=View $name sends no SQL", $GLOBALS['queries'], array());
    check("System=View $name logs a warning", count($GLOBALS['warnings']), 1);
    check("System=View $name page still renders", strpos($html, 'contentTable') !== false, true);

    render_api_tab('Edit', $post);
    check("System=Edit $name updates Users", count($GLOBALS['queries']) > 0 &&
      strpos($GLOBALS['queries'][0], 'UPDATE `Users`') === 0, true);
  }

  $html = render_api_tab('View', array());
  check('System=View sees the write buttons disabled',
    preg_match_all('/id="(updateSelected|revokeAllTokens)" disabled="disabled"/', $html), 2);
  check('System=View sees the per-user checkboxes disabled',
    preg_match_all('/name="(tokenUids|apiUids)\[\]"[^>]*disabled="disabled"/', $html), 2);
  $html = render_api_tab('Edit', array());
  check('System=Edit sees the write buttons enabled',
    substr_count($html, 'disabled="disabled"'), 0);

  render_api_tab('Edit', array('revokeAllTokens' => '', 'updateSelected' => ''));
  check('System=Edit with both buttons runs both handlers',
    count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'TokenMinExpiry') !== false; })) >= 1 &&
    count(array_filter($GLOBALS['queries'], function($q) { return strpos($q, 'APIEnabled') !== false; })) >= 1, true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
