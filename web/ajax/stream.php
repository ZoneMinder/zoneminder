<?php
  error_reporting(0);

$start_time = time();

define('MSG_TIMEOUT', ZM_WEB_AJAX_TIMEOUT/2);
define('MSG_DATA_SIZE', 4+256);

if ( !($_REQUEST['connkey'] && $_REQUEST['command']) ) {
  ajaxError("Unexpected received message type '$type'");
}

mkdir(ZM_PATH_SOCKS);

# The file that we point ftok to has to exist, and only exist if zms is running, so we are pointing it at the .sock
$key = ftok(ZM_PATH_SOCKS.'/zms-'.sprintf('%06d',$_REQUEST['connkey']).'s.sock', 'Z');
$semaphore = sem_get($key,1);
if ( sem_acquire($semaphore,1) !== false ) {
  if ( !($socket = @socket_create(AF_UNIX, SOCK_DGRAM, 0)) ) {
    ajaxError('socket_create() failed: '.socket_strerror(socket_last_error()));
  }

  $localSocketFile = ZM_PATH_SOCKS.'/zms-'.sprintf('%06d',$_REQUEST['connkey']).'w.sock';
  if ( file_exists($localSocketFile) ) {
    ZM\Warning("sock file $localSocketFile already exists?!  Is someone else talking to zms?");
    // They could be.  We can maybe have concurrent requests from a browser.  
  }
  if ( !socket_bind($socket, $localSocketFile) ) {
    ajaxError("socket_bind( $localSocketFile ) failed: ".socket_strerror(socket_last_error()));
  }

  switch ( $_REQUEST['command'] ) {
  case CMD_VARPLAY :
    ZM\Logger::Debug('Varplaying to '.$_REQUEST['rate']);
    $msg = pack('lcn', MSG_CMD, $_REQUEST['command'], $_REQUEST['rate']+32768);
    break;
  case CMD_ZOOMIN :
    ZM\Logger::Debug('Zooming to '.$_REQUEST['x'].','.$_REQUEST['y']);
    $msg = pack('lcnn', MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y']);
    break;
  case CMD_PAN :
    ZM\Logger::Debug('Panning to '.$_REQUEST['x'].','.$_REQUEST['y']);
    $msg = pack('lcnn', MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y']);
    break;
  case CMD_SCALE :
    ZM\Logger::Debug('Scaling to '.$_REQUEST['scale']);
    $msg = pack('lcn', MSG_CMD, $_REQUEST['command'], $_REQUEST['scale']);
    break;
  case CMD_SEEK :
    ZM\Logger::Debug('Seeking to '.$_REQUEST['offset']);
    $msg = pack('lcN', MSG_CMD, $_REQUEST['command'], $_REQUEST['offset']);
    break;
  default :
    ZM\Logger::Debug('Sending command ' . $_REQUEST['command']);
    $msg = pack('lc', MSG_CMD, $_REQUEST['command']);
    break;
  }

  $remSockFile = ZM_PATH_SOCKS.'/zms-'.sprintf('%06d',$_REQUEST['connkey']).'s.sock';
  // Pi can take up to 3 seconds for zms to start up.
  $max_socket_tries = 1000;
  // FIXME This should not exceed web_ajax_timeout
  while ( !file_exists($remSockFile) && $max_socket_tries-- ) {
		//sometimes we are too fast for our own good, if it hasn't been setup yet give it a second. 
    // WHY? We will just send another one... 
    // ANSWER: Because otherwise we get a log of errors logged

    //ZM\Logger::Debug("$remSockFile does not exist, waiting, current " . (time() - $start_time) . ' seconds' );
    usleep(1000);
  }

  if ( !file_exists($remSockFile) ) {
    ajaxError("Socket $remSockFile does not exist.  This file is created by zms, and since it does not exist, either zms did not run, or zms exited early.  Please check your zms logs and ensure that CGI is enabled in apache and check that the PATH_ZMS is set correctly.  Make sure that ZM is actually recording.  If you are trying to view a live stream and the capture process (zmc) is not running then zms will exit. Please go to http://zoneminder.readthedocs.io/en/latest/faq.html#why-can-t-i-see-streamed-images-when-i-can-see-stills-in-the-zone-window-etc for more information.");
  } else {
    if ( !@socket_sendto($socket, $msg, strlen($msg), 0, $remSockFile) ) {
      ajaxError("socket_sendto( $remSockFile ) failed: ".socket_strerror(socket_last_error()));
    }
  }

  $rSockets = array($socket);
  $wSockets = NULL;
  $eSockets = NULL;

  $timeout = MSG_TIMEOUT - ( time() - $start_time );

  $numSockets = socket_select($rSockets, $wSockets, $eSockets, intval($timeout/1000), ($timeout%1000)*1000);

  if ( $numSockets === false ) {
    ajaxError('socket_select failed: '.socket_strerror(socket_last_error()));
  } else if ( $numSockets < 0 ) {
    ajaxError("Socket closed $remSockFile");
  } else if ( $numSockets == 0 ) {
    ZM\Error("Timed out waiting for msg $remSockFile");
    socket_set_nonblock($socket);
    #ajaxError("Timed out waiting for msg $remSockFile");
  } else if ( $numSockets > 0 ) {
    if ( count($rSockets) != 1 ) {
      ajaxError('Bogus return from select, '.count($rSockets).' sockets available');
    }
  }

  switch( $nbytes = @socket_recvfrom($socket, $msg, MSG_DATA_SIZE, 0, $remSockFile) ) {
  case -1 :
    ajaxError("socket_recvfrom( $remSockFile ) failed: ".socket_strerror(socket_last_error()));
    break;
  case 0 :
    ajaxError('No data to read from socket');
    break;
  default :
    if ( $nbytes != MSG_DATA_SIZE )
      ajaxError("Got unexpected message size, got $nbytes, expected ".MSG_DATA_SIZE);
    break;
  }

  $data = unpack('ltype', $msg);
  switch ( $data['type'] ) {
  case MSG_DATA_WATCH :
    $data = unpack('ltype/imonitor/istate/dfps/ilevel/irate/ddelay/izoom/Cdelayed/Cpaused/Cenabled/Cforced', $msg);
    $data['fps'] = round( $data['fps'], 2 );
    $data['rate'] /= RATE_BASE;
    $data['delay'] = round( $data['delay'], 2 );
    $data['zoom'] = round( $data['zoom']/SCALE_BASE, 1 );
    if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
      $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
      if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
        $data['auth'] = $auth_hash;
        ZM\Logger::Debug("including nw auth hash " . $data['auth']);
      } else {
        ZM\Logger::Debug('Not including nw auth hash becase it hashn\'t changed '.$auth_hash);
      } 
    }
    ajaxResponse(array('status'=>$data));
    break;
  case MSG_DATA_EVENT :
    if ( version_compare( phpversion(), '5.6.0', '<') ) {
      ZM\Logger::Debug('Using old unpack methods to handle 64bit event id');
      $data = unpack('ltype/ieventlow/ieventhigh/iprogress/irate/izoom/Cpaused', $msg);
      $data['event'] = $data['eventhigh'] << 32 | $data['eventlow'];
    } else {
      $data = unpack('ltype/Qevent/iprogress/irate/izoom/Cpaused', $msg);
    }
    $data['rate'] /= RATE_BASE;
    $data['zoom'] = round($data['zoom']/SCALE_BASE, 1);
    if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
      $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
      if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
        $data['auth'] = $auth_hash;
      } 
    }
    ajaxResponse(array('status'=>$data));
    break;
  default :
    ajaxError("Unexpected received message type '$type'");
  }
  sem_release($semaphore);
} else {
  ZM\Logger::Debug('Couldn\'t get semaphore');
  ajaxResponse(array());
}

ajaxError('Unrecognised action or insufficient permissions in ajax/stream');

function ajaxCleanup() {
  global $socket, $localSocketFile;
  if ( !empty($socket) )
    @socket_close($socket);
  if ( !empty($localSocketFile) )
    @unlink($localSocketFile);
}
?>
