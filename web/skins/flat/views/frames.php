<?php
//
// ZoneMinder web frames view file, $Date: 2009-10-16 18:09:16 +0100 (Fri, 16 Oct 2009) $, $Revision: 2982 $
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
    $view = "error";
    return;
}
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '".dbEscape($_REQUEST['eid'])."'";
$event = dbFetchOne( $sql );

$sql = "select *, unix_timestamp( TimeStamp ) as UnixTimeStamp from Frames where EventID = '".dbEscape($_REQUEST['eid'])."' order by FrameId";
$frames = dbFetchAll( $sql );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Frames']." - ".$event['Id'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a></div>
      <h2><?= $SLANG['Frames'] ?> - <?= $event['Id'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colId"><?= $SLANG['FrameId'] ?></th>
              <th class="colType"><?= $SLANG['Type'] ?></th>
              <th class="colTimeStamp"><?= $SLANG['TimeStamp'] ?></th>
              <th class="colTimeDelta"><?= $SLANG['TimeDelta'] ?></th>
              <th class="colScore"><?= $SLANG['Score'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
if ( count($frames) )
{
    foreach ( $frames as $frame )
    {
        $class = strtolower($frame['Type']);
?>
            <tr class="<?= $class ?>">
              <td class="colId"><?= makePopupLink( '?view=frame&amp;eid='.$event['Id'].'&amp;fid='.$frame['FrameId'], 'zmImage', array( 'image', $event['Width'], $event['Height'] ), $frame['FrameId'] ) ?></td>
              <td class="colType"><?= $frame['Type'] ?></td>
              <td class="colTimeStamp"><?= strftime( STRF_FMT_TIME, $frame['UnixTimeStamp'] ) ?></td>
              <td class="colTimeDelta"><?= number_format( $frame['Delta'], 2 ) ?></td>
<?php
        if ( ZM_RECORD_EVENT_STATS && ($frame['Type'] == 'Alarm') )
        {
?>
              <td class="colScore"><?= makePopupLink( '?view=stats&amp;eid='.$event['Id'].'&amp;fid='.$frame['FrameId'], 'zmStats', 'stats', $frame['Score'] ) ?></td>
<?php
        }
        else
        {
?> 
              <td class="colScore"><?= $frame['Score'] ?></td>
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
              <td colspan="5"><?= $SLANG['NoFramesRecorded'] ?></td>
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
