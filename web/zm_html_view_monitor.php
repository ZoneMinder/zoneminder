<?php
//
// ZoneMinder web monitor view file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
$tabs["monitor"] = $zmSlangMonitor;
$tabs["source"] = $zmSlangSource;
$tabs["timestamp"] = $zmSlangTimestamp;
$tabs["buffers"] = $zmSlangBuffers;
$tabs["misc"] = $zmSlangMisc;
if ( ZM_OPT_X10 )
{
	$tabs["x10"] = $zmSlangX10;
}

if ( !isset($tab) )
	$tab = "monitor";

if ( !empty($mid) )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
	if ( ZM_OPT_X10 )
	{
		$result = mysql_query( "select * from TriggersX10 where MonitorId = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$x10_monitor = mysql_fetch_assoc( $result );
	}
}
else
{
	$monitor = array();
	$monitor['Name'] = $zmSlangNew;
	$monitor['Function'] = "None";
	$monitor['RunMode'] = "Continuous";
	$monitor['Type'] = "Local";
	$monitor['Device'] = "";
	$monitor['Channel'] = "";
	$monitor['Format'] = "";
	$monitor['Host'] = "";
	$monitor['Path'] = "";
	$monitor['Port'] = "80";
	$monitor['Width'] = "";
	$monitor['Height'] = "";
	$monitor['Orientation'] = "0";
	$monitor['LabelFormat'] = '%%s - %y/%m/%d %H:%M:%S';
	$monitor['LabelX'] = 0;
	$monitor['LabelY'] = 0;
	$monitor['ImageBufferCount'] = 100;
	$monitor['WarmupCount'] = 25;
	$monitor['PreEventCount'] = 10;
	$monitor['PostEventCount'] = 10;
	$monitor['AlarmFrameCount'] = 1;
	$monitor['SectionLength'] = 600;
	$monitor['FrameSkip'] = 0;
	$monitor['EventPrefix'] = 'Event-';
	$monitor['MaxFPS'] = 0;
	$monitor['FPSReportInterval'] = 1000;
	$monitor['RefBlendPerc'] = 10;
	$monitor['Triggers'] = "";
}
if ( !isset( $new_monitor ) )
{
	$new_monitor = $monitor;
	$new_monitor['Triggers'] = split( ',', isset($monitor['Triggers'])?$monitor['Triggers']:"" );
	$new_x10_monitor = isset($x10_monitor)?$x10_monitor:array();
}
$local_palettes = array( $zmSlangGrey=>1, "RGB24"=>4, "RGB565"=>3, "RGB555"=>6, "YUV422"=>7, "YUYV"=>8, "YUV422P"=>13, "YUV420P"=>15 );
$remote_palettes = array( $zmSlang8BitGrey=>1, $zmSlang24BitColour=>4 );
$orientations = array( $zmSlangNormal=>0, $zmSlangRotateRight=>90, $zmSlangInverted=>180, $zmSlangRotateLeft=>270 );

