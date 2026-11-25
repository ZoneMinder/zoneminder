<?php
// The snapshot view showed one thumbnail per event but the thumbnails did not link
// anywhere, so there was no way to open an event from its snapshot. Each thumbnail
// of an event the user may view is now wrapped in a link to that event; events the
// user may not view keep the plain image.
//
// Includes the real view with a stubbed ZM\Snapshot, ZM\Event and web helpers.
//
// Run as: php tests/php/test_snapshot_event_links.php [web root]

namespace ZM {
  class Snapshot {
    function __construct($id = null) {}
    function Id() { return 5; }
    function CreatedOn() { return '2026-01-01 00:00:00'; }
    function Name() { return 'reference'; }
    function Description() { return 'notes'; }
    function CreatedBy() { return 9; }
    function Events() { return $GLOBALS['snapshot_events']; }
  }

  class Event {
    private $id;
    private $viewable;

    function __construct($id, $viewable) {
      $this->id = $id;
      $this->viewable = $viewable;
    }
    function Id() { return $this->id; }
    function canView() { return $this->viewable; }
    function getThumbnailSrc($args = array(), $querySep = '&') {
      return '?view=image&eid='.$this->id.'&fid=snapshot';
    }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../../web';

  // The view requires these; empty stubs keep the real classes out of the way.
  $stubdir = sys_get_temp_dir().'/zm_snapshot_links_stubs_'.getmypid();
  @mkdir($stubdir.'/includes', 0700, true);
  foreach (array('Event', 'Filter', 'Snapshot') as $f) {
    file_put_contents($stubdir.'/includes/'.$f.'.php', "<?php\n");
  }
  set_include_path($stubdir.PATH_SEPARATOR.get_include_path());

  class StubUser {
    function Id() { return 2; }
    function unviewableMonitorIds() { return array(); }
    function viewableMonitorIds() { return array(); }
  }

  function canView($area, $mid = false) { return $area === 'Snapshots'; }
  function canEdit($area, $mid = false) { return false; }
  function translate($s) { return $s; }
  function validHtmlStr($s) { return htmlspecialchars($s, ENT_QUOTES); }
  function validInt($v) {
    if (!is_scalar($v)) return '';
    return preg_replace('/[^0-9]/', '', (string)$v);
  }
  function xhtmlHeaders($file, $title) {}
  function getBodyTopHTML() {}
  function getNavBarHTML() { return ''; }
  function xhtmlFooter() {}

  define('ZM_FEATURES_SNAPSHOTS', true);

  function render_snapshot($events) {
    global $root;
    $GLOBALS['snapshot_events'] = $events;
    $_REQUEST = array('id' => '5');
    $user = new StubUser();
    $view = '';
    ob_start();
    include $root.'/skins/classic/views/snapshot.php';
    return ob_get_clean();
  }

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

  $html = render_snapshot(array(new ZM\Event(11, true), new ZM\Event(12, true)));
  check('viewable event 11 links to its event view',
    strpos($html, '<a href="?view=event&amp;eid=11">') !== false, true);
  check('viewable event 12 links to its event view',
    strpos($html, '<a href="?view=event&amp;eid=12">') !== false, true);
  check('event 11 thumbnail is still shown',
    strpos($html, '?view=image&eid=11&fid=snapshot') !== false, true);
  check('event 12 thumbnail is still shown',
    strpos($html, '?view=image&eid=12&fid=snapshot') !== false, true);

  $html = render_snapshot(array(new ZM\Event(21, true), new ZM\Event(22, false)));
  check('viewable event 21 links to its event view',
    strpos($html, '<a href="?view=event&amp;eid=21">') !== false, true);
  check('unviewable event 22 is not linked',
    strpos($html, 'eid=22">') === false, true);
  check('unviewable event 22 thumbnail is still shown',
    strpos($html, '?view=image&eid=22&fid=snapshot') !== false, true);

  @unlink($stubdir.'/includes/Event.php');
  @unlink($stubdir.'/includes/Filter.php');
  @unlink($stubdir.'/includes/Snapshot.php');
  @rmdir($stubdir.'/includes');
  @rmdir($stubdir);
  exit($failures ? 1 : 0);
}
