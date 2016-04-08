<?php
//
// ZoneMinder web zones view file, $Date$, $Revision$
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

if ( !canView( 'Monitors' ) )
{
    $view = "error";
    return;
}

$mid = validInt($_REQUEST['mid']);
$wd = getcwd();
$monitor = new Monitor( $mid );

$zones = array();
foreach( dbFetchAll( 'select * from Zones where MonitorId = ? order by Area desc', NULL, array($mid) ) as $row )
{
    if ( $row['Points'] = coordsToPoints( $row['Coords'] ) )
    {
        $row['AreaCoords'] = preg_replace( '/\s+/', ',', $row['Coords'] );
        $zones[] = $row;
    }
}

xhtmlHeaders(__FILE__, translate('Zones') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a></div>
      <h2><?php echo translate('Zones') ?></h2>
    </div>
    <div id="content">
<?php
$scale = 100;
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamMode = "mpeg";
    $streamSrc = $monitor->getStreamSrc( array( "mode=".$streamMode, "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
}
elseif ( canStream() )
{
    $streamMode = "jpeg";
    $streamSrc = $monitor->getStreamSrc( array( "mode=".$streamMode, "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "buffer=".$monitor->StreamReplayBuffer() ) );
}
else
{
    $streamMode = "single";
    $streamSrc = $monitor->getStreamSrc( array( "mode=".$streamMode, "scale=".$scale ) );
    Info( "The system has fallen back to single jpeg mode for streaming. Consider enabling Cambozola or upgrading the client browser.");
}
?>
<img alt="zones" usemap="#zoneMap" width="<?php echo $monitor->Width() ?>" height="<?php echo $monitor->Height() ?>" border="0" src="<?php
if ( $streamMode == "mpeg" )
{
    outputVideoStream( "liveStream", $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), ZM_MPEG_LIVE_FORMAT, $monitor->Name() );
}
elseif ( $streamMode == "jpeg" )
{
    if ( canStreamNative() )
        outputImageStream( "liveStream", $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), $monitor->Name() );
    elseif ( canStreamApplet() )
        outputHelperStream( "liveStream", $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), $monitor->Name() );
}
else
{
    outputImageStill( "liveStream", $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), $monitor->Name() );
}
?>"/>
      <svg width="<?php echo $Monitor->Width ?>" height="<?php echo $Monitor->Height ?>" style="margin-top: -<?php echo $Monitor->Height ?>px;background: none;">
<?php
      foreach( array_reverse($zones) as $zone ) {
?>
        <polygon points="<?php echo $zone['AreaCoords'] ?>" class="<?php echo $zone['Type']?>" />
<?php
        foreach ( explode(' ', $zone['Coords'] ) as $point ) {
          $xy = explode(',', $point );
?>
          <rect class="point" x="<?php $xy[0] ?>" y="<?php $xy[1] ?>" onclick="createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>', 'zmZone', 'zone', <?php echo $monitor->Width ?>, <?php echo $monitor->Height ?> ); return( false );"/>
<?php
        } // end foreach point
      } // end foreach zone
?>
          Sorry, your browser does not support inline SVG
        </svg>
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colType"><?php echo translate('Type') ?></th>
              <th class="colUnits"><?php echo translate('AreaUnits') ?></th>
              <th class="colMark"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach( $zones as $zone )
{
?>
            <tr>
              <td class="colName"><a href="#" onclick="createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>', 'zmZone', 'zone', <?php echo $monitor->Width() ?>, <?php echo $monitor->Height() ?> ); return( false );"><?php echo $zone['Name'] ?></a></td>
              <td class="colType"><?php echo $zone['Type'] ?></td>
              <td class="colUnits"><?php echo $zone['Area'] ?>&nbsp;/&nbsp;<?php echo sprintf( "%.2f", ($zone['Area']*100)/($monitor->Width()*$monitor->Height()) ) ?></td>
              <td class="colMark"><input type="checkbox" name="markZids[]" value="<?php echo $zone['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?php echo translate('AddNewZone') ?>" onclick="createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=0', 'zmZone', 'zone', <?php echo $monitor->Width() ?>, <?php echo $monitor->Height() ?> );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
          <input type="submit" name="deleteBtn" value="<?php echo translate('Delete') ?>" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
