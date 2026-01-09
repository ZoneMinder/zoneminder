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
require_once('includes/User.php');
require_once('includes/Zone.php');

if (!canEdit('Monitors', empty($_REQUEST['mid'])?0:$_REQUEST['mid'])) {
  $view = 'error';
  return;
}

$Server = null;
if (defined('ZM_SERVER_ID')) {
  $Server = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, array(ZM_SERVER_ID));
}
if (!$Server) {
  $Server = array('Id' => '');
}

$monitors = dbFetchAll('SELECT Id, Name FROM Monitors WHERE Deleted=false ORDER BY Name,Sequence ASC');
$monitors_by_id = array();
foreach ($monitors as $row) {
  $monitors_by_id[$row['Id']] = $row['Name'];
}
$monitors_by_name = array_flip($monitors_by_id);

$mid = null;
$monitor = null;
$thisNewMonitor = false;
if (!empty($_REQUEST['mid'])) {
  $mid = validInt($_REQUEST['mid']);
  $monitor = new ZM\Monitor($mid);
  if ($monitor->Id()) {
    if (ZM_OPT_X10) {
      $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($mid));
    }
  } else {
    $monitor->Name(translate('Monitor').'-'.$mid);
    $monitor->WebColour(random_colour());
  }
}

if (!$monitor) {
  $thisNewMonitor = true;
  $monitor = new ZM\Monitor();
  $monitor->Name(translate('Monitor').'-'.getTableAutoInc('Monitors'));
  while (isset($monitors_by_name[$monitor->Name()])) {
    $monitor->Name($monitor->Name().'0');
  }
  $monitor->WebColour(random_colour());
} # end if $_REQUEST['mid']

if (isset($_REQUEST['dupId'])) {
  $monitor = new ZM\Monitor(validCardinal($_REQUEST['dupId']));
  $monitor->GroupIds(); // have to load before we change the Id
  if (ZM_OPT_X10)
    $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($_REQUEST['dupId']));
  $clonedName = $monitor->Name();
  $monitor->Name('Clone of '.$monitor->Name());
  while (isset($monitors_by_name[$monitor->Name()])) {
    $monitor->Name('Clone of '.$monitor->Name());
  }
  $monitor->Id($mid);
}

if (ZM_OPT_X10 && empty($x10Monitor)) {
  $x10Monitor = array(
      'Activation' => '',
      'AlarmInput' => '',
      'AlarmOutput' => '',
      );
}

function fourcc($a, $b, $c, $d) {
  return ord($a) | (ord($b) << 8) | (ord($c) << 16) | (ord($d) << 24);
}
if (isset($_REQUEST['newMonitor'])) {
  # Update the monitor object with whatever has been set so far.
  $monitor->set($_REQUEST['newMonitor']);

  if (ZM_OPT_X10)
    $newX10Monitor = $_REQUEST['newX10Monitor'];
} else {
  if (ZM_OPT_X10)
    $newX10Monitor = $x10Monitor;
}

# What if it has less zeros?  This is not robust code.
if ($monitor->AnalysisFPSLimit() == '0.00')
  $monitor->AnalysisFPSLimit('');
if ($monitor->MaxFPS() == '0.00')
  $monitor->MaxFPS('');
if ($monitor->AlarmMaxFPS() == '0.00')
  $monitor->AlarmMaxFPS('');

if (!empty($_REQUEST['preset'])) {
  $preset = dbFetchOne('SELECT Type, Device, Channel, Format, Protocol, Method, Host, Port, Path, Width, Height, Palette, MaxFPS, Controllable, ControlId, ControlDevice, ControlAddress, DefaultRate, DefaultScale FROM MonitorPresets WHERE Id = ?', NULL, array($_REQUEST['preset']));
  foreach ($preset as $name=>$value) {
    # Does isset handle NULL's?  I don't think this code is correct.
    # Icon: It does, but this means we can't set a null value.
    if (isset($value)) {
      $monitor->$name($value);
    }
  }
} # end if preset

if (!empty($_REQUEST['probe'])) {
  $probe = json_decode(base64_decode($_REQUEST['probe']));
  foreach ($probe as $name=>$value) {
    if (isset($value)) {
      $monitor->$name = urldecode($value);
    }
  }
  if (ZM_HAS_V4L2 && ($monitor->Type() == 'Local')) {
    $monitor->Palette(fourCC(substr($monitor->Palette,0,1), substr($monitor->Palette,1,1), substr($monitor->Palette,2,1), substr($monitor->Palette,3,1)));
    if ($monitor->Format() == 'PAL')
      $monitor->Format(0x000000ff);
    else if ($monitor->Format() == 'NTSC')
      $monitor->Format(0x0000b000);
  }
} # end if apply probe settings

$sourceTypes = array(
    'Local'  => translate('Local'),
    'Remote' => translate('Remote'),
    'File'   => translate('File'),
    'Ffmpeg' => translate('Ffmpeg'),
    'Libvlc' => translate('Libvlc'),
    'WebSite'=> 'Web Site',
    'NVSocket'	=> translate('NVSocket'),
    'VNC' => translate('VNC'),
    );
if (!ZM_HAS_V4L2)
  unset($sourceTypes['Local']);


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

if (!ZM_PCRE)
  unset($httpMethods['regexp']);
  // Currently unsupported
unset($httpMethods['jpegTags']);

