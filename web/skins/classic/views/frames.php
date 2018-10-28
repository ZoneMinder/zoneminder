<?php
//
// ZoneMinder web frames view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) ) {
  $view = 'error';
  return;
}
require_once( 'includes/Frame.php' );
$Event = new Event( $_REQUEST['eid'] );

$sql = 'SELECT *, unix_timestamp( TimeStamp ) AS UnixTimeStamp FROM Frames WHERE EventID = ? ORDER BY FrameId';
$frames = dbFetchAll( $sql, NULL, array( $_REQUEST['eid'] ) );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Frames')." - ".$Event->Id() );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a></div>
      <h2><?php echo translate('Frames') ?> - <?php echo $Event->Id() ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colId"><?php echo translate('FrameId') ?></th>
              <th class="colType"><?php echo translate('Type') ?></th>
              <th class="colTimeStamp"><?php echo translate('TimeStamp') ?></th>
              <th class="colTimeDelta"><?php echo translate('TimeDelta') ?></th>
              <th class="colScore"><?php echo translate('Score') ?></th>
<?php
        if ( ZM_WEB_LIST_THUMBS ) {
?>
              <th class="colThumbnail"><?php echo translate('Thumbnail') ?></th>
<?php
        }
?>
            </tr>
          </thead>
          <tbody>
<?php
if ( count($frames) ) {
  foreach ( $frames as $frame ) {
    $Frame = new Frame( $frame );

    $class = strtolower($frame['Type']);
?>
            <tr class="<?php echo $class ?>">
              <td class="colId"><?php echo makePopupLink( '?view=frame&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmImage', array( 'frame', $Event->Width(), $Event->Height() ), $frame['FrameId'] ) ?></td>
              <td class="colType"><?php echo $frame['Type'] ?></td>
              <td class="colTimeStamp"><?php echo strftime( STRF_FMT_TIME, $frame['UnixTimeStamp'] ) ?></td>
              <td class="colTimeDelta"><?php echo number_format( $frame['Delta'], 2 ) ?></td>
<?php
    if ( ZM_RECORD_EVENT_STATS && ($frame['Type'] == 'Alarm') ) {
?>
              <td class="colScore"><?php echo makePopupLink( '?view=stats&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmStats', 'stats', $frame['Score'] ) ?></td>
<?php
    } else {
?> 
              <td class="colScore"><?php echo $frame['Score'] ?></td>
<?php
    }
    if ( ZM_WEB_LIST_THUMBS ) {
?>
              <td class="colThumbnail"><?php echo makePopupLink( '?view=frame&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmImage', array( 'image', $Event->Width(), $Event->Height() ), '<img src="?view=image&amp;fid='.$Frame->Id().'&amp;'.
(ZM_WEB_LIST_THUMB_WIDTH?'width='.ZM_WEB_LIST_THUMB_WIDTH.'&amp;':'').
(ZM_WEB_LIST_THUMB_HEIGHT?'height='.ZM_WEB_LIST_THUMB_HEIGHT.'&amp;':'').'filename='.$Event->MonitorId().'_'.$frame['EventId'].'_'.$frame['FrameId'].'.jpg" '.
(ZM_WEB_LIST_THUMB_WIDTH?'width="'.ZM_WEB_LIST_THUMB_WIDTH.'" ':'').
(ZM_WEB_LIST_THUMB_HEIGHT?'height="'.ZM_WEB_LIST_THUMB_HEIGHT.'" ':'').' alt="'.$frame['FrameId'].'"/>' ) ?></td>
<?php
    }
?>
            </tr>
<?php
  } // end foreach frame
} else {
?>
            <tr>
              <td colspan="5"><?php echo translate('NoFramesRecorded') ?></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
