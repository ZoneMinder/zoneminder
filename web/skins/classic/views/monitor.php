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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

require_once( 'includes/Server.php');

if ( !canView( 'Monitors' ) )
{
    $view = "error";
    return;
}

$tabs = array();
$tabs["general"] = translate('General');
$tabs["source"] = translate('Source');
$tabs["timestamp"] = translate('Timestamp');
$tabs["buffers"] = translate('Buffers');
if ( ZM_OPT_CONTROL && canView( 'Control' ) )
    $tabs["control"] = translate('Control');
if ( ZM_OPT_X10 )
    $tabs["x10"] = translate('X10');
$tabs["misc"] = translate('Misc');

if ( isset($_REQUEST['tab']) )
    $tab = validHtmlStr($_REQUEST['tab']);
else
    $tab = "general";

  $Server = null;
    if ( defined( 'ZM_SERVER_ID' ) ) {
        $Server = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array( ZM_SERVER_ID ) );
  }
  if ( ! $Server ) {
        $Server = array( 'Id' => '' );
    }

if ( ! empty($_REQUEST['mid']) ) {
    $monitor = dbFetchMonitor( $_REQUEST['mid'] );
    if ( ZM_OPT_X10 )
        $x10Monitor = dbFetchOne( 'SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($_REQUEST['mid']) );
} else {

  $nextId = getTableAutoInc( 'Monitors' );
  $monitor = getMonitorObject($_REQUEST['dupId']);
  $clonedName = $monitor['Name'];
  $monitor['Name'] = translate('Monitor').'-'.$nextId;
  $monitor['Id']='0';
}

if ( ZM_OPT_X10 && empty($x10Monitor) )
{
    $x10Monitor = array(
        'Activation' => '',   
        'AlarmInput' => '',   
        'AlarmOutput' => '',   
    );
}

function getMonitorObject( $mid = null) 
{
    if ($mid !== null)
    {
      $monitor = dbFetchMonitor($mid);
    }
    else
    {
          $monitor = array(
                'Id' => 0,
                'Name' => "willbereplaced",
                'Function' => "Monitor",
                'Enabled' => true,
                'LinkedMonitors' => "",
                'Type' => "",
                'Device' => "/dev/video0",
                'Channel' => "0",
                'Format' => 0x000000ff,
                'Protocol' => "",
                'Method' => "",
                'Host' => "",
                'Path' => "",
                'Options' => "",
                'Port' => "80",
                'User' => "",
                'Pass' => "",
                'Colours' => 3,
                'Palette' => 0,
                'Width' => "320",
                'Height' => "240",
                'Orientation' => "0",
                'Deinterlacing' => 0,
                'RTSPDescribe' => 0,
                'LabelFormat' => '%N - %d/%m/%y %H:%M:%S',
                'LabelX' => 0,
                'LabelY' => 0,
                'LabelSize' => 1,
                'ImageBufferCount' => 50,
                'WarmupCount' => 25,
                'PreEventCount' => 25,
                'PostEventCount' => 25,
                'StreamReplayBuffer' => 1000,
                'AlarmFrameCount' => 1,
                'Controllable' => 0,
                'ControlId' => "",
                'ControlType' => 0,
                'ControlDevice' => "",
                'ControlAddress' => "",
                'AutoStopTimeout' => "",
                'TrackMotion' => 0,
                'TrackDelay' => "",
                'ReturnLocation' => -1,
                'ReturnDelay' => "",
                'SectionLength' => 600,
                'FrameSkip' => 0,
                'MotionFrameSkip' => 0,
                'EventPrefix' => 'Event-',
                'AnalysisFPS' => "",
                'AnalysisUpdateDelay' => 0,
                'MaxFPS' => "",
                'AlarmMaxFPS' => "",
                'FPSReportInterval' => 1000,
                'RefBlendPerc' => 6,
                'AlarmRefBlendPerc' => 6,
                'DefaultView' => 'Events',
                'DefaultRate' => '100',
                'DefaultScale' => '100',
                'SignalCheckColour' => '#0000c0',
                'WebColour' => 'red',
                'Exif' => '0',
                'Triggers' => "",
            'V4LMultiBuffer'  =>  '',
            'V4LCapturesPerFrame' =>  1,
            'ServerId'  =>  $Server['Id'],
            );
      }
      return ($monitor);
}

function fourcc( $a, $b, $c, $d )
{
    return( ord($a) | (ord($b) << 8) | (ord($c) << 16) | (ord($d) << 24) );
}

if ( isset( $_REQUEST['newMonitor'] ) )
{
    $newMonitor = $_REQUEST['newMonitor'];
    if ( ZM_OPT_X10 )
        $newX10Monitor = $_REQUEST['newX10Monitor'];
}
else
{
    $newMonitor = $monitor;
    $newMonitor['Triggers'] = explode( ',', isset($monitor['Triggers'])?$monitor['Triggers']:"" );
    if ( ZM_OPT_X10 )
        $newX10Monitor = $x10Monitor;
}

$newMonitor['Name'] = trim($newMonitor['Name']);

if ( $newMonitor['AnalysisFPS'] == '0.00' )
    $newMonitor['AnalysisFPS'] = '';
if ( $newMonitor['MaxFPS'] == '0.00' )
    $newMonitor['MaxFPS'] = '';
if ( $newMonitor['AlarmMaxFPS'] == '0.00' )
    $newMonitor['AlarmMaxFPS'] = '';

if ( !empty($_REQUEST['preset']) )
{
    $preset = dbFetchOne( 'SELECT Type, Device, Channel, Format, Protocol, Method, Host, Port, Path, Width, Height, Palette, MaxFPS, Controllable, ControlId, ControlDevice, ControlAddress, DefaultRate, DefaultScale FROM MonitorPresets WHERE Id = ?', NULL, array($_REQUEST['preset']) );
    foreach ( $preset as $name=>$value )
    {
        if ( isset($value) )
        {
            $newMonitor[$name] = $value;
        }
    }
}
if ( !empty($_REQUEST['probe']) )
{
    $probe = unserialize(base64_decode($_REQUEST['probe']));
    foreach ( $probe as $name=>$value )
    {
        if ( isset($value) )
        {
            $newMonitor[$name] = $value;
        }
    }
    if ( ZM_HAS_V4L && $newMonitor['Type'] == 'Local' )
    {
        $newMonitor['Palette'] = fourCC( substr($newMonitor['Palette'],0,1), substr($newMonitor['Palette'],1,1), substr($newMonitor['Palette'],2,1), substr($newMonitor['Palette'],3,1) );
        if ( $newMonitor['Format'] == 'PAL' )
            $newMonitor['Format'] = 0x000000ff;
        elseif ( $newMonitor['Format'] == 'NTSC' )
            $newMonitor['Format'] = 0x0000b000;
    }
}

$sourceTypes = array(
    'Local'  => translate('Local'),
    'Remote' => translate('Remote'),
    'File'   => translate('File'),
    'Ffmpeg' => translate('Ffmpeg'),
    'Libvlc' => translate('Libvlc'),
    'cURL'   => "cURL (HTTP(S) only)"
);
if ( !ZM_HAS_V4L )
    unset($sourceTypes['Local']);

$localMethods = array(
    'v4l2' => "Video For Linux version 2",
    'v4l1' => "Video For Linux version 1",
);
if ( !ZM_HAS_V4L2 )
    unset($localMethods['v4l2']);
if ( !ZM_HAS_V4L1 )
    unset($localMethods['v4l1']);

$remoteProtocols = array(
    "http" => "HTTP",
    "rtsp" => "RTSP"
);

$rtspMethods = array(
    "rtpUni"      => "RTP/Unicast",
    "rtpMulti"    => "RTP/Multicast",
    "rtpRtsp"     => "RTP/RTSP",
    "rtpRtspHttp" => "RTP/RTSP/HTTP"
);

$httpMethods = array(
    "simple"   => "Simple",
    "regexp"   => "Regexp",
    "jpegTags" => "JPEG Tags"
);
if ( !ZM_PCRE )
    unset($httpMethods['regexp']);
// Currently unsupported
unset($httpMethods['jpegTags']);

if ( ZM_HAS_V4L1 )
{
    $v4l1DeviceFormats = array(
        "PAL"   => 0,
        "NTSC"  => 1,
        "SECAM" => 2,
        "AUTO"  => 3,
        "FMT4"  => 4,
        "FMT5"  => 5,
        "FMT6"  => 6,
        "FMT7"  => 7
    );

    $v4l1MaxChannels = 15;
    $v4l1DeviceChannels = array();
    for ( $i = 0; $i <= $v4l1MaxChannels; $i++ )
        $v4l1DeviceChannels["$i"] = $i;

    $v4l1LocalPalettes = array(
        translate('Grey')      => 1,
        "BGR32"             => 5,
        "BGR24"             => 4,
        "*YUYV"              => 8,
        "*RGB565"            => 3,
        "*RGB555"            => 6,
        "*YUV422"            => 7,
        "*YUV422P"           => 13,
        "*YUV420P"           => 15
    );
}

if ( ZM_HAS_V4L2 )
{
    $v4l2DeviceFormats = array(
        "PAL"         => 0x000000ff,
        "NTSC"        => 0x0000b000,
        "PAL B"       => 0x00000001,
        "PAL B1"      => 0x00000002,
        "PAL G"       => 0x00000004,
        "PAL H"       => 0x00000008,
        "PAL I"       => 0x00000010,
        "PAL D"       => 0x00000020,
        "PAL D1"      => 0x00000040,
        "PAL K"       => 0x00000080,
        "PAL M"       => 0x00000100,
        "PAL N"       => 0x00000200,
        "PAL Nc"      => 0x00000400,
        "PAL 60"      => 0x00000800,
        "NTSC M"      => 0x00001000,
        "NTSC M JP"   => 0x00002000,
        "NTSC 443"    => 0x00004000,
        "NTSC M KR"   => 0x00008000,
        "SECAM B"     => 0x00010000,
        "SECAM D"     => 0x00020000,
        "SECAM G"     => 0x00040000,
        "SECAM H"     => 0x00080000,
        "SECAM K"     => 0x00100000,
        "SECAM K1"    => 0x00200000,
        "SECAM L"     => 0x00400000,
        "SECAM LC"    => 0x00800000,
        "ATSC 8 VSB"  => 0x01000000,
        "ATSC 16 VSB" => 0x02000000,
    );

    $v4l2MaxChannels = 31;
    $v4l2DeviceChannels = array();
    for ( $i = 0; $i <= $v4l2MaxChannels; $i++ )
        $v4l2DeviceChannels["$i"] = $i;

    $v4l2LocalPalettes = array(
        "Auto" => 0, /* Automatic palette selection */

        /*      Pixel format         FOURCC                        depth  Description  */
        translate('Grey') =>     fourcc('G','R','E','Y'), /*  8  Greyscale     */
        "BGR32" =>    fourcc('B','G','R','4'), /* 32  BGR-8-8-8-8   */
        "RGB32" =>    fourcc('R','G','B','4'), /* 32  RGB-8-8-8-8   */
        "BGR24" =>    fourcc('B','G','R','3'), /* 24  BGR-8-8-8     */
        "RGB24" =>    fourcc('R','G','B','3'), /* 24  RGB-8-8-8     */
        "*YUYV" =>     fourcc('Y','U','Y','V'), /* 16  YUV 4:2:2     */

        /* compressed formats */
        "*JPEG" =>     fourcc('J','P','E','G'), /* JFIF JPEG     */
        "*MJPEG" =>    fourcc('M','J','P','G'), /* Motion-JPEG   */
        //"DV" =>       fourcc('d','v','s','d'), /* 1394          */
        //"MPEG" =>     fourcc('M','P','E','G'), /* MPEG-1/2/4    */

        //"RGB332" =>   fourcc('R','G','B','1'), /*  8  RGB-3-3-2     */
        "*RGB444" =>   fourcc('R','4','4','4'), /* 16  xxxxrrrr ggggbbbb */
        "*RGB555" =>   fourcc('R','G','B','O'), /* 16  RGB-5-5-5     */
        "*RGB565" =>   fourcc('R','G','B','P'), /* 16  RGB-5-6-5     */
        //"RGB555X" =>  fourcc('R','G','B','Q'), /* 16  RGB-5-5-5 BE  */
        //"RGB565X" =>  fourcc('R','G','B','R'), /* 16  RGB-5-6-5 BE  */
        //"Y16" =>      fourcc('Y','1','6',''), /* 16  Greyscale     */
        //"PAL8" =>     fourcc('P','A','L','8'), /*  8  8-bit palette */
        //"YVU410" =>   fourcc('Y','V','U','9'), /*  9  YVU 4:1:0     */
        //"YVU420" =>   fourcc('Y','V','1','2'), /* 12  YVU 4:2:0     */

        "*UYVY" =>     fourcc('U','Y','V','Y'), /* 16  YUV 4:2:2     */
        "*YUV422P" =>  fourcc('4','2','2','P'), /* 16  YVU422 planar */
        "*YUV411P" =>  fourcc('4','1','1','P'), /* 16  YVU411 planar */
        //"Y41P" =>     fourcc('Y','4','1','P'), /* 12  YUV 4:1:1     */
        "*YUV444" =>   fourcc('Y','4','4','4'), /* 16  xxxxyyyy uuuuvvvv */
        //"YUV555" =>   fourcc('Y','U','V','O'), /* 16  YUV-5-5-5     */
        //"YUV565" =>   fourcc('Y','U','V','P'), /* 16  YUV-5-6-5     */
        //"YUV32" =>    fourcc('Y','U','V','4'), /* 32  YUV-8-8-8-8   */

        /* two planes -- one Y, one Cr + Cb interleaved  */
        //"NV12" =>     fourcc('N','V','1','2'), /* 12  Y/CbCr 4:2:0  */
        //"NV21" =>     fourcc('N','V','2','1'), /* 12  Y/CrCb 4:2:0  */

        /*  The following formats are not defined in the V4L2 specification */
        "*YUV410" =>   fourcc('Y','U','V','9'), /*  9  YUV 4:1:0     */
        "*YUV420" =>   fourcc('Y','U','1','2'), /* 12  YUV 4:2:0     */
        //"YYUV" =>     fourcc('Y','Y','U','V'), /* 16  YUV 4:2:2     */
        //"HI240" =>    fourcc('H','I','2','4'), /*  8  8-bit color   */
        //"HM12" =>     fourcc('H','M','1','2'), /*  8  YUV 4:2:0 16x16 macroblocks */

        /* see http://www.siliconimaging.com/RGB%20Bayer.htm */
        //"SBGGR8" =>   fourcc('B','A','8','1'), /*  8  BGBG.. GRGR.. */
        //"SGBRG8" =>   fourcc('G','B','R','G'), /*  8  GBGB.. RGRG.. */
        //"SBGGR16" =>  fourcc('B','Y','R','2'), /* 16  BGBG.. GRGR.. */

        /*  Vendor-specific formats   */
        //"WNVA" =>     fourcc('W','N','V','A'), /* Winnov hw compress */
        //"SN9C10X" =>  fourcc('S','9','1','0'), /* SN9C10x compression */
        //"PWC1" =>     fourcc('P','W','C','1'), /* pwc older webcam */
        //"PWC2" =>     fourcc('P','W','C','2'), /* pwc newer webcam */
        //"ET61X251" => fourcc('E','6','2','5'), /* ET61X251 compression */
        //"SPCA501" =>  fourcc('S','5','0','1'), /* YUYV per line */
        //"SPCA505" =>  fourcc('S','5','0','5'), /* YYUV per line */
        //"SPCA508" =>  fourcc('S','5','0','8'), /* YUVY per line */
        //"SPCA561" =>  fourcc('S','5','6','1'), /* compressed GBRG bayer */
        //"PAC207" =>   fourcc('P','2','0','7'), /* compressed BGGR bayer */
        //"PJPG" =>     fourcc('P','J','P','G'), /* Pixart 73xx JPEG */
        //"YVYU" =>     fourcc('Y','V','Y','U'), /* 16  YVU 4:2:2     */
    );
}

$Colours = array(
    translate('8BitGrey')    => 1,
    translate('24BitColour') => 3,
    translate('32BitColour') => 4
);

$orientations = array(
    translate('Normal')      => '0',
    translate('RotateRight') => '90',
    translate('Inverted')    => '180',
    translate('RotateLeft')  => '270',
    translate('FlippedHori') => 'hori',
    translate('FlippedVert') => 'vert'
);

$deinterlaceopts = array(
    "Disabled"                                            => 0x00000000,
    "Four field motion adaptive - Soft"                   => 0x00001E04, /* 30 change */
    "Four field motion adaptive - Medium"                 => 0x00001404, /* 20 change */
    "Four field motion adaptive - Hard"                   => 0x00000A04, /* 10 change */
    "Discard"                                             => 0x00000001,
    "Linear"                                              => 0x00000002,
    "Blend"                                               => 0x00000003,
    "Blend (25%)"                                         => 0x00000205
);

$deinterlaceopts_v4l2 = array(
    "Disabled"                                            => 0x00000000,
    "Four field motion adaptive - Soft"                   => 0x00001E04, /* 30 change */
    "Four field motion adaptive - Medium"                 => 0x00001404, /* 20 change */
    "Four field motion adaptive - Hard"                   => 0x00000A04, /* 10 change */
    "Discard"                                             => 0x00000001,
    "Linear"                                              => 0x00000002,
    "Blend"                                               => 0x00000003,
    "Blend (25%)"                                         => 0x00000205,
    "V4L2: Capture top field only"                        => 0x02000000,
    "V4L2: Capture bottom field only"                     => 0x03000000,
    "V4L2: Alternate fields (Bob)"                        => 0x07000000,
    "V4L2: Progressive"                                   => 0x01000000,
    "V4L2: Interlaced"                                    => 0x04000000
);

$fastblendopts = array(
    "No blending"                                         => 0,
    "1.5625%"                                             => 1,
    "3.125%"                                              => 3,
    "6.25% (Indoor)"                                      => 6,
    "12.5% (Outdoor)"                                     => 12,
    "25%"                                                 => 25,
    "50%"                                                 => 50
);

$fastblendopts_alarm = array(
    "No blending (Alarm lasts forever)"                   => 0,
    "1.5625%"                                             => 1,
    "3.125%"                                              => 3,
    "6.25%"                                               => 6,
    "12.5%"                                               => 12,
    "25%"                                                 => 25,
    "50% (Alarm lasts a moment)"                          => 50
);

$label_size = array(
    "Default"                                             => 1,
    "Large"                                               => 2
);

xhtmlHeaders(__FILE__, translate('Monitor')." - ".validHtmlStr($monitor['Name']) );
?>
<body>
  <div id="page">
    <div id="header">
<?php
if ( canEdit( 'Monitors' ) )
{
?>

<?php
if ( isset ($_REQUEST['dupId']))
{
  $dupId = $_REQUEST['dupId'];
?>

<div class="alert alert-info">
  Configuration cloned from Monitor: <?php echo $clonedName ?>
</div>

<?php 
}
?>

      <div id="headerButtons">
        <a href="#" onclick="createPopup( '?view=monitorprobe&amp;mid=<?php echo $monitor['Id'] ?>', 'zmMonitorProbe<?php echo $monitor['Id'] ?>', 'monitorprobe' ); return( false );"><?php echo translate('Probe') ?></a>
  <?php
  if ( ZM_HAS_ONVIF )
  {
  ?>
          <a href="#" onclick="createPopup( '?view=onvifprobe&amp;mid=<?php echo $monitor['Id'] ?>', 'zmOnvifProbe<?php echo $monitor['Id'] ?>', 'onvifprobe' ); return( false );"><?php echo  translate('OnvifProbe') ?></a>
  <?php
  }
  ?>
        <a href="#" onclick="createPopup( '?view=monitorpreset&amp;mid=<?php echo $monitor['Id'] ?>', 'zmMonitorPreset<?php echo $monitor['Id'] ?>', 'monitorpreset' ); return( false );"><?php echo translate('Presets') ?></a>
      </div>
<?php
}
?>
      <h2><?php echo translate('Monitor') ?> - <?php echo validHtmlStr($monitor['Name']) ?><?php if ( !empty($monitor['Id']) ) { ?> (<?php echo $monitor['Id'] ?>)<?php } ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?php echo $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="#" onclick="submitTab( '<?php echo $name ?>' ); return( false );"><?php echo $value ?></a></li>
<?php
    }
}
?>
      </ul>
      <div class="clear"></div>
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm( this )">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="monitor"/>
        <input type="hidden" name="mid" value="<?php echo $monitor['Id'] ?>"/>
        <input type="hidden" name="newMonitor[LinkedMonitors]" value="<?php echo isset($newMonitor['LinkedMonitors'])?$newMonitor['LinkedMonitors']:'' ?>"/>
        <input type="hidden" name="origMethod" value="<?php echo isset($newMonitor['Method'])?$newMonitor['Method']:'' ?>"/>
