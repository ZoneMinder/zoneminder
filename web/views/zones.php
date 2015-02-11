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
chdir( ZM_DIR_IMAGES );
$status = exec( escapeshellcmd( getZmuCommand( " -m ".$mid." -z" ) ) );
chdir( $wd );

$monitor = dbFetchMonitor( $mid );

$zones = array();
foreach( dbFetchAll( 'select * from Zones where MonitorId = ? order by Area desc', NULL, array($mid) ) as $row )
{
    if ( $row['Points'] = coordsToPoints( $row['Coords'] ) )
    {
        $row['AreaCoords'] = preg_replace( '/\s+/', ',', $row['Coords'] );
        $zones[] = $row;
    }
}

$image = 'Zones'.$monitor['Id'].'.jpg';

xhtmlHeaders(__FILE__, $SLANG['Zones'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a></div>
      <h2><?= $SLANG['Zones'] ?></h2>
    </div>
    <div id="content">
      <map name="zoneMap" id="zoneMap">
<?php
foreach( array_reverse($zones) as $zone )
{
?>
        <area shape="poly" alt="<?= htmlspecialchars($zone['Name']) ?>" coords="<?= $zone['AreaCoords'] ?>" href="#" onclick="createPopup( '?view=zone&amp;mid=<?= $mid ?>&amp;zid=<?= $zone['Id'] ?>', 'zmZone', 'zone', <?= $monitor['Width'] ?>, <?= $monitor['Height'] ?> ); return( false );"/>
<?php
}
?>
        <!--<area shape="default" nohref>-->
      </map>
      <img src="<?= ZM_DIR_IMAGES.'/'.$image ?>" alt="zones" usemap="#zoneMap" width="<?= $monitor['Width'] ?>" height="<?= $monitor['Height'] ?>" border="0"/>
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="mid" value="<?= $mid ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?= $SLANG['Name'] ?></th>
              <th class="colType"><?= $SLANG['Type'] ?></th>
              <th class="colUnits"><?= $SLANG['AreaUnits'] ?></th>
              <th class="colMark"><?= $SLANG['Mark'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach( $zones as $zone )
{
?>
            <tr>
              <td class="colName"><a href="#" onclick="createPopup( '?view=zone&amp;mid=<?= $mid ?>&amp;zid=<?= $zone['Id'] ?>', 'zmZone', 'zone', <?= $monitor['Width'] ?>, <?= $monitor['Height'] ?> ); return( false );"><?= $zone['Name'] ?></a></td>
              <td class="colType"><?= $zone['Type'] ?></td>
              <td class="colUnits"><?= $zone['Area'] ?>&nbsp;/&nbsp;<?= sprintf( "%.2f", ($zone['Area']*100)/($monitor['Width']*$monitor['Height']) ) ?></td>
              <td class="colMark"><input type="checkbox" name="markZids[]" value="<?= $zone['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?= $SLANG['AddNewZone'] ?>" onclick="createPopup( '?view=zone&amp;mid=<?= $mid ?>&amp;zid=0', 'zmZone', 'zone', <?= $monitor['Width'] ?>, <?= $monitor['Height'] ?> );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
          <input type="submit" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
