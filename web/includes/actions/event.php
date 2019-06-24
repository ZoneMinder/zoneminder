<?php
//
// ZoneMinder web action
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

// If there is an action on an event, then we must have an id.
if ( !empty($_REQUEST['eid']) ) {
  ZM\Warning('No eid in action on event view');
  return;
}

// Event scope actions, view permissions only required
if ( canEdit('Events') ) {

  if ( ($action == 'rename') && isset($_REQUEST['eventName']) ) {
    dbQuery('UPDATE Events SET Name=? WHERE Id=?', array($_REQUEST['eventName'], $_REQUEST['eid']));
  } else if ( $action == 'eventdetail' ) {
      dbQuery('UPDATE Events SET Cause=?, Notes=? WHERE Id=?',
        array(
          $_REQUEST['newEvent']['Cause'],
          $_REQUEST['newEvent']['Notes'],
          $_REQUEST['eid']
        )
      );
    $refreshParent = true;
    $closePopup = true;
  } else if ( $action == 'archive' ) {
      dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array(1, $_REQUEST['eid']));
  } else if ( $action == 'unarchive' ) {
      dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array(0, $_REQUEST['eid']));
  } else if ( $action == 'delete' ) {
    deleteEvent($_REQUEST['eid']);
    $refreshParent = true;
  }
} // end if canEdit(Events)
?>
