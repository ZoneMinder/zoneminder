<?php
//
// ZoneMinder web frame view file, $Date$, $Revision$
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

$eid = validInt($_REQUEST['eid']);
if ( !empty($_REQUEST['fid']) )
    $fid = validInt($_REQUEST['fid']);

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale,M.VideoWriter,M.Orientation FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?';
$event = dbFetchOne( $sql, NULL, array($eid) );

if ( !empty($fid) ) {
    $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId = ?';
    if ( !($frame = dbFetchOne( $sql, NULL, array($eid, $fid) )) )
        $frame = array( 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0 );
} else {
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventId = ? AND Score = ?', NULL, array( $eid, $event['MaxScore'] ) );
}

$maxFid = $event['Frames'];

$firstFid = 1;
$prevFid = $frame['FrameId']-1;
$nextFid = $frame['FrameId']+1;
$lastFid = $maxFid;

$alarmFrame = $frame['Type']=='Alarm';

if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );

$Transpose = '';
if ( $event['VideoWriter'] == "2" ) { // PASSTHROUGH
	$Rotation = $event['Orientation'];
// rotate right
	if ( in_array($event['Orientation'],array("90")))
		$Transpose = 'transpose=1,';
// rotate 180 // upside down cam
	if ( in_array($event['Orientation'],array("180")))
		$Transpose = 'transpose=2,transpose=2,';
// rotate left
	if ( in_array($event['Orientation'],array("270")))
		$Transpose = 'transpose=2,';
}
$focusWindow = true;
$Scale = 100/$scale;
$fid = $fid - 1;
#$command = 'ffmpeg -v 0 -i '.getEventDefaultVideoPath($event).' -vf "select=gte(selected_n\,'.$fid.'),setpts=PTS-STARTPTS" '.$Transpose.',scale=iw/'.$Scale.':-1" -frames:v 1 -f mjpeg -';
$command = 'ffmpeg -v 0 -i '.getEventDefaultVideoPath($event).' -vf "select=gte(n\\,'.$fid.'),setpts=PTS-STARTPTS,'.$Transpose.'scale=iw/'.$Scale.':-1" -f image2 -';
header('Content-Type: image/jpeg');
system($command);
?>
