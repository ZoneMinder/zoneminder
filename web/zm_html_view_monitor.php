<?php
	if ( !canView( 'Monitors' ) )
	{
		$view = "error";
		return;
	}

	$tabs = array();
	$tabs["monitor"] = "Monitor";
	$tabs["source"] = "Source";
	$tabs["timestamp"] = "Timestamp";
	$tabs["buffers"] = "Buffers";
	$tabs["misc"] = "Misc";
	if ( ZM_OPT_X10 )
	{
		$tabs["x10"] = "X10";
	}

	if ( !$tab )
		$tab = "monitor";

	if ( $mid > 0 )
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
		$monitor[Name] = "New";
		$monitor['Function'] = "None";
		$monitor['RunMode'] = "Continuous";
		$monitor[Type] = "Local";
		$monitor[Port] = "80";
		$monitor[Orientation] = "0";
		$monitor[LabelFormat] = '%%s - %y/%m/%d %H:%M:%S';
		$monitor[LabelX] = 0;
		$monitor[LabelY] = 0;
		$monitor[ImageBufferCount] = 100;
		$monitor[WarmupCount] = 25;
		$monitor[PreEventCount] = 10;
		$monitor[PostEventCount] = 10;
		$monitor[SectionLength] = 600;
		$monitor[FrameSkip] = 0;
		$monitor[MaxFPS] = 0;
		$monitor[FPSReportInterval] = 1000;
		$monitor[RefBlendPerc] = 10;
	}
	if ( !isset( $new_monitor ) )
	{
		$new_monitor = $monitor;
		$new_monitor[Triggers] = split( ',', $monitor[Triggers] );
		$new_x10_monitor = $x10_monitor;
	}
	$local_palettes = array( "Grey"=>1, "RGB24"=>4, "RGB565"=>3, "YUV420P"=>15 );
	$remote_palettes = array( "8 bit greyscale"=>1, "24 bit colour"=>4 );
	$orientations = array( "Normal"=>0, "Rotate Right"=>90, "Inverted"=>180, "Rotate Left"=>270 );
