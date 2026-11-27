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

// includes/config.php defines the ZM_WEB_* constants by switching on this
// value and has no default case, so an unrecognised one leaves all of them
// undefined and every page fatals on the first one it uses. Neither source is
// trustworthy - the cookie is set client-side, and ZM_BANDWIDTH_DEFAULT is a
// free-form string that a conf.d file can override - so validate both rather
// than only testing for empty.
if ( !isValidBandwidth($_COOKIE['zmBandwidth'] ?? '') )
  $_COOKIE['zmBandwidth'] = isValidBandwidth(ZM_BANDWIDTH_DEFAULT) ? ZM_BANDWIDTH_DEFAULT : 'low';

// Clamp bandwidth before config.php so the ZM_WEB_* defines use the clamped value
if ( isset($user) ) {
  if ($user->MaxBandwidth()) {
    if ($user->MaxBandwidth() == 'low' ) {
      $_COOKIE['zmBandwidth'] = 'low';
    } else if ( $user->MaxBandwidth() == 'medium' && $_COOKIE['zmBandwidth'] == 'high' ) {
      $_COOKIE['zmBandwidth'] = 'medium';
    }
  }
}

foreach ( getSkinIncludes('includes/config.php') as $includeFile )
  require_once $includeFile;

foreach ( getSkinIncludes('includes/functions.php') as $includeFile )
  require_once $includeFile;

// If there are additional actions
foreach ( getSkinIncludes( 'includes/actions.php' ) as $includeFile )
  require_once $includeFile; 
?>
