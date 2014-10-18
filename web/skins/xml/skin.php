<?php
//
// ZoneMinder HTML interface file, $Date: 2008-09-26 02:47:20 -0700 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

if ( empty($_COOKIE['zmBandwidth']) )
	    $_COOKIE['zmBandwidth'] = "low";

foreach ( getSkinIncludes( 'includes/config.php' ) as $includeFile )
    require_once $includeFile;

foreach ( getSkinIncludes( 'includes/functions.php' ) as $includeFile )
    require_once $includeFile;

if ( empty($view) )
     $view = 'console';

if ( !isset($user) && ZM_OPT_USE_AUTH && ZM_AUTH_TYPE == "remote" && !empty( $_SERVER['REMOTE_USER']) )
{
     $view = "postlogin";
     $action = "login";
     $_REQUEST['username'] = $_SERVER['REMOTE_USER'];
}
/* Get version info from client */
updateClientVer();
/* Store some logging information in session variables
 * so other processes can access them */
if (defined("ZM_EYEZM_LOG_TO_FILE")) $_SESSION['xml_log_to_file'] = ZM_EYEZM_LOG_TO_FILE;
if (defined("ZM_EYEZM_LOG_FILE")) $_SESSION['xml_log_file'] = ZM_EYEZM_LOG_FILE;
if (defined("ZM_EYEZM_DEBUG")) $_SESSION['xml_debug'] = ZM_EYEZM_DEBUG;

?>
