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

if ( $action == 'user' ) {
  if ( canEdit('System') ) {
    if ( !empty($_REQUEST['uid']) )
      $dbUser = dbFetchOne('SELECT * FROM Users WHERE Id=?', NULL, array($_REQUEST['uid']));
    else
      $dbUser = array();

    $types = array();
    $changes = getFormChanges($dbUser, $_REQUEST['newUser'], $types);

    if ( function_exists('password_hash') ) {
      $pass_hash = '"'.password_hash($_REQUEST['newUser']['Password'], PASSWORD_BCRYPT).'"';
    } else {
      $pass_hash = ' PASSWORD('.dbEscape($_REQUEST['newUser']['Password']).') ';
      ZM\Info('Cannot use bcrypt as you are using PHP < 5.3');
    }
   
    if ( $_REQUEST['newUser']['Password'] ) {
      $changes['Password'] = 'Password = '.$pass_hash;
    } else {
      unset($changes['Password']);
    }

    if ( count($changes) ) {
      if ( !empty($_REQUEST['uid']) ) {
        dbQuery('UPDATE Users SET '.implode(', ', $changes).' WHERE Id = ?', array($_REQUEST['uid']));
        # If we are updating the logged in user, then update our session user data.
        if ( $user and ( $dbUser['Username'] == $user['Username'] ) ) {
          # We are the logged in user, need to update the $user object and generate a new auth_hash
          $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Id=?';
          $user = dbFetchOne($sql, NULL, array($_REQUEST['uid']));

          # Have to update auth hash in session
          zm_session_start();
          generateAuthHash(ZM_AUTH_HASH_IPS, true);
          session_write_close();
        }
      } else {
        dbQuery('INSERT INTO Users SET '.implode(', ', $changes));
      }
      $refreshParent = true;
    }
    $view = 'none';
  } else if ( ZM_USER_SELF_EDIT and ( $_REQUEST['uid'] == $user['Id'] ) ) {
    $uid = $user['Id'];

    $dbUser = dbFetchOne('SELECT Id, Password, Language FROM Users WHERE Id = ?', NULL, array($uid));

    $types = array();
    $changes = getFormChanges($dbUser, $_REQUEST['newUser'], $types);

    if ( function_exists('password_hash') ) {
      $pass_hash = '"'.password_hash($_REQUEST['newUser']['Password'], PASSWORD_BCRYPT).'"';
    } else {
      $pass_hash = ' PASSWORD('.dbEscape($_REQUEST['newUser']['Password']).') ';
      ZM\Info ('Cannot use bcrypt as you are using PHP < 5.3');
    }

    if ( !empty($_REQUEST['newUser']['Password']) ) {
      $changes['Password'] = 'Password = '.$pass_hash;
    } else {
      unset($changes['Password']);
    }
    if ( count($changes) ) {
      dbQuery('UPDATE Users SET '.implode(', ', $changes).' WHERE Id=?', array($uid));

      # We are the logged in user, need to update the $user object and generate a new auth_hash
      $sql = 'SELECT * FROM Users WHERE Enabled=1 AND Id=?';
      $user = dbFetchOne($sql, NULL, array($uid));
      
      zm_session_start();
      generateAuthHash(ZM_AUTH_HASH_IPS, true);
      session_write_close();
      $refreshParent = true;
    }
    $view = 'none';
  }
} // end if $action == user
?>
