<?php
//
// ZoneMinder web monitor probe view file
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
//
ini_set('display_errors', '0');
require_once('Manufacturer.php');

// Probe Local Cameras
function probeV4L() {

  $cameras = array();
  $monitors = array();
  foreach ( ZM\Monitor::find(['Type'=>'Local'], ['order'=>'Device, Channel']) as $monitor )
    $monitors[$monitor->Device().':'.$monitor->Channel()] = $monitor;
  $devices = array();
  $devices_to_probe = array();
  $preferredStandards = array('PAL', 'NTSC');
  $preferredFormats = array('BGR3', 'RGB3', 'YUYV', 'UYVY', 'JPEG', 'MJPG', '422P', 'YU12', 'GREY');

  if ( !empty($_REQUEST['device']) ) {
    $devices_to_probe[] = $_REQUEST['device'];
  } else {
    $it = new FilesystemIterator('/dev/');
    foreach ($it as $fileinfo) {
      if (preg_match('/^video\d+$/', $fileinfo->getFilename())) {
        $devices_to_probe[] = '/dev/'.$fileinfo->getFilename();
      }
    }
  }

  foreach ($devices_to_probe as $d) {
    $command = getZmuCommand(' --query --device');
    $command .= '='.escapeshellarg($d);
    $result = exec(escapeshellcmd($command), $output, $status);
    if ($status) {
      ZM\Warning("Errors while probe local cameras using $command, status is '$status' " . implode("\n", $output));
    }

    foreach ( $output as $line ) {
      if ( !preg_match('/^d:([^|]+).*S:([^|]*).*F:([^|]+).*I:(\d+)\|(.+)$/', $line, $deviceMatches) ) {
        ZM\Error("Can't parse command output '$line'");
        continue;
      }
      $standards = explode('/', $deviceMatches[2]);
      $preferredStandard = false;
      foreach ( $preferredStandards as $standard ) {
        if ( in_array( $standard, $standards ) ) {
          $preferredStandard = $standard;
          break;
        }
      }
      $formats = explode('/', $deviceMatches[3]);
      $preferredFormat = false;
      foreach ( $preferredFormats as $format ) {
        if ( in_array($format, $formats) ) {
          $preferredFormat = $format;
          break;
        }
      }
      $device = array(
        'device'            => $deviceMatches[1],
        'standards'         => $standard,
        'preferredStandard' => $preferredStandard,
        'formats'           => $formats,
        'preferredFormat'   => $preferredFormat,
      );
      $inputs = array();
      for ( $i = 0; $i < $deviceMatches[4]; $i++ ) {
        if ( !preg_match('/i'.$i.':([^|]+)\|i'.$i.'T:([^|]+)\|/', $deviceMatches[5], $inputMatches) ) {
          ZM\Error("Can't parse input '".$deviceMatches[5]."'");
          continue;
        }
        if ( $inputMatches[2] == 'Camera' ) {
          $input = array(
            'index' => $i,
            'id'    => $deviceMatches[1].':'.$i,
            'name'  => $inputMatches[1],
            'free'  => empty($monitors[$deviceMatches[1].':'.$i]),
          );
          $inputMonitor = array(
            'Type'    => 'Local',
            'Device'  => $deviceMatches[1],
            'Channel' => $i,
            'Colours' => 3,
            'Format'  => $preferredStandard,
            'Palette' => $preferredFormat,
          );
          if ( $preferredStandard == 'NTSC' ) {
            $inputMonitor['Width'] = 320;
            $inputMonitor['Height'] = 240;
          } else {
            $inputMonitor['Width'] = 384;
            $inputMonitor['Height'] = 288;
          }
          if ( $preferredFormat == 'GREY' ) {
            $inputMonitor['Colours'] = 1;
            $inputMonitor['SignalCheckColour'] = '#000023';
          }
          $inputDesc = base64_encode(json_encode($inputMonitor));
          $inputString = $deviceMatches[1].', chan '.$i.($input['free']?(' - '.translate('Available')):(' ('.$monitors[$input['id']]->Name().')'));
          $inputs[] = $input;
          $cameras[$inputDesc] = $inputString;
        }
      }
      $device['inputs'] = $inputs;
      $devices[] = $device;
    } # end foreach output line
  } # end foreach device in /dev
  return $cameras;
} # end function probeV4L

