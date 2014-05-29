<?php
require_once( 'includes/control_functions.php' );

// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) )
    ajaxError( "No monitor id supplied" );

if ( canView( 'Control', $_REQUEST['id'] ) )
{
    $monitor = dbFetchOne( 'select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = ?', NULL, array($_REQUEST['id']) );

    $ctrlCommand = buildControlCommand( $monitor );

    if ( $ctrlCommand )
    {
        $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
        if ( !$socket )
            ajaxError( "socket_create() failed: ".socket_strerror(socket_last_error()) );

        $sock_file = ZM_PATH_SOCKS.'/zmcontrol-'.$monitor['Id'].'.sock';
        if ( @socket_connect( $socket, $sock_file ) )
        {
            $options = array();
            foreach ( explode( " ", $ctrlCommand ) as $option )
            {
                if ( preg_match( '/--([^=]+)(?:=(.+))?/', $option, $matches ) )
                {
                    $options[$matches[1]] = !empty($matches[2])?$matches[2]:1;
                }
            }
            $option_string = jsonEncode( $options );
            if ( !socket_write( $socket, $option_string ) )
                ajaxError( "socket_write() failed: ".socket_strerror(socket_last_error()) );
            ajaxResponse( 'Used socket' );
            //socket_close( $socket );
        }
        else
        {
            $ctrlCommand .= " --id=".$monitor['Id'];

            // Can't connect so use script
            $ctrlStatus = '';
            $ctrlOutput = array();
            exec( escapeshellcmd( $ctrlCommand ), $ctrlOutput, $ctrlStatus );
            if ( $ctrlStatus )
                ajaxError( $ctrlCommand.'=>'.join( ' // ', $ctrlOutput ) );
            ajaxResponse( 'Used script' );
        }
    }
    else
    {
        ajaxError( "No command received" );
    }
}

ajaxError( 'Unrecognised action or insufficient permissions' );

function ajaxCleanup()
{
    global $socket;
    if ( !empty( $socket ) )
        @socket_close( $socket );
}
?>
