<?php
//
// ZoneMinder web ONVIF probe view file, $Date: 2014-07-05 $, $Revision: 1 $
// Copyright (C) Jan M. Hochstein
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

$cameras = array();
$cameras[0] = translate('ChooseDetectedCamera');

$profiles = array();
$profiles[0] = translate('ChooseDetectedProfile');

function execONVIF($cmd) {
  $shell_command = escapeshellcmd(ZM_PATH_BIN . "/zmonvif-probe.pl $cmd");

  exec($shell_command, $output, $status);

  if ($status) {
    $html_output = implode('<br/>', $output);
    ZM\Error("Unable to probe network cameras, status is '$status'. Output was:
        $html_output
        Please run the following command from a command line for more information:
        $shell_command"
        );
  } else {
    ZM\Debug('Results from probe: '.implode('<br/>', $output));
  }

  return $output;
}

function probeCameras($localIp) {
  $cameras = array();
  $lines = @execONVIF('probe 1.1,1.2'.(isset($_REQUEST['interface']) ? ' '.$_REQUEST['interface'] : '' ));
  if ($lines) {
    foreach ($lines as $line) {
      $line = rtrim($line);
      if (preg_match('|^(.+),(.+),\s\((.*)\)$|', $line, $matches)) {
        $device_ep = $matches[1];
        $soapversion = $matches[2];
        $camera = array(
            'model'   => 'Unknown ONVIF Camera',
            'monitor' => array(
              'Function' => 'Monitor',
              'Type'     => 'Ffmpeg',
              'Host'     => $device_ep,
              'SOAP'     => $soapversion,
              'ConfigURL' => $device_ep,
              'ConfigOptions' => 'SOAP' . $soapversion,
              'Notes'     =>  '',
              ),
            );
        foreach ( preg_split('|,\s*|', $matches[3]) as $attr_val ) {
          if ( preg_match('|(.+)=\'(.*)\'|', $attr_val, $tokens) ) {
            if ( $tokens[1] == 'hardware' ) {
              $camera['model'] = $tokens[2];
            } else if ( $tokens[1] == 'name' ) {
              $camera['monitor']['Name'] = $tokens[2];
            } else if ( $tokens[1] == 'type' ) {
            } else if ( $tokens[1] == 'location' or $tokens[1] == 'location/city' or $tokens[1] == 'location/country' ) {
              $camera['monitor']['Notes'] .= $tokens[1].'='.$tokens[2]."\n";
              // $camera['location'] = $tokens[2];
            } else {
              ZM\Debug('Unknown token '.$tokens[1].' = '.$tokens[2]);
            }
          }
        } // end foreach token
        $cameras[] = $camera;
      }
    } // end foreach line
  } // end if results from execOnvif
  return $cameras;
} // end function probeCameras

function probeProfiles($device_ep, $soapversion, $username, $password) {
  $profiles = array();
  if ($lines = @execONVIF("profiles $device_ep $soapversion $username $password")) {
    foreach ($lines as $line) {
      $line = rtrim( $line );

      if ( preg_match('|^([^,]+),\s*([^,]+),\s*([^,]+),\s*(\d+),\s*(\d+),\s*(\d+),\s*([^,]+),\s*(.+)\s*$|', $line, $matches) ) {
        $stream_uri = $matches[8];
        // add user@pass to URI
        if ( preg_match('|^(\S+://)(.+)$|', $stream_uri, $tokens) ) {
          // The password could contain a slash(/) or other special
          // characters; so, it must be URL encoded, when it gets
          // stored in a monitor's configuration.  However, the rest
          // of the stream_uri (Path) should *not* be stored URL encoded
          // in the monitor's configuration.
          // When Path is POSTed, the entire stream_uri value will be
          // URL encoded, which is quite appropriate; on the receiving
          // end, the entire value of the Path attribute will be URL
          // decoded, which is also quite appropriate.  However, that
          // also applies to the password sub-field of the Path
          // attribute.  So, we must double encode the embedded password.
          $stream_uri = $tokens[1].$username.':'.urlencode(urlencode($password)).'@'.$tokens[2];
        }
        ZM\Debug(print_r($matches, true));

        $profile = array(  # 'monitor' part of camera
            'Type'        => 'Ffmpeg',
            'Width'       => $matches[4],
            'Height'      => $matches[5],
            'MaxFPS'      => $matches[6],
            'Path'        => $stream_uri,
            // local-only:
            'Profile'     => $matches[1],
            'Name'        => $matches[2],
            'Encoding'    => $matches[3],
            'Transport'	  => $matches[7],
            );
        $profiles[] = $profile;
      } else {
        ZM\Debug("Line did not match preg: $line");
      }
    } // end foreach line
  } // end if results from execONVIF
  return $profiles;
} // end function probeProfiles

