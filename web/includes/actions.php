<?php
//
// ZoneMinder web action file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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


function getAffectedIds( $name ) {
  $names = $name.'s';
  $ids = array();
	if ( isset($_REQUEST[$names]) ) {
		if ( is_array($_REQUEST[$names]) ) {
			$ids = $_REQUEST[$names];
		} else {
			$ids = array($_REQUEST[$names]);
		}
	} else if ( isset($_REQUEST[$name]) ) {
		if ( is_array($_REQUEST[$name]) ) {
			$ids = $_REQUEST[$name];
		} else {
			$ids = array($_REQUEST[$name]);
		}
	}
	return $ids;
}


if ( empty($action) ) {
  return;
}
if ( $action == 'login' && isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == 'remote' || isset($_REQUEST['password']) ) ) {

  $refreshParent = true;
  // User login is automatically performed in includes/auth.php So we don't need to perform a login here,
  // just handle redirects.  This is the action that comes from the login view, so the logical thing to
  // do on successful auth is redirect to console, otherwise loop back to login.
  if ( !$user ) {
    $view = 'login';
  } else {
    $view = 'console';
    $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=console';
  }
} else if ( $action == 'logout' ) {
  userLogout();
  $refreshParent = true;
  $view = 'none';
} else if ( $action == 'bandwidth' && isset($_REQUEST['newBandwidth']) ) {
  $_COOKIE['zmBandwidth'] = validStr($_REQUEST['newBandwidth']);
  setcookie('zmBandwidth', validStr($_REQUEST['newBandwidth']), time()+3600*24*30*12*10);
  $refreshParent = true;
}

// Event scope actions, view permissions only required
if ( canView('Events') ) {

  if ( isset($_REQUEST['object']) and ( $_REQUEST['object'] == 'filter' ) ) {
    if ( $action == 'addterm' ) {
      $_REQUEST['filter'] = addFilterTerm($_REQUEST['filter'], $_REQUEST['line']);
    } elseif ( $action == 'delterm' ) {
      $_REQUEST['filter'] = delFilterTerm($_REQUEST['filter'], $_REQUEST['line']);
    } else if ( canEdit('Events') ) {
      if ( $action == 'delete' ) {
        if ( ! empty($_REQUEST['Id']) ) {
          dbQuery('DELETE FROM Filters WHERE Id=?', array($_REQUEST['Id']));
        }
      } else if ( ( $action == 'Save' ) or ( $action == 'SaveAs' ) or ( $action == 'execute' ) ) {
       # or ( $action == 'submit' ) ) {

        $sql = '';
        $_REQUEST['filter']['Query']['sort_field'] = validStr($_REQUEST['filter']['Query']['sort_field']);
        $_REQUEST['filter']['Query']['sort_asc'] = validStr($_REQUEST['filter']['Query']['sort_asc']);
        $_REQUEST['filter']['Query']['limit'] = validInt($_REQUEST['filter']['Query']['limit']);
        if ( $action == 'execute' ) {
          $tempFilterName = '_TempFilter'.time();
          $sql .= ' Name = \''.$tempFilterName.'\'';
        } else {
          $sql .= ' Name = '.dbEscape($_REQUEST['filter']['Name']);
        }
        $sql .= ', Query = '.dbEscape(jsonEncode($_REQUEST['filter']['Query']));
        $sql .= ', AutoArchive = '.(!empty($_REQUEST['filter']['AutoArchive']) ? 1 : 0);
        $sql .= ', AutoVideo = '. ( !empty($_REQUEST['filter']['AutoVideo']) ? 1 : 0);
        $sql .= ', AutoUpload = '. ( !empty($_REQUEST['filter']['AutoUpload']) ? 1 : 0);
        $sql .= ', AutoEmail = '. ( !empty($_REQUEST['filter']['AutoEmail']) ? 1 : 0);
        $sql .= ', AutoMessage = '. ( !empty($_REQUEST['filter']['AutoMessage']) ? 1 : 0);
        $sql .= ', AutoExecute = '. ( !empty($_REQUEST['filter']['AutoExecute']) ? 1 : 0);
        $sql .= ', AutoExecuteCmd = '.dbEscape($_REQUEST['filter']['AutoExecuteCmd']);
        $sql .= ', AutoDelete = '. ( !empty($_REQUEST['filter']['AutoDelete']) ? 1 : 0);
        if ( !empty($_REQUEST['filter']['AutoMove']) ? 1 : 0) {
          $sql .= ', AutoMove = 1, AutoMoveTo='. validInt($_REQUEST['filter']['AutoMoveTo']);
        } else {
          $sql .= ', AutoMove = 0'; 
        }
        $sql .= ', UpdateDiskSpace = '. ( !empty($_REQUEST['filter']['UpdateDiskSpace']) ? 1 : 0);
        $sql .= ', Background = '. ( !empty($_REQUEST['filter']['Background']) ? 1 : 0);
        $sql .= ', Concurrent  = '. ( !empty($_REQUEST['filter']['Concurrent']) ? 1 : 0);

        if ( $_REQUEST['Id'] and ( $action == 'Save' ) ) {
          dbQuery('UPDATE Filters SET ' . $sql. ' WHERE Id=?', array($_REQUEST['Id']));
        } else {
          dbQuery('INSERT INTO Filters SET' . $sql);
          $_REQUEST['Id'] = dbInsertId();
        }
        if ( $action == 'execute' ) {
          executeFilter( $tempFilterName );
        }

      } // end if save or execute
    } // end if canEdit(Events)
    return;
  } // end if object == filter
    else {

    // Event scope actions, edit permissions required
    if ( canEdit('Events') ) {
      if ( ($action == 'rename') && isset($_REQUEST['eventName']) && !empty($_REQUEST['eid']) ) {
        dbQuery('UPDATE Events SET Name=? WHERE Id=?', array($_REQUEST['eventName'], $_REQUEST['eid']));
      } else if ( $action == 'eventdetail' ) {
        if ( !empty($_REQUEST['eid']) ) {
          dbQuery('UPDATE Events SET Cause=?, Notes=? WHERE Id=?',
            array($_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $_REQUEST['eid']) );
        } else {
					$dbConn->beginTransaction();
          foreach( getAffectedIds('markEid') as $markEid ) {
            dbQuery('UPDATE Events SET Cause=?, Notes=? WHERE Id=?',
              array($_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $markEid) );
          }
					$dbConn->commit();
        }
        $refreshParent = true;
        $closePopup = true;
      } elseif ( $action == 'archive' || $action == 'unarchive' ) {
        $archiveVal = ($action == 'archive')?1:0;
        if ( !empty($_REQUEST['eid']) ) {
          dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array($archiveVal, $_REQUEST['eid']));
        } else {
					$dbConn->beginTransaction();
          foreach( getAffectedIds('markEid') as $markEid ) {
            dbQuery('UPDATE Events SET Archived=? WHERE Id=?', array($archiveVal, $markEid));
          }
					$dbConn->commit();
          $refreshParent = true;
        }
      } elseif ( $action == 'delete' ) {
				$dbConn->beginTransaction();
        foreach( getAffectedIds('markEid') as $markEid ) {
          deleteEvent($markEid);
        }
				$dbConn->commit();
        $refreshParent = true;
      }
    } // end if canEdit(Events)
  } // end if filter or something else
} // end canView(Events)

// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( !empty($_REQUEST['mid']) && canView('Control', $_REQUEST['mid']) ) {
  require_once('control_functions.php');
  require_once('Monitor.php');
  $mid = validInt($_REQUEST['mid']);
  if ( $action == 'control' ) {
    $monitor = new Monitor($mid);

    $ctrlCommand = buildControlCommand($monitor);
    sendControlCommand($monitor->Id(), $ctrlCommand);
  } else if ( $action == 'settings' ) {
    $args = ' -m ' . escapeshellarg($mid);
    $args .= ' -B' . escapeshellarg($_REQUEST['newBrightness']);
    $args .= ' -C' . escapeshellarg($_REQUEST['newContrast']);
    $args .= ' -H' . escapeshellarg($_REQUEST['newHue']);
    $args .= ' -O' . escapeshellarg($_REQUEST['newColour']);

    $zmuCommand = getZmuCommand($args);

    $zmuOutput = exec($zmuCommand);
    list($brightness, $contrast, $hue, $colour) = explode(' ', $zmuOutput);
    dbQuery(
      'UPDATE Monitors SET Brightness = ?, Contrast = ?, Hue = ?, Colour = ? WHERE Id = ?',
      array($brightness, $contrast, $hue, $colour, $mid));
  }
}

// Control capability actions, require control edit permissions
if ( canEdit('Control') ) {
  if ( $action == 'controlcap' ) {
    require_once('Control.php');
    $Control = new Control( !empty($_REQUEST['cid']) ? $_REQUEST['cid'] : null );

    //$changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );
    $Control->save($_REQUEST['newControl']);
    $refreshParent = true;
    $view = 'none';
  } elseif ( $action == 'delete' ) {
    if ( isset($_REQUEST['markCids']) ) {
      foreach( $_REQUEST['markCids'] as $markCid ) {
        dbQuery('DELETE FROM Controls WHERE Id = ?', array($markCid));
        dbQuery('UPDATE Monitors SET Controllable = 0, ControlId = 0 WHERE ControlId = ?', array($markCid));
        $refreshParent = true;
      }
    }
  } // end if action
} // end if canEdit Controls

if ( isset($_REQUEST['object']) and $_REQUEST['object'] == 'Monitor' ) {
  if ( $action == 'save' ) {
    foreach ( $_REQUEST['mids'] as $mid ) {
      $mid = ValidInt($mid);
      if ( ! canEdit('Monitors', $mid) ) {
        Warning("Cannot edit monitor $mid");
        continue;
      }
      $Monitor = new Monitor($mid);
      if ( $Monitor->Type() != 'WebSite' ) {
        $Monitor->zmaControl('stop');
        $Monitor->zmcControl('stop');
      }
      $Monitor->save($_REQUEST['newMonitor']);
      if ( $Monitor->Function() != 'None' && $Monitor->Type() != 'WebSite' ) {
        $Monitor->zmcControl('start');
        if ( $Monitor->Enabled() ) {
          $Monitor->zmaControl('start');
        }
      }
    } // end foreach mid
    $refreshParent = true;
  } // end if action == save
} // end if object is Monitor

