<?php
//
// ZoneMinder web export function library, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

function exportHeader( $title )
{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= $title ?></title>
<style type="text/css">
<!--
td {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size:10px;
	color: #333333;
	font-weight: normal
}   
a:link {
	color: #7F7FB2;
	text-decoration: none
}
a:visited {
	color: #7F7FB2;
	text-decoration: none
}
a:hover {
	color: #666699;
	text-decoration: underline
}
-->
</style>
</head>
<?php
}

function exportEventDetail( $event )
{
	global $export_detail, $export_frames, $export_images, $export_video, $export_misc;
	global $zmSlangEvent, $zmSlangId, $zmSlangName, $zmSlangMonitor, $zmSlangCause, $zmSlangNotes, $zmSlangTime, $zmSlangDuration;
	global $zmSlangFrames, $zmSlangAttrAlarmFrames, $zmSlangAttrTotalScore, $zmSlangAttrAvgScore, $zmSlangAttrMaxScore, $zmSlangArchived;
	global $zmSlangYes, $zmSlangNo;

	ob_start();
	exportHeader( $zmSlangEvent." ".$event['Id'] );
?>
<body>
<table>
<tr><td><?= $zmSlangId ?></td><td><?= $event['Id'] ?></td></tr>
<tr><td><?= $zmSlangName ?></td><td><?= $event['Name'] ?></td></tr>
<tr><td><?= $zmSlangMonitor ?></td><td><?= $event['MonitorName'] ?> (<?= $event['MonitorId'] ?>)</td></tr>
<tr><td><?= $zmSlangCause ?></td><td><?= $event['Cause'] ?></td></tr>
<tr><td><?= $zmSlangNotes ?></td><td><?= $event['Notes'] ?></td></tr>
<tr><td><?= $zmSlangTime ?></td><td><?= strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime']) ) ?></td></tr>
<tr><td><?= $zmSlangDuration ?></td><td><?= $event['Length'] ?></td></tr>
<tr><td><?= $zmSlangFrames ?></td><td><?= $event['Frames'] ?></td></tr>
<tr><td><?= $zmSlangAttrAlarmFrames ?></td><td><?= $event['AlarmFrames'] ?></td></tr>
<tr><td><?= $zmSlangAttrTotalScore ?></td><td><?= $event['TotScore'] ?></td></tr>
<tr><td><?= $zmSlangAttrAvgScore ?></td><td><?= $event['AvgScore'] ?></td></tr>
<tr><td><?= $zmSlangAttrMaxScore ?></td><td><?= $event['MaxScore'] ?></td></tr>
<tr><td><?= $zmSlangArchived ?></td><td><?= $event['Archived']?$zmSlangYes:$zmSlangNo ?></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<?php
	if ( $export_frames )
	{
?>
<tr><td colspan="2"><a href="zmEventFrames.html"><?= $zmSlangFrames ?></a></td></tr>
<?php
	}
?>
</table>
</body>
</html>
<?php
	return( ob_get_clean() );
}