// Probe Network Cameras
//
function probeAxis($ip, $username, $password) {
  if (!$username) $username = 'root';
  if (!$password) $password = 'password';
  $cameras = [];
  $camera = array(
    'ip'      => $ip,
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.'/axis-cgi/mjpg/video.cgi?resolution=320x240',
    'Manufacturer' => 'Axis',
    'Model'   => 'Unknown Model',
    'monitor' => array(
      'Path'    => 'rtsp://'.$username.':'.$password.'@'.$ip.'/cam/realmonitor?channel=1&subtype=0',
      'Manufacturer' => 'Axis',
    ),
  );

  $url = 'http://'.$ip.'/axis-cgi/admin/param.cgi?action=list&group=Brand';
  $content = wget('GET', $url, $username, $password);

  if ($content) {
    ZM\Debug($content);
    $lines = explode("\n", $content);
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match('/^(.+)=(.+)$/', $line, $matches) ) {
        if ( $matches[1] == 'root.Brand.ProdShortName' ) {
          $camera['Model'] = $camera['monitor']['Model'] = $matches[2];
          break;
        } else if ( $matches[1] == 'root.Image.I0.Appearance.Resolution' ) {
          $resolution = explode('x', $matches[2]);
          $camera['monitor']['Width'] = $resolution[0];
          $camera['monitor']['Height'] = $resolution[1];
        }
      }
    }
  }
  $cameras[] = $camera;
  return $cameras;
}

function probePanasonic($ip) {
  $cameras = [];
  $url = 'http://'.$ip.'/Get?Func=Model&Kind=1';
  $camera = array(
    'ip'      => $ip,
    'Model'   => 'Panasonic Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'http',
      'Host'     => $ip,
      'Port'     => 80,
      'Path'     => '/nphMotionJpeg?Resolution=320x240&Quality=Standard',
      'Colours'  => 3,
      'Width'    => 320,
      'Height'   => 240,
    ),
  );
  $cameras[] = $camera;
  return $cameras;
}

function probeActi($ip) {
  $cameras = [];
  $url = 'http://'.$ip.'/cgi-bin/system?USER=Admin&PWD=123456&SYSTEM_INFO';
  $camera = array(
    'ip'      => $ip,
    'Model'   => 'Acti Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'rtsp',
      'Method'   => 'rtpUni',
      'Host'     => 'Admin:123456@'.$ip,
      'Port'     => 7070,
      'Path'     => '',
      'Width'    => 320,
      'Height'   => 240,
    ),
  );
  if ( $lines = @file($url) ) {
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match('/^(.+?)\s*=\s*(.+)$/', $line, $matches) ) {
        if ( $matches[1] == 'Production ID' ) {
          $camera['Model'] = 'ACTi '.substr($matches[2], 0, strpos($matches[2], '-'));
          break;
        }
      }
    }
  }
  $cameras[] = $camera;
  return $cameras;
}

function probeAmcrestTechnologies($ip, $username='', $password='') {
  return probeAmcrest($ip, $username, $password);
}

function probeAmcrest($ip, $username='', $password='') {
  if (!$username) $username='admin';
  if (!$password) $password='password';
  $cameras = [];
  $url = 'rtsp://'.$username.':'.$password.'@'.$ip.':554//cam/realmonitor?channel=1&subtype=0&unicast=true';
  $camera = array(
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.'/cgi-bin/snapshot.cgi',
    'ip'      => $ip,
    'Manufacturer' => 'Amcrest',
    #'Model' => 'Amcrest Camera',
    'monitor' =>  array(
      'Type'  =>  'Ffmpeg',
      'Path' => $url,
      'Width'   =>  1920,
      'Height'  =>  1080,
    ),
  );
  $cameras[] = $camera;
  return $cameras;
}

function wget($method, $url, $username, $password) {
  exec("wget --keep-session-cookies -O - $url", $output, $result_code);
  return implode("\n", $output);
}

