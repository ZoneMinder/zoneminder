<?php
ini_set('display_errors', '');

$start_time = time();
$connkey = sprintf('%06d', $_REQUEST['connkey']);

define('MSG_TIMEOUT', ZM_WEB_AJAX_TIMEOUT/2);
define('MSG_DATA_SIZE', 4+256);

/* Failure classes reported to the client as 'reason'.  The client needs to
 * tell "zms is gone, start a new one" apart from "this particular exchange
 * failed", because restarting the stream replaces the connkey and makes any
 * still-running zms unreachable.
 *
 *   no_socket - zms is not listening: it never started, or it has exited.
 *               The only class where restarting the stream is the right answer.
 *   timeout   - the command went out but no reply came back in time.  zms is
 *               most likely alive and busy.
 *   transient - a failure local to this php process (socket setup, a short
 *               read).  Says nothing at all about zms.
 *   invalid   - bad request or an unexpected reply.  Retrying will not help.
 */
define('STREAM_ERR_NO_SOCKET', 'no_socket');
define('STREAM_ERR_TIMEOUT', 'timeout');
define('STREAM_ERR_TRANSIENT', 'transient');
define('STREAM_ERR_INVALID', 'invalid');

if ( !($_REQUEST['connkey'] && $_REQUEST['command']) ) {
  ajaxError('No connkey or no command in stream ajax', HTTP_STATUS_OK, STREAM_ERR_INVALID);
}

@mkdir(ZM_PATH_SOCKS);

# The file that we point ftok to has to exist, and only exist if zms is running, so we are pointing it at the .sock
$key = $connkey;
$semaphore = sem_get($key, 1);
$have_semaphore = false;

if ($semaphore) {
  $semaphore_tries = 10;
  while ($semaphore_tries) {
    if (version_compare( phpversion(), '5.6.1', '<')) {
      # don't have support for non-blocking
      $have_semaphore = sem_acquire($semaphore);
    } else {
      $have_semaphore = sem_acquire($semaphore, 1);
    }
    if ($have_semaphore !== false) break;
    ZM\Debug('Failed to get semaphore for '.$connkey.' '.$key.', trying again');
    usleep(100000);
    $semaphore_tries -= 1;
  }
} else {
  ZM\Error("Failed to get semaphore for key $key.  It is likely that your php does not have the sysv semaphore extension either installed or enabled.");
}

