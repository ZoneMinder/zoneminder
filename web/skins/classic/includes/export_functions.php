<?php
// Stagecoach Wireless Test Commit
// ZoneMinder web export function library, $Date$, $Revision$
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

function exportHeader( $title )
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?= $title ?></title>
  <style type="text/css">
  <!--
<?php include( ZM_SKIN_PATH.'/css/export.css' ); ?>
  -->
  </style>
</head>
<?php
}

function exportEventDetail( $event, $exportFrames )
{
    global $SLANG;

    ob_start();
    exportHeader( $SLANG['Event']." ".$event['Id'] );
?>
<body>
  <div id="page">
    <div id="content">
      <h2><?= $SLANG['Event'] ?>: <?= validHtmlStr($event['Name']) ?><?php if ( $exportFrames ) { ?> (<a href="zmEventFrames.html"><?= $SLANG['Frames'] ?></a>)<?php } ?></h2>
      <table id="eventDetail">
        <tr><th scope="row"><?= $SLANG['Id'] ?></th><td><?= $event['Id'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Name'] ?></th><td><?= validHtmlStr($event['Name']) ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Monitor'] ?></th><td><?= validHtmlStr($event['MonitorName']) ?> (<?= $event['MonitorId'] ?>)</td></tr>
        <tr><th scope="row"><?= $SLANG['Cause'] ?></th><td><?= validHtmlStr($event['Cause']) ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Notes'] ?></th><td><?= validHtmlStr($event['Notes']) ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Time'] ?></th><td><?= strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime']) ) ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Duration'] ?></th><td><?= $event['Length'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Frames'] ?></th><td><?= $event['Frames'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['AttrAlarmFrames'] ?></th><td><?= $event['AlarmFrames'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['AttrTotalScore'] ?></th><td><?= $event['TotScore'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['AttrAvgScore'] ?></th><td><?= $event['AvgScore'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['AttrMaxScore'] ?></th><td><?= $event['MaxScore'] ?></td></tr>
        <tr><th scope="row"><?= $SLANG['Archived'] ?></th><td><?= $event['Archived']?$SLANG['Yes']:$SLANG['No'] ?></td></tr>
      </table>
    </div>
  </div>
</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportEventFrames( $event, $exportImages )
{
    global $SLANG;

    $sql = "select *, unix_timestamp( TimeStamp ) as UnixTimeStamp from Frames where EventID = '".dbEscape($event['Id'])."' order by FrameId";
    $frames = dbFetchAll( $sql );

    ob_start();
    exportHeader( $SLANG['Frames']." ".$event['Id'] );
?>
<body>
  <div id="page">
    <div id="content">
      <h2><?= $SLANG['Frames'] ?>: <?= validHtmlStr($event['Name']) ?> (<a href="zmEventDetail.html"><?= $SLANG['Event'] ?></a>)</h2>
      <table id="eventFrames">
        <tr>
          <th><?= $SLANG['FrameId'] ?></th>
          <th><?= $SLANG['Type'] ?></th>
          <th><?= $SLANG['TimeStamp'] ?></th>
          <th><?= $SLANG['TimeDelta'] ?></th>
          <th><?= $SLANG['Score'] ?></th>
<?php
    if ( $exportImages )
    {
?>
          <th><?= $SLANG['Image'] ?></th>
<?php
    }
?>
        </tr>
<?php
    if ( count($frames) )
    {
        $eventPath = getEventPath( $event );
        foreach ( $frames as $frame )
        {
            $imageFile = sprintf( "%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $frame['FrameId'] );
            $imagePath = $eventPath."/".$imageFile;
            $analImage = preg_replace( "/capture/", "analyse", $imagePath );
            if ( file_exists( $analImage ) )
            {
                $imageFile = preg_replace( "/capture/", "analyse", $imageFile );
            }

            $class = strtolower($frame['Type']);
?>
        <tr class="<?= $class ?>">
          <td><?= $frame['FrameId'] ?></td>
          <td><?= $frame['Type'] ?></td>
          <td><?= strftime( STRF_FMT_TIME, $frame['UnixTimeStamp'] ) ?></td>
          <td><?= number_format( $frame['Delta'], 2 ) ?></td>
          <td><?= $frame['Score'] ?></td>
<?php
            if ( $exportImages )
            {
?>
          <td><a href="<?= $imageFile ?>" target="zmExportImage"><img src="<?= $imageFile ?>" border="0" class="thumb" alt="Frame <?= $frame['FrameId'] ?>"/></a></td>
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
        <tr>
          <td class="monoRow" colspan="<?= $exportImages?6:5 ?>"><?= $SLANG['NoFramesRecorded'] ?></td>
        </tr>
<?php
    }
?>
      </table>
    </div>
  </div>
</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportFileList( $eid, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc )
{

    if ( canView( 'Events' ) && $eid )
    {
        $sql = "select E.Id,E.MonitorId,M.Name As MonitorName,M.Width,M.Height,E.Name,E.Cause,E.Notes,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where E.Id = '".dbEscape($eid)."'";
        $event = dbFetchOne( $sql );

        $eventPath = ZM_DIR_EVENTS.'/'.getEventPath( $event );
        $files = array();
        if ( !($dir = opendir( $eventPath )) )
            die( "Can't open event path '$eventPath'" );
        while ( ($file = readdir( $dir )) !== false )
        {
            if ( is_file( $eventPath."/".$file ) )
            {
                $files[$file] = $file;
            }
        }
        closedir( $dir );

        $exportFileList = array();

        if ( $exportDetail )
        {
            $file = "zmEventDetail.html";
            if ( !($fp = fopen( $eventPath."/".$file, "w" )) )
                die( "Can't open event detail export file '$file'" );
            fwrite( $fp, exportEventDetail( $event, $exportFrames ) );
            fclose( $fp );
            $exportFileList[$file] = $eventPath."/".$file;
        }
        if ( $exportFrames )
        {
            $file = "zmEventFrames.html";
            if ( !($fp = fopen( $eventPath."/".$file, "w" )) )
            {
                die( "Can't open event frames export file '$file'" );
            }
            fwrite( $fp, exportEventFrames( $event, $exportImages ) );
            fclose( $fp );
            $exportFileList[$file] = $eventPath."/".$file;
        }
        if ( $exportImages )
        {
            $filesLeft = array();
            foreach ( $files as $file )
            {
                if ( preg_match( "/-(?:capture|analyse).jpg$/", $file ) )
                {
                    $exportFileList[$file] = $eventPath."/".$file;
                }
                else
                {
                    $filesLeft[$file] = $file;
                }
            }
            $files = $filesLeft;
        }
        if ( $exportVideo )
        {
            $filesLeft = array();
            foreach ( $files as $file )
            {
                if ( preg_match( "/\.(?:mpg|mpeg|avi|asf|3gp)$/", $file ) )
                {
                    $exportFileList[$file] = $eventPath."/".$file;
                }
                else
                {
                    $filesLeft[$file] = $file;
                }
            }
            $files = $filesLeft;
        }
        if ( $exportMisc )
        {
            foreach ( $files as $file )
            {
                $exportFileList[$file] = $eventPath."/".$file;
            }
            $files = array();
        }
    }
    return( array_values( $exportFileList ) );
}

function exportEvents( $eids, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc, $exportFormat )
{
    if ( canView( 'Events' ) && !empty($eids) )
    {
        $export_root = "zmExport";
        $export_listFile = "zmFileList.txt";
        $exportFileList = array();

        if ( is_array( $eids ) )
        {
            foreach ( $eids as $eid )
            {
                $exportFileList = array_merge( $exportFileList, exportFileList( $eid, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc ) );
            }
        }
        else
        {
            $eid = $eids;
            $exportFileList = exportFileList( $eid, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc );
        }

        $listFile = "temp/".$export_listFile;
        if ( !($fp = fopen( $listFile, "w" )) )
        {
            die( "Can't open event export list file '$listFile'" );
        }
        foreach ( $exportFileList as $exportFile )
        {
            fwrite( $fp, "$exportFile\n" );
        }
        fclose( $fp );
        $archive = "";
        if ( $exportFormat == "tar" )
        {
            $archive = "temp/".$export_root.".tar.gz";
            @unlink( $archive );
            $command = "tar --create --gzip --file=$archive --files-from=$listFile";
            exec( escapeshellcmd( $command ), $output, $status );
            if ( $status )
            {
                error_log( "Command '$command' returned with status $status" );
                if ( $output[0] )
                    error_log( "First line of output is '".$output[0]."'" );
                return( false );
            }
        }
        elseif ( $exportFormat == "zip" )
        {
            $archive = "temp/zm_export.zip";
            $archive = "temp/".$export_root.".zip";
            @unlink( $archive );
            $command = "cat ".escapeshellarg($listFile)." | zip -q ".escapeshellarg($archive)." -@";
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
