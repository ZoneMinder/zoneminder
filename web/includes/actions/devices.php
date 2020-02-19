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

// Device view actions
if ( !canEdit('Devices') ) {
  ZM\Warning('No devices permission in editing device');
  return;
} // end if !canEdit(Devices)

if ( $action == 'delete' ) {
  if ( isset($_REQUEST['markDids']) ) {
    foreach( $_REQUEST['markDids'] as $markDid ) {
      dbQuery('DELETE FROM Devices WHERE Id=?', array($markDid));
      $refreshParent = true;
    }
  }
} // end if action
?>