function curl($method, $url, $username, $password) {

    $ch = curl_init();
    #curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    #curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    #curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    #curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt( $curl_handle, CURLOPT_COOKIESESSION, true );

    $res = curl_exec($ch);
    ZM\Debug($res);
    $status = curl_getinfo($ch);
    ZM\Debug(print_r($status, true));
    preg_match('/WWW-Authenticate: Digest (.*)/', $res, $matches);
    if (!empty($matches)) {
      $auth_header = $matches[1];
      $auth_header_array = explode(',', $auth_header);
      $parsed = array();

      foreach ($auth_header_array as $pair) {
        $vals = explode('=', $pair);
        $parsed[trim($vals[0])] = trim($vals[1], '" ');
      }

      $response_realm     = (isset($parsed['realm'])) ? $parsed['realm'] : '';
      $response_nonce     = (isset($parsed['nonce'])) ? $parsed['nonce'] : '';
      $response_opaque    = (isset($parsed['opaque'])) ? $parsed['opaque'] : '';

      $authenticate1 = md5($username.':'.$response_realm.':'.$password);
      $authenticate2 = md5($method.':'.$url);

      $authenticate_response = md5($authenticate1.":".$response_nonce.":".$authenticate2);

      $request = sprintf('Authorization: Digest username="%s", realm="%s", nonce="%s", opaque="%s", uri="%s", response="%s"',
        $username, $response_realm, $response_nonce, $response_opaque, $url, $authenticate_response);
      ZM\Debug($request);

      $request_header = array($request);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
      $res = curl_exec($ch);
      ZM\Debug($res);
      $status = curl_getinfo($ch);
      ZM\Debug(print_r($status, true));
    }
    curl_close($ch);
    $headerSize = curl_getinfo( $ch , CURLINFO_HEADER_SIZE );
    $headerStr = substr( $res , 0 , $headerSize );
    $bodyStr = substr( $res , $headerSize );
    return $bodyStr;
}

function probeAzureWaveTechnologyInc($ip, $username, $password) {
}
function probeAvigilonCorporation($ip, $username, $password) {
  return probeAvigilon($ip, $username, $password);
}
function probeAvigilon($ip, $username, $password) {
  if (!$username) $username='administrator';
  #if (!$password) $password='';
  # Avigilon tends to want the : in auth even if no password.
  $auth = $username ? $username.':'.$password.'@' : '';
  $port_open = port_open($ip, 554);
  $cameras = [];
  $camera = [
    'ip' => $ip,
    'mjpegstream' => 'http://'.$auth.$ip.'/media/cam0/still.jpg',
    'Manufacturer'=>'Avigilon Corporation',
    'monitor' => [
      'Type'=>'Ffmpeg',
      'Path' => 'rtsp://'.$auth.$ip.'/rtsp/defaultPrimary?streamType=u',
      'User' => $username,
      'Pass' => $password,
      'Manufacturer'=>'Avigilon Corporation',
    ]
  ];
  # Urls: /cgi-x/get-compression?port=0
  # get-ptz-capabilities?port=0
  # /storage/status
  # /cgi-x/positon?port=0
  # /media/cam0/still.jpg
  # /cgi-x/get-general
  # /cgi-x/get-streamuri?port=0
  #
  # May have to login to onvif first, to get an auth cookie...
  # http://administrator@192.168.4.60/onvif/device_service
  //<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.onvif.org/ver10/device/wsdl"><s:Body><ns1:GetDeviceInformation></ns1:GetDeviceInformation></s:Body></s:Envelope>
  $url = 'http://'.$auth.$ip.'/cgi-x/get-general';
  $general_json = wget('GET', $url, $username, $password);
  if ($general_json) {
    $general = json_decode($general_json, true);
    ZM\Debug(print_r($general, true));
    if ($general) {
      $camera['Model'] = $camera['monitor']['Model'] = (string)$general['partNumber'];
      $camera['monitor']['ONVIF_URL'] = 'http://'.$username.':'.$password.'@'.$ip.'/onvif/device_service';
    }
  }
  $url = 'http://'.$auth.$ip.'/cgi-x/get-streamuri?port=0';
  $json = wget('GET', $url, $username, $password);
  if ($json) {
    $stream_uri = json_decode($json, true);
    if ($stream_uri) {
      $camera['monitor']['Path'] = $stream_uri['streamUri-unicast'];
    }
  }
  $url = 'http://'.$auth.$ip.'/cgi-x/get-compression?port=0';
  $compression_json = wget('GET', $url, $username, $password);
  if ($compression_json) {
    $compression = json_decode($compression_json, true);
    if ($compression) {
      ZM\Debug(print_r($compression, true));

      $encodings = [];
      foreach ($compression['optionsEncoding'] as $encoding) {
        $encodings[$encoding[1]] = $encoding[0];
      }
      $h264options = [];
      foreach ($compression['h264Options'] as $options) {
        foreach ($options as $option) {
          $h264options[$option[1]] = $option[0];
        }
      }
      $h265options = [];
      foreach ($compression['h265Options'] as $options) {
        foreach ($options as $option) {
          $h265options[$option[1]] = $option[0];
        }
      }
      ZM\Debug(print_r($encodings, true));
      $encoding = $encodings[$compression['defaultEncoding']];
      $resolution = '';
      if ($encoding == 'H.264') {
        $resolution = $h264options[$compression['defaultResolution']];
      } else if ($encoding == 'H.265') {
        $resolution = $h265options[$compression['defaultResolution']];
      }
      if ($resolution) {
        $resolution = explode('x', $resolution);
        $camera['monitor']['Width']  = $resolution[0];
        $camera['monitor']['Height']  = $resolution[1];
      }
    }
  }

  $cameras[] = $camera;
  return $cameras;
} # End probeAvigilon

