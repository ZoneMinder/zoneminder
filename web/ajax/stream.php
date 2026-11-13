<?php
ini_set('display_errors', '');

$start_time = microtime(true);
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

# zms names its sockets after the user it serves, so a connkey only reaches the
# caller's own streams, not another user's.
$owner = (ZM_OPT_USE_AUTH and $user) ? $user->Id() : 0;

# The holder unlinks the lock file when done, so check we locked the file that
# is still at that path.
$lockFile = zmsSocketPath($connkey, $owner, 'w.lock');
$lockFd = false;
$lock_tries = 10;
while ($lock_tries--) {
  $fd = @fopen($lockFile, 'c');
  if ($fd) {
    clearstatcache(true, $lockFile);
    if (flock($fd, LOCK_EX|LOCK_NB) and ($path_stat = @stat($lockFile)) and fstat($fd)['ino'] == $path_stat['ino']) {
      $lockFd = $fd;
      break;
    }
    fclose($fd);
  }
  ZM\Debug('Failed to get lock for '.$connkey.', trying again');
  usleep(100000);
}

# zms always replies to the same socket path, so two requests for one connkey
# must never run at once.
if (!$lockFd) {
  ajaxError('Failed to get the lock for connkey '.$connkey, HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
}

if (!($socket = @socket_create(AF_UNIX, SOCK_DGRAM, 0))) {
  ajaxError('socket_create() failed: '.socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
}

$bindFile = zmsSocketPath($connkey, $owner, 'w.sock');
# We hold the lock, so any file here is left over from a killed request.
@unlink($bindFile);
if (!socket_bind($socket, $bindFile)) {
  ajaxError("socket_bind( $bindFile ) failed: ".socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
}
# Only set once bound, so ajaxCleanup never unlinks a socket we do not own.
$localSocketFile = $bindFile;

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
case CMD_SET_EVENT :
  # Tell zms which event is next (dir=0) or prev (dir=1) in the UI's filter/sort
  # (refs #4186). See includes/stream_set_event.php.
  require_once('includes/stream_set_event.php');
  $eventid = setEventIdFromRequest(isset($_REQUEST['eventid']) ? $_REQUEST['eventid'] : null);
  $srcid = setEventIdFromRequest(isset($_REQUEST['srcid']) ? $_REQUEST['srcid'] : null);
  if ($eventid === null or $srcid === null) {
    ajaxError('Invalid event id for SET_EVENT', HTTP_STATUS_OK, STREAM_ERR_INVALID);
  }

  # zms authorises only the event the stream started on; it loads whatever
  # target we forward without re-checking. So a client could hand us an event
  # from a monitor it cannot view and advance into that footage. Resolve the
  # target's monitor and require view permission here, where the session user is
  # known. Gated on ZM_OPT_USE_AUTH to match zms, which only validates event
  # access when auth is on; 0 carries no event (filter boundary) and needs no
  # check.
  if (ZM_OPT_USE_AUTH && $eventid) {
    $monitorId = dbFetchOne('SELECT `MonitorId` FROM `Events` WHERE `Id`=?', 'MonitorId', array($eventid));
    if (!$monitorId) {
      # Retention or another user deleted the neighbour between getNearEvents and
      # now. That is a benign race, not a privilege problem. Forward it as a
      # filter boundary (0) so zms stops there, as it does for a target deleted
      # after it was sent.
      ZM\Debug('SET_EVENT target '.$eventid.' no longer exists; treating as filter boundary');
      $eventid = 0;
    } else if (!canView('Events', $monitorId)) {
      ajaxError('Insufficient privileges for event '.$eventid, HTTP_STATUS_OK, STREAM_ERR_INVALID);
    }
  }
  $dir = intval($_REQUEST['dir']);
  ZM\Debug('Setting '.($dir ? 'prev' : 'next').' event to '.$eventid.' (source '.$srcid.')');
  $msg = packSetEventMessage(MSG_CMD, $_REQUEST['command'], $dir, $eventid, $srcid);
  break;
default :
  ZM\Debug('Sending command ' . $_REQUEST['command']);
  $msg = pack('lc', MSG_CMD, $_REQUEST['command']);
  break;
}

$remSockFile = zmsSocketPath($connkey, $owner, 's.sock');
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
  ajaxError("Socket $remSockFile does not exist.  This file is created by zms, and since it does not exist, either zms did not run, or zms exited early.  Please check your zms logs and ensure that CGI is enabled in apache and check that the PATH_ZMS is set correctly.  Make sure that ZM is actually recording.  If you are trying to view a live stream and the capture process (zmc) is not running then zms will exit. Please go to http://zoneminder.readthedocs.io/en/latest/faq.html#why-can-t-i-see-streamed-images-when-i-can-see-stills-in-the-zone-window-etc for more information.", HTTP_STATUS_OK, STREAM_ERR_NO_SOCKET);
} else {
  if (!@socket_sendto($socket, $msg, strlen($msg), 0, $remSockFile)) {
    ajaxError("socket_sendto( $remSockFile ) failed: ".socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_NO_SOCKET);
  }
}

$rSockets = array($socket);
$wSockets = NULL;
$eSockets = NULL;
$select_timed_out = false;

$timeout = max(0, intval(MSG_TIMEOUT - (microtime(true) - $start_time) * 1000));

$numSockets = socket_select($rSockets, $wSockets, $eSockets, intval($timeout/1000), ($timeout%1000)*1000);

if ( $numSockets === false ) {
  ajaxError('socket_select failed: '.socket_strerror(socket_last_error()), HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
} else if ( $numSockets < 0 ) {
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
    ajaxError('Bogus return from select, '.count($rSockets).' sockets available', HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  }
}

$nbytes = @socket_recvfrom($socket, $msg, MSG_DATA_SIZE, 0, $remSockFile);
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
  } else {
    ajaxError('No data to read from socket', HTTP_STATUS_OK, STREAM_ERR_TRANSIENT);
  }
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
      # Send the hash unconditionally. Withholding it when it matched the hash
      # the request carried meant a client whose copy had drifted could never be
      # corrected, and reconnecting streams then baked the drifted hash into
      # their <img> src and 403'd against zms indefinitely.
      $data['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
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
      # Unconditional, for the reason given in the MSG_DATA_WATCH branch above.
      $data['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
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
  global $socket, $localSocketFile, $lockFile, $lockFd;
  if ( !empty($socket) )
    @socket_close($socket);
  if ( !empty($localSocketFile) )
    @unlink($localSocketFile);
  # Release last: the next request may bind the same path as soon as we do.
  if ( $lockFd ) {
    @unlink($lockFile);
    fclose($lockFd);
    $lockFd = false;
  }
}
?>
