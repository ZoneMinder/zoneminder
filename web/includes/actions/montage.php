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
require_once('includes/MontageLayout.php');

if ( isset($_REQUEST['object']) ) {
  if ( $_REQUEST['object'] == 'MontageLayout' ) {
    $Layout = null;

    if ($action == 'Save') {
      # Name is only populated when creating a new layout
      if ( $_REQUEST['Name'] != '' ) {
        $Layout = new ZM\MontageLayout();
        $Layout->Name($_REQUEST['Name']);
      } else {
        $Layout = new ZM\MontageLayout(validCardinal($_REQUEST['zmMontageLayout']));
      }
      if (canEdit('System') or !$Layout->Id() or ($user->Id() == $Layout->UserId())) {
        $Layout->UserId($user->Id());
        $Layout->Positions($_REQUEST['Positions']);
        $Layout->save();
        zm_session_start();
        $_SESSION['zmMontageLayout'] = $Layout->Id();
        session_write_close();
        zm_setcookie('zmMontageLayout', $Layout->Id());
        $redirect = '?view=montage';
      } else {
        ZM\Warning('Need System permissions to edit layouts');
        return;
      } 
    } else if ($action == 'Delete') { // end if save
      if ( isset($_REQUEST['zmMontageLayout']) ) {
        $Layout = new ZM\MontageLayout(validCardinal($_REQUEST['zmMontageLayout']));
      } else {
        ZM\Warning('Name of layout to be deleted is not specified');
        return;
      }

      if (canEdit('System')) {
        if ($Layout->Id()) {
          $Layout->delete();
          zm_session_start();
          unset($_SESSION["zmMontageLayout"]);
          $_SESSION['zmMontageLayout'] = '';
          session_write_close();
          unset($_COOKIE['zmMontageLayout']);
          zm_setcookie('zmMontageLayout', '', array('expires'=>time()-3600*24)); //!!! After this JS still sees cookies, strange !!!
          $redirect = '?view=montage';
        } else {
          ZM\Warning('Layout Id=' . $_REQUEST['zmMontageLayout'] . ' not found for delete');
          $redirect = '?view=montage';
        }
      } else {
        ZM\Warning('Need System permissions to delete layouts');
        $redirect = '?view=montage';
      } 
    } else {// end if delete
      ZM\Warning("Unsupported action $action in montage");
    } // end if else
  } # end if isset($_REQUEST['object'] )
} # end if isset($_REQUEST['object'] )
?>
