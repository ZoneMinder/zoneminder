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

$mids = null;
if ( isset($_REQUEST['mid']) ) {
  $mids = array();
  $mids[] = validInt($_REQUEST['mid']);
} else if ( isset($_REQUEST['mids']) ) {
  $mids = $_REQUEST['mids'];
} else {
  $mids = dbFetchAll('SELECT Id FROM Monitors'.($user['MonitorIds'] ? 'WHERE Id IN ('.$user['MonitorIds'].')' : ''), 'Id');
}

if ( !($mids and count($mids)) ) {
  $view = 'error';
  return;
}
$monitors = ZM\ZM_Object::Objects_Indexed_By_Id('ZM\Monitor', array('Id'=>$mids));

xhtmlHeaders(__FILE__, translate('Zones'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <div class="w-100 py-1">
      <div class="float-left pl-3">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      </div>
      <div class="w-100 pt-2">
        <h2><?php echo translate('Zones').(isset($_REQUEST['mid']) ? ' '.translate('for').' '.$monitors[$_REQUEST['mid']]->Name() : '')?></h2>
      </div>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?view=<?php echo $view ?>">
        <input type="hidden" name="action" value="delete"/>
<?php
  foreach ( $mids as $mid ) {
    $monitor = $monitors[$mid];
    $monitor->connKey();
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

    $options = array('width'=>'100%', 'height'=>'auto', 'mode'=>'single');
?>
    <div class="Monitor"><div id="monitor<?php echo $mid ?>" class="monitor">
        <input type="hidden" name="mids[]" value="<?php echo $mid ?>"/>
        <div class="ZonesImage imageFeed" id="imageFeed<?php echo $monitor->Id() ?>">
          <?php echo getStreamHTML($monitor, $options); ?>
          <svg class="zones" viewBox="0 0 <?php echo $monitor->ViewWidth().' '.$monitor->ViewHeight() ?>">
<?php
      foreach( array_reverse($zones) as $zone ) {
?>
            <polygon points="<?php echo $zone['AreaCoords'] ?>"
                     class="zmlink <?php echo $zone['Type']?>"
                     data-on-click-true="streamCmdQuit"
                     data-url="?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>"
            />
<?php
      } // end foreach zone
?>
          Sorry, your browser does not support inline SVG
        </svg>
        <div id="monitorState">
          <?php echo translate('State') ?>:&nbsp;<span id="stateValue<?php echo $monitor->Id() ?>"></span>&nbsp;-&nbsp;<span id="fpsValue<?php echo $monitor->Id() ?>"></span>&nbsp;fps
        </div>
        </div>
        </div>
				<div class="zones">
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
	foreach ($zones as $zone) {
?>
							<tr>
								<td class="colName"><?php echo makeLink('?view=zone&mid='.$mid.'&zid='.$zone['Id'], validHtmlStr($zone['Name']), true, 'data-on-click-true="streamCmdQuit"'); ?></td>
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
            <?php echo makeButton('?view=zone&mid='.$mid.'&zid=0', 'AddNewZone', canEdit('Monitors')); ?>
            <button type="submit" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
          </div>
				</div><!--zones-->
        <br class="clear"/>
      </div><!--Monitor-->
<?php 
  } # end foreach monitor
?>
      </form>
    </div>
  </div>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>
