<?php
define('MSG_TIMEOUT', 2.0);
define('MSG_DATA_SIZE', 4+256);

if ( canEdit('Monitors') ) {
    $zmuCommand = getZmuCommand(' -m '.validInt($_REQUEST['id']));

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
