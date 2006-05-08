<?php
//
// ZoneMinder web action file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

if ( !empty($action) )
{
	//phpinfo( INFO_VARIABLES );

	// General scope actions
	if ( $action == "login" && $username && ( ZM_AUTH_TYPE == "remote" || $password ) )
	{
		userLogin( $username, $password );
	}
	elseif ( $action == "logout" )
	{
		userLogout();
		$refresh_parent = true;
		$view = 'none';
	}
	elseif ( $action == "bandwidth" && $new_bandwidth )
	{
		$bandwidth = $new_bandwidth;
		setcookie( "bandwidth", $new_bandwidth, time()+3600*24*30*12*10 );
		$refresh_parent = true;
		$view = 'none';
	}

	// Event scope actions, view permissions only required
	if ( canView( 'Events' ) )
	{
		if ( $action == "addterm" )
		{
			for ( $i = $trms; $i > $subaction; $i-- )
			{
				$conjunction_name1 = "cnj".($i+1);
				$obracket_name1 = "obr".($i+1);
				$cbracket_name1 = "cbr".($i+1);
				$attr_name1 = "attr".($i+1);
				$op_name1 = "op".($i+1);
				$value_name1 = "val".($i+1);

				$conjunction_name2 = "cnj$i";
				$obracket_name2 = "obr$i";
				$cbracket_name2 = "cbr$i";
				$attr_name2 = "attr$i";
				$op_name2 = "op$i";
				$value_name2 = "val$i";

				$$conjunction_name1 = $$conjunction_name2;
				$$obracket_name1 = $$obracket_name2;
				$$cbracket_name1 = $$cbracket_name2;
				$$attr_name1 = $$attr_name2;
				$$op_name1 = $$op_name2;
				$$value_name1 = $$value_name2;
			}
			$$conjunction_name2 = false;
			$$obracket_name2 = false;
			$$cbracket_name2 = false;
			$$attr_name2 = false;
			$$op_name2 = false;
			$$value_name2 = false;

			$trms++;
		}
		elseif ( $action == "delterm" )
		{
			$trms--;
			for ( $i = $subaction; $i <= $trms; $i++ )
			{
				$conjunction_name1 = "cnj$i";
				$obracket_name1 = "obr$i";
				$cbracket_name1 = "cbr$i";
				$attr_name1 = "attr$i";
				$op_name1 = "op$i";
				$value_name1 = "val$i";

				$conjunction_name2 = "cnj".($i+1);
				$obracket_name2 = "obr".($i+1);
				$cbracket_name2 = "cbr".($i+1);
				$attr_name2 = "attr".($i+1);
				$op_name2 = "op".($i+1);
				$value_name2 = "val".($i+1);

				$$conjunction_name1 = $$conjunction_name2;
				$$obracket_name1 = $$obracket_name2;
				$$cbracket_name1 = $$cbracket_name2;
				$$attr_name1 = $$attr_name2;
				$$op_name1 = $$op_name2;
				$$value_name1 = $$value_name2;
			}
			$$conjunction_name2 = false;
			$$obracket_name2 = false;
			$$cbracket_name2 = false;
			$$attr_name2 = false;
			$$op_name2 = false;
			$$value_name2 = false;
		}
	}

	// Event scope actions, edit permissions required
	if ( canEdit( 'Events' ) )
	{
		if ( $action == "rename" && $event_name && $eid )
		{
			simpleQuery( "update Events set Name = '$event_name' where Id = '$eid'" );
		}
		else if ( $action == "eventdetail" )
		{
			if ( $eid )
			{
				simpleQuery( "update Events set Cause = '".addslashes($new_event['Cause'])."', Notes = '".addslashes($new_event['Notes'])."' where Id = '$eid'" );
				$refresh_parent = true;
			}
			elseif ( $mark_eids || $mark_eid )
			{
				if ( !$mark_eids && $mark_eid )
				{
					$mark_eids[] = $mark_eid;
				}
				if ( $mark_eids )
				{
					foreach( $mark_eids as $mark_eid )
					{
						simpleQuery( "update Events set Cause = '".addslashes($new_event['Cause'])."', Notes = '".addslashes($new_event['Notes'])."' where Id = '$mark_eid'" );
					}
					$refresh_parent = true;
				}
			}
		}
		elseif ( $action == "archive" || $action == "unarchive" )
		{
			$archive_val = ($action == "archive")?1:0;

			if ( $eid )
			{
				simpleQuery( "update Events set Archived = $archive_val where Id = '$eid'" );
			}
			elseif ( $mark_eids || $mark_eid )
			{
				if ( !$mark_eids && $mark_eid )
				{
					$mark_eids[] = $mark_eid;
				}
				if ( $mark_eids )
				{
					foreach( $mark_eids as $mark_eid )
					{
						simpleQuery( "update Events set Archived = $archive_val where Id = '$mark_eid'" );
					}
					$refresh_parent = true;
				}
			}
		}
		elseif ( $action == "filter" )
		{
			if ( $filter_name || $new_filter_name )
			{
				if ( $new_filter_name )
					$filter_name = $new_filter_name;
				$filter_query = array();
				$filter_query['trms'] = $trms;
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
				$filter_parms[] = "sort_field=$sort_field";
				$filter_parms[] = "sort_asc=$sort_asc";
				$filter_parms[] = "limit=$limit";
				$filter_query_string = join( '&', $filter_parms );
				simpleQuery( "replace into Filters set Name = '$filter_name', Query = '$filter_query_string', AutoArchive = '$auto_archive', AutoVideo = '$auto_video', AutoUpload = '$auto_upload', AutoEmail = '$auto_email', AutoMessage = '$auto_message', AutoExecute = '$auto_execute', AutoExecuteCmd = '".addslashes($auto_execute_cmd)."', AutoDelete = '$auto_delete'" );
				$refresh_parent = true;
			}
		}
		elseif ( $action == "delete" )
		{
			if ( !$mark_eids && $mark_eid )
			{
				$mark_eids[] = $mark_eid;
			}
			if ( $mark_eids )
			{
				foreach( $mark_eids as $mark_eid )
				{
					deleteEvent( $mark_eid );
				}
				$refresh_parent = true;
			}
			if ( $fid )
			{
				simpleQuery( "delete from Filters where Name = '$fid'" );
				//$refresh_parent = true;
			}
		}
	}

	// Monitor control actions, require a monitor id and control view permissions for that monitor
	if ( !empty($mid) && canView( 'Control', $mid ) )
	{
		if ( $action == "control" )
		{
			$result = mysql_query( "select * from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
			mysql_free_result( $result );

			$ctrl_command = $monitor['Command'];
			if ( !preg_match( '/^\//', $ctrl_command ) )
				$ctrl_command = ZM_PATH_BIN.'/'.$ctrl_command;
			if ( $monitor['ControlDevice'] )
				$ctrl_command .= " --device=".$monitor['ControlDevice'];
			if ( $monitor['ControlAddress'] )
				$ctrl_command .= " --address=".$monitor['ControlAddress'];

			//$ctrl_command .= " --command=".$control;

			if ( isset($x) && isset($y) )
			{
				if ( $control == "move_map" )
				{
					$x = deScale( $x, $scale );
					$y = deScale( $y, $scale );
					switch ( $monitor['Orientation'] )
					{
						case '0' :
						case '180' :
						case 'hori' :
						case 'vert' :
							$width = $monitor['Width'];
							$height = $monitor['Height'];
							break;
						case '90' :
						case '270' :
							$width = $monitor['Height'];
							$height = $monitor['Width'];
							break;
					}
					switch ( $monitor['Orientation'] )
					{
						case '90' :
							$temp_y = $y;
							$y = $height - $x;
							$x = $temp_y;
							break;
						case '180' :
							$x = $width - $x;
							$y = $height - $y;
							break;
						case '270' :
							$temp_x = $x;
							$x = $width - $y;
							$y = $temp_x;
							break;
						case 'hori' :
							$x = $width - $x;
							break;
						case 'vert' :
							$y = $height - $y;
							break;
					}
					$ctrl_command .= " -xcoord $x -ycoord $y -width $width -height $height";
				}
				elseif ( $control == "move_pseudo_map" )
				{
					$x = deScale( $x, $scale );
					$y = deScale( $y, $scale );
					
					$half_width = $monitor['Width'] / 2;
					$half_height = $monitor['Height'] / 2;
					$x_factor = ($x - $half_width)/$half_width;
					$y_factor = ($y - $half_height)/$half_height;

					switch ( $monitor['Orientation'] )
					{
						case '90' :
							$temp_y_factor = $y;
							$y_factor = -$x_factor;
							$x_factor = $temp_y_factor;
							break;
						case '180' :
							$x_factor = -$x_factor;
							$y_factor = -$y_factor;
							break;
						case '270' :
							$temp_x_factor = $x;
							$x_factor = -$y_factor;
							$y_factor = $tenp_x_factor;
							break;
						case 'hori' :
							$x_factor = -$x_factor;
							break;
						case 'vert' :
							$y_factor = -$y_factor;
							break;
					}

					$turbo = 0.9; // Threshold for turbo speed
					$blind = 0.1; // Threshold for blind spot

					$pan_control = '';
					$tilt_control = '';
					if ( $x_factor > $blind )
					{
						$pan_control = 'right';
					}
					elseif ( $x_factor < -$blind )
					{
						$pan_control = 'left';
					}
					if ( $y_factor > $blind )
					{
						$tilt_control = 'down';
					}
					elseif ( $y_factor < -$blind )
					{
						$tilt_control = 'up';
					}

					$dirn = $tilt_control.$pan_control;
					if ( !$dirn )
					{
						// No command, probably in blind spot in middle
						$control = 'null';
					}
					else
					{
						$control = 'move_rel_'.$dirn;
						$x_factor = abs($x_factor);
						$y_factor = abs($y_factor);

						if ( $monitor['HasPanSpeed'] && $x_factor )
						{
							if ( $monitor['HasTurboPan'] )
							{
								if ( $x_factor >= $turbo )
								{
									$pan_speed = $monitor['TurboPanSpeed'];
								}
								else
								{
									$x_factor = $x_factor/$turbo;
									$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
								}
							}
							else
							{
								$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
							}
						}
						if ( $monitor['HasTiltSpeed'] && $y_factor )
						{
							if ( $monitor['HasTurboTilt'] )
							{
								if ( $y_factor >= $turbo )
								{
									$tilt_speed = $monitor['TurboTiltSpeed'];
								}
								else
								{
									$y_factor = $y_factor/$turbo;
									$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
								}
							}
							else
							{
								$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
							}
						}
						if ( preg_match( '/(left|right)$/', $dirn ) )
						{
							$pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
							$ctrl_command .= " --panstep=".$pan_step." --panspeed=".$pan_speed;
						}
						if ( preg_match( '/^(up|down)/', $dirn ) )
						{
							$tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
							$ctrl_command .= " --tiltstep=".$tilt_step." --tiltspeed=".$tilt_speed;
						}
					}
				}
				elseif ( $control == "move_con_map" )
				{
					$x = deScale( $x, $scale );
					$y = deScale( $y, $scale );
					
					$half_width = $monitor['Width'] / 2;
					$half_height = $monitor['Height'] / 2;
					$x_factor = ($x - $half_width)/$half_width;
					$y_factor = ($y - $half_height)/$half_height;

					switch ( $monitor['Orientation'] )
					{
						case '90' :
							$temp_y_factor = $y;
							$y_factor = -$x_factor;
							$x_factor = $temp_y_factor;
							break;
						case '180' :
							$x_factor = -$x_factor;
							$y_factor = -$y_factor;
							break;
						case '270' :
							$temp_x_factor = $x;
							$x_factor = -$y_factor;
							$y_factor = $tenp_x_factor;
							break;
						case 'hori' :
							$x_factor = -$x_factor;
							break;
						case 'vert' :
							$y_factor = -$y_factor;
							break;
					}

					$slow = 0.9; // Threshold for slow speed/timeouts
					$turbo = 0.9; // Threshold for turbo speed
					$blind = 0.1; // Threshold for blind spot

					$pan_control = '';
					$tilt_control = '';
					if ( $x_factor > $blind )
					{
						$pan_control = 'right';
					}
					elseif ( $x_factor < -$blind )
					{
						$pan_control = 'left';
					}
					if ( $y_factor > $blind )
					{
						$tilt_control = 'down';
					}
					elseif ( $y_factor < -$blind )
					{
						$tilt_control = 'up';
					}

					$dirn = $tilt_control.$pan_control;
					if ( !$dirn )
					{
						// No command, probably in blind spot in middle
						$control = 'move_stop';
					}
					else
					{
						$control = 'move_con_'.$dirn;
						$x_factor = abs($x_factor);
						$y_factor = abs($y_factor);

						if ( $monitor['HasPanSpeed'] && $x_factor )
						{
							if ( $monitor['HasTurboPan'] )
							{
								if ( $x_factor >= $turbo )
								{
									$pan_speed = $monitor['TurboPanSpeed'];
								}
								else
								{
									$x_factor = $x_factor/$turbo;
									$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
								}
							}
							else
							{
								$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
							}
						}
						if ( $monitor['HasTiltSpeed'] && $y_factor )
						{
							if ( $monitor['HasTurboTilt'] )
							{
								if ( $y_factor >= $turbo )
								{
									$tilt_speed = $monitor['TurboTiltSpeed'];
								}
								else
								{
									$y_factor = $y_factor/$turbo;
									$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
								}
							}
							else
							{
								$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
							}
						}
						if ( preg_match( '/(left|right)$/', $dirn ) )
						{
							$ctrl_command .= " --panspeed=".$pan_speed;
						}
						if ( preg_match( '/^(up|down)/', $dirn ) )
						{
							$ctrl_command .= " --tiltspeed=".$tilt_speed;
						}
						if ( $monitor['AutoStopTimeout'] )
						{
							$slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
							$slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
							if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
							{
								$ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
							}
						}
					}
				}
				else
				{
					$slow = 0.9; // Threshold for slow speed/timeouts
					$turbo = 0.9; // Threshold for turbo speed
					$long_y = 48;
					$short_x = 32;
					$short_y = 32;

					if ( preg_match( '/^([^_]+)_([^_]+)_([^_]+)$/', $control, $matches ) )
					{
						$command = $matches[1];
						$mode = $matches[2];
						$dirn = $matches[3];

						switch( $command )
						{
							case 'focus' :
							{
								switch( $dirn )
								{
									case 'near' :
									{
										$factor = ($long_y-($y+1))/$long_y;
										break;
									}
									case 'far' :
									{
										$factor = ($y+1)/$long_y;
										break;
									}
								}
								if ( $monitor['HasFocusSpeed'] )
								{
									$speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$factor)));
									$ctrl_command .= " --speed=".$speed;
								}
								switch( $mode )
								{
									case 'abs' :
									case 'rel' :
									{
										$step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
										$ctrl_command .= " --step=".$step;
										break;
									}
									case 'con' :
									{
										if ( $monitor['AutoStopTimeout'] )
										{
											$slow_speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
											if ( $speed < $slow_speed )
											{
												$ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
											}
										}
										break;
									}
								}
								break;
							}
							case 'zoom' :
							{
								switch( $dirn )
								{
									case 'tele' :
									{
										$factor = ($long_y-($y+1))/$long_y;
										break;
									}
									case 'wide' :
									{
										$factor = ($y+1)/$long_y;
										break;
									}
								}
								if ( $monitor['HasZoomSpeed'] )
								{
									$speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$factor)));
									$ctrl_command .= " --speed=".$speed;
								}
								switch( $mode )
								{
									case 'abs' :
									case 'rel' :
									{
										$step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
										$ctrl_command .= " --step=".$step;
										break;
									}
									case 'con' :
									{
										if ( $monitor['AutoStopTimeout'] )
										{
											$slow_speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
											if ( $speed < $slow_speed )
											{
												$ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
											}
										}
										break;
									}
								}
								break;
							}
							case 'iris' :
							{
								switch( $dirn )
								{
									case 'open' :
									{
										$factor = ($long_y-($y+1))/$long_y;
										break;
									}
									case 'close' :
									{
										$factor = ($y+1)/$long_y;
										break;
									}
								}
								if ( $monitor['HasIrisSpeed'] )
								{
									$speed = intval(round($monitor['MinIrisSpeed']+(($monitor['MaxIrisSpeed']-$monitor['MinIrisSpeed'])*$factor)));
									$ctrl_command .= " --speed=".$speed;
								}
								switch( $mode )
								{
									case 'abs' :
									case 'rel' :
									{
										$step = intval(round($monitor['MinIrisStep']+(($monitor['MaxIrisStep']-$monitor['MinIrisStep'])*$factor)));
										$ctrl_command .= " --step=".$step;
										break;
									}
								}
								break;
							}
							case 'white' :
							{
								switch( $dirn )
								{
									case 'in' :
									{
										$factor = ($long_y-($y+1))/$long_y;
										break;
									}
									case 'out' :
									{
										$factor = ($y+1)/$long_y;
										break;
									}
								}
								if ( $monitor['HasWhiteSpeed'] )
								{
									$speed = intval(round($monitor['MinWhiteSpeed']+(($monitor['MaxWhiteSpeed']-$monitor['MinWhiteSpeed'])*$factor)));
									$ctrl_command .= " --speed=".$speed;
								}
								switch( $mode )
								{
									case 'abs' :
									case 'rel' :
									{
										$step = intval(round($monitor['MinWhiteStep']+(($monitor['MaxWhiteStep']-$monitor['MinWhiteStep'])*$factor)));
										$ctrl_command .= " --step=".$step;
										break;
									}
								}
								break;
							}
							case 'gain' :
							{
								switch( $dirn )
								{
									case 'up' :
									{
										$factor = ($long_y-($y+1))/$long_y;
										break;
									}
									case 'down' :
									{
										$factor = ($y+1)/$long_y;
										break;
									}
								}
								if ( $monitor['HasGainSpeed'] )
								{
									$speed = intval(round($monitor['MinGainSpeed']+(($monitor['MaxGainSpeed']-$monitor['MinGainSpeed'])*$factor)));
									$ctrl_command .= " --speed=".$speed;
								}
								switch( $mode )
								{
									case 'abs' :
									case 'rel' :
									{
										$step = intval(round($monitor['MinGainStep']+(($monitor['MaxGainStep']-$monitor['MinGainStep'])*$factor)));
										$ctrl_command .= " --step=".$step;
										break;
									}
								}
								break;
							}
							case 'move' :
							{
								$x_factor = 0;
								$y_factor = 0;

								if ( preg_match( '/^up/', $dirn ) )
								{
									$y_factor = ($short_y-($y+1))/$short_y;
								}
								elseif ( preg_match( '/^down/', $dirn ) )
								{
									$y_factor = ($y+1)/$short_y;
								}
								if ( preg_match( '/left$/', $dirn ) )
								{
									$x_factor = ($short_x-($x+1))/$short_x;
								}
								elseif ( preg_match( '/right$/', $dirn ) )
								{
									$x_factor = ($x+1)/$short_x;
								}

								if ( $monitor['Orientation'] != '0' )
								{
									$conversions = array(
										'90' => array(
											'up' => 'left',
											'down' => 'right',
											'left' => 'down',
											'right' => 'up',
											'upleft' => 'downleft',
											'upright' => 'upleft',
											'downleft' => 'downright',
											'downright' => 'upright',
										),
										'180' => array(
											'up' => 'down',
											'down' => 'up',
											'left' => 'right',
											'right' => 'left',
											'upleft' => 'downright',
											'upright' => 'downleft',
											'downleft' => 'upright',
											'downright' => 'upleft',
										),
										'270' => array(
											'up' => 'right',
											'down' => 'left',
											'left' => 'up',
											'right' => 'down',
											'upleft' => 'upright',
											'upright' => 'downright',
											'downleft' => 'upleft',
											'downright' => 'downleft',
										),
										'hori' => array(
											'up' => 'up',
											'down' => 'down',
											'left' => 'right',
											'right' => 'left',
											'upleft' => 'upright',
											'upright' => 'upleft',
											'downleft' => 'downright',
											'downright' => 'downleft',
										),
										'vert' => array(
											'up' => 'down',
											'down' => 'up',
											'left' => 'left',
											'right' => 'right',
											'upleft' => 'downleft',
											'upright' => 'downright',
											'downleft' => 'upleft',
											'downright' => 'upright',
										),
									);
									$new_dirn = $conversions[$monitor['Orientation']][$dirn];
									$control = preg_replace( "/_$dirn\$/", "_$new_dirn", $control );
									$dirn = $new_dirn;
								}

								if ( $monitor['HasPanSpeed'] && $x_factor )
								{
									if ( $monitor['HasTurboPan'] )
									{
										if ( $x_factor >= $turbo )
										{
											$pan_speed = $monitor['TurboPanSpeed'];
										}
										else
										{
											$x_factor = $x_factor/$turbo;
											$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
										}
									}
									else
									{
										$pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
									}
									$ctrl_command .= " --panspeed=".$pan_speed;
								}
								if ( $monitor['HasTiltSpeed'] && $y_factor )
								{
									if ( $monitor['HasTurboTilt'] )
									{
										if ( $y_factor >= $turbo )
										{
											$tilt_speed = $monitor['TurboTiltSpeed'];
										}
										else
										{
											$y_factor = $y_factor/$turbo;
											$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
										}
									}
									else
									{
										$tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
									}
									$ctrl_command .= " --tiltspeed=".$tilt_speed;
								}
								switch( $mode )
								{
									case 'rel' :
									case 'abs' :
									{
										if ( preg_match( '/(left|right)$/', $dirn ) )
										{
											$pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
											$ctrl_command .= " --panstep=".$pan_step;
										}
										if ( preg_match( '/^(up|down)/', $dirn ) )
										{
											$tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
											$ctrl_command .= " --tiltstep=".$tilt_step;
										}
										break;
									}
									case 'con' :
									{
										if ( $monitor['AutoStopTimeout'] )
										{
											$slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
											$slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
											if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
											{
												$ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
											}
										}
										break;
									}
								}
							}
						}
					}
				}
			}
			else
			{
				if ( preg_match( '/^preset_goto_(\d+)$/', $control, $matches ) )
				{
					$control = 'preset_goto';
					$ctrl_command .= " --preset ".$matches[1];
				}
				elseif ( $control == "preset_set" )
				{
					$ctrl_command .= " --preset ".$preset;
					$view = 'none';
				}
				elseif ( $control == "move_map" )
				{
					$ctrl_command .= " -xcoord $x -ycoord $y";
				}
			}
			if ( $control != 'null' )
			{
				//if ( $monitor['Function'] == 'Modect' || $monitor['Function'] == 'Mocord' )
				//{
					//$zmu_command = getZmuCommand( " -m $mid -r" );
					//$zmu_output = exec( escapeshellcmd( $zmu_command ) );
				//}
				$ctrl_command .= " --command=".$control;
				//echo $ctrl_command;
				$ctrl_output = exec( escapeshellcmd( $ctrl_command ) );
				//echo $ctrl_output;
			}
		}
		elseif ( $action == "settings" )
		{
			$zmu_command = getZmuCommand( " -m $mid -B$new_brightness -C$new_contrast -H$new_hue -O$new_colour" );
			$zmu_output = exec( escapeshellcmd( $zmu_command ) );
			list( $brightness, $contrast, $hue, $colour ) = split( ' ', $zmu_output );
			$sql = "update Monitors set Brightness = '$brightness', Contrast = '$contrast', Hue = '$hue', Colour = '$colour' where Id = '$mid'";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
		}
	}

	// Control capability actions, require control edit permissions
	if ( canEdit( 'Control' ) )
	{
		if ( $action == "controlcap" )
		{
			if ( !empty($cid) )
			{
				$result = mysql_query( "select * from Controls where Id = '$cid'" );
				if ( !$result )
					die( mysql_error() );
				$control = mysql_fetch_assoc( $result );
				mysql_free_result( $result );
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
			$changes = getFormChanges( $control, $new_control, $types, $columns );

			if ( count( $changes ) )
			{
				if ( !empty($cid) )
				{
					simpleQuery( "update Controls set ".implode( ", ", $changes )." where Id = '$cid'" );
					$refresh_parent = true;
				}
				else
				{
					$sql = "insert into Controls set ".implode( ", ", $changes );
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					$cid = mysql_insert_id();
				}
				$refresh_parent = true;
			}
		}
		elseif ( $action == "delete" )
		{
			if ( $mark_cids )
			{
				foreach( $mark_cids as $mark_cid )
				{
					simpleQuery( "delete from Controls where Id = '$mark_cid'" );
					simpleQuery( "update Monitors set Controllable = 0, ControlId = 0 where ControlId = '$mark_cid'" );
					$refresh_parent = true;
				}
			}
		}
	}

	// Monitor edit actions, require a monitor id and edit permissions for that monitor
	if ( !empty($mid) && canEdit( 'Monitors', $mid ) )
	{
		if ( $action == "function" )
		{
			$sql = "select * from Monitors where Id = '$mid'";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
			mysql_free_result( $result );

			$old_function = $monitor['Function'];
			$old_enabled = $monitor['Enabled'];
			if ( $new_function != $old_function || $new_enabled != $old_enabled )
			{
				simpleQuery( "update Monitors set Function = '$new_function', Enabled = '$new_enabled' where Id = '$mid'" );

				$monitor['Function'] = $new_function;
				$monitor['Enabled'] = $new_enabled;
				if ( $cookies ) session_write_close();
				if ( daemonCheck() )
				{
					zmcControl( $monitor );
					zmaControl( $monitor, "reload" );
				}
				$refresh_parent = true;
			}
		}
		elseif ( $action == "zone" && isset( $zid ) )
		{
			$result = mysql_query( "select * from Monitors where Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
			mysql_free_result( $result );

			if ( $zid > 0 )
			{
				$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
				if ( !$result )
					die( mysql_error() );
				$zone = mysql_fetch_assoc( $result );
				mysql_free_result( $result );
			}
			else
			{
				$zone = array();
			}
			if ( false && $points )
			{
				$zone['NumCoords'] = count($points);
				$zone['Coords'] = pointsToCoords( $points );
				$zone['Area'] = getPolyArea( $points );
			}

			if ( $new_zone['Units'] == 'Percent' )
			{
				$new_zone['MinAlarmPixels'] = intval(($new_zone['MinAlarmPixels']*$new_zone['Area'])/100);
				$new_zone['MaxAlarmPixels'] = intval(($new_zone['MaxAlarmPixels']*$new_zone['Area'])/100);
				$new_zone['MinFilterPixels'] = intval(($new_zone['MinFilterPixels']*$new_zone['Area'])/100);
				$new_zone['MaxFilterPixels'] = intval(($new_zone['MaxFilterPixels']*$new_zone['Area'])/100);
				$new_zone['MinBlobPixels'] = intval(($new_zone['MinBlobPixels']*$new_zone['Area'])/100);
				$new_zone['MaxBlobPixels'] = intval(($new_zone['MaxBlobPixels']*$new_zone['Area'])/100);
			}

			unset( $new_zone['Points'] );
			$types = array();
			$changes = getFormChanges( $zone, $new_zone, $types );

			if ( count( $changes ) )
			{
				if ( $zid > 0 )
				{
					$sql = "update Zones set ".implode( ", ", $changes )." where MonitorId = '$mid' and Id = '$zid'";
				}
				else
				{
					$sql = "insert into Zones set MonitorId = '$mid', ".implode( ", ", $changes );
				}
				//echo "<html>$sql</html>";
				simpleQuery( $sql );
				if ( $cookies ) session_write_close();
				if ( daemonCheck() )
				{
					zmaControl( $mid, "restart" );
				}
				$refresh_parent = true;
			}
			$view = 'none';
		}
		elseif ( $action == "sequence" && isset($smid) )
		{
			$result = mysql_query( "select * from Monitors where Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
			mysql_free_result( $result );
			$result = mysql_query( "select * from Monitors where Id = '$smid'" );
			if ( !$result )
				die( mysql_error() );
			$smonitor = mysql_fetch_assoc( $result );
			mysql_free_result( $result );

			$sql = "update Monitors set Sequence = '".$smonitor['Sequence']."' where Id = '".$monitor['Id']."'";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
			$sql = "update Monitors set Sequence = '".$monitor['Sequence']."' where Id = '".$smonitor['Id']."'";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );

			$refresh_parent = true;
			fixSequences();
		}
		if ( $action == "delete" )
		{
			if ( $mark_zids )
			{
				$deleted_zid = 0;
				foreach( $mark_zids as $mark_zid )
				{
					$result = mysql_query( "delete from Zones where MonitorId = '$mid' && Id = '$mark_zid'" );
					if ( !$result )
						die( mysql_error() );
					$deleted_zid = 1;
				}
				if ( $deleted_zid )
				{
					if ( $cookies ) session_write_close();
					if ( daemonCheck() )
					{
						zmaControl( $mid, "restart" );
					}
					$refresh_parent = true;
				}
			}
		}
	}

	// Monitor edit actions, monitor id derived, require edit permissions for that monitor
	if ( canEdit( 'Monitors' ) )
	{
		if ( $action == "monitor" )
		{
			if ( !empty($mid) )
			{
				$result = mysql_query( "select * from Monitors where Id = '$mid'" );
				if ( !$result )
					die( mysql_error() );
				$monitor = mysql_fetch_assoc( $result );
				mysql_free_result( $result );

				if ( ZM_OPT_X10 )
				{
					$result = mysql_query( "select * from TriggersX10 where MonitorId = '$mid'" );
					if ( !$result )
						die( mysql_error() );
					if ( !($x10_monitor = mysql_fetch_assoc( $result )) )
					{
						$x10_monitor = array();
					}
					mysql_free_result( $result );
				}
			}
			else
			{
				$monitor = array();
				if ( ZM_OPT_X10 )
				{
					$x10_monitor = array();
				}
			}

			// Define a field type for anything that's not simple text equivalent
			$types = array(
				'Triggers' => 'set',
				'Controllable' => 'toggle',
				'TrackMotion' => 'toggle',
			);

			$columns = getTableColumns( 'Monitors' );
			$changes = getFormChanges( $monitor, $new_monitor, $types, $columns );

			if ( count( $changes ) )
			{
				if ( !empty($mid) )
				{
					simpleQuery( "update Monitors set ".implode( ", ", $changes )." where Id = '$mid'" );
					if ( $changes['Name'] )
					{
						exec( escapeshellcmd( "mv ".ZM_DIR_EVENTS."/".$monitor['Name']." ".ZM_DIR_EVENTS."/".$new_monitor['Name'] ) );
					}
					if ( $changes['Width'] || $changes['Height'] )
					{
						$new_w = $new_monitor['Width'];
						$new_h = $new_monitor['Height'];
						$new_a = $new_w * $new_h;
						$old_w = $monitor['Width'];
						$old_h = $monitor['Height'];
						$old_a = $old_w * $old_h;

						$result = mysql_query( "select * from Zones where MonitorId = '$mid'" );
						if ( !$result )
							die( mysql_error() );
						$zones = array();
						while ( $zone = mysql_fetch_assoc( $result ) )
						{
							$zones[] = $zone;
						}
						mysql_free_result( $result );
						foreach ( $zones as $zone )
						{
							$new_zone = $zone;
							$points = coordsToPoints( $zone['Coords'] );
							for ( $i = 0; $i < count($points); $i++ )
							{
                                $points[$i]['x'] = intval(($points[$i]['x']*($new_w-1))/($old_w-1));
                                $points[$i]['y'] = intval(($points[$i]['y']*($new_h-1))/($old_h-1));
							}
							$new_zone['Coords'] = pointsToCoords( $points );
							$new_zone['Area'] = intval(round(($zone['Area']*$new_a)/$old_a));
							$new_zone['MinAlarmPixels'] = intval(round(($new_zone['MinAlarmPixels']*$new_a)/$old_a));
							$new_zone['MaxAlarmPixels'] = intval(round(($new_zone['MaxAlarmPixels']*$new_a)/$old_a));
							$new_zone['MinFilterPixels'] = intval(round(($new_zone['MinFilterPixels']*$new_a)/$old_a));
							$new_zone['MaxFilterPixels'] = intval(round(($new_zone['MaxFilterPixels']*$new_a)/$old_a));
							$new_zone['MinBlobPixels'] = intval(round(($new_zone['MinBlobPixels']*$new_a)/$old_a));
							$new_zone['MaxBlobPixels'] = intval(round(($new_zone['MaxBlobPixels']*$new_a)/$old_a));

							$changes = getFormChanges( $zone, $new_zone, $types );

							if ( count( $changes ) )
							{
								$sql = "update Zones set ".implode( ", ", $changes )." where MonitorId = '$mid' and Id = '".$zone['Id']."'";
								//echo "<html>$sql</html>";
								simpleQuery( $sql );
							}
						}
					}
				}
				elseif ( !$user['MonitorIds'] )
				{
					$sql = "select max(Sequence) as MaxSequence from Monitors";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					$row = mysql_fetch_assoc( $result );
					mysql_free_result( $result );
					$changes[] = "Sequence = ".($row['MaxSequence']+1);

					$sql = "insert into Monitors set ".implode( ", ", $changes );
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					$mid = mysql_insert_id();
					$zone_area = $new_monitor['Width'] * $new_monitor['Height'];
					$sql = "insert into Zones set MonitorId = $mid, Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = '".sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $new_monitor['Width']-1, 0, $new_monitor['Width']-1, $new_monitor['Height']-1, 0, $new_monitor['Height']-1 )."', Area = ".$zone_area.", AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels = ".intval(($zone_area*3)/100).", MaxAlarmPixels = ".intval(($zone_area*75)/100).", FilterX = 3, FilterY = 3, MinFilterPixels = ".intval(($zone_area*3)/100).", MaxFilterPixels = ".intval(($zone_area*75)/100).", MinBlobPixels = ".intval(($zone_area*2)/100).", MinBlobs = 1";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					//$view = 'none';
					mkdir( ZM_DIR_EVENTS."/".$mid, 0755 );
					chdir( ZM_DIR_EVENTS );
					symlink( $mid, $new_monitor['Name'] );
					chdir( ".." );
				}
				$restart = true;
			}

			if ( ZM_OPT_X10 )
			{
				$x10_changes = getFormChanges( $x10_monitor, $new_x10_monitor );

				if ( count( $x10_changes ) )
				{
					if ( $x10_monitor && $new_x10_monitor )
					{
						$sql = "update TriggersX10 set ".implode( ", ", $x10_changes )." where MonitorId = '$mid'";
						$result = mysql_query( $sql );
						if ( !$result )
							die( mysql_error() );
					}
					elseif ( !$user['MonitorIds'] )
					{
						if ( !$x10_monitor )
						{
							$sql = "insert into TriggersX10 set MonitorId = '$mid', ".implode( ", ", $x10_changes );
							$result = mysql_query( $sql );
							if ( !$result )
								die( mysql_error() );
						}
						else
						{
							$sql = "delete from TriggersX10 where MonitorId = '$mid'";
							$result = mysql_query( $sql );
							if ( !$result )
								die( mysql_error() );
						}
					}
					$restart = true;
				}
			}

			if ( $restart )
			{
				$result = mysql_query( "select * from Monitors where Id = '$mid'" );
				if ( !$result )
					die( mysql_error() );
				$monitor = mysql_fetch_assoc( $result );
				mysql_free_result( $result );
				fixDevices();
				if ( $cookies ) session_write_close();
				if ( daemonCheck() )
				{
					zmcControl( $monitor, "restart" );
					zmaControl( $monitor, "restart" );
				}
				//daemonControl( 'restart', 'zmwatch.pl' );
				$refresh_parent = true;
			}
			$view = 'none';
		}
		if ( $action == "delete" )
		{
			if ( $mark_mids && !$user['MonitorIds'] )
			{
				foreach( $mark_mids as $mark_mid )
				{
					if ( canEdit( 'Monitors', $mark_mid ) )
					{
						zmaControl( $monitor, "stop" );
						zmcControl( $monitor, "stop" );

						$sql = "select * from Monitors where Id = '$mark_mid'";
						$result = mysql_query( $sql );
						if ( !$result )
							die( mysql_error() );
						if ( !($monitor = mysql_fetch_assoc( $result )) )
						{
							continue;
						}
						mysql_free_result( $result );

						$sql = "select Id from Events where MonitorId = '$mark_mid'";
						$result = mysql_query( $sql );
						if ( !$result )
							die( mysql_error() );

						$mark_eids = array();
						while( $row = mysql_fetch_assoc( $result ) )
						{
							$mark_eids[] = $row['Id'];
						}
						mysql_free_result( $result );
						foreach( $mark_eids as $mark_eid )
						{
							deleteEvent( $mark_eid );
						}
						unlink( ZM_DIR_EVENTS."/".$monitor['Name'] );
						system( "rm -rf ".ZM_DIR_EVENTS."/".$monitor['Id'] );

						$result = mysql_query( "delete from Zones where MonitorId = '$mark_mid'" );
						if ( !$result )
							die( mysql_error() );
						if ( ZM_OPT_X10 )
						{
							$result = mysql_query( "delete from TriggersX10 where MonitorId = '$mark_mid'" );
							if ( !$result )
								die( mysql_error() );
						}
						$result = mysql_query( "delete from Monitors where Id = '$mark_mid'" );
						if ( !$result )
							die( mysql_error() );

						fixSequences();
					}
				}
			}
		}
	}

	// System view actions
	if ( canView( 'System' ) )
	{
		if ( $action == "cgroup" )
		{
			if ( !empty($gid) )
			{
				setcookie( "cgroup", $gid, time()+3600*24*30*12*10 );
			}
			else
			{
				setcookie( "cgroup", "", time()-3600*24*2 );
			}
			$refresh_parent = true;
		}
	}

	// System edit actions
	if ( canEdit( 'System' ) )
	{
		if ( $action == "version" && isset($option) )
		{
			switch( $option )
			{
				case 'go' :
				{
					// Ignore this, the caller will open the page itself
					break;
				}
				case 'ignore' :
				{
					$sql = "update Config set Value = '".ZM_DYN_LAST_VERSION."' where Name = 'ZM_DYN_CURR_VERSION'";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					break;
				}
				case 'hour' :
				case 'day' :
				case 'week' :
				{
					$next_reminder = time();
					if ( $option == 'hour' )
					{
						$next_reminder += 60*60;
					}
					elseif ( $option == 'day' )
					{
						$next_reminder += 24*60*60;
					}
					elseif ( $option == 'week' )
					{
						$next_reminder += 7*24*60*60;
					}
					$sql = "update Config set Value = '".$next_reminder."' where Name = 'ZM_DYN_NEXT_REMINDER'";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					break;
				}
				case 'never' :
				{
					$sql = "update Config set Value = '0' where Name = 'ZM_CHECK_FOR_UPDATES'";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					break;
				}
			}
		}
		if ( $action == "donate" && isset($option) )
		{
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
					$next_reminder = time();
					if ( $option == 'hour' )
					{
						$next_reminder += 60*60;
					}
					elseif ( $option == 'day' )
					{
						$next_reminder += 24*60*60;
					}
					elseif ( $option == 'week' )
					{
						$next_reminder += 7*24*60*60;
					}
					elseif ( $option == 'month' )
					{
						$next_reminder += 30*24*60*60;
					}
					$sql = "update Config set Value = '".$next_reminder."' where Name = 'ZM_DYN_DONATE_REMINDER_TIME'";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					break;
				}
				case 'never' :
				case 'already' :
				{
					$sql = "update Config set Value = '0' where Name = 'ZM_DYN_SHOW_DONATE_REMINDER'";
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					break;
				}
			}
		}
		if ( $action == "options" && isset( $tab ) )
		{
			$config_cat = $config_cats[$tab];
			$changed = false;
			foreach ( $config_cat as $name=>$value )
			{
				if ( $value['Type'] == "boolean" && !$new_config[$name] )
				{
					 $new_config[$name] = 0;
				}
				else
				{
					 $new_config[$name] = preg_replace( "/\r\n/", "\n", stripslashes( $new_config[$name] ) );
				}
				if ( $value['Value'] != $new_config[$name] )
				{
					$sql = "update Config set Value = '".$new_config[$name]."' where Name = '".$name."'";
					//echo $sql;
					$result = mysql_query( $sql );
					if ( !$result )
						die( mysql_error() );
					$changed = true;
				}
			}
			if ( $changed )
			{
				switch( $tab )
				{
					case "system" :
					case "config" :
					case "paths" :
						$restart = true;
						break;
					case "web" :
					case "tools" :
						break;
					case "debug" :
					case "network" :
					case "mail" :
					case "ftp" :
						$restart = true;
						break;
					case "highband" :
					case "medband" :
					case "lowband" :
					case "phoneband" :
						break;
				}
			}
			loadConfig();
		}
		elseif ( $action == "user" )
		{
			if ( !empty($uid) )
			{
				$result = mysql_query( "select * from Users where Id = '$uid'" );
				if ( !$result )
					die( mysql_error() );
				$db_user = mysql_fetch_assoc( $result );
				mysql_free_result( $result );
			}
			else
			{
				$zone = array();
			}

			$types = array();
			$changes = getFormChanges( $db_user, $new_user, $types );

			if ( $new_user['Password'] )
				$changes['Password'] = "Password = password('".$new_user['Password']."')";
			else
				unset( $changes['Password'] );
			if ( count( $changes ) )
			{
				if ( $uid > 0 )
				{
					$sql = "update Users set ".implode( ", ", $changes )." where Id = '$uid'";
				}
				else
				{
					$sql = "insert into Users set ".implode( ", ", $changes );
				}
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );
				$refresh_parent = true;
				if ( $db_user['Username'] == $user['Username'] )
				{
					userLogin( $db_user['Username'], $db_user['Password'] );
				}
			}
			$view = 'none';
		}
		elseif ( $action == "state" )
		{
			if ( $run_state )
			{
				if ( $cookies ) session_write_close();
				packageControl( $run_state );
				$refresh_parent = true;
			}
		}
		elseif ( $action == "save" )
		{
			if ( $run_state || $new_state )
			{
				$sql = "select Id,Function,Enabled from Monitors order by Id";
				$result = mysql_query( $sql );
				if ( !$result )
					die( mysql_error() );

				$definitions = array();
				while( $monitor = mysql_fetch_assoc( $result ) )
				{
					$definitions[] = $monitor['Id'].":".$monitor['Function'].":".$monitor['Enabled'];
				}
				mysql_free_result( $result );
				$definition = join( ',', $definitions );
				if ( $new_state )
					$run_state = $new_state;
				simpleQuery( "replace into States set Name = '$run_state', Definition = '$definition'" );
			}
		}
		elseif ( $action == "group" )
		{
			if ( $gid )
			{
				simpleQuery( "update Groups set Name = '".addslashes($new_group['Name'])."', MonitorIds = '".addslashes($new_group['MonitorIds'])."' where Id = '$gid'" );
			}
			else
			{
				simpleQuery( "insert into Groups set Name = '".addslashes($new_group['Name'])."', MonitorIds = '".addslashes($new_group['MonitorIds'])."'" );
			}
			$refresh_parent = true;
			$view = 'none';
		}
		elseif ( $action == "delete" )
		{
			if ( $run_state )
			{
				simpleQuery( "delete from States where Name = '$run_state'" );
			}
			if ( $mark_uids )
			{
				foreach( $mark_uids as $mark_uid )
				{
					simpleQuery( "delete from Users where Id = '$mark_uid'" );
				}
				if ( $mark_uid == $user['Id'] )
				{
					userLogout();
				}
			}
			if ( !empty($gid) )
			{
				simpleQuery( "delete from Groups where Id = '$gid'" );
				if ( $gid == $cgroup )
				{
					unset( $cgroup );
					setcookie( "cgroup", "", time()-3600*24*2 );
					$refresh_parent = true;
				}
			}
		}
	}
	if ( $action == "learn" )
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
	elseif ( $action == "reset" )
	{
		$_SESSION['event_reset_time'] = strftime( STRF_FMT_DATETIME_DB );
		setcookie( "event_reset_time", $_SESSION['event_reset_time'], time()+3600*24*30*12*10 );
		if ( $cookies ) session_write_close();
	}
}

?>
