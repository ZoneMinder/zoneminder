<?php
//
// ZoneMinder web montage view file, $Date$, $Revision$
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
    $view = "error";
    return;
}

$groupSql = "";
if ( !empty($_REQUEST['group']) )
{
    $row = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_REQUEST['group']) );
	$sql = "select * from Monitors where Function != 'None' and find_in_set( Id, '".$row['MonitorIds']."' ) order by Sequence";
} else { 
	$sql = "select * from Monitors where Function != 'None' order by Sequence";
}

$maxWidth = 0;
$maxHeight = 0;
$showControl = false;
$index = 0;
$monitors = array();
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
    {
        continue;
    }
    
    if ( isset( $_REQUEST['scale'] ) )
        $scale = validInt($_REQUEST['scale']);
    else if ( isset( $_COOKIE['zmMontageScale'] ) )
        $scale = $_COOKIE['zmMontageScale'];
    else
        $scale = reScale( SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

    $scaleWidth = reScale( $row['Width'], $scale );
    $scaleHeight = reScale( $row['Height'], $scale );
    if ( $maxWidth < $scaleWidth )
        $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight )
        $maxHeight = $scaleHeight;
    if ( ZM_OPT_CONTROL && $row['ControlId'] )
        $showControl = true;
    $row['index'] = $index++;
    $row['scaleWidth'] = $scaleWidth;
    $row['scaleHeight'] = $scaleHeight;
    $row['connKey'] = generateConnKey();
    $monitors[] = $row;
}

$focusWindow = true;

$layouts = array(
    'montage_freeform.css' => $SLANG['MtgDefault'],
    'montage_2wide.css' => $SLANG['Mtg2widgrd'],
    'montage_3wide.css' => $SLANG['Mtg3widgrd'],
    'montage_4wide.css' => $SLANG['Mtg4widgrd'],
    'montage_3wide50enlarge.css' => $SLANG['Mtg3widgrx'],
);

if ( isset($_COOKIE['zmMontageLayout']) )
    $layout = $_COOKIE['zmMontageLayout'];

xhtmlHeaders(__FILE__, $SLANG['Montage'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
<?php
if ( $showControl )
{
?>
        <a href="#" onclick="createPopup( '?view=control', 'zmControl', 'control' )"><?= $SLANG['Control'] ?></a>
<?php
}
?>
        <a href="#" onclick="closeWindow()"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['Montage'] ?></h2>
      <div id="headerControl">
        <span id="scaleControl"><?= $SLANG['Scale'] ?>: <?= buildSelect( "scale", $scales, "changeScale( this );" ); ?></span> 
        <label for="layout"><?= $SLANG['Layout'] ?>:</label><?= buildSelect( "layout", $layouts, 'selectLayout( this )' )?>
      </div>
    </div>
    <div id="content">
      <div id="monitors">
<?php
foreach ( $monitors as $monitor )
{
    $connkey = $monitor['connKey']; // Minor hack
    if ( !isset( $scale ) )
        $scale = reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
?>
        <div id="monitorFrame<?= $monitor['index'] ?>" class="monitorFrame">
          <div id="monitor<?= $monitor['index'] ?>" class="monitor idle">
            <div id="imageFeed<?= $monitor['index'] ?>" class="imageFeed" onclick="createPopup( '?view=watch&amp;mid=<?= $monitor['Id'] ?>', 'zmWatch<?= $monitor['Id'] ?>', 'watch', <?= $monitor['scaleWidth'] ?>, <?= $monitor['scaleHeight'] ?> );">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $streamSrc = getStreamSrc( array( "mode=mpeg", "monitor=".$monitor['Id'], "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
    outputVideoStream( "liveStream".$monitor['Id'], $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), ZM_MPEG_LIVE_FORMAT );
}
else
{
    $streamSrc = getStreamSrc( array( "mode=jpeg", "monitor=".$monitor['Id'], "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
    if ( canStreamNative() )
    {
        outputImageStream( "liveStream".$monitor['Id'], $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), validHtmlStr($monitor['Name']) );
    }
    else
    {
        outputHelperStream( "liveStream".$monitor['Id'], $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) );
    }
}
?>
            </div>
<?php
    if ( !ZM_WEB_COMPACT_MONTAGE )
    {
?>
            <div id="monitorState<?= $monitor['index'] ?>" class="monitorState idle"><?= $SLANG['State'] ?>:&nbsp;<span id="stateValue<?= $monitor['index'] ?>"></span>&nbsp;-&nbsp;<span id="fpsValue<?= $monitor['index'] ?>"></span>&nbsp;fps</div>
<?php
    }
?>
          </div>
        </div>
<?php
}
?>
      </div>
    </div>
  </div>
</body>
</html>
