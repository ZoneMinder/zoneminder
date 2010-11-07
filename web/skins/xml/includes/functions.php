<?php
function getEventPathSafe($event)
{
	if (strcmp(ZM_VERSION, "1.24.3") == 0) {
		$ret = ZM_DIR_EVENTS."/".getEventPath($event);
	} else {
		$ret = getEventPath($event);
	}
	return $ret;
}
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
	function ajax(str) {
		var xmlhttp;
		if (window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		} else if (window.ActiveXObject) {
			xmlhttp = new ActiveXObject("Microsoft.XMLHttp");
		} else {
		}
		if (xmlhttp == null) {
			return;
		}
		var url = str;
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState == 4 || xmlhttp.readyState == "complete") {
				alert('done');
			}
		}
		alert('sending url ' + str);
		xmlhttp.open("GET", str, true);
		xmlhttp.send(null);
	}
	</script>
</head>
<?php
}
?>
