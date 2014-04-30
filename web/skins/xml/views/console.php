<?php
//
// ZoneMinder web console file, $Date: 2009-02-19 10:05:31 +0000 (Thu, 19 Feb 2009) $, $Revision: 2780 $
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

/* 
 * console.php is updated by Jai Dhar, FPS-Tech, for use with eyeZm
 * iPhone application. This is not intended for use with any other applications,
 * although source-code is provided under GPL.
 *
 * For questions, please email support@eyezm.com (http://www.eyezm.com)
 *
 */
$eventCounts = array(
    array(
        "title" => $SLANG['Events'],
        "filter" => array(
            "terms" => array(
            )
        ),
    ),
    array(
        "title" => $SLANG['Hour'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 hour" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Day'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Week'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-7 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Month'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 month" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Archived'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "1" ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?$SLANG['Running']:$SLANG['Stopped'];

if ( $group = dbFetchOne( 'SELECT * FROM Groups WHERE Id = ?', NULL, array(empty($_COOKIE['zmGroup'])?0:$_COOKIE['zmGroup']) ) ) 
    $groupIds = array_flip(split( ',', $group['MonitorIds'] ));

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$minSequence = 0;
$maxSequence = 1;
$seqIdList = array();
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
    if ( !visibleMonitor( $monitors[$i]['Id'] ) )
    {
        continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) )
    {
        continue;
    }
    $monitors[$i]['Show'] = true;
    if ( empty($minSequence) || ($monitors[$i]['Sequence'] < $minSequence) )
    {
        $minSequence = $monitors[$i]['Sequence'];
    }
    if ( $monitors[$i]['Sequence'] > $maxSequence )
    {
        $maxSequence = $monitors[$i]['Sequence'];
    }
    if (isset($_GET['nostatus'])) {
	    $monitors[$i]['zmc'] = 1;
	    $monitors[$i]['zma'] = 1;
    } else {
	    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
	    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    }
    $monitors[$i]['ZoneCount'] = dbFetchOne( 'select count(Id) as ZoneCount from Zones where MonitorId = ?', 'ZoneCount', array($monitors[$i]['Id']) );
    $counts = array();
    for ( $j = 0; $j < count($eventCounts); $j++ )
    {
        $filter = addFilterTerm( $eventCounts[$j]['filter'], count($eventCounts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['eventCounts'][$j]['filter'] = $filter;
    }
    $sql = 'SELECT '.join($counts,", ").' from Events as E where MonitorId = ?';
    $counts = dbFetchOne( $sql, NULL, array( $monitors[$i]['Id'] ) );
    if ( $monitors[$i]['Function'] != 'None' )
    {
        $cycleCount++;
        $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
        if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
    }
    $monitors[$i] = array_merge( $monitors[$i], $counts );
    $seqIdList[] = $monitors[$i]['Id'];
    $displayMonitors[] = $monitors[$i];
}
$states = dbFetchAll("select * from States");
/* XML Dump Starts here */
xml_header();
/* Print out the general section */
xml_tag_sec("ZM_XML", 1);
xml_tag_sec("GENERAL", 1);
xml_tag_val("RUNNING", $running);
xml_tag_val("PROTOVER", ZM_EYEZM_PROTOCOL_VERSION);
xml_tag_val("FEATURESET", ZM_EYEZM_FEATURE_SET);
xml_tag_val("VERSION", ZM_VERSION);
xml_tag_val("CANSTR264", canStream264(1));
xml_tag_val("GD", gdExists());
xml_tag_val("FVCODEC", ZM_EYEZM_FEED_VCODEC);
xml_tag_val("FVTMT", ZM_EYEZM_H264_TIMEOUT);
xml_tag_val("USER", $user['Username']);
xml_tag_val("UID", $user['Id']);
/* Permissions block */
xml_tag_sec("PERMS", 1);
xml_tag_val("STREAM", $user['Stream']);
xml_tag_val("EVENTS", $user['Events']);
xml_tag_val("CONTROL", $user['Control']);
xml_tag_val("MONITORS", $user['Monitors']);
xml_tag_val("DEVICES", $user['Devices']);
xml_tag_val("SYSTEM", $user['System']);
xml_tag_sec("PERMS", 0);
/* End permissions block */
if (canEdit('System')) {
	if ($running) {
		xml_tag_val("STATE", "stop");
		xml_tag_val("STATE", "restart");
	} else {
		xml_tag_val("STATE", "start");
	}
	foreach ($states as $state) {
		xml_tag_val("STATE", $state['Name']);
	}
}
/* End general section */
xml_tag_sec("GENERAL", 0);
/* Print out the monitors section */
xml_tag_sec("MONITOR_LIST", 1);
foreach( $displayMonitors as $monitor )
{
	if (!canView('Monitors')) continue;
	xml_tag_sec("MONITOR", 1);
	xml_tag_val("ID", $monitor['Id']);
	xml_tag_val("NAME", $monitor['Name']);
	xml_tag_val("FUNCTION", $monitor['Function']);
	xml_tag_val("NUMEVENTS", $monitor['EventCount0']);
	xml_tag_val("ENABLED", $monitor['Enabled']);
	xml_tag_val("ZMC", $monitor['zmc']);
	xml_tag_val("ZMA", $monitor['zma']);
	xml_tag_val("STATE", ($monitor['zmc']!=1)?"ERROR":(
		($monitor['zma']==1)?"OK":"WARN"));
	xml_tag_val("WIDTH", $monitor['Width']);
	xml_tag_val("HEIGHT", $monitor['Height']);

	/* Form the data-base query for this monitor */
	$pageOffset = 0;
	$offset = 0;
	if (isset($_GET['numEvents'])) {
		$numEvents = validInteger($_GET['numEvents']);
		$eventsSql = "select E.Id,E.MonitorId,M.Name As MonitorName,E.Cause,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived from Monitors as M inner join Events as E on (M.Id = E.MonitorId) and ( E.MonitorId = ? ) order by E.StartTime desc";
		$eventsSql .= " limit ".$numEvents;
		/* If there is an pageOff<x> tag for this monitor, then retrieve the offset. Otherwise, don't specify offset */
		if (isset($_GET['pageOff'.$monitor['Id']])) {
			/* If pageOffset is greater than we actually have,
			 * we need to adjust it */
			$pageOffset = validInteger($_GET['pageOff'.$monitor['Id']]); 
			if ($pageOffset >= ceil($monitor['EventCount0']/$numEvents)) {
				$pageOffset = 0;
			}
			$offset = $pageOffset * $numEvents;
		}
		$eventsSql .= " offset ".$offset;
	} else {
		unset($eventsSql);
	}
	xml_tag_val("PAGEOFF", $pageOffset);
	xml_tag_sec("EVENTS",1);
	if (canView('Events') && isset($eventsSql)) {
		foreach ( dbFetchAll( $eventsSql, NULL, array($monitor['Id']) ) as $event )
		{
			xml_tag_sec("EVENT",1);
			xml_tag_val("ID",$event['Id']);
			xml_tag_val("NAME",$event['Name']);
			xml_tag_val("TIME", strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime'])));
			xml_tag_val("DURATION", $event['Length']);
			xml_tag_val("FRAMES", $event['Frames']);
			xml_tag_val("FPS", ($event['Length'] > 0)?ceil($event['Frames']/$event['Length']):0);
			xml_tag_val("TOTSCORE", $event['TotScore']);
			xml_tag_val("AVGSCORE", $event['AvgScore']);
			xml_tag_val("MAXSCORE", $event['MaxScore']);
			/* Grab the max frame-id from Frames table. If AlarmFrames = 0, don't try
			 * to grab any frames, and just signal the max frame index as index 0 */
			$fridx = 1;
			$alarmFrames = 1;
			if ($event['AlarmFrames']) {
				$framesSql = "SELECT FrameId FROM Frames WHERE (Type = 'Alarm') and (EventId = ?) ORDER BY Score DESC LIMIT 1";
				$fr = dbFetchOne($framesSql, NULL, array( $event['Id'] ) );
				$fridx = $fr['FrameId'];
				$alarmFrames = $event['AlarmFrames'];
			}
			xml_tag_val("ALARMFRAMES", $alarmFrames);
			xml_tag_val("MAXFRAMEID", $fridx);
			xml_tag_sec("EVENT",0);
		}
	}
	xml_tag_sec("EVENTS",0);
	xml_tag_sec("MONITOR", 0);
}
xml_tag_sec("MONITOR_LIST", 0);
xml_tag_sec("ZM_XML", 0);
?>
