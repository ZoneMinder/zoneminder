<?php
//
// ZoneMinder web event details view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$event = dbFetchOne( 'select E.*,M.Name as MonitorName from Events as E, Monitors as M where E.Id = ? and E.MonitorId = M.Id', NULL, array($_REQUEST['eid']) );
$frame = dbFetchOne( 'select * from Frames where EventID = ? and Score = ?', NULL, array($_REQUEST['eid'],$event['MaxScore']) );

$scale = getDeviceScale( $event['Width'], $event['Height'], 2.2 );

$image1 = getImageSrc( $event, 1, $scale );
if ( $frame['Type'] == 'Alarm' )
    $image2 = getImageSrc( $event, $frame['FrameId'], $scale );
else
    $image2 = getImageSrc( $event, intval($event['Frames']/2), $scale );

xhtmlHeaders( __FILE__, $SLANG['Event'].' - '.$_REQUEST['eid'] );
?>
<body>
  <div id="page">
    <div id="content">
      <table id="contentTable" class="major">
        <tr>
          <th scope="row"><?= $SLANG['Name'] ?></th>
          <td><?= htmlentities($event['Name']) ?><?= $event['Archived']?("(".$SLANG['Archived'].")"):"" ?></td>
        </tr>
        <tr>
          <th scope="row"><?= $SLANG['Time'] ?></th>
          <td><?= htmlentities(strftime("%b %d, %H:%M",strtotime($event['StartTime']))) ?></td>
        </tr>
        <tr>
          <th scope="row"><?= $SLANG['Duration'] ?></th>
          <td><?= htmlentities($event['Length']) ?>s</td>
        </tr>
        <tr>
          <th scope="row"><?= $SLANG['Cause'] ?></th>
          <td><?= htmlentities($event['Cause']) ?></td>
        </tr>
        <?php if ( !empty($event['Notes']) ) { ?>
        <tr>
          <th scope="row"><?= $SLANG['Notes'] ?></th>
          <td><?= htmlentities($event['Notes']) ?></td>
        </tr>
        <?php } ?>
        <tr>
          <th scope="row"><?= $SLANG['Frames'] ?></th>
          <td><?= $event['Frames'] ?> (<?= $event['AlarmFrames'] ?>)</td>
        </tr>
        <tr>
          <th scope="row"><?= $SLANG['Score'] ?></th>
          <td><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></td>
        </tr>
      </table>
      <div id="eventImages">
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=1"><img src="<?= viewImagePath( $image1['thumbPath'] ) ?>" alt="1"/></a>
        <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $frame['FrameId'] ?>"><img src="<?= viewImagePath( $image2['thumbPath'] ) ?>" alt="<?= $frame['FrameId'] ?>"/></a>
      </div>
      <div id="contenButtons">
        <a href="?view=event&amp;eid=<?= $_REQUEST['eid'] ?>&amp;page=1"><?= $SLANG['Frames'] ?></a>
        <a href="?view=video&amp;eid=<?= $_REQUEST['eid'] ?>"><?= $SLANG['Video'] ?></a>
      </div>
    </div>
  </div>
</body>
</html>
