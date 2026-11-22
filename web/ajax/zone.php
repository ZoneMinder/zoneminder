<?php
require_once('includes/Monitor.php');
require_once('includes/Event.php');
require_once('includes/Zone.php');

if ( empty($_REQUEST['mid']) ) {
  ajaxError('No monitor id supplied');
} else if ( !isset($_REQUEST['zid']) ) {
  ajaxError('No zone id(s) supplied');
}

$monitor = new ZM\Monitor(validCardinal($_REQUEST['mid']));
// A zone editing tool, and a run costs real CPU: same permission as saving.
if ( !$monitor->Id() or !$monitor->canEdit() ) {
  ajaxError('Insufficient permissions');
}

// ponytail: fixed cap, an option if long events need it.
const ZONE_TEST_TIMEOUT = 600;

if ( isset($_REQUEST['command']) and $_REQUEST['command'] == 'test' ) {
  // Re-run detection over a recorded event with the form's zone settings, via
  // zma --dry-run so nothing is written. The zone must exist: zma rebuilds it
  // from its saved row with the form's values laid over it.
  $zid = validCardinal($_REQUEST['zid']);
  if ( !$zid or !ZM\Zone::find_one(array('Id'=>$zid, 'MonitorId'=>$monitor->Id())) ) {
    ajaxError('Save the zone before testing it');
  }
  $event = new ZM\Event(validCardinal(isset($_REQUEST['eid']) ? $_REQUEST['eid'] : 0));
  if ( !$event->Id() or $event->MonitorId() != $monitor->Id() or !$event->canView() ) {
    ajaxError('Unknown event for this monitor');
  }

  $zma = ZM_PATH_BIN.'/zma';
  if ( !is_executable($zma) ) {
    ajaxError('zma is not installed');
  }

  // Each value as the Zones table stores it, within the range the zone form
  // allows: field => array(max, whole numbers only). Anything else is refused
  // rather than passed to a shell.
  $limits = array(
    'MinPixelThreshold' => array(255, true), 'MaxPixelThreshold' => array(255, true),
    'FilterX' => array(15, true), 'FilterY' => array(15, true),
    'MinAlarmPixels' => array(100, false), 'MaxAlarmPixels' => array(100, false),
    'MinFilterPixels' => array(100, false), 'MaxFilterPixels' => array(100, false),
    'MinBlobPixels' => array(100, false), 'MaxBlobPixels' => array(100, false),
    'MinBlobs' => array(254, true), 'MaxBlobs' => array(254, true),
  );
  $newZone = isset($_REQUEST['newZone']) && is_array($_REQUEST['newZone']) ? $_REQUEST['newZone'] : array();
  foreach ( $newZone as $field => $value ) {
    if ( !is_string($value) ) ajaxError("Invalid value for $field");
  }
  $sets = array();
  foreach ( $limits as $field => $limit ) {
    if ( !isset($newZone[$field]) ) continue;
    $value = trim($newZone[$field]);
    if ( $value !== '' and (
      !preg_match($limit[1] ? '/^\d{1,3}$/' : '/^\d{1,3}(\.\d{1,2})?$/', $value) or floatval($value) > $limit[0]) ) {
      ajaxError("Invalid value for $field");
    }
    $sets[] = $field.'='.$value;
  }
  if ( isset($newZone['CheckMethod']) ) {
    $method = array_search($newZone['CheckMethod'], array('AlarmedPixels', 'FilteredPixels', 'Blobs'), true);
    if ( $method === false ) ajaxError('Invalid CheckMethod');
    $sets[] = 'CheckMethod='.($method + 1);
  }
  if ( isset($newZone['Coords']) ) {
    // getCoordString() always writes two decimals, and Zone::LoadRow reads a
    // '.' in Coords as "percent", so insist on that form.
    if ( !preg_match('/^\d+\.\d+,\d+\.\d+( \d+\.\d+,\d+\.\d+){2,}$/', $newZone['Coords']) ) {
      ajaxError('Invalid Coords');
    }
    $sets[] = 'Coords='.$newZone['Coords'];
  }

  // One run per monitor at a time, and none longer than ZONE_TEST_TIMEOUT: a
  // run decodes the whole event, and exec() outlives an abandoned request.
  $lock = fopen(sys_get_temp_dir().'/zm_zone_test_'.$monitor->Id().'.lock', 'c');
  if ( !$lock or !flock($lock, LOCK_EX|LOCK_NB) ) {
    ajaxError('A test is already running for this monitor');
  }
  $command = 'timeout '.ZONE_TEST_TIMEOUT.' '.escapeshellarg($zma).' --dry-run --json -e '.escapeshellarg($event->Id())
    .' -z '.escapeshellarg($zid);
  foreach ( $sets as $set ) $command .= ' -s '.escapeshellarg($set);

  ZM\Debug("Zone test: $command");
  $output = array();
  exec($command.' 2>/dev/null', $output, $status);
  flock($lock, LOCK_UN);
  fclose($lock);
  if ( $status == 124 ) {
    ajaxError('The test took longer than '.ZONE_TEST_TIMEOUT.'s - try a shorter event');
  }

  $result = array('zones' => array(), 'frames' => array(), 'done' => null);
  foreach ( $output as $line ) {
    $row = json_decode($line, true);
    if ( !is_array($row) or !isset($row['type']) ) continue;
    if ( $row['type'] == 'zones' ) {
      $result['zones'] = $row['zones'];
    } else if ( $row['type'] == 'frame' ) {
      $result['frames'][] = $row;
    } else if ( $row['type'] == 'done' ) {
      $result['done'] = $row;
    } else if ( $row['type'] == 'error' ) {
      ajaxError('zma: '.$row['message']);
    }
  }
  if ( $result['done'] === null ) {
    // No JSON at all usually means a zma from before --json existed.
    ajaxError($status ? "zma failed (exit $status) - see the zma_e".$event->Id().' log' : 'zma returned no results');
  }
  # What to replay the result over: the event's video, or failing that its
  # stored JPEG frames (SaveJPEGs bit 1), stepped through at the event's rate.
  $result['video'] = $event->DefaultVideo() ? $event->getStreamSrc(array('mode'=>'mp4')) : '';
  $result['jpegs'] = (bool)($event->SaveJPEGs() & 1);
  $result['length'] = (float)$event->Length();
  ajaxResponse($result);
}

ajaxError('Unrecognised action or insufficient permissions');
?>