?>
<html>
<head>
<title>ZM - <?= $zmSlangMonitor ?> <?= $monitor['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script language="JavaScript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
function validateForm(Form)
{
	var errors = new Array();

	if ( Form.elements['new_monitor[Name]'].value.search( /[^\w-]/ ) >= 0 )
	{
		errors[errors.length] = "<?= $zmSlangBadMonitorChars ?>";
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function submitTab(Form,Tab)
{
	Form.action.value = "";
	Form.tab.value = Tab;
	Form.submit();
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="head"><?= $zmSlangMonitor ?> <?= $monitor['Name'] ?></td>
</tr>
<tr>
<td>&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<?php
foreach ( $tabs as $name=>$value )
{
	if ( $tab == $name )
	{
?>
<td width="10" class="activetab"><?= $value ?></td>
<?php
	}
	else
	{
?>
<td width="10" class="passivetab"><a href="javascript: submitTab( document.monitor_form, '<?= $name ?>' );"><?= $value ?></a></td>
<?php
	}
}
?>
<td class="nontab">&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<form name="monitor_form" method="post" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.monitor_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="monitor">
<input type="hidden" name="mid" value="<?= $mid ?>">
<?php
if ( $tab != 'monitor' )
{
?>
<input type="hidden" name="new_monitor[Name]" value="<?= $new_monitor['Name'] ?>">
<input Type="hidden" name="new_monitor[Type]" value="<?= $new_monitor['Type'] ?>">
<input type="hidden" name="new_monitor[Function]" value="<?= $new_monitor['Function'] ?>">
<input type="hidden" name="new_monitor[SectionLength]" value="<?= $new_monitor['SectionLength'] ?>">
<input type="hidden" name="new_monitor[FrameSkip]" value="<?= $new_monitor['FrameSkip'] ?>">
<input type="hidden" name="new_monitor[RunMode]" value="<?= $new_monitor['RunMode'] ?>">
<?php
	if ( isset($new_monitor['Triggers']) )
	{
		foreach( $new_monitor['Triggers'] as $new_trigger )
		{
?>
<input type="hidden" name="new_monitor[Triggers][]" value="<?= $new_trigger ?>">
<?php
		}
	}
}
if ( $tab != 'source' )
{
?>
<input type="hidden" name="new_monitor[Device]" value="<?= $new_monitor['Device'] ?>">
<input type="hidden" name="new_monitor[Channel]" value="<?= $new_monitor['Channel'] ?>">
<input type="hidden" name="new_monitor[Format]" value="<?= $new_monitor['Format'] ?>">
<input type="hidden" name="new_monitor[Host]" value="<?= $new_monitor['Host'] ?>">
<input type="hidden" name="new_monitor[Port]" value="<?= $new_monitor['Port'] ?>">
<input type="hidden" name="new_monitor[Path]" value="<?= $new_monitor['Path'] ?>">
<input type="hidden" name="new_monitor[Palette]" value="<?= $new_monitor['Palette'] ?>">
<input type="hidden" name="new_monitor[Width]" value="<?= $new_monitor['Width'] ?>">
<input type="hidden" name="new_monitor[Height]" value="<?= $new_monitor['Height'] ?>">
<input type="hidden" name="new_monitor[Orientation]" value="<?= $new_monitor['Orientation'] ?>">
<?php
}
if ( $tab != 'timestamp' )
{
?>
<input type="hidden" name="new_monitor[LabelFormat]" value="<?= $new_monitor['LabelFormat'] ?>">
<input type="hidden" name="new_monitor[LabelX]" value="<?= $new_monitor['LabelX'] ?>">
<input type="hidden" name="new_monitor[LabelY]" value="<?= $new_monitor['LabelY'] ?>">
<?php
}
if ( $tab != 'buffers' )
{
?>
<input type="hidden" name="new_monitor[ImageBufferCount]" value="<?= $new_monitor['ImageBufferCount'] ?>">
<input type="hidden" name="new_monitor[WarmupCount]" value="<?= $new_monitor['WarmupCount'] ?>">
<input type="hidden" name="new_monitor[PreEventCount]" value="<?= $new_monitor['PreEventCount'] ?>">
<input type="hidden" name="new_monitor[PostEventCount]" value="<?= $new_monitor['PostEventCount'] ?>">
<input type="hidden" name="new_monitor[AlarmFrameCount]" value="<?= $new_monitor['AlarmFrameCount'] ?>">
<?php
}
if ( $tab != 'misc' )
{
?>
<input type="hidden" name="new_monitor[EventPrefix]" value="<?= $new_monitor['EventPrefix'] ?>">
<input type="hidden" name="new_monitor[MaxFPS]" value="<?= $new_monitor['MaxFPS'] ?>">
<input type="hidden" name="new_monitor[FPSReportInterval]" value="<?= $new_monitor['FPSReportInterval'] ?>">
<input type="hidden" name="new_monitor[RefBlendPerc]" value="<?= $new_monitor['RefBlendPerc'] ?>">
<?php
}
if ( ZM_OPT_X10 && $tab != 'x10' )
{
?>
<input type="hidden" name="new_x10_monitor[Activation]" value="<?= $new_x10_monitor['Activation'] ?>">
<input type="hidden" name="new_x10_monitor[AlarmInput]" value="<?= $new_x10_monitor['AlarmInput'] ?>">
<input type="hidden" name="new_x10_monitor[AlarmOutput]" value="<?= $new_x10_monitor['AlarmOutput'] ?>">
<?php
}
?>
<tr>
<td align="left" class="smallhead" width="70%"><?= $zmSlangParameter ?></td><td align="left" class="smallhead" width="30%"><?= $zmSlangValue ?></td>
</tr>
<?php
switch ( $tab )
{
	case 'monitor' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_monitor[Name]" value="<?= $new_monitor['Name'] ?>" size="16" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangFunction ?></td><td align="left" class="text"><select name="new_monitor[Function]" class="form">
<?php
		foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
		{
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $new_monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
		}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangSectionlength ?></td><td align="left" class="text"><input type="text" name="new_monitor[SectionLength]" value="<?= $new_monitor['SectionLength'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangFrameSkip ?></td><td align="left" class="text"><input type="text" name="new_monitor[FrameSkip]" value="<?= $new_monitor['FrameSkip'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangRunMode ?></td><td align="left" class="text"><select name="new_monitor[RunMode]" class="form">
<?php
		foreach ( getEnumValues( 'Monitors', 'RunMode' ) as $opt_runmode )
		{
?>
<option value="<?= $opt_runmode ?>"<?php if ( $opt_runmode == $new_monitor['RunMode'] ) { ?> selected<?php } ?>><?= $opt_runmode ?></option>
<?php
		}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangTriggers ?></td><td align="left" class="text">
<?php
		$opt_triggers = getSetValues( 'Monitors', 'Triggers' );
		$break_count = (int)(ceil(count($opt_triggers)));
		$break_count = min( 3, $break_count );
		$opt_count = 0;
		foreach( $opt_triggers as $opt_trigger )
		{
			if ( !ZM_OPT_X10 && $opt_trigger == 'X10' )
				continue;
			if ( $opt_count && ($opt_count%$break_count == 0) )
				echo "</br>";
?>
<input type="checkbox" name="new_monitor[Triggers][]" value="<?= $opt_trigger ?>" class="form-noborder"<?php if ( isset($new_monitor['Triggers']) && in_array( $opt_trigger, $new_monitor['Triggers'] ) ) { ?> checked<?php } ?>><?= $opt_trigger ?>
<?php
			$opt_count ++;
		}
		if ( !$opt_count )
		{
?>
<em><?= $zmSlangNoneAvailable ?></em>
<?php
		}
?>
</td></tr>
<?php
		$select_name = "new_monitor[Type]";
		$source_types = array( 'Local'=>$zmSlangLocal, 'Remote'=>$zmSlangRemote );
?>
<tr><td align="left" class="text"><?= $zmSlangSourceType ?></td><td><?= buildSelect( $select_name, $source_types ); ?></td></tr>
<?php
		break;
	}
	case 'source' :
	{
		if ( $new_monitor['Type'] == "Local" )
		{
?>
<tr><td align="left" class="text"><?= $zmSlangDeviceNumber ?></td><td align="left" class="text"><input type="text" name="new_monitor[Device]" value="<?= $new_monitor['Device'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangDeviceChannel ?></td><td align="left" class="text"><input type="text" name="new_monitor[Channel]" value="<?= $new_monitor['Channel'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangDeviceFormat ?></td><td align="left" class="text"><input type="text" name="new_monitor[Format]" value="<?= $new_monitor['Format'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCapturePalette ?></td><td align="left" class="text"><select name="new_monitor[Palette]" class="form"><?php foreach ( $local_palettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor['Palette'] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
		}
		else
		{
?>
<tr><td align="left" class="text"><?= $zmSlangRemoteHostName ?></td><td align="left" class="text"><input type="text" name="new_monitor[Host]" value="<?= $new_monitor['Host'] ?>" size="36" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangRemoteHostPort ?></td><td align="left" class="text"><input type="text" name="new_monitor[Port]" value="<?= $new_monitor['Port'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangRemoteHostPath ?></td><td align="left" class="text"><input type="text" name="new_monitor[Path]" value="<?= $new_monitor['Path'] ?>" size="36" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangRemoteImageColours ?></td><td align="left" class="text"><select name="new_monitor[Palette]" class="form"><?php foreach ( $remote_palettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor['Palette'] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
		}
?>
<tr><td align="left" class="text"><?= $zmSlangCaptureWidth ?> (<?= $zmSlangPixels ?>)</td><td align="left" class="text"><input type="text" name="new_monitor[Width]" value="<?= $new_monitor['Width'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCaptureHeight ?> (<?= $zmSlangPixels ?>)</td><td align="left" class="text"><input type="text" name="new_monitor[Height]" value="<?= $new_monitor['Height'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangOrientation ?></td><td align="left" class="text"><select name="new_monitor[Orientation]" class="form"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor['Orientation'] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
		break;
	}
	case 'timestamp' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangTimestampLabelFormat ?></td><td align="left" class="text"><input type="text" name="new_monitor[LabelFormat]" value="<?= $new_monitor['LabelFormat'] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangTimestampLabelX ?></td><td align="left" class="text"><input type="text" name="new_monitor[LabelX]" value="<?= $new_monitor['LabelX'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangTimestampLabelY ?></td><td align="left" class="text"><input type="text" name="new_monitor[LabelY]" value="<?= $new_monitor['LabelY'] ?>" size="4" class="form"></td></tr>
<?php
		break;
	}
	case 'buffers' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangImageBufferSize ?></td><td align="left" class="text"><input type="text" name="new_monitor[ImageBufferCount]" value="<?= $new_monitor['ImageBufferCount'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangWarmupFrames ?></td><td align="left" class="text"><input type="text" name="new_monitor[WarmupCount]" value="<?= $new_monitor['WarmupCount'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangPreEventImageBuffer ?></td><td align="left" class="text"><input type="text" name="new_monitor[PreEventCount]" value="<?= $new_monitor['PreEventCount'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangPostEventImageBuffer ?></td><td align="left" class="text"><input type="text" name="new_monitor[PostEventCount]" value="<?= $new_monitor['PostEventCount'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangAlarmFrameCount ?></td><td align="left" class="text"><input type="text" name="new_monitor[AlarmFrameCount]" value="<?= $new_monitor['AlarmFrameCount'] ?>" size="4" class="form"></td></tr>
<?php
		break;
	}
	case 'misc' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangEventPrefix ?></td><td align="left" class="text"><input type="text" name="new_monitor[EventPrefix]" value="<?= $new_monitor['EventPrefix'] ?>" size="24" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaximumFPS ?></td><td align="left" class="text"><input type="text" name="new_monitor[MaxFPS]" value="<?= $new_monitor['MaxFPS'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangFPSReportInterval ?></td><td align="left" class="text"><input type="text" name="new_monitor[FPSReportInterval]" value="<?= $new_monitor['FPSReportInterval'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangRefImageBlendPct ?></td><td align="left" class="text"><input type="text" name="new_monitor[RefBlendPerc]" value="<?= $new_monitor['RefBlendPerc'] ?>" size="4" class="form"></td></tr>
<?php
		break;
	}
	case 'x10' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangX10ActivationString ?></td><td align="left" class="text"><input type="text" name="new_x10_monitor[Activation]" value="<?= $new_x10_monitor['Activation'] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangX10InputAlarmString ?></td><td align="left" class="text"><input type="text" name="new_x10_monitor[AlarmInput]" value="<?= $new_x10_monitor['AlarmInput'] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangX10OutputAlarmString ?></td><td align="left" class="text"><input type="text" name="new_x10_monitor[AlarmOutput]" value="<?= $new_x10_monitor['AlarmOutput'] ?>" size="20" class="form"></td></tr>
<?php
		break;
	}
}
?>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</form>
</table>
</body>
</html>
