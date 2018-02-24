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

if ( !canEdit( 'Monitors' ) ) {
  $view = 'error';
  return;
}

$cameras = array();
$cameras[0] = translate('ChooseDetectedCamera');

$profiles = array();
$profiles[0] = translate('ChooseDetectedProfile');

function execONVIF( $cmd ) {
  $shell_command = escapeshellcmd(ZM_PATH_BIN . "/zmonvif-probe.pl $cmd");

  exec( $shell_command, $output, $status );

  if ( $status ) {
    $html_output = implode( '<br/>', $output );
    Fatal( "Unable to probe network cameras, status is '$status'. Output was:<br/><br/>
        $html_output<br/><br/>
        Please the following command from a command line for more information:<br/><br/>$shell_command"
        );
  } else {
    Logger::Debug( "Results from probe: " . implode( '<br/>', $output ) );
  }

  return $output;
}

function probeCameras( $localIp ) {
  $cameras = array();
  if ( $lines = @execONVIF( 'probe' ) ) {
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match( '|^(.+),(.+),\s\((.*)\)$|', $line, $matches ) ) {
        $device_ep = $matches[1];
        $soapversion = $matches[2];
        $camera = array(
            'model'   => 'Unknown ONVIF Camera',
            'monitor' => array(
              'Function' => 'Monitor',
              'Type'     => 'Ffmpeg',
              'Host'     => $device_ep,
              'SOAP'     => $soapversion,
              ),
            );
        foreach ( preg_split('|,\s*|', $matches[3]) as $attr_val ) {
          if ( preg_match( '|(.+)=\'(.*)\'|', $attr_val, $tokens ) ) {
            if ( $tokens[1] == 'hardware' ) {
              $camera['model'] = $tokens[2];
            } elseif ( $tokens[1] == 'name' ) {
              $camera['monitor']['Name'] = $tokens[2];
            } elseif ( $tokens[1] == 'location' ) {
              //                      $camera['location'] = $tokens[2];
            } else {
              Logger::Debug('Unknown token ' . $tokens[1] );
            }
          }
        } // end foreach token
        $cameras[] = $camera;
      }
    } // end foreach line
  }
  return( $cameras );
}

function probeProfiles( $device_ep, $soapversion, $username, $password ) {
  $profiles = array();
  if ( $lines = @execONVIF( "profiles $device_ep $soapversion $username $password" ) ) {
    foreach ( $lines as $line ) {
      $line = rtrim( $line );
      if ( preg_match( '|^(.+),\s*(.+),\s*(.+),\s*(.+),\s*(.+),\s*(.+),\s*(.+)\s*$|', $line, $matches ) ) {
        $stream_uri = $matches[7];
        // add user@pass to URI
        if ( preg_match( '|^(\S+://)(.+)$|', $stream_uri, $tokens ) ) {
          $stream_uri = $tokens[1].$username.':'.$password.'@'.$tokens[2];
      }

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

            );
        $profiles[] = $profile;
      } else {
        Logger::Debug("Line did not match preg: $line");
      }
    }
  }
  return( $profiles );
}


//==== STEP 1 ============================================================

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('MonitorProbe') );