?>
<html>
<head>
<title>ZM - Monitor <?= $monitor[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
	if ( $refresh_parent )
	{
?>
opener.location.reload(true);
<?php
	}
?>
window.focus();
function validateForm(Form)
{
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
<td align="left" class="head">Monitor <?= $monitor[Name] ?></td>
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
<form name="monitor_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.monitor_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="monitor">
<input type="hidden" name="mid" value="<?= $mid ?>">
<?php
	if ( $tab != 'monitor' )
	{
?>
<input type="hidden" name="new_monitor[Name]" value="<?= $new_monitor[Name] ?>">
<input Type="hidden" name="new_monitor[Type]" value="<?= $new_monitor['Type'] ?>">
<input type="hidden" name="new_monitor[Function]" value="<?= $new_monitor['Function'] ?>">
<input type="hidden" name="new_monitor[SectionLength]" value="<?= $new_monitor[SectionLength] ?>">
<input type="hidden" name="new_monitor[FrameSkip]" value="<?= $new_monitor[FrameSkip] ?>">
<input type="hidden" name="new_monitor[RunMode]" value="<?= $new_monitor[RunMode] ?>">
<?php
		if ( $new_monitor[Triggers] )
		{
			foreach( $new_monitor[Triggers] as $new_trigger )
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
<input type="hidden" name="new_monitor[Device]" value="<?= $new_monitor[Device] ?>">
<input type="hidden" name="new_monitor[Channel]" value="<?= $new_monitor[Channel] ?>">
<input type="hidden" name="new_monitor[Format]" value="<?= $new_monitor[Format] ?>">
<input type="hidden" name="new_monitor[Host]" value="<?= $new_monitor[Host] ?>">
<input type="hidden" name="new_monitor[Port]" value="<?= $new_monitor[Port] ?>">
<input type="hidden" name="new_monitor[Path]" value="<?= $new_monitor[Path] ?>">
<input type="hidden" name="new_monitor[Palette]" value="<?= $new_monitor[Palette] ?>">
<input type="hidden" name="new_monitor[Width]" value="<?= $new_monitor[Width] ?>">
<input type="hidden" name="new_monitor[Height]" value="<?= $new_monitor[Height] ?>">
<input type="hidden" name="new_monitor[Orientation]" value="<?= $new_monitor[Orientation] ?>">
<?php
	}
	if ( $tab != 'timestamp' )
	{
?>
<input type="hidden" name="new_monitor[LabelFormat]" value="<?= $new_monitor[LabelFormat] ?>">
<input type="hidden" name="new_monitor[LabelX]" value="<?= $new_monitor[LabelX] ?>">
<input type="hidden" name="new_monitor[LabelY]" value="<?= $new_monitor[LabelY] ?>">
<?php
	}
	if ( $tab != 'buffers' )
	{
?>
<input type="hidden" name="new_monitor[ImageBufferCount]" value="<?= $new_monitor[ImageBufferCount] ?>">
<input type="hidden" name="new_monitor[WarmupCount]" value="<?= $new_monitor[WarmupCount] ?>">
<input type="hidden" name="new_monitor[PreEventCount]" value="<?= $new_monitor[PreEventCount] ?>">
<input type="hidden" name="new_monitor[PostEventCount]" value="<?= $new_monitor[PostEventCount] ?>">
<?php
	}
	if ( $tab != 'misc' )
	{
?>
<input type="hidden" name="new_monitor[MaxFPS]" value="<?= $new_monitor[MaxFPS] ?>">
<input type="hidden" name="new_monitor[FPSReportInterval]" value="<?= $new_monitor[FPSReportInterval] ?>">
<input type="hidden" name="new_monitor[RefBlendPerc]" value="<?= $new_monitor[RefBlendPerc] ?>">
<?php
	}
	if ( ZM_OPT_X10 && $tab != 'x10' )
	{
?>
<input type="hidden" name="new_x10_monitor[Activation]" value="<?= $new_x10_monitor[Activation] ?>">
<input type="hidden" name="new_x10_monitor[AlarmInput]" value="<?= $new_x10_monitor[AlarmInput] ?>">
<input type="hidden" name="new_x10_monitor[AlarmOutput]" value="<?= $new_x10_monitor[AlarmOutput] ?>">
<?php
	}
?>
<tr>
<td align="left" class="smallhead" width="70%">Parameter</td><td align="left" class="smallhead" width="30%">Value</td>
</tr>
<?php
	switch ( $tab )
	{
		case 'monitor' :
		{
?>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_monitor[Name]" value="<?= $new_monitor[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Function</td><td align="left" class="text"><select name="new_monitor[Function]" class="form">
<?php
			foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
			{
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $new_monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
			}
?>
</select></td></tr>
<tr><td align="left" class="text">Section Length</td><td align="left" class="text"><input type="text" name="new_monitor[SectionLength]" value="<?= $new_monitor[SectionLength] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Frame Skip</td><td align="left" class="text"><input type="text" name="new_monitor[FrameSkip]" value="<?= $new_monitor[FrameSkip] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Run Mode</td><td align="left" class="text"><select name="new_monitor[RunMode]" class="form">
<?php
			foreach ( getEnumValues( 'Monitors', 'RunMode' ) as $opt_runmode )
			{
?>
<option value="<?= $opt_runmode ?>"<?php if ( $opt_runmode == $new_monitor['RunMode'] ) { ?> selected<?php } ?>><?= $opt_runmode ?></option>
<?php
			}
?>
</select></td></tr>
<tr><td align="left" class="text">Triggers</td><td align="left" class="text">
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
<input type="checkbox" name="new_monitor[Triggers][]" value="<?= $opt_trigger ?>" class="form-noborder"<?php if ( in_array( $opt_trigger, split( ",", $new_monitor['Triggers'] ) ) ) { ?> checked<?php } ?>><?= $opt_trigger ?>
<?php
				$opt_count ++;
			}
			if ( !$opt_count )
			{
?>
<em>None available</em>
<?php
			}
?>
</td></tr>
<?php
			$select_name = "new_monitor[Type]";
			$source_types = array( "Local"=>"Local", "Remote"=>"Remote" );
?>
<tr><td align="left" class="text">Source Type</td><td><?= buildSelect( $select_name, $source_types ); ?></td></tr>
<?php
			break;
		}
		case 'source' :
		{
			if ( $new_monitor[Type] == "Local" )
			{
?>
<tr><td align="left" class="text">Device Number (/dev/video?)</td><td align="left" class="text"><input type="text" name="new_monitor[Device]" value="<?= $new_monitor[Device] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Channel</td><td align="left" class="text"><input type="text" name="new_monitor[Channel]" value="<?= $new_monitor[Channel] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Format (0=PAL,1=NTSC etc)</td><td align="left" class="text"><input type="text" name="new_monitor[Format]" value="<?= $new_monitor[Format] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Capture Palette</td><td align="left" class="text"><select name="new_monitor[Palette]" class="form"><?php foreach ( $local_palettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor[Palette] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
			}
			else
			{
?>
<tr><td align="left" class="text">Remote Host Name</td><td align="left" class="text"><input type="text" name="new_monitor[Host]" value="<?= $new_monitor[Host] ?>" size="16" class="form"></td></tr>
<tr><td align="left" class="text">Remote Host Port</td><td align="left" class="text"><input type="text" name="new_monitor[Port]" value="<?= $new_monitor[Port] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Remote Host Path</td><td align="left" class="text"><input type="text" name="new_monitor[Path]" value="<?= $new_monitor[Path] ?>" size="36" class="form"></td></tr>
<tr><td align="left" class="text">Remote Image Colours</td><td align="left" class="text"><select name="new_monitor[Palette]" class="form"><?php foreach ( $remote_palettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor[Palette] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
			}
?>
<tr><td align="left" class="text">Capture Width (pixels)</td><td align="left" class="text"><input type="text" name="new_monitor[Width]" value="<?= $new_monitor[Width] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Capture Height (pixels)</td><td align="left" class="text"><input type="text" name="new_monitor[Height]" value="<?= $new_monitor[Height] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Orientation</td><td align="left" class="text"><select name="new_monitor[Orientation]" class="form"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $new_monitor[Orientation] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
			break;
		}
		case 'timestamp' :
		{
?>
<tr><td align="left" class="text">Timestamp Label Format</td><td align="left" class="text"><input type="text" name="new_monitor[LabelFormat]" value="<?= $new_monitor[LabelFormat] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">Timestamp Label X</td><td align="left" class="text"><input type="text" name="new_monitor[LabelX]" value="<?= $new_monitor[LabelX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Timestamp Label Y</td><td align="left" class="text"><input type="text" name="new_monitor[LabelY]" value="<?= $new_monitor[LabelY] ?>" size="4" class="form"></td></tr>
<?php
			break;
		}
		case 'buffers' :
		{
?>
<tr><td align="left" class="text">Image Buffer Size (frames)</td><td align="left" class="text"><input type="text" name="new_monitor[ImageBufferCount]" value="<?= $new_monitor[ImageBufferCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Warmup Frames</td><td align="left" class="text"><input type="text" name="new_monitor[WarmupCount]" value="<?= $new_monitor[WarmupCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Pre Event Image Buffer</td><td align="left" class="text"><input type="text" name="new_monitor[PreEventCount]" value="<?= $new_monitor[PreEventCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Post Event Image Buffer</td><td align="left" class="text"><input type="text" name="new_monitor[PostEventCount]" value="<?= $new_monitor[PostEventCount] ?>" size="4" class="form"></td></tr>
<?php
			break;
		}
		case 'misc' :
		{
?>
<tr><td align="left" class="text">Maximum FPS</td><td align="left" class="text"><input type="text" name="new_monitor[MaxFPS]" value="<?= $new_monitor[MaxFPS] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">FPS Report Interval</td><td align="left" class="text"><input type="text" name="new_monitor[FPSReportInterval]" value="<?= $new_monitor[FPSReportInterval] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Reference Image Blend %ge</td><td align="left" class="text"><input type="text" name="new_monitor[RefBlendPerc" value="<?= $new_monitor[RefBlendPerc] ?>" size="4" class="form"></td></tr>
<?php
			break;
		}
		case 'x10' :
		{
?>
<tr><td align="left" class="text">X10 Activation String</td><td align="left" class="text"><input type="text" name="new_x10_monitor[Activation]" value="<?= $new_x10_monitor[Activation] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">X10 Input Alarm String</td><td align="left" class="text"><input type="text" name="new_x10_monitor[AlarmInput]" value="<?= $new_x10_monitor[AlarmInput] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">X10 Output Alarm String</td><td align="left" class="text"><input type="text" name="new_x10_monitor[AlarmOutput]" value="<?= $new_x10_monitor[AlarmOutput] ?>" size="20" class="form"></td></tr>
<?php
			break;
		}
	}
?>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="Save" class="form"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
