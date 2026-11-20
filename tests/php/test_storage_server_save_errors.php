<?php
/*
 * The Options > Storage and Options > Servers modals post to
 * includes/actions/storage.php and includes/actions/server.php. Their save
 * branches used to always set $redirect, and web/index.php sends the Location
 * header and returns before the options page renders $error_message, so a
 * refused or failed save was invisible and the row simply did not change.
 * server.php also ignored the result of its INSERT/UPDATE, and audited the
 * request id (0 for a new server) instead of the inserted id.
 *
 * Runs the real action files against stubbed models.
 *
 * Run: php tests/php/test_storage_server_save_errors.php [zm-root]
 */

namespace ZM {
  function Warning($m) {}
  function Error($m) {}
  function Debug($m) {}
  function AuditAction($action, $object, $id, $text) {
    $GLOBALS['audit_actions'][] = array(
      'action' => $action, 'object' => $object, 'id' => $id, 'text' => $text,
    );
  }

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
    function save($changes) {
      $GLOBALS['storage_saves'][] = $changes;
      return $GLOBALS['storage_save_ok'];
    }
    function get_last_error() { return 'storage save failed'; }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  $GLOBALS['audit_actions'] = array();
  $GLOBALS['db_queries'] = array();
  $GLOBALS['storage_saves'] = array();
  $GLOBALS['db_server_rows'] = array();
  $GLOBALS['storage_save_ok'] = true;
  $GLOBALS['db_ok'] = true;

  function canEdit($area) { return true; }
  function validCardinal($v) { return (int)$v; }
  function validInt($v) { return (int)$v; }
  function dbFetchOne($sql, $col = null, $params = array()) {
    return $GLOBALS['db_server_rows'][$params[0] ?? 0] ?? null;
  }
  function dbQuery($sql, $params = array()) {
    $GLOBALS['db_queries'][] = trim($sql);
    return $GLOBALS['db_ok'] ? true : null;
  }
  function dbInsertId() { return 42; }
  function dbLastError() { return "Duplicate entry '<b>web1</b>' for key 'Name'"; }
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

  $tests = 0; $failures = 0;
  function check($label, $got, $want) {
    global $tests, $failures;
    $tests++;
    if ($got === $want) {
      echo "  ok   - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n       got:  ".var_export($got, true)."\n       want: ".var_export($want, true)."\n";
    }
  }

  // The action files are included from index.php at global scope, so $redirect
  // and $view are globals there. Including them from a function keeps those
  // two local, which lets each run start from a clean state; $error_message is
  // declared global by the action files either way.
  function run_action($file, $request) {
    global $action, $error_message;
    $action = 'save';
    $error_message = '';
    $redirect = null;
    $view = isset($request['view']) ? $request['view'] : null;
    $refreshParent = null;
    $_REQUEST = $request;
    $GLOBALS['audit_actions'] = array();
    $GLOBALS['db_queries'] = array();
    $GLOBALS['storage_saves'] = array();

    $problems = array();
    set_error_handler(function ($no, $str) use (&$problems) { $problems[] = $str; return true; });
    try {
      include $GLOBALS['root'].'/web/includes/actions/'.$file;
    } catch (\Throwable $e) {
      $problems[] = get_class($e).': '.$e->getMessage();
    }
    restore_error_handler();

    return array(
      'error_message' => $GLOBALS['error_message'],
      'redirect' => $redirect,
      'view' => $view,
      'tab' => $_REQUEST['tab'] ?? null,
      'refreshParent' => $refreshParent,
      'audit' => $GLOBALS['audit_actions'],
      'queries' => $GLOBALS['db_queries'],
      'storage_saves' => $GLOBALS['storage_saves'],
      'problems' => $problems,
    );
  }

  echo "storage\n";

  $s = run_action('storage.php', array(
    'view' => 'storage', 'action' => 'save', 'id' => '0',
    'newStorage' => array('Name' => '   ', 'Path' => '/tmp/events'),
  ));
  check('blank Name: error set', $s['error_message'] !== '', true);
  check('blank Name: nothing saved', $s['storage_saves'], array());
  check('blank Name: redirect stays unset', $s['redirect'], null);
  check('blank Name: renders the storage tab', $s['view'], 'options');
  check('blank Name: tab is storage', $s['tab'], 'storage');
  check('blank Name: raises no warnings', $s['problems'], array());

  $s = run_action('storage.php', array(
    'view' => 'storage', 'action' => 'save', 'id' => '0',
    'newStorage' => array('Name' => 'Test', 'Path' => '/tmp/events'),
  ));
  check('valid save: no error', $s['error_message'], '');
  check('valid save: redirect set', $s['redirect'], '?view=options&tab=storage');
  check('valid save: one save', count($s['storage_saves']), 1);
  check('valid save: raises no warnings', $s['problems'], array());

  $GLOBALS['storage_save_ok'] = false;
  $s = run_action('storage.php', array(
    'view' => 'storage', 'action' => 'save', 'id' => '1',
    'newStorage' => array('Name' => 'Test', 'Path' => '/tmp/events'),
  ));
  check('failed save: error set', $s['error_message'] !== '', true);
  check('failed save: redirect stays unset', $s['redirect'], null);
  $GLOBALS['storage_save_ok'] = true;

  echo "server\n";

  $GLOBALS['db_ok'] = false;
  $s = run_action('server.php', array(
    'view' => 'server', 'action' => 'save', 'id' => '0',
    'newServer' => array('Name' => 'web1'),
  ));
  check('db failure: error set', $s['error_message'] !== '', true);
  check('db failure: says Error saving server', strpos($s['error_message'], 'Error saving server') !== false, true);
  check('db failure: escapes the db error', strpos($s['error_message'], '<b>') === false, true);
  check('db failure: redirect stays unset', $s['redirect'], null);
  check('db failure: no audit', $s['audit'], array());
  check('db failure: refreshParent not set', $s['refreshParent'], null);
  check('db failure: renders the servers tab', $s['view'], 'options');
  check('db failure: tab is servers', $s['tab'], 'servers');
  check('db failure: raises no warnings', $s['problems'], array());

  $GLOBALS['db_ok'] = true;
  $s = run_action('server.php', array(
    'view' => 'server', 'action' => 'save', 'id' => '0',
    'newServer' => array('Name' => 'web1'),
  ));
  check('new server: no error', $s['error_message'], '');
  check('new server: redirect set', $s['redirect'], '?view=options&tab=servers');
  check('new server: one audit', count($s['audit']), 1);
  check('new server: audit action', $s['audit'][0]['action'] ?? null, 'create');
  check('new server: audit object', $s['audit'][0]['object'] ?? null, 'server');
  check('new server: audited the inserted id', $s['audit'][0]['id'] ?? null, dbInsertId());
  check('new server: refreshParent is true', $s['refreshParent'], true);
  check('new server: raises no warnings', $s['problems'], array());

  $GLOBALS['db_server_rows'][1] = array('Id' => 1, 'Name' => 'Existing', 'Protocol' => 'http');
  $s = run_action('server.php', array(
    'view' => 'server', 'action' => 'save', 'id' => '1',
    'newServer' => array('Protocol' => 'https'),
  ));
  check('update server: no error', $s['error_message'], '');
  check('update server: audited the request id', $s['audit'][0]['id'] ?? null, '1');
  check('update server: audit action', $s['audit'][0]['action'] ?? null, 'update');

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
