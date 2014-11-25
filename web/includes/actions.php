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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

function getAffectedIds( $name )
{
    $names = $name."s";
    $ids = array();
    if ( isset($_REQUEST[$names]) || isset($_REQUEST[$name]) )
    {
        if ( isset($_REQUEST[$names]) )
            $ids = validInt($_REQUEST[$names]);
        else if ( isset($_REQUEST[$name]) )
            $ids[] = validInt($_REQUEST[$name]);
    }
    return( $ids );
}

if ( ZM_OPT_USE_AUTH && ZM_AUTH_HASH_LOGINS && empty($user) && !empty($_REQUEST['auth']) )
{
    if ( $authUser = getAuthUser( $_REQUEST['auth'] ) )
    {
        userLogin( $authUser['Username'], $authUser['Password'], true );
    }
}

if ( !empty($action) )
{
    // General scope actions
    if ( $action == "login" && isset($_REQUEST['username']) && ( ZM_AUTH_TYPE == "remote" || isset($_REQUEST['password']) ) )
    {
        $username = validStr( $_REQUEST['username'] );
        $password = isset($_REQUEST['password'])?validStr($_REQUEST['password']):'';
        userLogin( $username, $password );
    }
    elseif ( $action == "logout" )
    {
        userLogout();
        $refreshParent = true;
        $view = 'none';
    }
    elseif ( $action == "bandwidth" && isset($_REQUEST['newBandwidth']) )
    {
        $_COOKIE['zmBandwidth'] = validStr($_REQUEST['newBandwidth']);
        setcookie( "zmBandwidth", validStr($_REQUEST['newBandwidth']), time()+3600*24*30*12*10 );
        $refreshParent = true;
    }

    // Event scope actions, view permissions only required
    if ( canView( 'Events' ) )
    {
        if ( $action == "filter" )
        {
            if ( !empty($_REQUEST['subaction']) )
            {
                if ( $_REQUEST['subaction'] == "addterm" )
                    $_REQUEST['filter'] = addFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
                elseif ( $_REQUEST['subaction'] == "delterm" )
                    $_REQUEST['filter'] = delFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
            }
            elseif ( canEdit( 'Events' ) )
            {
                if ( !empty($_REQUEST['execute']) )
                    $tempFilterName = "_TempFilter".time();
                if ( isset($tempFilterName) )
                    $filterName = $tempFilterName;
                elseif ( !empty($_REQUEST['newFilterName']) )
                    $filterName = $_REQUEST['newFilterName'];
                if ( !empty($filterName) )
                {
                    $_REQUEST['filter']['sort_field'] = validStr($_REQUEST['sort_field']);
                    $_REQUEST['filter']['sort_asc'] = validStr($_REQUEST['sort_asc']);
                    $_REQUEST['filter']['limit'] = validInt($_REQUEST['limit']);
                    $sql = "replace into Filters set Name = ".dbEscape($filterName).", Query = ".dbEscape(jsonEncode($_REQUEST['filter']));
                    if ( !empty($_REQUEST['autoArchive']) )
                        $sql .= ", AutoArchive = ".dbEscape($_REQUEST['autoArchive']);
                    if ( !empty($_REQUEST['autoVideo']) )
                        $sql .= ", AutoVideo = ".dbEscape($_REQUEST['autoVideo']);
                    if ( !empty($_REQUEST['autoUpload']) )
                        $sql .= ", AutoUpload = ".dbEscape($_REQUEST['autoUpload']);
                    if ( !empty($_REQUEST['autoEmail']) )
                        $sql .= ", AutoEmail = ".dbEscape($_REQUEST['autoEmail']);
                    if ( !empty($_REQUEST['autoMessage']) )
                        $sql .= ", AutoMessage = ".dbEscape($_REQUEST['autoMessage']);
                    if ( !empty($_REQUEST['autoExecute']) && !empty($_REQUEST['autoExecuteCmd']) )
                        $sql .= ", AutoExecute = ".dbEscape($_REQUEST['autoExecute']).", AutoExecuteCmd = ".dbEscape($_REQUEST['autoExecuteCmd']);
                    if ( !empty($_REQUEST['autoDelete']) )
                        $sql .= ", AutoDelete = ".dbEscape($_REQUEST['autoDelete']);
                    if ( !empty($_REQUEST['background']) )
                        $sql .= ", Background = ".dbEscape($_REQUEST['background']);
                    dbQuery( $sql );
                    $refreshParent = true;
                }
            }
        }
    }

    // Event scope actions, edit permissions required
    if ( canEdit( 'Events' ) )
    {
        if ( $action == "rename" && isset($_REQUEST['eventName']) && !empty($_REQUEST['eid']) )
        {
            dbQuery( 'UPDATE Events SET Name=? WHERE Id=?', array( $_REQUEST['eventName'], $_REQUEST['eid'] ) );
        }
        else if ( $action == "eventdetail" )
        {
            if ( !empty($_REQUEST['eid']) )
            {
                dbQuery( 'UPDATE Events SET Cause=?, Notes=? WHERE Id=?', array( $_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $_REQUEST['eid'] ) );
                $refreshParent = true;
            }
            else
            {
                foreach( getAffectedIds( 'markEid' ) as $markEid )
                {
					dbQuery( 'UPDATE Events SET Cause=?, Notes=? WHERE Id=?', array( $_REQUEST['newEvent']['Cause'], $_REQUEST['newEvent']['Notes'], $markEid ) );
                    $refreshParent = true;
                }
            }
        }
        elseif ( $action == "archive" || $action == "unarchive" )
        {
            $archiveVal = ($action == "archive")?1:0;
            if ( !empty($_REQUEST['eid']) )
            {
                dbQuery( 'UPDATE Events SET Archived=? WHERE Id=?', array( $archiveVal, $_REQUEST['eid']) );
            }
            else
            {
                foreach( getAffectedIds( 'markEid' ) as $markEid )
                {
					dbQuery( 'UPDATE Events SET Archived=? WHERE Id=?', array( $archiveVal, $markEid ) );
                    $refreshParent = true;
                }
            }
        }
        elseif ( $action == "delete" )
        {
            foreach( getAffectedIds( 'markEid' ) as $markEid )
            {
                deleteEvent( $markEid );
                $refreshParent = true;
            }
            if ( !empty($_REQUEST['fid']) )
            {
                dbQuery( 'DELETE FROM Filters WHERE Name=?', array( $_REQUEST['fid'] ) );
                //$refreshParent = true;
            }
        }
    }

    // Monitor control actions, require a monitor id and control view permissions for that monitor
    if ( !empty($_REQUEST['mid']) && canView( 'Control', $_REQUEST['mid'] ) )
    {
        require_once( 'control_functions.php' );
        $mid = validInt($_REQUEST['mid']);
        if ( $action == "control" )
        {
            $monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id) where M.Id = ?", NULL, array($mid) );

            $ctrlCommand = buildControlCommand( $monitor );

            if ( $ctrlCommand )
            {
                $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
                if ( $socket < 0 )
                {
                    Fatal( "socket_create() failed: ".socket_strerror($socket) );
                }
                $sockFile = ZM_PATH_SOCKS.'/zmcontrol-'.$monitor['Id'].'.sock';
                if ( @socket_connect( $socket, $sockFile ) )
                {
                    $options = array();
                    foreach ( explode( " ", $ctrlCommand ) as $option )
                    {
                        if ( preg_match( '/--([^=]+)(?:=(.+))?/', $option, $matches ) )
                        {
                            $options[$matches[1]] = $matches[2]?$matches[2]:1;
                        }
                    }
                    $optionString = jsonEncode( $options );
                    if ( !socket_write( $socket, $optionString ) )
                    {
                        Fatal( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
                    }
                    socket_close( $socket );
                }
                else
                {
                    $ctrlCommand .= " --id=".$monitor['Id'];

                    // Can't connect so use script
                    $ctrlOutput = exec( escapeshellcmd( $ctrlCommand ) );
                }
            }
        }
        elseif ( $action == "settings" )
        {
            $args = " -m " . escapeshellarg($mid);
            $args .= " -B" . escapeshellarg($_REQUEST['newBrightness']);
            $args .= " -C" . escapeshellarg($_REQUEST['newContrast']);
            $args .= " -H" . escapeshellarg($_REQUEST['newHue']);
            $args .= " -O" . escapeshellarg($_REQUEST['newColour']);

            $zmuCommand = getZmuCommand( $args );

            $zmuOutput = exec( $zmuCommand );
            list( $brightness, $contrast, $hue, $colour ) = explode( ' ', $zmuOutput );
			dbQuery( "update Monitors set Brightness = ?, Contrast = ?, Hue = ?, Colour = ? where Id = ?", array($brightness, $contrast, $hue, $colour, $mid));
        }
    }

    // Control capability actions, require control edit permissions
    if ( canEdit( 'Control' ) )
    {
        if ( $action == "controlcap" )
        {
            if ( !empty($_REQUEST['cid']) )
            {
                $control = dbFetchOne( "select * from Controls where Id = ?", NULL, array($_REQUEST['cid']) );
            }
            else
            {
                $control = array();
            }

            // Define a field type for anything that's not simple text equivalent
            $types = array(
                // Empty
            );

            $columns = getTableColumns( 'Controls' );
            foreach ( $columns as $name=>$type )
            {
                if ( preg_match( '/^(Can|Has)/', $name ) )
                {
                    $types[$name] = 'toggle';
                }
            }
            $changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['cid']) )
                {
                    dbQuery( "update Controls set ".implode( ", ", $changes )." where Id = ?", array($_REQUEST['cid']) );
                }
                else
                {
                    dbQuery( "insert into Controls set ".implode( ", ", $changes ) );
                    //$_REQUEST['cid'] = dbInsertId();
                }
                $refreshParent = true;
            }
            $view = 'none';
        }
        elseif ( $action == "delete" )
        {
            if ( isset($_REQUEST['markCids']) )
            {
                foreach( $_REQUEST['markCids'] as $markCid )
                {
                    dbQuery( "delete from Controls where Id = ?", array($markCid) );
                    dbQuery( "update Monitors set Controllable = 0, ControlId = 0 where ControlId = ?", array($markCid) );
                    $refreshParent = true;
                }
            }
        }
    }

    // Monitor edit actions, require a monitor id and edit permissions for that monitor
    if ( !empty($_REQUEST['mid']) && canEdit( 'Monitors', $_REQUEST['mid'] ) )
    {
        $mid = validInt($_REQUEST['mid']);
        if ( $action == "function" )
        {
            $monitor = dbFetchOne( "SELECT * FROM Monitors WHERE Id=?", NULL, array($mid) );

            $newFunction = validStr($_REQUEST['newFunction']);
            $newEnabled = validStr($_REQUEST['newEnabled']);
            if ($newEnabled != "1") $newEnabled = "0";
            $oldFunction = $monitor['Function'];
            $oldEnabled = $monitor['Enabled'];
            if ( $newFunction != $oldFunction || $newEnabled != $oldEnabled )
            {
                dbQuery( "update Monitors set Function=?, Enabled=? where Id=?", array( $newFunction, $newEnabled, $mid ) );

                $monitor['Function'] = $newFunction;
                $monitor['Enabled'] = $newEnabled;
                //if ( $cookies ) session_write_close();
                if ( daemonCheck() )
                {
                    $restart = ($oldFunction == 'None') || ($newFunction == 'None') || ($newEnabled != $oldEnabled);
                    zmaControl( $monitor, "stop" );
                    zmcControl( $monitor, $restart?"restart":"" );
                    zmaControl( $monitor, "start" );
                }
                $refreshParent = true;
            }
        }
        elseif ( $action == "zone" && isset( $_REQUEST['zid'] ) )
        {
            $zid = validInt($_REQUEST['zid']);
            $monitor = dbFetchOne( "SELECT * FROM Monitors WHERE Id=?", NULL, array($mid) );

            if ( !empty($zid) )
            {
                $zone = dbFetchOne( "SELECT * FROM Zones WHERE MonitorId=? AND Id=?", NULL, array( $mid, $zid ) );
            }
            else
            {
                $zone = array();
            }

            if ( $_REQUEST['newZone']['Units'] == 'Percent' )
            {
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

            if ( count( $changes ) )
            {
                if ( $zid > 0 )
                {
                dbQuery( "UPDATE Zones SET ".implode( ", ", $changes )." WHERE MonitorId=? AND Id=?", array( $mid, $zid) );
                }
                else
                {
                dbQuery( "INSERT INTO Zones SET MonitorId=?, ".implode( ", ", $changes ), array( $mid ) );
                }
                //if ( $cookies ) session_write_close();
                if ( daemonCheck() )
                {
                    zmaControl( $mid, "restart" );
                }
                $refreshParent = true;
            }
            $view = 'none';
        }
        elseif ( $action == "plugin" && isset($_REQUEST['pl']))
        {
           $sql="SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?";
           $pconfs=dbFetchAll( $sql, NULL, array( $mid, $_REQUEST['zid'], $_REQUEST['pl'] ) );
           $changes=0;
           foreach( $pconfs as $pconf )
           {
              $value=$_REQUEST['pluginOpt'][$pconf['Name']];
              if(array_key_exists($pconf['Name'], $_REQUEST['pluginOpt']) && ($pconf['Value']!=$value))
              {
                 dbQuery("UPDATE PluginsConfig SET Value=? WHERE id=?", array( $value, $pconf['Id'] ) );
                 $changes++;
              }
           }
           if($changes>0)
           {
                if ( daemonCheck() )
                {
                    zmaControl( $mid, "restart" );
                }
                $refreshParent = true;
           }
           $view = 'none';
        }
        elseif ( $action == "sequence" && isset($_REQUEST['smid']) )
        {
            $smid = validInt($_REQUEST['smid']);
            $monitor = dbFetchOne( "select * from Monitors where Id = ?", NULL, array($mid) );
            $smonitor = dbFetchOne( "select * from Monitors where Id = ?", NULL, array($smid) );

            dbQuery( "update Monitors set Sequence=? where Id=?", array( $smonitor['Sequence'], $monitor['Id'] ) );
            dbQuery( "update Monitors set Sequence=? WHERE Id=?", array( $monitor['Sequence'], $smonitor['Id'] ) );

            $refreshParent = true;
            fixSequences();
        }
        if ( $action == "delete" )
        {
            if ( isset($_REQUEST['markZids']) )
            {
                $deletedZid = 0;
                foreach( $_REQUEST['markZids'] as $markZid )
                {
                    dbQuery( "delete from Zones WHERE MonitorId=? AND Id=?", array( $mid, $markZid) );
                    $deletedZid = 1;
                }
                if ( $deletedZid )
                {
                    //if ( $cookies )
                        //session_write_close();
                    if ( daemonCheck() )
                        zmaControl( $mid, "restart" );
                    $refreshParent = true;
                }
            }
        }
    }

    // Monitor edit actions, monitor id derived, require edit permissions for that monitor
    if ( canEdit( 'Monitors' ) )
    {
        if ( $action == "monitor" )
        {
            if ( !empty($_REQUEST['mid']) )
            {
                $mid = validInt($_REQUEST['mid']);
                $monitor = dbFetchOne( "select * from Monitors where Id = ?", NULL, array($mid) );

                if ( ZM_OPT_X10 )
                {
                    $x10Monitor = dbFetchOne( "select * from TriggersX10 where MonitorId=?", NULL, array($mid) );
                    if ( !$x10Monitor )
                        $x10Monitor = array();
                }
            }
            else
            {
                $monitor = array();
                if ( ZM_OPT_X10 )
                {
                    $x10Monitor = array();
                }
            }

            // Define a field type for anything that's not simple text equivalent
            $types = array(
                'Triggers' => 'set',
                'Controllable' => 'toggle',
                'TrackMotion' => 'toggle',
                'Enabled' => 'toggle',
                'DoNativeMotDet' => 'toggle'
            );

            $columns = getTableColumns( 'Monitors' );
            $changes = getFormChanges( $monitor, $_REQUEST['newMonitor'], $types, $columns );

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['mid']) )
                {
                    $mid = validInt($_REQUEST['mid']);
                    dbQuery( "update Monitors set ".implode( ", ", $changes )." where Id =?", array($mid) );
                    if ( isset($changes['Name']) )
                    {
						$saferOldName = basename( $monitor['Name'] );
						$saferNewName = basename( $_REQUEST['newMonitor']['Name'] );
						rename( ZM_DIR_EVENTS."/".$saferOldName, ZM_DIR_EVENTS."/".$saferNewName);
                    }
                    if ( isset($changes['Width']) || isset($changes['Height']) )
                    {
                        $newW = $_REQUEST['newMonitor']['Width'];
                        $newH = $_REQUEST['newMonitor']['Height'];
                        $newA = $newW * $newH;
                        $oldW = $monitor['Width'];
                        $oldH = $monitor['Height'];
                        $oldA = $oldW * $oldH;

                        $zones = dbFetchAll( "select * from Zones where MonitorId=?", NULL, array($mid) );
                        foreach ( $zones as $zone )
                        {
                            $newZone = $zone;
                            $points = coordsToPoints( $zone['Coords'] );
                            for ( $i = 0; $i < count($points); $i++ )
                            {
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

                            if ( count( $changes ) )
                            {
                                dbQuery( "update Zones set ".implode( ", ", $changes )." WHERE MonitorId=? AND Id=?", array( $mid, $zone['Id'] ) );
                            }
                        }
                    }
                }
                elseif ( !$user['MonitorIds'] )
                {
					# FIXME This is actually a race condition. Should lock the table.
                    $maxSeq = dbFetchOne( "select max(Sequence) as MaxSequence from Monitors", "MaxSequence" );
                    $changes[] = "Sequence = ".($maxSeq+1);

                    dbQuery( "insert into Monitors set ".implode( ", ", $changes ) );
                    $mid = dbInsertId();
                    $zoneArea = $_REQUEST['newMonitor']['Width'] * $_REQUEST['newMonitor']['Height'];
                    dbQuery( "insert into Zones set MonitorId = ?, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = ?, Area=?, AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels=?, MaxAlarmPixels=?, FilterX = 3, FilterY = 3, MinFilterPixels=?, MaxFilterPixels=?, MinBlobPixels=?, MinBlobs = 1", array( $mid, sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $_REQUEST['newMonitor']['Width']-1, 0, $_REQUEST['newMonitor']['Width']-1, $_REQUEST['newMonitor']['Height']-1, 0, $_REQUEST['newMonitor']['Height']-1 ), $zoneArea, intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*3)/100), intval(($zoneArea*75)/100), intval(($zoneArea*2)/100)  ) );
                    //$view = 'none';
                    mkdir( ZM_DIR_EVENTS.'/'.$mid, 0755 );
					$saferName = basename($_REQUEST['newMonitor']['Name']);
					symlink( $mid, ZM_DIR_EVENTS.'/'.$saferName );
                    if ( isset($_COOKIE['zmGroup']) )
                    {
                        dbQuery( "UPDATE Groups SET MonitorIds = concat(MonitorIds,',".$mid."') WHERE Id=?", array($_COOKIE['zmGroup']) );
                    }
                }
                $restart = true;
            }

            if ( ZM_OPT_X10 )
            {
                $x10Changes = getFormChanges( $x10Monitor, $_REQUEST['newX10Monitor'] );

                if ( count( $x10Changes ) )
                {
                    if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) )
                    {
                        dbQuery( "update TriggersX10 set ".implode( ", ", $x10Changes )." where MonitorId=?", array($mid) );
                    }
                    elseif ( !$user['MonitorIds'] )
                    {
                        if ( !$x10Monitor )
                        {
                            dbQuery( "insert into TriggersX10 set MonitorId = ?".implode( ", ", $x10Changes ), array( $mid ) );
                        }
                        else
                        {
                            dbQuery( "delete from TriggersX10 where MonitorId = ?", array($mid) );
                        }
                    }
                    $restart = true;
                }
            }

            if ( $restart )
            {
                $monitor = dbFetchOne( "select * from Monitors where Id = ?", NULL, array($mid) );
                //fixDevices();
                //if ( $cookies )
                    //session_write_close();
                if ( daemonCheck() )
                {
                    zmaControl( $monitor, "stop" );
                    zmcControl( $monitor, "restart" );
                    zmaControl( $monitor, "start" );
                }
                //daemonControl( 'restart', 'zmwatch.pl' );
                $refreshParent = true;
            }
            $view = 'none';
        }
        if ( $action == "delete" )
        {
            if ( isset($_REQUEST['markMids']) && !$user['MonitorIds'] )
            {
                foreach( $_REQUEST['markMids'] as $markMid )
                {
                    if ( canEdit( 'Monitors', $markMid ) )
                    {
                        if ( $monitor = dbFetchOne( "select * from Monitors where Id = ?", NULL, array($markMid) ) )
                        {
                            if ( daemonCheck() )
                            {
                                zmaControl( $monitor, "stop" );
                                zmcControl( $monitor, "stop" );
                            }

                            // This is the important stuff
                            dbQuery( "delete from Monitors where Id = ?", array($markMid) );
                            dbQuery( "delete from Zones where MonitorId = ?", array($markMid) );
                            if ( ZM_OPT_X10 )
                                dbQuery( "delete from TriggersX10 where MonitorId=?", array($markMid) );

                            fixSequences();

                            // If fast deletes are on, then zmaudit will clean everything else up later
                            // If fast deletes are off and there are lots of events then this step may
                            // well time out before completing, in which case zmaudit will still tidy up
                            if ( !ZM_OPT_FAST_DELETE )
                            {
                                $markEids = dbFetchAll( "select Id from Events where MonitorId=?", 'Id', array($markMid) );
                                foreach( $markEids as $markEid )
                                    deleteEvent( $markEid );

                                deletePath( ZM_DIR_EVENTS."/".basename($monitor['Name']) );
                                deletePath( ZM_DIR_EVENTS."/".$monitor['Id'] ); // I'm trusting the Id.  
                            }
                        }
                    }
                }
            }
        }
    }

    // Device view actions
    if ( canEdit( 'Devices' ) )
    {
        if ( $action == "device" )
        {
            if ( !empty($_REQUEST['command']) )
            {
                setDeviceStatusX10( $_REQUEST['key'], $_REQUEST['command'] );
            }
            elseif ( isset( $_REQUEST['newDevice'] ) )
            {
                if ( isset($_REQUEST['did']) )
                {
                    dbQuery( "update Devices set Name=?, KeyString=? where Id=?", array($_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'], $_REQUEST['did']) );
                }
                else
                {
                    dbQuery( "insert into Devices set Name=?, KeyString=?", array( $_REQUEST['newDevice']['Name'], $_REQUEST['newDevice']['KeyString'] ) );
                }
                $refreshParent = true;
                $view = 'none';
            }
        }
        elseif ( $action == "delete" )
        {
            if ( isset($_REQUEST['markDids']) )
            {
                foreach( $_REQUEST['markDids'] as $markDid )
                {
                    dbQuery( "delete from Devices where Id=?", array($markDid) );
                    $refreshParent = true;
                }
            }
        }
    }

    // System view actions
    if ( canView( 'System' ) )
    {
        if ( $action == "setgroup" )
        {
            if ( !empty($_REQUEST['gid']) )
            {
                setcookie( "zmGroup", validInt($_REQUEST['gid']), time()+3600*24*30*12*10 );
            }
            else
            {
                setcookie( "zmGroup", "", time()-3600*24*2 );
            }
            $refreshParent = true;
        }
    }

    // System edit actions
    if ( canEdit( 'System' ) )
    {
        if ( $action == "version" && isset($_REQUEST['option']) )
        {
            $option = $_REQUEST['option'];
            switch( $option )
            {
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
                    if ( $option == 'hour' )
                    {
                        $nextReminder += 60*60;
                    }
                    elseif ( $option == 'day' )
                    {
                        $nextReminder += 24*60*60;
                    }
                    elseif ( $option == 'week' )
                    {
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
        if ( $action == "donate" && isset($_REQUEST['option']) )
        {
            $option = $_REQUEST['option'];
            switch( $option )
            {
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
                    if ( $option == 'hour' )
                    {
                        $nextReminder += 60*60;
                    }
                    elseif ( $option == 'day' )
                    {
                        $nextReminder += 24*60*60;
                    }
                    elseif ( $option == 'week' )
                    {
                        $nextReminder += 7*24*60*60;
                    }
                    elseif ( $option == 'month' )
                    {
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
            }
        }
        if ( $action == "options" && isset($_REQUEST['tab']) )
        {
            $configCat = $configCats[$_REQUEST['tab']];
            $changed = false;
            foreach ( $configCat as $name=>$value )
            {
                unset( $newValue );
                if ( $value['Type'] == "boolean" && empty($_REQUEST['newConfig'][$name]) )
                     $newValue = 0;
                elseif ( isset($_REQUEST['newConfig'][$name]) )
                     $newValue = preg_replace( "/\r\n/", "\n", stripslashes( $_REQUEST['newConfig'][$name] ) );

                if ( isset($newValue) && ($newValue != $value['Value']) )
                {
                    dbQuery( 'UPDATE Config SET Value=? WHERE Name=?', array( $newValue, $name ) );
                    $changed = true;
                }
            }
            if ( $changed )
            {
                switch( $_REQUEST['tab'] )
                {
                    case "system" :
                    case "config" :
                    case "paths" :
                        $restartWarning = true;
                        break;
                    case "web" :
                    case "tools" :
                        break;
                    case "logging" :
                    case "network" :
                    case "mail" :
                    case "upload" :
                        $restartWarning = true;
                        break;
                    case "highband" :
                    case "medband" :
                    case "lowband" :
                    case "phoneband" :
                        break;
                }
            }
            loadConfig( false );
        }
        elseif ( $action == "user" )
        {
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

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['uid']) )
                {
                    dbQuery( "update Users set ".implode( ", ", $changes )." where Id = ?", array($_REQUEST['uid']) );
                }
                else
                {
					dbQuery( "insert into Users set ".implode( ", ", $changes ) );
                }
                $refreshParent = true;
                if ( $dbUser['Username'] == $user['Username'] )
                    userLogin( $dbUser['Username'], $dbUser['Password'] );
            }
            $view = 'none';
        }
        elseif ( $action == "state" )
        {
            if ( !empty($_REQUEST['runState']) )
            {
                //if ( $cookies ) session_write_close();
                packageControl( $_REQUEST['runState'] );
                $refreshParent = true;
            }
        }
        elseif ( $action == "save" )
        {
            if ( !empty($_REQUEST['runState']) || !empty($_REQUEST['newState']) )
            {
                $sql = "select Id,Function,Enabled from Monitors order by Id";
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
        }
        elseif ( $action == "group" )
        {
			# Should probably verfy that each monitor id is a valid monitor, that we have access to. HOwever at the moment, you have to have System permissions to do this
			$monitors = empty( $_POST['newGroup']['MonitorIds'] ) ? NULL : implode(',', $_POST['newGroup']['MonitorIds']);
			if ( !empty($_POST['gid']) ) {
				dbQuery( "UPDATE Groups SET Name=?, MonitorIds=? WHERE Id=?", array($_POST['newGroup']['Name'], $monitors, $_POST['gid']) );
			} else {
				dbQuery( "INSERT INTO Groups SET Name=?, MonitorIds=?", array( $_POST['newGroup']['Name'], $monitors ) );
			}

            $refreshParent = true;
            $view = 'none';
        }
        elseif ( $action == "delete" )
        {
            if ( isset($_REQUEST['runState']) )
                dbQuery( "delete from States where Name=?", array($_REQUEST['runState']) );

            if ( isset($_REQUEST['markUids']) )
            {
                foreach( $_REQUEST['markUids'] as $markUid )
                    dbQuery( "delete from Users where Id = ?", array($markUid) );
                if ( $markUid == $user['Id'] )
                    userLogout();
            }
            if ( !empty($_REQUEST['gid']) )
            {
                dbQuery( "delete from Groups where Id = ?", array($_REQUEST['gid']) );
                if ( isset($_COOKIE['zmGroup']) )
                {
                    if ( $_REQUEST['gid'] == $_COOKIE['zmGroup'] )
                    {
                        unset( $_COOKIE['zmGroup'] );
                        setcookie( "zmGroup", "", time()-3600*24*2 );
                        $refreshParent = true;
                    }
                }
            }
        }
    }
    else
    {
        if ( ZM_USER_SELF_EDIT && $action == "user" )
        {
            $uid = $user['Id'];

            $dbUser = dbFetchOne( "select Id, Password, Language from Users where Id = ?", NULL, array($uid) );

            $types = array();
            $changes = getFormChanges( $dbUser, $_REQUEST['newUser'], $types );

            if ( !empty($_REQUEST['newUser']['Password']) )
                $changes['Password'] = "Password = password(".dbEscape($_REQUEST['newUser']['Password']).")";
            else
                unset( $changes['Password'] );
            if ( count( $changes ) )
            {
                dbQuery( "update Users set ".implode( ", ", $changes )." where Id=?", array($uid) );
                $refreshParent = true;
            }
            $view = 'none';
        }
    }

    if ( $action == "reset" )
    {
        $_SESSION['zmEventResetTime'] = strftime( STRF_FMT_DATETIME_DB );
        setcookie( "zmEventResetTime", $_SESSION['zmEventResetTime'], time()+3600*24*30*12*10 );
        //if ( $cookies ) session_write_close();
    }
}

?>
