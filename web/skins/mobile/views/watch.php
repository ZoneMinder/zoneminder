<?php
//
// ZoneMinder web watch view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$sql = 'select C.*, M.* from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id = ?';
$monitor = dbFetchOne( $sql, NULL, array($_REQUEST['mid']) );

$showPtzControls = ( ZM_OPT_CONTROL && $monitor['Controllable'] && canView( 'Control' ) );

$zmuCommand = getZmuCommand( " -m ".$_REQUEST['mid']." -s -f" );
$zmuOutput = exec( escapeshellcmd( $zmuCommand ) );
list( $status, $fps ) = explode( ' ', $zmuOutput );
$statusString = $SLANG['Unknown'];
$fpsString = "--.--";
$class = "infoText";
if ( $status <= STATE_PREALARM )
{
    $statusString = $SLANG['Idle'];
}
elseif ( $status == STATE_ALARM )
{
    $statusString = $SLANG['Alarm'];
    $class = "errorText";
}
elseif ( $status == STATE_ALERT )
{
    $statusString = $SLANG['Alert'];
    $class = "warnText";
}
elseif ( $status == STATE_TAPE )
{
    $statusString = $SLANG['Record'];
}
$fpsString = sprintf( "%.2f", $fps );

$sql = "select * from Monitors where Function != 'None' order by Sequence";
$monitors = array();
$monIdx = 0;
$maxWidth = 0;
$maxHeight = 0;
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
    {
        continue;
    }
    if ( isset($monitor['Id']) && $row['Id'] == $monitor['Id'] )
        $monIdx = count($monitors);
    if ( $maxWidth < $row['Width'] ) $maxWidth = $row['Width'];
    if ( $maxHeight < $row['Height'] ) $maxHeight = $row['Height'];
    $monitors[] = $row;
}

//$monitor = $monitors[$monIdx];
$nextMid = $monIdx==(count($monitors)-1)?$monitors[0]['Id']:$monitors[$monIdx+1]['Id'];
$prevMid = $monIdx==0?$monitors[(count($monitors)-1)]['Id']:$monitors[$monIdx-1]['Id'];

if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = getDeviceScale( $monitor['Width'], $monitor['Height'] );
$imageSrc = getStreamSrc( array( "mode=single", "monitor=".$monitor['Id'], "scale=".$scale ), '&amp;' );

if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $streamMode = "mpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
}
elseif ( canStream() )
{
    $streamMode = "jpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
}
else
{
    $streamMode = "single";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale ) );
}

xhtmlHeaders( __FILE__, $monitor['Name'].' - '.$SLANG['Watch'] );
?>
<body>
  <div id="page">
    <div id="content">
      <p class="<?= $class ?>"><?= makeLink( "?view=events&amp;page=1&amp;view=events&amp;page=1&amp;filter%5Bterms%5D%5B0%5D%5Battr%5D%3DMonitorId&amp;filter%5Bterms%5D%5B0%5D%5Bop%5D%3D%3D&amp;filter%5Bterms%5D%5B0%5D%5Bval%5D%3D".$monitor['Id']."&amp;sort_field=Id&amp;sort_desc=1", $monitor['Name'], canView( 'Events' ) ) ?>:&nbsp;<?= $statusString ?>&nbsp;-&nbsp;<?= $fpsString ?>&nbsp;fps</p>
      <p>
<?php
if ( $streamMode == "mpeg" )
{
    outputVideoStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), ZM_MPEG_LIVE_FORMAT, $monitor['Name'] );
}
elseif ( $streamMode == "jpeg" )
{
    if ( canStreamNative() )
        outputImageStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
    elseif ( canStreamApplet() )
        outputHelperStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
}
else
{
?>
        <a href="?view=<?= $_REQUEST['view'] ?>&amp;mid=<?= $monitor['Id'] ?>"><?= outputImageStill( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] ); ?></a>
<?php
}
?>
      </p>
<?php
if ( $showPtzControls )
{
    foreach ( getSkinIncludes( 'includes/control_functions.php' ) as $includeFile )
        require_once $includeFile;
?>
      <div id="ptzControls">
        <?= ptzControls( $monitor ) ?>
      </div>
<?php
}
if ( $nextMid != $monitor['Id'] || $prevMid != $monitor['Id'] )
{
?>
      <div id="contentButtons">
        <a href="?view=<?= $_REQUEST['view'] ?>&amp;mid=<?= $prevMid ?>"><?= $SLANG['Prev'] ?></a>
        <a href="?view=console"><?= $SLANG['Console'] ?></a>
        <a href="?view=<?= $_REQUEST['view'] ?>&amp;mid=<?= $nextMid ?>"><?= $SLANG['Next'] ?></a>
      </div>
<?php
}
?>
    </div>
  </div>
</body>
</html>
