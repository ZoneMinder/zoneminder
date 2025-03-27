<?php
//
// ZoneMinder HTML interface file, $Date$, $Revision$
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

if ( empty($_COOKIE['zmBandwidth']) )
  $_COOKIE['zmBandwidth'] = ZM_BANDWIDTH_DEFAULT;
if ( empty($_COOKIE['zmBandwidth']) )
  $_COOKIE['zmBandwidth'] = 'low';

if ( empty($view) ) {
  $view = isset($user)?'console':'login';
} else {
  foreach ( getSkinIncludes('views/class/' . $view . '_class.php') as $includeFile )
    require_once $includeFile;
}

foreach ( getSkinIncludes('includes/config.php') as $includeFile )
  require_once $includeFile;

foreach ( getSkinIncludes('includes/functions.php') as $includeFile )
  require_once $includeFile;

if ( isset($user) ) {
  // Bandwidth Limiter
  if ($user->MaxBandwidth()) {
    if ($user->MaxBandwidth() == 'low' ) {
      $_COOKIE['zmBandwidth'] = 'low';
    } else if ( $user->MaxBandwidth() == 'medium' && $_COOKIE['zmBandwidth'] == 'high' ) {
      $_COOKIE['zmBandwidth'] = 'medium';
    }
  }
}

// If there are additional actions
foreach ( getSkinIncludes( 'includes/actions.php' ) as $includeFile )
  require_once $includeFile; 
?>
