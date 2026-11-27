<?php
// Security regression test: ajax/snapshots.php delete must not build a Snapshot from a
// request array.
//
// ZM\Snapshot::__construct() (like ZM_Object) treats an array as the object's row, so
// deleteRequest(array('Id' => 5, 'CreatedBy' => 1)) built a snapshot claiming to be
// snapshot 5 and deleted it. deleteRequest() must reduce $id to a scalar first, which
// validCardinal() does by returning '' for anything that is not a single value.
//
// Run as: php tests/php/test_snapshots_ajax_forged_id.php

namespace ZM {
  class Snapshot {
    public static $deleted = array();
    private $row = null;

    public function __construct($id_or_row) {
      static $table = array(5 => array('Id' => 5));
      if (is_array($id_or_row)) {
        // ZM_Object treats an array as the row.
        $this->row = $id_or_row;
      } else if (is_scalar($id_or_row) and isset($table[(int)$id_or_row])) {
        $this->row = $table[(int)$id_or_row];
      }
    }

    public function Id() {
      return $this->row ? $this->row['Id'] : null;
    }

    public function delete() {
      self::$deleted[] = $this->Id();
      return true;
    }
  }
}

namespace {
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

  // Copied verbatim from web/includes/functions.php.
  function validCardinal($input) {
    if (!is_scalar($input)) return '';
    return preg_replace('/\D/', '', $input);
  }

  $path = getenv('ZM_SNAPSHOTS_AJAX');
  if (!$path) $path = __DIR__.'/../../web/ajax/snapshots.php';
  $source = file_get_contents($path);
  if (!preg_match('/^function deleteRequest\(.*?^}/ms', $source, $m)) {
    echo "FAIL - could not find deleteRequest in $path\n";
    exit(1);
  }
  eval($m[0]);

  // 1. A scalar id deletes the snapshot.
  ZM\Snapshot::$deleted = array();
  $messages = deleteRequest('5');
  check('deleteRequest(\'5\') deletes snapshot 5', ZM\Snapshot::$deleted, array(5));
  check('deleteRequest(\'5\') reports no problem', $messages, array());

  // 2. A forged row from the request deletes nothing.
  ZM\Snapshot::$deleted = array();
  $messages = deleteRequest(array('Id' => 5, 'CreatedBy' => 1));
  check('a forged row deletes nothing', ZM\Snapshot::$deleted, array());
  check('a forged row is reported', count($messages) > 0, true);

  // 3. A row carrying a string id deletes nothing.
  ZM\Snapshot::$deleted = array();
  $messages = deleteRequest(array('Id' => '9'));
  check('a row with a string id deletes nothing', ZM\Snapshot::$deleted, array());
  check('a row with a string id is reported', count($messages) > 0, true);

  // 4. A scalar with trailing junk is reduced to its digits.
  ZM\Snapshot::$deleted = array();
  $messages = deleteRequest('5abc');
  check('\'5abc\' is reduced to 5 and deletes 5', ZM\Snapshot::$deleted, array(5));

  // 5. An id that is not in the table is reported.
  ZM\Snapshot::$deleted = array();
  $messages = deleteRequest('99');
  check('an unknown id deletes nothing', ZM\Snapshot::$deleted, array());
  check('an unknown id is reported', count($messages) > 0, true);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
