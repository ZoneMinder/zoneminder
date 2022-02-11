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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Devices' ) )
{
    $view = "error";
     return;
}

$sql = "SELECT * FROM Devices WHERE Type = 'X10' ORDER BY Name";
$devices = array();
foreach( dbFetchAll( $sql ) as $row )
{
    $row['Status'] = getDeviceStatusX10( $row['KeyString'] );
    $devices[] = $row;
}

xhtmlHeaders(__FILE__, translate('Devices') );
?>
<body>
  <?php echo getNavBarHTML(); ?>
  <div id="page" class="container-fluid">
    <h2>X10 <?php echo translate('Devices') ?></h2>

    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <button id="newDeviceBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('New') ?>"><i class="fa fa-plus"></i></button>
      <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>

    <div id="content" class="row justify-content-center table-responsive-sm">
        <table
          id="devicesTable"
          data-locale="<?php echo i18n() ?>"
          class="table-sm table-borderless"
          data-search="true"
          data-cookie="true"
          data-cookie-id-table="zmDevicesTable"
          data-cookie-expire="2y"
          data-cookie-expire="2y"
          data-remember-order="true"
          data-click-to-select="true"
          data-maintain-meta-data="true"
          data-buttons-class="btn btn-normal"
          data-toolbar="#toolbar"
          data-show-columns="true"
        >

          <thead>
            <tr>
              <th data-sortable="true" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="true" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="KeyString"><?php echo translate('KeyString') ?></th>
              <th data-sortable="false" data-field="On"><?php echo translate('On') ?></th>
              <th data-sortable="false" data-field="Off"><?php echo translate('Off') ?></th>
              <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
            </tr>
          </thead>

          <tbody>
<?php
foreach( $devices as $device ) {

  if ( $device['Status'] == 'ON' ) {
    $fclass = "infoText";
  } else if ( $device['Status'] == 'OFF' ) {
    $fclass = "warnText";
  } else {
    $fclass = "errorText";
  }
  
  $str_opt = 'class="deviceCol" data-did="'.$device['Id'].'"';
?>
            <tr>
              <td><?php echo $device['Id'] ?></td>
              <td><?php echo makeLink( '#', '<span class="'.$fclass.'">'.validHtmlStr($device['Name']).'</span>', canEdit( 'Devices' ), $str_opt ) ?></td>
              <td><?php echo makeLink( '#', '<span class="'.$fclass.'">'.validHtmlStr($device['KeyString']).'</span>', canEdit( 'Devices' ), $str_opt ) ?></td>
              <td><button class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('On') ?>"><i class="fa fa-toggle-on"></i></button></td>
              <td><button class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Off') ?>"><i class="fa fa-toggle-off"></i></button></td>
              <td data-checkbox="true"></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
    </div>
  </div>
<?php xhtmlFooter() ?>
