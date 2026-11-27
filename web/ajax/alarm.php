<?php
define('MSG_TIMEOUT', 2.0);
define('MSG_DATA_SIZE', 4+256);

require_once('includes/Monitor.php');
$monitor = new ZM\Monitor(validCardinal($_REQUEST['id'] ?? 0));

// Needs edit on this monitor, not only the global Monitors permission.
if ( $monitor->Id() and $monitor->canEdit() ) {
    $zmuCommand = getZmuCommand(' -m '.$monitor->Id());

    switch ( validJsStr($_REQUEST['command'] ?? '') ) {
        case 'disableAlarms' :
            $zmuCommand .= ' -n'; 
            break;
        case 'enableAlarms' :
            $zmuCommand .= ' -c'; 
            break;
        case 'forceAlarm' :
            $zmuCommand .= ' -a'; 
            break;
        case 'cancelForcedAlarm' :
            $zmuCommand .= ' -c'; 
            break;
        default :
            ajaxError("Unexpected command '".validJsStr($_REQUEST['command'] ?? '')."'");
    }
    $output = array();
    $rc = 0;
    exec($zmuCommand, $output, $rc);
    // zmu exits non-zero when it cannot connect to the monitor or the monitor ignores the request.
    if ( $rc != 0 ) {
        ajaxError('zmu failed (exit '.$rc.') for monitor '.$monitor->Id().', is it running?');
    }
    ajaxResponse(end($output));
} else {
  ajaxError('Insufficient permissions');
}
?>
