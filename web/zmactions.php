<?php

//
// Zone Monitor web action file, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

if ( $action )
{
	if ( $action == "rename" && $event_name && $eid )
	{
		$result = mysql_query( "update Events set Name = '$event_name' where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
	}
	elseif ( $action == "archive" && $eid )
	{
		$result = mysql_query( "update Events set Archived = 1 where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
	}
	elseif ( $action == "unarchive" && $eid )
	{
		$result = mysql_query( "update Events set Archived = 0 where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
	}
	elseif ( $action == "delete" )
	{
		if ( !$delete_eids && $delete_eid )
		{
			$delete_eids[] = $delete_eid;
			$refresh_parent = true;
		}
		if ( $delete_eids )
		{
			foreach( $delete_eids as $delete_eid )
			{
				$result = mysql_query( "delete from Frames where EventId = '$delete_eid'" );
				if ( !$result )
					die( mysql_error() );
				$result = mysql_query( "delete from Events where Id = '$delete_eid'" );
				if ( !$result )
					die( mysql_error() );
				if ( $delete_eid )
					system( escapeshellcmd( "rm -rf ".EVENT_PATH."/*/".sprintf( "%04d", $delete_eid ) ) );
			}
		}
		elseif ( $delete_zids )
		{
			$deleted_zid = 0;
			foreach( $delete_zids as $delete_zid )
			{
				$result = mysql_query( "delete from Zones where Id = '$delete_zid'" );
				if ( !$result )
					die( mysql_error() );
				$deleted_zid = 1;
			}
			if ( $deleted_zid )
			{
				startDaemon( "zma", $mid );
				$refresh_parent = true;
			}
		}
		elseif ( $delete_mids )
		{
			foreach( $delete_mids as $delete_mid )
			{
				$sql = "select * from Monitors where Id = '$delete_mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				if ( !($monitor = mysql_fetch_assoc( $result )) )
				{
					continue;
				}

				$sql = "select Id from Events where MonitorId = '$delete_mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );

				$delete_eids = array();
				while( $row = mysql_fetch_assoc( $result ) )
				{
					$delete_eids[] = $row[Id];
				}
				foreach( $delete_eids as $delete_eid )
				{
					$result = mysql_query( "delete from Frames where EventId = '$delete_eid'" );
					if ( !$result )
						die( mysql_error() );
					$result = mysql_query( "delete from Events where Id = '$delete_eid'" );
					if ( !$result )
						die( mysql_error() );
					if ( $delete_eid )
						system( "rm -rf ".EVENT_PATH."/*/".sprintf( "%04d", $delete_eid ) );
				}
				system( "rm -rf ".EVENT_PATH."/".$monitor[Name] );

				$result = mysql_query( "delete from Zones where MonitorId = '$delete_mid'" );
				if ( !$result )
					die( mysql_error() );
				$result = mysql_query( "delete from Monitors where Id = '$delete_mid'" );
				if ( !$result )
					die( mysql_error() );
			}
		}
	}
	elseif ( $action == "function" && $mid )
	{
		$sql = "select * from Monitors where Id = '$mid'";
		$result = mysql_query( $sql );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$old_function = $monitor['Function'];
		if ( $new_function != $old_function )
		{
			$sql = "update Monitors set Function = '$new_function' where Id = '$mid'";
			$result = mysql_query( $sql );
			if ( !$result )
				echo mysql_error();

			controlDaemons( $monitor[Device] );
			$refresh_parent = true;
		}
	}
	elseif ( $action == "device" && isset( $did ) )
	{
		if ( $zmc_status && !$zmc_action )
		{
			stopDaemon( "zmc", $did );
		}
		elseif ( !$zmc_status && $zmc_action )
		{
			startDaemon( "zmc", $did );
		}
		if ( $zma_status && !$zma_action )
		{
			stopDaemon( "zma", $did );
		}
		elseif ( !$zma_status && $zma_action )
		{
			startDaemon( "zma", $did );
		}
	}
	elseif ( $action == "zone" && isset( $mid ) && isset( $zid ) )
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		if ( $zid > 0 )
		{
			$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
			if ( !$result )
				die( mysql_error() );
			$zone = mysql_fetch_assoc( $result );
		}
		else
		{
			$zone = array();
		}

		$changes = array();
		if ( $new_name != $zone[Name] ) $changes[] = "Name = '$new_name'";
		if ( $new_type != $zone['Type'] ) $changes[] = "Type = '$new_type'";
		if ( $new_units != $zone[Units] ) $changes[] = "Units = '$new_units'";
		if ( $new_lo_x != $zone[LoX] ) $changes[] = "LoX = '$new_lo_x'";
		if ( $new_lo_y != $zone[LoY] ) $changes[] = "LoY = '$new_lo_y'";
		if ( $new_hi_x != $zone[HiX] ) $changes[] = "HiX = '$new_hi_x'";
		if ( $new_hi_y != $zone[HiY] ) $changes[] = "HiY = '$new_hi_y'";
		if ( $new_alarm_rgb != $zone[AlarmRGB] ) $changes[] = "AlarmRGB = '$new_alarm_rgb'";
		if ( $new_alarm_threshold != $zone[AlarmThreshold] ) $changes[] = "AlarmThreshold = '$new_alarm_threshold'";
		if ( $new_min_alarm_pixels != $zone[MinAlarmPixels] ) $changes[] = "MinAlarmPixels = '$new_min_alarm_pixels'";
		if ( $new_max_alarm_pixels != $zone[MaxAlarmPixels] ) $changes[] = "MaxAlarmPixels = '$new_max_alarm_pixels'";
		if ( $new_filter_x != $zone[FilterX] ) $changes[] = "FilterX = '$new_filter_x'";
		if ( $new_filter_y != $zone[FilterY] ) $changes[] = "FilterY = '$new_filter_y'";
		if ( $new_min_filter_pixels != $zone[MinFilterPixels] ) $changes[] = "MinFilterPixels = '$new_min_filter_pixels'";
		if ( $new_max_filter_pixels != $zone[MaxFilterPixels] ) $changes[] = "MaxFilterPixels = '$new_max_filter_pixels'";
		if ( $new_min_blob_pixels != $zone[MinBlobPixels] ) $changes[] = "MinBlobPixels = '$new_min_blob_pixels'";
		if ( $new_max_blob_pixels != $zone[MaxBlobPixels] ) $changes[] = "MaxBlobPixels = '$new_max_blob_pixels'";
		if ( $new_min_blobs != $zone[MinBlobs] ) $changes[] = "MinBlobs = '$new_min_blobs'";
		if ( $new_max_blobs != $zone[MaxBlobs] ) $changes[] = "MaxBlobs = '$new_max_blobs'";

		if ( count( $changes ) )
		{
			if ( $zid > 0 )
			{
				$sql = "update Zones set ".implode( ", ", $changes )." where MonitorId = '$mid' and Id = '$zid'";
			}
			else
			{
				$sql = "insert into Zones set MonitorId = '$mid', ".implode( ", ", $changes );
				$view = 'none';
			}
			#echo "<html>$sql</html>";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
			startDaemon( "zma", $monitor[Device] );
			$refresh_parent = true;
		}
	}
	elseif ( $action == "monitor" && isset( $mid ) )
	{
		if ( $mid > 0 )
		{
			$result = mysql_query( "select * from Monitors where Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
		}
		else
		{
			$monitor = array();
		}

		$changes = array();
		if ( $new_name != $monitor[Name] ) $changes[] = "Name = '$new_name'";
		if ( $new_function != $monitor['Function'] ) $changes[] = "Function = '$new_function'";
		if ( $new_device != $monitor['Device'] ) $changes[] = "Device = '$new_device'";
		if ( $new_channel != $monitor['Channel'] ) $changes[] = "Channel = '$new_channel'";
		if ( $new_format != $monitor['Format'] ) $changes[] = "Format = '$new_format'";
		if ( $new_width != $monitor['Width'] ) $changes[] = "Width = '$new_width'";
		if ( $new_height != $monitor['Height'] ) $changes[] = "Height = '$new_height'";
		if ( $new_colours != $monitor['Colours'] ) $changes[] = "Colours = '$new_colours'";

		if ( count( $changes ) )
		{
			if ( $mid > 0 )
			{
				$sql = "update Monitors set ".implode( ", ", $changes )." where MonitorId = '$mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				if ( $new_name != $monitor[Name] )
				{
					exec( escape_shell_command( "mv ".EVENTS_PATH."/$monitor[Name] ".EVENTS_PATH."/$new_name" ) );
				}
			}
			else
			{
				$sql = "insert into Monitors set ".implode( ", ", $changes );
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				$mid = mysql_insert_id();
				$sql = "insert into Zones set MonitorId = $mid, Name = 'All', Type = 'Active', Units = 'Percent', LoX = 0, LoY = 0, HiX = 100, HiY = 100, AlarmRGB = 0xff0000, AlarmThreshold = 25, MinAlarmPixels = 3, MaxAlarmPixels = 75, FilterX = 3, FilterY = 3, MinFilterPixels = 3, MaxFilterPixels = 75, MinBlobPixels = 2, MaxBlobPixels = 0, MinBlobs = 1, MaxBlobs = 0";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				$view = 'none';
			}
			$result = mysql_query( "select * from Monitors where Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
			controlDaemons( $monitor[Device] );
			$refresh_parent = true;
		}
	}
	elseif ( $action == "reset" )
	{
		$HTTP_SESSION_VARS[event_reset_time] = strftime( "%Y-%m-%d %H:%M:%S" );
		setcookie( "event_reset_time", $HTTP_SESSION_VARS[event_reset_time], time()+3600*24*30*12*10 );
	}
}

?>
