<?php
//
// ZoneMinder web stats view file, $Date$, $Revision$
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
$fid = validInt($_REQUEST['fid']);

$sql = 'SELECT S.*,E.*,Z.Name AS ZoneName,Z.Units,Z.Area,M.Name AS MonitorName FROM Stats AS S LEFT JOIN Events AS E ON S.EventId = E.Id LEFT JOIN Zones AS Z ON S.ZoneId = Z.Id LEFT JOIN Monitors AS M ON E.MonitorId = M.Id WHERE S.EventId = ? AND S.FrameId = ? ORDER BY S.ZoneId';
$stats = dbFetchAll( $sql, NULL, array( $eid, $fid ) );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Stats')." - ".$eid." - ".$fid );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow(); return( false );"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Stats') ?> - <?php echo $eid ?> - <?php echo $fid ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colZone"><?php echo translate('Zone') ?></th>
              <th class="colPixelDiff"><?php echo translate('PixelDiff') ?></th>
              <th class="colAlarmPx"><?php echo translate('AlarmPx') ?></th>
              <th class="colFilterPx"><?php echo translate('FilterPx') ?></th>
              <th class="colBlobPx"><?php echo translate('BlobPx') ?></th>
              <th class="colBlobs"><?php echo translate('Blobs') ?></th>
              <th class="colBlobSizes"><?php echo translate('BlobSizes') ?></th>
              <th class="colAlarmLimits"><?php echo translate('AlarmLimits') ?></th>
              <th class="colScore"><?php echo translate('Score') ?></th>
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
              <td class="colZone"><?php echo validHtmlStr($stat['ZoneName']) ?></td>
              <td class="colPixelDiff"><?php echo validHtmlStr($stat['PixelDiff']) ?></td>
              <td class="colAlarmPx"><?php echo sprintf( "%d (%d%%)", $stat['AlarmPixels'], (100*$stat['AlarmPixels']/$stat['Area']) ) ?></td>
              <td class="colFilterPx"><?php echo sprintf( "%d (%d%%)", $stat['FilterPixels'], (100*$stat['FilterPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobPx"><?php echo sprintf( "%d (%d%%)", $stat['BlobPixels'], (100*$stat['BlobPixels']/$stat['Area']) ) ?></td>
              <td class="colBlobs"><?php echo validHtmlStr($stat['Blobs']) ?></td>
<?php
if ( $stat['Blobs'] > 1 )
{
?>
              <td class="colBlobSizes"><?php echo sprintf( "%d-%d (%d%%-%d%%)", $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$stat['Area']), (100*$stat['MaxBlobSize']/$stat['Area']) ) ?></td>
<?php
}
else
{
?>
              <td class="colBlobSizes"><?php echo sprintf( "%d (%d%%)", $stat['MinBlobSize'], 100*$stat['MinBlobSize']/$stat['Area'] ) ?></td>
<?php
}
?>
              <td class="colAlarmLimits"><?php echo validHtmlStr($stat['MinX'].",".$stat['MinY']."-".$stat['MaxX'].",".$stat['MaxY']) ?></td>
              <td class="colScore"><?php echo $stat['Score'] ?></td>
            </tr>
<?php
    }
}
else
{
?>
            <tr>
              <td class="rowNoStats" colspan="9"><?php echo translate('NoStatisticsRecorded') ?></td>
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
