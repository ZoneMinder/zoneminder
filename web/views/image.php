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
//     width and height are each optional, ideally supply both, but if only one is supplied the other is calculated
//          These are in pixels
//
//     If both scale and either width or height are specified, scale is ignored
//

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

require_once('includes/Storage.php');
require_once('includes/Event.php');
require_once('includes/Frame.php');

header( 'Content-type: image/jpeg' );

// Compatibility for PHP 5.4 
if (!function_exists('imagescale'))
{
	function imagescale($image, $new_width, $new_height = -1, $mode = 0)
	{
		$mode; // Not supported

		$new_height = ($new_height == -1) ? imagesy($image) : $new_height;
		$imageNew = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($imageNew, $image, 0, 0, 0, 0, (int)$new_width, (int)$new_height, imagesx($image), imagesy($image));

		return $imageNew;
	}
}

$Storage = NULL;
$errorText = false;
if ( empty($_REQUEST['path']) )
{
	if ( ! empty($_REQUEST['fid']) ) {
		if ( ! empty($_REQUEST['eid'] ) ) {
			$Event = new Event( $_REQUEST['eid'] );
			$Frame = Frame::find_one( array( 'EventId' => $_REQUEST['eid'], 'FrameId' => $_REQUEST['fid'] ) );
			if ( ! $Frame ) {
				Fatal("No Frame found for event(".$_REQUEST['eid'].") and frame id(".$_REQUEST['fid'].")");
			}
			$Storage = $Event->Storage();
			$path = $Event->Path().'/'.sprintf("%'.0".ZM_EVENT_IMAGE_DIGITS.'d',$_REQUEST['fid']).'-capture.jpg';
		} else {
# If we are only specifying fid, then the fid must be the primary key into the frames table. But when the event is specified, then it is the frame #
			$Frame = new Frame( $_REQUEST['fid'] );
			$Event = new Event( $Frame->EventId() );
			$Storage = $Event->Storage();
			$path = $Event->Path().'/'.sprintf("%'.0".ZM_EVENT_IMAGE_DIGITS.'d',$Frame->FrameId()).'-capture.jpg';
		}
	} else {
		$errorText = "No image path";
	}

  if ( ! file_exists( $path ) ) {
# Generate the frame JPG
      if ( $Event->DefaultVideo() ) {
        $command ='ffmpeg -i '.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
        #$command ='ffmpeg -v 0 -i '.$Storage->Path().'/'.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
        Debug( "Running $command" );
        $output = array();
        $retval = 0;
        exec( $command, $output, $retval );
        Debug("Retval: $retval, output: " . implode("\n", $output));
      } else {
        Fatal("Can't create frame images from video becuase there is no video file for this event (".$Event->DefaultVideo() );
      }
  }

}
else
{
	$Storage = new Storage();
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

$scale=0;
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


if ( $errorText ) {
	Error( $errorText );
} else {
	if ( ( $scale==0 || $scale==100 ) && $width==0 && $height==0 ) {
		if ( ! readfile( $path ) ) {
			Error("No bytes read from ". $path );
		}
  } else {
		Debug("Doing a scaled image: scale($scale) width($width) height($height)");
		$i = 0;
		if ( ! ( $width && $height ) ) {
			$i = imagecreatefromjpeg( $path );
			$oldWidth = imagesx( $i );
			$oldHeight = imagesy( $i );
			if ( $width == 0 && $height == 0 ) { // scale has to be set to get here with both zero
				$width = $oldWidth  * $scale / 100.0;
				$height= $oldHeight * $scale / 100.0;
			} elseif ( $width == 0 && $height != 0 ) {
				$width = ($height * $oldWidth) / $oldHeight;
			} elseif ( $width != 0 && $height == 0 ) {
				$height = ($width * $oldHeight) / $oldWidth;
			}
			if ( $width == $oldWidth && $height == $oldHeight) {
				Warning( "No change to width despite scaling." );
			}
		}
	
		# Slight optimisation, thumbnails always specify width and height, so we can cache them.
		$scaled_path = preg_replace('/\.jpg$/', "-${width}x${height}.jpg", $path );
		if ( file_exists( $scaled_path ) ) {
			Debug( "Using cached scaled image at $scaled_path.");
			if ( ! readfile( $scaled_path ) ) {
				Error("No bytes read from scaled image". $scaled_path );
			}
		} else {
			Debug( "Cached scaled image does not exist at $scaled_path. Creating it");
			ob_start();
			if ( ! $i )
				$i = imagecreatefromjpeg( $path );
			$iScale = imagescale( $i, $width, $height );
			imagejpeg( $iScale );
			imagedestroy( $i );
			imagedestroy( $iScale );
			$scaled_jpeg_data = ob_get_contents();
			file_put_contents( $scaled_path, $scaled_jpeg_data );
			ob_end_clean();
			echo $scaled_jpeg_data;
		}
	}
}
?>
