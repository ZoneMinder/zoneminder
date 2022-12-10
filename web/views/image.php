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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

if ( !canView('Events') and ($_REQUEST['fid'] != 'snapshot' or !canView('Snapshots'))) {
  $view = 'error';
  return;
}
require_once('includes/Event.php');
require_once('includes/Frame.php');

// Compatibility for PHP 5.4 
if ( !function_exists('imagescale') ) {
  function imagescale($image, $new_width, $new_height = -1, $mode = 0) {
    $mode; // Not supported

    $new_height = ($new_height == -1) ? imagesy($image) : $new_height;
    $imageNew = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($imageNew, $image, 0, 0, 0, 0, (int)$new_width, (int)$new_height, imagesx($image), imagesy($image));

    return $imageNew;
  }
}

$errorText = false;
$filename = '';
$Frame = null;
$Event = null;
$path = null;
$media_type='image/jpeg';

if ( empty($_REQUEST['path']) ) {

  $show = empty($_REQUEST['show']) ? 'capture' : $_REQUEST['show'];

  if ( empty($_REQUEST['fid']) ) {
    header('HTTP/1.0 404 Not Found');
    ZM\Error('No Frame ID specified');
    return;
  }

  if ( !empty($_REQUEST['eid']) ) {
    $Event = ZM\Event::find_one(array('Id'=>$_REQUEST['eid']));
    if ( !$Event ) {
      header('HTTP/1.0 404 Not Found');
      ZM\Error('Event '.$_REQUEST['eid'].' Not found');
      return;
    }

    if ( $_REQUEST['fid'] == 'objdetect' ) {
      // if animation file is found, return that, else return image
      // we are only looking for GIF or jpg here, not mp4
      // as most often, browsers asking for this link will be expecting
      // media types that can be rendered as <img src=>
      $path_anim_gif = $Event->Path().'/objdetect.gif';
      $path_image = $Event->Path().'/objdetect.jpg';
      if (file_exists($path_anim_gif)) {
        // we found the animation gif file
        $media_type = 'image/gif';
        ZM\Debug("Animation file found at $path");
        $path = $path_anim_gif;
      } else if (file_exists($path_image)) {
        // animation not found, but image found
        ZM\Debug("Image file found at $path");
        $path = $path_image;
      } else {
        // neither animation nor image found
        header('HTTP/1.0 404 Not Found');
        ZM\Error('Object detection animation and image not found for this event');  
        return;
      }
      $Frame = new ZM\Frame();
      $Frame->Id('objdetect');
    } else if ( $_REQUEST['fid'] == 'objdetect_mp4' ) {
      $path = $Event->Path().'/objdetect.mp4';
      if ( !file_exists($path) ) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error("File $path does not exist. You might not have enabled create_animation in objectconfig.ini. If you have, inspect debug logs for errors during creation");
        return;
      }
      $Frame = new ZM\Frame();
      $Frame->Id('objdetect');
      $media_type = 'video/mp4';
    } else if ( $_REQUEST['fid'] == 'objdetect_gif' ) {
      $path = $Event->Path().'/objdetect.gif';
      if ( !file_exists($path) ) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error("File $path does not exist. You might not have enabled create_animation in objectconfig.ini. If you have, inspect debug logs for errors during creation");
        return;
      }
      $Frame = new ZM\Frame();
      $Frame->Id('objdetect');
      $media_type = 'image/gif';
    } else if ( $_REQUEST['fid'] == 'objdetect_jpg' ) {
      $path = $Event->Path().'/objdetect.jpg';
      if ( !file_exists($path) ) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error("File $path does not exist. Please make sure store_frame_in_zm is enabled in the object detection config");
        return;
      }
      $Frame = new ZM\Frame();
      $Frame->Id('objdetect');
    } else if ( $_REQUEST['fid'] == 'alarm' ) {
      $path = $Event->Path().'/alarm.jpg';
      if ( !file_exists($path) ) {
        # legacy support
        # look for first alarmed frame
        $Frame = ZM\Frame::find_one(
          array('EventId'=>$_REQUEST['eid'], 'Type'=>'Alarm'),
          array('order'=>'FrameId ASC'));
        if ( !$Frame ) { # no alarms, get first one I find
          $Frame = ZM\Frame::find_one(array('EventId'=>$_REQUEST['eid']));
          if ( !$Frame ) { 
            ZM\Warning('No frame found for event '.$_REQUEST['eid']);
            $Frame = new ZM\Frame();
            $Frame->Delta(1);
            $Frame->FrameId(1);
          }
        }
        $Monitor = $Event->Monitor();
        if ( $Event->SaveJPEGs() & 1 ) {
          # If we store Frames as jpgs, then we don't store an alarmed snapshot
          $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $Frame->FrameId()).'-'.$show.'.jpg';
        } else {
          header('HTTP/1.0 404 Not Found');
          ZM\Error('No alarm jpg found for event '.$_REQUEST['eid']);
          return;
        }
      } else {
        $Frame = new ZM\Frame();
        $Frame->Delta(1);
        $Frame->FrameId('alarm');
      } # alarm.jpg found
    } else if ( $_REQUEST['fid'] == 'snapshot' ) {
      $path = $Event->Path().'/snapshot.jpg';
      if ( !file_exists($path) ) {
        $Frame = ZM\Frame::find_one(array('EventId'=>$_REQUEST['eid'], 'Score'=>$Event->MaxScore()));
        if ( !$Frame )
          $Frame = ZM\Frame::find_one(array('EventId'=>$_REQUEST['eid']));
        if ( !$Frame ) {
          ZM\Warning('No frame found for event ' . $_REQUEST['eid']);
          $Frame = new ZM\Frame();
          $Frame->Delta(1);
          if ( $Event->SaveJPEGs() & 1 ) {
            $Frame->FrameId(0);
          } else {
            $Frame->FrameId('snapshot');
          }
        }
        $Monitor = $Event->Monitor();
        if ( $Event->SaveJPEGs() & 1 ) {
          # If we store Frames as jpgs, then we don't store a snapshot
          $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $Frame->FrameId()).'-'.$show.'.jpg';
        } else {
          if ( $Event->DefaultVideo() ) {
            $command = ZM_PATH_FFMPEG.' -ss '. $Frame->Delta() .' -i '.$Event->Path().'/'.$Event->DefaultVideo().' -frames:v 1 '.$path . ' 2>&1';
            #$command ='ffmpeg -ss '. $Frame->Delta() .' -i '.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
            #$command ='ffmpeg -v 0 -i '.$Storage->Path().'/'.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
            ZM\Debug("Running $command");
            $output = array();
            $retval = 0;
            exec($command, $output, $retval);
            ZM\Debug("Command: $command, retval: $retval, output: " . implode("\n", $output));
            if ( ! file_exists($path) ) {
              header('HTTP/1.0 404 Not Found');
              ZM\Error('Can\'t create frame images from video for this event '.$Event->DefaultVideo().'

                Command was: '.$command.'

                Output was: '.implode(PHP_EOL,$output) );
              return;
            }
            # Generating an image file will use up more disk space, so update the Event record.
            if ( $Event->EndDateTime() ) {
              $Event->DiskSpace(null);
              $Event->save();
            }
          } else {
            header('HTTP/1.0 404 Not Found');
            ZM\Error('No snapshot jpg found for event '.$_REQUEST['eid']);
            return;
          }
        } # end if stored jpgs
      } else {
        $Frame = new ZM\Frame();
        $Frame->Delta(1);
        $Frame->FrameId('snapshot');
      } # end if found snapshot.jpg
    } else {
      $Frame = ZM\Frame::find_one(array('EventId'=>$_REQUEST['eid'], 'FrameId'=>$_REQUEST['fid']));
      if (!$Frame) {
        $previousBulkFrame = dbFetchOne(
          'SELECT * FROM Frames WHERE EventId=? AND FrameId < ? ORDER BY FrameID DESC LIMIT 1',
          NULL, array($_REQUEST['eid'], $_REQUEST['fid'])
        );
        $nextBulkFrame = dbFetchOne(
          'SELECT * FROM Frames WHERE EventId=? AND FrameId > ? ORDER BY FrameID ASC LIMIT 1',
          NULL, array($_REQUEST['eid'], $_REQUEST['fid'])
        );
        if ($previousBulkFrame and $nextBulkFrame) {
          $Frame = new ZM\Frame($previousBulkFrame);
          $Frame->FrameId($_REQUEST['fid']);

          $percentage = ($Frame->FrameId() - $previousBulkFrame['FrameId']) / ($nextBulkFrame['FrameId'] - $previousBulkFrame['FrameId']);

          $Frame->Delta($previousBulkFrame['Delta'] + floor( 100* ( $nextBulkFrame['Delta'] - $previousBulkFrame['Delta'] ) * $percentage )/100);
          ZM\Debug('Got virtual frame from Bulk Frames previous delta: ' . $previousBulkFrame['Delta'] . ' + nextdelta:' . $nextBulkFrame['Delta'] . ' - ' . $previousBulkFrame['Delta'] . ' * ' . $percentage );
        } 
        else if($previousBulkFrame){
          //If no next Frame we have to pull data from the Event itself
          $Frame = new ZM\Frame($previousBulkFrame);
          $Frame->FrameId($_REQUEST['fid']);

          $percentage = ($Frame->FrameId()/$Event->Frames());

          $Frame->Delta(floor($Event->Length() * $percentage));
        }
        else {
          header('HTTP/1.0 404 Not Found');
          ZM\Error('No Frame found for event('.$_REQUEST['eid'].') and frame id('.$_REQUEST['fid'].')');
          return;
        }
      }  # end if !Frame
      // Frame can be non-existent.  We have Bulk frames.  So now we should try to load the bulk frame 
      $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d',$Frame->FrameId()).'-'.$show.'.jpg';
      ZM\Debug("Path: $path");
    }  # if special frame (snapshot, alarm etc) or identified by id

  } else {
# If we are only specifying fid, then the fid must be the primary key into the frames table. But when the event is specified, then it is the frame #
    $Frame = ZM\Frame::find_one(array('Id'=>$_REQUEST['fid']));
    if ( !$Frame ) {
      header('HTTP/1.0 404 Not Found');
      ZM\Error('Frame ' . $_REQUEST['fid'] . ' Not Found');
      return;
    }

    $Event = ZM\Event::find_one(array('Id'=>$Frame->EventId()));
    if ( !$Event ) {
      header('HTTP/1.0 404 Not Found');
      ZM\Error('Event ' . $Frame->EventId() . ' Not Found');
      return;
    }
    $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d',$Frame->FrameId()).'-'.$show.'.jpg';
  } # end if have eid
    
  if ( !file_exists($path) ) {
    ZM\Debug("$path does not exist");
    # Generate the frame JPG
    if ( ($show == 'capture') and $Event->DefaultVideo() ) {
      if ( !file_exists($Event->Path().'/'.$Event->DefaultVideo()) ) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error("Can't create frame images from video because there is no video file for this event at (".$Event->Path().'/'.$Event->DefaultVideo() );
        return;
      }
      $command = ZM_PATH_FFMPEG.' -ss '. $Frame->Delta() .' -i '.$Event->Path().'/'.$Event->DefaultVideo().' -frames:v 1 '.$path . ' 2>&1';
      #$command ='ffmpeg -ss '. $Frame->Delta() .' -i '.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
#$command ='ffmpeg -v 0 -i '.$Storage->Path().'/'.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
      ZM\Debug("Running $command");
      $output = array();
      $retval = 0;
      exec($command, $output, $retval);
      ZM\Debug("Command: $command, retval: $retval, output: " . implode("\n", $output));
      if ( ! file_exists($path) ) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error('Can\'t create frame images from video for this event '.$Event->DefaultVideo().'

Command was: '.$command.'

Output was: '.implode(PHP_EOL,$output) );
        return;
      }
      # Generating an image file will use up more disk space, so update the Event record.
      if ( $Event->EndDateTime() ) {
        $Event->DiskSpace(null);
        $Event->save();
      }
    } else {
      header('HTTP/1.0 404 Not Found');
      ZM\Error("Can't create frame $show images from video because there is no video file for this event at ".
        $Event->Path().'/'.$Event->DefaultVideo() );
      return;
    }
  } # end if ! file_exists($path)

} else {
  ZM\Warning('Loading images by path is deprecated');
  $dir_events = realpath(ZM_DIR_EVENTS);
  $path = realpath($dir_events . '/' . $_REQUEST['path']);
  $pos = strpos($path, $dir_events);

  if ( $pos == 0 && $pos !== false ) {
    if ( ! empty($user['MonitorIds']) ) {
      $imageOk = false;
      $pathMonId = substr($path, 0, strspn($path, '1234567890'));
      foreach ( preg_split('/["\'\s]*,["\'\s]*/', $user['MonitorIds']) as $monId ) {
        if ( $pathMonId == $monId ) {
          $imageOk = true;
          break;
        }
      }
      if ( !$imageOk )
        $errorText = 'No image permissions';
    }
  } else {
    $errorText = 'Invalid image path';
  }
  if ( !file_exists($path) ) {
    header('HTTP/1.0 404 Not Found');
    ZM\Error("Image not found at $path");
    return;
  }
}

