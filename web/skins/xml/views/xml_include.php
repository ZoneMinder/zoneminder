<?php
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
?>