//==== STEP 1 ============================================================

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('MonitorProbe'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
<?php
if (!isset($_REQUEST['step']) || ($_REQUEST['step'] == '1')) {
  $monitors = array();
  foreach ( dbFetchAll("SELECT Id, Name, Host FROM Monitors WHERE Type='Remote' ORDER BY Host") as $monitor ) {
    if ( preg_match('/^(.+)@(.+)$/', $monitor['Host'], $matches) ) {
      //echo "1: ".$matches[2]." = ".gethostbyname($matches[2])."<br/>";
      $monitors[gethostbyname($matches[2])] = $monitor;
    } else {
      //echo "2: ".$monitor['Host']." = ".gethostbyname($monitor['Host'])."<br/>";
      $monitors[gethostbyname($monitor['Host'])] = $monitor;
    }
  }

  $detcameras = probeCameras('');
  foreach ($detcameras as $camera) {
    if (preg_match('|([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)|', $camera['monitor']['Host'], $matches)) {
      $ip = $matches[1];
    }
    $host = $ip;
    $sourceDesc = base64_encode(json_encode($camera['monitor']));
    $sourceString = $camera['model'].' @ '.$host.' using version '.$camera['monitor']['SOAP'];
    $cameras[$sourceDesc] = $sourceString;
  }

  if (count($cameras) <= 0)
    $cameras[0] = translate('NoDetectedCameras');
?>
    <h2><?php echo translate('MonitorProbe') ?></h2>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?php echo validNum($_REQUEST['mid']) ?>"/>
        <input type="hidden" name="step" value=""/>
        <p>
          <?php echo translate('OnvifProbeIntro') ?>
        </p>
        <p><label for="interface"><?php echo translate('Interface') ?></label>
<?php 
  $interfaces = get_networks();
  $default_interface = $interfaces['default'];
  unset($interfaces['default']);

  echo htmlSelect('interface', $interfaces, 
    (isset($_REQUEST['interface']) ? $_REQUEST['interface'] : $default_interface),
    array('data-on-change-this'=>'changeInterface') );
?>
        </p>
        <div id="DetectedCameras">
          <p>
            <label for="probe"><?php echo translate('DetectedCameras') ?></label>
            <?php echo htmlSelect('probe', $cameras, null, array('data-on-change-this'=>'configureButtons')); ?>
          </p>
          <p>
            <?php echo translate('OnvifCredentialsIntro') ?>
          </p>
          <p>
            <label for="Username"><?php echo translate('Username') ?></label>
            <input type="text" name="Username" data-on-change-this="configureButtons"/>
          </p>
          <p>
            <label for="Password"><?php echo translate('Password') ?></label>
            <input type="password" name="Password" id="Password" data-on-change-this="configureButtons"/>
            <span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="Password">visibility</span>
          </p>
        </div>
        <div id="contentButtons">
          <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
          <button type="button" name="nextBtn" data-on-click-this="gotoStep2" disabled="disabled"><?php echo translate('Next') ?></button>
        </div>
      </form>
    </div>
  </div>
<?php

//==== STEP 2 ============================================================
} else if ($_REQUEST['step'] == '2') {
  if (empty($_REQUEST['probe'])) 
    ZM\Fatal('No probe passed in request. Please go back and try again.');
    
  $probe = json_decode(base64_decode($_REQUEST['probe']));
  ZM\Debug(print_r($probe, true));
  foreach ($probe as $name=>$value) {
    if (isset($value)) {
      $monitor[$name] = $value;
    }
  }
  $camera['monitor'] = $monitor;

  //print $monitor['Host'].", ".$_REQUEST['username'].", ".$_REQUEST['password']."<br/>";

  $detprofiles = probeProfiles($monitor['Host'], $monitor['SOAP'], $_REQUEST['Username'], $_REQUEST['Password']);
  foreach ($detprofiles as $profile) {
    $monitor = $camera['monitor'];

    $sourceString = "${profile['Name']} : ${profile['Encoding']}" .
      " (${profile['Width']}x${profile['Height']} @ ${profile['MaxFPS']}fps ${profile['Transport']})";
    // copy technical details
    $monitor['Width']  = $profile['Width'];
    $monitor['Height'] = $profile['Height'];
    if ( $profile['Transport'] == 'RTP-Multicast' ) {
	    $monitor['Method'] = 'rtpMulti';
    } else if ( $profile['Transport'] == 'RTP-Unicast' ) {
	    $monitor['Method'] = 'rtpUni';
    } else {
	    $monitor['Method'] = 'rtpRtsp';
    }
    // The maxfps fields do not work for ip streams. Can re-enable if that is fixed.
    //       $monitor['MaxFPS'] = $profile['MaxFPS'];
    //       $monitor['AlarmMaxFPS'] = $profile['AlarmMaxFPS'];
    $monitor['Path'] = $profile['Path'];
    $monitor['ControlDevice'] = $profile['Profile']; # Netcat needs this for ProfileToken
    $sourceDesc = base64_encode(json_encode($monitor));
    $profiles[$sourceDesc] = $sourceString;
  }

  if (count($profiles) <= 0)
    $profiles[0] = translate('NoDetectedProfiles');
?>
    <div id="header">
      <h2><?php echo translate('ProfileProbe') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?php echo validNum($_REQUEST['mid']) ?>"/>
        <input type="hidden" name="step"/>
        <p>
          <?php echo translate('ProfileProbeIntro') ?>
        </p>
        <p>
          <label for="probe"><?php echo translate('DetectedProfiles') ?></label>
          <?php echo htmlSelect('probe', $profiles, null, array('data-on-change-this'=>'configureButtons')); ?>
        </p>
        <div id="contentButtons">
          <button type="button" name="prevBtn" data-on-click-this="gotoStep1"><?php echo translate('Prev') ?></button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Cancel') ?></button>
          <button type="button" name="saveBtn" data-on-click-this="submitCamera" disabled="disabled"><?php echo translate('Save') ?></button>
        </div>
      </form>
    </div>
  </div>
<?php
} // end if step 1 or 2
?>
<?php xhtmlFooter() ?>
