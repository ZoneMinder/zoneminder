<?php
/* Parse any specific actions here */
if (isset($_GET['action'])) {
	$action = $_GET['action'];
	if (strcmp($action, "devent") == 0) {
		/* ACTION: Delete an Event */
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
		/* ACTION: View a feed */
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
		/* ACTION: View an event */
		if (!canView('Events')) {
			error_log("User ".$user['Username']. " doesn't have view Events perms");
			exit;
		}
		if (!isset($_GET['mid']) || !isset($_GET['eid']) || !isset($_GET['fps'])) {
			error_log("Not all parameters set for Action View-event");
			exit;
		}
		$baseURL = trim(shell_exec('pwd'))."/events/".$_REQUEST['mid']."/".$_REQUEST['eid']."/";
		$relativeURL = "./events/".$_REQUEST['mid']."/".$_REQUEST['eid']."/";
		$shellCmd = "ffmpeg -y -r ".$_REQUEST['fps']." -i ".$baseURL."%03d-capture.jpg -vcodec mpeg4 -r 10 ".$baseURL."capture.mov 2> /dev/null";
		shell_exec("rm -f ".$baseURL."capture.mov");
		$shellOutput = shell_exec($shellCmd);
		header("Location: ".$relativeURL."capture.mov");
	} else if (strcmp($action, "state") == 0) {
		/* ACTION: Change the state of the system */
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
		/* ACTION: Change state of the monitor */
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
