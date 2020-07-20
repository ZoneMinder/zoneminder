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

$selfEdit = ZM_USER_SELF_EDIT && ($_REQUEST['uid'] == $user['Id']);

if ( !canEdit('System') && !$selfEdit ) {
  $view = 'error';
  return;
}

if ( $_REQUEST['uid'] ) {
	if ( !($newUser = dbFetchOne('SELECT * FROM Users WHERE Id = ?', NULL, ARRAY($_REQUEST['uid']))) ) {
		$view = 'error';
		return;
	}
} else {
	$newUser = array();
	$newUser['Username'] = translate('NewUser');
	$newUser['Enabled'] = 1;
	$newUser['MonitorIds'] = '';
}

$monitorIds = array_flip(explode(',', $newUser['MonitorIds']));

$yesno = array( 0=>translate('No'), 1=>translate('Yes') );
$nv = array( 'None'=>translate('None'), 'View'=>translate('View') );
$nve = array( 'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit') );
$bandwidths = array_merge( array( ''=>'' ), $bandwidth_options );
$langs = array_merge( array( ''=>'' ), getLanguages() );

$sql = 'SELECT Id, Name FROM Monitors ORDER BY Sequence ASC';
$monitors = array();
foreach ( dbFetchAll($sql) as $monitor ) {
  if ( visibleMonitor($monitor['Id']) ) {
    $monitors[$monitor['Id']] = $monitor;
  }
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('User').' - '.$newUser['Username']);
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('User').' - '.validHtmlStr($newUser['Username']); ?></h2>
    </div>
    <div id="content">
      <form id="contentForm" name="contentForm" method="post" action="?view=user">
        <input type="hidden" name="uid" value="<?php echo validHtmlStr($_REQUEST['uid']) ?>"/>
        <table id="contentTable" class="major">
          <tbody>
<?php
if ( canEdit('System') ) {
?>
            <tr>
              <th scope="row"><?php echo translate('Username') ?></th>
              <td><input type="text" name="newUser[Username]" value="<?php echo validHtmlStr($newUser['Username']); ?>"<?php echo $newUser['Username'] == 'admin' ? ' readonly="readonly"':''?>/></td>
            </tr>
<?php
}
?>
            <tr>
              <th scope="row"><?php echo translate('NewPassword') ?></th>
              <td><input type="password" name="newUser[Password]" autocomplete="new-password"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ConfirmPassword') ?></th>
              <td><input type="password" name="conf_password" autocomplete="new-password"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Language') ?></th>
              <td><?php echo htmlSelect('newUser[Language]', $langs, $newUser['Language']) ?></td>
            </tr>
<?php
if ( canEdit('System') and ( $newUser['Username'] != 'admin' ) ) {
?>
            <tr>
              <th scope="row"><?php echo translate('Enabled') ?></th>
              <td><?php echo htmlSelect('newUser[Enabled]', $yesno, $newUser['Enabled']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Stream') ?></th>
              <td><?php echo htmlSelect('newUser[Stream]', $nv, $newUser['Stream']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Events') ?></th>
              <td><?php echo htmlSelect('newUser[Events]', $nve, $newUser['Events']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Control') ?></th>
              <td><?php echo htmlSelect('newUser[Control]', $nve, $newUser['Control']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Monitors') ?></th>
              <td><?php echo htmlSelect('newUser[Monitors]', $nve, $newUser['Monitors']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Groups') ?></th>
              <td><?php echo htmlSelect('newUser[Groups]', $nve, $newUser['Groups']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('System') ?></th>
              <td><?php echo htmlSelect('newUser[System]', $nve, $newUser['System']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('MaxBandwidth') ?></th>
              <td><?php echo htmlSelect('newUser[MaxBandwidth]', $bandwidths, $newUser['MaxBandwidth']) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('RestrictedMonitors') ?></th>
              <td>
<?php echo htmlSelect('newUser[MonitorIds][]', $monitors, explode(',', $newUser['MonitorIds']), array('multiple'=>'multiple')); ?>
              </td>
            </tr>
<?php if ( ZM_OPT_USE_API ) { ?>
            <tr>
              <th scope="row"><?php echo translate('APIEnabled')?></th>
              <td><?php echo htmlSelect('newUser[APIEnabled]', $yesno, $newUser['APIEnabled']) ?></td>
            </tr>

<?php
      } // end if ZM_OPT_USE_API
} // end if canEdit(System)
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="submit" name="action" value="Save"><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