// Monitor edit actions, require a monitor id and edit permissions for that monitor
if ( !empty($_REQUEST['mid']) && canEdit('Monitors', $_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  if ( $action == 'function' ) {
    $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($mid));

    $newFunction = validStr($_REQUEST['newFunction']);
    # Because we use a checkbox, it won't get passed in the request. So not being in _REQUEST means 0
    $newEnabled = ( !isset($_REQUEST['newEnabled']) or $_REQUEST['newEnabled'] != '1' ) ? '0' : '1';
    $oldFunction = $monitor['Function'];
    $oldEnabled = $monitor['Enabled'];
    if ( $newFunction != $oldFunction || $newEnabled != $oldEnabled ) {
      dbQuery('UPDATE Monitors SET Function=?, Enabled=? WHERE Id=?',
        array($newFunction, $newEnabled, $mid));

      $monitor['Function'] = $newFunction;
      $monitor['Enabled'] = $newEnabled;
      if ( daemonCheck() && ($monitor['Type'] != 'WebSite') ) {
        $restart = ($oldFunction == 'None') || ($newFunction == 'None') || ($newEnabled != $oldEnabled);
        zmaControl($monitor, 'stop');
        zmcControl($monitor, $restart?'restart':'');
        zmaControl($monitor, 'start');
      }
      $refreshParent = true;
    }
  } else if ( $action == 'zone' && isset($_REQUEST['zid']) ) {
    $zid = validInt($_REQUEST['zid']);
    $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($mid));

    if ( !empty($zid) ) {
      $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($mid, $zid));
    } else {
      $zone = array();
    }

    if ( $_REQUEST['newZone']['Units'] == 'Percent' ) {
      $_REQUEST['newZone']['MinAlarmPixels'] = intval(($_REQUEST['newZone']['MinAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
      $_REQUEST['newZone']['MaxAlarmPixels'] = intval(($_REQUEST['newZone']['MaxAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MinFilterPixels']) )
        $_REQUEST['newZone']['MinFilterPixels'] = intval(($_REQUEST['newZone']['MinFilterPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MaxFilterPixels']) )
        $_REQUEST['newZone']['MaxFilterPixels'] = intval(($_REQUEST['newZone']['MaxFilterPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MinBlobPixels']) )
        $_REQUEST['newZone']['MinBlobPixels'] = intval(($_REQUEST['newZone']['MinBlobPixels']*$_REQUEST['newZone']['Area'])/100);
      if ( isset($_REQUEST['newZone']['MaxBlobPixels']) )
        $_REQUEST['newZone']['MaxBlobPixels'] = intval(($_REQUEST['newZone']['MaxBlobPixels']*$_REQUEST['newZone']['Area'])/100);
    }

    unset( $_REQUEST['newZone']['Points'] );
    $types = array();
    $changes = getFormChanges($zone, $_REQUEST['newZone'], $types);

    if ( count($changes) ) {
      if ( $zid > 0 ) {
        dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?', array($mid, $zid));
      } else {
        dbQuery('INSERT INTO Zones SET MonitorId=?, '.implode(', ', $changes), array($mid));
      }
      if ( daemonCheck() && ($monitor['Type'] != 'WebSite') ) {
        if ( $_REQUEST['newZone']['Type'] == 'Privacy' ) {
          zmaControl($monitor, 'stop');
          zmcControl($monitor, 'restart');
          zmaControl($monitor, 'start');
        } else {
          zmaControl($monitor, 'restart');
        }
      }
      if ( ($_REQUEST['newZone']['Type'] == 'Privacy') && $monitor['Controllable'] ) {
        require_once('control_functions.php');
        sendControlCommand($mid, 'quit');
      }
      $refreshParent = true;
    }
    $view = 'none';
  } elseif ( $action == 'plugin' && isset($_REQUEST['pl']) ) {
    $sql = 'SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?';
    $pconfs=dbFetchAll($sql, NULL, array($mid, $_REQUEST['zid'], $_REQUEST['pl']));
    $changes = 0;
    foreach ( $pconfs as $pconf ) {
      $value = $_REQUEST['pluginOpt'][$pconf['Name']];
      if ( array_key_exists($pconf['Name'], $_REQUEST['pluginOpt']) && ($pconf['Value'] != $value) ) {
        dbQuery('UPDATE PluginsConfig SET Value=? WHERE id=?', array($value, $pconf['Id']));
        $changes++;
      }
    }
    if ( $changes > 0 ) {
      if ( daemonCheck() && ($monitor['Type'] != 'WebSite') ) {
        zmaControl($mid, 'restart');
      }
      $refreshParent = true;
    }
    $view = 'none';
  } elseif ( ($action == 'sequence') && isset($_REQUEST['smid']) ) {
    $smid = validInt($_REQUEST['smid']);
    $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id = ?', NULL, array($mid));
    $smonitor = dbFetchOne('SELECT * FROM Monitors WHERE Id = ?', NULL, array($smid));

    dbQuery('UPDATE Monitors SET Sequence=? WHERE Id=?', array($smonitor['Sequence'], $monitor['Id']));
    dbQuery('UPDATE Monitors SET Sequence=? WHERE Id=?', array($monitor['Sequence'], $smonitor['Id']));

    $refreshParent = true;
    fixSequences();
  } elseif ( $action == 'delete' ) {
    if ( isset($_REQUEST['markZids']) ) {
      $deletedZid = 0;
      foreach ( $_REQUEST['markZids'] as $markZid ) {
        $zone = dbFetchOne('SELECT * FROM Zones WHERE Id=?', NULL, array($markZid));
        dbQuery('DELETE FROM Zones WHERE MonitorId=? AND Id=?', array($mid, $markZid));
        $deletedZid = 1;
      }
      if ( $deletedZid ) {
        if ( daemonCheck() && $monitor['Type'] != 'WebSite' ) {
          if ( $zone['Type'] == 'Privacy' ) {
            zmaControl($mid, 'stop');
            zmcControl($mid, 'restart');
            zmaControl($mid, 'start');
          } else {
            zmaControl($mid, 'restart');
          }
        } // end if daemonCheck()
        $refreshParent = true;
      } // end if deletedzid
    } // end if isset($_REQUEST['markZids'])
  } // end if action 
} // end if $mid and canEdit($mid)

// Monitor edit actions, monitor id derived, require edit permissions for that monitor
if ( canEdit('Monitors') ) {
  if ( $action == 'monitor' ) {
    $mid = 0;
    if ( !empty($_REQUEST['mid']) ) {
      $mid = validInt($_REQUEST['mid']);
      $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($mid));

      if ( ZM_OPT_X10 ) {
        $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId=?', NULL, array($mid));
        if ( !$x10Monitor )
          $x10Monitor = array();
      }
    } else {
      $monitor = array();
      if ( ZM_OPT_X10 ) {
        $x10Monitor = array();
      }
    }
    $Monitor = new Monitor($monitor);

    // Define a field type for anything that's not simple text equivalent
    $types = array(
        'Triggers' => 'set',
        'Controllable' => 'toggle',
        'TrackMotion' => 'toggle',
        'Enabled' => 'toggle',
        'DoNativeMotDet' => 'toggle',
        'Exif' => 'toggle',
        'RTSPDescribe' => 'toggle',
        'RecordAudio' => 'toggle',
        'Method' => 'raw',
        );

    if ( $_REQUEST['newMonitor']['ServerId'] == 'auto' ) {
      $_REQUEST['newMonitor']['ServerId'] = dbFetchOne(
        'SELECT Id FROM Servers WHERE Status=\'Running\' ORDER BY FreeMem DESC, CpuLoad ASC LIMIT 1', 'Id');
      Logger::Debug('Auto selecting server: Got ' . $_REQUEST['newMonitor']['ServerId'] );
      if ( ( ! $_REQUEST['newMonitor'] ) and defined('ZM_SERVER_ID') ) {
        $_REQUEST['newMonitor']['ServerId'] = ZM_SERVER_ID;
        Logger::Debug('Auto selecting server to ' . ZM_SERVER_ID);
      }
    }

    $columns = getTableColumns('Monitors');
    $changes = getFormChanges($monitor, $_REQUEST['newMonitor'], $types, $columns);

    if ( count($changes) ) {
      if ( $mid ) {

        # If we change anything that changes the shared mem size, zma can complain.  So let's stop first.
        if ( $monitor['Type'] != 'WebSite' ) {
          zmaControl($monitor, 'stop');
          zmcControl($monitor, 'stop');
        }
        dbQuery('UPDATE Monitors SET '.implode(', ', $changes).' WHERE Id=?', array($mid));
        // Groups will be added below
        if ( isset($changes['Name']) or isset($changes['StorageId']) ) {
          $OldStorage = new Storage($monitor['StorageId']);
          $saferOldName = basename($monitor['Name']);
          if ( file_exists($OldStorage->Path().'/'.$saferOldName) )
            unlink($OldStorage->Path().'/'.$saferOldName);

          $NewStorage = new Storage($_REQUEST['newMonitor']['StorageId']);
          if ( ! file_exists($NewStorage->Path().'/'.$mid) )
            mkdir($NewStorage->Path().'/'.$mid, 0755);
          $saferNewName = basename($_REQUEST['newMonitor']['Name']);
          symlink($mid, $NewStorage->Path().'/'.$saferNewName);
        }
        if ( isset($changes['Width']) || isset($changes['Height']) ) {
          $newW = $_REQUEST['newMonitor']['Width'];
          $newH = $_REQUEST['newMonitor']['Height'];
          $newA = $newW * $newH;
          $oldW = $monitor['Width'];
          $oldH = $monitor['Height'];
          $oldA = $oldW * $oldH;

          $zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId=?', NULL, array($mid));
          foreach ( $zones as $zone ) {
            $newZone = $zone;
            $points = coordsToPoints($zone['Coords']);
            for ( $i = 0; $i < count($points); $i++ ) {
              $points[$i]['x'] = intval(($points[$i]['x']*($newW-1))/($oldW-1));
              $points[$i]['y'] = intval(($points[$i]['y']*($newH-1))/($oldH-1));
            }
            $newZone['Coords'] = pointsToCoords($points);
            $newZone['Area'] = intval(round(($zone['Area']*$newA)/$oldA));
            $newZone['MinAlarmPixels'] = intval(round(($newZone['MinAlarmPixels']*$newA)/$oldA));
            $newZone['MaxAlarmPixels'] = intval(round(($newZone['MaxAlarmPixels']*$newA)/$oldA));
            $newZone['MinFilterPixels'] = intval(round(($newZone['MinFilterPixels']*$newA)/$oldA));
            $newZone['MaxFilterPixels'] = intval(round(($newZone['MaxFilterPixels']*$newA)/$oldA));
            $newZone['MinBlobPixels'] = intval(round(($newZone['MinBlobPixels']*$newA)/$oldA));
            $newZone['MaxBlobPixels'] = intval(round(($newZone['MaxBlobPixels']*$newA)/$oldA));

            $changes = getFormChanges($zone, $newZone, $types);

            if ( count($changes) ) {
              dbQuery('UPDATE Zones SET '.implode(', ', $changes).' WHERE MonitorId=? AND Id=?',
                array($mid, $zone['Id']));
            }
          } // end foreach zone
        } // end if width and height
        $restart = true;
      } else if ( ! $user['MonitorIds'] ) {
        // Can only create new monitors if we are not restricted to specific monitors
# FIXME This is actually a race condition. Should lock the table.
        $maxSeq = dbFetchOne('SELECT MAX(Sequence) AS MaxSequence FROM Monitors', 'MaxSequence');
        $changes[] = 'Sequence = '.($maxSeq+1);

        $sql = 'INSERT INTO Monitors SET '.implode(', ', $changes);
        if ( dbQuery($sql) ) {
          $mid = dbInsertId();
          $zoneArea = $_REQUEST['newMonitor']['Width'] * $_REQUEST['newMonitor']['Height'];
          dbQuery("INSERT INTO Zones SET MonitorId = ?, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = ?, Area=?, AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels=?, MaxAlarmPixels=?, FilterX = 3, FilterY = 3, MinFilterPixels=?, MaxFilterPixels=?, MinBlobPixels=?, MinBlobs = 1", array( $mid, sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $_REQUEST['newMonitor']['Width']-1, 0, $_REQUEST['newMonitor']['Width']-1, $_REQUEST['newMonitor']['Height']-1, 0, $_REQUEST['newMonitor']['Height']-1 ), $zoneArea, intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*2)/100)  ) );
          //$view = 'none';
          $Storage = new Storage($_REQUEST['newMonitor']['StorageId']);
          mkdir($Storage->Path().'/'.$mid, 0755);
          $saferName = basename($_REQUEST['newMonitor']['Name']);
          symlink($mid, $Storage->Path().'/'.$saferName);
  
        } else {
          Error('Error saving new Monitor.');
          $error_message = dbError($sql);
          return;
        }
      } else {
        Error('Users with Monitors restrictions cannot create new monitors.');
        return;
      }

      $restart = true;
    } else {
      Logger::Debug('No action due to no changes to Monitor');
    } # end if count(changes)

    if (
      ( !isset($_POST['newMonitor']['GroupIds']) )
      or
      ( count($_POST['newMonitor']['GroupIds']) != count($Monitor->GroupIds()) )
      or 
      array_diff($_POST['newMonitor']['GroupIds'], $Monitor->GroupIds())
    ) {
      if ( $Monitor->Id() )
        dbQuery('DELETE FROM Groups_Monitors WHERE MonitorId=?', array($mid));

      if ( isset($_POST['newMonitor']['GroupIds']) ) {
        foreach ( $_POST['newMonitor']['GroupIds'] as $group_id ) {
          dbQuery('INSERT INTO Groups_Monitors (GroupId,MonitorId) VALUES (?,?)', array($group_id, $mid));
        }
      }
    } // end if there has been a change of groups

    if ( ZM_OPT_X10 ) {
      $x10Changes = getFormChanges($x10Monitor, $_REQUEST['newX10Monitor']);

      if ( count($x10Changes) ) {
        if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) ) {
          dbQuery('UPDATE TriggersX10 SET '.implode(', ', $x10Changes).' WHERE MonitorId=?', array($mid));
        } elseif ( !$user['MonitorIds'] ) {
          if ( !$x10Monitor ) {
            dbQuery('INSERT INTO TriggersX10 SET MonitorId = ?, '.implode(', ', $x10Changes), array($mid));
          } else {
            dbQuery('DELETE FROM TriggersX10 WHERE MonitorId = ?', array($mid));
          }
        }
        $restart = true;
      } # end if has x10Changes
    } # end if ZM_OPT_X10

    if ( $restart ) {
      
      $new_monitor = new Monitor($mid);
      //fixDevices();

      if ( $new_monitor->Type() != 'WebSite' ) {
        $new_monitor->zmcControl('start');
        $new_monitor->zmaControl('start');
      }

      if ( $new_monitor->Controllable() ) {
        require_once('control_functions.php');
        sendControlCommand($mid, 'quit');
      } 
      // really should thump zmwatch and maybe zmtrigger too.
      //daemonControl( 'restart', 'zmwatch.pl' );
      $refreshParent = true;
    } // end if restart
    $view = 'none';
  } elseif ( $action == 'delete' ) {
    if ( isset($_REQUEST['markMids']) && !$user['MonitorIds'] ) {
      require_once('Monitor.php');
      foreach ( $_REQUEST['markMids'] as $markMid ) {
        if ( canEdit('Monitors', $markMid) ) {
          // This could be faster as a select all
          if ( $monitor = dbFetchOne('SELECT * FROM Monitors WHERE Id = ?', NULL, array($markMid)) ) {
            $Monitor = new Monitor($monitor);
            $Monitor->delete();
          } // end if monitor found in db
        } // end if canedit this monitor
      } // end foreach monitor in MarkMid
    } // markMids is set and we aren't limited to specific monitors
  } // end if action == Delete
}

