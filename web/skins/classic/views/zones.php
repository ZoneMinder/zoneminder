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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Monitors' ) ) {
  $view = 'error';
  return;
}

$mid = validInt($_REQUEST['mid']);
$monitor = new Monitor( $mid );
# Width() and Height() are already rotated
$minX = 0;
$maxX = $monitor->Width()-1;
$minY = 0;
$maxY = $monitor->Height()-1;

$zones = array();
foreach( dbFetchAll( 'SELECT * FROM Zones WHERE MonitorId=? ORDER BY Area DESC', NULL, array($mid) ) as $row ) {
  $row['Points'] = coordsToPoints( $row['Coords'] );

  limitPoints( $row['Points'], $minX, $minY, $maxX, $maxY );
  $row['Coords'] = pointsToCoords( $row['Points'] );
  $row['AreaCoords'] = preg_replace( '/\s+/', ',', $row['Coords'] );
  $zones[] = $row;
}

$connkey = generateConnKey();

xhtmlHeaders(__FILE__, translate('Zones') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a></div>
      <h2><?php echo translate('Zones') ?></h2>
    </div>
    <div id="content" style="width:<?php echo $monitor->Width() ?>px; height:<?php echo $monitor->Height() ?>px; position:relative; margin: 0 auto;">
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <div id="contentButtons">
          <input type="button" value="<?php echo translate('AddNewZone') ?>" onclick="createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=0', 'zmZone', 'zone', <?php echo $monitor->Width() ?>, <?php echo $monitor->Height() ?> );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
          <input type="submit" name="deleteBtn" value="<?php echo translate('Delete') ?>" disabled="disabled"/>
        </div>
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
foreach( $zones as $zone ) {
?>
            <tr>
              <td class="colName"><a href="#" onclick="streamCmdQuit( true ); createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>', 'zmZone', 'zone', <?php echo $monitor->Width() ?>, <?php echo $monitor->Height() ?> ); return( false );"><?php echo $zone['Name'] ?></a></td>
              <td class="colType"><?php echo $zone['Type'] ?></td>
              <td class="colUnits"><?php echo $zone['Area'] ?>&nbsp;/&nbsp;<?php echo sprintf( "%.2f", ($zone['Area']*100)/($monitor->Width()*$monitor->Height()) ) ?></td>
              <td class="colMark"><input type="checkbox" name="markZids[]" value="<?php echo $zone['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div class="ZonesImage" style="position:relative; clear:both;">
        <?php echo getStreamHTML( $monitor ); ?>
        <svg class="zones" width="<?php echo $monitor->Width() ?>" height="<?php echo $monitor->Height() ?>" style="position:absolute; top: 0; left: 0; background: none;">
<?php
      foreach( array_reverse($zones) as $zone ) {
?>
          <polygon points="<?php echo $zone['AreaCoords'] ?>" class="<?php echo $zone['Type']?>" onclick="streamCmdQuit( true ); createPopup( '?view=zone&amp;mid=<?php echo $mid ?>&amp;zid=<?php echo $zone['Id'] ?>', 'zmZone', 'zone', <?php echo $monitor->Width ?>, <?php echo $monitor->Height ?> ); return( false );"/>
<?php
      } // end foreach zone
?>
          Sorry, your browser does not support inline SVG
        </svg>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
