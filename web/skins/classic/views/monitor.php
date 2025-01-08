<?php
//
// ZoneMinder web monitor view file, $Date$, $Revision$
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

require_once('includes/Server.php');
require_once('includes/Storage.php');

if ( !canEdit('Monitors', empty($_REQUEST['mid'])?0:$_REQUEST['mid']) ) {
  $view = 'error';
  return;
}

$Server = null;
if ( defined('ZM_SERVER_ID') ) {
  $Server = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, array(ZM_SERVER_ID));
}
if ( !$Server ) {
  $Server = array('Id' => '');
}
$mid = null;
$monitor = null;
if ( !empty($_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  $monitor = new ZM\Monitor($mid);
  if ( $monitor and ZM_OPT_X10 )
    $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($mid));
}

if ( !$monitor ) {
  $monitor = new ZM\Monitor();
  $monitor->Name(translate('Monitor').'-'.getTableAutoInc('Monitors'));
  $monitor->WebColour(random_colour());
} # end if $_REQUEST['mid']

if ( isset($_REQUEST['dupId']) ) {
  $monitor = new ZM\Monitor($_REQUEST['dupId']);
  $monitor->GroupIds(); // have to load before we change the Id
  if ( ZM_OPT_X10 )
    $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($_REQUEST['dupId']));
  $clonedName = $monitor->Name();
  $monitor->Name('Clone of '.$monitor->Name());
  $monitor->Id($mid);
}

if ( ZM_OPT_X10 && empty($x10Monitor) ) {
  $x10Monitor = array(
      'Activation' => '',
      'AlarmInput' => '',
      'AlarmOutput' => '',
      );
}

function fourcc($a, $b, $c, $d) {
  return ord($a) | (ord($b) << 8) | (ord($c) << 16) | (ord($d) << 24);
}
if ( isset($_REQUEST['newMonitor']) ) {
  # Update the monitor object with whatever has been set so far.
  $monitor->set($_REQUEST['newMonitor']);

  if ( ZM_OPT_X10 )
    $newX10Monitor = $_REQUEST['newX10Monitor'];
} else {
  if ( ZM_OPT_X10 )
    $newX10Monitor = $x10Monitor;
}

# What if it has less zeros?  This is not robust code.
if ( $monitor->AnalysisFPSLimit() == '0.00' )
  $monitor->AnalysisFPSLimit('');
if ( $monitor->MaxFPS() == '0.00' )
  $monitor->MaxFPS('');
if ( $monitor->AlarmMaxFPS() == '0.00' )
  $monitor->AlarmMaxFPS('');

if ( !empty($_REQUEST['preset']) ) {
  $preset = dbFetchOne( 'SELECT Type, Device, Channel, Format, Protocol, Method, Host, Port, Path, Width, Height, Palette, MaxFPS, Controllable, ControlId, ControlDevice, ControlAddress, DefaultRate, DefaultScale FROM MonitorPresets WHERE Id = ?', NULL, array($_REQUEST['preset']) );
  foreach ( $preset as $name=>$value ) {
    # Does isset handle NULL's?  I don't think this code is correct.
    # Icon: It does, but this means we can't set a null value.
    if ( isset($value) ) {
      $monitor->$name($value);
    }
  }
} # end if preset

if ( !empty($_REQUEST['probe']) ) {
  $probe = json_decode(base64_decode($_REQUEST['probe']));
  foreach ( $probe as $name=>$value ) {
    if ( isset($value) ) {
      # Does isset handle NULL's?  I don't think this code is correct.
      $monitor->$name = urldecode($value);
    }
  }
  if ( ZM_HAS_V4L && $monitor->Type() == 'Local' ) {
    $monitor->Palette( fourCC( substr($monitor->Palette,0,1), substr($monitor->Palette,1,1), substr($monitor->Palette,2,1), substr($monitor->Palette,3,1) ) );
    if ( $monitor->Format() == 'PAL' )
      $monitor->Format( 0x000000ff );
    elseif ( $monitor->Format() == 'NTSC' )
      $monitor->Format( 0x0000b000 );
  }
} # end if apply probe settings

$sourceTypes = array(
    'Local'  => translate('Local'),
    'Remote' => translate('Remote'),
    'File'   => translate('File'),
    'Ffmpeg' => translate('Ffmpeg'),
    'Libvlc' => translate('Libvlc'),
    'cURL'   => 'cURL (HTTP(S) only)',
    'WebSite'=> 'Web Site',
    'NVSocket'	=> translate('NVSocket'),
    'VNC' => translate('VNC'),
    );
if ( !ZM_HAS_V4L )
  unset($sourceTypes['Local']);

$localMethods = array(
    'v4l2' => 'Video For Linux version 2',
    'v4l1' => 'Video For Linux version 1',
    );

if ( !ZM_HAS_V4L2 )
  unset($localMethods['v4l2']);
if ( !ZM_HAS_V4L1 )
  unset($localMethods['v4l1']);

$remoteProtocols = array(
    'http' => 'HTTP',
    'rtsp' => 'RTSP'
    );

$rtspMethods = array(
    'rtpUni'      => 'RTP/Unicast',
    'rtpMulti'    => 'RTP/Multicast',
    'rtpRtsp'     => 'RTP/RTSP',
    'rtpRtspHttp' => 'RTP/RTSP/HTTP'
    );

$rtspFFMpegMethods = array(
    'rtpRtsp'     => 'TCP',
    'rtpUni'      => 'UDP',
    'rtpMulti'    => 'UDP Multicast',
    'rtpRtspHttp' => 'HTTP Tunnel'
    );

$httpMethods = array(
    'simple'   => 'Simple',
    'regexp'   => 'Regexp',
    'jpegTags' => 'JPEG Tags'
    );

if ( !ZM_PCRE )
  unset($httpMethods['regexp']);
  // Currently unsupported
unset($httpMethods['jpegTags']);

if ( ZM_HAS_V4L1 ) {
  $v4l1DeviceFormats = array(
      0 => 'PAL',
      1 => 'NTSC',
      2 => 'SECAM',
      3 => 'AUTO',
      4 => 'FMT4',
      5 => 'FMT5',
      6 => 'FMT6',
      7 => 'FMT7'
      );

  $v4l1MaxChannels = 15;
  $v4l1DeviceChannels = array();
  for ( $i = 0; $i <= $v4l1MaxChannels; $i++ )
    $v4l1DeviceChannels[$i] = $i;

  $v4l1LocalPalettes = array(
      1  => translate('Grey'),
      5  => 'BGR32',
      4  => 'BGR24',
      8  => '*YUYV',
      3  => '*RGB565',
      6  => '*RGB555',
      7  => '*YUV422',
      13 => '*YUV422P',
      15 => '*YUV420P',
      );
}