<?php
if ( $tab != 'general' )
{
?>
        <input type="hidden" name="newMonitor[Name]" value="<?php echo validHtmlStr($newMonitor['Name']) ?>"/>
        <input type="hidden" name="newMonitor[ServerId]" value="<?php echo validHtmlStr($newMonitor['ServerId']) ?>"/>
        <input type="hidden" name="newMonitor[Type]" value="<?php echo validHtmlStr($newMonitor['Type']) ?>"/>
        <input type="hidden" name="newMonitor[Function]" value="<?php echo validHtmlStr($newMonitor['Function']) ?>"/>
        <input type="hidden" name="newMonitor[Enabled]" value="<?php echo validHtmlStr($newMonitor['Enabled']) ?>"/>
        <input type="hidden" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['RefBlendPerc']) ?>"/>
        <input type="hidden" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>"/>
        <input type="hidden" name="newMonitor[AnalysisFPS]" value="<?php echo validHtmlStr($newMonitor['AnalysisFPS']) ?>"/>
        <input type="hidden" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>"/>
        <input type="hidden" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>"/>
<?php
    if ( isset($newMonitor['Triggers']) )
    {
        foreach( $newMonitor['Triggers'] as $newTrigger )
        {
?>
        <input type="hidden" name="newMonitor[Triggers][]" value="<?php echo validHtmlStr($newTrigger) ?>"/>
<?php
        }
    }
}
if ( ZM_HAS_V4L && ($tab != 'source' || $newMonitor['Type'] != 'Local') )
{
?>
    <input type="hidden" name="newMonitor[Device]" value="<?php echo validHtmlStr($newMonitor['Device']) ?>"/>
    <input type="hidden" name="newMonitor[Channel]" value="<?php echo validHtmlStr($newMonitor['Channel']) ?>"/>
    <input type="hidden" name="newMonitor[Format]" value="<?php echo validHtmlStr($newMonitor['Format']) ?>"/>
    <input type="hidden" name="newMonitor[Palette]" value="<?php echo validHtmlStr($newMonitor['Palette']) ?>"/>
    <input type="hidden" name="newMonitor[V4LMultiBuffer]" value="<?php echo validHtmlStr($newMonitor['V4LMultiBuffer']) ?>"/>
    <input type="hidden" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo validHtmlStr($newMonitor['V4LCapturesPerFrame']) ?>"/>
<?php
}
if ( $tab != 'source' || $newMonitor['Type'] != 'Remote' )
{
?>
    <input type="hidden" name="newMonitor[Protocol]" value="<?php echo validHtmlStr($newMonitor['Protocol']) ?>"/>
    <input type="hidden" name="newMonitor[Host]" value="<?php echo validHtmlStr($newMonitor['Host']) ?>"/>
    <input type="hidden" name="newMonitor[Port]" value="<?php echo validHtmlStr($newMonitor['Port']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Local' && $newMonitor['Type'] != 'Remote' && $newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc') )
{
?>
    <input type="hidden" name="newMonitor[Method]" value="<?php echo validHtmlStr($newMonitor['Method']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc' ))
{
?>
    <input type="hidden" name="newMonitor[Options]" value="<?php echo validHtmlStr($newMonitor['Options']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Remote' && $newMonitor['Type'] != 'File' && $newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc' && $newMonitor['Type'] != 'cURL') )
{
?>
    <input type="hidden" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>"/>
    <input type="hidden" name="newMonitor[User]" value="<?php echo validHtmlStr($newMonitor['User']) ?>"/>
    <input type="hidden" name="newMonitor[Pass]" value="<?php echo validHtmlStr($newMonitor['Pass']) ?>"/>
<?php
}
if ( $tab != 'source' )
{
?>
    <input type="hidden" name="newMonitor[Colours]" value="<?php echo validHtmlStr($newMonitor['Colours']) ?>"/>
    <input type="hidden" name="newMonitor[Width]" value="<?php echo validHtmlStr($newMonitor['Width']) ?>"/>
    <input type="hidden" name="newMonitor[Height]" value="<?php echo validHtmlStr($newMonitor['Height']) ?>"/>
    <input type="hidden" name="newMonitor[Orientation]" value="<?php echo validHtmlStr($newMonitor['Orientation']) ?>"/>
    <input type="hidden" name="newMonitor[Deinterlacing]" value="<?php echo validHtmlStr($newMonitor['Deinterlacing']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Remote' && $newMonitor['Protocol'] != 'RTSP'))
{
?>
    <input type="hidden" name="newMonitor[RTSPDescribe]" value="<?php echo validHtmlStr($newMonitor['RTSPDescribe']) ?>"/>
<?php
}
if ( $tab != 'timestamp' )
{
?>
    <input type="hidden" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($newMonitor['LabelFormat']) ?>"/>
    <input type="hidden" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($newMonitor['LabelX']) ?>"/>
    <input type="hidden" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($newMonitor['LabelY']) ?>"/>
    <input type="hidden" name="newMonitor[LabelSize]" value="<?php echo validHtmlStr($newMonitor['LabelSize']) ?>"/>
<?php
}
if ( $tab != 'buffers' )
{
?>
    <input type="hidden" name="newMonitor[ImageBufferCount]" value="<?php echo validHtmlStr($newMonitor['ImageBufferCount']) ?>"/>
    <input type="hidden" name="newMonitor[WarmupCount]" value="<?php echo validHtmlStr($newMonitor['WarmupCount']) ?>"/>
    <input type="hidden" name="newMonitor[PreEventCount]" value="<?php echo validHtmlStr($newMonitor['PreEventCount']) ?>"/>
    <input type="hidden" name="newMonitor[PostEventCount]" value="<?php echo validHtmlStr($newMonitor['PostEventCount']) ?>"/>
    <input type="hidden" name="newMonitor[StreamReplayBuffer]" value="<?php echo validHtmlStr($newMonitor['StreamReplayBuffer']) ?>"/>
    <input type="hidden" name="newMonitor[AlarmFrameCount]" value="<?php echo validHtmlStr($newMonitor['AlarmFrameCount']) ?>"/>
<?php
}
if ( ZM_OPT_CONTROL && $tab != 'control' )
{
?>
    <input type="hidden" name="newMonitor[Controllable]" value="<?php echo validHtmlStr($newMonitor['Controllable']) ?>"/>
    <input type="hidden" name="newMonitor[ControlId]" value="<?php echo validHtmlStr($newMonitor['ControlId']) ?>"/>
    <input type="hidden" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($newMonitor['ControlDevice']) ?>"/>
    <input type="hidden" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($newMonitor['ControlAddress']) ?>"/>
    <input type="hidden" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($newMonitor['AutoStopTimeout']) ?>"/>
    <input type="hidden" name="newMonitor[TrackMotion]" value="<?php echo validHtmlStr($newMonitor['TrackMotion']) ?>"/>
    <input type="hidden" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($newMonitor['TrackDelay']) ?>"/>
    <input type="hidden" name="newMonitor[ReturnLocation]" value="<?php echo validHtmlStr($newMonitor['ReturnLocation']) ?>"/>
    <input type="hidden" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($newMonitor['ReturnDelay']) ?>"/>
<?php
}
if ( ZM_OPT_X10 && $tab != 'x10' )
{
?>
    <input type="hidden" name="newX10Monitor[Activation]" value="<?php echo validHtmlStr($newX10Monitor['Activation']) ?>"/>
    <input type="hidden" name="newX10Monitor[AlarmInput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmInput']) ?>"/>
    <input type="hidden" name="newX10Monitor[AlarmOutput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmOutput']) ?>"/>
<?php
}
if ( $tab != 'misc' )
{
?>
    <input type="hidden" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($newMonitor['EventPrefix']) ?>"/>
    <input type="hidden" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($newMonitor['SectionLength']) ?>"/>
    <input type="hidden" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($newMonitor['FrameSkip']) ?>"/>
    <input type="hidden" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($newMonitor['MotionFrameSkip']) ?>"/>
    <input type="hidden" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($newMonitor['AnalysisUpdateDelay']) ?>"/>
    <input type="hidden" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($newMonitor['FPSReportInterval']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultView]" value="<?php echo validHtmlStr($newMonitor['DefaultView']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultRate]" value="<?php echo validHtmlStr($newMonitor['DefaultRate']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultScale]" value="<?php echo validHtmlStr($newMonitor['DefaultScale']) ?>"/>
    <input type="hidden" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($newMonitor['WebColour']) ?>"/>
    <input type="hidden" name="newMonitor[Exif]" value="<?php echo validHtmlStr($newMonitor['Exif']) ?>"/>
<?php
}
if ( ZM_HAS_V4L && ($tab != 'misc' || $newMonitor['Type'] != 'Local') )
{
?>
    <input type="hidden" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($newMonitor['SignalCheckColour']) ?>"/>
<?php
}
?>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
<?php
switch ( $tab )
{
    case 'general' :
    {
?>
            <tr><td><?php echo translate('Name') ?></td><td><input type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($newMonitor['Name']) ?>" size="16"/></td></tr>
            <tr><td><?php echo translate('Server') ?></td><td>
<?php 
  $servers = array(''=>'None');
  $result = dbQuery( 'SELECT * FROM Servers ORDER BY Name');
  $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server' );
  foreach ( $results as $row => $server_obj ) {
    $servers[$server_obj->Id] = $server_obj->Name();
  }
?>
  <?php echo buildSelect( "newMonitor[ServerId]", $servers ); ?>
</td></tr>
            <tr><td><?php echo translate('SourceType') ?></td><td><?php echo buildSelect( "newMonitor[Type]", $sourceTypes ); ?></td></tr>
            <tr><td><?php echo translate('Function') ?></td><td><select name="newMonitor[Function]">
<?php
        foreach ( getEnumValues( 'Monitors', 'Function' ) as $optFunction )
        {
?>
              <option value="<?php echo $optFunction ?>"<?php if ( $optFunction == $newMonitor['Function'] ) { ?> selected="selected"<?php } ?>><?php echo $optFunction ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?php echo translate('Enabled') ?></td><td><input type="checkbox" name="newMonitor[Enabled]" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr>
              <td><?php echo translate('LinkedMonitors') ?></td>
              <td>
                <select name="monitorIds" size="4" multiple="multiple" onchange="updateLinkedMonitors( this )">
<?php
    $monitors = dbFetchAll( "select Id,Name from Monitors order by Sequence asc" );
    if ( !empty($newMonitor['LinkedMonitors']) )
        $monitorIds = array_flip( explode( ',', $newMonitor['LinkedMonitors'] ) );
    else
        $monitorIds = array();
    foreach ( $monitors as $monitor )
    {
        if ( (empty($newMonitor['Id']) || ($monitor['Id'] != $newMonitor['Id'])) && visibleMonitor( $monitor['Id'] ) )
        {
?>
                  <option value="<?php echo $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($monitor['Name']) ?></option>
<?php
        }
    }
?>
                </select>
              </td>
            </tr>
            <tr><td><?php echo translate('AnalysisFPS') ?></td><td><input type="text" name="newMonitor[AnalysisFPS]" value="<?php echo validHtmlStr($newMonitor['AnalysisFPS']) ?>" size="6"/></td></tr>
<?php
    if ( $newMonitor['Type'] != "Local" && $newMonitor['Type'] != "File" )
    {
?>
            <tr><td><?php echo translate('MaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" onclick="document.getElementById('newMonitor[MaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/><span id="newMonitor[MaxFPS]" style="color:red"></span></td></tr>
            <tr><td><?php echo translate('AlarmMaximumFPS') ?>&nbsp;(<?php echo makePopupLink('?view=optionhelp&amp;option=OPTIONS_MAXFPS', 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" onclick="document.getElementById('newMonitor[AlarmMaxFPS]').innerHTML= ' CAUTION: See the help text'" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/><span id="newMonitor[AlarmMaxFPS]" style="color:red"></span></td></tr>
<?php
    } else {
?>
            <tr><td><?php echo translate('MaximumFPS') ?></td><td><input type="text" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($newMonitor['MaxFPS']) ?>" size="5"/></td></tr>
            <tr><td><?php echo translate('AlarmMaximumFPS') ?></td><td><input type="text" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="5"/></td></tr>
<?php
    }
  if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
            <tr><td><?php echo translate('RefImageBlendPct') ?></td><td><select name="newMonitor[RefBlendPerc]"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('AlmRefImageBlendPct') ?></td><td><select name="newMonitor[AlarmRefBlendPerc]"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
  } else {
?>
            <tr><td><?php echo translate('RefImageBlendPct') ?></td><td><input type="text" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['RefBlendPerc']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('AlarmRefImageBlendPct') ?></td><td><input type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>" size="4"/></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('Triggers') ?></td><td>
<?php
        $optTriggers = getSetValues( 'Monitors', 'Triggers' );
        $breakCount = (int)(ceil(count($optTriggers)));
        $breakCount = min( 3, $breakCount );
        $optCount = 0;
        foreach( $optTriggers as $optTrigger )
        {
            if ( !ZM_OPT_X10 && $optTrigger == 'X10' )
                continue;
            if ( $optCount && ($optCount%$breakCount == 0) )
                echo "</br>";
?>
              <input type="checkbox" name="newMonitor[Triggers][]" value="<?php echo $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?php echo $optTrigger ?>
<?php
            $optCount ++;
        }
        if ( !$optCount )
        {
?>
              <em><?php echo translate('NoneAvailable') ?></em>
<?php
        }
?>
            </td></tr>
<?php
        break;
    }
    case 'source' :
    {
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('DevicePath') ?></td><td><input type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($newMonitor['Device']) ?>" size="24"/></td></tr>
            <tr><td><?php echo translate('CaptureMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )" ); ?></td></tr>
<?php
            if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' )
            {
?>
            <tr><td><?php echo translate('DeviceChannel') ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('DeviceFormat') ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CapturePalette') ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?php echo translate('DeviceChannel') ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('DeviceFormat') ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CapturePalette') ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
            }
?>
      <tr><td><?php echo translate('V4LMultiBuffer') ?></td><td>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]1">Yes</label>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]0">No</label>
        <input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
        <label for="newMonitor[V4LMultiBuffer]">Use Config Value</label>
      </td></tr>
      <tr><td><?php echo translate('V4LCapturesPerFrame') ?></td><td><input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo $newMonitor['V4LCapturesPerFrame'] ?>"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr><td><?php echo translate('RemoteProtocol') ?></td><td><?php echo buildSelect( "newMonitor[Protocol]", $remoteProtocols, "updateMethods( this )" ); ?></td></tr>
<?php
            if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" )
            {
?>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $httpMethods ); ?></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
<?php
            }
?>
            <tr><td><?php echo translate('RemoteHostName') ?></td><td><input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($newMonitor['Host']) ?>" size="36"/></td></tr>
            <tr><td><?php echo translate('RemoteHostPort') ?></td><td><input type="text" name="newMonitor[Port]" value="<?php echo validHtmlStr($newMonitor['Port']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('RemoteHostPath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "File" )
        {
?>
            <tr><td><?php echo translate('SourcePath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "cURL" )
        {
?>
            <tr><td><?php echo "URL" ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?php echo "Username" ?></td><td><input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($newMonitor['User']) ?>" size="12"/></td></tr>
            <tr><td><?php echo "Password" ?></td><td><input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($newMonitor['Pass']) ?>" size="12"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Ffmpeg" || $newMonitor['Type'] == "Libvlc")
        {
?>
      <tr><td><?php echo translate('SourcePath') ?></td><td><input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?php echo translate('RemoteMethod') ?></td><td><?php echo buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
      <tr><td><?php echo translate('Options') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($newMonitor['Options']) ?>" size="36"/></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('TargetColorspace') ?></td><td><select name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
            <tr><td><?php echo translate('CaptureWidth') ?> (<?php echo translate('Pixels') ?>)</td><td><input type="text" name="newMonitor[Width]" value="<?php echo validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?php echo translate('CaptureHeight') ?> (<?php echo translate('Pixels') ?>)</td><td><input type="text" name="newMonitor[Height]" value="<?php echo validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?php echo translate('PreserveAspect') ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
            <tr><td><?php echo translate('Orientation') ?></td><td><select name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        if ( $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('Deinterlacing') ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts_v4l2 as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        } else {
?>
            <tr><td><?php echo translate('Deinterlacing') ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        }
?>
<?php
        if ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr><td><?php echo translate('RTSPDescribe') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_RTSPDESCRIBE', 'zmOptionHelp', 'optionhelp', '?' ) ?>) </td><td><input type="checkbox" name="newMonitor[RTSPDescribe]" value="1"<?php if ( !empty($newMonitor['RTSPDescribe']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        }
?>
<?php
        break;
    }
    case 'timestamp' :
    {
?>
            <tr><td><?php echo translate('TimestampLabelFormat') ?></td><td><input type="text" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($newMonitor['LabelFormat']) ?>" size="32"/></td></tr>
            <tr><td><?php echo translate('TimestampLabelX') ?></td><td><input type="text" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($newMonitor['LabelX']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('TimestampLabelY') ?></td><td><input type="text" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($newMonitor['LabelY']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('TimestampLabelSize') ?></td><td><select name="newMonitor[LabelSize]"><?php foreach ( $label_size as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['LabelSize'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></td></tr>
<?php
        break;
    }
    case 'buffers' :
    {
?>
            <tr><td><?php echo translate('ImageBufferSize') ?></td><td><input type="text" name="newMonitor[ImageBufferCount]" value="<?php echo validHtmlStr($newMonitor['ImageBufferCount']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('WarmupFrames') ?></td><td><input type="text" name="newMonitor[WarmupCount]" value="<?php echo validHtmlStr($newMonitor['WarmupCount']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('PreEventImageBuffer') ?></td><td><input type="text" name="newMonitor[PreEventCount]" value="<?php echo validHtmlStr($newMonitor['PreEventCount']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('PostEventImageBuffer') ?></td><td><input type="text" name="newMonitor[PostEventCount]" value="<?php echo validHtmlStr($newMonitor['PostEventCount']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('StreamReplayBuffer') ?></td><td><input type="text" name="newMonitor[StreamReplayBuffer]" value="<?php echo validHtmlStr($newMonitor['StreamReplayBuffer']) ?>" size="6"/></td></tr>
<tr><td><?php echo translate('AlarmFrameCount') ?></td><td><input type="text" name="newMonitor[AlarmFrameCount]" value="<?php echo validHtmlStr($newMonitor['AlarmFrameCount']) ?>" size="4"/></td></tr>
<?php
        break;
    }
    case 'control' :
    {
?>
            <tr><td><?php echo translate('Controllable') ?></td><td><input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( !empty($newMonitor['Controllable']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><td><?php echo translate('ControlType') ?></td><td><?php echo buildSelect( "newMonitor[ControlId]", $controlTypes, 'loadLocations( this )' ); ?><?php if ( canEdit( 'Control' ) ) { ?>&nbsp;<a href="#" onclick="createPopup( '?view=controlcaps', 'zmControlCaps', 'controlcaps' );"><?php echo translate('Edit') ?></a><?php } ?></td></tr>
            <tr><td><?php echo translate('ControlDevice') ?></td><td><input type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($newMonitor['ControlDevice']) ?>" size="32"/></td></tr>
            <tr><td><?php echo translate('ControlAddress') ?></td><td><input type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($newMonitor['ControlAddress']) ?>" size="32"/></td></tr>
            <tr><td><?php echo translate('AutoStopTimeout') ?></td><td><input type="text" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($newMonitor['AutoStopTimeout']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('TrackMotion') ?></td><td><input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( !empty($newMonitor['TrackMotion']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        $return_options = array(
            '-1' => translate('None'),
            '0' => translate('Home'),
            '1' => translate('Preset')." 1",
        );
?>
            <tr><td><?php echo translate('TrackDelay') ?></td><td><input type="text" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($newMonitor['TrackDelay']) ?>" size="4"/></td></tr>
            <tr><td><?php echo translate('ReturnLocation') ?></td><td><?php echo buildSelect( "newMonitor[ReturnLocation]", $return_options ); ?></td></tr>
            <tr><td><?php echo translate('ReturnDelay') ?></td><td><input type="text" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($newMonitor['ReturnDelay']) ?>" size="4"/></td></tr>
<?php
        break;
    }
    case 'x10' :
    {
?>
            <tr><td><?php echo translate('X10ActivationString') ?></td><td><input type="text" name="newX10Monitor[Activation]" value="<?php echo validHtmlStr($newX10Monitor['Activation']) ?>" size="20"/></td></tr>
            <tr><td><?php echo translate('X10InputAlarmString') ?></td><td><input type="text" name="newX10Monitor[AlarmInput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmInput']) ?>" size="20"/></td></tr>
            <tr><td><?php echo translate('X10OutputAlarmString') ?></td><td><input type="text" name="newX10Monitor[AlarmOutput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmOutput']) ?>" size="20"/></td></tr>
<?php
        break;
    }
    case 'misc' :
    {
?>
            <tr><td><?php echo translate('EventPrefix') ?></td><td><input type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($newMonitor['EventPrefix']) ?>" size="24"/></td></tr>
            <tr><td><?php echo translate('Sectionlength') ?></td><td><input type="text" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($newMonitor['SectionLength']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('FrameSkip') ?></td><td><input type="text" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($newMonitor['FrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('MotionFrameSkip') ?></td><td><input type="text" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($newMonitor['MotionFrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('AnalysisUpdateDelay') ?></td><td><input type="text" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($newMonitor['AnalysisUpdateDelay']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('FPSReportInterval') ?></td><td><input type="text" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($newMonitor['FPSReportInterval']) ?>" size="6"/></td></tr>
            <tr><td><?php echo translate('DefaultView') ?></td><td><select name="newMonitor[DefaultView]">
<?php
        foreach ( getEnumValues( 'Monitors', 'DefaultView' ) as $opt_view )
        {
          if ( $opt_view == 'Control' && ( !ZM_OPT_CONTROL || !$monitor['Controllable'] ) )
            continue;
?>
              <option value="<?php echo $opt_view ?>"<?php if ( $opt_view == $newMonitor['DefaultView'] ) { ?> selected="selected"<?php } ?>><?php echo $opt_view ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?php echo translate('DefaultRate') ?></td><td><?php echo buildSelect( "newMonitor[DefaultRate]", $rates ); ?></td></tr>
            <tr><td><?php echo translate('DefaultScale') ?></td><td><?php echo buildSelect( "newMonitor[DefaultScale]", $scales ); ?></td></tr>
<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?php echo translate('SignalCheckColour') ?></td><td><input type="text" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($newMonitor['SignalCheckColour']) ?>" size="10" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?php echo $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
<?php
        }
?>
            <tr><td><?php echo translate('WebColour') ?></td><td><input type="text" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($newMonitor['WebColour']) ?>" size="10" onchange="$('WebSwatch').setStyle( 'backgroundColor', this.value )"/><span id="WebSwatch" class="swatch" style="background-color: <?php echo validHtmlStr($newMonitor['WebColour']) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
            <tr><td><?php echo translate('Exif') ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option=OPTIONS_EXIF', 'zmOptionHelp', 'optionhelp', '?' ) ?>) </td><td><input type="checkbox" name="newMonitor[Exif]" value="1"<?php if ( !empty($newMonitor['Exif']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Save') ?>"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
        
      </form>
    </div>
    </div>
</body>
</html>
