<?php
//
// ZoneMinder web user view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) ) {
  $view = 'error';
  return;
}

if ( $_REQUEST['id'] ) {
  if ( !($newStorage = dbFetchOne('SELECT * FROM Storage WHERE Id=?', NULL, ARRAY($_REQUEST['id'])) ) ) {
    $view = 'error';
    return;
    $newStorage['ServerId'] = '';
  }
} else {
  $newStorage = array();
  $newStorage['Name'] = translate('NewStorage');
  $newStorage['Path'] = '';
  $newStorage['Type'] = 'local';
  $newStorage['Url'] = '';
  $newStorage['Scheme'] = 'Medium';
  $newStorage['StorageId'] = '';
  $newStorage['ServerId'] = '';
  $newStorage['DoDelete'] = 1;
}

$type_options = array( 'local' => translate('Local'), 's3fs' => translate('s3fs') );
$scheme_options = array(
  'Deep' => translate('Deep'),
  'Medium' => translate('Medium'),
  'Shallow' => translate('Shallow'),
);

$servers = Server::find( null, array('order'=>'lower(Name)') );
$ServersById = array();
foreach ( $servers as $S ) {
  $ServersById[$S->Id()] = $S;
}
$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Storage')." - ".$newStorage['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Storage')." - ".$newStorage['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm( this, <?php echo empty($newStorage['Name'])?'true':'false' ?> )">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="object" value="storage"/>
        <input type="hidden" name="id" value="<?php echo validHtmlStr($_REQUEST['id']) ?>"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newStorage[Name]" value="<?php echo $newStorage['Name'] ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Path') ?></th>
              <td><input type="text" name="newStorage[Path]" value="<?php echo $newStorage['Path'] ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Url') ?></th>
              <td><input type="text" name="newStorage[Url]" value="<?php echo $newStorage['Url'] ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Server') ?></th>
              <td><?php echo htmlSelect( 'newStorage[ServerId]', array(''=>'Remote / No Specific Server') + $ServersById, $newStorage['ServerId'] ); ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Type') ?></th>
              <td><?php echo htmlSelect( 'newStorage[Type]', $type_options, $newStorage['Type'] ); ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('StorageScheme') ?></th>
              <td><?php echo htmlSelect( 'newStorage[Scheme]', $scheme_options, $newStorage['Scheme'] ); ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('StorageDoDelete') ?></th>
              <td>
              <input type="radio" name="newStorage[DoDelete]" value="1"<?php echo $newStorage['DoDelete'] ? 'checked="checked"' : '' ?>/>Yes
              <input type="radio" name="newStorage[DoDelete]" value="0"<?php echo $newStorage['DoDelete'] ? '' : 'checked="checked"' ?>/>No
              </td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <button name="action" type="submit" value="Save"><?php echo translate('Save') ?></button>
          <button type="button" onclick="closeWindow();"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
