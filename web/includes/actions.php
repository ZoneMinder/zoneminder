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


// PP - POST request handler for PHP which does not need extensions
// credit: http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl/


function do_post_request($url, $data, $optional_headers = null) {
  $params = array('http' => array(
        'method' => 'POST',
        'content' => $data
        ));
  if ($optional_headers !== null) {
    $params['http']['header'] = $optional_headers;
  }
  $ctx = stream_context_create($params);
  $fp = @fopen($url, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  $response = @stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return $response;
}

function getAffectedIds( $name ) {
  $names = $name.'s';
  $ids = array();
  if ( isset($_REQUEST[$names]) || isset($_REQUEST[$name]) ) {
    if ( isset($_REQUEST[$names]) )
      $ids = validInt($_REQUEST[$names]);
    else if ( isset($_REQUEST[$name]) )
      $ids[] = validInt($_REQUEST[$name]);
  }
  return( $ids );
}


if ( !empty($action) ) {
  if ( $action == 'login' && isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == 'remote' || isset($_REQUEST['password']) ) ) {
    // if true, a popup will display after login
    // PP - lets validate reCaptcha if it exists
    if ( defined('ZM_OPT_USE_GOOG_RECAPTCHA') 
        && defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY') 
        && defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY')
        && ZM_OPT_USE_GOOG_RECAPTCHA && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY 
        && ZM_OPT_GOOG_RECAPTCHA_SITEKEY)
    {
      $url = 'https://www.google.com/recaptcha/api/siteverify';
      $fields = array (
          'secret'=> ZM_OPT_GOOG_RECAPTCHA_SECRETKEY,
          'response' => $_REQUEST['g-recaptcha-response'],
          'remoteip'=> $_SERVER['REMOTE_ADDR']
          );
      $res= do_post_request($url, http_build_query($fields));
      $responseData = json_decode($res,true);
      // PP - credit: https://github.com/google/recaptcha/blob/master/src/ReCaptcha/Response.php
      // if recaptcha resulted in error, we might have to deny login
      if (isset($responseData['success']) && $responseData['success'] == false) {
        // PP - before we deny auth, let's make sure the error was not 'invalid secret'
        // because that means the user did not configure the secret key correctly
        // in this case, we prefer to let him login in and display a message to correct
        // the key. Unfortunately, there is no way to check for invalid site key in code
        // as it produces the same error as when you don't answer a recaptcha
        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
          if (!in_array('invalid-input-secret',$responseData['error-codes'])) {	
            Error ('reCaptcha authentication failed');
            userLogout();
            $view='login';
            $refreshParent = true;
          } else {
            //Let them login but show an error
            echo '<script type="text/javascript">alert("'.translate('RecaptchaWarning').'"); </script>';
            Error ("Invalid recaptcha secret detected");
          }
        }
      } // end if success==false

    } // end if using reCaptcha

    $username = validStr( $_REQUEST['username'] );
    $password = isset($_REQUEST['password'])?validStr($_REQUEST['password']):'';
    userLogin( $username, $password );
    $refreshParent = true;
    $view = 'console';
    $redirect = true;
  } else if ( $action == 'logout' ) {
    userLogout();
    $refreshParent = true;
    $view = 'none';
  } else if ( $action == 'bandwidth' && isset($_REQUEST['newBandwidth']) ) {
    $_COOKIE['zmBandwidth'] = validStr($_REQUEST['newBandwidth']);
    setcookie( 'zmBandwidth', validStr($_REQUEST['newBandwidth']), time()+3600*24*30*12*10 );
    $refreshParent = true;
  }

  // Event scope actions, view permissions only required
  if ( canView( 'Events' ) ) {

    if ( $action == 'filter' ) {
      if ( !empty($_REQUEST['subaction']) ) {
        if ( $_REQUEST['subaction'] == 'addterm' )
          $_REQUEST['filter'] = addFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
        elseif ( $_REQUEST['subaction'] == 'delterm' )
          $_REQUEST['filter'] = delFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
      } elseif ( canEdit( 'Events' ) ) {
        $sql = '';
        $endSql = '';
        $filterName = '';
        if ( !empty($_REQUEST['execute']) ) {
          // TempFilterName is used in event listing later on
          $tempFilterName = $filterName = '_TempFilter'.time();
        } elseif ( !empty($_REQUEST['newFilterName']) ) {
          $filterName = $_REQUEST['newFilterName'];
        }
        if ( $filterName ) {
          # Replace will teplace any filter with the same Id 
          # Since we aren't specifying the Id , this is effectively an insert
          $sql = 'REPLACE INTO Filters SET Name = '.dbEscape($filterName).',';
        } else {
          $sql = 'UPDATE Filters SET';
          $endSql = 'WHERE Id = '.$_REQUEST['Id'];
        }

        # endSql is only set if ! filterName... so... woulnd't this always be true
        if ( !empty($filterName) || $endSql ) {
          $_REQUEST['filter']['sort_field'] = validStr($_REQUEST['sort_field']);
          $_REQUEST['filter']['sort_asc'] = validStr($_REQUEST['sort_asc']);
          $_REQUEST['filter']['limit'] = validInt($_REQUEST['limit']);
          $sql .= ' Query = '.dbEscape(jsonEncode($_REQUEST['filter']));
          if ( !empty($_REQUEST['AutoArchive']) )
            $sql .= ', AutoArchive = '.dbEscape($_REQUEST['AutoArchive']);
          if ( !empty($_REQUEST['AutoVideo']) )
            $sql .= ', AutoVideo = '.dbEscape($_REQUEST['AutoVideo']);
          if ( !empty($_REQUEST['AutoUpload']) )
            $sql .= ', AutoUpload = '.dbEscape($_REQUEST['AutoUpload']);
          if ( !empty($_REQUEST['AutoEmail']) )
            $sql .= ', AutoEmail = '.dbEscape($_REQUEST['AutoEmail']);
          if ( !empty($_REQUEST['AutoMessage']) )
            $sql .= ', AutoMessage = '.dbEscape($_REQUEST['AutoMessage']);
          if ( !empty($_REQUEST['AutoExecute']) && !empty($_REQUEST['AutoExecuteCmd']) )
            $sql .= ', AutoExecute = '.dbEscape($_REQUEST['AutoExecute']).", AutoExecuteCmd = ".dbEscape($_REQUEST['AutoExecuteCmd']);
          if ( !empty($_REQUEST['AutoDelete']) )
            $sql .= ', AutoDelete = '.dbEscape($_REQUEST['AutoDelete']);
          if ( !empty($_REQUEST['background']) )
            $sql .= ', Background = '.dbEscape($_REQUEST['background']);
          if ( !empty($_REQUEST['concurrent']) )
            $sql .= ', Concurrent = '.dbEscape($_REQUEST['concurrent']);
          $sql .= $endSql;
          dbQuery( $sql );
          if ( $filterName ) {
            $filter = dbFetchOne( 'SELECT * FROM Filters WHERE Name=?', NULL, array($filterName) );
            if ( $filter ) {
              # This won't work yet because refreshparent refreshes the old filter.  Need to do a redirect instead of a refresh.
              $_REQUEST['Id'] = $filter['Id'];
            } else {
              Error("No new Id despite new name");
            }
            $refreshParent = '/index.php?view=filter&Id='.$_REQUEST['Id'];
          }
        }
      } // end if canedit events
    } // end if action == filter
  } // end if canview events

  // Event scope actions, edit permissions required
  if ( canEdit( 'Events' ) ) {
    if ( $action == 'rename' && isset($_REQUEST['eventName']) && !empty($_REQUEST['eid']) ) {
      dbQuery( 'UPDATE Events SET Name=? WHERE Id=?', array( $_REQUEST['eventName'], $_REQUEST['eid'] ) );
    } else if ( $action == 'eventdetail' ) {
      if ( !empty($_REQUEST['eid']) ) {
        dbQuery( 'UPDATE Events SET Cause=?, Notes=? WHERE Id=?', array( $_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $_REQUEST['eid'] ) );
        $refreshParent = true;
      } else {
        foreach( getAffectedIds( 'markEid' ) as $markEid ) {
          dbQuery( 'UPDATE Events SET Cause=?, Notes=? WHERE Id=?', array( $_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $markEid ) );
          $refreshParent = true;
        }
      }
    } elseif ( $action == 'archive' || $action == 'unarchive' ) {
      $archiveVal = ($action == 'archive')?1:0;
      if ( !empty($_REQUEST['eid']) ) {
        dbQuery( 'UPDATE Events SET Archived=? WHERE Id=?', array( $archiveVal, $_REQUEST['eid']) );
      } else {
        foreach( getAffectedIds( 'markEid' ) as $markEid ) {
          dbQuery( 'UPDATE Events SET Archived=? WHERE Id=?', array( $archiveVal, $markEid ) );
          $refreshParent = true;
        }
      }
    } elseif ( $action == 'delete' ) {
      foreach( getAffectedIds( 'markEid' ) as $markEid ) {
        deleteEvent( $markEid );
        $refreshParent = true;
      }
      if ( !empty($_REQUEST['fid']) ) {
        dbQuery( 'DELETE FROM Filters WHERE Name=?', array( $_REQUEST['fid'] ) );
        //$refreshParent = true;
      }
    }
  }

  // Monitor control actions, require a monitor id and control view permissions for that monitor
  if ( !empty($_REQUEST['mid']) && canView( 'Control', $_REQUEST['mid'] ) ) {
    require_once( 'control_functions.php' );
    require_once( 'Monitor.php' );
    $mid = validInt($_REQUEST['mid']);
    if ( $action == 'control' ) {
      $monitor = new Monitor( $mid );

      $ctrlCommand = buildControlCommand( $monitor );
      sendControlCommand( $monitor->Id(), $ctrlCommand );
    } elseif ( $action == 'settings' ) {
      $args = " -m " . escapeshellarg($mid);
      $args .= " -B" . escapeshellarg($_REQUEST['newBrightness']);
      $args .= " -C" . escapeshellarg($_REQUEST['newContrast']);
      $args .= " -H" . escapeshellarg($_REQUEST['newHue']);
      $args .= " -O" . escapeshellarg($_REQUEST['newColour']);

      $zmuCommand = getZmuCommand( $args );

      $zmuOutput = exec( $zmuCommand );
      list( $brightness, $contrast, $hue, $colour ) = explode( ' ', $zmuOutput );
      dbQuery( 'UPDATE Monitors SET Brightness = ?, Contrast = ?, Hue = ?, Colour = ? WHERE Id = ?', array($brightness, $contrast, $hue, $colour, $mid));
    }
  }

  // Control capability actions, require control edit permissions
  if ( canEdit( 'Control' ) ) {
    if ( $action == 'controlcap' ) {
      require_once( 'Control.php' );
      $Control = new Control( !empty($_REQUEST['cid']) ? $_REQUEST['cid'] : null );

      //$changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );
      $Control->save( $_REQUEST['newControl'] );
      $refreshParent = true;
      $view = 'none';
    } elseif ( $action == 'delete' ) {
      if ( isset($_REQUEST['markCids']) ) {
        foreach( $_REQUEST['markCids'] as $markCid ) {
          dbQuery( "delete from Controls where Id = ?", array($markCid) );
          dbQuery( "update Monitors set Controllable = 0, ControlId = 0 where ControlId = ?", array($markCid) );
          $refreshParent = true;
        }
      }
    }
  }

  // Monitor edit actions, require a monitor id and edit permissions for that monitor
  if ( !empty($_REQUEST['mid']) && canEdit( 'Monitors', $_REQUEST['mid'] ) ) {
    $mid = validInt($_REQUEST['mid']);
    if ( $action == 'function' ) {
      $monitor = dbFetchOne( 'SELECT * FROM Monitors WHERE Id=?', NULL, array($mid) );

      $newFunction = validStr($_REQUEST['newFunction']);
      # Because we use a checkbox, it won't get passed in the request. So not being in _REQUEST means 0
      $newEnabled = ( !isset( $_REQUEST['newEnabled'] ) or $_REQUEST['newEnabled'] != '1' ) ? '0' : '1';
      $oldFunction = $monitor['Function'];
      $oldEnabled = $monitor['Enabled'];
      if ( $newFunction != $oldFunction || $newEnabled != $oldEnabled ) {
        dbQuery( 'UPDATE Monitors SET Function=?, Enabled=? WHERE Id=?', array( $newFunction, $newEnabled, $mid ) );

        $monitor['Function'] = $newFunction;
        $monitor['Enabled'] = $newEnabled;
        if ( daemonCheck() ) {
          $restart = ($oldFunction == 'None') || ($newFunction == 'None') || ($newEnabled != $oldEnabled);
          zmaControl( $monitor, 'stop' );
          zmcControl( $monitor, $restart?'restart':'' );
          zmaControl( $monitor, 'start' );
        }
        $refreshParent = true;
      }
    } elseif ( $action == 'zone' && isset( $_REQUEST['zid'] ) ) {
      $zid = validInt($_REQUEST['zid']);
      $monitor = dbFetchOne( 'SELECT * FROM Monitors WHERE Id=?', NULL, array($mid) );

      if ( !empty($zid) ) {
        $zone = dbFetchOne( 'SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array( $mid, $zid ) );
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
      $changes = getFormChanges( $zone, $_REQUEST['newZone'], $types );

      if ( count( $changes ) ) {
        if ( $zid > 0 ) {
          dbQuery( "UPDATE Zones SET ".implode( ", ", $changes )." WHERE MonitorId=? AND Id=?", array( $mid, $zid) );
        } else {
          dbQuery( "INSERT INTO Zones SET MonitorId=?, ".implode( ", ", $changes ), array( $mid ) );
        }
        //if ( $cookies ) session_write_close();
        if ( daemonCheck() ) {
          if ( $_REQUEST['newZone']['Type'] == 'Privacy' ) {
            zmaControl( $monitor, 'stop' );
            zmcControl( $monitor, 'restart' );
            zmaControl( $monitor, 'start' );
          } else {
            zmaControl( $mid, 'restart' );
          }
        }
        if ( $_REQUEST['newZone']['Type'] == 'Privacy' && $monitor['Controllable'] ) {
          require_once( 'control_functions.php' );
          sendControlCommand( $mid, 'quit' );
        }
        $refreshParent = true;
      }
      $view = 'none';
    } elseif ( $action == 'plugin' && isset($_REQUEST['pl'])) {
      $sql='SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?';
      $pconfs=dbFetchAll( $sql, NULL, array( $mid, $_REQUEST['zid'], $_REQUEST['pl'] ) );
      $changes=0;
      foreach( $pconfs as $pconf ) {
        $value=$_REQUEST['pluginOpt'][$pconf['Name']];
        if(array_key_exists($pconf['Name'], $_REQUEST['pluginOpt']) && ($pconf['Value']!=$value)) {
          dbQuery("UPDATE PluginsConfig SET Value=? WHERE id=?", array( $value, $pconf['Id'] ) );
          $changes++;
        }
      }
      if($changes>0) {
        if ( daemonCheck() ) {
          zmaControl( $mid, 'restart' );
        }
        $refreshParent = true;
      }
      $view = 'none';
    } elseif ( $action == 'sequence' && isset($_REQUEST['smid']) ) {
      $smid = validInt($_REQUEST['smid']);
      $monitor = dbFetchOne( 'select * from Monitors where Id = ?', NULL, array($mid) );
      $smonitor = dbFetchOne( 'select * from Monitors where Id = ?', NULL, array($smid) );

      dbQuery( 'update Monitors set Sequence=? where Id=?', array( $smonitor['Sequence'], $monitor['Id'] ) );
      dbQuery( 'update Monitors set Sequence=? WHERE Id=?', array( $monitor['Sequence'], $smonitor['Id'] ) );

      $refreshParent = true;
      fixSequences();
    } elseif ( $action == 'delete' ) {
      if ( isset($_REQUEST['markZids']) ) {
        $deletedZid = 0;
        foreach( $_REQUEST['markZids'] as $markZid ) {
          $zone = dbFetchOne( 'select * from Zones where Id=?', NULL, array($markZid) );
          dbQuery( 'delete from Zones WHERE MonitorId=? AND Id=?', array( $mid, $markZid) );
          $deletedZid = 1;
        }
        if ( $deletedZid ) {
          //if ( $cookies )
          //session_write_close();
          if ( daemonCheck() ) {
            if ( $zone['Type'] == 'Privacy' ) {
              zmaControl( $mid, 'stop' );
              zmcControl( $mid, 'restart' );
              zmaControl( $mid, 'start' );
            } else {
              zmaControl( $mid, 'restart' );
            }
          } // end if daemonCheck()
          $refreshParent = true;
        } // end if deletedzid
      } // end if isset($_REQUEST['markZids'])
    } // end if action 
  } // end if $mid and canEdit($mid)

  // Monitor edit actions, monitor id derived, require edit permissions for that monitor
  if ( canEdit( 'Monitors' ) ) {
    if ( $action == 'monitor' ) {
      $mid = 0;
      if ( !empty($_REQUEST['mid']) ) {
        $mid = validInt($_REQUEST['mid']);
        $monitor = dbFetchOne( 'SELECT * FROM Monitors WHERE Id = ?', NULL, array($mid) );

        if ( ZM_OPT_X10 ) {
          $x10Monitor = dbFetchOne( 'SELECT * FROM TriggersX10 WHERE MonitorId=?', NULL, array($mid) );
          if ( !$x10Monitor )
            $x10Monitor = array();
        }
      } else {
        $monitor = array();
        if ( ZM_OPT_X10 ) {
          $x10Monitor = array();
        }
      }

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

      $columns = getTableColumns( 'Monitors' );
      $changes = getFormChanges( $monitor, $_REQUEST['newMonitor'], $types, $columns );

      if ( count( $changes ) ) {
        if ( $mid ) {

          # If we change anything that changes the shared mem size, zma can complain.  So let's stop first.
          zmaControl( $monitor, 'stop' );
          zmcControl( $monitor, 'stop' );
          dbQuery( 'UPDATE Monitors SET '.implode( ", ", $changes ).' WHERE Id =?', array($mid) );
          if ( isset($changes['Name']) ) {
            $saferOldName = basename( $monitor['Name'] );
            $saferNewName = basename( $_REQUEST['newMonitor']['Name'] );
            rename( ZM_DIR_EVENTS."/".$saferOldName, ZM_DIR_EVENTS."/".$saferNewName);
          }
          if ( isset($changes['Width']) || isset($changes['Height']) ) {
            $newW = $_REQUEST['newMonitor']['Width'];
            $newH = $_REQUEST['newMonitor']['Height'];
            $newA = $newW * $newH;
            $oldW = $monitor['Width'];
            $oldH = $monitor['Height'];
            $oldA = $oldW * $oldH;

            $zones = dbFetchAll( 'SELECT * FROM Zones WHERE MonitorId=?', NULL, array($mid) );
            foreach ( $zones as $zone ) {
              $newZone = $zone;
              $points = coordsToPoints( $zone['Coords'] );
              for ( $i = 0; $i < count($points); $i++ ) {
                $points[$i]['x'] = intval(($points[$i]['x']*($newW-1))/($oldW-1));
                $points[$i]['y'] = intval(($points[$i]['y']*($newH-1))/($oldH-1));
              }
              $newZone['Coords'] = pointsToCoords( $points );
              $newZone['Area'] = intval(round(($zone['Area']*$newA)/$oldA));
              $newZone['MinAlarmPixels'] = intval(round(($newZone['MinAlarmPixels']*$newA)/$oldA));
              $newZone['MaxAlarmPixels'] = intval(round(($newZone['MaxAlarmPixels']*$newA)/$oldA));
              $newZone['MinFilterPixels'] = intval(round(($newZone['MinFilterPixels']*$newA)/$oldA));
              $newZone['MaxFilterPixels'] = intval(round(($newZone['MaxFilterPixels']*$newA)/$oldA));
              $newZone['MinBlobPixels'] = intval(round(($newZone['MinBlobPixels']*$newA)/$oldA));
              $newZone['MaxBlobPixels'] = intval(round(($newZone['MaxBlobPixels']*$newA)/$oldA));

              $changes = getFormChanges( $zone, $newZone, $types );

              if ( count( $changes ) ) {
                dbQuery( "update Zones set ".implode( ", ", $changes )." WHERE MonitorId=? AND Id=?", array( $mid, $zone['Id'] ) );
              }
            }
          }
        } elseif ( ! $user['MonitorIds'] ) { // Can only create new monitors if we are not restricted to specific monitors
# FIXME This is actually a race condition. Should lock the table.
          $maxSeq = dbFetchOne( 'SELECT max(Sequence) AS MaxSequence FROM Monitors', 'MaxSequence' );
          $changes[] = 'Sequence = '.($maxSeq+1);

          dbQuery( 'INSERT INTO Monitors SET '.implode( ', ', $changes ) );
          $mid = dbInsertId();
          $zoneArea = $_REQUEST['newMonitor']['Width'] * $_REQUEST['newMonitor']['Height'];
          dbQuery( "insert into Zones set MonitorId = ?, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = ?, Area=?, AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels=?, MaxAlarmPixels=?, FilterX = 3, FilterY = 3, MinFilterPixels=?, MaxFilterPixels=?, MinBlobPixels=?, MinBlobs = 1", array( $mid, sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $_REQUEST['newMonitor']['Width']-1, 0, $_REQUEST['newMonitor']['Width']-1, $_REQUEST['newMonitor']['Height']-1, 0, $_REQUEST['newMonitor']['Height']-1 ), $zoneArea, intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*2)/100)  ) );
          //$view = 'none';
          mkdir( ZM_DIR_EVENTS.'/'.$mid, 0755 );
          $saferName = basename($_REQUEST['newMonitor']['Name']);
          symlink( $mid, ZM_DIR_EVENTS.'/'.$saferName );
          if ( isset($_COOKIE['zmGroup']) ) {
            dbQuery( "UPDATE Groups SET MonitorIds = concat(MonitorIds,',".$mid."') WHERE Id=?", array($_COOKIE['zmGroup']) );
          }
        } else {
          Error("Users with Monitors restrictions cannot create new monitors.");
        }
        $restart = true;
      }

      if ( ZM_OPT_X10 ) {
        $x10Changes = getFormChanges( $x10Monitor, $_REQUEST['newX10Monitor'] );

        if ( count( $x10Changes ) ) {
          if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) ) {
            dbQuery( "update TriggersX10 set ".implode( ", ", $x10Changes )." where MonitorId=?", array($mid) );
          } elseif ( !$user['MonitorIds'] ) {
            if ( !$x10Monitor ) {
              dbQuery( "insert into TriggersX10 set MonitorId = ?, ".implode( ", ", $x10Changes ), array( $mid ) );
            } else {
              dbQuery( "delete from TriggersX10 where MonitorId = ?", array($mid) );
            }
          }
          $restart = true;
        }
      }

      if ( $restart ) {
        $new_monitor = dbFetchOne( 'SELECT * FROM Monitors WHERE Id = ?', NULL, array($mid) );
        //fixDevices();
        //if ( $cookies )
        //session_write_close();

        zmcControl( $new_monitor, 'start' );
        zmaControl( $new_monitor, 'start' );

        if ( $monitor['Controllable'] ) {
          require_once( 'control_functions.php' );
          sendControlCommand( $mid, 'quit' );
        } 
        // really should thump zmwatch and maybe zmtrigger too.
        //daemonControl( 'restart', 'zmwatch.pl' );
        $refreshParent = true;
      } // end if restart
      $view = 'none';
    }
    if ( $action == 'delete' ) {
      if ( isset($_REQUEST['markMids']) && !$user['MonitorIds'] ) {
        foreach( $_REQUEST['markMids'] as $markMid ) {
          if ( canEdit( 'Monitors', $markMid ) ) {
            if ( $monitor = dbFetchOne( 'SELECT * FROM Monitors WHERE Id = ?', NULL, array($markMid) ) ) {
              if ( daemonCheck() ) {
                zmaControl( $monitor, 'stop' );
                zmcControl( $monitor, 'stop' );
              }

              // This is the important stuff
              dbQuery( 'DELETE FROM Monitors WHERE Id = ?', array($markMid) );
              dbQuery( 'DELETE FROM Zones WHERE MonitorId = ?', array($markMid) );
              if ( ZM_OPT_X10 )
                dbQuery( 'DELETE FROM TriggersX10 WHERE MonitorId=?', array($markMid) );

              fixSequences();

              // If fast deletes are on, then zmaudit will clean everything else up later
              // If fast deletes are off and there are lots of events then this step may
              // well time out before completing, in which case zmaudit will still tidy up
              if ( !ZM_OPT_FAST_DELETE ) {
                // Slight hack, we maybe should load *, but we happen to know that the deleteEvent function uses Id and StartTime.
                $markEids = dbFetchAll( 'SELECT Id,StartTime FROM Events WHERE MonitorId=?', NULL, array($markMid) );
                foreach( $markEids as $markEid )
                  deleteEvent( $markEid, $markMid );

                deletePath( ZM_DIR_EVENTS.'/'.basename($monitor['Name']) );
                deletePath( ZM_DIR_EVENTS.'/'.$monitor['Id'] ); // I'm trusting the Id.  
              } // end if ZM_OPT_FAST_DELETE
            } // end if found the monitor in the db
          } // end if canedit this monitor
        } // end foreach monitor in MarkMid
      } // markMids is set and we aren't limited to specific monitors
    } // end if action == Delete
  }

  // Device view actions
  if ( canEdit( 'Devices' ) ) {
    if ( $action == 'device' ) {
      if ( !empty($_REQUEST['command']) ) {
        setDeviceStatusX10( $_REQUEST['key'], $_REQUEST['command'] );
      } elseif ( isset( $_REQUEST['newDevice'] ) ) {
        if ( isset($_REQUEST['did']) ) {
          dbQuery( "update Devices set Name=?, KeyString=? where Id=?", array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'], $_REQUEST['did']) );
        } else {
          dbQuery( "insert into Devices set Name=?, KeyString=?", array( $_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'] ) );
        }
        $refreshParent = true;
        $view = 'none';
      }
    } elseif ( $action == 'delete' ) {
      if ( isset($_REQUEST['markDids']) ) {
        foreach( $_REQUEST['markDids'] as $markDid ) {
          dbQuery( "delete from Devices where Id=?", array($markDid) );
          $refreshParent = true;
        }
      }
    } // end if action
  } // end if canedit devices

  // Group view actions
  if ( canView( 'Groups' ) && $action == 'setgroup' ) {
    if ( !empty($_REQUEST['gid']) ) {
      setcookie( 'zmGroup', validInt($_REQUEST['gid']), time()+3600*24*30*12*10 );
    } else {
      setcookie( 'zmGroup', '', time()-3600*24*2 );
    }
    $refreshParent = true;
  }

  // Group edit actions
  if ( canEdit( 'Groups' ) ) {
    if ( $action == 'group' ) {
# Should probably verfy that each monitor id is a valid monitor, that we have access to. HOwever at the moment, you have to have System permissions to do this
      $monitors = empty( $_POST['newGroup']['MonitorIds'] ) ? NULL : implode(',', $_POST['newGroup']['MonitorIds']);
      if ( !empty($_POST['gid']) ) {
        dbQuery( "UPDATE Groups SET Name=?, MonitorIds=? WHERE Id=?", array($_POST['newGroup']['Name'], $monitors, $_POST['gid']) );
      } else {
        dbQuery( "INSERT INTO Groups SET Name=?, MonitorIds=?", array( $_POST['newGroup']['Name'], $monitors ) );
      }
      $view = 'none';
    }
    if ( !empty($_REQUEST['gid']) && $action == 'delete' ) {
      dbQuery( 'DELETE FROM Groups WHERE Id = ?', array($_REQUEST['gid']) );
      if ( isset($_COOKIE['zmGroup']) ) {
        if ( $_REQUEST['gid'] == $_COOKIE['zmGroup'] ) {
          unset( $_COOKIE['zmGroup'] );
          setcookie( 'zmGroup', '', time()-3600*24*2 );
          $refreshParent = true;
        }
      }
    }
    $refreshParent = true;
  } // end if can edit groups

  // System edit actions
  if ( canEdit( 'System' ) ) {
    if ( isset( $_REQUEST['object'] ) ) {
      if ( $_REQUEST['object'] == 'server' ) {

        if ( $action == 'Save' ) {
          if ( !empty($_REQUEST['id']) )
            $dbServer = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array($_REQUEST['id']) );
          else
            $dbServer = array();

          $types = array();
          $changes = getFormChanges( $dbServer, $_REQUEST['newServer'], $types );

          if ( count( $changes ) ) {
            if ( !empty($_REQUEST['id']) ) {
              dbQuery( "UPDATE Servers SET ".implode( ", ", $changes )." WHERE Id = ?", array($_REQUEST['id']) );
            } else {
              dbQuery( "INSERT INTO Servers set ".implode( ", ", $changes ) );
            }
            $refreshParent = true;
          }
          $view = 'none';
        } else if ( $action == 'delete' ) {
          if ( !empty($_REQUEST['markIds']) ) {
            foreach( $_REQUEST['markIds'] as $Id )
              dbQuery( "DELETE FROM Servers WHERE Id=?", array($Id) );
          }
          $refreshParent = true;
        } else {
          Error( "Unknown action $action in saving Server" );
        }
      } else if ( $_REQUEST['object'] == 'storage' ) {
        if ( $action == 'Save' ) {
          if ( !empty($_REQUEST['id']) )
            $dbStorage = dbFetchOne( 'SELECT * FROM Storage WHERE Id=?', NULL, array($_REQUEST['id']) );
          else
            $dbStorage = array();

          $types = array();
          $changes = getFormChanges( $dbStorage, $_REQUEST['newStorage'], $types );

          if ( count( $changes ) ) {
            if ( !empty($_REQUEST['id']) ) {
              dbQuery( "UPDATE Storage SET ".implode( ", ", $changes )." WHERE Id = ?", array($_REQUEST['id']) );
            } else {
              dbQuery( "INSERT INTO Storage set ".implode( ", ", $changes ) );
            }
            $refreshParent = true;
          }
          $view = 'none';
        } else if ( $action == 'delete' ) {
          if ( !empty($_REQUEST['markIds']) ) {
            foreach( $_REQUEST['markIds'] as $Id )
              dbQuery( 'DELETE FROM Storage WHERE Id=?', array($Id) );
          }
          $refreshParent = true;
        } else {
          Error( "Unknown action $action in saving Storage" );
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
            dbQuery( "update Config set Value = '".ZM_DYN_LAST_VERSION."' where Name = 'ZM_DYN_CURR_VERSION'" );
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
            dbQuery( "update Config set Value = '".$nextReminder."' where Name = 'ZM_DYN_NEXT_REMINDER'" );
            break;
          }
        case 'never' :
          {
            dbQuery( "update Config set Value = '0' where Name = 'ZM_CHECK_FOR_UPDATES'" );
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
            dbQuery( "update Config set Value = '".$nextReminder."' where Name = 'ZM_DYN_DONATE_REMINDER_TIME'" );
            break;
          }
        case 'never' :
        case 'already' :
          {
            dbQuery( "update Config set Value = '0' where Name = 'ZM_DYN_SHOW_DONATE_REMINDER'" );
            break;
          }
      } // end switch option
    }
    if ( $action == 'options' && isset($_REQUEST['tab']) ) {
      $configCat = $configCats[$_REQUEST['tab']];
      $changed = false;
      foreach ( $configCat as $name=>$value ) {
        unset( $newValue );
        if ( $value['Type'] == 'boolean' && empty($_REQUEST['newConfig'][$name]) )
          $newValue = 0;
        elseif ( isset($_REQUEST['newConfig'][$name]) )
          $newValue = preg_replace( "/\r\n/", "\n", stripslashes( $_REQUEST['newConfig'][$name] ) );

        if ( isset($newValue) && ($newValue != $value['Value']) ) {
          dbQuery( 'UPDATE Config SET Value=? WHERE Name=?', array( $newValue, $name ) );
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
      }
      loadConfig( false );
    } elseif ( $action == 'user' ) {
      if ( !empty($_REQUEST['uid']) )
        $dbUser = dbFetchOne( "SELECT * FROM Users WHERE Id=?", NULL, array($_REQUEST['uid']) );
      else
        $dbUser = array();

      $types = array();
      $changes = getFormChanges( $dbUser, $_REQUEST['newUser'], $types );

      if ( $_REQUEST['newUser']['Password'] )
        $changes['Password'] = "Password = password(".dbEscape($_REQUEST['newUser']['Password']).")";
      else
        unset( $changes['Password'] );

      if ( count( $changes ) ) {
        if ( !empty($_REQUEST['uid']) ) {
          dbQuery( "update Users set ".implode( ", ", $changes )." where Id = ?", array($_REQUEST['uid']) );
          # If we are updating the logged in user, then update our session user data.
          if ( $user and ( $dbUser['Username'] == $user['Username'] ) )
            userLogin( $dbUser['Username'], $dbUser['Password'] );
        } else {
          dbQuery( "insert into Users set ".implode( ", ", $changes ) );
        }
        $refreshParent = true;
      }
      $view = 'none';
    } elseif ( $action == 'state' ) {
      if ( !empty($_REQUEST['runState']) ) {
        //if ( $cookies ) session_write_close();
        packageControl( $_REQUEST['runState'] );
        $refreshParent = true;
      }
    } elseif ( $action == 'save' ) {
      if ( !empty($_REQUEST['runState']) || !empty($_REQUEST['newState']) ) {
        $sql = 'SELECT Id,Function,Enabled FROM Monitors ORDER BY Id';
        $definitions = array();
        foreach( dbFetchAll( $sql ) as $monitor )
        {
          $definitions[] = $monitor['Id'].":".$monitor['Function'].":".$monitor['Enabled'];
        }
        $definition = join( ',', $definitions );
        if ( $_REQUEST['newState'] )
          $_REQUEST['runState'] = $_REQUEST['newState'];
        dbQuery( "replace into States set Name=?, Definition=?", array( $_REQUEST['runState'],$definition) );
      }
    } elseif ( $action == 'delete' ) {
      if ( isset($_REQUEST['runState']) )
        dbQuery( "delete from States where Name=?", array($_REQUEST['runState']) );

      if ( isset($_REQUEST['markUids']) ) {
        foreach( $_REQUEST['markUids'] as $markUid )
          dbQuery( "delete from Users where Id = ?", array($markUid) );
        if ( $markUid == $user['Id'] )
          userLogout();
      }
    }
  } else {
    if ( ZM_USER_SELF_EDIT && $action == 'user' ) {
      $uid = $user['Id'];

      $dbUser = dbFetchOne( 'SELECT Id, Password, Language FROM Users WHERE Id = ?', NULL, array($uid) );

      $types = array();
      $changes = getFormChanges( $dbUser, $_REQUEST['newUser'], $types );

      if ( !empty($_REQUEST['newUser']['Password']) )
        $changes['Password'] = "Password = password(".dbEscape($_REQUEST['newUser']['Password']).")";
      else
        unset( $changes['Password'] );
      if ( count( $changes ) ) {
        dbQuery( "update Users set ".implode( ", ", $changes )." where Id=?", array($uid) );
        $refreshParent = true;
      }
      $view = 'none';
    }
  }

  if ( $action == 'reset' ) {
    $_SESSION['zmEventResetTime'] = strftime( STRF_FMT_DATETIME_DB );
    setcookie( 'zmEventResetTime', $_SESSION['zmEventResetTime'], time()+3600*24*30*12*10 );
    //if ( $cookies ) session_write_close();
  }
}

?>
