<?php

//
// ZoneMinder web action file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
	//phpinfo( INFO_VARIABLES );
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
		if ( !$mark_eids && $mark_eid )
		{
			$mark_eids[] = $mark_eid;
			$refresh_parent = true;
		}
		if ( $mark_eids )
		{
			foreach( $mark_eids as $mark_eid )
			{
				deleteEvent( $mark_eid );
			}
		}
		elseif ( $mark_zids )
		{
			$deleted_zid = 0;
			foreach( $mark_zids as $mark_zid )
			{
				$result = mysql_query( "delete from Zones where Id = '$mark_zid'" );
				if ( !$result )
					die( mysql_error() );
				$deleted_zid = 1;
			}
			if ( $deleted_zid )
			{
				zmaControl( $mid, true );
				$refresh_parent = true;
			}
		}
		elseif ( $mark_mids )
		{
			foreach( $mark_mids as $mark_mid )
			{
				$sql = "select * from Monitors where Id = '$mark_mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				if ( !($monitor = mysql_fetch_assoc( $result )) )
				{
					continue;
				}

				$sql = "select Id from Events where MonitorId = '$mark_mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );

				$mark_eids = array();
				while( $row = mysql_fetch_assoc( $result ) )
				{
					$mark_eids[] = $row[Id];
				}
				foreach( $mark_eids as $mark_eid )
				{
					deleteEvent( $mark_eid );
				}
				system( "rm -rf ".EVENT_PATH."/".$monitor[Name] );

				$result = mysql_query( "delete from Zones where MonitorId = '$mark_mid'" );
				if ( !$result )
					die( mysql_error() );
				$result = mysql_query( "delete from Monitors where Id = '$mark_mid'" );
				if ( !$result )
					die( mysql_error() );
			}
		}
		elseif ( $fid )
		{
			$sql = "delete from Filters where MonitorId = '$mid' and Name = '$fid'";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
			//$refresh_parent = true;
		}
	}
	elseif ( $action == "learn" )
	{
		if ( !$mark_eids && $mark_eid )
		{
			$mark_eids[] = $mark_eid;
			$refresh_parent = true;
		}
		if ( $mark_eids )
		{
			foreach( $mark_eids as $mark_eid )
			{
				$result = mysql_query( "update Events set LearnState = '$learn_state' where Id = '$mark_eid'" );
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

			$monitor['Function'] = $new_function;
			zmcControl( $monitor, true );
			zmaControl( $monitor );
			$refresh_parent = true;
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
			zmaControl( $mid, true );
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
		if ( $new_type != $monitor['Type'] ) $changes[] = "Type = '$new_type'";
		if ( $new_type == "Local" )
		{
			if ( $new_device != $monitor['Device'] ) $changes[] = "Device = '$new_device'";
			if ( $new_channel != $monitor['Channel'] ) $changes[] = "Channel = '$new_channel'";
			if ( $new_format != $monitor['Format'] ) $changes[] = "Format = '$new_format'";
		}
		else
		{
			if ( $new_host != $monitor['Device'] ) $changes[] = "Host = '$new_host'";
			if ( $new_port != $monitor['Channel'] ) $changes[] = "port = '$new_port'";
			if ( $new_path != $monitor['Format'] ) $changes[] = "Path = '$new_path'";
		}
		if ( $new_width != $monitor['Width'] ) $changes[] = "Width = '$new_width'";
		if ( $new_height != $monitor['Height'] ) $changes[] = "Height = '$new_height'";
		if ( $new_palette != $monitor['Palette'] ) $changes[] = "Palette = '$new_palette'";
		if ( $new_orientation != $monitor['Orientation'] ) $changes[] = "Orientation = '$new_orientation'";
		if ( $new_label_format != $monitor['LabelFormat'] ) $changes[] = "LabelFormat = '$new_label_format'";
		if ( $new_label_x != $monitor['LabelX'] ) $changes[] = "LabelX = '$new_label_x'";
		if ( $new_label_y != $monitor['LabelY'] ) $changes[] = "LabelY = '$new_label_y'";
		if ( $new_image_buffer_count != $monitor['ImageBufferCount'] ) $changes[] = "ImageBufferCount = '$new_image_buffer_count'";
		if ( $new_warmup_count != $monitor['WarmupCount'] ) $changes[] = "WarmupCount = '$new_warmup_count'";
		if ( $new_pre_event_count != $monitor['PreEventCount'] ) $changes[] = "PreEventCount = '$new_pre_event_count'";
		if ( $new_post_event_count != $monitor['PostEventCount'] ) $changes[] = "PostEventCount = '$new_post_event_count'";
		if ( $new_max_fps != $monitor['MaxFPS'] ) $changes[] = "MaxFPS = '$new_max_fps'";
		if ( $new_fps_report_interval != $monitor['FPSReportInterval'] ) $changes[] = "FPSReportInterval = '$new_fps_report_interval'";
		if ( $new_ref_blend_perc != $monitor['RefBlendPerc'] ) $changes[] = "RefBlendPerc = '$new_ref_blend_perc'";
		if ( $new_x10_activation != $monitor['X10Activation'] ) $changes[] = "X10Activation = '$new_x10_activation'";
		if ( $new_x10_alarm_input != $monitor['X10AlarmInput'] ) $changes[] = "X10AlarmInput = '$new_x10_alarm_input'";
		if ( $new_x10_alarm_output != $monitor['X10AlarmOutput'] ) $changes[] = "X10AlarmOutput = '$new_x10_alarm_output'";

		if ( count( $changes ) )
		{
			if ( $mid > 0 )
			{
				$sql = "update Monitors set ".implode( ", ", $changes )." where Id = '$mid'";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				if ( $new_name != $monitor[Name] )
				{
					exec( escapeshellcmd( "mv ".EVENTS_PATH."/$monitor[Name] ".EVENTS_PATH."/$new_name" ) );
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
			fixDevices();
			zmcControl( $monitor, true );
			zmaControl( $monitor, true );
			daemonControl( 'restart', 'zmwatch.pl' );
			$refresh_parent = true;
		}
	}
	elseif ( $action == "filter" )
	{
		if ( $filter_name || $new_filter_name )
		{
			if ( $new_filter_name )
				$filter_name = $new_filter_name;
			$filter_query = array();
			$filter_query[trms] = $trms;
			for ( $i = 1; $i <= $trms; $i++ )
			{
				$conjunction_name = "cnj$i";
				$obracket_name = "obr$i";
				$cbracket_name = "cbr$i";
				$attr_name = "attr$i";
				$op_name = "op$i";
				$value_name = "val$i";
				if ( $i > 1 )
				{
					$filter_query[$conjunction_name] = $$conjunction_name;
				}
				$filter_query[$obracket_name] = $$obracket_name;
				$filter_query[$cbracket_name] = $$cbracket_name;
				$filter_query[$attr_name] = $$attr_name;
				$filter_query[$op_name] = $$op_name;
				$filter_query[$value_name] = $$value_name;
			}
			$filter_parms = array();
			while( list( $key, $value ) = each( $filter_query ) )
			{
				$filter_parms[] = "$key=$value";
			}
			$filter_query_string = join( '&', $filter_parms );
			//$filter_query_string = serialize( $filter_query );
			$sql = "replace into Filters set MonitorId = '$mid', Name = '$filter_name', Query = '$filter_query_string', AutoArchive = '$auto_archive', AutoDelete = '$auto_delete', AutoUpload = '$auto_upload', AutoEmail = '$auto_email', AutoMessage = '$auto_message'";
			#echo "<html>$sql</html>";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
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