if ( ZM_HAS_V4L2 ) {
  $v4l2DeviceFormats = array(
    0x000000ff => 'PAL',
    0x0000b000 => 'NTSC',
    0x00000001 => 'PAL B',
    0x00000002 => 'PAL B1',
    0x00000004 => 'PAL G',
    0x00000008 => 'PAL H',
    0x00000010 => 'PAL I',
    0x00000020 => 'PAL D',
    0x00000040 => 'PAL D1',
    0x00000080 => 'PAL K',
    0x00000100 => 'PAL M',
    0x00000200 => 'PAL N',
    0x00000400 => 'PAL Nc',
    0x00000800 => 'PAL 60',
    0x00001000 => 'NTSC M',
    0x00002000 => 'NTSC M JP',
    0x00004000 => 'NTSC 443',
    0x00008000 => 'NTSC M KR',
    0x00010000 => 'SECAM B',
    0x00020000 => 'SECAM D',
    0x00040000 => 'SECAM G',
    0x00080000 => 'SECAM H',
    0x00100000 => 'SECAM K',
    0x00200000 => 'SECAM K1',
    0x00400000 => 'SECAM L',
    0x00800000 => 'SECAM LC',
    0x01000000 => 'ATSC 8 VSB',
    0x02000000 => 'ATSC 16 VSB',
      );

  $v4l2MaxChannels = 31;
  $v4l2DeviceChannels = array();
  for ( $i = 0; $i <= $v4l2MaxChannels; $i++ )
    $v4l2DeviceChannels[$i] = $i;

  $v4l2LocalPalettes = array(
      0 => 'Auto', /* Automatic palette selection */

      /*  FOURCC              =>  Pixel format         depth  Description  */
      fourcc('G','R','E','Y') =>  translate('Grey'), /*  8  Greyscale     */
      fourcc('B','G','R','4') => 'BGR32', /* 32  BGR-8-8-8-8   */
      fourcc('R','G','B','4') => 'RGB32', /* 32  RGB-8-8-8-8   */
      fourcc('B','G','R','3') => 'BGR24', /* 24  BGR-8-8-8     */
      fourcc('R','G','B','3') => 'RGB24', /* 24  RGB-8-8-8     */
      fourcc('Y','U','Y','V') => '*YUYV', /* 16  YUV 4:2:2     */

      /* compressed formats */
      fourcc('J','P','E','G') => '*JPEG',  /* JFIF JPEG     */
      fourcc('M','J','P','G') => '*MJPEG', /* Motion-JPEG   */
      // fourcc('d','v','s','d') => 'DV',  /* 1394          */
      // fourcc('M','P','E','G') => 'MPEG', /* MPEG-1/2/4    */

      //
      fourcc('R','G','B','1') =>  'RGB332', /*  8  RGB-3-3-2     */
      fourcc('R','4','4','4') => '*RGB444', /* 16  xxxxrrrr ggggbbbb */
      fourcc('R','G','B','O') => '*RGB555', /* 16  RGB-5-5-5     */
      fourcc('R','G','B','P') => '*RGB565', /* 16  RGB-5-6-5     */
      // fourcc('R','G','B','Q') => 'RGB555X', /* 16  RGB-5-5-5 BE  */
      // fourcc('R','G','B','R') => 'RGB565X', /* 16  RGB-5-6-5 BE  */
      // fourcc('Y','1','6','')  => 'Y16',     /* 16  Greyscale     */
      // fourcc('P','A','L','8') => 'PAL8',    /*  8  8-bit palette */
      // fourcc('Y','V','U','9') => 'YVU410',  /*  9  YVU 4:1:0     */
      // fourcc('Y','V','1','2') => 'YVU420',  /* 12  YVU 4:2:0     */

      fourcc('U','Y','V','Y') => '*UYVY',      /* 16  YUV 4:2:2     */
      fourcc('4','2','2','P') => '*YUV422P',   /* 16  YVU422 planar */
      fourcc('4','1','1','P') => '*YUV411P',   /* 16  YVU411 planar */
      // fourcc('Y','4','1','P') => 'Y41P',    /* 12  YUV 4:1:1     */
      fourcc('Y','4','4','4') => '*YUV444',    /* 16  xxxxyyyy uuuuvvvv */
      // fourcc('Y','U','V','O') => 'YUV555',  /* 16  YUV-5-5-5     */
      // fourcc('Y','U','V','P') => 'YUV565',  /* 16  YUV-5-6-5     */
      // fourcc('Y','U','V','4') => 'YUV32',   /* 32  YUV-8-8-8-8   */

      /* two planes -- one Y, one Cr + Cb interleaved  */
      fourcc('N','V','1','2') => 'NV12', /* 12  Y/CbCr 4:2:0  */
      // fourcc('N','V','2','1') => 'NV21', /* 12  Y/CrCb 4:2:0  */

      /*  The following formats are not defined in the V4L2 specification */
      fourcc('Y','U','V','9') => '*YUV410', /*  9  YUV 4:1:0     */
      fourcc('Y','U','1','2') => '*YUV420', /* 12  YUV 4:2:0     */
      // fourcc('Y','Y','U','V') => 'YYUV', /* 16  YUV 4:2:2     */
      // fourcc('H','I','2','4') => 'HI240',   /*  8  8-bit color   */
      // fourcc('H','M','1','2') => 'HM12',  /*  8  YUV 4:2:0 16x16 macroblocks */

      /* see http://www.siliconimaging.com/RGB%20Bayer.htm */
      // fourcc('B','A','8','1') => 'SBGGR8', /*  8  BGBG.. GRGR.. */
      // fourcc('G','B','R','G') => 'SGBRG8', /*  8  GBGB.. RGRG.. */
      // fourcc('B','Y','R','2') => 'SBGGR16', /* 16  BGBG.. GRGR.. */

      /*  Vendor-specific formats   */
      //'WNVA' =>     fourcc('W','N','V','A'), /* Winnov hw compress */
      //'SN9C10X' =>  fourcc('S','9','1','0'), /* SN9C10x compression */
      //'PWC1' =>     fourcc('P','W','C','1'), /* pwc older webcam */
      //'PWC2' =>     fourcc('P','W','C','2'), /* pwc newer webcam */
      //'ET61X251' => fourcc('E','6','2','5'), /* ET61X251 compression */
      //'SPCA501' =>  fourcc('S','5','0','1'), /* YUYV per line */
      //'SPCA505' =>  fourcc('S','5','0','5'), /* YYUV per line */
      //'SPCA508' =>  fourcc('S','5','0','8'), /* YUVY per line */
      //'SPCA561' =>  fourcc('S','5','6','1'), /* compressed GBRG bayer */
      //'PAC207' =>   fourcc('P','2','0','7'), /* compressed BGGR bayer */
      //'PJPG' =>     fourcc('P','J','P','G'), /* Pixart 73xx JPEG */
      //'YVYU' =>     fourcc('Y','V','Y','U'), /* 16  YVU 4:2:2     */
      );
}