if( !isset($_REQUEST['step']) || ($_REQUEST['step'] == "1")) {

  $monitors = array();
  foreach ( dbFetchAll( "select Id, Name, Host from Monitors where Type = 'Remote' order by Host" ) as $monitor ) {
      if ( preg_match( '/^(.+)@(.+)$/', $monitor['Host'], $matches ) ) {
          //echo "1: ".$matches[2]." = ".gethostbyname($matches[2])."<br/>";
          $monitors[gethostbyname($matches[2])] = $monitor;
      } else {
          //echo "2: ".$monitor['Host']." = ".gethostbyname($monitor['Host'])."<br/>";
          $monitors[gethostbyname($monitor['Host'])] = $monitor;
      }
  }

  $detcameras = probeCameras( '' );
  foreach ( $detcameras as $camera ) {
    if ( preg_match( '|([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)|', $camera['monitor']['Host'], $matches ) ) {
      $ip = $matches[1];
    }
    $host = $ip;
/*
        if ( isset($monitors[$ip]) )
        {
            $monitor = $monitors[$ip];
            $sourceString .= " (".$monitor['Name'].")";
        }
        else
        {
            $sourceString .= " - ".translate('Available');
        }
        $cameras[$sourceDesc] = $sourceString;
    }
*/
//       $sourceDesc = htmlspecialchars(serialize($camera['monitor']));
       $sourceDesc = base64_encode(serialize($camera['monitor']));
       $sourceString = $camera['model'].' @ '.$host . ' using version ' . $camera['monitor']['SOAP'] ;
       $cameras[$sourceDesc] = $sourceString;
  }

  if ( count($cameras) <= 0 )
      $cameras[0] = translate('NoDetectedCameras');

?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('MonitorProbe') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?php echo validNum($_REQUEST['mid']) ?>"/>
        <input type="hidden" name="step" value=""/>
        <p>
          <?php echo translate('OnvifProbeIntro') ?>
        </p>
        <p>
          <label for="probe"><?php echo translate('DetectedCameras') ?></label><?php echo buildSelect( "probe", $cameras, 'configureButtons( this )' ); ?>
        </p>
        <p>
          <?php echo translate('OnvifCredentialsIntro') ?>
        </p>
        <p>
          <label for="username"><?php echo translate('Username') ?></label>
          <input type="text" name="username" value="" onChange="configureButtons( this )" />
        </p>
        <p>
          <label for="password"><?php echo translate('Password') ?></label>
          <input type="password" name="password" value=""onChange="configureButtons( this )" />
        </p>
        <div id="contentButtons">
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
          <input type="submit" name="nextBtn" value="<?php echo translate('Next') ?>" onclick="gotoStep2( this )" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
<?php

//==== STEP 2 ============================================================
} 
else if($_REQUEST['step'] == "2") 
{
  if ( empty($_REQUEST['probe']) ) 
    Fatal("No probe passed in request. Please go back and try again.");
#|| empty($_REQUEST['username']) || 
       #empty($_REQUEST['password']) )
    
  $probe = unserialize(base64_decode($_REQUEST['probe']));
  foreach ( $probe as $name=>$value ) {
      if ( isset($value) ) {
          $monitor[$name] = $value;
      }
  }
  $camera['monitor'] = $monitor;

  //print $monitor['Host'].", ".$_REQUEST['username'].", ".$_REQUEST['password']."<br/>";

  $detprofiles = probeProfiles( $monitor['Host'], $monitor['SOAP'], $_REQUEST['username'], $_REQUEST['password']);
  foreach ( $detprofiles as $profile ) {
       $monitor = $camera['monitor'];
       
       $sourceString = "${profile['Name']} : ${profile['Encoding']}" .
                       " (${profile['Width']}x${profile['Height']} @ ${profile['MaxFPS']}fps)";
       // copy technical details
       $monitor['Width']  = $profile['Width'];
       $monitor['Height'] = $profile['Height'];
// The maxfps fields do not work for ip streams. Can re-enable if that is fixed.
//       $monitor['MaxFPS'] = $profile['MaxFPS'];
//       $monitor['AlarmMaxFPS'] = $profile['AlarmMaxFPS'];
       $monitor['Path'] = $profile['Path'];
//       $sourceDesc = htmlspecialchars(serialize($monitor));
       $sourceDesc = base64_encode(serialize($monitor));
       $profiles[$sourceDesc] = $sourceString;
  }

  if ( count($profiles) <= 0 )
      $profiles[0] = translate('NoDetectedProfiles');

?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('ProfileProbe') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?php echo validNum($_REQUEST['mid']) ?>"/>
        <input type="hidden" name="step" value=""/>
        <p>
          <?php echo translate('ProfileProbeIntro') ?>
        </p>
        <p>
          <label for="probe"><?php echo translate('DetectedProfiles') ?></label><?php echo buildSelect( 'probe', $profiles, 'configureButtons( this )' ); ?>
        </p>
        <div id="contentButtons">
          <input type="button" name="prevBtn" value="<?php echo translate('Prev') ?>" onclick="gotoStep1( this )"/>
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
          <input type="submit" name="saveBtn" value="<?php echo translate('Save') ?>" onclick="submitCamera( this )" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
<?php
}
?>
