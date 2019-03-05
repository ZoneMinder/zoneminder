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

if ( !canEdit('System') ) {
  ZM\Warning('Need System permissions to update donation');
  return;
}

if ( $action == 'donate' && isset($_REQUEST['option']) ) {
  $option = $_REQUEST['option'];
  switch( $option ) {
    case 'go' :
      // Ignore this, the caller will open the page itself
      break;
    case 'hour' :
    case 'day' :
    case 'week' :
    case 'month' :
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
      dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_DONATE_REMINDER_TIME'");
      break;
    case 'never' :
    case 'already' :
      dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_DYN_SHOW_DONATE_REMINDER'");
      break;
    default :
      Warning("Unknown value for option in donate: $option");
      break;
  } // end switch option
  $view = 'none';
}
?>
