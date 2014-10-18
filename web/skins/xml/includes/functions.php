<?php
/* 
 * functions.php is created by Jai Dhar, FPS-Tech, for use with eyeZm
 * iPhone application. This is not intended for use with any other applications,
 * although source-code is provided under GPL.
 *
 * For questions, please email support@eyezm.com (http://www.eyezm.com)
 *
 */
/* These functions are taken from functions.php */
function validInteger( $input )
{
    return( preg_replace( '/\D/', '', $input ) );
}

function validString( $input )
{
    return( strip_tags( $input ) );
}
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
	logXml("(".$_SERVER['REMOTE_ADDR'].") GET: ".$_SERVER['REQUEST_URI']." - eyeZm ".getClientVerMaj().".".getClientVerMin());
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
	logXml($str, 1);
}
function logXml($str, $err = 0)
{
	if (!defined("ZM_EYEZM_DEBUG")) {
		/* Check session variable */
		if (isset($_SESSION['xml_debug'])) define("ZM_EYEZM_DEBUG", $_SESSION['xml_debug']);
		else define ("ZM_EYEZM_DEBUG", "0");
	}
	if (!defined("ZM_EYEZM_LOG_TO_FILE")) {
		/* Check session variable */
		if (isset($_SESSION['xml_log_to_file'])) define("ZM_EYEZM_LOG_TO_FILE", $_SESSION['xml_log_to_file']);
		else define ("ZM_EYEZM_LOG_TO_FILE", "1");
	}
	if (!defined("ZM_EYEZM_LOG_FILE")) {
		/* Check session variable */
		if (isset($_SESSION['xml_log_file'])) define("ZM_EYEZM_LOG_FILE", $_SESSION['xml_log_file']);
		else define ("ZM_EYEZM_LOG_FILE", "/tmp/zm_xml.log");
	}
	/* Only log if debug is enabled */
	if (ZM_EYEZM_DEBUG == 0) return;
	/* Logging is enabled, set log string */
	$logstr = "XML_LOG (".($err?"ERROR":"NOTICE")."): ".$str.(ZM_EYEZM_LOG_TO_FILE?"\n":"");
	if (ZM_EYEZM_LOG_TO_FILE) {
		error_log("[".date("r")."] ".$logstr, 3, ZM_EYEZM_LOG_FILE);
	} else {
		error_log($logstr);
	}
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
function canStream264($sup = 0) {
	if (!ffmpegSupportsCodec("libx264")) {
		if (!$sup) logXmlErr("FFMPEG not installed, accessible in path/ZM_PATH_FFMPEG, or doesn't support libx264");
		return FALSE;
	}
	/* Make sure segmenter exists */
	if (!exeExists(shell_exec("which segmenter"))) {
		if (!$sup) logXmlErr("HTTP segmenter not installed or not accessible in path");
		return FALSE;
	}
	/* Check for zmstreamer */
	if (!exeExists(shell_exec("which zmstreamer"))) {
		if (!$sup) logXmlErr("ZMSTREAMER not installed or not accessible in path");
		return FALSE;
	}
	return TRUE;
}
/* Returns the path of ffmpeg by using define */
function getFfmpegPath()
{
	if (defined("ZM_PATH_FFMPEG")) {
		return ZM_PATH_FFMPEG;
	} else {
		/* Not defined, get it from using 'which' */
		return shell_exec("which ffmpeg");
	}
}
/* Returns whether ffmpeg supports a given codec. Takes into account
 * whether FFMPEG exists or not */
function ffmpegSupportsCodec($codec)
{
	if (!ffmpegExists()) return FALSE;
	/* FFMPEG exists */
	if (preg_match("/\b".$codec."\b/", shell_exec(getFfmpegPath()." -codecs 2> /dev/null")) > 0) {
		/* More than one match */
		return TRUE;
	} else {
		/* Check -formats tag also if we fail -codecs */
		if (preg_match("/\b".$codec."\b/", shell_exec(getFfmpegPath()." -formats 2> /dev/null")) > 0) return TRUE;
		return FALSE;
	}
}
function exeExists($exepath)
{
	$path = trim($exepath);
	return (file_exists($path) && is_readable($path) && ($path != ""));
}
/* Returns whether ffmpeg exists or not */
function ffmpegExists()
{
	return exeExists(getFfmpegPath());
}
/* Returns with PHP-GD exists */
function gdExists()
{
	if (extension_loaded('gd') && function_exists('gd_info')) {
		return TRUE;
	}
	return FALSE;
}
	
function getFfmpeg264FoutParms($br, $fout)
{
	$ffparms = "-analyzeduration 0 -acodec copy";
	$ffparms .= " -vcodec libx264 -b ".$br;
	$ffparms .= " -flags +loop -cmp +chroma -partitions +parti4x4+partp8x8+partb8x8";
        $ffparms .= " -subq 5 -trellis 1 -refs 1 -coder 0 -me_range 16 -keyint_min 25";
        $ffparms .= " -sc_threshold 40 -i_qfactor 0.71 -bt 16k";
	$ffparms .= " -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6";
	$ffparms .= " -qmin 10 -qmax 51 -qdiff 4 -level 30";
	$ffparms .= " -g 30 -analyzeduration 0 -async 2 ".$fout." 2> /dev/null";
	return $ffparms;
}
/** Return FFMPEG parameters for H264 streaming */
function getFfmpeg264Str($width, $height, $br, $fin, $fout)
{
	$ffparms = getFfmpeg264FoutParms($br, $fout);
	$ffstr = getFfmpegPath()." -t ".ZM_EYEZM_H264_MAX_DURATION." -analyzeduration 0 -i ";
	$ffstr .= $fin." -f mpegts ".$ffparms;
	return $ffstr;
}
/** Returns true when monitor exists */
function isMonitor($monitor)
{
	$query = "select Id from Monitors where Id = ?";
	$res = dbFetchOne($query, NULL, array($monitor));
	if ($res) return TRUE;
	logXml("Monitor ID ".$monitor." does not exist");	
	return FALSE;
}
/** Returns the width and height of a monitor */
function getMonitorDims($monitor)
{
	$query = "select Width,Height from Monitors where Id = ?";
	$res = dbFetchOne($query, NULL, array( $monitor ) );
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
	/** NOTE: This command executes an 'rm' command, so $monitor parameter
	 * should be properly validated before executing */
	/* Remove wdir/.m3u8 and wdir/sample_<mon>*.ts */
	shell_exec("rm -f ".getTempDir()."/".m3u8fname($monitor)." ".getTempDir()."/sample_".$monitor."*.ts");
}
function kill264proc($monitor) {
	/** NOTE: This command executes an 'kill' command, so $monitor parameter
	 * should be properly validated before executing */
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
	$zmstrm = "zmstreamer -m ".$mid." 2> /dev/null";
	$ffstr = getFfmpeg264Str($width, $height, $br, "-", "-");
	$seg = "segmenter - ".ZM_EYEZM_SEG_DURATION." ".$cdir."/sample_".$mid." ".$cdir."/".m3u8fname($mid)." ../ 2> /dev/null";
	$url = $zmstrm . " | ".$ffstr." | " . $seg;
	return "nohup ".$url." & echo $!";
}

/** Generate the web-page presented to the viewer when using H264 */
function h264vidHtml($width, $height, $monitor, $br, $thumbsrc) {
	function printTermLink() {
		$str = "H264 Streaming Launching...<br>Tap to re-load if stream fails";
		$str2 = "document.getElementById(\"loaddiv\").innerHTML = \"".$str."\";";
		echo $str2;

	}
	$ajaxUrl = "?view=actions&action=spawn264&&monitor=".$monitor."&br=".$br;
	/* Call these two directly to bypass server blocking issues */
	$ajax2Url = "./skins/xml/views/actions.php?action=chk264&monitor=".$monitor;
	$ajax2Url .= "&timeout=".ZM_EYEZM_H264_TIMEOUT;
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