function exportEventFrames( $event )
{
	global $export_detail, $export_frames, $export_images, $export_video, $export_misc;
	global $zmSlangFrames, $zmSlangFrameId, $zmSlangAlarmFrame, $zmSlangTimeStamp, $zmSlangTimeDelta, $zmSlangScore, $zmSlangImage;
	global $zmSlangYes, $zmSlangNo, $zmSlangNoFramesRecorded;

	$sql = "select *, unix_timestamp( TimeStamp ) as UnixTimeStamp from Frames where EventID = '".$event['Id']."' order by FrameId";
    $frames = dbFetchAll( $sql );

	ob_start();
	exportHeader( $zmSlangFrames." ".$event['Id'] );
?>
<body>
<table width="100%" border="0" bgcolor="#7F7FB2" cellpadding="3" cellspacing="1">
<tr bgcolor="#FFFFFF">
<td align="center"><?= $zmSlangFrameId ?></td>
<td align="center"><?= $zmSlangAlarmFrame ?></td>
<td align="center"><?= $zmSlangTimeStamp ?></td>
<td align="center"><?= $zmSlangTimeDelta ?></td>
<td align="center"><?= $zmSlangScore ?></td>
<?php
	if ( $export_images )
	{
?>
<td align="center"><?= $zmSlangImage ?></td>
<?php
	}
?>
</tr>
<?php
	if ( count($frames) )
	{
        $event_path = getEventPath( $event );
		foreach ( $frames as $frame )
		{
			$image_file = sprintf( "%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $frame['FrameId'] );
			$image_path = $event_path."/".$image_file;
			$anal_image = preg_replace( "/capture/", "analyse", $image_path );
			if ( file_exists( $anal_image ) )
			{
				$image_file = preg_replace( "/capture/", "analyse", $image_file );
			}

			$alarm_frame = $frame['Type']=='Alarm';
			$bgcolor = $alarm_frame?'#FA8072':($frame['Type']=='Bulk'?'#CCCCCC':'#FFFFFF');
?>
<tr bgcolor="<?= $bgcolor ?>">
<td align="center"><?= $frame['FrameId'] ?></td>
<td align="center"><?= $alarm_frame?$zmSlangYes:$zmSlangNo ?></td>
<td align="center"><?= strftime( STRF_FMT_TIME, $frame['UnixTimeStamp'] ) ?></td>
<td align="center"><?= number_format( $frame['Delta'], 2 ) ?></td>
<td align="center"><?= $frame['Score'] ?></td>
<?php
			if ( $export_images )
			{
?>
<td align="center"><a href="<?= $image_file ?>"><img src="<?= $image_file ?>" border="0" width="40" alt="Frame <?= $frame['FrameId'] ?>"></a></td>
<?php
			}
?>
</tr>
<?php
		}
	}
	else
	{
?>
<tr bgcolor="#FFFFFF">
<td class="text" colspan="<?= $export_images?6:5 ?>" align="center"><br><?= $zmSlangNoFramesRecorded ?><br><br></td>
</tr>
<?php
	}
?>
</table></td>
</tr>
</table>
</body>
</html>
<?php
	return( ob_get_clean() );
}

function exportFileList( $eid )
{
	global $export_detail, $export_frames, $export_images, $export_video, $export_misc;

	if ( canView( 'Events' ) && $eid )
	{
		$sql = "select E.Id,E.MonitorId,M.Name As MonitorName,M.Width,M.Height,E.Name,E.Cause,E.Notes,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where E.Id = '$eid'";
        $event = dbFetchOne( $sql );

        $event_path = getEventPath( $event );
		$files = array();
		if ( $dir = opendir( $event_path ) )
		{
			while ( ($file = readdir( $dir )) !== false )
			{
				if ( is_file( $event_path."/".$file ) )
				{
					$files[$file] = $file;
				}
			}
			closedir( $dir );
		}

		$export_file_list = array();

		if ( $export_detail )
		{
			$file = "zmEventDetail.html";
			if ( !($fp = fopen( $event_path."/".$file, "w" )) )
			{
				die( "Can't open event detail export file '$file'" );
			}
			fwrite( $fp, exportEventDetail( $event ) );
			fclose( $fp );
			$export_file_list[$file] = $event_path."/".$file;
		}
		if ( $export_frames )
		{
			$file = "zmEventFrames.html";
			if ( !($fp = fopen( $event_path."/".$file, "w" )) )
			{
				die( "Can't open event frames export file '$file'" );
			}
			fwrite( $fp, exportEventFrames( $event ) );
			fclose( $fp );
			$export_file_list[$file] = $event_path."/".$file;
		}

		if ( $export_images )
		{
			$files_left = array();
			foreach ( $files as $file )
			{
				if ( preg_match( "/-(?:capture|analyse).jpg$/", $file ) )
				{
					$export_file_list[$file] = $event_path."/".$file;
				}
				else
				{
					$files_left[$file] = $file;
				}
			}
			$files = $files_left;
		}
		if ( $export_video )
		{
			$files_left = array();
			foreach ( $files as $file )
			{
				if ( preg_match( "/\.(?:mpg|mpeg|avi|asf|3gp)$/", $file ) )
				{
					$export_file_list[$file] = $event_path."/".$file;
				}
				else
				{
					$files_left[$file] = $file;
				}
			}
			$files = $files_left;
		}
		if ( $export_misc )
		{
			foreach ( $files as $file )
			{
				$export_file_list[$file] = $event_path."/".$file;
			}
			$files = array();
		}
	}
	return( array_values( $export_file_list ) );
}

function exportEvents( $eids )
{
	global $export_format;

	if ( canView( 'Events' ) && $eids )
	{
		$export_root = "zmExport";
		$export_list_file = "zmFileList.txt";
		$export_file_list = array();

		if ( is_array( $eids ) )
		{
			foreach ( $eids as $eid )
			{
				$export_file_list = array_merge( $export_file_list, exportFileList( $eid ) );
			}
		}
		else
		{
			$eid = $eids;
			$export_file_list = exportFileList( $eid );
		}

		$list_file = "temp/".$export_list_file;
		if ( !($fp = fopen( $list_file, "w" )) )
		{
			die( "Can't open event export list file '$list_file'" );
		}
		foreach ( $export_file_list as $export_file )
		{
			fwrite( $fp, "$export_file\n" );
		}
		fclose( $fp );
		$archive = "not-specified";
		if ( $export_format == "tar" )
		{
			$archive = "temp/".$export_root.".tar.gz";
			@unlink( $archive );
			$command = "tar --create --gzip --file=$archive --files-from=$list_file";
			exec( $command, $output, $status );
			if ( $status )
			{
				error_log( "Command '$command' returned with status $status" );
				if ( $output[0] )
					error_log( "First line of output is '".$output[0]."'" );
				return( false );
			}
		}
		elseif ( $export_format == "zip" )
		{
			$archive = "temp/zm_export.zip";
			$archive = "temp/".$export_root.".zip";
			@unlink( $archive );
			$command = "cat $list_file | zip -q $archive -@";
			exec( $command, $output, $status );
			if ( $status )
			{
				error_log( "Command '$command' returned with status $status" );
				if ( $output[0] )
					error_log( "First line of output is '".$output[0]."'" );
				return( false );
			}
		}
	}
	return( $archive );
}
