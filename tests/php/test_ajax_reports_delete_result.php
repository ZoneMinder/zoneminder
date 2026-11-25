<?php
// The reports delete branch used to call $report->delete() and ignore its result, so a
// failed DELETE was reported as success. deleteRequest() now reports the failure like
// snapshots.php does. The function is extracted from the ajax script (which cannot be
// included because of its top-level request handling) and run against a stub Report.
//
// Run as: php tests/php/test_ajax_reports_delete_result.php [root]   (root defaults to the repo)

namespace ZM {
  class Report {
    private $id;
    public function __construct($id) {
      $this->id = ((int)$id === 0) ? 0 : $id;
    }
    public function Id() { return $this->id; }
    public function canEdit() { return $GLOBALS['reportCanEdit']; }
    public function delete() { return $GLOBALS['reportDeleteResult']; }
  }
}

namespace {
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

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $path = $root.'/web/ajax/reports.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function deleteRequest\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find deleteRequest() in $path\n";
    exit(1);
  }
  eval($m[0]);

  $GLOBALS['reportCanEdit'] = true;
  $GLOBALS['reportDeleteResult'] = true;
  check('a delete that works reports nothing', deleteRequest(4), array());

  $GLOBALS['reportDeleteResult'] = false;
  $messages = deleteRequest(4);
  check('a failed delete is reported', count($messages) > 0, true);
  check('a failed delete mentions the report id', strpos(implode(' ', $messages), '4') !== false, true);

  $GLOBALS['reportDeleteResult'] = true;
  check('a missing report is reported', deleteRequest(0), array('Report not found.'));

  $GLOBALS['reportCanEdit'] = false;
  $messages = deleteRequest(7);
  check('a denied delete reports one message', count($messages), 1);
  check('a denied delete mentions permission', strpos($messages[0], 'You do not have permission') === 0, true);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
