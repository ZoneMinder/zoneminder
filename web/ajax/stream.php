<?php
if ( !canView( 'Stream' ) )
{
    $view = "error";
    return;
}

error_reporting( E_ALL );

define( "MSG_TIMEOUT", 2.0 );
define( "MSG_DATA_SIZE", 4+256 );

header("Content-type: text/plain" );
if ( !($_REQUEST['connkey'] && $_REQUEST['command']) )
{
    error_log( "No connection key or command supplied" );
    return;
}

if ( !($socket = socket_create( AF_UNIX, SOCK_DGRAM, 0 )) )
{
    error_log( "socket_create() failed: ".socket_strerror(socket_last_error()) );
    return;
}
$locSockFile = ZM_PATH_SOCKS.'/zms-'.sprintf("%06d",$_REQUEST['connkey']).'w.sock';
if ( !socket_bind( $socket, $locSockFile ) )
{
    error_log( "socket_bind() failed: ".socket_strerror(socket_last_error()) );
    return;
}

switch ( $_REQUEST['command'] )
{
    case CMD_ZOOMIN :
        //error_log( "Zooming to ".$_REQUEST['x'].",".$_REQUEST['y'] );
        $msg =  pack( "lcnn", MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y'] );
        break;
    case CMD_PAN :
        //error_log( "Panning to ".$_REQUEST['x'].",".$_REQUEST['y'] );
        $msg =  pack( "lcnn", MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y'] );
        break;
    case CMD_SCALE :
        //error_log( "Scaling to ".$_REQUEST['scale'] );
        $msg =  pack( "lcn", MSG_CMD, $_REQUEST['command'], $_REQUEST['scale'] );
        break;
    case CMD_SEEK :
        //error_log( "Seeking to ".$_REQUEST['offset'] );
        $msg =  pack( "lcN", MSG_CMD, $_REQUEST['command'], $_REQUEST['offset'] );
        break;
    default :
        $msg =  pack( "lc", MSG_CMD, $_REQUEST['command'] );
        break;
}

$remSockFile = ZM_PATH_SOCKS.'/zms-'.sprintf("%06d",$_REQUEST['connkey']).'s.sock';
if ( !@socket_sendto( $socket, $msg, strlen($msg), 0, $remSockFile ) )
{
    error_log( "socket_sendto() failed: ".socket_strerror(socket_last_error()) );
    return;
}

$rSockets = array( $socket );
$wSockets = NULL;
$eSockets = NULL;
$numSockets = socket_select( $rSockets, $wSockets, $eSockets, MSG_TIMEOUT );

if ( $numSockets === false)
{
    error_log( "Timed out waiting for msg" );
    return;
}
else if ( $numSockets > 0 )
{
    if ( count($rSockets) != 1 )
    {
        error_log( "Bogus return from select" );
        return;
    }
}

switch( $nbytes = socket_recvfrom( $socket, $msg, MSG_DATA_SIZE, 0, $rem_addr ) )
{
    case -1 :
    {
        error_log( "socket_recvfrom() failed: ".socket_strerror(socket_last_error()) );
        return;
    }
    case 0 :
    {
        error_log( "No data to read from socket" );
        return;
    }
    default :
    {
        if ( $nbytes != MSG_DATA_SIZE )
        {
            error_log( "Got unexpected message size, got $nbytes, expected ".MSG_DATA_SIZE );
            return;
        }
        break;
    }
}

$data = unpack( "ltype", $msg );
switch ( $data['type'] )
{
    case MSG_DATA_WATCH :
    {
        $data =  unpack( "ltype/imonitor/dfps/istate/ilevel/Cdelayed/Cpaused/C/C/irate/ddelay/izoom/Cenabled/Cforced", $msg );
        $data['fps'] = sprintf( "%.2f", $data['fps'] );
        $data['rate'] /= 100;
        $data['delay'] = sprintf( "%.2f", $data['delay'] );
        $data['zoom'] = sprintf( "%.1f", $data['zoom']/100 );
        break;
    }
    case MSG_DATA_EVENT :
    {
        $data =  unpack( "ltype/ievent/Cpaused/C/C/C/iprogress/irate/izoom", $msg );
        //$data['progress'] = sprintf( "%.2f", $data['progress'] );
        $data['rate'] /= 100;
        $data['zoom'] = sprintf( "%.1f", $data['zoom']/100 );
        break;
    }
    default :
    {
        error_log( "Unexpected received message type '$type'" );
        $response = array( 'result'=>'Error', 'message' => "Unexpected received message type '$type'" );
        echo jsValue( $response );
        return;
    }
}

$response = array( 'result'=>'Ok', 'status' => $data );
echo jsValue( $response );

socket_close( $socket );
unlink( $locSockFile );

?>
