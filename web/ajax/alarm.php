<?php
if ( !canView( 'Stream' ) )
{
    $view = "error";
    return;
}

define( "MSG_TIMEOUT", 2.0 );
define( "MSG_DATA_SIZE", 4+256 );

header("Content-type: text/plain" );

if ( canEdit( 'Monitors' ) )
{
    $zmu_command = getZmuCommand( " -m ".validInt($_REQUEST['id']) );

    switch ( validJsStr($_REQUEST['command']) )
    {
        case "disableAlarms" :
        {
            $zmu_command .= " -n"; 
            break;
        }
        case "enableAlarms" :
        {
            $zmu_command .= " -c"; 
            break;
        }
        case "forceAlarm" :
        {
            $zmu_command .= " -a"; 
            break;
        }
        case "cancelForcedAlarm" :
        {
            $zmu_command .= " -c"; 
            break;
        }
        default :
        {
            $response['result'] = 'Error';
            $response['message'] = "Unexpected command '".validJsStr($_REQUEST['command'])."'";
            echo jsValue( $response );
            exit;
        }
    }
}

$response['result'] = 'Ok';
//error_log( $zmu_command );
$response['message'] = exec( escapeshellcmd( $zmu_command ) );
echo jsValue( $response );

?>
