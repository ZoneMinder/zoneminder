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

if ( !isset($_REQUEST['eids']) ) {
  ZM\Warning('Events actions require eids');
  return;
}

// Event scope actions, view permissions only required
if ( !canEdit('Events') ) {
  ZM\Warning('Events actions require Edit permissions');
  return;
} // end if ! canEdit(Events)

if ( $action == 'archive' ) {
  $dbConn->beginTransaction();
  foreach( getAffectedIds('eids') as $markEid ) {
    dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array(1, $markEid));
  }
  $dbConn->commit();
  $refreshParent = true;
} else if ( $action == 'unarchive' ) {
  $dbConn->beginTransaction();
  foreach( getAffectedIds('eids') as $markEid ) {
    dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array(0, $markEid));
  }
  $dbConn->commit();
  $refreshParent = true;
} else if ( $action == 'delete' ) {
  foreach ( getAffectedIds('eids') as $markEid ) {
    deleteEvent($markEid);
  }
  $refreshParent = true;
}
?>
