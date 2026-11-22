<?php
// The events delete action audit entry counted every requested id, including
// archived events that deleteEvent() refuses to delete.
//
// Run as: php tests/php/test_events_delete_audit_count.php

namespace ZM {
  class Event {
    public $id;
    function __construct($id) { $this->id = $id; }
    function Id() { return $this->id; }
    function Archived() { return $this->id == 2; }
    function canEdit() { return true; }
    function delete() { return true; }
  }
  function Error($s) {}
  function Warning($s) {}
  function Info($s) {}
  function AuditAction($a, $t, $i, $m) { $GLOBALS['audit'] = $m; }
}

namespace {
  $src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
  if (!preg_match('/^function deleteEvent\(.*?^}\n/ms', $src, $m)) { echo "FAIL - deleteEvent not found\n"; exit(1); }
  $fn = str_replace('ZM\\', '\\ZM\\', $m[0]);
  eval($fn);
  function canEdit($a) { return true; }
  function getAffectedIds($k) { return array(1, 2, 3); }

  $action = 'delete';
  $_REQUEST['eids'] = array(1, 2, 3);
  include __DIR__.'/../../web/includes/actions/events.php';
  if ($audit === 'Count: 2') {
    echo "ok - audit counts only deleted events\n";
    exit(0);
  }
  echo "FAIL - audit message was '$audit', want 'Count: 2'\n";
  exit(1);
}
