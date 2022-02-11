<?php
//
// ZoneMinder web monitor probe view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

if (!canEdit('Monitors')) {
  $view = 'error';
  return;
}

// Probe Local Cameras
function probeV4L() {

  $cameras = array();

  $command = getZmuCommand(' --query --device');
  if ( !empty($_REQUEST['device']) )
    $command .= '='.escapeshellarg($_REQUEST['device']);
  $result = exec(escapeshellcmd($command), $output, $status);
  if ($status) {
    ZM\Warning("Errors while probe local cameras using $command, status is '$status' " . implode("\n", $output));
  }

  $monitors = array();
  foreach ( dbFetchAll("SELECT Id, Name, Device, Channel FROM Monitors WHERE Type = 'Local' ORDER BY Device, Channel" ) as $monitor )
    $monitors[$monitor['Device'].':'.$monitor['Channel']] = $monitor;

  $devices = array();
  $preferredStandards = array('PAL', 'NTSC');
  $preferredFormats = array('BGR3', 'RGB3', 'YUYV', 'UYVY', 'JPEG', 'MJPG', '422P', 'YU12', 'GREY');
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
        $inputString = $deviceMatches[1].', chan '.$i.($input['free']?(' - '.translate('Available')):(' ('.$monitors[$input['id']]['Name'].')'));
        $inputs[] = $input;
        $cameras[$inputDesc] = $inputString;
      }
    }
    $device['inputs'] = $inputs;
    $devices[] = $device;
  } # end foreach output line
  return $cameras;
} # end function probeV4L

// Probe Network Cameras
//
function probeAxis($ip) {
  $url = 'http://'.$ip.'/axis-cgi/admin/param.cgi?action=list&group=Brand';
  $camera = array(
    'model'   => 'Unknown Axis Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'http',
      'Host'     => $ip,
      'Port'     => 80,
      'Path'     => '/axis-cgi/mjpg/video.cgi?resolution=320x240',
      'Colours'  => 3,
      'Width'    => 320,
      'Height'   => 240,
    ),
  );
  if ( $lines = @file($url) ) {
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match('/^(.+)=(.+)$/', $line, $matches) ) {
        if ( $matches[1] == 'root.Brand.ProdShortName' ) {
          $camera['model'] = $matches[2];
          break;
        }
      }
    }
  }
  return $camera;
}

function probePana($ip) {
  $url = 'http://'.$ip.'/Get?Func=Model&Kind=1';
  $camera = array(
    'model'   => 'Unknown Panasonic Camera',
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
  return $camera;
}

function probeActi($ip) {
  $url = 'http://'.$ip.'/cgi-bin/system?USER=Admin&PWD=123456&SYSTEM_INFO';
  $camera = array(
    'model'   => 'Unknown Panasonic Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'rtsp',
      'Method'   => 'rtpUni',
      'Host'     => 'Admin:123456@'.$ip,
      'Port'     => 7070,
      'Path'     => '',
      'Colours'  => 3,
      'Width'    => 320,
      'Height'   => 240,
    ),
  );
  if ( $lines = @file($url) ) {
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match('/^(.+?)\s*=\s*(.+)$/', $line, $matches) ) {
        if ( $matches[1] == 'Production ID' ) {
          $camera['model'] = 'ACTi '.substr($matches[2], 0, strpos($matches[2], '-'));
          break;
        }
      }
    }
  }
  return $camera;
}

function probeHikvision($ip) {
  $url = 'rtsp://admin:password@'.$ip.':554/Streaming/Channels/101?transportmode=unicast';
  $camera = array(
    'model' => 'Unknown Hikvision Camera',
    'monitor' =>  array(
      'Type'  =>  'FFmpeg',
      'Path' => $url,
      'Colours' =>  4,
      'Width'   =>  1920,
      'Height'  =>  1080,
    ),
  );
  return $camera;
}

function probeVivotek($ip) {
  $url = 'http://'.$ip.'/cgi-bin/viewer/getparam.cgi';
  $camera = array(
    'model'   => 'Unknown Vivotek Camera',
    'monitor' => array(
      'Type'     => 'Remote',
      'Protocol' => 'rtsp',
      'Method'   => 'rtpUni',
      'Host'     => $ip,
      'Port'     => 554,
      'Path'     => '',
      'Colours'  => 3,
      'Width'    => 352,
      'Height'   => 240,
    ),
  );
  if ( $lines = @file($url) ) {
    foreach ( $lines as $line ) {
      $line = rtrim($line);
      if ( preg_match('/^(.+?)\s*=\'(.+)\'$/', $line, $matches) ) {
        if ( $matches[1] == 'system_info_modelname' ) {
          $camera['model'] = 'Vivotek '.$matches[2];
        } elseif ( $matches[1] == 'network_rtsp_port' ) {
          $camera['monitor']['Port'] = $matches[2];
        } elseif ( $matches[1] == 'network_rtsp_s0_accessname' ) {
          $camera['monitor']['Path'] = $matches[2];
        }
      }
    }
  }
  return $camera;
}

function probeWansview($ip) {
  $camera = array(
    'model'   => 'Wansview Camera',
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
  $arp_scan_command = '/usr/bin/pkexec '.ZM_PATH_ARP_SCAN.' '.$network.' 2>&1';
  $result = exec(escapeshellcmd($arp_scan_command), $output, $status);
  if ($status) {
    ZM\Error("Unable to probe network cameras, command was $arp_scan_command, status is '$status' output: ".implode(PHP_EOL, $output));
    return $results;
  }
  foreach ($output as $line) {
    if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+(([0-9a-f]{2}:){5})/', $line, $matches)) {
      $results[$matches[2]] = $matches[1];
    } else {
      ZM\Debug("Didn't match preg $line");
    }
  }
  return $results;
}

