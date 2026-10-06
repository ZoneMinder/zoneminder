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

if (!empty($_REQUEST['proxy'])) {
  // The only consumer is the camera discovery thumbnail on add_monitors, which
  // already requires monitor editing rights. Don't hand an outbound fetch
  // primitive to plain event viewers.
  if (!canEdit('Monitors')) {
    ZM\Warning('Insufficient privileges to use the image proxy');
    return;
  }

  // index.php exempts view=image from csrf_check() so images work as <img src>,
  // and csrf_check() only looks at POSTs anyway. The proxy makes the server
  // fetch a URL, so require the token here or any site could drive it through
  // a logged-in user's browser.
  if (ZM_ENABLE_CSRF_MAGIC) {
    require_once('includes/csrf/csrf-magic.php');
    if (!isset($_REQUEST['__csrf_magic']) || !is_string($_REQUEST['__csrf_magic']) ||
        !csrf_check_tokens($_REQUEST['__csrf_magic'])) {
      ZM\Warning('Image proxy request without a valid CSRF token');
      http_response_code(403);
      return;
    }
  }
  // Also covers installs with CSRF magic off: browsers that send Sec-Fetch-Site
  // say when a request was started by another site. Older browsers omit it.
  if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) and
      !in_array($_SERVER['HTTP_SEC_FETCH_SITE'], ['same-origin', 'none'], true)) {
    ZM\Warning('Image proxy request started by another site');
    http_response_code(403);
    return;
  }

  $url = $_REQUEST['proxy'];
  if (!$url) {
    ZM\Warning('No url passed to image proxy');
    return;
  }

  $url_parts = parse_url($url);
  if (!$url_parts || !isset($url_parts['scheme']) ||
      !in_array(strtolower($url_parts['scheme']), array('http', 'https'))) {
    ZM\Warning('Image proxy only supports http/https URLs');
    return;
  }

  $host = isset($url_parts['host']) ? $url_parts['host'] : '';
  if ($host === '') {
    ZM\Warning('Image proxy requires a host in the url');
    return;
  }

  // Guard against SSRF. Discovery legitimately proxies cameras on the local
  // LAN, so private ranges stay reachable, but loopback, link-local and other
  // reserved addresses (127.0.0.1, ::1, 169.254.169.254 cloud metadata) are
  // refused. FILTER_FLAG_NO_RES_RANGE covers exactly those without excluding
  // 10/8, 172.16/12, 192.168/16 or fc00::/7.
  // parse_url keeps the brackets around an IPv6 literal.
  $host_ip = trim($host, '[]');
  if (filter_var($host_ip, FILTER_VALIDATE_IP)) {
    $addresses = array($host_ip);
  } else {
    $addresses = gethostbynamel($host);
    if (!$addresses) $addresses = array();
    $aaaa = @dns_get_record($host, DNS_AAAA);
    if ($aaaa) {
      foreach ($aaaa as $rr) {
        if (!empty($rr['ipv6'])) $addresses[] = $rr['ipv6'];
      }
    }
  }
  if (!$addresses) {
    ZM\Warning('Image proxy could not resolve '.$host);
    return;
  }
  foreach ($addresses as $address) {
    if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
      ZM\Warning('Image proxy refusing reserved address '.$address.' for host '.$host);
      return;
    }
  }

  // Connect to the address that was just checked rather than letting fopen()
  // resolve $host again: a second lookup could return a different answer (DNS
  // rebinding) such as 127.0.0.1. The original name still goes out in the
  // Host header and as the TLS peer name/SNI.
  $connect_ip = $addresses[0];
  $host_header = 'Host: '.$host.(isset($url_parts['port']) ? ':'.$url_parts['port'] : '');
  $fetch_url = $url_parts['scheme'].'://';
  if (isset($url_parts['user'])) {
    $fetch_url .= $url_parts['user'].(isset($url_parts['pass']) ? ':'.$url_parts['pass'] : '').'@';
  }
  $fetch_url .= (strpos($connect_ip, ':') !== false ? '['.$connect_ip.']' : $connect_ip);
  if (isset($url_parts['port'])) $fetch_url .= ':'.$url_parts['port'];
  $fetch_url .= isset($url_parts['path']) ? $url_parts['path'] : '/';
  if (isset($url_parts['query'])) $fetch_url .= '?'.$url_parts['query'];

  $username = isset($url_parts['user']) ? $url_parts['user'] : '';
  $password = isset($url_parts['pass']) ? $url_parts['pass'] : '';

  $method = 'GET';
  // preparing http options:
  $opts = array(
    'http'=>array(
      'method'=>$method,
      'header'=>array($host_header),
      #'header'=>"Accept-language: en\r\n" .
      'ignore_errors'   => true,
      // The SSRF guard above only validated $host. Following a redirect would
      // connect to a Location the guard never checked (e.g. 127.0.0.1).
      'follow_location' => 0,
      #"Cookie: foo=bar\r\n"
    ),
    'ssl'=>array(
      "verify_peer"=>false,
      "verify_peer_name"=>false,
      "peer_name"=>$host_ip,
    )
  );
  $context = stream_context_create($opts);

  // set no time limit and disable compression:
  set_time_limit(5);
  if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
  @ini_set('zlib.output_compression', 0);

  /* Sends an http request with additional headers shown above */
  $fp = @fopen($fetch_url, 'r', false, $context);
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
        $opts['http']['header'] = array($host_header, $request);
        $context = stream_context_create($opts);
        $fp = fopen($fetch_url, 'r', false, $context);
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

