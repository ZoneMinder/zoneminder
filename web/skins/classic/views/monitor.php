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

if ( !canView( 'Monitors' ) )
{
    $view = "error";
    return;
}

$tabs = array();
$tabs["general"] = $SLANG['General'];
$tabs["source"] = $SLANG['Source'];
$tabs["timestamp"] = $SLANG['Timestamp'];
$tabs["buffers"] = $SLANG['Buffers'];
if ( ZM_OPT_CONTROL && canView( 'Control' ) )
    $tabs["control"] = $SLANG['Control'];
if ( ZM_OPT_X10 )
    $tabs["x10"] = $SLANG['X10'];
$tabs["misc"] = $SLANG['Misc'];

if ( isset($_REQUEST['tab']) )
    $tab = validHtmlStr($_REQUEST['tab']);
else
    $tab = "general";

if ( ! empty($_REQUEST['mid']) ) {
    $monitor = dbFetchMonitor( $_REQUEST['mid'] );
    if ( ZM_OPT_X10 )
        $x10Monitor = dbFetchOne( 'SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($_REQUEST['mid']) );
} else {
    $nextId = getTableAutoInc( 'Monitors' );
    $monitor = array(
        'Id' => 0,
        'Name' => $SLANG['Monitor'].'-'.$nextId,
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
        'LabelFormat' => '%N - %d/%m/%y %H:%M:%S',
        'LabelX' => 0,
        'LabelY' => 0,
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
        'Triggers' => "",
		'V4LMultiBuffer'	=>	'',
		'V4LCapturesPerFrame'	=>	1,
    );
}

if ( ZM_OPT_X10 && empty($x10Monitor) )
{
    $x10Monitor = array(
        'Activation' => '',   
        'AlarmInput' => '',   
        'AlarmOutput' => '',   
    );
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
    'Local'  => $SLANG['Local'],
    'Remote' => $SLANG['Remote'],
    'File'   => $SLANG['File'],
    'Ffmpeg' => $SLANG['Ffmpeg'],
    'Libvlc' => $SLANG['Libvlc'],
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
        $SLANG['Grey']      => 1,
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
        $SLANG['Grey'] =>     fourcc('G','R','E','Y'), /*  8  Greyscale     */
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
    $SLANG['8BitGrey']    => 1,
    $SLANG['24BitColour'] => 3,
    $SLANG['32BitColour'] => 4
);

$orientations = array(
    $SLANG['Normal']      => '0',
    $SLANG['RotateRight'] => '90',
    $SLANG['Inverted']    => '180',
    $SLANG['RotateLeft']  => '270',
    $SLANG['FlippedHori'] => 'hori',
    $SLANG['FlippedVert'] => 'vert'
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

xhtmlHeaders(__FILE__, $SLANG['Monitor']." - ".validHtmlStr($monitor['Name']) );
?>
<body>
  <div id="page">
    <div id="header">
<?php
if ( canEdit( 'Monitors' ) )
{
?>
      <div id="headerButtons">
        <a href="#" onclick="createPopup( '?view=monitorprobe&amp;mid=<?= $monitor['Id'] ?>', 'zmMonitorProbe<?= $monitor['Id'] ?>', 'monitorprobe' ); return( false );"><?= $SLANG['Probe'] ?></a>
        <a href="#" onclick="createPopup( '?view=monitorpreset&amp;mid=<?= $monitor['Id'] ?>', 'zmMonitorPreset<?= $monitor['Id'] ?>', 'monitorpreset' ); return( false );"><?= $SLANG['Presets'] ?></a>
      </div>
<?php
}
?>
      <h2><?= $SLANG['Monitor'] ?> - <?= validHtmlStr($monitor['Name']) ?><?php if ( !empty($monitor['Id']) ) { ?> (<?= $monitor['Id'] ?>)<?php } ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?= $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="#" onclick="submitTab( '<?= $name ?>' ); return( false );"><?= $value ?></a></li>
<?php
    }
}
?>
      </ul>
      <div class="clear"></div>
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm( this )">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="tab" value="<?= $tab ?>"/>
        <input type="hidden" name="action" value="monitor"/>
        <input type="hidden" name="mid" value="<?= $monitor['Id'] ?>"/>
        <input type="hidden" name="newMonitor[LinkedMonitors]" value="<?= isset($newMonitor['LinkedMonitors'])?$newMonitor['LinkedMonitors']:'' ?>"/>
        <input type="hidden" name="origMethod" value="<?= isset($newMonitor['Method'])?$newMonitor['Method']:'' ?>"/>
