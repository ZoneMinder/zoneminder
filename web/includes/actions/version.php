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
  ZM\Warning('Need System permissions to update version');
  return;
}
if ( $action == 'version' && isset($_REQUEST['option']) ) {
  $option = $_REQUEST['option'];
  switch( $option ) {
  case 'go' :
    // Ignore this, the caller will open the page itself
    break;
  case 'ignore' :
    dbQuery("UPDATE Config SET Value = '".ZM_DYN_LAST_VERSION."' WHERE Name = 'ZM_DYN_CURR_VERSION'");
    break;
  case 'hour' :
  case 'day' :
  case 'week' :
    $nextReminder = time();
    if ( $option == 'hour' ) {
      $nextReminder += 60*60;
    } elseif ( $option == 'day' ) {
      $nextReminder += 24*60*60;
    } elseif ( $option == 'week' ) {
      $nextReminder += 7*24*60*60;
    } elseif ( $option == 'month' ) {
      $nextReminder += 30*24*60*60;
    }
    dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_NEXT_REMINDER'");
    break;
  case 'never' :
    dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_CHECK_FOR_UPDATES'");
    break;
  } // end switch (option)
}
