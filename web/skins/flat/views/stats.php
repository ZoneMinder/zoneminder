<?php
//
// ZoneMinder web stats view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

$eid = validInt($_REQUEST['eid']);
$fid = validInt($_REQUEST['fid']);

$sql = "select S.*,E.*,Z.Name as ZoneName,Z.Units,Z.Area,M.Name as MonitorName,M.Width,M.Height from Stats as S left join Events as E on S.EventId = E.Id left join Zones as Z on S.ZoneId = Z.Id left join Monitors as M on E.MonitorId = M.Id where S.EventId = '".dbEscape($eid)."' and S.FrameId = '".dbEscape($fid)."' order by S.ZoneId";
$stats = dbFetchAll( $sql );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Stats']." - ".$eid." - ".$fid );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow(); return( false );"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['Stats'] ?> - <?= $eid ?> - <?= $fid ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colZone"><?= $SLANG['Zone'] ?></th>
              <th class="colPixelDiff"><?= $SLANG['PixelDiff'] ?></th>
              <th class="colAlarmPx"><?= $SLANG['AlarmPx'] ?></th>
              <th class="colFilterPx"><?= $SLANG['FilterPx'] ?></th>
              <th class="colBlobPx"><?= $SLANG['BlobPx'] ?></th>
              <th class="colBlobs"><?= $SLANG['Blobs'] ?></th>
              <th class="colBlobSizes"><?= $SLANG['BlobSizes'] ?></th>
              <th class="colAlarmLimits"><?= $SLANG['AlarmLimits'] ?></th>
              <th class="colScore"><?= $SLANG['Score'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
if ( count($stats) )
{
    foreach ( $stats as $stat )
    {
?>
            <tr>
              <td class="colZone"><?= validHtmlStr($stat['ZoneName']) ?></td>
              <td class="colPixelDiff"><?= validHtmlStr($stat['PixelDiff']) ?></td>
              <td class="colAlarmPx"><?= sprintf( "%d (%d%%)", $stat['AlarmPixels'], (100*$stat['AlarmPixels']/$stat['Area']) ) ?></td>
              <td class="colFilterPx"><?= sprintf( "%d (%d%%)", $stat['FilterPixels'], (100*$stat['FilterPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobPx"><?= sprintf( "%d (%d%%)", $stat['BlobPixels'], (100*$stat['BlobPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobs"><?= validHtmlStr($stat['Blobs']) ?></td>
<?php
if ( $stat['Blobs'] > 1 )
{
?>
              <td class="colBlobSizes"><?= sprintf( "%d-%d (%d%%-%d%%)", $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$stat['Area']), (100*$stat['MaxBlobSize']/$stat['Area']) ) ?></td>
<?php
}
else
{
?>
              <td class="colBlobSizes"><?= sprintf( "%d (%d%%)", $stat['MinBlobSize'], 100*$stat['MinBlobSize']/$stat['Area'] ) ?></td>
<?php
}
?>
              <td class="colAlarmLimits"><?= validHtmlStr($stat['MinX'].",".$stat['MinY']."-".$stat['MaxX'].",".$stat['MaxY']) ?></td>
              <td class="colScore"><?= $stat['Score'] ?></td>
            </tr>
<?php
    }
}
else
{
?>
            <tr>
              <td class="rowNoStats" colspan="9"><?= $SLANG['NoStatisticsRecorded'] ?></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
      </form>
    </div>
  </div>
</body>
</html>