$Colours = array(
    '1' => translate('8BitGrey'),
    '3' => translate('24BitColour'),
    '4' => translate('32BitColour')
    );

$orientations = array(
    'ROTATE_0' => translate('Normal'),
    'ROTATE_90' => translate('RotateRight'),
    'ROTATE_180' => translate('Inverted'),
    'ROTATE_270' => translate('RotateLeft'),
    'FLIP_HORI' => translate('FlippedHori'),
    'FLIP_VERT' => translate('FlippedVert')
    );

$deinterlaceopts = array(
  0x00000000 => translate('Disabled'),
  0x00001E04 => translate('Four field motion adaptive - Soft'), /* 30 change */
  0x00001404 => translate('Four field motion adaptive - Medium'), /* 20 change */
  0x00000A04 => translate('Four field motion adaptive - Hard'), /* 10 change */
  0x00000001 => translate('Discard'),
  0x00000002 => translate('Linear'),
  0x00000003 => translate('Blend'),
  0x00000205 => translate('Blend (25%)'),
);

$deinterlaceopts_v4l2 = array(
  0x00000000 => 'Disabled',
  0x00001E04 => 'Four field motion adaptive - Soft',   /* 30 change */
  0x00001404 => 'Four field motion adaptive - Medium', /* 20 change */
  0x00000A04 => 'Four field motion adaptive - Hard',   /* 10 change */
  0x00000001 => 'Discard',
  0x00000002 => 'Linear',
  0x00000003 => 'Blend',
  0x00000205 => 'Blend (25%)',
  0x02000000 => 'V4L2: Capture top field only',
  0x03000000 => 'V4L2: Capture bottom field only',
  0x07000000 => 'V4L2: Alternate fields (Bob)',
  0x01000000 => 'V4L2: Progressive',
  0x04000000 => 'V4L2: Interlaced',
);

$fastblendopts = array(
    0  => translate ('No blending'),
    1  => '1.5625%',
    3  => '3.125%',
    6  => translate('6.25% (Indoor)'),
    12 => translate('12.5% (Outdoor)'),
    25 => '25%',
    50 => '50%',
    );

$fastblendopts_alarm = array(
    0  => translate('No blending (Alarm lasts forever)'),
    1  => '1.5625%',
    3  => '3.125%',
    6  => '6.25%',
    12 => '12.5%',
    25 => '25%',
    50 => translate('50% (Alarm lasts a moment)'),
    );

$label_size = array(
    1 => translate('Small'),
    2 => translate('Default'),
    3 => translate('Large'),
    4 => translate('Extra Large'),
    );

$codecs = array(
  'auto'  => translate('Auto'),
  'MP4'  => translate('MP4'),
  'MJPEG' => translate('MJPEG'),
);

$controls = ZM\Control::find(null, array('order'=>'lower(Name)'));

xhtmlHeaders(__FILE__, translate('Monitor').' - '.validHtmlStr($monitor->Name()));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page" class="container-fluid">

  <div class="row flex-nowrap">
    <nav>  <!-- BEGIN PILL LIST -->
    <ul class="nav nav-pills flex-column h-100" id="pills-tab" role="tablist" aria-orientation="vertical">
<?php
$tabs = array();
$tabs['general'] = translate('General');
$tabs['source'] = translate('Source');
$tabs['onvif'] = translate('ONVIF');
if ( $monitor->Type() != 'WebSite' ) {
  $tabs['storage'] = translate('Storage');
  $tabs['timestamp'] = translate('Timestamp');
  $tabs['buffers'] = translate('Buffers');
  if ( ZM_OPT_CONTROL && canView('Control') )
    $tabs['control'] = translate('Control');
  if ( ZM_OPT_X10 )
    $tabs['x10'] = translate('X10');
  $tabs['misc'] = translate('Misc');
  if (defined('ZM_OPT_USE_GEOLOCATION') and ZM_OPT_USE_GEOLOCATION)
    $tabs['location'] = translate('Location');
}

if ( isset($_REQUEST['tab']) )
  $tab = validHtmlStr($_REQUEST['tab']);
else
  $tab = 'general';

foreach ( $tabs as $name=>$value ) {
?>
    <li class="nav-item form-control-sm my-1">
      <a 
        id="<?php echo $name?>-tab"
        role="tab"
        data-toggle="pill"
        class="nav-link<?php echo $tab == $name ? ' active' : '' ?>"
          href="#pills-<?php echo $name?>"
        aria-controls="pills-<?php echo $name?>"
        aria-selected="<?php echo $tab == $name ? 'true':'false'?>"
      ><?php echo $value ?></a></li>
<?php
}
  ?>
    </ul>
    </nav> <!-- END PILL LIST -->

<div class="d-flex flex-column col-sm-offset-2 container-fluid">
    <!-- BEGIN MINI HEADER -->
    <div class="d-flex flex-row justify-content-between px-3 py-1">
      <div class="" id="toolbar" >
        <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      </div>
      
    <h2><?php echo translate('Monitor') ?> - <?php echo validHtmlStr($monitor->Name()) ?><?php if ( $monitor->Id() ) { ?> (<?php echo $monitor->Id()?>)<?php } ?></h2>
<?php
if ( canEdit('Monitors') ) {
  if ( isset($_REQUEST['dupId']) ) {
?>
    <div class="alert alert-info">
      Configuration cloned from Monitor: <?php echo validHtmlStr($clonedName) ?>
    </div>
<?php
  }
?>
    <div>
      <button id="probeBtn" class="btn btn-normal" data-mid="<?php echo $monitor->Id() ?>" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Probe') ?>" ><i class="fa fa-rss-square"></i></button>
      <button id="onvifBtn" class="btn btn-normal" data-mid="<?php echo $monitor->Id() ?>" data-toggle="tooltip" data-placement="top" title="<?php echo translate('OnvifProbe') ?>" ><i class="fa fa-rss"></i></button>
      <button id="presetBtn" class="btn btn-normal" data-mid="<?php echo $monitor->Id() ?>" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Presets') ?>" ><i class="fa fa-list-ol"></i></button>
    </div>
<?php
} // end if canEdit('Monitors')
?>
  </div>

    <!-- BEGIN ITEM LIST -->
    <div class="d-flex flex-row container-fluid pr-0">
    <form name="contentForm" id="contentForm" method="post" action="?view=monitor">
      <input type="hidden" name="tab" value="<?php echo $tab?>"/>
      <input type="hidden" name="mid" value="<?php echo $monitor->Id() ? $monitor->Id() : $mid ?>"/>
      <input type="hidden" name="origMethod" value="<?php echo (null !== $monitor->Method())?validHtmlStr($monitor->Method()):'' ?>"/>
