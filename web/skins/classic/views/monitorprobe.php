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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canEdit( 'Monitors' ) )
{
    $view = "error";
    return;
}

$cameras = array();
$cameras[0] = $SLANG['ChooseDetectedCamera'];

if ( ZM_V4L2 )
{
    // Probe Local Cameras 
    //
    $command = getZmuCommand( " --query --device" );
    if ( !empty($_REQUEST['device']) )
        $command .= "=".escapeshellarg($_REQUEST['device']);

    $result = exec( escapeshellcmd($command), $output, $status );
    if ( $status )
        die( "Unable to probe local cameras, status is '$status'" );

    $monitors = array();
    foreach ( dbFetchAll( "select Id, Name, Device,Channel from Monitors where Type = 'Local' order by Device, Channel" ) as $monitor )
        $monitors[$monitor['Device'].':'.$monitor['Channel']] = $monitor;

    $devices = array();
    $preferredStandards = array( 'PAL', 'NTSC' );
    $preferredFormats = array( '422P', 'YUYV', 'BGR3' );
    foreach ( $output as $line )
    {
        if ( !preg_match( '/^d:([^|]+).*S:([^|]+).*F:([^|]+).*I:(\d+)\|(.+)$/', $line, $deviceMatches ) )
            die( "Can't parse command output '$line'" );
        $standards = split('/',$deviceMatches[2]);
        $preferredStandard = false;
        foreach ( $preferredStandards as $standard )
        {
            if ( in_array( $standard, $standards ) )
            {
                $preferredStandard = $standard;
                break;
            }
        }
        $formats = split('/',$deviceMatches[3]);
        $preferredFormat = false;
        foreach ( $preferredFormats as $format )
        {
            if ( in_array( $format, $formats ) )
            {
                $preferredFormat = $format;
                break;
            }
        }
        $device = array( 'device'=>$deviceMatches[1], 'standards'=>$standard, 'preferredStandard'=>$preferredStandard, 'formats'=>$formats, 'preferredFormat'=>$preferredFormat );
        $inputs = array();
        for ( $i = 0; $i < $deviceMatches[4]; $i++ )
        {
            if ( !preg_match( '/i'.$i.':([^|]+)\|i'.$i.'T:([^|]+)\|/', $deviceMatches[5], $inputMatches ) )
                die( "Can't parse input '".$deviceMatches[5]."'" );
            if ( $inputMatches[2] == 'Camera' )
            {
                $input = array( 'index'=>$i, 'id'=>$deviceMatches[1].':'.$i, 'name'=>$inputMatches[1], 'free'=>empty($monitors[$deviceMatches[1].':'.$i]) );
                $inputMonitor = array(
                    'Type'    => 'Local',
                    'Device'  => $deviceMatches[1],
                    'Channel' => $i,
                    'Format'  => $preferredStandard,
                    'Palette' => $preferredFormat,
                );
                if ( $preferredStandard == 'NTSC' )
                {
                    $inputMonitor['Width'] = 320;
                    $inputMonitor['Height'] = 240;
                }
                else
                {
                    $inputMonitor['Width'] = 352;
                    $inputMonitor['Height'] = 288;
                }
                $inputDesc = htmlspecialchars(serialize($inputMonitor));
                $inputString = $deviceMatches[1].', chan '.$i.($input['free']?(" - ".$SLANG['Available']):(" (".$monitors[$input['id']]['Name'].")"));
                $inputs[] = $input;
                $cameras[$inputDesc] = $inputString;
            }
        }
        $device['inputs'] = $inputs;
        $devices[] = $device;
    }
}

// Probe Network Cameras
//
function probeAxis( $ip )
{
    $url = 'http://'.$ip.'/axis-cgi/admin/param.cgi?action=list&group=Brand';
    $camera = array(
        'model'   => "Unknown Axis Camera",
        'monitor' => array(
            'Type'     => 'Remote',
            'Protocol' => 'http',
            'Host'     => $ip,
            'Port'     => 80,
            'Path'     => '/axis-cgi/mjpg/video.cgi?resolution=320x240',
            'Palette'  => 3,
            'Width'    => 320,
            'Height'   => 240,
        ),
    );
    if ( $lines = @file( $url ) )
    {
        foreach ( $lines as $line )
        {
            $line = rtrim( $line );
            if ( preg_match( '/^(.+)=(.+)$/', $line, $matches ) )
            {
                if ( $matches[1] == 'root.Brand.ProdShortName' )
                {
                    $camera['model'] = $matches[2];
                    break;
                }
            }
        }
    }
    return( $camera );
}

function probePana( $ip )
{
    $url = 'http://'.$ip.'/Get?Func=Model&Kind=1';
    $camera = array(
        'model'   => "Unknown Panasonic Camera",
        'monitor' => array(
            'Type'     => 'Remote',
            'Protocol' => 'http',
            'Host'     => $ip,
            'Port'     => 80,
            'Path'     => '/nphMotionJpeg?Resolution=320x240&Quality=Standard',
            'Palette'  => 3,
            'Width'    => 320,
            'Height'   => 240,
        ),
    );
    return( $camera );
}

