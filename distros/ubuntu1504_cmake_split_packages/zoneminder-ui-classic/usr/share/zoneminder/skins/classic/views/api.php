<?php
header('Content-type: text/xml');
header('Pragma: public');        
header('Cache-control: private');
header('Expires: -1');
echo('<?xml version="1.0" encoding="utf-8"?>');
?>
<response>
<?php
function make_tag($name,$value)
{
	return "<$name>" . $value . "</$name>";
}

if ($_REQUEST['action'] == "zone_info")
{
    if ( !canView( "Monitors" ) )
        ajaxError( 'Unrecognised action or insufficient permissions' );
	$mid = validInt($_REQUEST['mid']);
	$monitor = dbFetchMonitor( $mid );
	$sql = "select * from Zones where MonitorId = '".$mid."' order by Area desc";
	foreach( dbFetchAll( $sql ) as $zone )
	{
		$zone_str = "";
		$zone_str .= make_tag("id",$zone['Id']);
		$zone_str .= make_tag("name",$zone['Name']);
		$zone_str .= make_tag("method",$zone['CheckMethod']);
		$zone_str .= make_tag("color",(($zone['AlarmRGB']>>16)&0xff) . "," . (($zone['AlarmRGB']>>8)&0xff) . "," . (($zone['AlarmRGB'])&0xff) );
		$zone_str .= make_tag("MinPixelThreshold",$zone['MinPixelThreshold']);
		$zone_str .= make_tag("MaxPixelThreshold",$zone['MaxPixelThreshold']);
		$zone_str .= make_tag("MinAlarmPixels",$zone['MinAlarmPixels']);
		$zone_str .= make_tag("MaxAlarmPixels",$zone['MaxAlarmPixels']);
		$zone_str .= make_tag("MinFilterPixels",$zone['MinFilterPixels']);
		$zone_str .= make_tag("MaxFilterPixels",$zone['MaxFilterPixels']);
		$zone_str .= make_tag("MinBlobPixels",$zone['MinBlobPixels']);
		$zone_str .= make_tag("MaxBlobPixels",$zone['MaxBlobPixels']);
		$zone_str .= make_tag("MinBlobs",$zone['MinBlobs']);
		$zone_str .= make_tag("MaxBlobs",$zone['MaxBlobs']);
		$zone_str .= make_tag("cord",$zone['Coords']);
		print make_tag("zone",$zone_str);
	}
}
else if ($_REQUEST['action'] == "list_monitors")
{
    if ( !canView( "Monitors" ) )
        ajaxError( 'Unrecognised action or insufficient permissions' );
	$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
	for ( $i = 0; $i < count($monitors); $i++ )
	{
		$monitor = $monitors[$i];
		$monitor_str = "";
		$monitor_str .= make_tag("id",$monitor['Id']);
		$monitor_str .= make_tag("name",$monitor['Name']);
		$monitor_str .= make_tag("method",$monitor['Function']);
		$monitor_str .= make_tag("enabled",$monitor['Enabled']);
		$monitor_str .= make_tag("width",$monitor['Width']);
		$monitor_str .= make_tag("height",$monitor['Height']);
		print make_tag("monitor",$monitor_str);
	}
}
else if ($_REQUEST['action'] == "get_connkey")
{
	print make_tag("connkey",generateConnKey());
}
else if ($_REQUEST['action'] == "event_stats"){
    if ( !canView( "Events" ) )
        ajaxError( 'Unrecognised action or insufficient permissions' );
	$eid = validInt($_REQUEST['eid']);
	$stats = dbFetchAll( "select S.*,E.*,Z.Name as ZoneName,Z.Units,Z.Area,M.Name as MonitorName,M.Width,M.Height from Stats as S left join Events as E on S.EventId = E.Id left join Zones as Z on S.ZoneId = Z.Id left join Monitors as M on E.MonitorId = M.Id where S.EventId = '".$eid."' order by S.FrameId, S.ZoneId" );
	for ( $i = 0; $i < count($stats); $i++ )
	{
		$stat = $stats[$i];
		$stat_str = "";
		$stat_str .= make_tag("FrameId",$stat["FrameId"]);
		$stat_str .= make_tag("PixelDiff",$stat["PixelDiff"]);
		$stat_str .= make_tag("AlarmPixels",$stat["AlarmPixels"]);
		$stat_str .= make_tag("FilterPixels",$stat["FilterPixels"]);
		$stat_str .= make_tag("BlobPixels",$stat["BlobPixels"]);
		$stat_str .= make_tag("Blobs",$stat["Blobs"]);
		$stat_str .= make_tag("ZoneName",$stat["ZoneName"]);
		$stat_str .= make_tag("Score",$stat["Score"]);
		print make_tag("stat",$stat_str);

	}
}
?>
</response>
