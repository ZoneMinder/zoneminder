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

// System edit actions
if ( !canEdit('System') ) {
  ZM\Warning('Need System permission to edit Storage');
  return;
}

if ($action == 'save') {
  $storage = new ZM\Storage($_REQUEST['id']);

  $changes = $storage->changes($_REQUEST['newStorage']);

  if (count($changes)) {
    $storage->save($changes);
    $refreshParent = true;
  }
  $redirect = '?view=options&tab=storage';
} else {
  ZM\Error("Unknown action $action in saving Storage");
}
?>
