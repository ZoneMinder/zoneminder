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
//     Path is physical path to the image.
//     If "path" starts with "/" - then the link is relative to the root (ZM_PATH_WEB), if there is no slash at the beginning, then it is relative to the skin folder (ZM_SKIN_PATH)
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

if (!empty($_REQUEST['proxy'])) {
  $url = $_REQUEST['proxy'];
  if (!$url) {
    ZM\Warning('No url passed to image proxy');
    return;
  }

  $url_parts = parse_url($url);
  $username = $url_parts['user'];
  $password = isset($url_parts['pass']) ? $url_parts['pass'] : '';

  $method = 'GET';
  // preparing http options:
  $opts = array(
    'http'=>array(
      'method'=>$method,
      #'header'=>"Accept-language: en\r\n" .
      'ignore_errors'   => true
      #"Cookie: foo=bar\r\n"
    ),
    'ssl'=>array(
      "verify_peer"=>false,
      "verify_peer_name"=>false,
    )
  );
  $context = stream_context_create($opts);

  // set no time limit and disable compression:
  set_time_limit(5);
  if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
  @ini_set('zlib.output_compression', 0);

  /* Sends an http request with additional headers shown above */
  $fp = @fopen($url, 'r', false, $context);
  if ($fp) {
    $meta_data = stream_get_meta_data($fp);
    ZM\Debug(print_r($meta_data, true));
    foreach ($meta_data['wrapper_data'] as $header) {
      preg_match('/WWW-Authenticate: Digest (.*)/i', $header, $matches);
      $nc = 1;
      if (!empty($matches)) {
        ZM\Debug("Matched $header");
        $auth_header = $matches[1];
        $auth_header_array = explode(',', $auth_header);
        $parsed = array();

        foreach ($auth_header_array as $pair) {
          preg_match('/^\s*(\w+)="?(.+)"?\s*$/', $pair, $vals);
          if (!empty($vals)) {
            $parsed[$vals[1]] = trim($vals[2], '"');
          } else {
            ZM\Debug("Didn't match preg $pair");
          }
        }
        ZM\Debug(print_r($parsed, true));

        $cnonce = uniqid();
        $response_realm     = (isset($parsed['realm'])) ? $parsed['realm'] : '';
        $response_nonce     = (isset($parsed['nonce'])) ? $parsed['nonce'] : '';
        $response_opaque    = (isset($parsed['opaque'])) ? $parsed['opaque'] : '';

        $authenticate1 = md5($username.':'.$response_realm.':'.$password);
        $authenticate2 = md5($method.':'.$url);

        $digestData = $authenticate1.':'.$response_nonce;
        if (!empty($parsed['qop'])) {
          $digestData .= ':' . sprintf('%08x', $nc) . ':' . $cnonce . ':' . $parsed['qop'];
        }
        $authenticate_response = md5($digestData.':'.$authenticate2);

        $request = sprintf('Authorization: Digest username="%s", realm="%s", nonce="%s", uri="%s", response="%s"',
          $username, $response_realm, $response_nonce, $url, $authenticate_response);
        if (!empty($parsed['opaque'])) $request .= ', opaque="'.$parsed['opaque'].'"';
        if (!empty($parsed['qop'])) {
          $request .= ', qop="'.$parsed['qop'].'"';
          $request .= ', nc="'.sprintf('%08x', $nc).'"';
          $nc++;
          $request .= ', cnonce="'.$cnonce.'"';
        }
        $request .= ', algorithm="MD5"';
        ZM\Debug($request);

        $request_header = array($request);
        $opts['http']['header'] = $request;
        $context = stream_context_create($opts);
        $fp = fopen($url, 'r', false, $context);
        $meta_data = stream_get_meta_data($fp);
        ZM\Debug(print_r($meta_data, true));
      } # end if have auth
    } # end foreach header

    # Read in until we either stop reading or have a second Content-Length
    $r = '';
    while (substr_count($r, 'Content-Length') != 2) {
      $new = fread($fp, 512);
      if (!$new) break;
      $r .= $new;
    }
    #ZM\Debug($r);

    $start = strpos($r, "\xff");
    if (false !== $start) {
      header('Content-type: image/jpeg');
      $end   = strpos($r, "--\n", $start)-1;
      if ($end > $start) {
        $frame = substr($r, $start, $end - $start);
        ZM\Debug("Start $start end $end");
        if (imagecreatefromstring($frame)) {
          echo $frame;
        }
      } else {
        # This is possibly an XSS but I don't see how to get around it other than actually trying to parse it as a valid image first.
        # So we only output it if imagecreatefromdata succeeds
        if (imagecreatefromstring($r)) {
          echo $r;
        }
      }
    } else {
      $img = imagecreate(320, 240);

      $textbgcolor = imagecolorallocate($img, 0, 0, 0);
      $textcolor = imagecolorallocate($img, 255, 255, 255);

      imagestring($img, 5, 5, 5, 'Authentication Failed', $textcolor);
      header('Content-type: image/jpeg');
      imagejpeg($img);
    }

    fclose($fp);
  } else {
    ZM\Debug("Failed to open $url");
    $img = imagecreate(320, 200);

    $textbgcolor = imagecolorallocate($img, 0, 0, 0);
    $textcolor = imagecolorallocate($img, 255, 255, 255);

    imagestring($img, 5, 5, 5, 'Failed to open', $textcolor);
    header('Content-type: image/jpeg');
    imagejpeg($img);
  }
  return;
}

