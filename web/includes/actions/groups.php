<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
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

// Group view actions
if ( ($action == 'setgroup') && canView('Groups')) {
  if ( !empty($_REQUEST['gid']) ) {
    zm_setcookie('zmGroup', validInt($_REQUEST['gid']));
  } else {
    zm_setcookie('zmGroup', '', time()-3600*24*2);
  }
  $refreshParent = true;
  return;
}

// Group edit actions
# Deleting a group also needs edit on each of its monitors, checked below.
if ( ! canEdit('Groups') ) {
  ZM\Warning('Need group edit permissions to edit groups');
  return;
}

if ( $action == 'delete' ) {
  if ( !empty($_REQUEST['gid']) ) {
    foreach ( ZM\Group::find(array('Id'=>$_REQUEST['gid'])) as $Group ) {
      // Deleting a group removes its monitors from it, which can lift a deny on them.
      if (!ZM\Group::canEditMembership($Group->MonitorIds())) {
        ZM\Warning('Need edit permission on every monitor in group '.$Group->Id().' to delete it');
        continue;
      }
      $Group->delete();
      ZM\AuditAction('delete', 'group', $Group->Id(), 'Name: '.$Group->Name());
    }
  }
  $redirect = '?view=groups';
  $refreshParent = true;
} # end if action
?>
