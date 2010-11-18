<?php
/* 
 * functions.php is created by Jai Dhar, FPS-Tech, for use with eyeZm
 * iPhone application. This is not intended for use with any other applications,
 * although source-code is provided under GPL.
 *
 * For questions, please email jdhar@eyezm.com (http://www.eyezm.com)
 *
 */

/* There appears to be some discrepancy btw. 1.24.1/2 and .3 for EventPaths, to escape them here */
function getEventPathSafe($event)
{
	if (ZM_USE_DEEP_STORAGE) {
		$ret = ZM_DIR_EVENTS."/".$event['MonitorId'].'/'.strftime( "%y/%m/%d/%H/%M/%S", strtotime($event['StartTime']) );
	} else {
		$ret = ZM_DIR_EVENTS."/".$event['MonitorId']."/".$event['Id'];
	}
	return $ret;
}
function updateClientVer()
{
	$str = $_SERVER['HTTP_USER_AGENT'];
	/* Check if it starts with eyeZm */
	if (!strcmp(substr($str, 0, 5),"eyeZm")) {
		/* Found eyeZm */
		$ver = substr($str, 6);
		$verarray = explode(".", $ver);
		$_SESSION['vermaj']=$verarray[0];
		$_SESSION['vermin']=$verarray[1];
		$_SESSION['verbuild']=$verarray[2];
	}
}
function getClientVerMaj()
{
	if (isset($_SESSION['vermaj'])) return $_SESSION['vermaj'];
	return "0";
}
function getClientVerMin()
{
	if (isset($_SESSION['vermin'])) return $_SESSION['vermin'];
	return "0";
}
function requireVer($maj, $min)
{
	if (getClientVerMaj() > $maj) return 1;
	if ((getClientVerMaj() == $maj) && (getClientVerMin() >= $min)) return 1;
	return 0;
}
function logXmlErr($str)
{
	error_log("XML_LOG (ERR): ".$str);
}
function logXml($str)
{
	if (!defined("ZM_XML_DEBUG")) define ( "ZM_XML_DEBUG", "0" );
	if (ZM_XML_DEBUG == 1) trigger_error("XML_LOG: ".$str, E_USER_NOTICE);
}
/* Returns defval if varname is not set, otherwise return varname */
function getset($varname, $defval)
{
	if (isset($_GET[$varname])) return $_GET[$varname];
	return $defval;
}
function xml_header()
{
	header ("content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
}
function xml_tag_val($tag, $val)
{
	echo "<".$tag.">".$val."</".$tag.">";
	//echo "&lt;".$tag."&gt;".$val."&lt;/".$tag."&gt<br>";
}
function xml_tag_sec($tag, $open)
{
	if ($open) $tok = "<";
	else $tok = "</";
	echo $tok.$tag.">";
}
function xhtmlHeaders( $file, $title )
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
body {
	border: 0px solid;
	margin: 0px;
	padding: 0px;
}
</style>
	<script type="text/javascript">
	</script>
</head>
<?php
}
/** Returns whether necessary components for MPEG-4 event-generation are present */
function canGenerateMpeg4() {
	/* Check for ffmpeg */
	$res = shell_exec("which ffmpeg");
	if ($res == "") {
		logXml("ZMSTREAMER not installed, cannot generate MPEG-4");
		return 0;
	}
	/* Check for libx264 support */
	$res = shell_exec("ffmpeg -codecs 2> /dev/null | grep mpeg4");
	if ($res == "") {
		logXml("FFMPEG doesn't support MPEG-4");
		return 0;
	}
	return 1;
}
/** Returns whether necessary components for H264 event-generation are present */
function canGenerateH264() {
	/* Check for ffmpeg */
	$res = shell_exec("which ffmpeg");
	if ($res == "") {
		logXml("ZMSTREAMER not installed, cannot stream H264");
		return 0;
	}
	/* Check for libx264 support */
	$res = shell_exec("ffmpeg -codecs 2> /dev/null | grep libx264");
	if ($res == "") {
		logXml("FFMPEG doesn't support libx264");
		return 0;
	}
	return 1;
}
/** Returns whether necessary components for H264 streaming
 * are present */
