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
  $monitor = new ZM\Monitor($_REQUEST['id']);

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
?>
