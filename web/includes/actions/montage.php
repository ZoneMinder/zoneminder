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

if ( isset($_REQUEST['object']) ) {
  if ( $_REQUEST['object'] == 'MontageLayout' ) {
    // System edit actions
    if ( ! canEdit('System') ) {
      ZM\Warning('Need System permissions to edit layouts');
      return;
    }
    require_once('includes/MontageLayout.php');
    if ( $action == 'Save' ) {
      $Layout = null;
      if ( $_REQUEST['Name'] != '' ) {
        $Layout = new ZM\MontageLayout();
        $Layout->Name($_REQUEST['Name']);
      } else {
        $Layout = new ZM\MontageLayout($_REQUEST['zmMontageLayout']);
      }
      $Layout->Positions($_REQUEST['Positions']);
      $Layout->save();
      zm_session_start();
      $_SESSION['zmMontageLayout'] = $Layout->Id();
      setcookie('zmMontageLayout', $Layout->Id(), 1);
      session_write_close();
      $redirect = '?view=montage';
    } // end if save

  } # end if isset($_REQUEST['object'] )
} # end if isset($_REQUEST['object'] )
?>