function probeHangzhouHikvisionDigitalTechnologyCoLtd($ip, $username, $password) {
  return probeHikvision($ip, $username, $password);
}
function probeHikvision($ip, $username, $password) {
  if (!$username) $username='admin';
  if (!$password) $password='password';
  $cameras = [];
  $port_open = port_open($ip, 554);
  $url = 'rtsp://'.$username.':'.$password.'@'.$ip.':554/Streaming/Channels/101?transportmode=unicast';
  $camera = array(
    'ip'      => $ip,
    'mjpegstream' => 'https://'.$username.':'.$password.'@'.$ip.'/ISAPI/streaming/channels/1/picture',
    'Manufacturer'  => 'Hikvision',
    'Model'         => ($port_open ? 'Camera' : 'Not Camera'),
    'monitor' =>  array(
      'Type'  =>  'Ffmpeg',
      'Path' => $url,
      'Width'   =>  1920,
      'Height'  =>  1080,
      'Manufacturer'  => 'Hikvision',
    ),
  );
  $url = 'http://'.$username.':'.$password.'@'.$ip.'/ISAPI/Streaming/channels/101';
  $xml_str = wget('GET', $url, $username, $password);
  if ($xml_str) {
    $xml = new SimpleXMLElement($xml_str);
    if ($xml->Video) {
      ZM\Debug(print_r($xml->Video, true));
      $camera['monitor']['Width'] = (int) $xml->Video->videoResolutionWidth;
      $camera['monitor']['Height'] = (int) $xml->Video->videoResolutionHeight;
      $camera['monitor']['Name'] = (string) $xml->channelName;
      $camera['Codec'] = (string) $xml->Video->videoCodecType;
    } else {
      ZM\Debug("No Video in xml".print_r($xml_str, true));
    }
    
  } else {
    ZM\Debug("No xml from $url");
  }

  $url = 'http://'.$username.':'.$password.'@'.$ip.'/ISAPI/System/deviceInfo';
  $xml_str = wget('GET', $url, $username, $password);
  if ($xml_str) {
    ZM\Debug($xml_str);
    $xml = new SimpleXMLElement($xml_str);
    $camera['Model'] = (string) $xml->model;
    $camera['Name'] = (string) $xml->deviceName;
    ZM\Debug(print_r($xml, true));
  } else {
    ZM\Debug("No xml from $url");
  }

  $cameras[] = $camera;
  return $cameras;
}

function probeUbiquitiNetworksInc($ip, $username, $password) {
  return probeUbiquiti($ip, $username, $password);
}

function port_open($ip, $port) {
  $fp = @fsockopen($ip, $port, $errno, $errstr, 1);
  if (!$fp) {
    return false;
  }
  fclose($fp);
  return true;
}

