<?php
require_once('includes/control_functions.php');
require_once('includes/Monitor.php');

// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) )
  ajaxError('No monitor id supplied');

if ( isset($_REQUEST['action']) and ($_REQUEST['action'] == 'monitorAction') ) {
  // Fire one of this monitor's manually-triggered actions. Only the action id
  // arrives from the browser; the command and its target are rebuilt from the
  // stored row, so a request cannot name an arbitrary control method or drive
  // a device the operator has no rights on.
  require_once('includes/MonitorAction.php');

  if ( !canView('Control', $_REQUEST['id']) )
    ajaxError('Insufficient permissions');

  if ( empty($_REQUEST['aid']) )
    ajaxError('No action id supplied');

  $monitorAction = ZM\MonitorAction::find_one(array(
    'Id' => validInt($_REQUEST['aid']),
    'MonitorId' => validInt($_REQUEST['id']),
    'TriggerOn' => 'Manual',
    'Enabled' => 1,
  ));
  if ( !$monitorAction )
    ajaxError('No such enabled manual action for this monitor');

  $target = $monitorAction->TargetMonitor();
  if ( !$target )
    ajaxError('Action has no target monitor');

  // Rights are needed on the device being driven, not just on the monitor the
  // action hangs off.
  if ( !canView('Control', $target->Id()) )
    ajaxError('Insufficient permissions on the target monitor');

  if ( $url = remoteControlUrl($target, $_REQUEST) )
    relayControlReply($url);

  $ctrlCommand = $monitorAction->controlCommand();
  if ( !$ctrlCommand )
    ajaxError('Action has no usable command');

  if ( $target->sendControlCommand($ctrlCommand) ) {
    ajaxResponse('Success');
  } else {
    ajaxError('Failed');
  }
  return;
}

if ( canView('Control', $_REQUEST['id']) ) {
  $monitor = new ZM\Monitor(validCardinal($_REQUEST['id']));

  if ( $url = remoteControlUrl($monitor, $_REQUEST) )
    relayControlReply($url);

  $ctrlCommand = buildControlCommand($monitor);

  if ( !$ctrlCommand ) {
    ajaxError('No command received');
    return;
  }

  // Opt-in query: return the daemon's reply (e.g. light status) to the browser.
  if ( !empty($_REQUEST['response']) ) {
    ajaxResponse(array('status' => $monitor->sendControlCommandWithResponse($ctrlCommand)));
    return;
  }

  if ( $monitor->sendControlCommand($ctrlCommand) ) {
    ajaxResponse('Success');
  } else {
    ajaxError('Failed');
  }
}

ajaxError('Unrecognised action or insufficient permissions');

// Hands the request to the server that records the monitor and answers with its reply.
function relayControlReply($url) {
  $reply = forwardControlRequest($url);
  if ( $reply === false )
    ajaxError('Failed');
  // The other server answers with an ajaxResponse/ajaxError JSON object. Don't
  // pass a non-JSON body (e.g. an unexpected HTML error page) back under a JSON
  // content-type; report a clean error instead.
  if ( json_decode($reply) === null && trim($reply) !== 'null' )
    ajaxError('Recording server returned an unexpected reply');
  if ( function_exists('ajaxCleanup') )
    ajaxCleanup();
  header('Content-type: application/json');
  exit($reply);
}
?>
