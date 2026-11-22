<?php
// The Options > Servers and Options > Storage modals post to
// includes/actions/server.php and includes/actions/storage.php. Their save
// branches used to accept a blank Name (and a blank Storage Path, which
// Storage::Path() then replaces with the Name). This runs the real action
// files against stubbed models.
// Run: php tests/php/test_server_storage_require_name_path.php

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function AuditAction($a, $o, $id, $t) {}

  class Storage {
    public $Id = null;
    public $Name = null;
    public $Path = null;
    public static $rows = array();

    function __construct($IdOrRow = null) {
      if (is_array($IdOrRow)) {
        foreach ($IdOrRow as $k => $v) $this->{$k} = $v;
      } else if ($IdOrRow and isset(self::$rows[$IdOrRow])) {
        foreach (self::$rows[$IdOrRow] as $k => $v) $this->{$k} = $v;
      }
    }
    function Id() { return $this->Id; }
    function changes($new) {
      $changes = array();
      foreach ($new as $k => $v) {
        if (!property_exists($this, $k) or !is_string($v)) continue;
        $v = trim($v);
        if ($this->{$k} != $v) $changes[$k] = $v;
      }
      return $changes;
    }
    function save($changes) { $GLOBALS['storage_saves'][] = $changes; return true; }
    function get_last_error() { return 'save failed'; }
  }
}

namespace {
  $GLOBALS['db_queries'] = array();
  $GLOBALS['storage_saves'] = array();
  $GLOBALS['db_server_rows'] = array();

  function canEdit($a) { return true; }
  function validCardinal($v) { return (int)$v; }
  function validInt($v) { return (int)$v; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return $GLOBALS['db_server_rows'][$params[0] ?? 0] ?? null;
  }
  function dbQuery($sql, $params = array()) {
    $GLOBALS['db_queries'][] = trim($sql);
    return true;
  }
  function dbInsertId() { return 1; }
  function getFormChanges($values, $newValues, $types = false, $columns = false) {
    $changes = array();
    if (!is_array($newValues)) return $changes;
    // Same shape as the real function (functions.php): an absent or different
    // field becomes a change, and an empty value becomes `field` = NULL.
    foreach ($newValues as $k => $v) {
      if (!isset($values[$k]) or ($values[$k] != $v)) {
        $changes[$k] = $k.' = '.($v === '' ? 'NULL' : $v);
      }
    }
    return $changes;
  }

  $passed = 0; $failed = 0;
  function check($label, $got, $want) {
    global $passed, $failed;
    if ($got === $want) { $passed++; echo "  ok $label\n"; }
    else { $failed++; echo "  FAIL $label\n       got:  ".var_export($got, true)."\n       want: ".var_export($want, true)."\n"; }
  }

  function run_storage($request) {
    global $action, $error_message;
    $action = 'save'; $error_message = '';
    $GLOBALS['storage_saves'] = array();
    $_REQUEST = $request;
    include __DIR__.'/../../web/includes/actions/storage.php';
    return $error_message;
  }

  function run_server($request) {
    global $action, $error_message;
    $action = 'save'; $error_message = '';
    $GLOBALS['db_queries'] = array();
    $_REQUEST = $request;
    include __DIR__.'/../../web/includes/actions/server.php';
    return $error_message;
  }

  echo "storage\n";
  $err = run_storage(array('id' => '0', 'newStorage' => array('Name' => 'Test', 'Path' => '')));
  check('empty Path: not saved', $GLOBALS['storage_saves'], array());
  check('empty Path: error set', $err !== '', true);

  $err = run_storage(array('id' => '0', 'newStorage' => array('Name' => '   ', 'Path' => '/tmp/events')));
  check('blank Name: not saved', $GLOBALS['storage_saves'], array());
  check('blank Name: error set', $err !== '', true);

  $err = run_storage(array('id' => '0', 'newStorage' => array('Name' => 'Test', 'Path' => '/tmp/events')));
  check('valid Name and Path: saved', count($GLOBALS['storage_saves']), 1);
  check('valid Name and Path: no error', $err, '');

  echo "server\n";
  $err = run_server(array('id' => '0', 'newServer' => array('Name' => '')));
  check('empty Name: not saved', $GLOBALS['db_queries'], array());
  check('empty Name: error set', $err !== '', true);

  $err = run_server(array('id' => '0', 'newServer' => array('Name' => 'New Server')));
  check('valid Name: saved', count($GLOBALS['db_queries']), 1);
  check('valid Name: no error', $err, '');

  // An existing server may be saved without Name in the POST: the stored Name counts.
  $GLOBALS['db_server_rows'][1] = array('Id' => 1, 'Name' => 'Existing', 'Protocol' => 'http');
  $err = run_server(array('id' => '1', 'newServer' => array('Protocol' => 'https')));
  check('existing server, Name not posted: saved', count($GLOBALS['db_queries']), 1);
  check('existing server, Name not posted: no error', $err, '');

  echo "$passed passed, $failed failed\n";
  exit($failed ? 1 : 0);
}
