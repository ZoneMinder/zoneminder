<?php
//
// ZoneMinder web image view file, $Date: 2008-09-29 14:15:13 +0100 (Mon, 29 Sep 2008) $, $Revision: 2640 $
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

header( 'Content-type: image/jpeg' );

$errorText = false;
if ( empty($_REQUEST['path']) )
{
    $errorText = "No image path";
}
else
{
    $path = $_REQUEST['path'];
    if ( !empty($user['MonitorIds']) )
    {
        $imageOk = false;
        $pathMonId = substr( $path, 0, strspn( $path, "1234567890" ) );
        foreach ( preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) as $monId )
        {
            if ( $pathMonId == $monId )
            {
                $imageOk = true;
                break;
            }
        }
        if ( !$imageOk )
            $errorText = "No image permissions";
    }
}

if ( true )
{
    // Simple version
    if ( $errorText )
        Error( $errorText );
    else
        readfile( ZM_DIR_EVENTS.'/'.$path );
}
else
{
    // Not so simple version
    if ( !function_exists( "imagecreatefromjpeg" ) )
        Warning( "The imagecreatefromjpeg function is not present, php-gd not installed?" );

    if ( !$errorText )
    {
        if ( !($image = imagecreatefromjpeg( ZM_DIR_EVENTS.'/'.$path )) )
        {
            $errorText = "Can't load image";
            $error = error_get_last();
            Error( $error['message'] );
        }
    }

    if ( $errorText )
    {
        if ( !($image = imagecreatetruecolor( 160, 120 )) )
        {
            $error = error_get_last();
            Error( $error['message'] );
        }
        if ( !($textColor = imagecolorallocate( $image, 255, 0, 0 )) )
        {
            $error = error_get_last();
            Error( $error['message'] );
        }
        if ( !imagestring( $image, 1, 20, 60, $errorText, $textColor ) )
        {
            $error = error_get_last();
            Error( $error['message'] );
        }
        Fatal( $errorText." - ".$path );
    }

    imagejpeg( $image );

    imagedestroy( $image );
}
?>
