<?php

define( "MSG_TIMEOUT", 2.0 );
define( "MSG_DATA_SIZE", 4+256 );

if ( !($_REQUEST['connkey'] && $_REQUEST['command']) )
{
    ajaxError( "Unexpected received message type '$type'" );
}

if ( !($socket = @socket_create( AF_UNIX, SOCK_DGRAM, 0 )) )
{
    ajaxError( "socket_create() failed: ".socket_strerror(socket_last_error()) );
}
$locSockFile = ZM_PATH_SOCKS.'/zms-'.sprintf("%06d",$_REQUEST['connkey']).'w.sock';
if ( !@socket_bind( $socket, $locSockFile ) )
{
    ajaxError( "socket_bind( $lockSockFile ) failed: ".socket_strerror(socket_last_error()) );
}

switch ( $_REQUEST['command'] )
{
    case CMD_VARPLAY :
        //error_log( "Varplaying to ".$_REQUEST['rate'] );
        $msg = pack( "lcn", MSG_CMD, $_REQUEST['command'], $_REQUEST['rate']-32768 );
        break;
    case CMD_ZOOMIN :
        //error_log( "Zooming to ".$_REQUEST['x'].",".$_REQUEST['y'] );
        $msg = pack( "lcnn", MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y'] );
        break;
    case CMD_PAN :
        //error_log( "Panning to ".$_REQUEST['x'].",".$_REQUEST['y'] );
        $msg = pack( "lcnn", MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y'] );
        break;
    case CMD_SCALE :
        //error_log( "Scaling to ".$_REQUEST['scale'] );
        $msg = pack( "lcn", MSG_CMD, $_REQUEST['command'], $_REQUEST['scale'] );
        break;
    case CMD_SEEK :
        //error_log( "Seeking to ".$_REQUEST['offset'] );
        $msg = pack( "lcN", MSG_CMD, $_REQUEST['command'], $_REQUEST['offset'] );
        break;
    default :
        $msg = pack( "lc", MSG_CMD, $_REQUEST['command'] );
        break;
}

$remSockFile = ZM_PATH_SOCKS.'/zms-'.sprintf("%06d",$_REQUEST['connkey']).'s.sock';
if ( !@socket_sendto( $socket, $msg, strlen($msg), 0, $remSockFile ) )
{
    ajaxError( "socket_sendto( $remSockFile ) failed: ".socket_strerror(socket_last_error()) );
}

$rSockets = array( $socket );
$wSockets = NULL;
$eSockets = NULL;
$numSockets = @socket_select( $rSockets, $wSockets, $eSockets, MSG_TIMEOUT );

if ( $numSockets === false)
{
    ajaxError( "Timed out waiting for msg"  );
}
else if ( $numSockets > 0 )
{
    if ( count($rSockets) != 1 )
        ajaxError( "Bogus return from select" );
}

switch( $nbytes = @socket_recvfrom( $socket, $msg, MSG_DATA_SIZE, 0, $remSockFile ) )
{
    case -1 :
    {
        ajaxError( "socket_recvfrom( $remSockFile ) failed: ".socket_strerror(socket_last_error()) );
        break;
    }
    case 0 :
    {
        ajaxError( "No data to read from socket" );
        break;
    }
    default :
    {
        if ( $nbytes != MSG_DATA_SIZE )
            ajaxError( "Got unexpected message size, got $nbytes, expected ".MSG_DATA_SIZE );
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
        $data['rate'] /= RATE_BASE;
        $data['delay'] = sprintf( "%.2f", $data['delay'] );
        $data['zoom'] = sprintf( "%.1f", $data['zoom']/SCALE_BASE );
        ajaxResponse( array( 'status'=>$data ) );
        break;
    }
    case MSG_DATA_EVENT :
    {
        $data =  unpack( "ltype/ievent/Cpaused/C/C/C/iprogress/irate/izoom", $msg );
        //$data['progress'] = sprintf( "%.2f", $data['progress'] );
        $data['rate'] /= RATE_BASE;
        $data['zoom'] = sprintf( "%.1f", $data['zoom']/SCALE_BASE );
        ajaxResponse( array( 'status'=>$data ) );
        break;
    }
    default :
    {
        ajaxError( "Unexpected received message type '$type'" );
    }
}

ajaxError( 'Unrecognised action or insufficient permissions' );

function ajaxCleanup()
{
    global $socket, $locSockFile;
    if ( !empty( $socket ) )
        @socket_close( $socket );
    if ( !empty( $locSockFile ) )
        @unlink( $locSockFile );
}
?>
