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

// Calling sequence:   ... /zm/index.php?view=image&path=/monid/path/image.jpg&scale=nnn&width=wwww&height=hhhhh
//
//     Path is physical path to the image starting at the monitor id
//
//     Scale is optional and between 1 and 400 (percent),
//          Omitted or 100 = no scaling done, image passed through directly
//          Scaling will increase response time slightly
//
//     width and height are optional, but if one is passed both should be passed (or they will be ignored)
//          Pixel dimension to returned, scaling up or down
//
//     If both scale and width & height are specified, scale is ignored
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

$scale=100;
if( !empty($_REQUEST['scale']) )
    if (is_numeric($_REQUEST['scale']))
    {
        $x = $_REQUEST['scale'];
        if($x >= 1 and $x <= 400)
            $scale=$x;
    }

$width=0;
if( !empty($_REQUEST['width']) )
    if (is_numeric($_REQUEST['width']))
    {
        $x = $_REQUEST['width'];
        if($x >= 10 and $x <= 8000)
            $width=$x;
    }
$height=0;
if( !empty($_REQUEST['height']) )
    if (is_numeric($_REQUEST['height']))
    {
        $x = $_REQUEST['height'];
        if($x >= 10 and $x <= 8000)
            $height=$x;
    }


if ( $errorText )
    Error( $errorText );
else
    if($scale==100 && ($width==0 || $height==0) )
        readfile( ZM_DIR_EVENTS.'/'.$path );
    else
    {
        $i = imagecreatefromjpeg ( ZM_DIR_EVENTS.'/'.$path );
        $oldWidth=imagesx($i);
        $oldHeight=imagesy($i);
        if($width==0 || $height==0)  // default to scale if either height or width is missing
        {
            $width=$oldWidth * $scale / 100.0;
            $height=$oldHeight * $scale / 100.0;
        }
        if($width==$oldWidth && $height==$oldHeight)  // See if we really need to scale
        {
            imagejpg($i);
            imagedestroy($i);
        }
        else  // we do need to scale
        {
            $iScale = imagescale($i, $width, $height);
            imagejpeg($iScale);
            imagedestroy($i);
            imagedestroy($iScale);
        }
    }
?>
