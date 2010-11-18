<?php
/* 
 * actions.php is created by Jai Dhar, FPS-Tech, for use with eyeZm
 * iPhone application. This is not intended for use with any other applications,
 * although source-code is provided under GPL.
 *
 * For questions, please email jdhar@eyezm.com (http://www.eyezm.com)
 *
 */
/* Parse any specific actions here */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	if (strcmp($action, "devent") == 0) {
		/* ACTION: Delete an Event. Parms: <eid> */
		if (!canEdit('Events')) {
			logXmlErr("User ".$user['Username']. " doesn't have edit Events perms");
			exit;
		}
		if (!isset($_REQUEST['eid'])) {
			logXmlErr("EID not set for action delete-event");
			exit;
		}
		$eid = validInt($_REQUEST['eid']);
		$url = "./index.php?view=request&request=event&id=".$eid."&action=delete";
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "spawn264") == 0) {
		/* ACTION: Spawn 264 streaming process.
		 * Parms: <monitor>[br|width|height] */
		if (!canView('Stream')) {
			logXmlErr("User ".$user['Username']. " doesn't have view Stream perms");
			exit;
		}
		if (!isset($_GET['monitor'])) {
			logXmlErr("Not all parameters specified for spawn264");
			exit;
		}
		$monitor = validInt($_REQUEST['monitor']);
		$dims = getMonitorDims($monitor);
		$width = getset('width', $dims['Width']);
		$height = getset('height', $dims['Height']);
		$br = getset('br', ZM_XML_H264_DEFAULT_BR);
		/* Check that we can stream first */
		if (!canStream264()) {
			/* canStream264 will print out error */
			exit;
		}
		$streamUrl = stream264fn($monitor, $width, $height, $br);
		logXml("Using H264 Pipe Function: ".$streamUrl);
		$pid = shell_exec($streamUrl);
		logXml("Streaming Process for monitor ".$monitor." ended, cleaning up files");
		eraseH264Files($monitor);
		exit;

	} else if (strcmp($action, "kill264") == 0) {
		/* ACTION: Kill existing H264 stream process and cleanup files.
		 * Parms: <monitor>.
		 * NOTE: This will be called directly by path, so include files
		 * may not be available */
		session_start();
		require_once(dirname(__FILE__)."/../includes/functions.php");
		if (!isset($_GET['monitor'])) {
			logXmlErr("Not all parameters specified for kill264");
			exit;
		}
		$monitor = $_GET['monitor'];
		kill264proc($monitor);
		logXml("Killed Segmenter process for monitor ".$monitor);
		exit;

	} else if (strcmp($action, "chk264") == 0) {
		/* ACTION: Simply stalls while checking for 264 file.
		 * Parms: <monitor><timeout> 
		 * NOTE: This will be called directly by path, so include files
		 * may not be available */
		session_start();
		require_once(dirname(__FILE__)."/../includes/functions.php");
		if (!isset($_GET['monitor']) || !isset($_GET['timeout'])) {
			logXmlErr("Monitor not specified for chk264");
			exit;
		}
		$monitor = $_GET['monitor'];
		$path = getTempDir()."/".m3u8fname($monitor);
		/* Wait for the second sample to become available */
		$tsfile = getTempDir()."/sample_".$monitor."-2.ts";
		/* Setup timeout */
		$startTime = time();
		$timeout = $_GET['timeout'];
		while (!file_exists($path) || !file_exists($tsfile)) {
			if (time() > $startTime + $timeout) {
				logXmlErr("Timed out waiting for stream to start, exiting...");
				kill264proc($monitor);
				exit;
			}
			usleep(10000);
		}
		logXml("File exists, stream created after ".(time()-$startTime)." sec");
		exit;

	} else if (strcmp($action, "feed") == 0) {
		/* ACTION: View a feed. Parms: <monitor>> [height|width|fps|scale|vcodec|br] */
		if (!canView('Stream')) {
			logXmlErr("User ".$user['Username']. " doesn't have view Stream perms");
			exit;
		}
		/* Check that required variables are set */
		if (!isset($_REQUEST['monitor'])) {
			logXmlErr("Not all parameters set for action view-feed");
			exit;
		}
		$monitor = validInt($_REQUEST['monitor']);
		$dims = getMonitorDims($monitor);
		$width = getset('width', $dims['Width']);
		$height = getset('height', $dims['Height']);
		$fps = getset('fps', ZM_WEB_VIDEO_MAXFPS);
		$scale = getset('scale', 100);
		$vcodec = getset('vcodec', ZM_XML_FEED_VCODEC);
		/* Select which codec we want */
		if (!strcmp($vcodec, "h264")) {
			/* Validate that we can in fact stream H264 */
			if (!canStream264()) {
				/* canStream264 will print out error if
				 * there is one */
				echo "Server cannot stream H264. Check XML log for details";
				exit;
			}
			if (!requireVer("1", "2")) {
				echo "H264 Streaming requires eyeZm v1.2 or above";
				logXmlErr("H264 Streaming requires eyeZm v1.2 or above");
				exit;
			}
			$br = getset('br', ZM_XML_H264_DEFAULT_BR);
			/* H264 processing */
			noCacheHeaders();
			/* Kill any existing processes and files */
			kill264proc($monitor);
			eraseH264Files($monitor);
			logXml("Streaming H264 on Monitor ".$monitor.", ".$width."x".$height." @".$br);
			/* Get thumbnail source */
			$thumbsrc = 
				getStreamSrc( array( 
					"mode=single", 
					"monitor=".$monitor, 
					"scale=".$scale,	
					"maxfps=".$fps,
					"buffer=1000" 
				) );
			logXml("Using thumbnail image from ".$thumbsrc);
			/* Generate H264 Web-page */
			h264vidHtml($width, $height, $monitor, $br, $thumbsrc);
		} else if (!strcmp($vcodec, "mjpeg")) {
			/* MJPEG streaming */
			$streamSrc = 
				getStreamSrc( array( 
					"mode=jpeg", 
					"monitor=".$monitor, 
					"scale=".$scale,	
					"maxfps=".$fps,
					"buffer=1000" 
				) );
			noCacheHeaders();
			xhtmlHeaders( __FILE__, "Stream" );
			logXml("Streaming MJPEG on Monitor ".$monitor.", ".$width."x".$height." @".$fps."fps");
			echo "<body>\n";
			echo "<div style=\"border: 0px solid; padding: 0px; background-color: black; position: absolute; top: 0px; left; 0px; margin: 0px; width: ".$width."px; height: ".$height."px;\">\n";
			logXml("Using stream source: ".$streamSrc);
			outputImageStream("liveStream", $streamSrc, $width, $height, "stream");
			echo "</div></body></html>";
		} else {
			logXmlErr("Unsupported codec ".$vcodec." selected for streaming");
			echo("Unsupported codec ".$vcodec." selected for streaming");
		}
		exit;

	} else if (strcmp($action, "vevent") == 0) {
		/* ACTION: View an event. Parms: <eid> [fps|vcodec|br] */
		if (!canView('Events')) {
			logXmlErr("User ".$user['Username']. " doesn't have view Events perms");
			exit;
		}
		if (!isset($_GET['eid'])) {
			logXmlErr("Not all parameters set for Action View-event");
			exit;
		}
		/* Grab event from the database */
		$eventsSql = "select E.Id, E.MonitorId, E.Name, E.StartTime, E.Length, E.Frames from Events as E where (E.Id = ".$_GET['eid'].")";
		$event = dbFetchOne($eventsSql);
		/* Calculate FPS */
		$fps = getset('fps',ceil($event['Frames'] / $event['Length']));
		$vcodec = getset('vcodec', ZM_XML_EVENT_VCODEC);
		$baseURL = ZM_PATH_WEB."/".getEventPathSafe($event);
		/* Here we validate the codec.
		 * Check that FFMPEG exists and supports codecs */
		if (!strcmp($vcodec, "mpeg4")) {
			if (!ffmpegSupportsCodec("mpeg4")) {
				logXmlErr("FFMPEG not installed, accessible in path/ZM_PATH_FFMPEG, or doesn't support mpeg4");
				exit;
			}
			/* Can generate, we are good to go */
			$fname = "capture.mov";
			$ffparms = "-vcodec mpeg4 -r ".ZM_XML_EVENT_FPS." ".$baseURL."/".$fname." 2> /dev/null";

		} else if (!strcmp($vcodec, "h264")) {
			if (!ffmpegSupportsCodec("libx264")) {
				logXmlErr("FFMPEG not installed, accessible in path/ZM_PATH_FFMPEG, or doesn't support H264");
				exit;
			}
			if (!requireVer("1","2")) {
				logXmlErr("H264 Event viewing requires eyeZm v1.2 or greater");
				exit;
			}
			/* Good to go */
			$fname = "capture.mp4";
			$ffparms = getFfmpeg264FoutParms(
				getset('br',ZM_XML_H264_DEFAULT_EVBR),
				$baseURL."/".$fname);

		} else {
			logXmlErr("Unknown codec ".$vcodec." selected for event viewing");
			exit;
		}
		logXml("Selected ".$vcodec." for viewing event ".$event['Id']);
		$fnameOut = $baseURL."/".$fname;
		$shellCmd = getFfmpegPath()." -y -r ".$fps." -i ".$baseURL."/%03d-capture.jpg";
		$shellCmd .= " ".$ffparms;
		logXml("Encoding event with command: ".$shellCmd);
		$shellOutput = shell_exec($shellCmd);
		/* Check that file exists */
		if (!file_exists(trim($fnameOut))) {
			logXmlErr("Generate Event ".$event['Id']." file ".$fnameOut." does not exist");
			exit;
		}
		$url = "./".getEventPathSafe($event)."/".$fname;
		logXml("Loading Event URL ".$url);
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "vframe") == 0) {
		/* ACTION: View a frame given by an event and frame-id. Parms: <eid> <frame> [alarm | analyze]
		 * If 'alarm' is set, the returned frame will be the <frame>-th alarm frame. If 'analyze' is set,
		 * the returned frame will be the %03d-analyse frame instead of %03d-capture, if ZM_CREATE_ANALYSIS_IMAGES
		 * is set. Otherwise it just returns the captured frame */
		if (!isset($_GET['eid']) || !isset($_GET['frame'])) {
			logXmlErr("Not all parameters set for action view-frame");
			exit;
		}
		$eid = $_GET['eid'];
		$eventsSql = "select E.Id, E.MonitorId, E.Name, E.StartTime, E.Length, E.Frames from Events as E where (E.Id = ".$_GET['eid'].")";
		$event = dbFetchOne($eventsSql);
		/* Figure out the frame number. If 'alarm' is not set, this is just equal to the <frame> parameter.
		 * If 'alarm' is set, need to query DB and grab the <frame>-th item */
		if (isset($_GET['alarm'])) {
			$frameSql = "select * from Frames as F where (F.EventId = ".$eid.") and (F.Type = 'Alarm') order by F.FrameId";
			$i=0;
			$frame = 0;
			foreach (dbFetchAll($frameSql) as $dbframe) {
				if ($i == $_GET['frame']) {
					$frame = $dbframe['FrameId'];
					break;
				}
				$i++;
			}
		} else {
			$frame = $_GET['frame'];
		}
		if (isset($_GET['analyze']) && ZM_CREATE_ANALYSIS_IMAGES) {
			$suffix = "analyse";
		} else {
			$suffix = "capture";
		}
		/* Suffix based on 'analyze' */
		$fname = sprintf("%03d-%s.jpg", $frame, $suffix);
		$url = "./".getEventPathSafe($event)."/".$fname;
		if (!file_exists($url)) {
			$url = "./skins/xml/views/notfound.png";
		}
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "state") == 0) {
		/* ACTION: Change the state of the system. Parms: <state> */
		if (!canEdit('System')) {
			logXmlErr("User ".$user['Username']. " doesn't have edit System perms");
			exit;
		}
		if (!isset($_GET['state'])) {
			logXmlErr("Server state not specified for action");
			exit;
		}
		$url = "./index.php?view=none&action=state&runState=".$_GET['state'];
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "func") == 0) {
		/* ACTION: Change state of the monitor. Parms: <mid><func><en> */
		if (!canEdit('Monitors')) {
			logXmlErr("User ".$user['Username']. " doesn't have monitors Edit perms");
			exit;
		}
		if (!isset($_GET['mid']) || !isset($_GET['func']) || !isset($_GET['en'])) {
			logXmlErr("Not all parameters specified for action Monitor state");
			exit;
		}
		$url = "./index.php?view=none&action=function&mid=".$_GET['mid']."&newFunction=".$_GET['func']."&newEnabled=".$_GET['en'];
		header("Location: ".$url);
		exit;
	} else if (strcmp($action, "vlog") == 0) {
		/* ACTION: View log file. Must have debug and log to file enabled, and sufficient perms 
		 * Parms: [lines] */
		if (!canEdit('System')) {
			logXmlErr("Insufficient permissions to view log file");
			echo "Insufficient permissions to view log file";
			exit;
		}
		if (!ZM_XML_DEBUG || !ZM_XML_LOG_TO_FILE) {
			echo "XML Debug (XML_DEBUG) or log-to-file (XML_LOG_TO_FILE) not enabled. Please enable first";
			exit;
		}
		if (!file_exists(ZM_XML_LOG_FILE)) {
			echo "Log file ".ZM_XML_LOG_FILE." doesn't exist";
			exit;
		}
		$lines = getset('lines',ZM_XML_LOG_LINES);
		logXml("Returning last ".$lines." lines of XML Log from ".ZM_XML_LOG_FILE);
		echo shell_exec("tail -n ".$lines." ".ZM_XML_LOG_FILE);
		echo "\n\n--- Showing last ".$lines." lines ---\n";
		echo "--- End of Log ---\n\n";
	}
}
?>
