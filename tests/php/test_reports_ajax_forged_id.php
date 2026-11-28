<?php
// Security regression test: deleting a report must not accept a forged row.
//
// ZM_Object::__construct() treats an array as the object's row. The reports
// ajax handler passed each requested id straight to new ZM\Report($id), so
// ids[0][Id]=5&ids[0][CreatedBy]=1 built a Report row claiming CreatedBy=1
// (the current user). Report::canEdit() only compares CreatedBy with the user
// id, so the forged row passed the check and delete() removed report 5, which
// belongs to another user. deleteRequest() now reduces the id with
// validCardinal() first, so an array becomes '' and no row is built.
//
// Only the function deleteRequest() is taken from the handler; the rest of the
// file is top-level code that needs a database. ZM\Report and validCardinal()
// are stubbed here. validCardinal() is copied verbatim from
// web/includes/functions.php (requiring that file pulls in the database).
//
// Run as: php tests/php/test_reports_ajax_forged_id.php
//     or: ZM_REPORTS_AJAX=/path/to/reports.php php tests/php/test_reports_ajax_forged_id.php

namespace ZM {

// Same shape as ZM_Object/Report for the two cases that matter: an array is
// used as the row, a scalar id is loaded from the table.
class Report {
  protected static $table = array(
    5 => array('Id' => 5, 'CreatedBy' => 2),
    7 => array('Id' => 7, 'CreatedBy' => 1),
  );
  public static $deleted = array();

  protected $row = array();

  public function __construct($IdOrRow = null) {
    $row = null;
    if ($IdOrRow) {
      if (is_array($IdOrRow)) {
        $row = $IdOrRow;
      } else if (is_integer($IdOrRow) or ctype_digit((string)$IdOrRow)) {
        $id = (int)$IdOrRow;
        if (isset(self::$table[$id])) $row = self::$table[$id];
      }
    }
    if ($row) $this->row = $row;
  }

  public function Id() {
    return isset($this->row['Id']) ? $this->row['Id'] : null;
  }

  public function CreatedBy() {
    return isset($this->row['CreatedBy']) ? $this->row['CreatedBy'] : null;
  }

  // The only edit permission that matters here: the report is yours.
  public function canEdit() {
    return $this->CreatedBy() == 1;
  }

  public function delete() {
    self::$deleted[] = $this->Id();
    return true;
  }
}

} // namespace ZM

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

// Copied exactly from web/includes/functions.php.
function validCardinal($input) {
  if (!is_scalar($input)) return '';
  return preg_replace('/\D/', '', $input);
}

$path = getenv('ZM_REPORTS_AJAX');
if ($path === false or $path === '') {
  $path = __DIR__.'/../../web/ajax/reports.php';
}
if (!is_file($path)) {
  echo "FAIL - reports ajax file not found: $path\n";
  exit(1);
}
$source = file_get_contents($path);
if (!preg_match('/^function deleteRequest\(.*?^}/ms', $source, $m)) {
  echo "FAIL - could not find deleteRequest in $path\n";
  exit(1);
}
eval($m[0]);

// 1. A report the user owns is deleted.
\ZM\Report::$deleted = array();
$result = deleteRequest(7);
check('own report 7 is deleted', \ZM\Report::$deleted, array(7));
check('own report 7 has no message', $result, array());

// 2. Someone else's report is refused.
\ZM\Report::$deleted = array();
$result = deleteRequest(5);
check('foreign report 5 is not deleted', \ZM\Report::$deleted, array());
check('foreign report 5 is refused', count($result) > 0, true);

// 3. A forged row must not be treated as the report's real data.
\ZM\Report::$deleted = array();
$result = deleteRequest(array('Id' => 5, 'CreatedBy' => 1));
check('forged row does not delete report 5', \ZM\Report::$deleted, array());
check('forged row returns a message', count($result) > 0, true);

// 4. A non-numeric suffix is stripped, giving report 5, which is refused.
\ZM\Report::$deleted = array();
$result = deleteRequest('5abc');
check('"5abc" acts as id 5 and is not deleted', \ZM\Report::$deleted, array());
check('"5abc" is refused', count($result) > 0, true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);

}
