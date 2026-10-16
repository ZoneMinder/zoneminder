<?php
define('MSG_TIMEOUT', 2.0);
define('MSG_DATA_SIZE', 4+256);

require_once('includes/Monitor.php');
$monitor = new ZM\Monitor(validCardinal($_REQUEST['id']));

// Needs edit on this monitor, not only the global Monitors permission.
if ( $monitor->Id() and $monitor->canEdit() ) {
    $zmuCommand = getZmuCommand(' -m '.$monitor->Id());

    switch ( validJsStr($_REQUEST['command']) ) {
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
            ajaxError("Unexpected command '".validJsStr($_REQUEST['command'])."'");
    }
    ajaxResponse(exec(escapeshellcmd($zmuCommand)));
} else {
  ajaxError('Insufficient permissions');
}
?>