if (!($socket = @socket_create(AF_UNIX, SOCK_DGRAM, 0))) {
  if ($semaphore) sem_release($semaphore);
  ajaxError('socket_create() failed: '.socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
}

$localSocketFile = ZM_PATH_SOCKS.'/zms-'.$connkey.'w.sock';
if (!socket_bind($socket, $localSocketFile)) {
  if ($semaphore) sem_release($semaphore);
  ajaxError("socket_bind( $localSocketFile ) failed: ".socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
}

switch ($_REQUEST['command']) {
case CMD_VARPLAY :
  ZM\Debug('Varplaying to '.$_REQUEST['rate']);
  $msg = pack('lcn', MSG_CMD, $_REQUEST['command'], $_REQUEST['rate']+32768);
  break;
case CMD_ZOOMIN :
  ZM\Debug('Zooming to '.$_REQUEST['x'].','.$_REQUEST['y']);
  $msg = pack('lcnn', MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y']);
  break;
case CMD_PAN :
  ZM\Debug('Panning to '.$_REQUEST['x'].','.$_REQUEST['y']);
  $msg = pack('lcnn', MSG_CMD, $_REQUEST['command'], $_REQUEST['x'], $_REQUEST['y']);
  break;
case CMD_SCALE :
  ZM\Debug('Scaling to '.$_REQUEST['scale']);
  $msg = pack('lcn', MSG_CMD, $_REQUEST['command'], $_REQUEST['scale']);
  break;
case CMD_SEEK :
  # Pack int two 32 bit integers instead of trying to deal with floats
  $msg = pack('lcNN', MSG_CMD, $_REQUEST['command'],
    intval($_REQUEST['offset']),
    1000000*( $_REQUEST['offset']-intval($_REQUEST['offset'])));
  break;
case CMD_MAXFPS :
  if (!floatval($_REQUEST['maxfps'])) $_REQUEST['maxfps'] = 0;
  ZM\Debug('Maxfps to '.$_REQUEST['maxfps']);
  # Pack int two 32 bit integers instead of trying to deal with floats
  $msg = pack('lcNN', MSG_CMD, $_REQUEST['command'],
    intval($_REQUEST['maxfps']),
    1000000*( $_REQUEST['maxfps']-intval($_REQUEST['maxfps'])));
  break;
default :
  ZM\Debug('Sending command ' . $_REQUEST['command']);
  $msg = pack('lc', MSG_CMD, $_REQUEST['command']);
  break;
}

$remSockFile = ZM_PATH_SOCKS.'/zms-'.$connkey.'s.sock';
// Pi can take up to 3 seconds for zms to start up.
$max_socket_tries = 1000;
// FIXME This should not exceed web_ajax_timeout
while ( !file_exists($remSockFile) && $max_socket_tries-- ) {
  //sometimes we are too fast for our own good, if it hasn't been setup yet give it a second.
  // WHY? We will just send another one...
  // ANSWER: Because otherwise we get a log of errors logged

  //ZM\Debug("$remSockFile does not exist, waiting, current " . (time() - $start_time) . ' seconds' );
  usleep(1000);
}

if (!file_exists($remSockFile)) {
  if ($semaphore) sem_release($semaphore);
  ajaxError("Socket $remSockFile does not exist.  This file is created by zms, and since it does not exist, either zms did not run, or zms exited early.  Please check your zms logs and ensure that CGI is enabled in apache and check that the PATH_ZMS is set correctly.  Make sure that ZM is actually recording.  If you are trying to view a live stream and the capture process (zmc) is not running then zms will exit. Please go to http://zoneminder.readthedocs.io/en/latest/faq.html#why-can-t-i-see-streamed-images-when-i-can-see-stills-in-the-zone-window-etc for more information.", HTTP_STATUS_OK, STREAM_ERR_NO_SOCKET);
} else {
  if (!@socket_sendto($socket, $msg, strlen($msg), 0, $remSockFile)) {
    if ($semaphore) sem_release($semaphore);
    ajaxError("socket_sendto( $remSockFile ) failed: ".socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_NO_SOCKET);
  }
}

$rSockets = array($socket);
$wSockets = NULL;
$eSockets = NULL;
$select_timed_out = false;

$timeout = MSG_TIMEOUT - ( time() - $start_time );

$numSockets = socket_select($rSockets, $wSockets, $eSockets, intval($timeout/1000), ($timeout%1000)*1000);

if ( $numSockets === false ) {
  if ($semaphore) sem_release($semaphore);
  ajaxError('socket_select failed: '.socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
} else if ( $numSockets < 0 ) {
  if ($semaphore) sem_release($semaphore);
  ajaxError("Socket closed $remSockFile", HTTP_STATUS_OK, STREAM_ERR_NO_SOCKET);
} else if ( $numSockets == 0 ) {
  ZM\Error("Timed out waiting for msg $remSockFile after waiting $timeout milliseconds");
  socket_set_nonblock($socket);
  // Not an error on its own: the socket is now non-blocking, so the recvfrom
  // below returns immediately and reports this as a timeout rather than
  // pretending zms had nothing to say.
  $select_timed_out = true;
  #ajaxError("Timed out waiting for msg $remSockFile");
} else if ( $numSockets > 0 ) {
  if ( count($rSockets) != 1 ) {
    if ($semaphore) sem_release($semaphore);
    ajaxError('Bogus return from select, '.count($rSockets).' sockets available', HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  }
}

$nbytes = @socket_recvfrom($socket, $msg, MSG_DATA_SIZE, 0, $remSockFile);
if ($semaphore) sem_release($semaphore);
switch ($nbytes) {
case -1 :
  ajaxError("socket_recvfrom( $remSockFile ) failed: ".socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  break;
case 0 :
  // socket_recvfrom() returns false on error, and false == 0 under switch's
  // loose comparison, so a timed-out select lands here rather than on -1.
  if ($select_timed_out) {
    ajaxError("Timed out waiting for msg $remSockFile after waiting $timeout milliseconds",
      HTTP_STATUS_OK, STREAM_ERR_TIMEOUT);
  }
  ajaxError('No data to read from socket', HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  break;
default :
  if ( $nbytes != MSG_DATA_SIZE ) {
    ajaxError("Got unexpected message size, got $nbytes, expected ".MSG_DATA_SIZE, HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  }
  break;
}

$data = unpack('ltype', $msg);
switch ( $data['type'] ) {
case MSG_DATA_WATCH :
  $data = unpack('ltype/imonitor/istate/dfps/dcapturefps/danalysisfps/ilevel/irate/ddelay/izoom/iscale/Cdelayed/Cpaused/Cenabled/Cforced/iscore/ianalysing/Canalysisimage/Cstopped', $msg);
  $data['fps'] = round( $data['fps'], 2 );
  $data['capturefps'] = round( $data['capturefps'], 2 );
  $data['analysisfps'] = round( $data['analysisfps'], 2 );
  $data['rate'] /= RATE_BASE;
  $data['delay'] = round( $data['delay'], 2 );
  $data['zoom'] = round( $data['zoom']/SCALE_BASE, 1 );
  #$data['scale'] = $data['scale'];
  if (ZM_OPT_USE_AUTH) {
    if (ZM_AUTH_RELAY == 'hashed') {
      $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
      if (isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash)) {
        $data['auth'] = $auth_hash;
        ZM\Debug('including new auth hash '.$data['auth'].'because doesnt match request auth hash '.$_REQUEST['auth']);
      //} else {
        //ZM\Debug('Not including new auth hash because it hasn\'t changed '.$auth_hash);
      }
    }
    $data['auth_relay'] = get_auth_relay();
  }
  ajaxResponse(array('status'=>$data));
  break;
case MSG_DATA_EVENT :
  if ( PHP_INT_SIZE===4 || version_compare( phpversion(), '5.6.0', '<') ) {
    ZM\Debug('Using old unpack methods to handle 64bit event id');
    $data = unpack('ltype/ieventlow/ieventhigh/dduration/dprogress/dfps/irate/izoom/iscale/Cpaused/Cstopped', $msg);
    $data['event'] = $data['eventhigh'] << 32 | $data['eventlow'];
  } else {
    $data = unpack('ltype/Qevent/dduration/dprogress/dfps/irate/izoom/iscale/Cpaused/Cstopped', $msg);
  }
  $data['rate'] /= RATE_BASE;
  $data['zoom'] = round($data['zoom']/SCALE_BASE, 1);
  $data['fps'] = round( $data['fps'], 2 );
  if ( ZM_OPT_USE_AUTH ) {
    if (ZM_AUTH_RELAY == 'hashed') {
      $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
      if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
        $data['auth'] = $auth_hash;
      }
    }
    $data['auth_relay'] = get_auth_relay();
  }

  ajaxResponse(array('status'=>$data));
  break;
default :
  ajaxError('Unexpected received message type '.$data['type'], HTTP_STATUS_OK, STREAM_ERR_INVALID);
}
ajaxError('Unrecognised action or insufficient permissions in ajax/stream', HTTP_STATUS_OK, STREAM_ERR_INVALID);

function ajaxCleanup() {
  global $socket, $localSocketFile;
  if ( !empty($socket) )
    @socket_close($socket);
  if ( !empty($localSocketFile) )
    @unlink($localSocketFile);
}
?>
