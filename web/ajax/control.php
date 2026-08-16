<?php
require_once('includes/control_functions.php');
require_once('includes/Monitor.php');

// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) )
  ajaxError('No monitor id supplied');

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
