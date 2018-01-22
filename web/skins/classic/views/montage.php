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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Stream' ) )
{
    $view = "error";
    return;
}

require_once( 'includes/Monitor.php' );

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

$scale = 100;
if ( isset( $_REQUEST['scale'] ) )
  $scale = validInt($_REQUEST['scale']);
else if ( isset( $_COOKIE['zmMontageScale'] ) )
  $scale = $_COOKIE['zmMontageScale'];

if ( ! $scale ) 
  $scale = 100;

foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
    {
        continue;
    }
    
    $monitor_scale = 100;
    if ( isset( $_REQUEST['scale'] ) )
      $monitor_scale = validInt($_REQUEST['scale']);
    else if ( isset( $_COOKIE['zmMontageScale'] ) )
      $monitor_scale = $_COOKIE['zmMontageScale'];
    else
      $monitor_scale = reScale( SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    if ( ! $monitor_scale )
      $monitor_scale = 100;

    $scaleWidth = reScale( $row['Width'], $monitor_scale );
    $scaleHeight = reScale( $row['Height'], $scale );
    if ( $maxWidth < $scaleWidth )
        $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight )
        $maxHeight = $scaleHeight;
    if ( ZM_OPT_CONTROL && $row['ControlId'] && $row['Controllable'] )
        $showControl = true;
    $row['index'] = $index++;
    $row['scaleWidth'] = $scaleWidth;
    $row['scaleHeight'] = $scaleHeight;
    $row['connKey'] = generateConnKey();
    $monitors[] = new Monitor( $row );
}

$focusWindow = true;

$layouts = array(
    'montage_freeform.css' => translate('MtgDefault'),
    'montage_2wide.css' => translate('Mtg2widgrd'),
    'montage_3wide.css' => translate('Mtg3widgrd'),
    'montage_4wide.css' => translate('Mtg4widgrd'),
    'montage_3wide50enlarge.css' => translate('Mtg3widgrx'),
);

if ( isset($_COOKIE['zmMontageLayout']) )
    $layout = $_COOKIE['zmMontageLayout'];

xhtmlHeaders(__FILE__, translate('Montage') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
<?php
if ( $showControl )
{
?>
        <a href="#" onclick="createPopup( '?view=control', 'zmControl', 'control' )"><?php echo translate('Control') ?></a>
<?php
}
?>
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Montage') ?></h2>
      <div id="headerControl">
        <span id="scaleControl"><?php echo translate('Scale') ?>: <?php echo buildSelect( 'scale', $scales, 'changeScale(this);' ); ?></span> 
        <label for="layout"><?php echo translate('Layout') ?>:</label><?php echo buildSelect( 'layout', $layouts, 'selectLayout(this);' )?>
      </div>
    </div>
    <div id="content">
      <div id="monitors">
<?php
foreach ( $monitors as $monitor )
{
    $connkey = $monitor->connKey(); // Minor hack
    if ( !isset( $scale ) )
        $scale = reScale( SCALE_BASE, $monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE );
?>
        <div id="monitorFrame<?php echo $monitor->index() ?>" class="monitorFrame">
          <div id="monitor<?php echo $monitor->index() ?>" class="monitor idle">
            <div id="imageFeed<?php echo $monitor->index() ?>" class="imageFeed" onclick="createPopup( '?view=watch&amp;mid=<?php echo $monitor->Id() ?>', 'zmWatch<?php echo $monitor->Id() ?>', 'watch', <?php echo $monitor->scaleWidth() ?>, <?php echo $monitor->scaleHeight() ?> );">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamSrc = $monitor->getStreamSrc( array( 'mode'=>'mpeg', 'scale'=>$scale, 'bitrate'=>ZM_WEB_VIDEO_BITRATE, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'format'=>ZM_MPEG_LIVE_FORMAT ) );
    outputVideoStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), ZM_MPEG_LIVE_FORMAT );
} else {
    $streamSrc = $monitor->getStreamSrc( array( 'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS ) );
    if ( canStreamNative() )
    {
        outputImageStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), validHtmlStr($monitor->Name()) );
    }
    else
    {
        outputHelperStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ) );
    }
}
?>
            </div>
<?php
    if ( !ZM_WEB_COMPACT_MONTAGE )
    {
?>
            <div id="monitorState<?php echo $monitor->index() ?>" class="monitorState idle"><?php echo translate('State') ?>:&nbsp;<span id="stateValue<?php echo $monitor->index() ?>"></span>&nbsp;-&nbsp;<span id="fpsValue<?php echo $monitor->index() ?>"></span>&nbsp;fps</div>
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
