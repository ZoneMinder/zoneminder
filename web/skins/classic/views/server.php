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

$Server = new ZM\Server($_REQUEST['id']);
if ( $_REQUEST['id'] and ! $Server->Id() ) {
  $view = 'error';
  return;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Server').' - '.$Server->Name());
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Server').' - '.$Server->Name() ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="post" action="?" class="validateFormOnSubmit">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="object" value="server"/>
        <input type="hidden" name="id" value="<?php echo validHtmlStr($_REQUEST['id']) ?>"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newServer[Name]" value="<?php echo $Server->Name() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Protocol') ?></th>
              <td><input type="text" name="newServer[Protocol]" value="<?php echo $Server->Protocol() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Hostname') ?></th>
              <td><input type="text" name="newServer[Hostname]" value="<?php echo $Server->Hostname() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Port') ?></th>
              <td><input type="number" name="newServer[Port]" value="<?php echo $Server->Port() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('PathToIndex') ?></th>
              <td><input type="text" name="newServer[PathToIndex]" value="<?php echo $Server->PathToIndex() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('PathToZMS') ?></th>
              <td><input type="text" name="newServer[PathToZMS]" value="<?php echo $Server->PathToZMS() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('PathToApi') ?></th>
              <td><input type="text" name="newServer[PathToApi]" value="<?php echo $Server->PathToApi() ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunStats') ?></th>
              <td>
                <input type="radio" name="newServer[zmstats]" value="1"<?php echo $Server->zmstats() ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmstats]" value="0"<?php echo $Server->zmstats() ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunAudit') ?></th>
              <td>
                <input type="radio" name="newServer[zmaudit]" value="1"<?php echo $Server->zmaudit() ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmaudit]" value="0"<?php echo $Server->zmaudit() ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunTrigger') ?></th>
              <td>
                <input type="radio" name="newServer[zmtrigger]" value="1"<?php echo $Server->zmtrigger() ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmtrigger]" value="0"<?php echo $Server->zmtrigger() ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RunEventNotification') ?></th>
              <td>
                <input type="radio" name="newServer[zmeventnotification]" value="1"<?php echo $Server->zmeventnotification() ? ' checked="checked"' : '' ?>/> Yes
                <input type="radio" name="newServer[zmeventnotification]" value="0"<?php echo $Server->zmeventnotification() ? '' : ' checked="checked"' ?>/> No
              </td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="submit" name="action" value="Save" ><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
