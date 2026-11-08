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

if ( !isset($_REQUEST['markEids']) ) {
  ZM\Warning('Events actions require eids');
  return;
}

// Event scope actions, view permissions only required
if ( !canEdit('Events') ) {
  ZM\Warning('Events actions require Edit permissions');
  return;
} // end if ! canEdit(Events)

if ( $action == 'eventdetail' ) {
  $dbConn->beginTransaction();
  foreach ( (array)$_REQUEST['markEids'] as $markEid ) {
    // Events=Edit alone isn't enough: the event's monitor has to be one the user
    // may see, as for archiving in actions/events.php. Only a plain id: an array
    // would build the Event from the request instead of loading it.
    $markEid = is_scalar($markEid) ? validCardinal($markEid) : '';
    $event = ($markEid === '') ? null : new ZM\Event($markEid);
    if (!$event or !$event->Id() or !$event->canEdit()) {
      ZM\Warning('No permission to edit event '.validHtmlStr($markEid));
      continue;
    }
    dbQuery('UPDATE Events SET Name=?, Cause=?, Notes=? WHERE Id=?',
      array(
        $_REQUEST['newEvent']['Name'],
        $_REQUEST['newEvent']['Cause'],
        $_REQUEST['newEvent']['Notes'],
        $event->Id()
      )
    );
  }
  $dbConn->commit();
  // Back to the page the edit came from, if that is on this server.
  $redirect = localRedirectUrl(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', '?view=events');
}
?>
