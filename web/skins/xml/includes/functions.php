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
	if (strcmp(ZM_VERSION, "1.24.3") == 0) {
		$ret = ZM_DIR_EVENTS."/".getEventPath($event);
	} else {
		$ret = getEventPath($event);
	}
	return $ret;
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
/** Returns whether necessary components for H264 streaming
 * are present */
function canStream264() {
	/* Make sure segmenter exists */
	$res = shell_exec("which segmenter");
	if ($res == "") {
		error_log("H264 Requested, but segmenter not installed.");
		return 0;
	}
	/* Check for zmstreamer */
	$res = shell_exec("which zmstreamer");
	if ($res == "") {
		error_log("ZMSTREAMER not installed, cannot stream H264");
		return 0;
	}
	/* Check for ffmpeg */
	$res = shell_exec("which ffmpeg");
	if ($res == "") {
		error_log("ZMSTREAMER not installed, cannot stream H264");
		return 0;
	}
	/* Check for libx264 support */
	$res = shell_exec("ffmpeg -codecs 2> /dev/null | grep libx264");
	if ($res == "") {
		error_log("FFMPEG doesn't support libx264");
		return 0;
	}
	logXml("Determined can stream for H264");
	return 1;
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
	$seg = "segmenter - ".ZM_XML_SEG_DURATION." ".$cdir."/sample_".$mid." ".$cdir."/".m3u8fname($mid)." ../".(ZM_XML_DEBUG?"":" 2> /dev/null");
	$ffparms = "-f mpegts -analyzeduration 0 -acodec copy -s ".$width."x".$height." -vcodec libx264 -b ".$br." -flags +loop -cmp +chroma -partitions +parti4x4+partp8x8+partb8x8 -subq 5 -trellis 1 -refs 1 -coder 0 -me_range 16 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -bt 200k -maxrate ".$br." -bufsize ".$br." -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -level 30 -aspect ".$width.":".$height." -g 30 -analyzeduration 0 -async 2 -".(ZM_XML_DEBUG?"":" 2> /dev/null");
	$url = $zmstrm . " | ffmpeg -t ".ZM_XML_H264_MAX_DURATION." -analyzeduration 0 -i - ". $ffparms . " | " . $seg;
	return "nohup ".$url." & echo $!";
}

/** Generate the web-page presented to the viewer when using H264 */
function h264vidHtml($width, $height, $monitor, $br) {
	$ajaxUrl = "?view=actions&action=spawn264&width=".$width."&height=".$height."&monitor=".$monitor."&br=".$br;
	/* Call these two directly to bypass server blocking issues */
	$ajax2Url = "./skins/xml/views/actions.php?action=chk264&monitor=".$monitor;
	$ajax3Url = "./skins/xml/views/actions.php?action=kill264&monitor=".$monitor;
?>
<html>
<head>
	<script type="text/javascript">
	/* Called when paused or done is pressed */
	function vidAbort() {
		document.getElementById("viddiv").style.display = "none";
		document.getElementById("loaddiv").style.display = "block";
		var pElement = document.getElementsByTagName('video')[0];
		var ajaxKill = new AjaxConnection("<?php echo $ajax3Url;?>");
		ajaxKill.connect("cbKilled");
		pElement.stop();
		pElement.src="";
		
	}
	/* Callback when spawn264 process is ended */
	function cbVidLoad()
	{
		document.getElementById("loaddiv").innerHTML = "H264 Stream Terminated";
	}
	function bindListeners()
	{
		var pElement = document.getElementsByTagName('video')[0];
		/* Bind abort */
		pElement.addEventListener('abort', vidAbort, false);
		pElement.addEventListener('done', vidAbort, false);
		pElement.addEventListener('ended', vidAbort, false);
		pElement.addEventListener('pause', vidAbort, false);
	}
	/* Callback when kill264 process is ended */
	function cbKilled()
	{
		document.getElementById("loaddiv").innerHTML = "H264 Stream Terminated";
	}
	function startVid()
	{
		var pElement = document.getElementById("vidcontainer");
		pElement.play();
	}
	/* Callback when stream is active and ready to be played */
	function cbFileExists()
	{
		document.getElementById("viddiv").style.display = "block";
		document.getElementById("loaddiv").style.display = "none";
		var pElement = document.getElementById("vidcontainer");
<?php
		echo "pElement.src=\"./temp/".m3u8fname($monitor)."\"\n";
?>
		pElement.load();
<?php if (ZM_XML_H264_AUTOPLAY == 1) { ?>
		window.setTimeout("startVid()", 1000);
<?php } ?>
	}
	/* On-load triggers two requests immediately: spawn264 and chk264 */
	window.onload = function() {
		bindListeners();
		var ajax1 = new AjaxConnection("<?php echo "$ajaxUrl";?>");
		var ajax2 = new AjaxConnection("<?php echo "$ajax2Url";?>");
		ajax1.connect("cbVidLoad");
		ajax2.connect("cbFileExists");
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
	color: white;
<?php echo "margin-top: ".($height/2)."px;"; ?>
}
</style>
</head>
<body>
<div id="viddiv" style="display: none;">
<?php
		echo "<video id=\"vidcontainer\" width='".$width."' height='".$height."' />\n";
?>
</div>
<div id="loaddiv" class="textcl">
Initializing H264 Stream...
</div>
</body>
</html>
<?php
}
?>
