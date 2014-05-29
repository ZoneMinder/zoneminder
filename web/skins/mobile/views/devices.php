<?php
//
// ZoneMinder web devices file, $Date$, $Revision$
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

if ( !canView( 'Devices' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$sql = "select * from Devices where Type = 'X10' order by Name";
$devices = array();
foreach ( dbFetchAll( $sql ) as $row )
{
    $row['Status'] = getDeviceStatusX10( $row['KeyString'] );
    $devices[] = $row;
}

xhtmlHeaders( __FILE__, $SLANG['Devices'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Devices'] ?></h2>
    </div>
    <div id="content">
      <table id="contentTable" class="major">
<?php
foreach( $devices as $device )
{
    if ( $device['Status'] == 'ON' )
    {
        $fclass = "infoText";
    }
    elseif ( $device['Status'] == 'OFF' )
    {
        $fclass = "warnText";
    }
    else
    {
        $fclass = "errorText";
    }
?>
        <tr>
          <th scope="row" class="<?= $fclass  ?>"><span class="<?= $fclass ?>"><?= substr( $device['Name'], 0, 16 ) ?></span></th>
          <td><?= makeLink( "?view=".$_REQUEST['view']."&amp;action=device&amp;key=".$device['KeyString']."&amp;command=on", $SLANG['On'], canEdit('Devices') ) ?></td>
          <td><?= makeLink( "?view=".$_REQUEST['view']."&amp;action=device&amp;key=".$device['KeyString']."&amp;command=off", $SLANG['Off'], canEdit('Devices') ) ?></td>
        </tr>
<?php
}
?>
      </table>
      <p><a href="?view=console"><?= $SLANG['Console'] ?></a></p>
    </div>
  </div>
</body>
</html>