<div class="tab-content" id="pills-tabContent">
<?php
foreach ( $tabs as $name=>$value ) {
  echo '<div id="pills-'.$name.'" class="tab-pane fade'.($name==$tab ? ' show active' : '').'" role="tabpanel" aria-labelledby="'.$name.'-tab">';
?>
      <table class="major">
        <tbody>
<?php
switch ( $name ) {
  case 'general' :
    {
?>
          <tr class="Name">
            <td class="text-right pr-3"><?php echo translate('Name') ?></td>
            <td><input type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($monitor->Name()) ?>"/></td>
          </tr>
          <tr class="Notes">
            <td class="text-right pr-3"><?php echo translate('Notes') ?></td>
            <td><textarea name="newMonitor[Notes]" rows="4"><?php echo validHtmlStr($monitor->Notes()) ?></textarea></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('Server') ?></td><td>
<?php
      $servers = array(''=>'None','auto'=>'Auto');
      foreach ( ZM\Server::find(NULL, array('order'=>'lower(Name)')) as $Server ) {
        $servers[$Server->Id()] = $Server->Name();
      }
      echo htmlSelect( 'newMonitor[ServerId]', $servers, $monitor->ServerId() );
?>
            </td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('SourceType') ?></td>
            <td><?php echo htmlSelect('newMonitor[Type]', $sourceTypes, $monitor->Type()); ?></td>
          </tr>
<?php
      if ( $monitor->Type() != 'WebSite' ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('Function') ?></td>
            <td>
<?php
      $function_options = array();
      foreach ( getEnumValues('Monitors', 'Function') as $f ) {
        $function_options[$f] = translate("Fn$f");
      }
      echo htmlSelect('newMonitor[Function]', $function_options, $monitor->Function());
?>
 <div id="function_help">
<?php
  foreach ( ZM\getMonitorFunctionTypes() as $fn => $translated ) {
    if ( isset($OLANG['FUNCTION_'.strtoupper($fn)]) ) {
      echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['FUNCTION_'.strtoupper($fn)]['Help'].'</div>';
    }
  }
?>
          </div>
          </td>
        </tr>
        <tr id="FunctionEnabled">
          <td class="text-right pr-3"><?php echo translate('Analysis Enabled') ?></td>
          <td><input type="checkbox" name="newMonitor[Enabled]" value="1"<?php echo $monitor->Enabled() ? ' checked="checked"' : '' ?>/>
<?php
  if ( isset($OLANG['FUNCTION_ANALYSIS_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_ANALYSIS_ENABLED']['Help'].'</div>';
  }
?>
          </td>
        </tr>
  <tr id="FunctionDecodingEnabled">
          <td class="text-right pr-3"><?php echo translate('Decoding Enabled') ?></td>
          <td><input type="checkbox" name="newMonitor[DecodingEnabled]" value="1"<?php echo $monitor->DecodingEnabled() ? ' checked="checked"' : '' ?>/>
<?php
  if ( isset($OLANG['FUNCTION_DECODING_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_DECODING_ENABLED']['Help'].'</div>';
  }
?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('LinkedMonitors'); echo makeHelpLink('OPTIONS_LINKED_MONITORS') ?></td>
          <td>
<?php
      $monitors = dbFetchAll('SELECT Id, Name FROM Monitors ORDER BY Name,Sequence ASC');
      $monitor_options = array();
      foreach ( $monitors as $linked_monitor ) {
        if ( (!$monitor->Id() || ($monitor->Id()!= $linked_monitor['Id'])) && visibleMonitor($linked_monitor['Id']) ) {
          $monitor_options[$linked_monitor['Id']] = validHtmlStr($linked_monitor['Name']);
        }
      }

      echo htmlSelect(
        'newMonitor[LinkedMonitors][]',
        $monitor_options,
        ($monitor->LinkedMonitors() ? explode(',', $monitor->LinkedMonitors()) : array()),
        array('class'=>'chosen', 'multiple'=>'multiple')
      );
?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Groups'); ?></td>
          <td><select name="newMonitor[GroupIds][]" multiple="multiple" class="chosen"><?php
            echo htmlOptions(ZM\Group::get_dropdown_options(), $monitor->GroupIds());
            ?></select></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('AnalysisFPS') ?></td>
          <td><input type="number" name="newMonitor[AnalysisFPSLimit]" value="<?php echo validHtmlStr($monitor->AnalysisFPSLimit()) ?>" min="0" step="any"/></td>
        </tr>
<?php
      if ( $monitor->Type() != 'Local' && $monitor->Type() != 'File' && $monitor->Type() != 'NVSocket' ) {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('MaximumFPS'); echo makeHelpLink('OPTIONS_MAXFPS') ?></td>
              <td>
                <input type="number" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($monitor->MaxFPS()) ?>" min="0" step="any"/>
                <span id="newMonitor[MaxFPS]" style="color:red;<?php echo $monitor->MaxFPS() ? '' : 'display:none;' ?>">CAUTION: See the help text</span>
              </td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('AlarmMaximumFPS'); echo makeHelpLink('OPTIONS_ALARMMAXFPS') ?></td>
              <td>
                <input type="number" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($monitor->AlarmMaxFPS()) ?>" min="0" step="any"/>
                <span id="newMonitor[AlarmMaxFPS]" style="color:red;<?php echo $monitor->AlarmMaxFPS() ? '' : 'display:none;' ?>">CAUTION: See the help text</span>
              </td>
            </tr>
<?php
      } else {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('MaximumFPS') ?></td>
              <td><input type="number" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($monitor->MaxFPS()) ?>" min="0" step="any"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('AlarmMaximumFPS') ?></td>
              <td><input type="number" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($monitor->AlarmMaxFPS()) ?>" min="0" step="any"/></td>
            </tr>
<?php
      }
      if ( ZM_FAST_IMAGE_BLENDS ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RefImageBlendPct') ?></td>
            <td><?php echo htmlSelect('newMonitor[RefBlendPerc]', $fastblendopts, $monitor->RefBlendPerc()); ?></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('AlarmRefImageBlendPct') ?></td>
            <td><?php echo htmlSelect('newMonitor[AlarmRefBlendPerc]', $fastblendopts_alarm, $monitor->AlarmRefBlendPerc()); ?></td>
          </tr>
          <?php
      } else {
?>
            <tr><td class="text-right pr-3"><?php echo translate('RefImageBlendPct') ?></td><td><input type="text" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($monitor->RefBlendPerc()) ?>" size="4"/></td></tr>
            <tr><td class="text-right pr-3"><?php echo translate('AlarmRefImageBlendPct') ?></td><td><input type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($monitor->AlarmRefBlendPerc()) ?>" size="4"/></td></tr>
<?php
      }
?>
            <tr><td class="text-right pr-3"><?php echo translate('Triggers') ?></td><td>
<?php
      $optTriggers = getSetValues('Monitors', 'Triggers');
      $breakCount = (int)(ceil(count($optTriggers)));
      $breakCount = min(3, $breakCount);
      $optCount = 0;
      foreach ( $optTriggers as $optTrigger ) {
        if ( $optTrigger == 'X10' and !ZM_OPT_X10 )
          continue;
        if ( $optCount && ($optCount%$breakCount == 0) )
          echo '</br>';
        echo '<input type="checkbox" name="newMonitor[Triggers][]" value="'.$optTrigger.'"'.
          (( ('' !== $monitor->Triggers()) && in_array($optTrigger, $monitor->Triggers()) ) ? ' checked="checked"' : ''). '/> '. $optTrigger;
        $optCount ++;
      } # end foreach trigger option
      if ( !$optCount ) {
        echo '<em>'.translate('NoneAvailable').'</em>';
      }
?>
        </td></tr>
        <?php
        }
        break;
    }
    case 'onvif' :
    {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ONVIF_URL') ?></td>
              <td><input type="text" name="newMonitor[ONVIF_URL]" value="<?php echo validHtmlStr($monitor->ONVIF_URL()) ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Username') ?></td>
              <td><input type="text" name="newMonitor[ONVIF_Username]" value="<?php echo validHtmlStr($monitor->ONVIF_Username()) ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Password') ?></td>
              <td><input type="text" name="newMonitor[ONVIF_Password]" value="<?php echo validHtmlStr($monitor->ONVIF_Password()) ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ONVIF_Options') ?></td>
              <td><input type="text" name="newMonitor[ONVIF_Options]" value="<?php echo validHtmlStr($monitor->ONVIF_Options()) ?>"/></td>
            </tr>
<?php
        break;
    }
    case 'source' :
    {
      if ( ZM_HAS_V4L && $monitor->Type() == 'Local' ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('DevicePath') ?></td>
            <td><input type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($monitor->Device()) ?>"/></td>
          </tr>
          <tr>
            <td><?php echo translate('CaptureMethod') ?></td>
            <td><?php echo htmlSelect('newMonitor[Method]', $localMethods, $monitor->Method(), array('onchange'=>'submitTab', 'data-tab-name'=>$tab) ); ?></td>
          </tr>
<?php
        if ( ZM_HAS_V4L1 && $monitor->Method() == 'v4l1' ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('DeviceChannel') ?></td>
            <td><?php echo htmlSelect('newMonitor[Channel]', $v4l1DeviceChannels, $monitor->Channel()); ?></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('DeviceFormat') ?></td>
            <td><?php echo htmlSelect('newMonitor[Format]', $v4l1DeviceFormats, $monitor->Format()); ?></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('CapturePalette') ?></td>
            <td><?php echo htmlSelect('newMonitor[Palette]', $v4l1LocalPalettes, $monitor->Palette()); ?></td>
          </tr>
<?php
        } else {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('DeviceChannel') ?></td>
            <td><?php echo htmlSelect('newMonitor[Channel]', $v4l2DeviceChannels, $monitor->Channel()); ?></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('DeviceFormat') ?></td>
            <td><?php echo htmlSelect('newMonitor[Format]', $v4l2DeviceFormats, $monitor->Format()); ?></td>
          </tr>
         <tr>
            <td class="text-right pr-3"><?php echo translate('CapturePalette') ?></td>
            <td><?php echo htmlSelect('newMonitor[Palette]', $v4l2LocalPalettes, $monitor->Palette()); ?></td>
          </tr>
<?php
        }
?>
          <tr><td class="text-right pr-3"><?php echo translate('V4LMultiBuffer') ?></td><td>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $monitor->V4LMultiBuffer() == '1' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]1">Yes</label>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $monitor->V4LMultiBuffer() == '0' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]0">No</label>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( $monitor->V4LMultiBuffer() == '' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]">Use Config Value</label>
          </td></tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('V4LCapturesPerFrame') ?></td>
            <td><input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo validHtmlStr($monitor->V4LCapturesPerFrame()); ?>" min="1"/></td>
          </tr>
<?php

      } else if ( $monitor->Type() == 'NVSocket' ) {
include('_monitor_source_nvsocket.php');
      } else if ( $monitor->Type() == 'VNC' ) {
?>
        <tr>
          <td class="text-right pr-3"><?php echo translate('RemoteHostName') ?></td>
          <td><input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('RemoteHostPort') ?></td>
          <td><input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" size="6"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Username') ?></td>
          <td><input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Password') ?></td>
          <td><input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>"/></td>
        </tr>
<?php
      } else if ( $monitor->Type() == 'Remote' ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RemoteProtocol') ?></td>
            <td><?php echo htmlSelect('newMonitor[Protocol]', $remoteProtocols, $monitor->Protocol(), "updateMethods( this );if(this.value=='rtsp'){\$('RTSPDescribe').setStyle('display','table-row');}else{\$('RTSPDescribe').hide();}" ); ?></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RemoteMethod') ?></td>
            <td>
<?php
        if ( !$monitor->Protocol() || $monitor->Protocol() == 'http' ) {
          echo htmlSelect('newMonitor[Method]', $httpMethods, $monitor->Method());
        } else {
          echo htmlSelect('newMonitor[Method]', $rtspMethods, $monitor->Method());
        }
?>
            </td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RemoteHostName') ?></td>
            <td><input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>"/></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RemoteHostPort') ?></td>
            <td><input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" min="0" max="65535" step="1"/></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('RemoteHostPath') ?></td>
            <td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/></td>
          </tr>
<?php
      } else if ( $monitor->Type() == 'File' ) {
?>
          <tr><td class="text-right pr-3"><?php echo translate('SourcePath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/></td></tr>
<?php
      } elseif ( $monitor->Type() == 'cURL' ) {
?>
          <tr><td class="text-right pr-3"><?php echo 'URL' ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/></td></tr>
          <tr><td class="text-right pr-3"><?php echo 'Username' ?></td><td><input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>"/></td></tr>
          <tr><td class="text-right pr-3"><?php echo 'Password' ?></td><td><input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>"/></td></tr>
<?php
      } elseif ( $monitor->Type() == 'WebSite' ) {
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('WebSiteUrl') ?></td>
            <td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/></td>
          </tr>
          <tr>
            <td><?php echo translate('Width') ?> (<?php echo translate('Pixels') ?>)</td>
            <td><input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1"/></td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('Height') ?> (<?php echo translate('Pixels') ?>)</td>
            <td><input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1"/></td>
          </tr>
          <tr>
            <td class="text-right pr-3"<?php echo 'Web Site Refresh (Optional)' ?></td>
            <td><input type="number" name="newMonitor[Refresh]" value="<?php echo validHtmlStr($monitor->Refresh()) ?>" min="1"/></td>
          </tr>
<?php
      } else if ( $monitor->Type() == 'Ffmpeg' || $monitor->Type() == 'Libvlc' ) {
?>
          <tr class="SourcePath">
            <td class="text-right pr-3"><?php echo translate('SourcePath') ?></td>
            <td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" /></td>
          </tr>
          <tr>
            <td class="text-right pr-3">
              <?php echo translate('RemoteMethod'); echo makeHelpLink('OPTIONS_RTSPTrans') ?></td>
            <td><?php echo htmlSelect('newMonitor[Method]', $rtspFFMpegMethods, $monitor->Method()) ?></td>
          </tr>
          <tr class="SourceOptions">
            <td class="text-right pr-3"><?php echo translate('Options'); echo makeHelpLink('OPTIONS_'.strtoupper($monitor->Type())) ?></td>
            <td><input type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($monitor->Options()) ?>"/></td>
          </tr>
<?php
      }
      if ( $monitor->Type() == 'Ffmpeg' ) {
?>
          <tr class="SourceSecondPath">
            <td class="text-right pr-3"><?php echo translate('SourceSecondPath') ?></td>
            <td><input type="text" name="newMonitor[SecondPath]" value="<?php echo validHtmlStr($monitor->SecondPath()) ?>" /></td>
          </tr>
          <tr class="DecoderHWAccelName">
            <td class="text-right pr-3">
              <?php echo translate('DecoderHWAccelName'); echo makeHelpLink('OPTIONS_DECODERHWACCELNAME') ?>
            </td>
            <td><input type="text" name="newMonitor[DecoderHWAccelName]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelName()) ?>"/></td>
          </tr>
          <tr class="DecoderHWAccelDevice">
            <td class="text-right pr-3"><?php echo translate('DecoderHWAccelDevice') ?>
                <?php echo makeHelpLink('OPTIONS_DECODERHWACCELDEVICE') ?>
            </td>
            <td><input type="text" name="newMonitor[DecoderHWAccelDevice]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelDevice()) ?>"/></td>
          </tr>
<?php
      }
      if ( $monitor->Type() != 'NVSocket' && $monitor->Type() != 'WebSite' ) {
?>
        <tr>
          <td class="text-right pr-3"><?php echo translate('TargetColorspace') ?></td>
          <td><?php echo htmlSelect('newMonitor[Colours]', $Colours, $monitor->Colours()) ?></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('CaptureResolution') ?> (<?php echo translate('Pixels') ?>)</td>
          <td>
<?php 
        $resolutions =  
          array(
            ''=>translate('Custom'),
            '176x120'=>'176x120 QCIF',
            '176x144'=>'176x14',
            '320x240'=>'320x240',
            '320x200'=>'320x200',
            '352x240'=>'352x240 CIF',
            '352x480'=>'352x480',
            '640x360'=>'640x360',
            '640x400'=>'640x400',
            '640x480'=>'640x480',
            '704x240'=>'704x240 2CIF',
            '704x480'=>'704x480 4CIF',
            '704x576'=>'704x576 D1 PAL',
            '720x480'=>'720x480 Full D1 NTSC',
            '720x576'=>'720x576 Full D1 PAL',
            '1280x720'=>'1280x720 720p',
            '1280x800'=>'1280x800',
            '1280x960'=>'1280x960 960p',
            '1280x1024'=>'1280x1024 1MP',
            '1600x1200'=>'1600x1200 2MP',
            '1920x1080'=>'1920x1080 1080p',
            '2048x1536'=>'2048x1536 3MP',
            '2560x1440'=>'2560x1440 1440p QHD WQHD',
            '2592x1944'=>'2592x1944 5MP',
            '2688x1520'=>'2688x1520 4MP',
            '3072x2048'=>'3072x2048 6MP',
            '3840x2160'=>'3840x2160 4K UHD',
          );
        $selected = '';
        if ( $monitor->Width() and $monitor->Height() ) {
          $selected = $monitor->Width().'x'.$monitor->Height();
          if ( ! isset($resolutions[$selected]) ) {
            $resolutions[$selected] = $selected;
          }
        }
        echo htmlselect('dimensions_select', $resolutions, $selected);
?>
<?php
          if ( $monitor->Width() and $monitor->Height() ) {
?>
            <input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1"/>
            <input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1"/>
<?php
          }
         else {
?>
            <input type="number" name="newMonitor[Width]" value="" min="1"/>
            <input type="number" name="newMonitor[Height]" value="" min="1"/>
<?php
          }
?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('PreserveAspect') ?></td>
          <td><input type="checkbox" name="preserveAspectRatio" value="1"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Orientation') ?></td>
          <td><?php echo htmlselect('newMonitor[Orientation]', $orientations, $monitor->Orientation());?></td>
        </tr>
<?php
      }
      if ( $monitor->Type() == 'Local' ) {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Deinterlacing') ?></td>
              <td><?php echo htmlselect('newMonitor[Deinterlacing]', $deinterlaceopts_v4l2, $monitor->Deinterlacing())?></td>
            </tr>
<?php
        } else if ( $monitor->Type() != 'WebSite' ) {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Deinterlacing') ?></td>
              <td><?php echo htmlselect('newMonitor[Deinterlacing]', $deinterlaceopts, $monitor->Deinterlacing())?></td>
            </tr>
<?php
        }
        if ( $monitor->Type() == 'Remote' ) {
          ?>
            <tr id="RTSPDescribe"<?php if ( $monitor->Protocol()!= 'rtsp' ) { echo ' style="display:none;"'; } ?>>
              <td class="text-right pr-3"><?php echo translate('RTSPDescribe'); echo makeHelpLink('OPTIONS_RTSPDESCRIBE') ?></td>
              <td><input type="checkbox" name="newMonitor[RTSPDescribe]" value="1"<?php if ( $monitor->RTSPDescribe() ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
      } # end if monitor->Type() == 'Remote'
      break;
    }
  case 'storage' :
?>
          <tr>
            <td class="text-right pr-3"><?php echo translate('StorageArea') ?></td>
            <td>
<?php
      $storage_areas = array(0=>'Default');
      foreach ( ZM\Storage::find(array('Enabled'=>true), array('order'=>'lower(Name)')) as $Storage ) {
        $storage_areas[$Storage->Id()] = $Storage->Name();
      }
      echo htmlSelect('newMonitor[StorageId]', $storage_areas, $monitor->StorageId());
?>
            </td>
          </tr>
          <tr>
            <td class="text-right pr-3"><?php echo translate('SaveJPEGs') ?></td>
            <td>
<?php
      $savejpegopts = array(
        0 => translate('Disabled'),
        1 => translate('Frames only'),
        2 => translate('Analysis images only (if available)'),
        3 => translate('Frames + Analysis images (if available)'),
      );
      echo htmlSelect('newMonitor[SaveJPEGs]', $savejpegopts, $monitor->SaveJPEGs());
?>
             </td>
            </tr>
            <tr><td class="text-right pr-3"><?php echo translate('VideoWriter') ?></td><td>
<?php
	$videowriteropts = array(
			0 => translate('Disabled'),
			);

  $videowriteropts[1] = translate('Encode');

  if ( $monitor->Type() == 'Ffmpeg' )
    $videowriteropts[2] = translate('Camera Passthrough');
  else
    $videowriteropts[2] = array('text'=>translate('Camera Passthrough - only for FFMPEG'),'disabled'=>1);
	echo htmlSelect('newMonitor[VideoWriter]', $videowriteropts, $monitor->VideoWriter());
?>
              </td>
            </tr>
            <tr class="OutputCodec">
              <td class="text-right pr-3"><?php echo translate('OutputCodec') ?></td>
              <td>
<?php
$videowriter_codecs = array(
  '0' => translate('Auto'),
  '27' => 'h264',
  '173' => 'h265/hevc',
  '226' => 'av1',
);
echo htmlSelect('newMonitor[OutputCodec]', $videowriter_codecs, $monitor->OutputCodec());
?>
              </td>
            </tr>
            <tr class="Encoder">
              <td class="text-right pr-3"><?php echo translate('Encoder') ?></td>
              <td>
<?php
$videowriter_encoders = array(
  'auto' => translate('Auto'),
  'libx264' => 'libx264',
  'h264' => 'h264',
  'h264_ni_quadra_enc' => 'h264_ni_quadra_enc',
  'h264_nvenc' => 'h264_nvenc',
  'h264_omx' => 'h264_omx',
  'h264_vaapi' => 'h264_vaapi',
  'libx265' => 'libx265',
  'h265_ni_quadra_enc' => 'h265_ni_quadra_enc',
  'hevc_nvenc' => 'hevc_nvenc',
  'hevc_vaapi' => 'hevc_vaapi',
  'libaom-av1' => 'libaom-av1',
  'av1_ni_quadra_enc' => 'av1_ni_quadra_enc',
);
echo htmlSelect('newMonitor[Encoder]', $videowriter_encoders, $monitor->Encoder());?></td></tr>
            <tr class="OutputContainer">
              <td class="text-right pr-3"><?php echo translate('OutputContainer') ?></td>
              <td>
<?php
$videowriter_containers = array(
  '' => translate('Auto'),
  'mp4' => 'mp4',
  'mkv' => 'mkv',
);
echo htmlSelect('newMonitor[OutputContainer]', $videowriter_containers, $monitor->OutputContainer());
?>
              </td>
            </tr>
            <tr class="OptionalEncoderParam">
              <td class="text-right pr-3"><?php echo translate('OptionalEncoderParam'); echo makeHelpLink('OPTIONS_ENCODER_PARAMETERS') ?></td>
              <td>
              <textarea name="newMonitor[EncoderParameters]" width=40 rows="<?php echo count(explode("\n", $monitor->EncoderParameters()))+2; ?>"><?php echo validHtmlStr($monitor->EncoderParameters()) ?></textarea>
              </td>
            </tr>
            <tr class="RecordAudio"><td class="text-right pr-3"><?php echo translate('RecordAudio'); echo makeHelpLink('OPTIONS_RECORDAUDIO') ?></td><td>
<?php if ( $monitor->Type() == 'Ffmpeg' ) { ?>
              <input type="checkbox" name="newMonitor[RecordAudio]" value="1"<?php if ( $monitor->RecordAudio() ) { ?> checked="checked"<?php } ?>/>
<?php } else { ?>
              <?php echo translate('Audio recording only available with FFMPEG')?>
              <input type="hidden" name="newMonitor[RecordAudio]" value="<?php echo $monitor->RecordAudio() ? 1 : 0 ?>"/>
<?php } ?>
            </td></tr>

<?php
      break;
  case 'timestamp' :
    {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TimestampLabelFormat') ?></td>
              <td><input type="text" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($monitor->LabelFormat()) ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TimestampLabelX') ?></td>
              <td><input type="number" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($monitor->LabelX()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TimestampLabelY') ?></td>
              <td><input type="number" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($monitor->LabelY()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TimestampLabelSize') ?></td>
              <td><?php echo htmlselect('newMonitor[LabelSize]', $label_size, $monitor->LabelSize()) ?></td>
            </tr>
<?php
      break;
    }
  case 'buffers' :
    {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ImageBufferSize'); echo makeHelpLink('ImageBufferCount'); ?></td>
              <td><input type="number" name="newMonitor[ImageBufferCount]" value="<?php echo validHtmlStr($monitor->ImageBufferCount()) ?>" min="1"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('MaxImageBufferCount'); echo makeHelpLink('MaxImageBufferCount'); ?></td>
              <td><input type="number" id="newMonitor[MaxImageBufferCount]" name="newMonitor[MaxImageBufferCount]" value="<?php echo validHtmlStr($monitor->MaxImageBufferCount()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('WarmupFrames') ?></td>
              <td><input type="number" name="newMonitor[WarmupCount]" value="<?php echo validHtmlStr($monitor->WarmupCount()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('PreEventImageBuffer') ?></td>
              <td><input type="number" id="newMonitor[PreEventCount]" name="newMonitor[PreEventCount]" value="<?php echo validHtmlStr($monitor->PreEventCount()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('PostEventImageBuffer') ?></td>
              <td><input type="number" name="newMonitor[PostEventCount]" value="<?php echo validHtmlStr($monitor->PostEventCount()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('StreamReplayBuffer') ?></td>
              <td><input type="number" name="newMonitor[StreamReplayBuffer]" value="<?php echo validHtmlStr($monitor->StreamReplayBuffer()) ?>" min="0"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('AlarmFrameCount') ?></td>
              <td><input type="number" name="newMonitor[AlarmFrameCount]" value="<?php echo validHtmlStr($monitor->AlarmFrameCount()) ?>" min="1"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Estimated Ram Use') ?></td>
              <td id="estimated_ram_use"></td>
            </tr>
<?php
      break;
    }
  case 'control' :
    {
?>
            <tr>
              <td class="text-right pr-3"><?php echo translate('Controllable') ?></td>
              <td><input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( $monitor->Controllable() ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ControlType') ?></td>
              <td>
<?php 
                  $controlTypes = array(''=>translate('None'));
                  foreach ( $controls as $control ) {
                    $controlTypes[$control->Id()] = $control->Name();
                  }

                  echo htmlSelect('newMonitor[ControlId]', $controlTypes, $monitor->ControlId());
                  if ( canEdit('Control') ) {
                    echo '&nbsp;'.makeLink('?view=controlcaps', translate('Edit'));
                  }
?>
              </td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ControlDevice') ?></td>
              <td><input type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($monitor->ControlDevice()) ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ControlAddress') ?></td>
              <td><input type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($monitor->ControlAddress()) ? : 'user:port@ip' ?>"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ModectDuringPTZ') ?></td>
              <td><input type="checkbox" name="newMonitor[ModectDuringPTZ]" value="1"<?php if ( $monitor->ModectDuringPTZ() ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('AutoStopTimeout') ?></td>
              <td><input type="number" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($monitor->AutoStopTimeout()) ?>" min="0" step="any"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TrackMotion') ?></td>
              <td><input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( $monitor->TrackMotion() ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('TrackDelay') ?></td>
              <td><input type="number" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($monitor->TrackDelay()) ?>" min="0" step="any"/></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ReturnLocation') ?></td>
              <td><?php
      $return_options = array(
          '-1' => translate('None'),
          '0' => translate('Home'),
          '1' => translate('Preset').' 1',
      );
echo htmlSelect('newMonitor[ReturnLocation]', $return_options, $monitor->ReturnLocation()); ?></td>
            </tr>
            <tr>
              <td class="text-right pr-3"><?php echo translate('ReturnDelay') ?></td>
              <td><input type="number" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($monitor->ReturnDelay()) ?>" min="0" step="any"/></td>
            </tr>
<?php
      break;
    }
  case 'x10' :
    {
?>
            <tr><td class="text-right pr-3"><?php echo translate('X10ActivationString') ?></td><td><input type="text" name="newX10Monitor[Activation]" value="<?php echo validHtmlStr($newX10Monitor['Activation']) ?>" size="20"/></td></tr>
            <tr><td class="text-right pr-3"><?php echo translate('X10InputAlarmString') ?></td><td><input type="text" name="newX10Monitor[AlarmInput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmInput']) ?>" size="20"/></td></tr>
            <tr><td class="text-right pr-3"><?php echo translate('X10OutputAlarmString') ?></td><td><input type="text" name="newX10Monitor[AlarmOutput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmOutput']) ?>" size="20"/></td></tr>
<?php
      break;
    }
  case 'misc' :
    {
?>
        <tr>
          <td class="text-right pr-3"><?php echo translate('EventPrefix') ?></td>
          <td><input type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($monitor->EventPrefix()) ?>"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Sectionlength') ?></td>
          <td>
            <input type="number" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($monitor->SectionLength()) ?>" min="0"/>
            <?php echo translate('seconds')?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('MinSectionlength') ?></td>
          <td>
            <input type="number" name="newMonitor[MinSectionLength]" value="<?php echo validHtmlStr($monitor->MinSectionLength()) ?>" min="0"/>
            <?php echo translate('seconds')?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('FrameSkip') ?></td>
          <td>
            <input type="number" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($monitor->FrameSkip()) ?>" min="0"/>
            <?php echo translate('frames')?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('MotionFrameSkip') ?></td>
          <td>
            <input type="number" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($monitor->MotionFrameSkip()) ?>" min="0"/>
            <?php echo translate('frames')?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('AnalysisUpdateDelay') ?></td>
          <td>
            <input type="number" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($monitor->AnalysisUpdateDelay()) ?>" min="0"/>
            <?php echo translate('seconds')?>
          </td></tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('FPSReportInterval') ?></td>
          <td>
            <input type="number" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($monitor->FPSReportInterval()) ?>" min="0"/>
            <?php echo translate('frames')?>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('DefaultRate') ?></td>
          <td><?php echo htmlSelect('newMonitor[DefaultRate]', $rates, $monitor->DefaultRate()); ?></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('DefaultScale') ?></td>
          <td><?php echo htmlSelect('newMonitor[DefaultScale]', $scales, $monitor->DefaultScale()); ?></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('DefaultCodec') ?></td>
          <td><?php echo htmlSelect('newMonitor[DefaultCodec]', $codecs, $monitor->DefaultCodec()); ?></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('SignalCheckPoints') ?></td>
          <td>
            <input type="number" name="newMonitor[SignalCheckPoints]" value="<?php echo validInt($monitor->SignalCheckPoints()) ?>" min="0"/>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('SignalCheckColour') ?></td>
          <td>
            <input type="color" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($monitor->SignalCheckColour()) ?>"/>
            <span id="SignalCheckSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($monitor->SignalCheckColour()); ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('WebColour') ?></td>
          <td>
            <input type="color" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($monitor->WebColour()) ?>"/>
            <span id="WebSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($monitor->WebColour()) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
            <i class="material-icons" data-on-click="random_WebColour">sync</i>

          </td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Exif'); echo makeHelpLink('OPTIONS_EXIF') ?></td>
          <td><input type="checkbox" name="newMonitor[Exif]" value="1"<?php echo $monitor->Exif() ? ' checked="checked"' : '' ?>/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('RTSPServer'); echo makeHelpLink('OPTIONS_RTSPSERVER') ?></td>
          <td><input type="checkbox" name="newMonitor[RTSPServer]" value="1"<?php echo $monitor->RTSPServer() ? ' checked="checked"' : '' ?>/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('RTSPStreamName'); echo makeHelpLink('OPTIONS_RTSPSTREAMNAME') ?></td>
          <td><input type="text" name="newMonitor[RTSPStreamName]" value="<?php echo validHtmlStr($monitor->RTSPStreamName()) ?>"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Importance'); echo makeHelpLink('OPTIONS_IMPORTANCE') ?></td>
          <td>
<?php
      echo htmlselect('newMonitor[Importance]',
              array(
                'Not'=>translate('Not important'),
                'Less'=>translate('Less important'),
                'Normal'=>translate('Normal')
              ), $monitor->Importance());
?>
          </td>
        </tr>
<?php
        break;
    }
  case 'location':
?>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Latitude') ?></td>
          <td><input type="number" name="newMonitor[Latitude]" step="any" value="<?php echo $monitor->Latitude() ?>" min="-90" max="90"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"><?php echo translate('Longitude') ?></td>
          <td><input type="number" name="newMonitor[Longitude]" step="any" value="<?php echo $monitor->Longitude() ?>" min="-180" max="180"/></td>
        </tr>
        <tr>
          <td class="text-right pr-3"></td>
          <td><button type="button" data-on-click="getLocation"><?php echo translate('GetCurrentLocation') ?></button></td>
        </tr>
        <tr>
          <td colspan="2"><div id="LocationMap" style="height: 500px; width: 500px;"></div></td>
        </tr>
            
<?php
    break;
  default :
    ZM\Error("Unknown tab $tab");
} // end switch tab
?>
          </tbody>
        </table>
</div>
<?php 
} # end foreach tab
?>
</div><!--tab-content-->
        <div id="contentButtons" class="pr-3">
          <input type="hidden" name="action"/>
          <button type="button" data-on-click="validateForm" <?php echo canEdit('Monitors') ? '' : ' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
          <button type="button" id="cancelBtn"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
    </div>
    </div>
  </div>
<?php xhtmlFooter() ?>