function probeUbiquiti($ip, $username, $password) {
  if (!$username) $username='ubnt';
  if (!$password) $password='ubnt';
  $port_open = port_open($ip, 554);
  $cameras = [];
  $camera = [
    'ip'      => $ip,
    'Model' => ($port_open ? 'Ubiquiti Camera' : 'Unknown'),
    'Manufacturer'  => 'Ubiquiti',
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.'/snap.jpeg',
    'monitor' => [
      'Type' => 'Ffmpeg',
      'Path' => 'rtsp://'.$username.':'.$password.'@'.$ip.':554/s0',
      'Width'   => 1920,
      'Height'  => 1080,
      'Manufacturer'  => 'Ubiquiti',
    ]
  ];
  $cameras[] = $camera;
  return $cameras;
}

function probeFoscam($ip, $username, $password) {
  if ($username === null) $username = 'admin';

  $rtsp_port = 0;
  $http_port = 0;
  if (port_open($ip, 554)) {
	  $rtsp_port = 554;
  }
  if (port_open($ip, 88)) {
	  $http_port = 88;
  } else if (port_open($ip, 80)) {
	  $http_port = 80;
  }
  if (!$rtsp_port) $rtsp_port = $http_port;
  $cameras = [];
  $camera = array(
    'ip'      => $ip,
    'Name'   => 'Foscam Camera',
    'Manufacturer'  => 'Foscam',
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.':'.$http_port.'/videostream.cgi',
    'monitor' => array(
      'Manufacturer'  => 'Foscam',
      'Type'     => 'Ffmpeg',
      'Path' => ($rtsp_port == 554 ? 'rtsp' : 'http').'://'.$username.':'.$password.'@'.$ip.':'.$rtsp_port.'/videoMain',
      'Host'    => $ip,
      'Width'	=> 640,
      'Height'	=> 480,
    ),
  );
  if (!count($cameras)) {
    $cameras[] = $camera;
  }

  return $cameras;
}

function probeDLinkInternational($ip, $username, $password) {
  if ($username === null) $username = 'root';
  if ($password === null) $password = '';
  $cameras = [];
  $camera = array(
    'ip'      => $ip,
    'Name'   => 'DLink Camera',
    'Manufacturer'  => 'D-Link',
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.'/video.cgi',
    'monitor' => array(
      'Manufacturer'  => 'D-Link',
      'Type'     => 'Ffmpeg',
      'Path'     => 'http://'.$ip.'/video.cgi',
      'Host'     => $ip,
      'Width'	=> 640,
      'Height'	=> 480,
    ),
  );
}
function probeVivotek($ip, $username, $password) {
  if ($username === null) $username = 'root';
  if ($password === null) $password = '';

  $control = ZM\Control::find_one(['Name'=>'Vivotek ePTZ']);

  $cameras = [];
  $camera = array(
    'ip'      => $ip,
    'Name'   => 'Vivotek Camera',
    'Manufacturer'  => 'Vivotek',
    'mjpegstream' => 'http://'.$username.':'.$password.'@'.$ip.'/cgi-bin/viewer/video.jpg',
    'monitor' => array(
      'Manufacturer'  => 'Vivotek',
      'Type'     => 'Ffmpeg',
      'Path'     => 'rtsp://'.$ip.'/',
      'Host'     => $ip,
      'ControlId'=> ($control?$control->Id():''),
    ),
  );

  $settings = [];
  $authority = $username.':'.$password.'@'.$ip;
  $url = 'http://'.$authority.'/cgi-bin/viewer/getparam.cgi';
  $content = wget('GET', $url, $username, $password);

  #try {
    #$content = do_request('GET', $url);
    if ($content) {
      ZM\Debug($content);
      $lines = explode("\n", $content);
      #if ($lines = @file($url)) {
      foreach ($lines as $line) {
        $line = rtrim($line);
        if (preg_match('/^(.+?)\s*=\'(.+)\'$/', $line, $matches)) {
          $settings[$matches[1]] = $matches[2];
        }
      }
    } else {
      ZM\Debug("Failed to load config from camera using $url");
    }
  #} catch (Exception $e) {
    #ZM\Debug($e->getMessage());
  #}

  if (!empty($settings['system_info_modelname'])) {
    $camera['Model'] = $settings['system_info_modelname'];
    $camera['monitor']['Model'] = $settings['system_info_modelname'];
  }
  if (!empty($settings['videoin_text'])) {
    $camera['Name'] = $settings['videoin_text'];
    $camera['monitor']['Name'] = $settings['videoin_text'];
  } else if (!empty($settings['system_hostname'])) {
    $camera['Name'] = $settings['system_hostname'];
    $camera['monitor']['Name'] = $settings['system_hostname'];
  }

  if (!empty($settings['network_rtsp_port'])) {
    $camera['monitor']['Port'] = $settings['network_rtsp_port'];
  }
  foreach (['0', '1', '2'] as $i) {
    if (!empty($settings['network_rtsp_s'.$i.'_accessname'])) {
      $camera['monitor']['Path'] = 'rtsp://'.$authority.'/'.$settings['network_rtsp_s'.$i.'_accessname'];
      if (!empty($settings['videoin_c0_s'.$i.'_resolution'])) {
        $res = explode('x', $settings['videoin_c0_s'.$i.'_resolution']);
        $camera['monitor']['Width'] = $res[0];
        $camera['monitor']['Height'] = $res[1];
      }
      if (!empty($settings['videoin_c0_s'.$i.'_codectype'])) {
        $camera['Codec'] = $settings['videoin_c0_s'.$i.'_codectype'];
        if ($camera['Codec'] == 'mjpeg') {
          if (!empty($settings['network_http_s'.$i.'_accessname'])) {
            $camera['mjpegstream'] = 'http://'.$authority.'/'.$settings['network_http_s'.$i.'_accessname'];
          }
        }
      }
      $cameras[] = $camera;
    } # has accessname
  }
  if (!count($cameras)) {
    ZM\Debug("Failed getting streams, adding default");
    $cameras[] = $camera;
  }

  return $cameras;
}