# we now load the actual image to send
$scale = 0;
if ( !empty($_REQUEST['scale']) ) {
  if ( is_numeric($_REQUEST['scale']) ) {
    $x = $_REQUEST['scale'];
    if ( $x >= 1 and $x <= 400 )
      $scale = $x;
  }
}

$width = 0;
if ( !empty($_REQUEST['width']) ) {
  if ( is_numeric($_REQUEST['width']) ) {
    $x = $_REQUEST['width'];
    if ( $x >= 10 and $x <= 8000 )
      $width = $x;
  }
}

$height = 0;
if ( !empty($_REQUEST['height']) ) {
  if ( is_numeric($_REQUEST['height']) ) {
    $x = $_REQUEST['height'];
    if ( $x >= 10 and $x <= 8000 )
      $height = $x;
  }
}

if ( $errorText ) {
  ZM\Error($errorText);
} else {
  header('Content-type: '.$media_type);
  if ( ( $scale==0 || $scale==100 ) && ($width==0) && ($height==0) ) {
    # This is so that Save Image As give a useful filename
    if ( $Event ) {
      $filename = $Event->MonitorId().'_'.$Event->Id().'_'.$Frame->FrameId().'.jpg';
      header('Content-Disposition: inline; filename="' . $filename . '"');
    }
    if ( !readfile($path) ) {
      ZM\Error('No bytes read from '. $path);
    }
  } else {
    ZM\Debug("Doing a scaled image: scale($scale) width($width) height($height)");
    $i = null;
    if ( ! ( $width && $height ) ) {
      $i = imagecreatefromjpeg($path);
      $oldWidth = imagesx($i);
      $oldHeight = imagesy($i);
      if ( $width == 0 && $height == 0 ) { // scale has to be set to get here with both zero
        $width = intval($oldWidth  * $scale / 100.0);
        $height= intval($oldHeight * $scale / 100.0);
      } elseif ( $width == 0 && $height != 0 ) {
        $width = intval(($height * $oldWidth) / $oldHeight);
      } elseif ( $width != 0 && $height == 0 ) {
        $height = intval(($width * $oldHeight) / $oldWidth);
ZM\Debug("Figuring out height using width: $height = ($width * $oldHeight) / $oldWidth");
      }
      if ( $width == $oldWidth && $height == $oldHeight ) {
        ZM\Warning('No change to width despite scaling.');
      }
    }
  
    # Slight optimisation, thumbnails always specify width and height, so we can cache them.
    $scaled_path = preg_replace('/\.jpg$/', "-${width}x${height}.jpg", $path);
    if ($Event) {
      $filename = $Event->MonitorId().'_'.$Event->Id().'_'.$Frame->FrameId()."-${width}x${height}.jpg";
      header('Content-Disposition: inline; filename="' . $filename . '"');
    }
    if ( !( file_exists($scaled_path) and readfile($scaled_path) ) ) {
      ZM\Debug("Cached scaled image does not exist at $scaled_path or is no good.. Creating it");
      if (!$i)
        $i = imagecreatefromjpeg($path);
      if ( !$i) {
        ZM\Error('Unable to load jpeg from '.$scaled_path);
        $i  = imagecreatetruecolor($width, $height);
        $bg_colour = imagecolorallocate($i, 255, 255, 255);
        $fg_colour = imagecolorallocate($i, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg_colour);
        imagestring($i, 1, 5, 5, 'Unable to load jpeg from  ' . $scaled_path, $fg_colour);
        imagejpeg($i);
      } else {
        ZM\Debug("Have image scaling to $width x $height");
        ob_start();
        $iScale = imagescale($i, $width, $height);
        imagejpeg($iScale);
        imagedestroy($i);
        imagedestroy($iScale);
        $scaled_jpeg_data = ob_get_contents();
        file_put_contents($scaled_path, $scaled_jpeg_data);
        echo $scaled_jpeg_data;
      }
    } else {
      ZM\Debug("Sending $scaled_path");
      $bytes = readfile($scaled_path);
      if ( !$bytes ) {
        ZM\Error('No bytes read from '. $scaled_path);
      } else {
        ZM\Debug("$bytes sent");
      }
    }
  }
}
exit();
