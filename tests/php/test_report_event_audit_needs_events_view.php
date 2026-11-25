<?php
// The event audit report view had no permission check, unlike report.php, so a user
// without Events view could open ?view=report_event_audit and see event ids and file audit.
//
// Run as: php tests/php/test_report_event_audit_needs_events_view.php [view file]

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $GLOBALS['perm'] = true;
  function canView($a) { return $GLOBALS['perm']; }
  function getNavBarHTML() {
    $GLOBALS['navCalled'] = true;
    // Halt before the rest of the view runs, which needs the full web environment.
    throw new \Exception('STOP');
  }

  // Runs the view and reports what it did.
  function run_view($path, $perm, &$view, &$navCalled) {
    global $view;
    $GLOBALS['perm'] = $perm;
    $GLOBALS['navCalled'] = false;
    $view = 'report_event_audit';
    ob_start();
    try {
      include $path;
    } catch (\Throwable $e) {
      if ($e->getMessage() !== 'STOP') {
        ob_end_clean();
        throw $e;
      }
    }
    $out = ob_get_clean();
    $navCalled = $GLOBALS['navCalled'];
    return $out;
  }

  $path = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/skins/classic/views/report_event_audit.php';
  $view = null;
  $navCalled = null;

  run_view($path, false, $view, $navCalled);
  check('view without Events permission shows the error page', $view, 'error');
  check('view without Events permission is refused before the navbar', $navCalled, false);

  run_view($path, true, $view, $navCalled);
  check('view with Events permission renders the view', $navCalled, true);
  check('view with Events permission keeps the view', $view === 'error', false);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
