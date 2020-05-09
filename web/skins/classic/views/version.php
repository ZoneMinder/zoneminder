<?php
//
// ZoneMinder web version view file, $Date$, $Revision$
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

if ( !canView('System') ) {
  $view = 'error';
  return;
}

$options = array(
  'go' => translate('GoToZoneMinder')
);

if ( verNum(ZM_DYN_CURR_VERSION) != verNum(ZM_DYN_LAST_VERSION) and canEdit('System') ) {
  $options = array_merge( $options, array(
    'ignore' => translate('VersionIgnore'),
    'hour'   => translate('VersionRemindHour'),
    'day'    => translate('VersionRemindDay'),
    'week'   => translate('VersionRemindWeek'),
    'month'  => translate('VersionRemindMonth'),
    'never'  => translate('VersionRemindNever')
  ) );
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Version'));
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Version') ?></h2>
    </div>
    <div id="content">
<?php
if ( ZM_DYN_DB_VERSION && (ZM_DYN_DB_VERSION != ZM_VERSION) ) {
?>
      <p class="errorText"><?php echo sprintf( $CLANG['VersionMismatch'], ZM_VERSION, ZM_DYN_DB_VERSION ) ?></p>
      <p><?php echo translate('RunLocalUpdate') ?></p>
      <div id="contentButtons">
<?php
} else if ( verNum( ZM_DYN_LAST_VERSION ) <= verNum( ZM_VERSION ) ) {
?>
      <p><?php echo sprintf($CLANG['RunningRecentVer'], ZM_VERSION) ?></p>
      <p><?php echo translate('UpdateNotNecessary') ?></p>
      <div id="contentButtons">
      <button type="button" data-on-click="zmWindow"><?php echo translate('GoToZoneMinder') ?></button>
<?php
} else {
?>
      <form name="contentForm" id="contentForm" method="get" action="?">
        <input type="hidden" name="view" value="version"/>
        <input type="hidden" name="action" value="version"/>
        <p><?php echo translate('UpdateAvailable') ?></p>
        <p><?php echo sprintf( $CLANG['LatestRelease'], ZM_DYN_LAST_VERSION, ZM_VERSION ) ?></p>
        <p><?php echo buildSelect('option', $options); ?></p>
        <div id="contentButtons">
<?php
  if ( canEdit('System') ) {
?>
          <button type="submit"><?php echo translate('Apply') ?></button>
<?php
  }
}
?>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Close') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
