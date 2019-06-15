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
  ZM\Warning('Must have System permissions to perform options actions');
  return;
}

if ( $action == 'delete' ) {
  if ( isset($_REQUEST['object']) ) {
    if ( $_REQUEST['object'] == 'server' ) {
      if ( !empty($_REQUEST['markIds']) ) {
        foreach( $_REQUEST['markIds'] as $Id )
          dbQuery('DELETE FROM Servers WHERE Id=?', array($Id));
      }
      $refreshParent = true;
    } else if ( $_REQUEST['object'] == 'storage' ) {
      if ( !empty($_REQUEST['markIds']) ) {
        foreach( $_REQUEST['markIds'] as $Id )
          dbQuery('DELETE FROM Storage WHERE Id=?', array($Id));
      }
      $refreshParent = true;
    } # end if isset($_REQUEST['object'] )
  } else if ( isset($_REQUEST['markUids']) ) {
    // deletes users
    foreach( $_REQUEST['markUids'] as $markUid )
      dbQuery('DELETE FROM Users WHERE Id = ?', array($markUid));
    if ( $markUid == $user['Id'] )
      userLogout();
  }

} else if ( $action == 'options' && isset($_REQUEST['tab']) ) {

  $result = dbQuery('SELECT Name,Value,Type FROM Config WHERE Category=? ORDER BY Id ASC', array($_REQUEST['tab']));
  if ( !$result ) {
    echo mysql_error();
    return;
  }

  $changed = false;
  while( $config = dbFetchNext($result) ) {
    unset($newValue);
    if ( $config['Type'] == 'boolean' && empty($_REQUEST['newConfig'][$config['Name']]) ) {
      $newValue = 0;
    } else if ( isset($_REQUEST['newConfig'][$config['Name']]) ) {
      $newValue = preg_replace("/\r\n/", "\n", stripslashes($_REQUEST['newConfig'][$config['Name']]));
    }

    if ( isset($newValue) && ($newValue != $config['Value']) ) {
      dbQuery('UPDATE Config SET Value=? WHERE Name=?', array($newValue, $config['Name']));
      $changed = true;
    }
  }
  if ( $changed ) {
    switch ( $_REQUEST['tab'] ) {
    case 'system' :
    case 'config' :
      $restartWarning = true;
      break;
    case 'API':
    case 'web' :
    case 'tools' :
      break;
    case 'logging' :
    case 'network' :
    case 'mail' :
    case 'upload' :
      $restartWarning = true;
      break;
    case 'highband' :
    case 'medband' :
    case 'lowband' :
      break;
    }
    $redirect = '?view=options&tab='.$_REQUEST['tab'];
    loadConfig(false);
    # Might need to update auth hash
    # This doesn't work because the config are constants and won't really be loaded until the next refresh.
    #generateAuthHash(ZM_AUTH_HASH_IPS, true);
  }
  return;
} // end if object vs action

?>
