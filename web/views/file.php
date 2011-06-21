<?php
//
// ZoneMinder file view file, $Date: 2008-09-29 14:15:13 +0100 (Mon, 29 Sep 2008) $, $Revision: 2640 $
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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

if ( empty($_REQUEST['path']) )
{
    $errorText = "No path given to file.php";
}
else
{
    $path = $_REQUEST['path'];
    if ( !empty($user['MonitorIds']) )
    {
        $fileOk = false;
        $pathMonId = substr( $path, 0, strspn( $path, "1234567890" ) );
        foreach ( preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) as $monId )
        {
            if ( $pathMonId == $monId )
            {
                $fileOk = true;
                break;
            }
        }
        if ( !$fileOk )
            $errorText = "No permissions to view file '$path'";
    }
}

// Simple version
if ( $errorText )
    Error( $errorText );
else
    readfile( ZM_DIR_EVENTS.'/'.$path );
?>