function canStream264() {
	/* Make sure segmenter exists */
	$res = shell_exec("which segmenter");
	if ($res == "") {
		logXml("H264 Requested, but segmenter not installed.");
		return 0;
	}
	/* Check for zmstreamer */
	$res = shell_exec("which zmstreamer");
	if ($res == "") {
		logXml("ZMSTREAMER not installed, cannot stream H264");
		return 0;
	}
	if (!canGenerateH264()) {
		return 0;
	}
	logXml("Determined can stream for H264");
	return 1;
}
function getFfmpeg264FoutParms($br, $fout)
{
	$ffparms = "-analyzeduration 0 -acodec copy -s 320x240";
	$ffparms .= " -vcodec libx264 -b ".$br;
	$ffparms .= " -flags +loop -cmp +chroma -partitions +parti4x4+partp8x8+partb8x8";
        $ffparms .= " -subq 5 -trellis 1 -refs 1 -coder 0 -me_range 16 -keyint_min 25";
        $ffparms .= " -sc_threshold 40 -i_qfactor 0.71 -bt 16k";
	$ffparms .= " -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6";
	$ffparms .= " -qmin 10 -qmax 51 -qdiff 4 -level 30";
	$ffparms .= " -g 30 -analyzeduration 0 -async 2 ".$fout.(ZM_XML_DEBUG?"":" 2> /dev/null");
	return $ffparms;
}
/** Return FFMPEG parameters for H264 streaming */
function getFfmpeg264Str($width, $height, $br, $fin, $fout)
{
	$ffparms = getFfmpeg264FoutParms($br, $fout);
	$ffstr = "ffmpeg -t ".ZM_XML_H264_MAX_DURATION." -analyzeduration 0 -i ";
	$ffstr .= $fin." -f mpegts ".$ffparms;
	return $ffstr;
}
/** Returns the width and height of a monitor */
function getMonitorDims($monitor)
{
	$query = "select Width,Height from Monitors where Id = ".$monitor;
	$res = dbFetchOne($query);
	return $res;
}
/** Returns the temp directory for H264 encoding */
function getTempDir()
{
	/* Assume that the directory structure is <base>/skins/xml/views */
	return dirname(__FILE__)."/../../../temp";
}
/** Returns the name of the m3u8 playlist based on monitor */
function m3u8fname($monitor) {
	return "stream_".$monitor.".m3u8";
}

/** Erases the M3u8 and TS file names for a given monitor */
function eraseH264Files($monitor) {
	/* Remove wdir/.m3u8 and wdir/sample_<mon>*.ts */
	shell_exec("rm -f ".getTempDir()."/".m3u8fname($monitor)." ".getTempDir()."/sample_".$monitor."*.ts");
}
function kill264proc($monitor) {
	$pid = trim(shell_exec("pgrep -f -x \"zmstreamer -m ".$monitor."\""));
	if ($pid == "") {
		logXml("No PID found for ZMStreamer to kill");
	} else {
		shell_exec("kill -9 ".$pid);
		logXml("Killed process ".$pid." for Monitor ".$monitor);
	}
}
/** Return the command-line shell function to setup H264 stream */
function stream264fn ($mid, $width, $height, $br) {
	$cdir = "./temp";
	$zmstrm = "zmstreamer -m ".$mid.(ZM_XML_DEBUG?"":" 2> /dev/null");
	$ffstr = getFfmpeg264Str($width, $height, $br, "-", "-");
	$seg = "segmenter - ".ZM_XML_SEG_DURATION." ".$cdir."/sample_".$mid." ".$cdir."/".m3u8fname($mid)." ../".(ZM_XML_DEBUG?"":" 2> /dev/null");
	$url = $zmstrm . " | ".$ffstr." | " . $seg;
	return "nohup ".$url." & echo $!";
}

