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

	<?php include("header.php"); ?>

	<div class="container-fluid">
		<div id="headerButtons">
			<a href="#" onclick="createPopup( '?view=monitorprobe&amp;mid=<?= $monitor['Id'] ?>', 'zmMonitorProbe<?= $monitor['Id'] ?>', 'monitorprobe' ); return( false );"><?= $SLANG['Probe'] ?></a>
			<a href="#" onclick="createPopup( '?view=monitorpreset&amp;mid=<?= $monitor['Id'] ?>', 'zmMonitorPreset<?= $monitor['Id'] ?>', 'monitorpreset' ); return( false );"><?= $SLANG['Presets'] ?></a>
		</div>
		<h2><?= $SLANG['Monitor'] ?> - <?= validHtmlStr($monitor['Name']) ?><?php if ( !empty($monitor['Id']) ) { ?> (<?= $monitor['Id'] ?>)<?php } ?></h2>
	</div>

	<div class="container-fluid">

		<div class="container" ng-controller="MonitorController">
			<ul class="nav nav-tabs" role="tablist" id="tabMonitorAdd">
				<li class="active" role="presentation"><a href="#general" aria-expanded="true" aria-controls="general" role="tab" data-toggle="tab">General</a></li>
				<li role="presentation"><a href="#source" aria-controls="source" role="tab" data-toggle="tab">Source</a></li>
				<li role="presentation"><a href="#timestamp" aria-controls="timestamp" role="tab" data-toggle="tab">Timestamps</a></li>
				<li role="presentation"><a href="#buffers" aria-controls="buffers" role="tab" data-toggle="tab">Buffers</a></li>
				<li role="presentation"><a href="#misc" aria-controls="misc" role="tab" data-toggle="tab">Misc</a></li>
			</ul> <!-- End tabs -->

			<div class="tab-content">
				<?php include("tab-monitor-general.php"); ?>
				<?php include("tab-monitor-source.php"); ?>
				<?php include("tab-monitor-timestamp.php"); ?>
				<?php include("tab-monitor-buffers.php"); ?>
				<?php include("tab-monitor-misc.php"); ?>
			</div> <!-- End .tab-content -->
		</div>

	</div> <!-- End .container-fluid -->
	<?php include("footer.php"); ?>
</body>
</html>