function probeActi( $ip )
{
    $url = 'http://'.$ip.'/cgi-bin/system?USER=Admin&PWD=123456&SYSTEM_INFO';
    $camera = array(
        'model'   => "Unknown Panasonic Camera",
        'monitor' => array(
            'Type'     => 'Remote',
            'Protocol' => 'rtsp',
            'Method'   => 'rtpUni',
            'Host'     => 'Admin:123456@'.$ip,
            'Port'     => 7070,
            'Path'     => '',
            'Palette'  => 3,
            'Width'    => 320,
            'Height'   => 240,
        ),
    );
    if ( $lines = @file( $url ) )
    {
        foreach ( $lines as $line )
        {
            $line = rtrim( $line );
            if ( preg_match( '/^(.+?)\s*=\s*(.+)$/', $line, $matches ) )
            {
                if ( $matches[1] == 'Production ID' )
                {
                    $camera['model'] = "ACTi ".substr( $matches[2], 0, strpos( $matches[2], '-' ));
                    break;
                }
            }
        }
    }
    return( $camera );
}

function probeVivotek( $ip )
{
    $url = 'http://'.$ip.'/cgi-bin/viewer/getparam.cgi';
    $camera = array(
        'model'   => "Unknown Vivotek Camera",
        'monitor' => array(
            'Type'     => 'Remote',
            'Protocol' => 'rtsp',
            'Method'   => 'rtpUni',
            'Host'     => $ip,
            'Port'     => 554,
            'Path'     => '',
            'Palette'  => 3,
            'Width'    => 352,
            'Height'   => 240,
        ),
    );
    if ( $lines = @file( $url ) )
    {
        foreach ( $lines as $line )
        {
            $line = rtrim( $line );
            if ( preg_match( '/^(.+?)\s*=\'(.+)\'$/', $line, $matches ) )
            {
                if ( $matches[1] == 'system_info_modelname' )
                {
                    $camera['model'] = "Vivotek ".$matches[2];
                }
                elseif ( $matches[1] == 'network_rtsp_port' )
                {
                    $camera['monitor']['Port'] = $matches[2];
                }
                elseif ( $matches[1] == 'network_rtsp_s0_accessname' )
                {
                    $camera['monitor']['Path'] = $matches[2];
                }
            }
        }
    }
    return( $camera );
}

$monitors = array();
foreach ( dbFetchAll( "select Id, Name, Host from Monitors where Type = 'Remote' order by Host" ) as $monitor )
{
    if ( preg_match( '/^(.+)@(.+)$/', $monitor['Host'], $matches ) )
    {
        //echo "1: ".$matches[2]." = ".gethostbyname($matches[2])."<br/>";
        $monitors[gethostbyname($matches[2])] = $monitor;
    }
    else
    {
        //echo "2: ".$monitor['Host']." = ".gethostbyname($monitor['Host'])."<br/>";
        $monitors[gethostbyname($monitor['Host'])] = $monitor;
    }
}
$macBases = array(
    '00:40:8c' => array( 'type'=>'Axis', 'probeFunc'=>'probeAxis' ),
    '00:80:f0' => array( 'type'=>'Panasonic','probeFunc'=>'probePana' ),
    '00:0f:7c' => array( 'type'=>'ACTi','probeFunc'=>'probeACTi' ),
    '00:02:d1' => array( 'type'=>'Vivotek','probeFunc'=>'probeVivotek' ),
);

unset($output);
$command = "arp -a";
$result = exec( escapeshellcmd($command), $output, $status );
if ( $status )
    die( "Unable to probe network cameras, status is '$status'" );
foreach ( $output as $line )
{
    if ( !preg_match( '/^(\S+) \(([\d.]+)\) at ([0-9a-f:]+)/', $line, $matches ) )
        next;
        //die( "Can't parse command output '$line'" );
    $host = $matches[1];
    $ip = $matches[2];
    if ( !$host || $host == '?' )
        $host = $ip;
    $mac = $matches[3];
    //echo "I:$ip, H:$host, M:$mac<br/>";
    $macRoot = substr($mac,0,8);
    if ( isset($macBases[$macRoot]) )
    {
        $macBase = $macBases[$macRoot];
        $camera = call_user_func( $macBase['probeFunc'], $ip );
        $sourceDesc = htmlspecialchars(serialize($camera['monitor']));
        $sourceString = $camera['model'].' @ '.$host;
        if ( isset($monitors[$ip]) )
        {
            $monitor = $monitors[$ip];
            $sourceString .= " (".$monitor['Name'].")";
        }
        else
        {
            $sourceString .= " - ".$SLANG['Available'];
        }
        $cameras[$sourceDesc] = $sourceString;
    }
}

if ( count($cameras) <= 0 )
    $cameras[0] = $SLANG['NoDetectedCameras'];

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['MonitorProbe'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['MonitorProbe'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?= validNum($_REQUEST['mid']) ?>"/>
        <p>
          <?= $SLANG['MonitorProbeIntro'] ?>
        </p>
        <p>
          <label for="probe"><?= $SLANG['DetectedCameras'] ?></label><?= buildSelect( "probe", $cameras, 'configureButtons( this )' ); ?>
        </p>
        <div id="contentButtons">
          <input type="submit" name="saveBtn" value="<?= $SLANG['Save'] ?>" onclick="submitCamera( this )" disabled="disabled"/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