/** Generate the web-page presented to the viewer when using H264 */
function h264vidHtml($width, $height, $monitor, $br, $thumbsrc) {
	function printTermLink() {
		$str = "H264 Stream Terminated<br>Click to Reload";
		$str2 = "document.getElementById(\"loaddiv\").innerHTML = \"".$str."\";";
		echo $str2;

	}
	$ajaxUrl = "?view=actions&action=spawn264&&monitor=".$monitor."&br=".$br;
	/* Call these two directly to bypass server blocking issues */
	$ajax2Url = "./skins/xml/views/actions.php?action=chk264&monitor=".$monitor;
	$ajax2Url .= "&timeout=".ZM_XML_H264_TIMEOUT;
	$ajax3Url = "./skins/xml/views/actions.php?action=kill264&monitor=".$monitor;
?>
<html>
<head>
	<script type="text/javascript">
	/* Called when paused or done is pressed */
	function vidAbort() {
		document.getElementById('viddiv').style.display = 'none';
		var pElement = document.getElementsByTagName('video')[0];
		var ajaxKill = new AjaxConnection("<?php echo $ajax3Url;?>");
		ajaxKill.connect("cbKilled");
		pElement.stop();
		pElement.src="";
		
	}
	function reloadStreamImage() {
		var obj = document.getElementById('liveStream');
		var src = obj.src;
		var date = new Date();
		obj.src = src + '&vrand=' + date.getTime();
		return false;
	}
	/* Callback when spawn264 process is ended */
	function cbVidLoad()
	{
		reloadStreamImage();
<?php 
		printTermLink(); 
?>
	}
	function vidLoaded() {
		window.setTimeout("startVid()", 500);
	}
	function bindListeners()
	{
		var pElement = document.getElementsByTagName('video')[0];
		/* Bind abort */
		pElement.addEventListener('abort', vidAbort, false);
		pElement.addEventListener('done', vidAbort, false);
		pElement.addEventListener('ended', vidAbort, false);
		pElement.addEventListener('pause', vidAbort, false);
		pElement.addEventListener('loadstart', vidLoaded, false);
	}
	/* Callback when kill264 process is ended */
	function cbKilled()
	{
		<?php printTermLink(); ?>
	}
	/* Called after an interval from cbFileExists() */
	function loadVid()
	{
		var pElement = document.getElementById("vidcontainer");
<?php
		echo "pElement.src=\"./temp/".m3u8fname($monitor)."\"\n";
?>
		pElement.load();
	}
	function startVid()
	{
		document.getElementById('viddiv').style.display = 'block';
		var pElement = document.getElementById("vidcontainer");
		pElement.play();
	}
	/* Callback when stream is active and ready to be played */
	function cbFileExists()
	{
		window.setTimeout("loadVid()", 500);
	}
	/* On-load triggers two requests immediately: spawn264 and chk264 */
	window.onload = function() {
		bindListeners();
		var ajax1 = new AjaxConnection("<?php echo "$ajaxUrl";?>");
		var ajax2 = new AjaxConnection("<?php echo "$ajax2Url";?>");
		ajax1.connect("cbVidLoad");
		/* Don't initiate file-exists since eyeZm will */
		/*ajax2.connect("cbFileExists");*/
	}
	function AjaxConnection(url) {
		this.connect = connect;
		this.url = url;
	}
	function connect(return_func) {
		this.x = new XMLHttpRequest();
		this.x.open("GET", this.url, true);
		var self = this;
		this.x.onreadystatechange = function() {
			if (self.x.readyState != 4)
				return;
			eval(return_func + '()');
			delete self.x;
		}
		this.x.send(null);
	}
	</script>
<style type="text/css">
body {
	border: 0px solid;
	margin: 0px;
	padding: 0px;
	background-color: black;
	width: <?php echo $width ?>px;
	height: <?php echo $height ?>px;
}
.textcl {
	text-align: center;
	font-family: Arial;
	font-size: larger;
	width: 100%;
<?php echo "padding-top: ".(($height/2)-100)."px;"; ?>
<?php echo "padding-bottom: ".(($height/2)-100)."px;"; ?>
	z-index: 2;
	position: absolute;
	top: 0px;
	left: 0px;
	height: 100%;
}
.textcl2 {
	width: auto;
	height: auto;
	background-color: black;
	padding: 5px 5px;
	margin-left: 10px;
	margin-right: 10px;
	opacity: 0.7;
}
.textcl3 {
	width: auto;
	height: auto;
	padding: 2px 2px;
	margin: auto;
	color: white;
}
.imgdiv {
	position: absolute;
	padding: 0px;
	background-color: black;
	top: 0px;
	left: 0px;
	margin: 0px;
	width: <?php echo $width ?>px;
	height: <?php echo $height ?>px;
	z-index: 1;
	opacity: 0.7;
}

</style>
</head>
<body>
<div id="viddiv" style="display: none;">
<?php
		echo "<video id=\"vidcontainer\" width='".$width."' height='".$height."' />\n";
?>
</div>
<div id="loaddiv2" class="textcl"><div id="loaddiv3" class="textcl2">
<div id="loaddiv" class="textcl3">
Initializing H264 Stream (<?php echo($br); ?>)...<br>
<span style="font-size: small;"><i>This may take a few seconds</i></span>
</div>
</div></div>

<div class="imgdiv" id="imagediv">
<?php outputImageStream("liveStream", $thumbsrc, $width, $height, "stream"); ?>
</div>
</body>
</html>
<?php
}
?>