function probeWansview($ip) {
  $cameras = [];
  $camera = array(
    'ip'      => $ip,
    'Model'   => 'Wansview Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'http',
      'Host'     => 'admin:123456@'.$ip,
      'Port'     => 80,
      'Path'     => 'videostream.cgi',
      'Width'    => 640,
      'Height'   => 480,
      'Palette'  => 3
    ),
  );
  $cameras[] = $camera;
  return $camera;
}

function get_arp_results() {
  $results = array();
  $arp_command = ZM_PATH_ARP;
  $result = explode(' ', $arp_command);
  if ( !is_executable($result[0]) ) {
    ZM\Error('ARP compatible binary not found or not executable by the web user account. Verify ZM_PATH_ARP points to a valid arp tool.');
    return $results;
  }
  if (count($result)==1) {
    $arp_command .= ' -n';
  }

  $result = exec(escapeshellcmd($arp_command), $output, $status);
  if ($status) {
    ZM\Error("Unable to probe network cameras, status is '$status'");
    return $results;
  }
  foreach ($output as $line) {
    if ( !preg_match('/(\d+\.\d+\.\d+\.\d+).*(([0-9a-f]{2}:){5})/', $line, $matches) ) {
      ZM\Debug("Didn't match preg $line");
      continue;
    }
    $results[$matches[2]] = $matches[1]; // results[mac] = ip
  }
  return $results;
}

function get_arp_scan_results($network) {
  ZM\Debug("arp-scanning $network");
  $results = array();
  $arp_scan_command = ZM_PATH_ARP_SCAN;
  $result = explode(' ', $arp_scan_command);
  if (!is_executable($result[0])) {
    ZM\Error('arp-scan compatible binary not found or not executable by the web user account. Verify ZM_PATH_ARP_SCAN points to a valid arp-scan tool.');
    return $results;
  }
  $arp_scan_command = '/usr/bin/pkexec '.ZM_PATH_ARP_SCAN.' '.$network;
  $result = exec(escapeshellcmd($arp_scan_command), $output, $status);
  if ($status) {
    ZM\Error("Unable to probe network cameras, command was $arp_scan_command, status is '$status' output: ".implode(PHP_EOL, $output));
    return $results;
  }
  ZM\Debug(print_r($output, true));
  foreach ($output as $line) {
    if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+([0-9a-f:]+)/', $line, $matches)) {
      $results[$matches[2]] = $matches[1];
    } else {
      ZM\Debug("Didn't match preg $line");
    }
  }
  ZM\Debug(print_r($results, true));
  return $results;
} # end function get_arp_scan_results