// Device view actions
if ( canEdit('Devices') ) {
  if ( $action == 'device' ) {
    if ( !empty($_REQUEST['command']) ) {
      setDeviceStatusX10($_REQUEST['key'], $_REQUEST['command']);
    } else if ( isset($_REQUEST['newDevice']) ) {
      if ( isset($_REQUEST['did']) ) {
        dbQuery('UPDATE Devices SET Name=?, KeyString=? WHERE Id=?',
          array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'], $_REQUEST['did']) );
      } else {
        dbQuery('INSERT INTO Devices SET Name=?, KeyString=?',
          array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString']) );
      }
      $refreshParent = true;
      $view = 'none';
    }
  } elseif ( $action == 'delete' ) {
    if ( isset($_REQUEST['markDids']) ) {
      foreach( $_REQUEST['markDids'] as $markDid ) {
        dbQuery('DELETE FROM Devices WHERE Id=?', array($markDid));
        $refreshParent = true;
      }
    }
  } // end if action
} // end if canedit devices

// Group view actions
if ( canView('Groups') && ($action == 'setgroup') ) {
  if ( !empty($_REQUEST['gid']) ) {
    setcookie('zmGroup', validInt($_REQUEST['gid']), time()+3600*24*30*12*10);
  } else {
    setcookie('zmGroup', '', time()-3600*24*2);
  }
  $refreshParent = true;
}

// Group edit actions
# Should probably verify that each monitor id is a valid monitor, that we have access to.
# However at the moment, you have to have System permissions to do this
if ( canEdit('Groups') ) {
  if ( $action == 'group' ) {
    $monitors = empty($_POST['newGroup']['MonitorIds']) ? '' : implode(',', $_POST['newGroup']['MonitorIds']);
    $group_id = null;
    if ( !empty($_POST['gid']) ) {
      $group_id = $_POST['gid'];
      dbQuery(
        'UPDATE Groups SET Name=?, ParentId=? WHERE Id=?',
        array(
          $_POST['newGroup']['Name'],
          ( $_POST['newGroup']['ParentId'] == '' ? null : $_POST['newGroup']['ParentId'] ),
          $group_id,
        )
      );
      dbQuery('DELETE FROM Groups_Monitors WHERE GroupId=?', array($group_id));
    } else {
      dbQuery(
        'INSERT INTO Groups (Name,ParentId) VALUES (?,?)',
        array(
          $_POST['newGroup']['Name'],
          ( $_POST['newGroup']['ParentId'] == '' ? null : $_POST['newGroup']['ParentId'] ),
        )
      );
      $group_id = dbInsertId();
    }
    if ( $group_id ) {
      foreach ( $_POST['newGroup']['MonitorIds'] as $mid ) {
        dbQuery('INSERT INTO Groups_Monitors (GroupId,MonitorId) VALUES (?,?)', array($group_id, $mid));
      }
    }
    $view = 'none';
    $refreshParent = true;
  } else if ( $action == 'delete' ) {
    if ( !empty($_REQUEST['gid']) ) {
      if ( is_array($_REQUEST['gid']) ) {
        foreach ( $_REQUEST['gid'] as $gid ) {
          $Group = new Group($gid);
          $Group->delete();
        }
      } else {
        $Group = new Group($_REQUEST['gid'] );
        $Group->delete();
      }
    }
    $refreshParent = true;
  } # end if action
} // end if can edit groups

// System edit actions
if ( canEdit('System') ) {
  if ( isset($_REQUEST['object']) ) {
    if ( $_REQUEST['object'] == 'MontageLayout' ) {
      require_once('MontageLayout.php');
      if ( $action == 'Save' ) {
        $Layout = null;
        if ( $_REQUEST['Name'] != '' ) {
          $Layout = new MontageLayout();
          $Layout->Name($_REQUEST['Name']);
        } else {
          $Layout = new MontageLayout($_REQUEST['zmMontageLayout']);
        }
        $Layout->Positions($_REQUEST['Positions']);
        $Layout->save();
        session_start();
        $_SESSION['zmMontageLayout'] = $Layout->Id();
        setcookie('zmMontageLayout', $Layout->Id(), 1);
        session_write_close();
        $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=montagereview';
      } // end if save

    } else if ( $_REQUEST['object'] == 'server' ) {

      if ( $action == 'Save' ) {
        if ( !empty($_REQUEST['id']) ) {
          $dbServer = dbFetchOne(
            'SELECT * FROM Servers WHERE Id=?',
            NULL,
            array($_REQUEST['id']) );
        } else {
          $dbServer = array();
        }

        $types = array();
        $changes = getFormChanges($dbServer, $_REQUEST['newServer'], $types);

        if ( count($changes) ) {
          if ( !empty($_REQUEST['id']) ) {
            dbQuery('UPDATE Servers SET '.implode(', ', $changes).' WHERE Id = ?',
              array($_REQUEST['id']) );
          } else {
            dbQuery('INSERT INTO Servers SET '.implode(', ', $changes));
          }
          $refreshParent = true;
        }
        $view = 'none';
      } else if ( $action == 'delete' ) {
        if ( !empty($_REQUEST['markIds']) ) {
          foreach( $_REQUEST['markIds'] as $Id )
            dbQuery('DELETE FROM Servers WHERE Id=?', array($Id));
        }
        $refreshParent = true;
      } else {
        Error("Unknown action $action in saving Server");
      }
    } else if ( $_REQUEST['object'] == 'storage' ) {
      if ( $action == 'Save' ) {
        if ( !empty($_REQUEST['id']) )
          $dbStorage = dbFetchOne('SELECT * FROM Storage WHERE Id=?', NULL, array($_REQUEST['id']));
        else
          $dbStorage = array();

        $types = array();
        $changes = getFormChanges($dbStorage, $_REQUEST['newStorage'], $types);

        if ( count($changes) ) {
          if ( !empty($_REQUEST['id']) ) {
            dbQuery('UPDATE Storage SET '.implode(', ', $changes).' WHERE Id = ?', array($_REQUEST['id']));
          } else {
            dbQuery('INSERT INTO Storage set '.implode(', ', $changes));
          }
          $refreshParent = true;
        }
        $view = 'none';
      } else if ( $action == 'delete' ) {
        if ( !empty($_REQUEST['markIds']) ) {
          foreach( $_REQUEST['markIds'] as $Id )
            dbQuery('DELETE FROM Storage WHERE Id=?', array($Id));
        }
        $refreshParent = true;
      } else {
        Error("Unknown action $action in saving Storage");
      }
    } # end if isset($_REQUEST['object'] )

  } else if ( $action == 'version' && isset($_REQUEST['option']) ) {
    $option = $_REQUEST['option'];
    switch( $option ) {
      case 'go' :
        {
          // Ignore this, the caller will open the page itself
          break;
        }
      case 'ignore' :
        {
          dbQuery("UPDATE Config SET Value = '".ZM_DYN_LAST_VERSION."' WHERE Name = 'ZM_DYN_CURR_VERSION'");
          break;
        }
      case 'hour' :
      case 'day' :
      case 'week' :
        {
          $nextReminder = time();
          if ( $option == 'hour' ) {
            $nextReminder += 60*60;
          } elseif ( $option == 'day' ) {
            $nextReminder += 24*60*60;
          } elseif ( $option == 'week' ) {
            $nextReminder += 7*24*60*60;
          }
          dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_NEXT_REMINDER'");
          break;
        }
      case 'never' :
        {
          dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_CHECK_FOR_UPDATES'");
          break;
        }
    }
  }
  if ( $action == 'donate' && isset($_REQUEST['option']) ) {
    $option = $_REQUEST['option'];
    switch( $option ) {
      case 'go' :
        {
          // Ignore this, the caller will open the page itself
          break;
        }
      case 'hour' :
      case 'day' :
      case 'week' :
      case 'month' :
        {
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
        }
      case 'never' :
      case 'already' :
        {
          dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_DYN_SHOW_DONATE_REMINDER'");
          break;
        }
    } // end switch option
  }
  if ( ($action == 'privacy') && isset($_REQUEST['option']) ) {
    switch( $_REQUEST['option'] ) {
      case 'decline' :
        {
          dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_SHOW_PRIVACY'");
          dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_TELEMETRY_DATA'");
          $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=console';
          break;
        }
      case 'accept' :
        {
          dbQuery("UPDATE Config SET Value = '0' WHERE Name = 'ZM_SHOW_PRIVACY'");
          dbQuery("UPDATE Config SET Value = '1' WHERE Name = 'ZM_TELEMETRY_DATA'");
          $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=console';
          break;
        }
      default: # Enable the privacy statement if we somehow submit something other than accept or decline
          dbQuery("UPDATE Config SET Value = '1' WHERE Name = 'ZM_SHOW_PRIVACY'");
    } // end switch option
    return;
  }
  if ( $action == 'options' && isset($_REQUEST['tab']) ) {
    $configCat = $configCats[$_REQUEST['tab']];
    $changed = false;
    foreach ( $configCat as $name=>$value ) {
      unset($newValue);
      if ( $value['Type'] == 'boolean' && empty($_REQUEST['newConfig'][$name]) ) {
        $newValue = 0;
      } else if ( isset($_REQUEST['newConfig'][$name]) ) {
        $newValue = preg_replace("/\r\n/", "\n", stripslashes($_REQUEST['newConfig'][$name]));
      }

      if ( isset($newValue) && ($newValue != $value['Value']) ) {
        dbQuery('UPDATE Config SET Value=? WHERE Name=?', array($newValue, $name));
        $changed = true;
      }
    }
    if ( $changed ) {
      switch( $_REQUEST['tab'] ) {
        case 'system' :
        case 'config' :
          $restartWarning = true;
          break;
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
      $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=options&tab='.$_REQUEST['tab'];
    }
    loadConfig(false);
    return;
  } elseif ( $action == 'user' ) {
    if ( !empty($_REQUEST['uid']) )
      $dbUser = dbFetchOne('SELECT * FROM Users WHERE Id=?', NULL, array($_REQUEST['uid']));
    else
      $dbUser = array();

    $types = array();
    $changes = getFormChanges($dbUser, $_REQUEST['newUser'], $types);

    if ( $_REQUEST['newUser']['Password'] )
      $changes['Password'] = 'Password = password('.dbEscape($_REQUEST['newUser']['Password']).')';
    else
      unset($changes['Password']);

    if ( count($changes) ) {
      if ( !empty($_REQUEST['uid']) ) {
        dbQuery('UPDATE Users SET '.implode(', ', $changes).' WHERE Id = ?', array($_REQUEST['uid']));
        # If we are updating the logged in user, then update our session user data.
        if ( $user and ( $dbUser['Username'] == $user['Username'] ) )
          userLogin($dbUser['Username'], $dbUser['Password']);
      } else {
        dbQuery('INSERT INTO Users SET '.implode(', ', $changes));
      }
      $refreshParent = true;
    }
    $view = 'none';
  } elseif ( $action == 'state' ) {
    if ( !empty($_REQUEST['runState']) ) {
      //if ( $cookies ) session_write_close();
      packageControl($_REQUEST['runState']);
      $refreshParent = true;
    }
  } elseif ( $action == 'save' ) {
    if ( !empty($_REQUEST['runState']) || !empty($_REQUEST['newState']) ) {
      $sql = 'SELECT Id,Function,Enabled FROM Monitors ORDER BY Id';
      $definitions = array();
      foreach( dbFetchAll($sql) as $monitor ) {
        $definitions[] = $monitor['Id'].':'.$monitor['Function'].':'.$monitor['Enabled'];
      }
      $definition = join(',', $definitions);
      if ( $_REQUEST['newState'] )
        $_REQUEST['runState'] = $_REQUEST['newState'];
      dbQuery('REPLACE INTO States SET Name=?, Definition=?', array($_REQUEST['runState'],$definition));
    }
  } elseif ( $action == 'delete' ) {
    if ( isset($_REQUEST['runState']) )
      dbQuery('DELETE FROM States WHERE Name=?', array($_REQUEST['runState']));

    if ( isset($_REQUEST['markUids']) ) {
      foreach( $_REQUEST['markUids'] as $markUid )
        dbQuery('DELETE FROM Users WHERE Id = ?', array($markUid));
      if ( $markUid == $user['Id'] )
        userLogout();
    }
  }
} else {
  if ( ZM_USER_SELF_EDIT && $action == 'user' ) {
    $uid = $user['Id'];

    $dbUser = dbFetchOne('SELECT Id, Password, Language FROM Users WHERE Id = ?', NULL, array($uid));

    $types = array();
    $changes = getFormChanges($dbUser, $_REQUEST['newUser'], $types);

    if ( !empty($_REQUEST['newUser']['Password']) )
      $changes['Password'] = 'Password = password('.dbEscape($_REQUEST['newUser']['Password']).')';
    else
      unset($changes['Password']);
    if ( count($changes) ) {
      dbQuery('UPDATE Users SET '.implode(', ', $changes).' WHERE Id=?', array($uid));
      $refreshParent = true;
    }
    $view = 'none';
  }
}

if ( $action == 'reset' ) {
  session_start();
  $_SESSION['zmEventResetTime'] = strftime(STRF_FMT_DATETIME_DB);
  setcookie('zmEventResetTime', $_SESSION['zmEventResetTime'], time()+3600*24*30*12*10);
  session_write_close();
}

?>
