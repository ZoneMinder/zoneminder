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

require_once('includes/config_values.php');

// AI object-detection CRUD lives in dedicated action files. The list views post
// here (view=options) with object=ai_* for deletes; route those to the matching
// handler, which processes both save and delete via $action.
if ( isset($_REQUEST['object']) && in_array($_REQUEST['object'], array('ai_model', 'ai_dataset', 'ai_class'), true) ) {
  require_once('includes/actions/'.$_REQUEST['object'].'.php');
  return;
}

if ( $action == 'delete' ) {
  if ( isset($_REQUEST['object']) ) {
    if ( $_REQUEST['object'] == 'server' ) {
      if ( !empty($_REQUEST['markIds']) ) {
        // Monitors, storage areas and cron jobs hold a ServerId with no foreign key: refuse rather than orphan them.
        foreach ( $_REQUEST['markIds'] as $key => $Id ) {
          $Id = validCardinal($Id);
          $_REQUEST['markIds'][$key] = $Id;
          $summary = ZM\Server::InUseSummary($Id);
          if ( $summary ) {
            $error_message .= 'Server '.validHtmlStr($Id).' is still used by '.$summary.' and cannot be deleted.<br/>';
            unset($_REQUEST['markIds'][$key]);
          }
        }
        foreach ( $_REQUEST['markIds'] as $Id ) {
          if ( dbQuery('DELETE FROM Servers WHERE Id=?', array($Id)) === null ) {
            $error_message .= 'Error deleting server '.validHtmlStr($Id).': '.validHtmlStr(dbLastError()).'<br/>';
            continue;
          }
          ZM\AuditAction('delete', 'server', $Id, '');
        }
      }
      if ( !$error_message ) $refreshParent = true;
    } else if ( $_REQUEST['object'] == 'storage' ) {
      if ( !empty($_REQUEST['markIds']) ) {
        foreach ( $_REQUEST['markIds'] as $Id ) {
          $Storage = ZM\Storage::find_one(array('Id'=>validCardinal($Id)));
          if ( !$Storage ) continue;
          if ( $Storage->EventCount() or $Storage->MonitorCount() ) {
            $error_message .= 'Storage area '.validHtmlStr($Storage->Name()).' is in use by events or monitors and cannot be deleted.<br/>';
            continue;
          }
          if ( dbQuery('DELETE FROM Storage WHERE Id=?', array($Storage->Id())) === null ) {
            $error_message .= 'Error deleting storage area '.validHtmlStr($Storage->Name()).': '.validHtmlStr(dbLastError()).'<br/>';
            continue;
          }
          ZM\AuditAction('delete', 'storage', $Storage->Id(), '');
        }
      }
      if ( !$error_message ) $refreshParent = true;
    } else if ( $_REQUEST['object'] == 'role' ) {
      if ( !empty($_REQUEST['markRids']) ) {
        foreach ( $_REQUEST['markRids'] as $Id ) {
          if ( dbQuery('DELETE FROM User_Roles WHERE Id=?', array($Id)) === null ) {
            $error_message .= 'Error deleting role '.validHtmlStr($Id).': '.validHtmlStr(dbLastError()).'<br/>';
            continue;
          }
          ZM\AuditAction('delete', 'role', $Id, '');
        }
      }
      if ( !$error_message ) $redirect = '?view=options&tab=roles';
    } # end if isset($_REQUEST['object'] )
  } else if ( isset($_REQUEST['markUids']) ) {
    // deletes users
    foreach ($_REQUEST['markUids'] as $markUid) {
      # User_Preferences has no ON DELETE CASCADE, so the user can't be deleted while it has rows.
      if (dbQuery('DELETE FROM User_Preferences WHERE UserId = ?', array($markUid)) === null) {
        $error_message .= 'Error deleting user '.validHtmlStr($markUid).': '.validHtmlStr(dbLastError()).'<br/>';
        continue;
      }
      if (dbQuery('DELETE FROM Users WHERE Id = ?', array($markUid)) === null) {
        $error_message .= 'Error deleting user '.validHtmlStr($markUid).': '.validHtmlStr(dbLastError()).'<br/>';
        continue;
      }
      ZM\AuditAction('delete', 'user', $markUid, '');
    }
    if ($error_message == '') {
      if (in_array($user->Id(), $_REQUEST['markUids'])) {
        userLogout();
        $redirect = '?view=login';
      } else {
        $redirect = '?view=options&tab=users';
      }
    }
  }
} else if ( $action == 'options' && isset($_REQUEST['tab']) ) {

  $result = dbQuery('SELECT Name,Value,Type,DefaultValue,Requires,`System`,`Readonly` FROM Config WHERE Category=? ORDER BY Id ASC', array($_REQUEST['tab']));
  if (!$result) {
    $error_message .= 'Error loading config for tab ' . htmlspecialchars($_REQUEST['tab']) . ': ' . htmlspecialchars(dbLastError()) . '<br/>';
    return;
  }

  // Options set in zm.conf or conf.d belong to the system owner and can't be changed here: skip
  // a submitted value, and a boolean left out of the form (a disabled checkbox isn't sent),
  // which would otherwise be saved as 0.
  $fileConfig = loadConfigFiles();
  $effective = array();
  foreach (dbFetchAll('SELECT Name,Value,Type FROM Config') as $row) {
    $effective[$row['Name']] = isset($fileConfig[$row['Name']])
      ? configFileValue($fileConfig[$row['Name']]['Value'], $row['Type']) : $row['Value'];
  }
  // Options whose value is a number of seconds that must be at least 1. The
  // type check only proves they are numeric: ZM_WATCH_CHECK_INTERVAL=0 makes
  // zmwatch loop on sleep(0) and query the database nonstop, and
  // ZM_WATCH_MAX_DELAY=0 makes it restart every capture daemon on every pass.
  $optionMinimums = array(
    'ZM_WATCH_CHECK_INTERVAL' => 1,
    'ZM_WATCH_MAX_DELAY' => 1,
  );
  $changed = false;
  while ($config = dbFetchNext($result)) {
    unset($newValue);
    if (isset($fileConfig[$config['Name']])) {
      if (isset($_REQUEST['newConfig'][$config['Name']]))
        ZM\Debug('Not saving '.$config['Name'].': it is set in '.$fileConfig[$config['Name']]['File']);
      continue;
    }
    // Readonly rows are maintained by the daemons (version detection, telemetry),
    // so a posted value for one is not saved, and not counted as a change.
    if (!empty($config['Readonly'])) continue;
    // System rows (paths the daemons execute, such as ZM_PATH_FFMPEG) are only changed in
    // the config files, as the Options page already shows them.
    if (!empty($config['System'])) continue;
    if ( ($config['Type'] == 'boolean') and empty($_REQUEST['newConfig'][$config['Name']]) ) {
      // A checkbox disabled for unmet requirements isn't sent: not the user turning it off.
      if (!empty($config['Requires']) and !configRequirementsMet($config['Requires'], $effective)) continue;
      $newValue = 0;
    } else if (isset($_REQUEST['newConfig'][$config['Name']])) {
      $newValue = preg_replace("/\r\n/", "\n", $_REQUEST['newConfig'][$config['Name']]);
    }

    if (isset($newValue)) $effective[$config['Name']] = $newValue;
    # Compare as strings: loosely, "1e3" equals "1000" and "007" equals "7", so such an edit was dropped.
    if (isset($newValue) && ((string)$newValue !== (string)$config['Value'])) {
      # Handle special cases first
      if ($config['Name'] == 'ZM_LANG_DEFAULT') {
        # Verify that the language file exists in the lang directory.
        if (!file_exists(ZM_PATH_WEB.'/lang/'.$newValue.'.php')) {
          $error_message .= 'Error setting ' . $config['Name'].'. New value ' .$newValue.' not saved because '.ZM_PATH_WEB.'/lang/'.$newValue.'.php doesn\'t exist.<br/>';
          ZM\Error($error_message);
          continue;
        }
      }
      if (($config['Name'] == 'ZM_AUTH_HASH_SECRET') and !authHashSecretValid($newValue)) {
        $error_message .= 'Error setting ZM_AUTH_HASH_SECRET. New value not saved because it must be at least '.ZM_AUTH_HASH_SECRET_MIN_LENGTH.' characters long and not the shipped default.<br/>';
        ZM\Error('Not saving ZM_AUTH_HASH_SECRET: shorter than '.ZM_AUTH_HASH_SECRET_MIN_LENGTH.' characters or the shipped default');
        continue;
      }
      if (!configValueIsValid($newValue, $config['Type'], (string)$config['DefaultValue'])) {
        $error_message .= 'Error setting ' . htmlspecialchars($config['Name']) . ': ' . htmlspecialchars($newValue) . ' is not a valid ' . $config['Type'] . '.<br/>';
        continue;
      }
      if (isset($optionMinimums[$config['Name']]) and (float)$newValue < $optionMinimums[$config['Name']]) {
        $minimum = $optionMinimums[$config['Name']];
        $error_message .= 'Error setting ' . htmlspecialchars($config['Name']) . ': ' . htmlspecialchars($newValue) . ' is below the minimum of ' . $minimum . '.<br/>';
        ZM\Error('Not saving '.$config['Name'].': '.$newValue.' is below the minimum of '.$minimum);
        continue;
      }
      if (dbQuery('UPDATE Config SET Value=? WHERE Name=?', array($newValue, $config['Name'])) === null) {
        $error_message .= 'Error saving ' . htmlspecialchars($config['Name']) . ': ' . htmlspecialchars(dbLastError()) . '<br/>';
        ZM\Error('Error saving config '.$config['Name'].': '.dbLastError());
      } else {
        $changed = true;
      }
    } # end if value changed
  } # end foreach config entry
  if ( $changed ) {
    ZM\AuditAction('update', 'config', 0, 'Tab: '.$_REQUEST['tab']);
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
    # Read by zmc (image quality, MQTT publishing) and zmtelemetry when they start.
    case 'images' :
    case 'MQTT' :
    case 'telemetry' :
      $restartWarning = true;
      break;
    case 'highband' :
    case 'medband' :
    case 'lowband' :
      break;
    }
    loadConfig(false);
    # Might need to update auth hash
    # This doesn't work because the config are constants and won't really be loaded until the next refresh.
    #generateAuthHash(ZM_AUTH_HASH_IPS, true);
  }
  # On error stay on the page (no redirect) so the error is shown; otherwise
  # redirect to drop the POST and reload with fresh values.
  if (!$error_message) {
    $redirect = '?view=options&tab='.$_REQUEST['tab'].(empty($restartWarning) ? '' : '&restart=1');
  }
  return;
} else if ($action == 'save') {
  if (isset($_REQUEST['object'])) {
    if ($_REQUEST['object'] == 'dnsmasq') {
      $config = isset($_REQUEST['config']) ? $_REQUEST['config'] : [];
      $conf = dnsmasqConfText($config);
      if ($conf === false) {
        ZM\Warning('Refusing to write the dnsmasq configuration: it names a directive that is not allowed or has a line break in it');
      } else if (false===file_put_contents(ZM_PATH_DNSMASQ_CONF, $conf)) {
        ZM\Warning("Failed to writh to ".ZM_PATH_DNSMASQ_CONF);
      } else {
        exec('sudo -n /bin/systemctl restart dnsmasq.service');
      }
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
} else if ($action == 'menuitems') {
  if (!canEdit('System')) {
    ZM\Warning('Need System permission to edit menu items');
  } else if (isset($_REQUEST['items'])) {
    require_once('includes/MenuItem.php');
    $allItems = ZM\MenuItem::find();
    foreach ($allItems as $item) {
      $id = $item->Id();
      // An entry that was not on the submitted form (added by another admin
      // since the page loaded) must keep its settings, not be disabled.
      if (!isset($_REQUEST['items'][$id])) continue;
      $enabled = isset($_REQUEST['items'][$id]['Enabled']) ? 1 : 0;
      $label = isset($_REQUEST['items'][$id]['Label']) && is_string($_REQUEST['items'][$id]['Label']) ? trim($_REQUEST['items'][$id]['Label']) : null;
      $sortOrder = isset($_REQUEST['items'][$id]['SortOrder']) ? intval($_REQUEST['items'][$id]['SortOrder']) : $item->SortOrder();
      if ($label === '') $label = null;

      $iconType = isset($_REQUEST['items'][$id]['IconType']) && is_string($_REQUEST['items'][$id]['IconType']) ? $_REQUEST['items'][$id]['IconType'] : $item->IconType();
      if (!in_array($iconType, ['material', 'fontawesome', 'image', 'none'])) $iconType = 'material';
      $icon = isset($_REQUEST['items'][$id]['Icon']) && is_string($_REQUEST['items'][$id]['Icon']) ? trim($_REQUEST['items'][$id]['Icon']) : $item->Icon();
      if ($icon === '') $icon = null;

      $link = isset($_REQUEST['items'][$id]['Link']) && is_string($_REQUEST['items'][$id]['Link']) ? trim($_REQUEST['items'][$id]['Link']) : $item->Link();
      if ($link === '') $link = null;

      // The MenuKey is only editable for custom (non-built-in) entries, so it
      // is only present in the request for those rows; keep it otherwise.
      $menuKey = isset($_REQUEST['items'][$id]['MenuKey']) && is_string($_REQUEST['items'][$id]['MenuKey']) ? trim($_REQUEST['items'][$id]['MenuKey']) : $item->MenuKey();
      if ($menuKey === '') $menuKey = $item->MenuKey();

      if (!$item->save([
        'MenuKey' => $menuKey,
        'Enabled' => $enabled,
        'Label' => $label,
        'SortOrder' => $sortOrder,
        'Icon' => $icon,
        'IconType' => $iconType,
        'Link' => $link,
      ])) {
        $error_message .= 'Failed to save menu item ' . htmlspecialchars($menuKey) . ': ' . htmlspecialchars($item->get_last_error()) . '<br/>';
      }
    }

    // Insert any new menu entries added via the "Add" button.
    if (isset($_REQUEST['newItems']) && is_array($_REQUEST['newItems'])) {
      foreach ($_REQUEST['newItems'] as $new) {
        $menuKey = isset($new['MenuKey']) ? trim($new['MenuKey']) : '';
        if ($menuKey === '') continue;

        $enabled = isset($new['Enabled']) ? 1 : 0;
        $label = isset($new['Label']) ? trim($new['Label']) : null;
        if ($label === '') $label = null;
        $sortOrder = isset($new['SortOrder']) ? intval($new['SortOrder']) : 0;

        $iconType = isset($new['IconType']) ? $new['IconType'] : 'material';
        if (!in_array($iconType, ['material', 'fontawesome', 'image', 'none'])) $iconType = 'material';
        $icon = isset($new['Icon']) ? trim($new['Icon']) : null;
        if ($icon === '') $icon = null;
        $link = isset($new['Link']) ? trim($new['Link']) : null;
        if ($link === '') $link = null;

        $newItem = new ZM\MenuItem();
        if (!$newItem->save([
          'MenuKey' => $menuKey,
          'Enabled' => $enabled,
          'Label' => $label,
          'SortOrder' => $sortOrder,
          'Icon' => $icon,
          'IconType' => $iconType,
          'Link' => $link,
        ])) {
          $error_message .= 'Failed to add menu item ' . htmlspecialchars($menuKey) . ': ' . htmlspecialchars($newItem->get_last_error()) . '<br/>';
        }
      }
    }
  }
  # Stay on the page when something failed so the error is shown.
  if (!$error_message) {
    $redirect = '?view=options&tab=menu';
  }
} else if ($action == 'deletemenuitems') {
  if (!canEdit('System')) {
    ZM\Warning('Need System permission to delete menu items');
  } else if (!empty($_REQUEST['deleteIds']) && is_array($_REQUEST['deleteIds'])) {
    $ids = array_values(array_filter(array_map('intval', $_REQUEST['deleteIds']), function($v) { return $v > 0; }));
    if (count($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      if (dbQuery('DELETE FROM `Menu_Items` WHERE `Id` IN ('.$placeholders.')', $ids) === null) {
        $error_message .= 'Failed to delete menu entries: ' . htmlspecialchars(dbLastError()) . '<br/>';
      }
    }
  }
  if (!$error_message) {
    $redirect = '?view=options&tab=menu';
  }
} else if ($action == 'resetmenu') {
  if (!canEdit('System')) {
    ZM\Warning('Need System permission to reset menu items');
  } else {
    if ( dbQuery('DELETE FROM Menu_Items') === null ) {
      $error_message .= 'Failed to reset menu entries: '.htmlspecialchars(dbLastError()).'<br/>';
    } else if ( dbQuery("INSERT INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES
      ('Console', 1, 10),
      ('Watch', 1, 15),
      ('Montage', 1, 20),
      ('MontageReview', 1, 30),
      ('Events', 1, 40),
      ('Options', 1, 50),
      ('Log', 1, 60),
      ('Devices', 1, 70),
      ('IntelGpu', 1, 80),
      ('Groups', 1, 90),
      ('Filters', 1, 100),
      ('Snapshots', 1, 110),
      ('Reports', 1, 120),
      ('ReportEventAudit', 1, 130),
      ('Map', 1, 140)") === null ) {
      $error_message .= 'Failed to reset menu entries: '.htmlspecialchars(dbLastError()).'<br/>';
    }
  }
  if (!$error_message) {
    $redirect = '?view=options&tab=menu';
  }
} // end if object vs action
?>