if (ZM_HAS_V4L2) {
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
  for ($i = 0; $i <= $v4l2MaxChannels; $i++)
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


xhtmlHeaders(__FILE__, translate('Monitor').' - '.validHtmlStr($monitor->Name()));
getBodyTopHTML();
echo getNavBarHTML();
?>
<div id="page">
  <div id="content" class="row flex-nowrap">
    <nav>  <!-- BEGIN PILL LIST -->
      <ul class="nav nav-pills" id="pills-tab" role="tablist" aria-orientation="vertical">
<?php
$tabs = array();
$tabs['general'] = translate('General');
$tabs['source'] = translate('Source');
if ( $monitor->Type() != 'WebSite' ) {
  $tabs['analysis'] = translate('Analysis');
  $tabs['recording'] = translate('Recording');
  $tabs['viewing'] = translate('Viewing');
  $tabs['onvif'] = translate('ONVIF');
  $tabs['timestamp'] = translate('Timestamp');
  $tabs['buffers'] = translate('Buffers');
  if ( ZM_OPT_CONTROL && canView('Control') )
    $tabs['control'] = translate('Control');
  if ( ZM_OPT_X10 )
    $tabs['x10'] = translate('X10');
  $tabs['misc'] = translate('Misc');
  $tabs['zones'] = translate('Zones');
  if (defined('ZM_OPT_USE_GEOLOCATION') and ZM_OPT_USE_GEOLOCATION)
    $tabs['location'] = translate('Location');
  $tabs['mqtt'] = translate('MQTT');
}

if (isset($_REQUEST['tab']) and isset($tabs[$_REQUEST['tab']]) ) {
  $tab = validHtmlStr($_REQUEST['tab']);
} else {
  $tab = 'general';
}

foreach ($tabs as $name=>$value) {
?>
    <li class="nav-item form-control-sm my-1" id="<?php echo $name?>-li">
      <a 
        id="<?php echo $name?>-tab"
        class="nav-link<?php echo ($tab == $name ? ' active' : '') . ' ' . (($name == 'zones' && $thisNewMonitor === true) ? 'disabled' : '')?>"
        <?php 
        if ($name == 'zones') {
          //echo 'href="index.php?view=zones&mid=' . $monitor->Id() . '" ';
          echo 'href="#"';
        } else {
          echo 'href="#pills-' . $name . '" '; 
          echo 'role="tab" '; 
          echo 'data-toggle="pill" '; 
        }
        ?>
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
      
      <h2><?php echo translate('Monitor').' - '.($monitor->Id() ? $monitor->Id().' - ' : '').validHtmlStr($monitor->Name()) ?></h2>
<?php
if (canEdit('Monitors')) {
  if (isset($_REQUEST['dupId'])) {
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
    </div><!--mini header-->

    <!-- BEGIN ITEM LIST -->
    <div class="container-fluid" id="monitor">
      <form name="contentForm" id="contentForm" method="post" action="?view=monitor" autocomplete="off">
        <input type="password" name="dummy_password" style="display:none;"/><?php #to prevent chrome from saving passwords ?>
        <input type="hidden" name="tab" value="<?php echo $tab?>"/>
        <input type="hidden" name="mid" value="<?php echo $monitor->Id() ? $monitor->Id() : $mid ?>"/>
        <input type="hidden" name="origMethod" value="<?php echo (null !== $monitor->Method())?validHtmlStr($monitor->Method()):'' ?>"/>
        <div class="tab-content" id="pills-tabContent">
<?php
foreach ($tabs as $name=>$value) {
  echo '<div id="pills-'.$name.'" class="tab-pane fade'.($name==$tab ? ' show active' : '').'" role="tabpanel" aria-labelledby="'.$name.'-tab">';
?>
          <ul class="form">
<?php
switch ($name) {
  case 'general' :
    {
      if (!$monitor->Id() and count($monitors)) {
        $monitor_ids = array_keys($monitors_by_id);
        $available_monitor_ids = array_diff(range(min($monitor_ids),max($monitor_ids)), $monitor_ids);
?>
              <li class="Id">
                <label><?php echo translate('Id') ?></label>
                <input type="number" step="1" min="1" name="newMonitor[Id]" placeholder="<?php echo translate('leave blank for auto') ?>"/><br/>
<?php 
        if (count($available_monitor_ids)) {
          echo 'Some available ids: '.implode(', ', array_slice($available_monitor_ids, 0, 10));
        }
?>
              </li>
<?php
      } # end if ! $monitor->Id() and count($monitors)
      if ($monitor->Deleted()) {
?>
              <li class="Deleted warning">
                <label><?php echo translate('Monitor is Deleted, Undelete') ?>?</label>
                <input type="checkbox" name="newMonitor[Deleted]" value="0"/>
              </li>
<?php
      }
?>
              <li class="Name">
                <label><?php echo translate('Name') ?></label>
                <input type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($monitor->Name()) ?>" autocomplete="monitor_name"/>
              </li>
              <li class="Notes">
                <label><?php echo translate('Notes') ?></label>
                <textarea name="newMonitor[Notes]" rows="4"><?php echo validHtmlStr($monitor->Notes()) ?></textarea>
              </li>
              <li class="Manufacturer">
                <label><?php echo translate('Manufacturer') ?></label>
<?php 
  require_once('includes/Manufacturer.php');
  $manufacturers = array(''=>translate('Unknown'));
  foreach ( ZM\Manufacturer::find( null, array('order'=>'lower(Name)')) as $Manufacturer ) {
    $manufacturers[$Manufacturer->Id()] = $Manufacturer->Name();
  }
  echo htmlSelect('newMonitor[ManufacturerId]', $manufacturers, $monitor->ManufacturerId(),
      array('class'=>'chosen','data-on-change-this'=>'ManufacturerId_onchange'));
?>
                  <input type="text" name="newMonitor[Manufacturer]"
                    placeholder="enter new manufacturer name"
                    autocomplete="new_manufacturer"
                    <?php echo $monitor->ManufacturerId() ? ' style="display:none" disabled="disabled"' : '' ?>
                    data-on-input-this="Manufacturer_onchange"
                  />
              </li>
              <li class="Model">
                <label><?php echo translate('Model') ?></label>
<?php 
  require_once('includes/Model.php');
  $models = array(''=>translate('Unknown'));
  # We still do the query even if manufacturerId is empty so that it lists models with no manufacturer
  foreach ( ZM\Model::find(array('ManufacturerId'=>$monitor->ManufacturerId()), array('order'=>'lower(Name)')) as $Model ) {
    $models[$Model->Id()] = $Model->Name();
  }
  # This is to handle a case where the model's manufacturerId didn't get set, or somehow is no longer valid
  if ($monitor->ModelId() and !isset($models[$monitor->ModelId()])) {
    $model = $monitor->Model();
    if (!$model->ManufacturerId() or !ZM\Manufacturer::find_one(['Id'=>$model->ManufacturerId()])) {
      $model->save(['ManufacturerId'=>$monitor->ManufacturerId()]);
    }
  }
  echo htmlSelect('newMonitor[ModelId]', $models, $monitor->ModelId(),
      array('class'=>'chosen', 'data-on-change-this'=>'ModelId_onchange'));
?>
                  <input type="text" name="newMonitor[Model]"
                    placeholder="enter new model name"
                    autocomplete="new_model"
                    <?php echo $monitor->ModelId() ? ' style="display:none" disabled="disabled"' : '' ?>
                    data-on-input-this="Model_onchange"
                    />
              </li>
<?php 
      $Servers = ZM\Server::find(NULL, array('order'=>'lower(Name)'));
      if (count($Servers)) {
?>
              <li class="Server">
                <label><?php echo translate('Server') ?></label>
<?php
      $servers = array(''=>'None', 'auto'=>'Auto');
      foreach ($Servers as $Server) {
        $servers[$Server->Id()] = $Server->Name();
      }
      echo htmlSelect('newMonitor[ServerId]', $servers, $monitor->ServerId());
?>
              </li>
<?php 
      } # end if count($Servers)
?>
              <li class="Type">
                <label><?php echo translate('SourceType') ?></label>
                <?php echo htmlSelect('newMonitor[Type]', $sourceTypes, $monitor->Type()); ?>
              </li>
<?php
      $groups_dropdown = ZM\Group::get_dropdown_options();
      if (count($groups_dropdown)) {
?>
              <li class="Groups">
                <label><?php echo translate('Groups'); ?></label>
                <select name="newMonitor[GroupIds][]" multiple="multiple" class="chosen"><?php
                  echo htmlOptions($groups_dropdown, $monitor->GroupIds());
                  ?></select>
              </li>
<?php 
      }
?>
              <li class="Triggers"><label><?php echo translate('Triggers') ?></label>
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
            </li>
<?php
      break;
    }
    case 'onvif' :
    {
?>
            <li class="ONVIF_URL">
              <label><?php echo translate('ONVIF_URL') ?></label>
              <input type="text" name="newMonitor[ONVIF_URL]" value="<?php echo validHtmlStr($monitor->ONVIF_URL()) ?>"/>
            </li>
            <li class="ONVIF_Events_Path">
              <label><?php echo translate('ONVIF_EVENTS_PATH') ?></label>
              <input type="text" name="newMonitor[ONVIF_Events_Path]" value="<?php echo validHtmlStr($monitor->ONVIF_Events_Path()) ?>"/>
            </li>
            <li class="ONVIF_Username">
              <label><?php echo translate('Username') ?></label>
              <input type="text" name="newMonitor[ONVIF_Username]" value="<?php echo validHtmlStr($monitor->ONVIF_Username()) ?>" autocomplete="onvif_username"/>
            </li>
            <li class="ONVIF_Password">
              <label><?php echo translate('Password') ?></label>
              <input type="password" id="newMonitor[ONVIF_Password]" name="newMonitor[ONVIF_Password]" value="<?php echo validHtmlStr($monitor->ONVIF_Password()) ?>" autocomplete="onvif_password"/>
              <span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="newMonitor[ONVIF_Password]">visibility</span>
            </li>
            <li class="ONVIF_Options">
              <label><?php echo translate('ONVIF_Options') ?></label>
              <input type="text" name="newMonitor[ONVIF_Options]" value="<?php echo validHtmlStr($monitor->ONVIF_Options()) ?>"/>
            </li>
            <li class="ONVIF_Alarm_Text">
              <label><?php echo translate('ONVIF_Alarm_Text') ?></label>
              <input type="text" name="newMonitor[ONVIF_Alarm_Text]" value="<?php echo validHtmlStr($monitor->ONVIF_Alarm_Text()) ?>"/>
            </li>
            <li class="SOAP_wsa_compl">
              <label><?php echo translate('SOAP WSA COMPLIANCE'); echo makeHelpLink('OPTIONS_SOAP_wsa') ?></label>
              <input type="checkbox" name="newMonitor[SOAP_wsa_compl]" value="1"<?php echo $monitor->SOAP_wsa_compl()  ? ' checked="checked"' : '' ?>/>
            </li>
            <li class="ONVIF_Event_Listener">
              <label><?php echo translate('ONVIF_Event_Listener') ?></label>
              <?php echo html_radio('newMonitor[ONVIF_Event_Listener]', array('1'=>translate('Enabled'), '0'=>translate('Disabled')), $monitor->ONVIF_Event_Listener()); ?>
            </li>
<?php
        break;
    }
    case 'source' :
    {
?>
            <li class="Capturing">
              <label><?php echo translate('Capturing'); echo makeHelpLink('OPTIONS_CAPTURING'); ?></label>
<?php
        echo htmlSelect('newMonitor[Capturing]', ZM\Monitor::getCapturingOptions(), $monitor->Capturing());
?>
              <div id="capturing_help">
<?php
        foreach (ZM\Monitor::getCapturingOptions() as $fn => $translated) {
          if (isset($OLANG['CAPTURING_'.strtoupper($fn)])) {
            echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['CAPTURING_'.strtoupper($fn)]['Help'].'</div>';
          }
        }
?>
                </div>
            </li>
<?php
      if ( ZM_HAS_V4L2 && $monitor->Type() == 'Local' ) {
        $devices = [''=>translate('Other')];
        foreach (glob('/dev/video*') as $device) 
          $devices[$device] = $device;
        if ($monitor->Device() and !isset($devices[$monitor->Device()]))
          $devices[$monitor->Device()] = $monitor->Device();
?>
          <li class="Device">
            <label><?php echo translate('DevicePath') ?></label>
<?php echo count($devices) > 1 ? htmlSelect('newMonitor[Devices]', $devices, $monitor->Device()) : ''; ?>
            <input type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($monitor->Device()) ?>"
<?php echo (count($devices) > 1) ? 'style="display: none;"' : '' ?> autocomplete="off"
            />
          </li>
<?php
$localMethods = array(
    'v4l2' => 'Video For Linux version 2',
    );
if (!ZM_HAS_V4L2)
  unset($localMethods['v4l2']);
if (!isset($localMethods[$monitor->Method()])) $monitor->Method(array_keys($localMethods)[0]);
if (count($localMethods)>1) {
  echo '<li><label>'.translate('CaptureMethod').'</label>';
  echo htmlSelect('newMonitor[Method]', $localMethods, $monitor->Method(), ['data-on-change'=>'submitTab', 'data-tab-name'=>$tab] );
  echo '</li>'.PHP_EOL;
} else {
  echo '<input type="hidden" name="newMonitor[Method]" value="'.validHtmlStr($monitor->Method()).'"/>'.PHP_EOL;
}
        if ( ZM_HAS_V4L2 && $monitor->Method() == 'v4l2' ) {
?>
          <li class="Channel">
            <label><?php echo translate('DeviceChannel') ?></label>
            <?php echo htmlSelect('newMonitor[Channel]', $v4l2DeviceChannels, $monitor->Channel()); ?>
          </li>
          <li class="Format">
            <label><?php echo translate('DeviceFormat') ?></label>
            <?php echo htmlSelect('newMonitor[Format]', $v4l2DeviceFormats, $monitor->Format()); ?>
          </li>
          <li class="Palette">
            <label><?php echo translate('CapturePalette') ?></label>
            <?php echo htmlSelect('newMonitor[Palette]', $v4l2LocalPalettes, $monitor->Palette()); ?>
          </li>
<?php
        }
?>
          <li class="V4LMultiBuffer"><label><?php echo translate('V4LMultiBuffer') ?></label>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $monitor->V4LMultiBuffer() == '1' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]1">Yes</label>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $monitor->V4LMultiBuffer() == '0' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]0">No</label>
            <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( $monitor->V4LMultiBuffer() == '' ? 'checked="checked"' : '' ) ?>/>
            <label for="newMonitor[V4LMultiBuffer]">Use Config Value
          </li>
          <li class="V4LCapturesPerFrame"api:
  origin: "*"
>
            <label><?php echo translate('V4LCapturesPerFrame') ?></label>
            <input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo validHtmlStr($monitor->V4LCapturesPerFrame()); ?>" min="1"/>
          </li>
<?php

      } else if ( $monitor->Type() == 'NVSocket' ) {
include('_monitor_source_nvsocket.php');
      } else if ( $monitor->Type() == 'VNC' ) {
?>
        <li class="Host">
          <label><?php echo translate('RemoteHostName') ?></label>
          <input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>"/>
        </li>
        <li class="Port">
          <label><?php echo translate('RemoteHostPort') ?></label>
          <input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" step="1" min="1" max="65536" />
        </li>
        <li class="User">
          <label><?php echo translate('Username') ?></label>
          <input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>" autocomplete="source_username"/>
        </li>
        <li class="Pass">
          <label><?php echo translate('Password') ?></label>
          <input type="password" id="newMonitor[Pass]" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>" autocomplete="source_password"/>
          <span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="newMonitor[Pass]">visibility</span>
        </li>
<?php
      } else if ( $monitor->Type() == 'Remote' ) {
?>
          <li class="User"><label><?php echo translate('Username') ?></label>
            <input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>"/>
          </li>
          <li class="Pass">
            <label><?php echo translate('Password') ?></label>
              <input type="password" id="newMonitor[Pass]" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>" autocomplete="source_password"/>
              <span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="newMonitor[Pass]">visibility</span>
          </li>
          <li class="Protocol">
            <label><?php echo translate('RemoteProtocol') ?></label>
            <?php echo htmlSelect('newMonitor[Protocol]', $remoteProtocols, $monitor->Protocol(), ['data-on-change-this'=>'updateMethods'] ); ?>
          </li>
          <li class="Method">
            <label><?php echo translate('RemoteMethod') ?></label>
<?php
        if ( !$monitor->Protocol() || $monitor->Protocol() == 'http' ) {
          echo htmlSelect('newMonitor[Method]', $httpMethods, $monitor->Method());
        } else {
          echo htmlSelect('newMonitor[Method]', $rtspMethods, $monitor->Method());
        }
?>
          </li>
          <li class="Host">
            <label><?php echo translate('RemoteHostName') ?></label>
            <input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>"/>
          </li>
          <li class="Port"><label><?php echo translate('RemoteHostPort') ?></label>
            <input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" min="0" max="65535"/>
          </li>
          <li class="Path"><label><?php echo translate('RemoteHostPath') ?></label>
            <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/>
          </li>
<?php
      } else if ( $monitor->Type() == 'File' ) {
?>
          <li class="Path">
            <label><?php echo translate('SourcePath') ?></label>
            <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/>
          </li>
<?php
      } elseif ( $monitor->Type() == 'WebSite' ) {
?>
          <li class="Path">
            <label><?php echo translate('WebSiteUrl') ?></label>
            <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>"/>
          </li>
          <li class="Width">
            <label><?php echo translate('Width') ?> (<?php echo translate('Pixels') ?>)</label>
            <input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1" step="1"/>
          </li>
          <li class="Height">
            <label><?php echo translate('Height') ?> (<?php echo translate('Pixels') ?>)</label>
            <input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1" step="1"/>
          </li>
	        <li class="Refresh">
            <label><?php echo 'Web Site Refresh (Optional)' ?></label>
            <input type="number" name="newMonitor[Refresh]" value="<?php echo validHtmlStr($monitor->Refresh()) ?>" min="1" step="1"/>
          </li>
<?php
      } else if ( $monitor->Type() == 'Ffmpeg' || $monitor->Type() == 'Libvlc' ) {
?>
          <li class="SourcePath">
            <label><?php echo translate('SourcePath') ?></label>
            <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" />
          </li>
          <li class="User"><label><?php echo translate('Username') ?></label>
            <input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>" autocomplete="source_username"/>
          </li>
          <li class="Pass">
            <label><?php echo translate('Password') ?></label>
            <input type="password" id="newMonitor[Pass]" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>" autocomplete="source_password"/>
            <span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="newMonitor[Pass]">visibility</span>
          </li>
          <li class="Method">
            <label><?php echo translate('RemoteMethod'); echo makeHelpLink('OPTIONS_RTSPTrans') ?></label>
            <?php echo htmlSelect('newMonitor[Method]', $rtspFFMpegMethods, $monitor->Method()) ?>
          <li>
          <li class="SourceOptions">
            <label><?php echo translate('Options'); echo makeHelpLink('OPTIONS_'.strtoupper($monitor->Type())) ?></label>
            <input type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($monitor->Options()) ?>"/>
          <li>
<?php
      }
?>
          <li class="Decoding">
            <label><?php echo translate('Decoding'); echo makeHelpLink('FUNCTION_DECODING');?></label>
            
<?php
        echo htmlSelect('newMonitor[Decoding]', ZM\Monitor::getDecodingOptions(), $monitor->Decoding());
?>
                <div id="decoding_help">
<?php
        foreach (ZM\Monitor::getDecodingOptions() as $fn => $translated) {
          if (isset($OLANG['FUNCTION_DECODING_'.strtoupper($fn)])) {
            echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['FUNCTION_DECODING_'.strtoupper($fn)]['Help'].'</div>';
          }
        }
?>
                </div>
            
          <li>
<?php
      if ( $monitor->Type() == 'Ffmpeg' ) {
?>
          <li class="SourceSecondPath">
            <label><?php echo translate('SourceSecondPath') ?></label>
            <input type="text" name="newMonitor[SecondPath]" value="<?php echo validHtmlStr($monitor->SecondPath()) ?>" data-on-input-this="SecondPath_onChange"/>
          <li>
          <li class="Decoder">
            <label><?php echo translate('Decoder') ?></label>
<?php
$decoders = array(
  'auto' => translate('Auto'),
  'libx264' => 'libx264',
  'h264' => 'h264',
  'h264_cuvid' => 'h264_cuvid',
  'h264_nvmpi' => 'h264_nvmpi',
  'h264_mmal'   => 'h264_mmal',
  'h264_omx' => 'h264_omx',
  'h264_qsv' => 'h264_qsv',
  'h264_vaapi' => 'h264_vaapi',
  'h264_v4l2m2m' => 'h264_v4l2m2m',
  'libx265' => 'libx265',
  'hevc_cuvid' => 'hevc_cuvid',
  'hevc_nvmpi' => 'hevc_nvmpi',
  'hevc_qsv' => 'hevc_qsv',
  'vp8_nvmpi' => 'vp8_nvmpi',
  'libvpx-vp9' => 'libvpx-vp9',
  'vp9_qsv' => 'vp9-qsv',
  'vp9_cuvid' => 'vp9_cuvid',
  'vp9_nvmpi' => 'vp9_nvmpi',
  'vp9_v4l2m2m' => 'vp9_v4l2m2m',
  'libsvtav1' => 'libsvtav1',
  'libaom-av1'  => 'libaom-av1',
  'libdav1d'    => 'libdav1d',
  'av1' => 'av1',
  'av1_qsv' => 'av1_qsv',
  'av1_cuvid' => 'av1_cuvid',
);
echo htmlSelect('newMonitor[Decoder]', $decoders, $monitor->Decoder());
?>
            </li>
          <li class="DecoderHWAccelName">
            <label>
              <?php echo translate('DecoderHWAccelName'); echo makeHelpLink('OPTIONS_DECODERHWACCELNAME') ?>
            </label>
            <input type="text" name="newMonitor[DecoderHWAccelName]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelName()) ?>"/>
          <li>
          <li class="DecoderHWAccelDevice">
            <label><?php echo translate('DecoderHWAccelDevice') ?>
                <?php echo makeHelpLink('OPTIONS_DECODERHWACCELDEVICE') ?>
            </label>
            <input type="text" name="newMonitor[DecoderHWAccelDevice]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelDevice()) ?>"/>
          <li>
<?php
      }
      if ( $monitor->Type() != 'NVSocket' && $monitor->Type() != 'WebSite' ) {
?>
        <li class="TargetColorspace">
          <label><?php echo translate('TargetColorspace') ?></label>
          <?php echo htmlSelect('newMonitor[Colours]', $Colours, $monitor->Colours()) ?>
        </li>
        <li class="CaptureResolution">
          <label><?php echo translate('CaptureResolution') ?> (<?php echo translate('Pixels') ?>)</label>
          
            <input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1" step="1"/>
            <input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1" step="1"/>
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
            '2560x1920'=>'2560x1920 5MP',
            '2688x1520'=>'2688x1520 4MP',
	    '2960x1668'=>'2960x1668 5MP',
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
          
        </li>
        <li class="PreserveAspect">
          <label><?php echo translate('PreserveAspect') ?></label>
          <input type="checkbox" name="preserveAspectRatio" value="1"/>
        </li>
        <li class="Orientation">
          <label><?php echo translate('Orientation') ?></label>
          <?php echo htmlselect('newMonitor[Orientation]', $orientations, $monitor->Orientation());?>
        </li>
<?php
      }
      if ( $monitor->Type() == 'Local' ) {
?>
            <li class="Deinterlacing">
              <label><?php echo translate('Deinterlacing') ?></label>
              <?php echo htmlselect('newMonitor[Deinterlacing]', $deinterlaceopts_v4l2, $monitor->Deinterlacing())?>
            </li>
<?php
        } else if ( $monitor->Type() != 'WebSite' ) {
?>
            <li class="Deinterlacing">
              <label><?php echo translate('Deinterlacing') ?></label>
              <?php echo htmlselect('newMonitor[Deinterlacing]', $deinterlaceopts, $monitor->Deinterlacing())?>
            </li>
<?php
        }
        if ( $monitor->Type() == 'Remote' ) {
          ?>
            <li id="RTSPDescribe"<?php if ( $monitor->Protocol()!= 'rtsp' ) { echo ' style="display:none;"'; } ?>>
              <label><?php echo translate('RTSPDescribe'); echo makeHelpLink('OPTIONS_RTSPDESCRIBE') ?></label>
              <input type="checkbox" name="newMonitor[RTSPDescribe]" value="1"<?php if ( $monitor->RTSPDescribe() ) { ?> checked="checked"<?php } ?>/>
            </li>
<?php
      } # end if monitor->Type() == 'Remote'
?>
            <li class="MaxFPS">
              <label><?php echo translate('MaximumFPS'); echo makeHelpLink('OPTIONS_MAXFPS') ?></label>
              <input type="number" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($monitor->MaxFPS()) ?>" min="0" step="any"/>
<?php
      if ( $monitor->Type() != 'Local' && $monitor->Type() != 'File' && $monitor->Type() != 'NVSocket' ) {
?>
                <span id="newMonitor[MaxFPS]" style="color:red;<?php echo $monitor->MaxFPS() ? '' : 'display:none;' ?>">CAUTION: See the help text</span>
<?php } ?>
              
            </li>
            <li class="AlarmMaximumFPS">
              <label><?php echo translate('AlarmMaximumFPS'); echo makeHelpLink('OPTIONS_ALARMMAXFPS') ?></label>
              <input type="number" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($monitor->AlarmMaxFPS()) ?>" min="0" step="any"/>
<?php
      if ( $monitor->Type() != 'Local' && $monitor->Type() != 'File' && $monitor->Type() != 'NVSocket' ) {
?>
              <span id="newMonitor[AlarmMaxFPS]" style="color:red;<?php echo $monitor->AlarmMaxFPS() ? '' : 'display:none;' ?>">CAUTION: See the help text</span>
<?php } ?>
            </li>
<?php
      break;
    }
    case 'analysis' : {
?>
            <li class="Analysing">
              <label><?php echo translate('Motion Detection') ?></label>
              
<?php
        echo htmlSelect('newMonitor[Analysing]', ZM\Monitor::getAnalysingOptions(),
            $monitor->Analysing(), array('data-on-change-this'=>'Analysing_onChange'));
?>
              <div id="Analysing_help">
<?php
        foreach (ZM\Monitor::getAnalysingOptions() as $fn => $translated) {
          if (isset($OLANG['ANALYSING_'.strtoupper($fn)])) {
            echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['ANALYSING_'.strtoupper($fn)]['Help'].'</div>';
          }
        }
?>
                </div>
            </li>
            <li id="AnalysisSource"<?php echo $monitor->SecondPath() ? '' : ' style="display:none;"' ?>>
              <label><?php echo translate('AnalysisSource') ?></label>
              
<?php
        echo htmlSelect('newMonitor[AnalysisSource]', ZM\Monitor::getAnalysisSourceOptions(), $monitor->AnalysisSource());
?>
            </li>
            <li id="AnalysisImage" class="AnalysisImage">
              <label><?php echo translate('Analysis Image') ?></label>
              
<?php
        echo htmlSelect('newMonitor[AnalysisImage]', ZM\Monitor::getAnalysisImageOptions(), $monitor->AnalysisImage());
?>
              
            </li>
            <li class="AnalysisFPS">
              <label><?php echo translate('AnalysisFPS') ?></label>
              <input type="number" name="newMonitor[AnalysisFPSLimit]" value="<?php echo validHtmlStr($monitor->AnalysisFPSLimit()) ?>" min="0" step="any"/>
            </li>
<?php
      if ( ZM_FAST_IMAGE_BLENDS ) {
?>
              <li class="RefBlendPerc">
                <label><?php echo translate('RefImageBlendPct') ?></label>
                <?php echo htmlSelect('newMonitor[RefBlendPerc]', $fastblendopts, $monitor->RefBlendPerc()); ?>
              </li>
              <li class="AlarmRefBlendPerc">
                <label><?php echo translate('AlarmRefImageBlendPct') ?></label>
                <?php echo htmlSelect('newMonitor[AlarmRefBlendPerc]', $fastblendopts_alarm, $monitor->AlarmRefBlendPerc()); ?>
              </li>
          <?php
      } else {
?>
            <li class="RefBlendPerc">
              <label><?php echo translate('RefImageBlendPct') ?></label>
              <input type="number" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($monitor->RefBlendPerc()) ?>" step="any" min="0"/>
            </li>
            <li class="AlarmRefImageBlendPct">
              <label><?php echo translate('AlarmRefImageBlendPct') ?></label>
              <input type="number" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($monitor->AlarmRefBlendPerc()) ?>" step="any" min="0"/>
            </li>
<?php
      }
?>
            <li class="LinkedMonitors">
              <label><?php echo translate('LinkedMonitors'); echo makeHelpLink('OPTIONS_LINKED_MONITORS') ?></label>
              <input type="text" name="newMonitor[LinkedMonitors]" value="<?php echo $monitor->LinkedMonitors() ?>" data-on-input="updateLinkedMonitorsUI"/><br/>
              <div id="LinkedMonitorsUI"></div>
            </li>
            <li id="function_use_Amcrest_API" class="use_Amcreat_API">
              <label><?php echo translate('use_Amcrest_API') ?></label>
              <?php echo html_radio('newMonitor[use_Amcrest_API]', array('1'=>translate('Enabled'), '0'=>translate('Disabled')), $monitor->use_Amcrest_API()); ?>
            </li>
<?php
    }
    break;
  case 'recording' :
    {
?>
          <li class="Recording">
            <label><?php echo translate('Recording') ?></label>
            
<?php
      echo htmlSelect('newMonitor[Recording]', ZM\Monitor::getRecordingOptions(),
        $monitor->Recording(),
        array('data-on-change-this'=>'Recording_onChange'));
?>
            <div id="Recording_help">
  <?php
          foreach (ZM\Monitor::getRecordingOptions() as $fn => $translated) {
            if (isset($OLANG['RECORDING_'.strtoupper($fn)])) {
              echo '<div class="form-text" id="'.$fn.'Help">'.$OLANG['RECORDING_'.strtoupper($fn)]['Help'].'</div>';
            }
          }
  ?>
            </div>
          </li>
          <li id="RecordingSource"<?php echo $monitor->SecondPath() ? '' : ' style="display:none;"' ?>>
            <label><?php echo translate('RecordingSource') ?></label>
<?php
        echo htmlSelect('newMonitor[RecordingSource]', ZM\Monitor::getRecordingSourceOptions(), $monitor->RecordingSource());
?>
          </li>
<?php
      $storage_areas = array(0=>translate('Unspecified'));
      foreach ( ZM\Storage::find(array('Enabled'=>true), array('order'=>'lower(Name)')) as $Storage ) {
        $storage_areas[$Storage->Id()] = $Storage->Name();
      }
       if (count($storage_areas) > 1) {
         echo '<li class="StorageArea"><label>'.translate('StorageArea').'</label>'.PHP_EOL;
         echo htmlSelect('newMonitor[StorageId]', $storage_areas, $monitor->StorageId());
         echo PHP_EOL.'</li>'.PHP_EOL;
       }
?>
          <li class="SaveJPEGs">
            <label><?php echo translate('SaveJPEGs') ?></label>
            
<?php
      $savejpegopts = array(
        0 => translate('Disabled'),
        1 => translate('Frames only'),
        2 => translate('Analysis images only (if available)'),
        3 => translate('Frames + Analysis images (if available)'),
      );
      echo htmlSelect('newMonitor[SaveJPEGs]', $savejpegopts, $monitor->SaveJPEGs());
?>
             
            </li>
            <li class="VideoWriter">
              <label><?php echo translate('VideoWriter') ?></label>
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
            </li>
            <li class="OutputCodec">
              <label><?php echo translate('OutputCodec') ?></label>
<?php
$videowriter_codecs = array(
  'auto' => translate('Auto'),
  'h264' => 'h264',
  'hevc' => 'h265/hevc',
  'vp9' => 'vp9',
  'av1' => 'av1',
);
echo htmlSelect('newMonitor[OutputCodecName]', $videowriter_codecs, $monitor->OutputCodecName());
?>
            </li>
            <li class="Encoder">
              <label><?php echo translate('Encoder') ?></label>
              
<?php
$videowriter_encoders = array(
  'auto' => translate('Auto'),
  'libx264' => 'libx264',
  'h264' => 'h264',
  'h264_nvenc' => 'h264_nvenc',
  'h264_omx' => 'h264_omx',
  'h264_qsv' => 'h264_qsv',
  'h264_vaapi' => 'h264_vaapi',
  'h264_v4l2m2m' => 'h264_v4l2m2m',
  'libx265' => 'libx265',
  'hevc_nvenc' => 'hevc_nvenc',
  'hevc_qsv' => 'hevc_qsv',
  'hevc_vaapi' => 'hevc_vaapi',
  'libvpx-vp9' => 'libvpx-vp9',
  'vp9-qsv' => 'vp9-qsv',
  'libsvtav1' => 'libsvtav1',
  'libaom-av1'  => 'libaom-av1',
  'av1_qsv' => 'av1_qsv',
  'av1_vaapi' => 'av1_vaapi',
  'av1_nvenc' => 'av1_nvenc'
);
echo htmlSelect('newMonitor[Encoder]', $videowriter_encoders, $monitor->Encoder());
?>
            </li>
            <li class="EncoderHWAccelName">
              <label>
                <?php echo translate('EncoderHWAccelName'); echo makeHelpLink('OPTIONS_ENCODERHWACCELNAME') ?>
              </label>
              <input type="text" name="newMonitor[EncoderHWAccelName]" value="<?php echo validHtmlStr($monitor->EncoderHWAccelName()) ?>"/>
            <li>
            <li class="EncoderHWAccelDevice">
              <label><?php echo translate('EncoderHWAccelDevice') ?>
                  <?php echo makeHelpLink('OPTIONS_ENCODERHWACCELDEVICE') ?>
              </label>
              <input type="text" name="newMonitor[EncoderHWAccelDevice]" value="<?php echo validHtmlStr($monitor->EncoderHWAccelDevice()) ?>"/>
            <li>
            <li class="OutputContainer">
              <label><?php echo translate('OutputContainer') ?></label>
<?php
$videowriter_containers = array(
  '' => translate('Auto'),
  'mp4' => 'mp4',
  'mkv' => 'mkv',
  'webm' => 'webm',
);
echo htmlSelect('newMonitor[OutputContainer]', $videowriter_containers, $monitor->OutputContainer());
?>
            </li>
            <li class="EncoderParameters">
              <label><?php echo translate('OptionalEncoderParam'); echo makeHelpLink('OPTIONS_ENCODER_PARAMETERS') ?></label>
              <textarea name="newMonitor[EncoderParameters]" rows="<?php echo count(explode("\n", $monitor->EncoderParameters())); ?>"><?php echo validHtmlStr($monitor->EncoderParameters()) ?></textarea>
              
            </li>
            <li class="WallClockTimeStamps">
              <label><?php echo translate('Use Wallclock Timestamps') ?></label>
              <input type="checkbox" name="newMonitor[WallClockTimestamps]" value="1"<?php if ( $monitor->WallClockTimestamps() ) { ?> checked="checked"<?php } ?>/>
            </li>
            <li class="RecordAudio">
              <label><?php echo translate('RecordAudio') ?></label>
<?php if ( $monitor->Type() == 'Ffmpeg' ) { ?>
              <input type="checkbox" name="newMonitor[RecordAudio]" value="1"<?php if ( $monitor->RecordAudio() ) { ?> checked="checked"<?php } ?>/>
<?php } else { ?>
              <?php echo translate('Audio recording only available with FFMPEG')?>
              <input type="hidden" name="newMonitor[RecordAudio]" value="<?php echo $monitor->RecordAudio() ? 1 : 0 ?>"/>
<?php } ?>
            </li>
            <li class="EventStartCommand">
              <label><?php echo translate('Event Start Command') ?></label>
              <input type="text" name="newMonitor[EventStartCommand]" value="<?php echo validHtmlStr($monitor->EventStartCommand()) ?>" />
            </li>
            <li class="EventEndCommand">
              <label><?php echo translate('Event End Command') ?></label>
              <input type="text" name="newMonitor[EventEndCommand]" value="<?php echo validHtmlStr($monitor->EventEndCommand()) ?>" />
            <li>
<?php
      break;
    }
  case 'viewing' :
?>
            <li class="RTSPServer">
              <label><?php echo translate('RTSPServer'); echo makeHelpLink('OPTIONS_RTSPSERVER') ?></label>
              <input type="checkbox" name="newMonitor[RTSPServer]" value="1"<?php echo $monitor->RTSPServer() ? ' checked="checked"' : '' ?>/>
            </li>
            <li class="RTSPStreamName">
              <label><?php echo translate('RTSPStreamName'); echo makeHelpLink('OPTIONS_RTSPSTREAMNAME') ?></label>
              <input type="text" name="newMonitor[RTSPStreamName]" value="<?php echo validHtmlStr($monitor->RTSPStreamName()) ?>"/>
            </li>
            <li id="FunctionGo2RTCEnabled">
              <label><?php echo translate('Go2RTC Live Stream') ?></label>
              <input type="checkbox" name="newMonitor[Go2RTCEnabled]" value="1"<?php echo $monitor->Go2RTCEnabled() ? ' checked="checked"' : '' ?> on_click="update_players"/>
<?php
  if ( isset($OLANG['FUNCTION_GO2RTC_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_GO2RTC_ENABLED']['Help'].'</div>';
  }
?>
            </li>
            <li id="FunctionRTSP2WebEnabled">
              <label><?php echo translate('RTSP2Web Live Stream') ?></label>
              <input type="checkbox" name="newMonitor[RTSP2WebEnabled]" value="1"<?php echo $monitor->RTSP2WebEnabled() ? ' checked="checked"' : '' ?> on_click="update_players"/>
<?php
  if ( isset($OLANG['FUNCTION_RTSP2WEB_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_RTSP2WEB_ENABLED']['Help'].'</div>';
  }
?>
            </li>
            <li id="RTSP2WebStream">
              <label><?php echo translate('Stream source') ?> </label>
              <?php echo htmlSelect('newMonitor[RTSP2WebStream]', ZM\Monitor::getRTSP2WebStreamOptions(), $monitor->RTSP2WebStream()); ?>
            </li>
            <li id="FunctionJanusEnabled">
              <label><?php echo translate('Janus Live Stream') ?></label>
              <input type="checkbox" name="newMonitor[JanusEnabled]" value="1"<?php echo $monitor->JanusEnabled() ? ' checked="checked"' : '' ?> on_click="update_players"/>
<?php
  if ( isset($OLANG['FUNCTION_JANUS_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_JANUS_ENABLED']['Help'].'</div>';
  }
?>
            </li>
            <li id="FunctionJanusAudioEnabled">
              <label><?php echo translate('Janus Live Stream Audio') ?></label>
              <input type="checkbox" name="newMonitor[JanusAudioEnabled]" value="1"<?php echo $monitor->JanusAudioEnabled() ? ' checked="checked"' : '' ?>/>
<?php
  if ( isset($OLANG['FUNCTION_JANUS_AUDIO_ENABLED']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_JANUS_AUDIO_ENABLED']['Help'].'</div>';
  }
?>
            </li>
            <li id="FunctionJanusProfileOverride">
              <label><?php echo translate('Janus Profile-ID Override') ?></label>
              <input type="text" name="newMonitor[Janus_Profile_Override]" value="<?php echo $monitor->Janus_Profile_Override()?>"/>
<?php
  if ( isset($OLANG['FUNCTION_JANUS_PROFILE_OVERRIDE']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_JANUS_PROFILE_OVERRIDE']['Help'].'</div>';
  }
?>
            </li>
            <li id="FunctionJanusRTSPSessionTimeout">
              <label><?php echo translate('Janus RTSP Session Timeout Override') ?></label>
              <input type="text" name="newMonitor[Janus_RTSP_Session_Timeout]" value="<?php echo $monitor->Janus_RTSP_Session_Timeout()?>"/>
<?php
  if ( isset($OLANG['FUNCTION_JANUS_RTSP_SESSION_TIMEOUT']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_JANUS_RTSP_SESSION_TIMEOUT']['Help'].'</div>';
  }
?>
            </li>
            <li id="FunctionJanusUseRTSPRestream">
              <label><?php echo translate('Janus Use RTSP Restream') ?></label>
              <input type="checkbox" name="newMonitor[Janus_Use_RTSP_Restream]" value="1"<?php echo $monitor->Janus_Use_RTSP_Restream() ? ' checked="checked"' : '' ?>/>
<?php
  if ( isset($OLANG['FUNCTION_JANUS_USE_RTSP_RESTREAM']) ) {
    echo '<div class="form-text">'.$OLANG['FUNCTION_JANUS_USE_RTSP_RESTREAM']['Help'].'</div>';
  }
?>
              
            </li>
            <li id="Janus_RTSP_User" <?php echo (!ZM_OPT_USE_AUTH or !$monitor->Janus_Use_RTSP_Restream()) ? 'style="display:none;"' : ''?>>
              <label><?php echo translate('User for RTSP Server Auth') ?></label>
              <?php
                $users = array(''=>translate('None'));
                foreach (ZM\User::find() as $u) {
                  if (!$monitor->Id() or !$monitor->canView($u))
                    continue;
                  $users[$u->Id()] = $u->Username();
                }
                echo htmlSelect("newMonitor[Janus_RTSP_User]", $users, $monitor->Janus_RTSP_User());
?>
              
            </li>
            <li id="DefaultPlayer">
              <label><?php echo translate('Default Player') ?></label>
<?php 
  $players = [
    ''=>translate('Auto'),
    'zms'=>'ZMS MJPEG',
    'go2rtc' => 'Go2RTC Auto',
    'go2rtc_webrtc' => 'Go2RTC WEBRTC',
    'go2rtc_mse' => 'Go2RTC MSE',
    'go2rtc_hls' => 'Go2RTC HLS',
    'rtsp2web_webrtc' => 'RTSP2Web WEBRTC',
    'rtsp2web_mse' => 'RTSP2Web MSE',
    'rtsp2web_hls' => 'RTSP2Web HLS',
    'janus' => 'Janus'
  ];

echo htmlSelect('newMonitor[DefaultPlayer]', $players, $monitor->DefaultPlayer()); ?>
            </li>
            <li>
              <label><?php echo translate('DefaultRate') ?></label>
              <?php echo htmlSelect('newMonitor[DefaultRate]', $rates, $monitor->DefaultRate()); ?>
            </li>
            <li>
              <label><?php echo translate('DefaultScale') ?></label>
              <?php echo htmlSelect('newMonitor[DefaultScale]', $scales, $monitor->DefaultScale()); ?>
            </li>
            <li>
              <label><?php echo translate('DefaultCodec') ?></label>
              <?php
$codecs = array(
  'auto'  => translate('Auto'),
  'MP4'   => translate('MP4'),
  'MJPEG' => translate('MJPEG'),
);
 echo htmlSelect('newMonitor[DefaultCodec]', $codecs, $monitor->DefaultCodec()); ?>
            </li>
            <li>
<?php
  $stream_available = canView('Stream') and $monitor->Type()=='WebSite' or ($monitor->CaptureFPS() && $monitor->Capturing() != 'None');
  $options = array();

  $ratio_factor = $monitor->ViewWidth() ? $monitor->ViewHeight() / $monitor->ViewWidth() : 1;
  $options['width'] = ZM_WEB_LIST_THUMB_WIDTH;
  $options['height'] = ZM_WEB_LIST_THUMB_HEIGHT ? ZM_WEB_LIST_THUMB_HEIGHT : ZM_WEB_LIST_THUMB_WIDTH*$ratio_factor;
  $options['scale'] = $monitor->ViewWidth() ? intval(100*ZM_WEB_LIST_THUMB_WIDTH / $monitor->ViewWidth()) : 100;
  $options['mode'] = 'jpeg';
  $options['frames'] = 1;

  $stillSrc = $monitor->getStreamSrc($options);
  $streamSrc = $monitor->getStreamSrc(array('scale'=>$options['scale']*5));

  $thmbWidth = ( $options['width'] ) ? 'width:'.$options['width'].'px;' : '';
  $thmbHeight = ( $options['height'] ) ? 'height:'.$options['height'].'px;' : '';

  $imgHTML = '<div class="colThumbnail" style="'.$thmbHeight.'"><a';
  $imgHTML .= $stream_available ? ' href="?view=watch&amp;mid='.$monitor->Id().'">' : '>';
  $imgHTML .= '<img id="thumbnail' .$monitor->Id(). '" src="' .$stillSrc. '" style="'
    .$thmbWidth.$thmbHeight. '" stream_src="' .$streamSrc. '" still_src="' .$stillSrc. '"'.
    ($options['width'] ? ' width="'.$options['width'].'"' : '' ).
    ($options['height'] ? ' height="'.$options['height'].'"' : '' ).
    ' loading="lazy" /></a></div>';
  echo $imgHTML;
?>
            </li>
<?php
    break;
  case 'timestamp' :
    {
?>
            <li>
              <label><?php echo translate('TimestampLabelFormat') ?></label>
              <input type="text" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($monitor->LabelFormat()) ?>" placeholder="<?php echo translate('Python strftime format. %f for hundredths, %N for Monitor Name, %Q for show text.') ?>"/>
            </li>
            <li>
              <label><?php echo translate('TimestampLabelX') ?></label>
              <input type="number" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($monitor->LabelX()) ?>" min="0"/>
            </li>
            <li>
              <label><?php echo translate('TimestampLabelY') ?></label>
              <input type="number" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($monitor->LabelY()) ?>" min="0"/>
            </li>
            <li>
              <label><?php echo translate('TimestampLabelSize') ?></label>
              <?php echo htmlselect('newMonitor[LabelSize]', $label_size, $monitor->LabelSize()) ?>
            </li>
<?php
      break;
    }
  case 'buffers' :
    {
?>
            <li class="ImageBufferCount">
              <label><?php echo translate('ImageBufferSize'); echo makeHelpLink('ImageBufferCount'); ?></label>
              <input type="number" name="newMonitor[ImageBufferCount]" value="<?php echo validHtmlStr($monitor->ImageBufferCount()) ?>" min="1"/>
            </li>
            <li class="MaxImageBufferCount">
              <label><?php echo translate('MaxImageBufferCount'); echo makeHelpLink('MaxImageBufferCount'); ?></label>
              <input type="number" id="newMonitor[MaxImageBufferCount]" name="newMonitor[MaxImageBufferCount]" value="<?php echo validHtmlStr($monitor->MaxImageBufferCount()) ?>" min="0"/>
            </li>
            <li class="WarmupCount">
              <label><?php echo translate('WarmupFrames') ?></label>
              <input type="number" name="newMonitor[WarmupCount]" value="<?php echo validHtmlStr($monitor->WarmupCount()) ?>" min="0"/>
            </li>
            <li class="PreEventCount">
              <label><?php echo translate('PreEventImageBuffer') ?></label>
              <input type="number" id="newMonitor[PreEventCount]" name="newMonitor[PreEventCount]" value="<?php echo validHtmlStr($monitor->PreEventCount()) ?>" min="0"/>
            </li>
            <li class="PostEventCount">
              <label><?php echo translate('PostEventImageBuffer') ?></label>
              <input type="number" name="newMonitor[PostEventCount]" value="<?php echo validHtmlStr($monitor->PostEventCount()) ?>" min="0"/>
            </li>
            <li class="StreamReplayBuffer">
              <label><?php echo translate('StreamReplayBuffer') ?></label>
              <input type="number" name="newMonitor[StreamReplayBuffer]" value="<?php echo validHtmlStr($monitor->StreamReplayBuffer()) ?>" min="0"/>
            </li>
            <li class="AlarmFrameCount">
              <label><?php echo translate('AlarmFrameCount') ?></label>
              <input type="number" name="newMonitor[AlarmFrameCount]" value="<?php echo validHtmlStr($monitor->AlarmFrameCount()) ?>" min="1"/>
            </li>
            <li class="EstimatedRamUse">
              <label><?php echo translate('Estimated Ram Use') ?></label>
              <span id="estimated_ram_use"></span>
            </li>
<?php
      break;
    }
  case 'control' :
    {
?>
            <li>
              <label><?php echo translate('Controllable') ?></label>
              <input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( $monitor->Controllable() ) { ?> checked="checked"<?php } ?>/>
            </li>
            <li>
              <label><?php echo translate('ControlType') ?></label>
<?php 
                  $controls = ZM\Control::find(null, array('order'=>'lower(Name)'));
                  $controlTypes = array(''=>translate('None'));
                  foreach ( $controls as $control ) {
                    $controlTypes[$control->Id()] = $control->Name();
                  }

                  echo htmlSelect('newMonitor[ControlId]', $controlTypes, $monitor->ControlId(), ['id'=>'ControlId', 'data-on-click-this'=>'ControlId_onChange']);
                  if (canEdit('Control')) {
                    if ($monitor->ControlId()) {
                      echo '&nbsp;<button type="button" data-on-click="ControlEdit_onClick" id="ControlEdit">'.translate('Edit').'</button>';
                    }
                    echo '&nbsp;<button type="button" data-on-click="ControlList_onClick" id="ControlList">'.translate('List').'</button>';
                  }
?>
            </li>
            <li>
              <label><?php echo translate('ControlDevice') ?></label>
              <input type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($monitor->ControlDevice()) ?>"/>
            </li>
            <li>
              <label><?php echo translate('ControlAddress') ?></label>
              <input type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($monitor->ControlAddress()) ?>" placeholder="user:pass@ip"/>
            </li>
            <li>
              <label><?php echo translate('ModectDuringPTZ') ?></label>
              <input type="checkbox" name="newMonitor[ModectDuringPTZ]" value="1"<?php if ( $monitor->ModectDuringPTZ() ) { ?> checked="checked"<?php } ?>/>
            </li>
            <li>
              <label><?php echo translate('AutoStopTimeout') ?></label>
              <input type="number" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($monitor->AutoStopTimeout()) ?>" min="0" step="any"/>
            </li>
            <li>
              <label><?php echo translate('TrackMotion') ?></label>
              <input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( $monitor->TrackMotion() ) { ?> checked="checked"<?php } ?>/>
            </li>
            <li>
              <label><?php echo translate('TrackDelay') ?></label>
              <input type="number" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($monitor->TrackDelay()) ?>" min="0" step="any"/>
            </li>
            <li>
              <label><?php echo translate('ReturnLocation') ?></label>
<?php
      $return_options = array(
          '-1' => translate('None'),
          '0' => translate('Home'),
          '1' => translate('Preset').' 1',
      );
echo htmlSelect('newMonitor[ReturnLocation]', $return_options, $monitor->ReturnLocation()); ?>
            </li>
            <li>
              <label><?php echo translate('ReturnDelay') ?></label>
              <input type="number" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($monitor->ReturnDelay()) ?>" min="0" step="any"/>
            </li>
<?php
      break;
    }
  case 'x10' :
    {
?>
            <li>
              <label><?php echo translate('X10ActivationString') ?></label>
              <input type="text" name="newX10Monitor[Activation]" value="<?php echo validHtmlStr($newX10Monitor['Activation']) ?>" size="20"/>
            </li>
            <li>  
              <label><?php echo translate('X10InputAlarmString') ?></label>
              <input type="text" name="newX10Monitor[AlarmInput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmInput']) ?>" size="20"/>
            </li>
            <li>
              <label><?php echo translate('X10OutputAlarmString') ?></label>
              <input type="text" name="newX10Monitor[AlarmOutput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmOutput']) ?>" size="20"/>
            </li>
<?php
      break;
    }
  case 'misc' :
    {
?>
        <li>
          <label><?php echo translate('EventPrefix') ?></label>
          <input type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($monitor->EventPrefix()) ?>"/>
        </li>
        <li>
          <label><?php echo translate('Sectionlength') ?></label>
            <input type="number" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($monitor->SectionLength()) ?>" min="0"/>
            <?php echo translate('seconds')?>
            <input type="checkbox" name="newMonitor[SectionLengthWarn}" value="1"<?php echo $monitor->SectionLengthWarn() ? ' checked="checked"' : '' ?>/>
            <?php echo translate('Warn if exceeded') ?>
        </li>
        <li>
          <label><?php echo translate('MinSectionlength') ?></label>
            <input type="number" name="newMonitor[MinSectionLength]" value="<?php echo validHtmlStr($monitor->MinSectionLength()) ?>" min="0"/>
            <?php echo translate('seconds')?>
        </li>
        <li class="EventCloseMode">
          <label><?php echo translate('Event Close Mode') ?></label>
          <?php echo html_radio('newMonitor[EventCloseMode]', ['system'=>translate('System'), 'time'=>translate('Time'), 'duration'=>translate('Duration'), 'idle'=>translate('Idle'), 'alarm'=>translate('Alarm')], $monitor->EventCloseMode()); ?>
          <span class="form-text form-control-sm">When continuous events are closed.&nbsp;(<a id="ZM_EVENT_CLOSE_MODE" class="optionhelp">?</a>)</span>
        </li>
        <li>
          <label><?php echo translate('FrameSkip') ?></label>
            <input type="number" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($monitor->FrameSkip()) ?>" min="0"/>
            <?php echo translate('frames')?>
        </li>
        <li>
          <label><?php echo translate('MotionFrameSkip') ?></label>
            <input type="number" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($monitor->MotionFrameSkip()) ?>" min="0"/>
            <?php echo translate('frames')?>
        </li>
        <li>
          <label><?php echo translate('AnalysisUpdateDelay') ?></label>
            <input type="number" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($monitor->AnalysisUpdateDelay()) ?>" min="0"/>
            <?php echo translate('seconds')?>
        </li>
        <li>
          <label><?php echo translate('FPSReportInterval') ?></label>
            <input type="number" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($monitor->FPSReportInterval()) ?>" min="0"/>
            <?php echo translate('frames')?>
        </li>
        <li>
          <label><?php echo translate('SignalCheckPoints') ?></label>
            <input type="number" name="newMonitor[SignalCheckPoints]" value="<?php echo validInt($monitor->SignalCheckPoints()) ?>" min="0"/>
        </li>
        <li>
          <label><?php echo translate('SignalCheckColour') ?></label>
            <input type="color" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($monitor->SignalCheckColour()) ?>"/>
            <span id="SignalCheckSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($monitor->SignalCheckColour()); ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        </li>
        <li>
          <label><?php echo translate('WebColour') ?></label>
            <input type="color" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($monitor->WebColour()) ?>"/>
            <span id="WebSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($monitor->WebColour()) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span>
            <i class="material-icons" data-on-click="random_WebColour">sync</i>

        </li>
        <li>
          <label class="Exif"><?php echo translate('Exif'); echo makeHelpLink('OPTIONS_EXIF') ?></label>
          <input type="checkbox" name="newMonitor[Exif]" value="1"<?php echo $monitor->Exif() ? ' checked="checked"' : '' ?>/>
        </li>
        <li>
          <label class="Importance"><?php echo translate('Importance'); echo makeHelpLink('OPTIONS_IMPORTANCE') ?></label>
<?php
      echo htmlselect('newMonitor[Importance]',
              array(
                'Normal'=>translate('Normal'),
                'Less'=>translate('Less important'),
                'Not'=>translate('Not important')
              ), $monitor->Importance());
?>
        </li>
        <li class="StartupDelay">
          <label><?php echo translate('Startup Delay'); ?></label>
          <input type="number" min="0" max="65536" step="1" name="newMonitor[StartupDelay]" value="<?php echo validCardinal($monitor->StartupDelay()) ?>"/><?php echo translate('seconds') ?>
        </li>
<?php
        break;
    }
  case 'location':
?>
        <li class="Latitude">
          <label class="Latitude"><?php echo translate('Latitude') ?></label>
          <input type="number" id="newMonitor[Latitude]" name="newMonitor[Latitude]" step="any" value="<?php echo $monitor->Latitude() ?>" min="-90" max="90" data-on-input-this="ll2dms" placeholder="degrees"/>
          <input type="text" id="LatitudeDMS" data-on-input-this="dms2ll" placeholder="Degrees Minutes Seconds" />
        </li>
        <li class="Longitude">
          <label class="Longitude"><?php echo translate('Longitude') ?></label>
          <input type="number" id="newMonitor[Longitude]" name="newMonitor[Longitude]" step="any" value="<?php echo $monitor->Longitude() ?>" min="-180" max="180" data-on-input-this="ll2dms" placeholder="degrees"/>
          <input type="text" id="LongitudeDMS" data-on-input-this="dms2ll" placeholder="Degrees Minutes Seconds"/>
        </li>
        <li class="DMS">
        </li>
        <li>
          <button type="button" data-on-click="getLocation"><?php echo translate('GetCurrentLocation') ?></button>
        </li>
        <li>
          <div id="LocationMap" style="height: 500px; width: 500px;"></div>
        </li>
<?php
    break;
  case 'mqtt':
?>
        <li>
          <label class="MQTT_Enabled"><?php echo translate('MQTT Enabled') ?></label>
          <?php echo html_radio('newMonitor[MQTT_Enabled]', array('1'=>translate('Enabled'), '0'=>translate('Disabled')), $monitor->MQTT_Enabled()) ?>
        </li>
        <li>
          <label class="MQTT_Subscriptions"><?php echo translate('MQTT Subscriptions') ?></label>
          <input type="text" name="newMonitor[MQTT_Subscriptions]" value="<?php echo $monitor->MQTT_Subscriptions() ?>" />
        </li>
<?php
    break;
  case 'zones':
    break;
  default :
    ZM\Error("Unknown tab \"$name\"");
} // end switch tab
?>
  </ul>
</div>
<?php 
} # end foreach tab
?>
</div><!--tab-content-->
        <div id="contentButtons" class="pr-3">
          <button type="button" id="saveBtn" name="action" value="save"<?php echo canEdit('Monitors', $mid) ? ($thisNewMonitor === true ? ' disabled="disabled"' : '') : ' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
          <button type="submit" name="action" value="save"<?php echo canEdit('Monitors', $mid) ? '' : ' disabled="disabled"' ?>><?php echo translate('SaveAndClose') ?></button>
          <button type="button" id="cancelBtn"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div><!--monitor-->
</div><!-- flex column container-->
    </div><!--content-->
  </div><!--page-->
  <div id="alertSaveMonitorData" class="fixed-t-r alert alert-info" role="alert" style="display: none;">
    <h2 class="alert-heading"><?php echo translate('PleaseWait') ?></h2>
    <?php echo translate('MonitorDataIsSaved') ?>
  </div>

  <script src="<?php echo cache_bust('js/MonitorLinkExpression.js') ?>"></script>
<script type="module" nonce="<?php echo $cspNonce ?>">
  import DmsCoordinates, {parseDms} from "./js/dms.js";
  window.DmsCoordinates = DmsCoordinates;
  window.parseDms = parseDms;
</script>
<?php
echo output_script_if_exists(array('js/leaflet/leaflet.js'), false);
echo output_link_if_exists(array('js/leaflet/leaflet.css'), false);
xhtmlFooter()
?>
