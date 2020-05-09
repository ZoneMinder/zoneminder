<?php
//
// ZoneMinder web zones view file
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

if ( !canView('Monitors') ) {
  $view = 'error';
  return;
}

$mid = validInt($_REQUEST['mid']);
$monitor = new ZM\Monitor($mid);
# ViewWidth() and ViewHeight() are already rotated
$minX = 0;
$maxX = $monitor->ViewWidth()-1;
$minY = 0;
$maxY = $monitor->ViewHeight()-1;

$zones = array();
foreach ( dbFetchAll('SELECT * FROM Zones WHERE MonitorId=? ORDER BY Area DESC', NULL, array($mid)) as $row ) {
  $row['Points'] = coordsToPoints($row['Coords']);

  limitPoints($row['Points'], $minX, $minY, $maxX, $maxY);
  $row['Coords'] = pointsToCoords($row['Points']);
  $row['AreaCoords'] = preg_replace('/\s+/', ',', $row['Coords']);
  $zones[] = $row;
}

$connkey = generateConnKey();

xhtmlHeaders(__FILE__, translate('Zones'));
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" data-on-click="closeWindow"><?php echo translate('Close') ?></a></div>
      <h2><?php echo translate('Zones') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <div class="ZonesImage" style="width: <?php echo $monitor->ViewWidth() ?>px;">
        <?php echo getStreamHTML($monitor); ?>
        <svg class="zones" width="<?php echo $monitor->ViewWidth() ?>" height="<?php echo $monitor->ViewHeight() ?>" style="position:absolute; top: 0; left: 0; background: none;">
<?php
      foreach( array_reverse($zones) as $zone ) {
?>
          <polygon points="<?php echo $zone['AreaCoords'] ?>" class="popup-link <?php echo $zone['Type']?>" onclick="streamCmdQuit(true); return false;"
                   data-url="?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>"
                   data-window-name="zmZone<?php echo $zone['Id'] ?>"
                   data-window-tag="zone"
                   data-window-width="<?php echo $monitor->ViewWidth() ?>"
                   data-window-height="<?php echo $monitor->ViewHeight() ?>"
									 />
<?php
      } // end foreach zone
?>
          Sorry, your browser does not support inline SVG
        </svg>
        </div>
				<div id="zones">
					<table id="zonesTable" class="major">
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
	foreach( $zones as $zone ) {
	?>
							<tr>
								<td class="colName"><?php echo makePopupLink('?view=zone&mid='.$mid.'&zid='.$zone['Id'], 'zmZone', array('zone', $monitor->ViewWidth(), $monitor->ViewHeight()), validHtmlStr($zone['Name']), true, 'onclick="streamCmdQuit(true); return false;"'); ?></td>
								<td class="colType"><?php echo validHtmlStr($zone['Type']) ?></td>
								<td class="colUnits"><?php echo $zone['Area'] ?>&nbsp;/&nbsp;<?php echo sprintf('%.2f', ($zone['Area']*100)/($monitor->ViewWidth()*$monitor->ViewHeight()) ) ?></td>
								<td class="colMark"><input type="checkbox" name="markZids[]" value="<?php echo $zone['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if ( !canEdit('Monitors') ) { ?> disabled="disabled"<?php } ?>/></td>
							</tr>
	<?php
	}
	?>
						</tbody>
					</table>
					<div id="contentButtons">
						<?php echo makePopupButton('?view=zone&mid='.$mid.'&zid=0', 'zmZone', array('zone', $monitor->ViewWidth(), $monitor->ViewHeight()), translate('AddNewZone'), canEdit('Monitors')); ?>
						<input type="submit" name="deleteBtn" value="<?php echo translate('Delete') ?>" disabled="disabled"/>
					</div>
				</div><!--zones-->
      </form>
    </div>
  </div>
</body>
</html>
