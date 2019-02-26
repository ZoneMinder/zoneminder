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

if ( !empty($_REQUEST['mid']) && canEdit('Monitors', $_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  $monitor = new ZM\Monitor($mid);
  
  if ( $action == 'delete' ) {
    if ( isset($_REQUEST['markZids']) ) {
      $restart_zmc = 0;
      foreach ( $_REQUEST['markZids'] as $markZid ) {

        if ( ! $restart_zmc ) {
          $zone = dbFetchOne('SELECT * FROM Zones WHERE Id=?', NULL, array($markZid));
          if ( $zone['Type'] == 'Privacy' ) {
            $restart_zmc = 1;
          }
        }
        dbQuery('DELETE FROM Zones WHERE MonitorId=? AND Id=?', array($mid, $markZid));
      }
      if ( daemonCheck() && $monitor->Type() != 'WebSite' ) {
        zmaControl($mid, 'stop');
        if ( $restart_zmc )
          zmcControl($mid, 'restart');
        zmaControl($mid, 'start');
      } // end if daemonCheck()
      $refreshParent = true;
    } // end if isset($_REQUEST['markZids'])
  } // end if action 
} // end if $mid and canEdit($mid)

?>