function probeNetwork() {
  $cameras = array();

  $monitors = array();
  foreach ( dbFetchAll("SELECT `Id`, `Name`, `Host` FROM `Monitors` WHERE `Type` = 'Remote' ORDER BY `Host`") as $monitor ) {
    if ( preg_match('/^(.+)@(.+)$/', $monitor['Host'], $matches) ) {
      //echo "1: ".$matches[2]." = ".gethostbyname($matches[2])."<br/>";
      $monitors[gethostbyname($matches[2])] = $monitor;
    } else {
      //echo "2: ".$monitor['Host']." = ".gethostbyname($monitor['Host'])."<br/>";
      $monitors[gethostbyname($monitor['Host'])] = $monitor;
    }
  }
  foreach ( dbFetchAll("SELECT `Id`, `Name`, `Path` FROM `Monitors` WHERE `Type` = 'Ffmpeg' ORDER BY `Path`") as $monitor ) {
    $url_parts = parse_url($monitor['Path']);
    ZM\Debug("Ffmpeg monitor ${url_parts['host']} = ${monitor['Id']} ${monitor['Name']}");
    $monitors[gethostbyname($url_parts['host'])] = $monitor;
  }

  $macBases = array(
    '00:0f:7c' => array('type'=>'ACTi','probeFunc'=>'probeACTi'),
    '00:40:8c' => array('type'=>'Axis', 'probeFunc'=>'probeAxis'),
    '2c:a5:9c' => array('type'=>'Hikvision', 'probeFunc'=>'probeHikvision'),
    '00:80:f0' => array('type'=>'Panasonic','probeFunc'=>'probePana'),
    '00:02:d1' => array('type'=>'Vivotek','probeFunc'=>'probeVivotek'),
    '7c:dd:90' => array('type'=>'Wansview','probeFunc'=>'probeWansview'),
    '78:a5:dd' => array('type'=>'Wansview','probeFunc'=>'probeWansview')
  );

  foreach ( get_arp_results() as $mac=>$ip ) {
    $macRoot = substr($mac,0,8);
    if ( isset($macBases[$macRoot]) ) {
      ZM\Debug("Have match for $macRoot ".$macBases[$macRoot]['type']);
      $macBase = $macBases[$macRoot];
      $camera = call_user_func($macBase['probeFunc'], $ip);
      $sourceDesc = base64_encode(json_encode($camera['monitor']));
      $sourceString = $camera['model'].' @ '.$host;
      if ( isset($monitors[$ip]) ) {
        $monitor = $monitors[$ip];
        $sourceString .= ' ('.$monitor['Name'].')';
      } else {
        $sourceString .= ' - '.translate('Available');
      }
      $cameras[$sourceDesc] = $sourceString;
    } else {
      ZM\Debug("No match for $macRoot");
    }
  } # end foreach output line

  if (isset($_REQUEST['interface']) and $_REQUEST['interface']) {
    foreach (get_subnets($_REQUEST['interface']) as $network) {
      foreach ( get_arp_scan_results($network) as $mac=>$ip ) {
        $macRoot = substr($mac,0,8);
        ZM\Debug("Got $macRoot from $mac");
        if (isset($macBases[$macRoot])) {
          ZM\Debug("Have match for $macRoot $ip ".$macBases[$macRoot]['type']);
          $macBase = $macBases[$macRoot];
          $camera = call_user_func($macBase['probeFunc'], $ip);
          $sourceDesc = base64_encode(json_encode($camera['monitor']));
          $sourceString = $camera['model'].' @ '.$host;
          if (isset($monitors[$ip])) {
            $monitor = $monitors[$ip];
            $sourceString .= ' ('.$monitor['Name'].')';
          } else {
            $sourceString .= ' - '.translate('Available');
          }
          $cameras[$sourceDesc] = $sourceString;
        } else {
          ZM\Debug("No match for $macRoot");
        }
      } # end foreach output line
    } # end foreach network
  } # end if we have a network specified

  return $cameras;
} # end function probeNetwork()

$cameras = array();
$cameras[0] = translate('ChooseDetectedCamera');

if ( ZM_HAS_V4L2 )
    $cameras += probeV4L();
$cameras += probeNetwork();

if ( count($cameras) <= 1 )
  $cameras[0] = translate('NoDetectedCameras');

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('MonitorProbe') );
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <h2><?php echo translate('MonitorProbe') ?></h2>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="?">
        <input type="hidden" name="mid" value="<?php echo isset($_REQUEST['mid'])?validNum($_REQUEST['mid']):'' ?>"/>
        <input type="hidden" name="view" value="monitorprobe"/>
    
        <p>
          <?php echo translate('MonitorProbeIntro') ?>
        </p>
<p><label for="interface"><?php echo translate('Interface') ?></label>
<?php
$interfaces = array('', 'select');
  $interfaces += get_networks();
  $default_interface = $interfaces['default'];
  unset($interfaces['default']);

  echo htmlSelect('interface', $interfaces,
    (isset($_REQUEST['interface']) ? $_REQUEST['interface'] : $default_interface),
    array('data-on-change-this'=>'changeInterface') );

?>
        </p>
        <p>
          <label for="probe"><?php echo translate('DetectedCameras') ?></label>
          <?php echo htmlSelect('probe', $cameras, null, array('data-on-change-this'=>'configureButtons')); ?>
        </p>
        <div id="contentButtons">
        <button type="button" name="saveBtn" value="Save" data-on-click-this="submitCamera" disabled="disabled">
        <?php echo translate('Save') ?></button>
        <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
