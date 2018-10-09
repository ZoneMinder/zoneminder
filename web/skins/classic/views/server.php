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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canEdit('System') ) {
  $view = 'error';
  return;
}

if ( $_REQUEST['id'] ) {
	if ( !($newServer = dbFetchOne('SELECT * FROM Servers WHERE Id = ?', NULL, ARRAY($_REQUEST['id'])) ) ) {
		$view = 'error';
		return;
	}
} else {
	$newServer = array();
	$newServer['Name'] = translate('NewServer');
	$newServer['Hostname'] = '';
	$newServer['zmstats'] = '';
	$newServer['zmaudit'] = '';
	$newServer['zmtrigger'] = '';
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Server').' - '.$newServer['Name']);
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Server').' - '.$newServer['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm(this, <?php echo empty($newServer['Name'])?'true':'false' ?>)">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="object" value="server"/>
        <input type="hidden" name="id" value="<?php echo validHtmlStr($_REQUEST['id']) ?>"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newServer[Name]" value="<?php echo $newServer['Name'] ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Hostname') ?></th>
              <td><input type="text" name="newServer[Hostname]" value="<?php echo $newServer['Hostname'] ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunStats') ?></th>
              <td>
                <input type="radio" name="newServer[zmstats]" value="1"<?php echo $newServer['zmstats'] ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmstats]" value="0"<?php echo $newServer['zmstats'] ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunAudit') ?></th>
              <td>
                <input type="radio" name="newServer[zmaudit]" value="1"<?php echo $newServer['zmaudit'] ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmaudit]" value="0"<?php echo $newServer['zmaudit'] ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunTrigger') ?></th>
              <td>
                <input type="radio" name="newServer[zmtrigger]" value="1"<?php echo $newServer['zmtrigger'] ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmtrigger]" value="0"<?php echo $newServer['zmtrigger'] ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="hidden" name="action" value="Save"/>
          <input type="submit" value="<?php echo translate('Save') ?>"/>
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