if ( empty($_REQUEST['path']) ) {

  // Validate show parameter against allowlist to prevent command injection (CVE-2025-65791)
  $allowed_show_values = array('capture', 'analyse');
  $show = empty($_REQUEST['show']) ? 'capture' : $_REQUEST['show'];
  if (!in_array($show, $allowed_show_values)) {
    header('HTTP/1.0 400 Bad Request');
    ZM\Error('Invalid show parameter: ' . preg_replace('/[^a-zA-Z0-9_-]/', '', $show));
    return;
  }

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
    // Per-event ACL: coarse Events/Snapshots role isn't enough, must also check
    // monitor-level permission (GHSA-vj5r-pc2v-gfwv). 404 to avoid leaking the id.
    if (!$Event->canView()) {
      header('HTTP/1.0 404 Not Found');
      ZM\Warning('Event '.$_REQUEST['eid'].' access denied');
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
            if($Event->DefaultVideo() !== 'index.m3u8') {
              $file_path = $Event->Path().'/'.$Event->DefaultVideo();
            } else {
              $file_path = $Event->Path().'/'.find_video($Event->Path());
            }

            if (!file_exists($file_path)) {
              if ($file = find_video($Event->Path())) {
                $file_path = $Event->Path().'/'.$file;
              }
            }
            if (file_exists($file_path)) {
              if ( !is_executable(ZM_PATH_FFMPEG) ) {
                header('HTTP/1.0 500 Internal Server Error');
                ZM\Error('ZM_PATH_FFMPEG is not a valid executable: '.ZM_PATH_FFMPEG);
                return;
              }

              create_frame_from_video($file_path, $path, $Frame->Delta());

              if ( $Event->DefaultVideo() !== 'index.m3u8' && ! file_exists($path) ) {
                header('HTTP/1.0 404 Not Found');
                ZM\Error('Can\'t create frame images from video for this event '.$Event->DefaultVideo());
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
    # A frame is only identified by its event and its number within the event.
    header('HTTP/1.0 404 Not Found');
    ZM\Error('No Event ID specified for frame '.validInt($_REQUEST['fid']));
    return;
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
        ZM\Warning("Can't create frame images from video because there is no video file for this event at (".$Event->Path().'/'.$Event->DefaultVideo() );
        return;
      }
      if ( !is_executable(ZM_PATH_FFMPEG) ) {
        header('HTTP/1.0 500 Internal Server Error');
        ZM\Error('ZM_PATH_FFMPEG is not a valid executable: '.ZM_PATH_FFMPEG);
        return;
      }

      create_frame_from_video($file_path, $path, $Frame->Delta());

      if ($Event->DefaultVideo() !== 'index.m3u8' && ! file_exists($path) ) {
        header('HTTP/1.0 404 Not Found');
        $message = 'Can\'t create frame images from video for this event '.$Event->DefaultVideo();
        if (str_contains($Event->DefaultVideo(), 'incomplete')) {
          ZM\Warning($message);
        } else {
          ZM\Error($message);
        }
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
  if (!file_exists($path)) {
    header('HTTP/1.0 404 Not Found');
    ZM\Warning("File '$path' cannot be locked because it does not exist.");
    return;
  }
  $fp_path = fopen($path, 'r');
  $lock = flock($fp_path, LOCK_SH);
  if (!$lock) ZM\Warning("Unable to get a read lock on $path, continuing.");

  header('Content-type: '.$media_type);
  header('Cache-Control: max-age=86400');
  header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60))); // Default set to 1 hour
  header('Pragma: cache');
  if ((($scale==0 || $scale==100) && ($width==0) && ($height==0)) or !function_exists('imagecreatefromjpeg')) {
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
    $scaled_path = preg_replace('/\.jpg$/', "-{$width}x{$height}.jpg", $path);
    if ($Event) {
      $filename = $Event->MonitorId().'_'.$Event->Id().'_'.$Frame->FrameId()."-{$width}x{$height}.jpg";
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
        if (PHP_MAJOR_VERSION < 8) { // imagedestroy() is deprecated since 8.0.0
          imagedestroy($i);
          imagedestroy($iScale);
        } else {
          unset($i);
          unset($iScale);
        }
        $scaled_jpeg_data = ob_get_contents();
        file_put_contents($scaled_path, $scaled_jpeg_data, LOCK_EX);

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

function extract_frame_from_video($ffmpeg, $file_path, $path, $frame_time, $message) {
  # Use a unique temporary file so a failed attempt cannot delete a file
  # created by another concurrent request for the same frame.
  $tmp_path = $path.'.'.getmypid().'.'.uniqid('', true).'.tmp.jpg';

  $command = $ffmpeg
    .' -ss '.escapeshellarg(sprintf('%.6F', $frame_time))
    .' -i '.escapeshellarg($file_path)
    .' -frames:v 1 -y '.escapeshellarg($tmp_path)
    .' 2>&1';

  ZM\Debug($message.$command);

  $output = array();
  $retval = 0;

  exec($command, $output, $retval);

  ZM\Debug(
    "Frame command: $command, retval: $retval, output: ".
    implode("\n", $output)
  );

  if ($retval === 0 && file_exists($tmp_path) && filesize($tmp_path) > 0) {
    if (@rename($tmp_path, $path)) {
      return true;
    }
  }

  if (file_exists($tmp_path)) @unlink($tmp_path);

  return false;
}

function find_video_packet_pts($ffprobe, $file_path, $frame_time, $lookback, $lookahead, $tried_pts = array()) {
  $interval_start = max(0, $frame_time - $lookback);
  $interval_duration = $frame_time + $lookahead - $interval_start;

  $probe_command = escapeshellarg($ffprobe)
    .' -v error'
    .' -read_intervals '
    .escapeshellarg(
        sprintf('%.6F%%+%.6F', $interval_start, $interval_duration)
      )
    .' -select_streams v:0'
    .' -show_packets'
    .' -show_entries packet=pts_time'
    .' -of csv=p=0 '
    .escapeshellarg($file_path);

  ZM\Debug("Finding video packets around $frame_time: $probe_command");

  $before_pts = null;
  $after_pts = null;

  $probe = popen($probe_command, 'r');
  if ($probe === false) {
    ZM\Warning("Unable to run ffprobe for $file_path");
    return array(null, null);
  }

  # Packet output is not guaranteed to be sorted by PTS.
  # Find the closest packet before and the closest packet after the requested timestamp.
  while (($line = fgets($probe)) !== false) {
    $line = trim($line);
    if ($line === '' || !is_numeric($line)) continue;

    $pts = (float)$line;
    $pts_key = sprintf('%.6F', $pts);

    if (isset($tried_pts[$pts_key])) continue;

    # Ignore a packet exactly at the requested timestamp because it was
    # already tried above. Only packets strictly before it can be a fallback.
    if ($pts <= $frame_time) {
      if ($pts < $frame_time &&
          ($before_pts === null || $pts > $before_pts)) {
        $before_pts = $pts;
      }
      continue;
    }

    if ($after_pts === null || $pts < $after_pts) {
      $after_pts = $pts;
    }
  }
  pclose($probe);

  return array($before_pts, $after_pts);
}

function create_frame_from_video($file_path, $path, $frame_time) {
  $ffmpeg = ZM_PATH_FFMPEG;

  # First try the requested timestamp.
  # Use escapeshellarg() to prevent command injection
  #$command ='ffmpeg -ss '. $Frame->Delta() .' -i '.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;
  #$command ='ffmpeg -v 0 -i '.$Storage->Path().'/'.$Event->Path().'/'.$Event->DefaultVideo().' -vf "select=gte(n\\,'.$Frame->FrameId().'),setpts=PTS-STARTPTS" '.$path;

  if (extract_frame_from_video(
      $ffmpeg,
      $file_path,
      $path,
      $frame_time,
      "Running "
    )) {
    return true;
  }

  # Derive ffprobe from ZM_PATH_FFMPEG.
  $ffprobe = preg_replace('/ffmpeg(\.exe)?$/i', 'ffprobe$1', $ffmpeg);

  if (!$ffprobe || !is_executable($ffprobe)) {
    ZM\Warning("ffprobe executable not found: $ffprobe");
    return false;
  }

  # Get video packet PTS values from 2 seconds before to 2 seconds after the requested timestamp.
  # This bounded 4-second window avoids scanning the entire video file.
  list($before_pts, $after_pts) = find_video_packet_pts(
    $ffprobe,
    $file_path,
    $frame_time,
    2,
    2
  );

  ZM\Debug(
    "Nearest video packet before requested time: ".
    ($before_pts === null ? 'none' : sprintf('%.6F', $before_pts))
  );

  ZM\Debug(
    "Nearest video packet at/after requested time: ".
    ($after_pts === null ? 'none' : sprintf('%.6F', $after_pts))
  );

  # Try the nearest packet before the requested timestamp, then the nearest packet at or after it.
  $tried_pts = array();

  foreach (array($before_pts, $after_pts) as $pts) {
    if ($pts === null) continue;

    $tried_pts[sprintf('%.6F', $pts)] = true;

    if (extract_frame_from_video(
        $ffmpeg,
        $file_path,
        $path,
        $pts,
        "Trying video frame at PTS ".sprintf('%.6F', $pts).": "
      )) {
      return true;
    }
  }

  # If the nearby packets could not be decoded, progressively expand the search backwards.
  $lookback = 4;

  while ($lookback > 0) {
    list($before_pts, $after_pts) = find_video_packet_pts(
      $ffprobe,
      $file_path,
      $frame_time,
      $lookback,
      2,
      $tried_pts
    );

    if ($before_pts === null) {
      if ($lookback >= $frame_time) break;

      $lookback *= 2;
      continue;
    }

    ZM\Debug(
      "Nearest untried video packet before requested time: ".
      sprintf('%.6F', $before_pts)
    );

    $tried_pts[sprintf('%.6F', $before_pts)] = true;

    if (extract_frame_from_video(
        $ffmpeg,
        $file_path,
        $path,
        $before_pts,
        "Trying expanded video frame at PTS ".sprintf('%.6F', $before_pts).": "
      )) {
      return true;
    }

    if ($lookback >= $frame_time) break;

    $lookback *= 2;
  }

  return false;
}

exit();
