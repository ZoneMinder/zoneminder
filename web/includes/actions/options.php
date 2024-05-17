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

global $error_message;

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
    foreach ($_REQUEST['markUids'] as $markUid)
      dbQuery('DELETE FROM Users WHERE Id = ?', array($markUid));
    if ($markUid == $user->Id()) {
      userLogout();
      $redirect = '?view=login';
    } else {
      $redirect = '?view=options&tab=users';
    }
  }
} else if ( $action == 'options' && isset($_REQUEST['tab']) ) {

  $result = dbQuery('SELECT Name,Value,Type,`System` FROM Config WHERE Category=? ORDER BY Id ASC', array($_REQUEST['tab']));
  if (!$result) {
    echo mysql_error();
    return;
  }

  $changed = false;
  while ($config = dbFetchNext($result)) {
    unset($newValue);
    if ( ($config['Type'] == 'boolean') and empty($_REQUEST['newConfig'][$config['Name']]) ) {
      $newValue = 0;
    } else if (isset($_REQUEST['newConfig'][$config['Name']])) {
      $newValue = preg_replace('/\r\n/', '\n', $_REQUEST['newConfig'][$config['Name']]);
    }

    if (isset($newValue) && ($newValue != $config['Value'])) {
      # Handle special cases first
      if ($config['Name'] == 'ZM_LANG_DEFAULT') {
        # Verify that the language file exists in the lang directory.
        if (!file_exists(ZM_PATH_WEB.'/lang/'.$newValue.'.php')) {
          $error_message .= 'Error setting ' . $config['Name'].'. New value ' .$newValue.' not saved because '.ZM_PATH_WEB.'/lang/'.$newValue.'.php doesn\'t exist.<br/>';
          ZM\Error($error_message);
          continue;
        }
      }
      dbQuery('UPDATE Config SET Value=? WHERE Name=?', array($newValue, $config['Name']));
      $changed = true;
    } # end if value changed
  } # end foreach config entry
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
} else if ($action == 'save') {
  if (isset($_REQUEST['object'])) {
    if ($_REQUEST['object'] == 'dnsmasq') {
      $config = isset($_REQUEST['config']) ? $_REQUEST['config'] : [];
      $conf = '';
      foreach ($config as $name=>$value) {
        if ($name == 'dhcp-host') {
          foreach ($value as $mac=>$ip) {
            $conf .= $name.'='.$mac.','.$ip.PHP_EOL;
          }
        } else if (
            ($name == 'bind-interfaces')
            or
            ($name == 'dhcp-authoritative')
            ) {
          if ($value=='yes') {
            $conf .= $name.PHP_EOL;
          }
        } else if ($name == 'dhcp-range') {
          $conf .= $name.'='.$value['min'].','.$value['max'].','.$value['expires'].PHP_EOL;
        } else {
          if (is_array($value)) {
            foreach ($value as $v) {
            }
          } else {
            $conf .= $name.'='.$value.PHP_EOL;
          }
        }
      }
      if (false===file_put_contents(ZM_PATH_DNSMASQ_CONF, $conf)) {
        ZM\Warning("Failed to writh to ".ZM_PATH_DNSMASQ_CONF);
      } else {
        exec('sudo -n /bin/systemctl restart dnsmasq.service');
      }
      exec('sudo -n /bin/systemctl restart dnsmasq.service');
    }
  }
} else if ($action == 'start') {
  if (isset($_REQUEST['object'])) {
    if ($_REQUEST['object'] == 'dnsmasq') {
      exec('sudo -n /bin/systemctl start dnsmasq.service', $output, $result);
      if ($result) {
              ZM\Warning("Error execing sudo -n /bin/systemctl start dnsmasq.service. Output: ".implode(PHP_EOL, $output));
      } else {
              ZM\Debug("Error execing sudo -n /bin/systemctl start dnsmasq.service. Output: ".implode(PHP_EOL, $output));
      }
    }
  }
} else if ($action == 'stop') {
  if (isset($_REQUEST['object'])) {
    if ($_REQUEST['object'] == 'dnsmasq') {
      exec('sudo -n /bin/systemctl stop dnsmasq.service', $output, $result);
      if ($result) {
              ZM\Warning("Error execing sudo -n /bin/systemctl start dnsmasq.service. Output: ".implode(PHP_EOL, $output));
      } else {
              ZM\Debug("Error execing sudo -n /bin/systemctl start dnsmasq.service. Output: ".implode(PHP_EOL, $output));
      }
    }
  }
} // end if object vs action
?>
