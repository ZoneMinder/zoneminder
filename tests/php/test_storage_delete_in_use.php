<?php
// Options > Storage delete must refuse a storage area that events or monitors
// still reference, instead of leaving their StorageId dangling.
//
// Run as: php tests/php/test_storage_delete_in_use.php

namespace ZM {
  $GLOBALS['warnings'] = array();
  function Warning($s) { $GLOBALS['warnings'][] = $s; }
  function AuditAction($a, $o, $id, $m) { $GLOBALS['audits'][] = $a.':'.$id; }

  class Storage {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public static function find_one($params) {
      $rows = $GLOBALS['storage'];
      return isset($rows[$params['Id']]) ? new Storage($rows[$params['Id']]) : null;
    }
    public function Id() { return $this->row['Id']; }
    public function Name() { return $this->row['Name']; }
    public function EventCount() { return $this->row['Events']; }
    public function MonitorCount() { return $this->row['Monitors']; }
  }
}

namespace {
  function canEdit($area) { return true; }
  function validCardinal($v) { return ctype_digit((string)$v) ? (int)$v : 0; }
  function validHtmlStr($s) { return htmlspecialchars($s); }
  function dbQuery($sql, $params = array()) { $GLOBALS['queries'][] = array($sql, $params); return true; }

  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
      echo "ok - $name\n";
    } else {
      $failures++;
      echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
    }
  }

  $GLOBALS['storage'] = array(
    2 => array('Id' => 2, 'Name' => 'Free', 'Events' => 0, 'Monitors' => 0),
    3 => array('Id' => 3, 'Name' => 'WithEvents', 'Events' => 5, 'Monitors' => 0),
    4 => array('Id' => 4, 'Name' => 'WithMonitor', 'Events' => 0, 'Monitors' => 1),
  );
  $GLOBALS['queries'] = array();
  $GLOBALS['audits'] = array();
  $error_message = '';
  $action = 'delete';
  $_REQUEST = array('object' => 'storage', 'markIds' => array('2', '3', '4', '99'));
  include __DIR__.'/../../web/includes/actions/options.php';

  $deleted = array_map(function($q) { return $q[1][0]; }, $GLOBALS['queries']);
  check('only the unused storage is deleted', $deleted, array(2));
  check('only the deleted storage is audited', $GLOBALS['audits'], array('delete:2'));
  check('refusal names the storage with events', strpos($error_message, 'WithEvents') !== false, true);
  check('refusal names the storage with monitors', strpos($error_message, 'WithMonitor') !== false, true);

  exit($failures ? 1 : 0);
}