function probeNetwork() {
  $username = empty($_REQUEST['probe_username']) ? null : $_REQUEST['probe_username'];
  $password = empty($_REQUEST['probe_password']) ? null : $_REQUEST['probe_password'];
  $interface = isset($_REQUEST['interface']) ? $_REQUEST['interface'] : null;
  $filter_ip = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : null;
  $filter_manufacturer = isset($_REQUEST['probe_Manufacturer']) ? $_REQUEST['probe_Manufacturer'] : null;
  #$filter_manufacturer = $filter_manufacturer_id > 0 ? (new ZM\Manufacturer($filter_manufacturer_id))->Name() : '';
  #ZM\Debug("$filter_manufacturer_id, $filter_manufacturer");

  $cameras = array();
  $results = array();

  $monitors = array();
  foreach (ZM\Monitor::find(['Type'=>'Remote']) as $monitor) {
    if ( preg_match('/^(.+)@(.+)$/', $monitor->Host(), $matches) ) {
      //echo "1: ".$matches[2]." = ".gethostbyname($matches[2])."<br/>";
      $monitors[gethostbyname($matches[2])] = $monitor;
    } else {
      //echo "2: ".$monitor['Host']." = ".gethostbyname($monitor['Host'])."<br/>";
      $monitors[gethostbyname($monitor->Host())] = $monitor;
    }
  }
  foreach (ZM\Monitor::find(['Type'=>'Ffmpeg']) as $monitor) {
    $url_parts = parse_url($monitor->Path());
    if ($url_parts !== false) {
      #ZM\Debug("Ffmpeg monitor ${url_parts['host']} = ${monitor['Id']} ${monitor['Name']}");
      $monitors[gethostbyname($url_parts['host'])] = $monitor;
    } else {
      ZM\Debug('Unable to parse '.$monitor->Path());
    }
  }
  //ZM\Debug(print_r($monitors, true));

  $macVendors = file_get_contents(ZM_PATH_DATA.'/MacVendors.json');
  if (!$macVendors) {
    ZM\Warning('No content from '.ZM_PATH_DATA.'/MacVendors.json');
  }
  $macBases = json_decode($macVendors, true);
  $oui_txt = file_get_contents('/usr/share/arp-scan/ieee-oui.txt');
  if (!$oui_txt) {
    ZM\Warning('No content from /usr/share/arp-scan/ieee-oui.txt');
  } else {
    foreach (explode(PHP_EOL, $oui_txt) as $line) {
      if (false === strpos($line , '#')) {
        $record = explode("\t", $line);
        if (count($record) < 2) continue;
        $mac = strtolower($record[0]);
        $type = preg_replace('/\W/', '', $record[1]);
        if (!isset($macBases[$mac]))
          $macBases[$mac] = [ 'vendor'=>$record[1], 'type'=>$type];
      }
    }
    #ZM\Debug("bases: " . print_r($macBases, true));
  }

  foreach (get_arp_results() as $mac=>$ip) {
    if ($filter_ip and ($ip != $filter_ip)) {
      ZM\Debug("Skipping $mac $ip because of ip_filter $filter_ip");
      continue;
    }
    
    $macRoot = str_replace(':', '', substr($mac, 0, 8));
    if (isset($macBases[$macRoot])) {
      ZM\Debug("Have match for $ip $mac $macRoot ".$macBases[$macRoot]['type']);
      $macBase = $macBases[$macRoot];
      if ($filter_manufacturer and ($filter_manufacturer != $macBase['type'])) {
	      ZM\Debug("Continuing because offilter $filter_manufacturer == ".$macBase['type']);
	      continue;
      } else {
	      ZM\Debug("Not Continuing because offilter $filter_manufacturer != ".$macBase['type']);
      }
      if (function_exists('probe'.$macBase['type'])) {
        if (!$username and isset($monitors[$ip])) {
          $new_cameras = call_user_func('probe'.$macBase['type'], $ip, $monitors[$ip]->User(), $monitors[$ip]->Pass());
          if (!$new_cameras) {
            ZM\Warning('probe'.$macBase['type'].' returned nothing');
          } else {
            $cameras = array_merge($cameras, $new_cameras);
          }
        } else {
          ZM\Debug("Not Using auth from monitor $ip $username $password");
          $cameras = array_merge($cameras, call_user_func('probe'.$macBase['type'], $ip, $username, $password));
        }
      } else {
        ZM\Debug("No probe function for ${macBase['type']}");
        $cameras = array_merge($cameras, [['ip'=>$ip, 'Manufacturer'=>$macBase['vendor']]]);
      }
    } else {
      ZM\Debug("No match for $ip $macRoot");
    }
    if (connection_aborted()) exit();
  } # end foreach output line

  $interfaces = $interface ? $interface : get_networks();
  foreach ($interfaces as $interface) {
    foreach (get_subnets($interface) as $network) {
      foreach (get_arp_scan_results($network) as $mac=>$ip) {
        if ($filter_ip and ($ip != $filter_ip)) {
          ZM\Debug("Skipping $mac $ip because of ip_filter $filter_ip");
          continue;
        }
        $macRoot = str_replace(':', '', substr($mac, 0, 8));
        #ZM\Debug("Got $macRoot from $mac");
        if (isset($macBases[$macRoot])) {
          ZM\Debug("Have match for $macRoot $ip ".$macBases[$macRoot]['type']);
          $macBase = $macBases[$macRoot];
          if ($filter_manufacturer and ($filter_manufacturer != $macBase['type'])) {
            ZM\Debug("Continuing because offilter $filter_manufacturer == ".$macBase['type']);
            continue;
          }
          if ($macBase['type'] != 'Unknown' and function_exists('probe'.$macBase['type'])) {
            ZM\Debug("Calling ".$macBase['type']);
            $found_cameras = [];
            if (!$username and isset($monitors[$ip])) {
              $found_cameras = call_user_func('probe'.$macBase['type'], $ip, $monitors[$ip]->User(), $monitors[$ip]->Pass());
            } else {
              ZM\Debug("Not Using auth from monitor $ip $username $password");
              $found_cameras = call_user_func('probe'.$macBase['type'], $ip, $username, $password);
            }
            if (count($found_cameras)) {
              $cameras = array_merge($cameras, $found_cameras);
            } else {
              ZM\Debug("DIdn't find any cameras");
            }
          } else {
            $cameras = array_merge($cameras, [['ip'=>$ip, 'Manufacturer'=>$macBase['vendor']]]);
            ZM\Debug("No probe function for ${macBase['type']} ${macBase['vendor']}");
          }
        } else {
          ZM\Debug("No match for $macRoot");
          $cameras = array_merge($cameras, [['ip'=>$ip, 'Manufacturer'=>'Unknown']]);
        }
        if (connection_aborted()) exit();
      } # end foreach output line
    } # end foreach network
  } # foreach interface

  $url_filter = [];
  foreach ($cameras as $camera) {
    if (isset($camera['monitor']))  {
      if (isset($url_filter[$camera['monitor']['Path']])) continue;
      $url_filter[$camera['monitor']['Path']] = 1;
    }
    if (!is_array($camera)) {
      ZM\Error("What is ".print_r($camera, true));
      continue;
    }

    $ip = $camera['ip'];
    $sourceString = (isset($camera['Model']) ? ($camera['Model'].' @ '):'').$ip;
    $monitor = null;
    if (isset($monitors[$ip])) {
      $monitor = $monitors[$ip];
      $sourceString .= ' ('.$monitor->Name().')';
    } else {
      $sourceString .= ' - '.translate('Available');
    }

    $results[] = [
      'description' => $sourceString,
      'url'         => (isset($camera['monitor']) ? $camera['monitor']['Path'] : ''),
      'IP'          => $camera['ip'],
      'camera'      => $camera,
      'Monitor'     => $monitor,
    ];
  } # end foreach stream
  return $results;
} # end function probeNetwork()
