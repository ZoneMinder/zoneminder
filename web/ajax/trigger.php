<?php
if (canEdit('Monitors')) {
  $mid = empty($_REQUEST['mid']) ? 0 : validCardinal($_REQUEST['mid']);
  if (!$mid) {
    ajaxError('No monitor id specified.');
    return;
  }
  $monitor = ZM\Monitor::find_one(['Id'=>$mid]);
  if (!$monitor) {
    ajaxError("No monitor found for id $mid.");
    return;
  }

  $action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
  $score = empty($_REQUEST['score']) ? 0 : validCardinal($_REQUEST['score']);
  $cause = empty($_REQUEST['cause']) ? '' : $_REQUEST['cause'];
  $text = empty($_REQUEST['text']) ? '' : $_REQUEST['text'];
  $showtext = empty($_REQUEST['showtext']) ? '' : $_REQUEST['showtext'];

  if ($action == 'show') {
  } else if ($action == 'enable') {
    $monitor->enable();
  } else if ($action == 'disable') {
    $monitor->disable();
  } else if ($action == 'on') {
    if ( $score == 0 ) {
      ZM\Warning('Triggering on with invalid score will have no result.');
      return;
    }
    $monitor->TriggerEventOn($score, $cause, $text);
    if ($showtext) $monitor->TriggerShowtext($showtext);
    ZM\Info("Trigger action:'$action' cause:'$cause'");

    # Because we aren't a persistent daemon here, we can't handle delays
    #if ( $delay ) {
      #my $action_text = $id.'|cancel';
      #handleDelay($delay, $connection, $action_text);
    #}
  } else if ($action == 'off') {
    $last_event_id = $monitor->GetLastEventId();
    $monitor->TriggerEventOff();
    if ($showtext) $monitor->TriggerShowtext($showtext);
    ZM\Info("Trigger '$action'");
    # Wait til it's finished
    while ($monitor->InAlarm() && ($last_event_id == $monitor->GetLastEventId())) {
      # Tenth of a second
      usleep(100000);
    }
    $monitor->TriggerEventCancel();
    ajaxResponse(['event_id'=>$last_event_id]);
  } else {
    ZM\Warning("Invalid action $action");
    ajaxError("Invalid action $action");
  }

  ajaxResponse();
} else {
  ajaxError('Insufficient permissions');
}
?>
