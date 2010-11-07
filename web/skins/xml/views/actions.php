<?php
/* Parse any specific actions here */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	if (strcmp($action, "devent") == 0) {
		/* ACTION: Delete an Event. Parms: <eid> */
		if (!canEdit('Events')) {
			error_log("User ".$user['Username']. " doesn't have edit Events perms");
			exit;
		}
		if (!isset($_REQUEST['eid'])) {
			error_log("EID not set for action delete-event");
			exit;
		}
		$eid = validInt($_REQUEST['eid']);
		$url = "./index.php?view=request&request=event&id=".$eid."&action=delete";
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "feed") == 0) {
		/* ACTION: View a feed. Parms: <monitor><img. width><img. height> [fps|scale] */
		if (!canView('Stream')) {
			error_log("User ".$user['Username']. " doesn't have view Stream perms");
			exit;
		}
		/* Check that required variables are set */
		if (!isset($_REQUEST['monitor']) || !isset($_GET['width']) || !isset($_GET['height'])) {
			error_log("Not all parameters set for action view-feed");
			exit;
		}
		$width = validInt($_GET['width']);
		$height = validInt($_GET['height']);
		$monitor = validInt($_REQUEST['monitor']);
		if (isset($_GET['fps'])) $fps = $_GET['fps'];
		else $fps = ZM_WEB_VIDEO_MAXFPS;
		if (isset($_GET['scale'])) $scale = $_GET['scale'];
		else $scale = 100;
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
		echo "<body>\n";
		echo "<div style=\"border: 0px solid; padding: 0px; background-color: black; position: absolute; top: 0px; left; 0px; margin: 0px; width: ".$width."px; height: ".$height."px;\">\n";
		outputImageStream("liveStream", $streamSrc, $width, $height, "stream");
		echo "</div></body></html>";
		exit;

	} else if (strcmp($action, "vevent") == 0) {
		/* ACTION: View an event. Parms: <eid> [fps|vcodec] */
		if (!canView('Events')) {
			error_log("User ".$user['Username']. " doesn't have view Events perms");
			exit;
		}
		if (!isset($_GET['eid'])) {
			error_log("Not all parameters set for Action View-event");
			exit;
		}
		/* Grab event from the database */
		$eventsSql = "select E.Id, E.MonitorId, E.Name, E.StartTime, E.Length, E.Frames from Events as E where (E.Id = ".$_GET['eid'].")";
		$event = dbFetchOne($eventsSql);
		/* Calculate FPS */
		$fps = getset('fps',ceil($event['Frames'] / $event['Length']));
		$vcodec = getset('vcodec', XML_EVENT_VCODEC);
		$relativeURL = getEventPath($event);
		$baseURL = ZM_PATH_WEB."/".ZM_DIR_EVENTS."/".getEventPath($event);
		$shellCmd = "ffmpeg -y -r ".$fps." -i ".$baseURL."/%03d-capture.jpg -vcodec ".$vcodec." -r ".XML_EVENT_FPS." ".$baseURL."/capture.mov 2> /dev/null";
		$shellOutput = shell_exec($shellCmd);
		$url = "./".ZM_DIR_EVENTS."/".getEventPath($event)."/capture.mov";
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "vframe") == 0) {
		/* ACTION: View a frame given by an event and frame-id. Parms: <eid> <frame> [alarm | analyze]
		 * If 'alarm' is set, the returned frame will be the <frame>-th alarm frame. If 'analyze' is set,
		 * the returned frame will be the %03d-analyse frame instead of %03d-capture, if ZM_CREATE_ANALYSIS_IMAGES
		 * is set. Otherwise it just returns the captured frame */
		if (!isset($_GET['eid']) || !isset($_GET['frame'])) {
			error_log("Not all parameters set for action view-frame");
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
		$url = "./".ZM_DIR_EVENTS."/".getEventPath($event)."/".$fname;
		if (!file_exists($url)) {
			$url = "./skins/xml/views/notfound.png";
		}
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "state") == 0) {
		/* ACTION: Change the state of the system. Parms: <state> */
		if (!canEdit('System')) {
			error_log("User ".$user['Username']. " doesn't have edit System perms");
			exit;
		}
		if (!isset($_GET['state'])) {
			error_log("Server state not specified for action");
			exit;
		}
		$url = "./index.php?view=none&action=state&runState=".$_GET['state'];
		header("Location: ".$url);
		exit;

	} else if (strcmp($action, "func") == 0) {
		/* ACTION: Change state of the monitor. Parms: <mid><func><en> */
		if (!canEdit('Monitors')) {
			error_log("User ".$user['Username']. " doesn't have monitors Edit perms");
			exit;
		}
		if (!isset($_GET['mid']) || !isset($_GET['func']) || !isset($_GET['en'])) {
			error_log("Not all parameters specified for action Monitor state");
			exit;
		}
		$url = "./index.php?view=none&action=function&mid=".$_GET['mid']."&newFunction=".$_GET['func']."&newEnabled=".$_GET['en'];
		header("Location: ".$url);
		exit;
	}
}
?>