$errorText = false;
$filename = '';
$Frame = null;
$Event = null;
$path = null;
$media_type='image/jpeg';
$no_generate_scaled_jpeg = false;

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
        if ( $Event->SaveJPEGs() & 1 ) {
          # If we store Frames as jpgs, then we don't store an alarmed snapshot
          $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $Frame->FrameId()).'-'.$show.'.jpg';
        } else {
          header('HTTP/1.0 404 Not Found');
          ZM\Debug('No alarm jpg found for event '.$_REQUEST['eid'].' at '.$path);
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
        if ( $Event->SaveJPEGs() & 1 ) {
          # If we store Frames as jpgs, then we don't store a snapshot
          $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d', $Frame->FrameId()).'-'.$show.'.jpg';
        } else {
          if ( $Event->DefaultVideo() ) {
            $file_path = $Event->Path().'/'.$Event->DefaultVideo();

            if (!file_exists($file_path)) {
              if ($file = find_video($Event->Path())) {
                $file_path = $Event->Path().'/'.$file;
              }
            }
            if (file_exists($file_path)) {
              $command = ZM_PATH_FFMPEG.' -ss '. $Frame->Delta() .' -i '.$file_path.' -frames:v 1 '.$path . ' 2>&1';
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
              }
            } else {
              header('HTTP/1.0 404 Not Found');
              ZM\Error('Can\'t create frame images from missing video file at '.$Event->DefaultVideo());
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
        $Frame = $Event->find_virtual_frame($_REQUEST['fid']);
        if (!$Frame) {
          header('HTTP/1.0 404 Not Found');
          ZM\Error('No Frame found for event('.$_REQUEST['eid'].') and frame id('.$_REQUEST['fid'].')');
          return;
        }
      }  # end if !Frame
      // Frame can be non-existent.  We have Bulk frames.  So now we should try to load the bulk frame 
      $path = $Event->Path().'/'.sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d',$Frame->FrameId()).'-'.$show.'.jpg';
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
      $file_path = $Event->Path().'/'.$Event->DefaultVideo();

      if (!file_exists($file_path)) {
        if ($file = find_video($Event->Path())) {
          $file_path = $Event->Path().'/'.$file;
        }
      }
      if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        ZM\Error("Can't create frame images from video because there is no video file for this event at (".$Event->Path().'/'.$Event->DefaultVideo() );
        return;
      }
      $command = ZM_PATH_FFMPEG.' -ss '. $Frame->Delta() .' -i '.$file_path.' -frames:v 1 '.$path . ' 2>&1';
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
      }
    } else {
      header('HTTP/1.0 404 Not Found');
      ZM\Error("Can't create frame $show images from video because there is no video file for this event at ".
        $Event->Path().'/'.$Event->DefaultVideo() );
      return;
    }
  } # end if ! file_exists($path)
} else {
  $path = (strpos(validHtmlStr($_REQUEST['path']), '/') == 0) ? ZM_PATH_WEB.validHtmlStr($_REQUEST['path']) : ZM_PATH_WEB.'/'.ZM_SKIN_PATH.'/'.validHtmlStr($_REQUEST['path']);
  if ( !file_exists($path) ) return;
  $no_generate_scaled_jpeg = true; //Firstly, this is not necessary, and secondly, the image may be located in a write-locked directory.
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
  # Must lock it because zmc may be still writing the jpg and will have a lock on it.
  $fp_path = fopen($path, 'r');
  $lock = flock($fp_path, LOCK_SH);
  if (!$lock) ZM\Warning("Unable to get a read lock on $path, continuing.");

  header('Content-type: '.$media_type);
  header('Cache-Control: max-age=86400');
  header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60))); // Default set to 1 hour
  header('Pragma: cache');
  if (($scale==0 || $scale==100) && ($width==0) && ($height==0)) {
    # This is so that Save Image As give a useful filename
    if ($Event) {
      $filename = $Event->MonitorId().'_'.$Event->Id().'_'.$Frame->FrameId().'.jpg';
      header('Content-Disposition: inline; filename="' . $filename . '"');
    }
    if (!readfile($path)) {
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

    if (!file_exists($scaled_path)) {
      ZM\Debug("Cached scaled image does not exist at $scaled_path. Creating it");

      if (!$i) {
        $i = imagecreatefromjpeg($path);
      }
      if (!$i) {
        ZM\Error('Unable to load jpeg from '.$scaled_path);
        $i  = imagecreatetruecolor($width, $height);
        $bg_colour = imagecolorallocate($i, 255, 255, 255);
        $fg_colour = imagecolorallocate($i, 0, 0, 0);
        imagefilledrectangle($i, 0, 0, $width, $height, $bg_colour);
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
        if (!$no_generate_scaled_jpeg) file_put_contents($scaled_path, $scaled_jpeg_data, LOCK_EX);

        echo $scaled_jpeg_data;
      }
    } else {
      $fp_scaled_path = fopen($scaled_path, 'r');
      $lock = flock($fp_scaled_path, LOCK_SH);
      if (!$lock) Warning("Unable to get a read lock on $scaled_path, trying to send anyways.");

      ZM\Debug("Sending $scaled_path");
      $bytes = readfile($scaled_path);
      if ( !$bytes ) {
        ZM\Error('No bytes read from '. $scaled_path);
      } else {
        ZM\Debug("$bytes sent");
      }
      flock($fp_scaled_path, LOCK_UN);
      fclose($fp_scaled_path);
    } # end if scaled image doesn't exist or failed sending it

  } # end if scaled or not
  flock($fp_path, LOCK_UN);
  fclose($fp_path);
}

function find_video($path) {
  # Look for other mp4s
  if (file_exists($path)) {
    $files = scandir($path);
    foreach ($files as $file) {
      if (preg_match('/.mp4$/i', $file)) {
        return $file;
      }
    }
  }
}
exit();
