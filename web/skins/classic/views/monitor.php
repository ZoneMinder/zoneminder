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

if ( !empty($_REQUEST['mid']) )
{
    $monitor = dbFetchMonitor( $_REQUEST['mid'] );
    if ( ZM_OPT_X10 )
        $x10Monitor = dbFetchOne( "select * from TriggersX10 where MonitorId = '".dbEscape($_REQUEST['mid'])."'" );
}
else
{
    $monitor = array(
        'Id' => 0,
        'Name' => $SLANG['New'],
        'Function' => "Monitor",
        'Enabled' => true,
        'LinkedMonitors' => "",
        'Type' => "Local",
        'Device' => "/dev/video",
        'Channel' => "0",
        'Format' => "0",
        'Protocol' => "",
        'Method' => "",
        'Host' => "",
        'Path' => "",
        'SubPath' => "",
        'Port' => "80",
        'Palette' => "",
        'Width' => "",
        'Height' => "",
        'Orientation' => "0",
        'LabelFormat' => '%N - %y/%m/%d %H:%M:%S',
        'LabelX' => 0,
        'LabelY' => 0,
        'ImageBufferCount' => 40,
        'WarmupCount' => 25,
        'PreEventCount' => 10,
        'PostEventCount' => 10,
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
        'EventPrefix' => 'Event-',
        'MaxFPS' => "",
        'AlarmMaxFPS' => "",
        'FPSReportInterval' => 1000,
        'RefBlendPerc' => 7,
        'DefaultView' => 'Events',
        'DefaultRate' => '100',
        'DefaultScale' => '100',
        'SignalCheckColour' => '#0100BE',
        'WebColour' => 'red',
        'Triggers' => "",
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

if ( isset( $_REQUEST['newMonitor'] ) )
{
    $newMonitor = $_REQUEST['newMonitor'];
    if ( ZM_OPT_X10 )
        $newX10Monitor = $_REQUEST['newX10Monitor'];
}
else
{
    $newMonitor = $monitor;
    $newMonitor['Triggers'] = split( ',', isset($monitor['Triggers'])?$monitor['Triggers']:"" );
    if ( ZM_OPT_X10 )
        $newX10Monitor = $x10Monitor;
}

if ( $newMonitor['MaxFPS'] == '0.00' )
    $newMonitor['MaxFPS'] = '';
if ( $newMonitor['AlarmMaxFPS'] == '0.00' )
    $newMonitor['AlarmMaxFPS'] = '';

if ( !empty($_REQUEST['preset']) )
{
    $preset = dbFetchOne( "select Type, Device, Channel, Format, Protocol, Method, Host, Port, Path, SubPath, Width, Height, Palette, MaxFPS, Controllable, ControlId, ControlDevice, ControlAddress, DefaultRate, DefaultScale from MonitorPresets where Id = '".dbEscape($_REQUEST['preset'])."'" );
    foreach ( $preset as $name=>$value )
    {
        if ( isset($value) )
        {
            $newMonitor[$name] = $value;
        }
    }
}

$sourceTypes = array(
    'Local'  => $SLANG['Local'],
    'Remote' => $SLANG['Remote'],
    'File'   => $SLANG['File'],
    'Ffmpeg' => $SLANG['Ffmpeg'],
);

$localMethods = array(
    'v4l2' => "Video For Linux version 2",
    'v4l1' => "Video For Linux version 1",
);
if ( !ZM_V4L2 )
    unset($localMethods['v4l2']);

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

$deviceFormats = array(
    "PAL"   => 0,
    "NTSC"  => 1,
    "SECAM" => 2,
    "AUTO"  => 3,
    "FMT4"  => 4,
    "FMT5"  => 5,
    "FMT6"  => 6,
    "FMT7"  => 7
);

$deviceChannels = array();
for ( $i = 0; $i <= 15; $i++ )
    $deviceChannels["$i"] = $i;

$localPalettes = array(
    $SLANG['Grey'] => 1,
    "RGB24"        => 4,
    "RGB565"       => 3,
    "RGB555"       => 6,
    "YUV422"       => 7,
    "YUYV"         => 8,
    "YUV422P"      => 13,
    "YUV420P"      => 15
);

$remoteColours = $fileColours = array(
    $SLANG['8BitGrey']    => 1,
    $SLANG['24BitColour'] => 3
);

$orientations = array(
    $SLANG['Normal']      => '0',
    $SLANG['RotateRight'] => '90',
    $SLANG['Inverted']    => '180',
    $SLANG['RotateLeft']  => '270',
    $SLANG['FlippedHori'] => 'hori',
    $SLANG['FlippedVert'] => 'vert'
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
        <a href="#" onclick="createPopup( '?view=monitorpreset&mid=<?= $monitor['Id'] ?>', 'zmMonitorPreset<?= $monitor['Id'] ?>', 'monitorpreset' ); return( false );"><?= $SLANG['Presets'] ?></a>
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
if ( $tab != 'source' || $newMonitor['Type'] != 'Local' )
{
?>
    <input type="hidden" name="newMonitor[Device]" value="<?= validHtmlStr($newMonitor['Device']) ?>"/>
    <input type="hidden" name="newMonitor[Channel]" value="<?= validHtmlStr($newMonitor['Channel']) ?>"/>
    <input type="hidden" name="newMonitor[Format]" value="<?= validHtmlStr($newMonitor['Format']) ?>"/>
    <input type="hidden" name="newMonitor[Palette]" value="<?= validHtmlStr($newMonitor['Palette']) ?>"/>
<?php
}
if ( $tab != 'source' || $newMonitor['Type'] != 'Remote' )
{
?>
    <input type="hidden" name="newMonitor[Protocol]" value="<?= validHtmlStr($newMonitor['Protocol']) ?>"/>
    <input type="hidden" name="newMonitor[Host]" value="<?= validHtmlStr($newMonitor['Host']) ?>"/>
    <input type="hidden" name="newMonitor[Port]" value="<?= validHtmlStr($newMonitor['Port']) ?>"/>
    <input type="hidden" name="newMonitor[SubPath]" value="<?= validHtmlStr($newMonitor['SubPath']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Local' && $newMonitor['Type'] != 'Remote') )
{
?>
    <input type="hidden" name="newMonitor[Method]" value="<?= validHtmlStr($newMonitor['Method']) ?>"/>
<?php
}
if ( $tab != 'source' || ($newMonitor['Type'] != 'Remote' && $newMonitor['Type'] != 'File' && $newMonitor['Type'] != 'Ffmpeg') )
{
?>
    <input type="hidden" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>"/>
<?php
}
if ( $tab != 'source' )
{
?>
    <input type="hidden" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>"/>
    <input type="hidden" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>"/>
    <input type="hidden" name="newMonitor[Orientation]" value="<?= validHtmlStr($newMonitor['Orientation']) ?>"/>
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
    <input type="hidden" name="newMonitor[FPSReportInterval]" value="<?= validHtmlStr($newMonitor['FPSReportInterval']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultView]" value="<?= validHtmlStr($newMonitor['DefaultView']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultRate]" value="<?= validHtmlStr($newMonitor['DefaultRate']) ?>"/>
    <input type="hidden" name="newMonitor[DefaultScale]" value="<?= validHtmlStr($newMonitor['DefaultScale']) ?>"/>
    <input type="hidden" name="newMonitor[WebColour]" value="<?= validHtmlStr($newMonitor['WebColour']) ?>"/>
<?php
}
if ( $tab != 'misc' || $newMonitor['Type'] != 'Local' )
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
        $monitorIds = array_flip( split( ',', $newMonitor['LinkedMonitors'] ) );
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
            <tr><td><?= $SLANG['RefImageBlendPct'] ?></td><td><input type="text" name="newMonitor[RefBlendPerc]" value="<?= validHtmlStr($newMonitor['RefBlendPerc']) ?>" size="4"/></td></tr>
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
        // Set up initial palette value
        if ( $newMonitor['Palette'] == '' )
        {
            if ( $newMonitor['Type'] == 'Local' )
                $newMonitor['Palette'] = 4;
            else
                $newMonitor['Palette'] = 3;
        }
        if ( $newMonitor['Type'] == "Local" )
        {
?>
            <tr><td><?= $SLANG['DevicePath'] ?></td><td><input type="text" name="newMonitor[Device]" value="<?= validHtmlStr($newMonitor['Device']) ?>" size="24"/></td></tr>
            <tr><td><?= $SLANG['DeviceChannel'] ?></td><td><select name="newMonitor[Channel]"><?php foreach ( $deviceChannels as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Channel'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['DeviceFormat'] ?></td><td><select name="newMonitor[Format]"><?php foreach ( $deviceFormats as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Format'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
            <tr><td><?= $SLANG['CaptureMethod'] ?></td><td><?= buildSelect( "newMonitor[Method]", $localMethods ); ?></td></tr>
            <tr><td><?= $SLANG['CapturePalette'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $localPalettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
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
            <tr><td><?= $SLANG['RemoteHostSubPath'] ?></td><td><input type="text" name="newMonitor[SubPath]" value="<?= validHtmlStr($newMonitor['SubPath']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['RemoteImageColours'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $remoteColours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        }
        elseif ( $newMonitor['Type'] == "File" || $newMonitor['Type'] == "Ffmpeg" )
        {
?>
            <tr><td><?= $SLANG['FilePath'] ?></td><td><input type="text" name="newMonitor[Path]" value="<?= validHtmlStr($newMonitor['Path']) ?>" size="36"/></td></tr>
            <tr><td><?= $SLANG['FileColours'] ?></td><td><select name="newMonitor[Palette]"><?php foreach ( $fileColours as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Palette'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
        }
?>
            <tr><td><?= $SLANG['CaptureWidth'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Width]" value="<?= validHtmlStr($newMonitor['Width']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['CaptureHeight'] ?> (<?= $SLANG['Pixels'] ?>)</td><td><input type="text" name="newMonitor[Height]" value="<?= validHtmlStr($newMonitor['Height']) ?>" size="4" onkeyup="updateMonitorDimensions(this);"/></td></tr>
            <tr><td><?= $SLANG['PreserveAspect'] ?></td><td><input type="checkbox" name="preserveAspectRatio" value="1"/></td></tr> 
            <tr><td><?= $SLANG['Orientation'] ?></td><td><select name="newMonitor[Orientation]"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $newMonitor['Orientation'] ) { ?> selected="selected"<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
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
        if ( $newMonitor['Type'] == "Local" )
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