<?php
if ( $tab != 'general' )
{
?>
        <input type="hidden" name="newMonitor[Name]" value="<?= validHtmlStr($newMonitor['Name']) ?>"/>
        <input type="hidden" name="newMonitor[Type]" value="<?= validHtmlStr($newMonitor['Type']) ?>"/>
        <input type="hidden" name="newMonitor[Function]" value="<?= validHtmlStr($newMonitor['Function']) ?>"/>
        <input type="hidden" name="newMonitor[Enabled]" value="<?= validHtmlStr($newMonitor['Enabled']) ?>"/>
        <input type="hidden" name="newMonitor[RefBlendPerc]" value="<?= validHtmlStr($newMonitor['RefBlendPerc']) ?>"/>
        <input type="hidden" name="newMonitor[AlarmRefBlendPerc]" value="<?= validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>"/>
        <input type="hidden" name="newMonitor[MaxFPS]" value="<?= validHtmlStr($newMonitor['MaxFPS']) ?>"/>
        <input type="hidden" name="newMonitor[AlarmMaxFPS]" value="<?= validHtmlStr($newMonitor['AlarmMaxFPS']) ?>"/>
<?php
    if ( isset($newMonitor['Triggers']) )
    {
        foreach( $newMonitor['Triggers'] as $newTrigger )
        {
?>
        <input type="hidden" name="newMonitor[Triggers][]" value="<?= validHtmlStr($newTrigger) ?>"/>
<?php
        }
    }
}
if ( ZM_HAS_V4L && ($tab != 'source' || $newMonitor['Type'] != 'Local') )
{
?>
    <input type="hidden" name="newMonitor[Device]" value="<?= validHtmlStr($newMonitor['Device']) ?>"/>
    <input type="hidden" name="newMonitor[Channel]" value="<?= validHtmlStr($newMonitor['Channel']) ?>"/>
    <input type="hidden" name="newMonitor[Format]" value="<?= validHtmlStr($newMonitor['Format']) ?>"/>
    <input type="hidden" name="newMonitor[Palette]" value="<?= validHtmlStr($newMonitor['Palette']) ?>"/>
    <input type="hidden" name="newMonitor[V4LMultiBuffer]" value="<?= validHtmlStr($newMonitor['V4LMultiBuffer']) ?>"/>
    <input type="hidden" name="newMonitor[V4LCapturesPerFrame]" value="<?= validHtmlStr($newMonitor['V4LCapturesPerFrame']) ?>"/>
<?php
}
if ( $tab != 'source' || $newMonitor['Type'] != 'Remote' )
{
?>
    <input type="hidden" name="newMonitor[Protocol]" value="<?= validHtmlStr($newMonitor['Protocol']) ?>"/>
    <input type="hidden" name="newMonitor[Host]" value="<?= validHtmlStr($newMonitor['Host']) ?>"/>
    <input type="hidden" name="newMonitor[Port]" value="<?= validHtmlStr($newMonitor['Port']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Local' && $newMonitor['Type'] != 'Remote' && $newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc') )
{
?>
    <input type="hidden" name="newMonitor[Method]" value="<?= validHtmlStr($newMonitor['Method']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc' ))
{
?>
    <input type="hidden" name="newMonitor[Options]" value="<?= validHtmlStr($newMonitor['Options']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Remote' && $newMonitor['Type'] != 'File' && $newMonitor['Type'] != 'Ffmpeg' && $newMonitor['Type'] != 'Libvlc' && $newMonitor['Type'] != 'cURL') )
{
?>
    <input type="hidden" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>"/>
    <input type="hidden" name="newMonitor[User]" value="<?= validHtmlStr($newMonitor['User']) ?>"/>
    <input type="hidden" name="newMonitor[Pass]" value="<?= validHtmlStr($newMonitor['Pass']) ?>"/>
<?php
}
if ( $tab != 'source' )
{
?>
    <input type="hidden" name="newMonitor[Colours]" value="<?= validHtmlStr($newMonitor['Colours']) ?>"/>
    <input type="hidden" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>"/>
    <input type="hidden" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>"/>
    <input type="hidden" name="newMonitor[Orientation]" value="<?= validHtmlStr($newMonitor['Orientation']) ?>"/>
    <input type="hidden" name="newMonitor[Deinterlacing]" value="<?= validHtmlStr($newMonitor['Deinterlacing']) ?>"/>
<?php
}
if ( $tab != 'timestamp' )
{
?>
    <input type="hidden" name="newMonitor[LabelFormat]" value="<?= validHtmlStr($newMonitor['LabelFormat']) ?>"/>
    <input type="hidden" name="newMonitor[LabelX]" value="<?= validHtmlStr($newMonitor['LabelX']) ?>"/>
    <input type="hidden" name="newMonitor[LabelY]" value="<?= validHtmlStr($newMonitor['LabelY']) ?>"/>
<?php
}
if ( $tab != 'buffers' )
{
?>
    <input type="hidden" name="newMonitor[ImageBufferCount]" value="<?= validHtmlStr($newMonitor['ImageBufferCount']) ?>"/>
    <input type="hidden" name="newMonitor[WarmupCount]" value="<?= validHtmlStr($newMonitor['WarmupCount']) ?>"/>
    <input type="hidden" name="newMonitor[PreEventCount]" value="<?= validHtmlStr($newMonitor['PreEventCount']) ?>"/>
    <input type="hidden" name="newMonitor[PostEventCount]" value="<?= validHtmlStr($newMonitor['PostEventCount']) ?>"/>
    <input type="hidden" name="newMonitor[StreamReplayBuffer]" value="<?= validHtmlStr($newMonitor['StreamReplayBuffer']) ?>"/>
    <input type="hidden" name="newMonitor[AlarmFrameCount]" value="<?= validHtmlStr($newMonitor['AlarmFrameCount']) ?>"/>
<?php
}
if ( ZM_OPT_CONTROL && $tab != 'control' )
{
?>
    <input type="hidden" name="newMonitor[Controllable]" value="<?= validHtmlStr($newMonitor['Controllable']) ?>"/>
    <input type="hidden" name="newMonitor[ControlId]" value="<?= validHtmlStr($newMonitor['ControlId']) ?>"/>
    <input type="hidden" name="newMonitor[ControlDevice]" value="<?= validHtmlStr($newMonitor['ControlDevice']) ?>"/>
    <input type="hidden" name="newMonitor[ControlAddress]" value="<?= validHtmlStr($newMonitor['ControlAddress']) ?>"/>
    <input type="hidden" name="newMonitor[AutoStopTimeout]" value="<?= validHtmlStr($newMonitor['AutoStopTimeout']) ?>"/>
    <input type="hidden" name="newMonitor[TrackMotion]" value="<?= validHtmlStr($newMonitor['TrackMotion']) ?>"/>
    <input type="hidden" name="newMonitor[TrackDelay]" value="<?= validHtmlStr($newMonitor['TrackDelay']) ?>"/>
    <input type="hidden" name="newMonitor[ReturnLocation]" value="<?= validHtmlStr($newMonitor['ReturnLocation']) ?>"/>
    <input type="hidden" name="newMonitor[ReturnDelay]" value="<?= validHtmlStr($newMonitor['ReturnDelay']) ?>"/>
<?php
}
if ( ZM_OPT_X10 && $tab != 'x10' )
{
?>
    <input type="hidden" name="newX10Monitor[Activation]" value="<?= validHtmlStr($newX10Monitor['Activation']) ?>"/>
    <input type="hidden" name="newX10Monitor[AlarmInput]" value="<?= validHtmlStr($newX10Monitor['AlarmInput']) ?>"/>
    <input type="hidden" name="newX10Monitor[AlarmOutput]" value="<?= validHtmlStr($newX10Monitor['AlarmOutput']) ?>"/>
<?php
}
if ( $tab != 'misc' )
{
?>
    <input type="hidden" name="newMonitor[EventPrefix]" value="<?= validHtmlStr($newMonitor['EventPrefix']) ?>"/>
    <input type="hidden" name="newMonitor[SectionLength]" value="<?= validHtmlStr($newMonitor['SectionLength']) ?>"/>
    <input type="hidden" name="newMonitor[FrameSkip]" value="<?= validHtmlStr($newMonitor['FrameSkip']) ?>"/>
    <input type="hidden" name="newMonitor[MotionFrameSkip]" value="<?= validHtmlStr($newMonitor['MotionFrameSkip']) ?>"/>
    <input type="hidden" name="newMonitor[FPSReportInterval]" value="<?= validHtmlStr($newMonitor['FPSReportInterval']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultView]" value="<?= validHtmlStr($newMonitor['DefaultView']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultRate]" value="<?= validHtmlStr($newMonitor['DefaultRate']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultScale]" value="<?= validHtmlStr($newMonitor['DefaultScale']) ?>"/>
    <input type="hidden" name="newMonitor[WebColour]" value="<?= validHtmlStr($newMonitor['WebColour']) ?>"/>
<?php
}
if ( ZM_HAS_V4L && ($tab != 'misc' || $newMonitor['Type'] != 'Local') )
{
?>
    <input type="hidden" name="newMonitor[SignalCheckColour]" value="<?= validHtmlStr($newMonitor['SignalCheckColour']) ?>"/>
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
            <tr><td><?= $SLANG['Name'] ?></td><td><input type="text" name="newMonitor[Name]" value="<?= validHtmlStr($newMonitor['Name']) ?>" size="16"/></td></tr>
            <tr><td><?= $SLANG['SourceType'] ?></td><td><?= buildSelect( "newMonitor[Type]", $sourceTypes ); ?></td></tr>
            <tr><td><?= $SLANG['Function'] ?></td><td><select name="newMonitor[Function]">
<?php
        foreach ( getEnumValues( 'Monitors', 'Function' ) as $optFunction )
        {
?>
              <option value="<?= $optFunction ?>"<?php if ( $optFunction == $newMonitor['Function'] ) { ?> selected="selected"<?php } ?>><?= $optFunction ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?= $SLANG['Enabled'] ?></td><td><input type="checkbox" name="newMonitor[Enabled]" value="1"<?php if ( !empty($newMonitor['Enabled']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr>
              <td><?= $SLANG['LinkedMonitors'] ?></td>
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
                  <option value="<?= $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?= validHtmlStr($monitor['Name']) ?></option>
<?php
        }
    }
?>
                </select>
              </td>
            </tr>
            <tr><td><?= $SLANG['MaximumFPS'] ?></td><td><input type="text" name="newMonitor[MaxFPS]" value="<?= validHtmlStr($newMonitor['MaxFPS']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['AlarmMaximumFPS'] ?></td><td><input type="text" name="newMonitor[AlarmMaxFPS]" value="<?= validHtmlStr($newMonitor['AlarmMaxFPS']) ?>" size="6"/></td></tr>
<?php
	if ( ZM_FAST_IMAGE_BLENDS )
        {
?>
            <tr><td><?= $SLANG['RefImageBlendPct'] ?></td><td><select name="newMonitor[RefBlendPerc]"><?php foreach ( $fastblendopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['RefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></td><td><select name="newMonitor[AlarmRefBlendPerc]"><?php foreach ( $fastblendopts_alarm as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['AlarmRefBlendPerc'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
	} else {
?>
            <tr><td><?= $SLANG['RefImageBlendPct'] ?></td><td><input type="text" name="newMonitor[RefBlendPerc]" value="<?= validHtmlStr($newMonitor['RefBlendPerc']) ?>" size="4"/></td></tr>
            <tr><td><?= "Alarm " . $SLANG['RefImageBlendPct'] ?></td><td><input type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?= validHtmlStr($newMonitor['AlarmRefBlendPerc']) ?>" size="4"/></td></tr>
<?php
        }
?>
            <tr><td><?= $SLANG['Triggers'] ?></td><td>
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
              <input type="checkbox" name="newMonitor[Triggers][]" value="<?= $optTrigger ?>"<?php if ( isset($newMonitor['Triggers']) && in_array( $optTrigger, $newMonitor['Triggers'] ) ) { ?> checked="checked"<?php } ?>/>&nbsp;<?= $optTrigger ?>
<?php
            $optCount ++;
        }
        if ( !$optCount )
        {
?>
              <em><?= $SLANG['NoneAvailable'] ?></em>
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
            <tr><td><?= $SLANG['DevicePath'] ?></td><td><input type="text" name="newMonitor[Device]" value="<?= validHtmlStr($newMonitor['Device']) ?>" size="24"/></td></tr>
            <tr><td><?= $SLANG['CaptureMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $localMethods, "submitTab( '$tab' )" ); ?></td></tr>
<?php
            if ( ZM_HAS_V4L1 && $newMonitor['Method'] == 'v4l1' )
            {
?>
            <tr><td><?= $SLANG['DeviceChannel'] ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l1DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['DeviceFormat'] ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l1DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CapturePalette'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l1LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?= $SLANG['DeviceChannel'] ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $v4l2DeviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['DeviceFormat'] ?></td><td><select name="newMonitor[Format]"><?php foreach ( $v4l2DeviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CapturePalette'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $v4l2LocalPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
            }
?>
			<tr><td><?= $SLANG['V4LMultiBuffer'] ?></td><td>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]1" value="1" <?php echo ( $newMonitor['V4LMultiBuffer'] == 1 ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]1">Yes</label>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]0" value="0" <?php echo ( $newMonitor['V4LMultiBuffer'] == 0 ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]0">No</label>
				<input type="radio" name="newMonitor[V4LMultiBuffer]" id="newMonitor[V4LMultiBuffer]" value="" <?php echo ( empty($newMonitor['V4LMultiBuffer']) ? 'checked="checked"' : '' ) ?>/>
				<label for="newMonitor[V4LMultiBuffer]">Use Config Value</label>
			</td></tr>
			<tr><td><?= $SLANG['V4LCapturesPerFrame'] ?></td><td><input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo $newMonitor['V4LCapturesPerFrame'] ?>"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Remote" )
        {
?>
            <tr><td><?= $SLANG['RemoteProtocol'] ?></td><td><?= buildSelect( "newMonitor[Protocol]", $remoteProtocols, "updateMethods( this )" ); ?></td></tr>
<?php
            if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" )
            {
?>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $httpMethods ); ?></td></tr>
<?php
            }
            else
            {
?>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
<?php
            }
?>
            <tr><td><?= $SLANG['RemoteHostName'] ?></td><td><input type="text" name="newMonitor[Host]" value="<?= validHtmlStr($newMonitor['Host']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteHostPort'] ?></td><td><input type="text" name="newMonitor[Port]" value="<?= validHtmlStr($newMonitor['Port']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['RemoteHostPath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "File" )
        {
?>
            <tr><td><?= $SLANG['SourcePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "cURL" )
        {
?>
            <tr><td><?= "URL" ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= "Username" ?></td><td><input type="text" name="newMonitor[User]" value="<?= validHtmlStr($newMonitor['User']) ?>" size="12"/></td></tr>
            <tr><td><?= "Password" ?></td><td><input type="text" name="newMonitor[Pass]" value="<?= validHtmlStr($newMonitor['Pass']) ?>" size="12"/></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "Ffmpeg" || $newMonitor['Type'] == "Libvlc")
        {
?>
			<tr><td><?= $SLANG['SourcePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $rtspMethods ); ?></td></tr>
			<tr><td><?= $SLANG['Options'] ?>&nbsp;(<?= makePopupLink( '?view=optionhelp&amp;option=OPTIONS_'.strtoupper($newMonitor['Type']), 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td><td><input type="text" name="newMonitor[Options]" value="<?= validHtmlStr($newMonitor['Options']) ?>" size="36"/></td></tr>
<?php
        }
?>
            <tr><td><?= "Target Colorspace" ?></td><td><select name="newMonitor[Colours]"><?php foreach ( $Colours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Colours'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CaptureWidth'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['CaptureHeight'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['PreserveAspect'] ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
            <tr><td><?= $SLANG['Orientation'] ?></td><td><select name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        if ( $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= "Deinterlacing" ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts_v4l2 as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        } else {
?>
            <tr><td><?= "Deinterlacing" ?></td><td><select name="newMonitor[Deinterlacing]"><?php foreach ( $deinterlaceopts as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Deinterlacing'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        }
?>
<?php
        break;
    }
    case 'timestamp' :
    {
?>
            <tr><td><?= $SLANG['TimestampLabelFormat'] ?></td><td><input type="text" name="newMonitor[LabelFormat]" value="<?= validHtmlStr($newMonitor['LabelFormat']) ?>" size="32"/></td></tr>
            <tr><td><?= $SLANG['TimestampLabelX'] ?></td><td><input type="text" name="newMonitor[LabelX]" value="<?= validHtmlStr($newMonitor['LabelX']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['TimestampLabelY'] ?></td><td><input type="text" name="newMonitor[LabelY]" value="<?= validHtmlStr($newMonitor['LabelY']) ?>" size="4"/></td></tr>
<?php
        break;
    }
    case 'buffers' :
    {
?>
            <tr><td><?= $SLANG['ImageBufferSize'] ?></td><td><input type="text" name="newMonitor[ImageBufferCount]" value="<?= validHtmlStr($newMonitor['ImageBufferCount']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['WarmupFrames'] ?></td><td><input type="text" name="newMonitor[WarmupCount]" value="<?= validHtmlStr($newMonitor['WarmupCount']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['PreEventImageBuffer'] ?></td><td><input type="text" name="newMonitor[PreEventCount]" value="<?= validHtmlStr($newMonitor['PreEventCount']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['PostEventImageBuffer'] ?></td><td><input type="text" name="newMonitor[PostEventCount]" value="<?= validHtmlStr($newMonitor['PostEventCount']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['StreamReplayBuffer'] ?></td><td><input type="text" name="newMonitor[StreamReplayBuffer]" value="<?= validHtmlStr($newMonitor['StreamReplayBuffer']) ?>" size="6"/></td></tr>
<tr><td><?= $SLANG['AlarmFrameCount'] ?></td><td><input type="text" name="newMonitor[AlarmFrameCount]" value="<?= validHtmlStr($newMonitor['AlarmFrameCount']) ?>" size="4"/></td></tr>
<?php
        break;
    }
    case 'control' :
    {
?>
            <tr><td><?= $SLANG['Controllable'] ?></td><td><input type="checkbox" name="newMonitor[Controllable]" value="1"<?php if ( !empty($newMonitor['Controllable']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><td><?= $SLANG['ControlType'] ?></td><td><?= buildSelect( "newMonitor[ControlId]", $controlTypes, 'loadLocations( this )' ); ?><?php if ( canEdit( 'Control' ) ) { ?>&nbsp;<a href="#" onclick="createPopup( '?view=controlcaps', 'zmControlCaps', 'controlcaps' );"><?= $SLANG['Edit'] ?></a><?php } ?></td></tr>
            <tr><td><?= $SLANG['ControlDevice'] ?></td><td><input type="text" name="newMonitor[ControlDevice]" value="<?= validHtmlStr($newMonitor['ControlDevice']) ?>" size="32"/></td></tr>
            <tr><td><?= $SLANG['ControlAddress'] ?></td><td><input type="text" name="newMonitor[ControlAddress]" value="<?= validHtmlStr($newMonitor['ControlAddress']) ?>" size="32"/></td></tr>
            <tr><td><?= $SLANG['AutoStopTimeout'] ?></td><td><input type="text" name="newMonitor[AutoStopTimeout]" value="<?= validHtmlStr($newMonitor['AutoStopTimeout']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['TrackMotion'] ?></td><td><input type="checkbox" name="newMonitor[TrackMotion]" value="1"<?php if ( !empty($newMonitor['TrackMotion']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        $return_options = array(
            '-1' => $SLANG['None'],
            '0' => $SLANG['Home'],
            '1' => $SLANG['Preset']." 1",
        );
?>
            <tr><td><?= $SLANG['TrackDelay'] ?></td><td><input type="text" name="newMonitor[TrackDelay]" value="<?= validHtmlStr($newMonitor['TrackDelay']) ?>" size="4"/></td></tr>
            <tr><td><?= $SLANG['ReturnLocation'] ?></td><td><?= buildSelect( "newMonitor[ReturnLocation]", $return_options ); ?></td></tr>
            <tr><td><?= $SLANG['ReturnDelay'] ?></td><td><input type="text" name="newMonitor[ReturnDelay]" value="<?= validHtmlStr($newMonitor['ReturnDelay']) ?>" size="4"/></td></tr>
<?php
        break;
    }
    case 'x10' :
    {
?>
            <tr><td><?= $SLANG['X10ActivationString'] ?></td><td><input type="text" name="newX10Monitor[Activation]" value="<?= validHtmlStr($newX10Monitor['Activation']) ?>" size="20"/></td></tr>
            <tr><td><?= $SLANG['X10InputAlarmString'] ?></td><td><input type="text" name="newX10Monitor[AlarmInput]" value="<?= validHtmlStr($newX10Monitor['AlarmInput']) ?>" size="20"/></td></tr>
            <tr><td><?= $SLANG['X10OutputAlarmString'] ?></td><td><input type="text" name="newX10Monitor[AlarmOutput]" value="<?= validHtmlStr($newX10Monitor['AlarmOutput']) ?>" size="20"/></td></tr>
<?php
        break;
    }
    case 'misc' :
    {
?>
            <tr><td><?= $SLANG['EventPrefix'] ?></td><td><input type="text" name="newMonitor[EventPrefix]" value="<?= validHtmlStr($newMonitor['EventPrefix']) ?>" size="24"/></td></tr>
            <tr><td><?= $SLANG['Sectionlength'] ?></td><td><input type="text" name="newMonitor[SectionLength]" value="<?= validHtmlStr($newMonitor['SectionLength']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['FrameSkip'] ?></td><td><input type="text" name="newMonitor[FrameSkip]" value="<?= validHtmlStr($newMonitor['FrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['MotionFrameSkip'] ?></td><td><input type="text" name="newMonitor[MotionFrameSkip]" value="<?= validHtmlStr($newMonitor['MotionFrameSkip']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['FPSReportInterval'] ?></td><td><input type="text" name="newMonitor[FPSReportInterval]" value="<?= validHtmlStr($newMonitor['FPSReportInterval']) ?>" size="6"/></td></tr>
            <tr><td><?= $SLANG['DefaultView'] ?></td><td><select name="newMonitor[DefaultView]">
<?php
        foreach ( getEnumValues( 'Monitors', 'DefaultView' ) as $opt_view )
        {
          if ( $opt_view == 'Control' && ( !ZM_OPT_CONTROL || !$monitor['Controllable'] ) )
            continue;
?>
              <option value="<?= $opt_view ?>"<?php if ( $opt_view == $newMonitor['DefaultView'] ) { ?> selected="selected"<?php } ?>><?= $opt_view ?></option>
<?php
        }
?>
            </select></td></tr>
            <tr><td><?= $SLANG['DefaultRate'] ?></td><td><?= buildSelect( "newMonitor[DefaultRate]", $rates ); ?></td></tr>
            <tr><td><?= $SLANG['DefaultScale'] ?></td><td><?= buildSelect( "newMonitor[DefaultScale]", $scales ); ?></td></tr>
<?php
        if ( ZM_HAS_V4L && $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= $SLANG['SignalCheckColour'] ?></td><td><input type="text" name="newMonitor[SignalCheckColour]" value="<?= validHtmlStr($newMonitor['SignalCheckColour']) ?>" size="10" onchange="$('SignalCheckSwatch').setStyle( 'backgroundColor', this.value )"/><span id="SignalCheckSwatch" class="swatch" style="background-color: <?= $newMonitor['SignalCheckColour'] ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
<?php
        }
?>
            <tr><td><?= $SLANG['WebColour'] ?></td><td><input type="text" name="newMonitor[WebColour]" value="<?= validHtmlStr($newMonitor['WebColour']) ?>" size="10" onchange="$('WebSwatch').setStyle( 'backgroundColor', this.value )"/><span id="WebSwatch" class="swatch" style="background-color: <?= validHtmlStr($newMonitor['WebColour']) ?>;">&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>
<?php
        break;
    }
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
