<?php
	if ( !canView( 'Monitors' ) )
	{
		$view = "error";
		return;
	}
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );

	if ( $zid > 0 )
	{
		$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
		if ( !$result )
			die( mysql_error() );
		$zone = mysql_fetch_assoc( $result );
	}
	else
	{
		$zone = array();
		$zone[Name] = "New";
		$zone[LoX] = 0;
		$zone[LoY] = 0;
		$zone[HiX] = $monitor[Width]-1;
		$zone[HiY] = $monitor[Height]-1;
	}
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Zone <?= $zone[Name] ?></title>
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
function validateForm( Form )
{
	var errors = new Array();
	Form.new_alarm_rgb.value = (Form.new_alarm_rgb_r.value<<16)|(Form.new_alarm_rgb_g.value<<8)|Form.new_alarm_rgb_b.value;
	if ( Form.new_min_alarm_pixels.value < Form.new_min_filter_pixels.value )
	{
		errors[errors.length] = "Minimum alarm pixels should be greater than or equal to minimum filter pixels";
	}
	if ( Form.new_min_filter_pixels.value < Form.new_min_blob_pixels.value )
	{
		errors[errors.length] = "Minimum filter pixels should be greater than or equal to minimum blob pixels";
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function applyZoneType( Form )
{
	if ( Form.new_type.value == 'Inactive' )
	{
		Form.new_alarm_rgb_r.disabled = true;
		Form.new_alarm_rgb_r.value = "";
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_g.value = "";
		Form.new_alarm_rgb_b.disabled = true;
		Form.new_alarm_rgb_b.value = "";
		Form.new_alarm_threshold.disabled = true;
		Form.new_alarm_threshold.value = "";
		Form.new_min_alarm_pixels.disabled = true;
		Form.new_min_alarm_pixels.value = "";
		Form.new_max_alarm_pixels.disabled = true;
		Form.new_max_alarm_pixels.value = "";
		Form.new_filter_x.disabled = true;
		Form.new_filter_x.value = "";
		Form.new_filter_y.disabled = true;
		Form.new_filter_y.value = "";
		Form.new_min_filter_pixels.disabled = true;
		Form.new_min_filter_pixels.value = "";
		Form.new_max_filter_pixels.disabled = true;
		Form.new_max_filter_pixels.value = "";
		Form.new_min_blob_pixels.disabled = true;
		Form.new_min_blob_pixels.value = "";
		Form.new_max_blob_pixels.disabled = true;
		Form.new_max_blob_pixels.value = "";
		Form.new_min_blobs.disabled = true;
		Form.new_min_blobs.value = "";
		Form.new_max_blobs.disabled = true;
		Form.new_max_blobs.value = "";
	}
	else if ( Form.new_type.value == 'Preclusive' )
	{
		Form.new_alarm_rgb_r.disabled = true;
		Form.new_alarm_rgb_r.value = "";
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_g.value = "";
		Form.new_alarm_rgb_b.disabled = true;
		Form.new_alarm_rgb_b.value = "";
		Form.new_alarm_threshold.disabled = false;
		Form.new_alarm_threshold.value = "<?= $zone[AlarmThreshold] ?>";
		Form.new_min_alarm_pixels.disabled = false;
		Form.new_min_alarm_pixels.value = "<?= $zone[MinAlarmPixels] ?>";
		Form.new_max_alarm_pixels.disabled = false;
		Form.new_max_alarm_pixels.value = "<?= $zone[MaxAlarmPixels] ?>";
		Form.new_filter_x.disabled = false;
		Form.new_filter_x.value = "<?= $zone[FilterX] ?>";
		Form.new_filter_y.disabled = false;
		Form.new_filter_y.value = "<?= $zone[FilterY] ?>";
		Form.new_min_filter_pixels.disabled = false;
		Form.new_min_filter_pixels.value = "<?= $zone[MinFilterPixels] ?>";
		Form.new_max_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.value = "<?= $zone[MaxFilterPixels] ?>";
		Form.new_min_blob_pixels.disabled = false;
		Form.new_min_blob_pixels.value = "<?= $zone[MinBlobPixels] ?>";
		Form.new_max_blob_pixels.disabled = false;
		Form.new_max_blob_pixels.value = "<?= $zone[MaxBlobPixels] ?>";
		Form.new_min_blobs.disabled = false;
		Form.new_min_blobs.value = "<?= $zone[MinBlobs] ?>";
		Form.new_max_blobs.disabled = false;
		Form.new_max_blobs.value = "<?= $zone[MaxBlobs] ?>";
	}
	else
	{
		Form.new_alarm_rgb_r.disabled = false;
		Form.new_alarm_rgb_r.value = "<?= ($zone[AlarmRGB]>>16)&0xff; ?>";
		Form.new_alarm_rgb_g.disabled = false;
		Form.new_alarm_rgb_g.value = "<?= ($zone[AlarmRGB]>>8)&0xff; ?>";
		Form.new_alarm_rgb_b.disabled = false;
		Form.new_alarm_rgb_b.value = "<?= $zone[AlarmRGB]&0xff; ?>";
		Form.new_alarm_threshold.disabled = false;
		Form.new_alarm_threshold.value = "<?= $zone[AlarmThreshold] ?>";
		Form.new_min_alarm_pixels.disabled = false;
		Form.new_min_alarm_pixels.value = "<?= $zone[MinAlarmPixels] ?>";
		Form.new_max_alarm_pixels.disabled = false;
		Form.new_max_alarm_pixels.value = "<?= $zone[MaxAlarmPixels] ?>";
		Form.new_filter_x.disabled = false;
		Form.new_filter_x.value = "<?= $zone[FilterX] ?>";
		Form.new_filter_y.disabled = false;
		Form.new_filter_y.value = "<?= $zone[FilterY] ?>";
		Form.new_min_filter_pixels.disabled = false;
		Form.new_min_filter_pixels.value = "<?= $zone[MinFilterPixels] ?>";
		Form.new_max_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.value = "<?= $zone[MaxFilterPixels] ?>";
		Form.new_min_blob_pixels.disabled = false;
		Form.new_min_blob_pixels.value = "<?= $zone[MinBlobPixels] ?>";
		Form.new_max_blob_pixels.disabled = false;
		Form.new_max_blob_pixels.value = "<?= $zone[MaxBlobPixels] ?>";
		Form.new_min_blobs.disabled = false;
		Form.new_min_blobs.value = "<?= $zone[MinBlobs] ?>";
		Form.new_max_blobs.disabled = false;
		Form.new_max_blobs.value = "<?= $zone[MaxBlobs] ?>";
	}
}

function toPixels( Field, maxValue )
{
		Field.value = Math.round((Field.value*maxValue)/100);
}

function toPercent( Field, maxValue )
{
		Field.value = Math.round((100*Field.value)/maxValue);
}

function applyZoneUnits( Form )
{
	var max_width = <?= $monitor[Width]-1 ?>;
	var max_height = <?= $monitor[Height]-1 ?>;
	var area = (max_width+1) * (max_height+1);

	if ( Form.new_units.value == 'Pixels' )
	{
		toPixels( Form.new_lo_x, max_width );
		toPixels( Form.new_lo_y, max_height );
		toPixels( Form.new_hi_x, max_width );
		toPixels( Form.new_hi_y, max_height );
		toPixels( Form.new_min_alarm_pixels, area );
		toPixels( Form.new_max_alarm_pixels, area );
		toPixels( Form.new_min_filter_pixels, area );
		toPixels( Form.new_max_filter_pixels, area );
		toPixels( Form.new_min_blob_pixels, area );
		toPixels( Form.new_max_blob_pixels, area );
	}
	else
	{
		toPercent( Form.new_lo_x, max_width );
		toPercent( Form.new_lo_y, max_height );
		toPercent( Form.new_hi_x, max_width );
		toPercent( Form.new_hi_y, max_height );
		toPercent( Form.new_min_alarm_pixels, area );
		toPercent( Form.new_max_alarm_pixels, area );
		toPercent( Form.new_min_filter_pixels, area );
		toPercent( Form.new_max_filter_pixels, area );
		toPercent( Form.new_min_blob_pixels, area );
		toPercent( Form.new_max_blob_pixels, area );
	}
}

function checkBounds( Field, fieldText, minValue, maxValue)
{
	if ( document.zone_form.new_units.value == "Percent" )
	{
		minValue = 0;
		maxValue = 100;
	}
	if ( Field.value < minValue )
	{
		alert( fieldText + " must be greater than or equal to " + minValue );
		Field.value = minValue;
	}
	if ( Field.value > maxValue )
	{
		alert( fieldText + " must be less than or equal to " + maxValue );
		Field.value = maxValue;
	}
}

function checkWidth( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor[Width]-1 ?> ) );
}

function checkHeight( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor[Height]-1 ?> ) );
}

function checkArea( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor[Width]*$monitor[Height] ?> ) );
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
<td colspan="2" align="left" class="head">Monitor <?= $monitor[Name] ?> - Zone <?= $zone[Name] ?></td>
</tr>
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.zone_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="zone">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="zid" value="<?= $zid ?>">
<input type="hidden" name="new_alarm_rgb" value="">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?= $zone[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Type</td><td align="left" class="text"><select name="new_type" class="form" onchange="applyZoneType(document.zone_form)">
<?php
	foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
	{
?>
<option value="<?= $opt_type ?>"<?php if ( $opt_type == $zone['Type'] ) { ?> selected<?php } ?>><?= $opt_type ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Units</td><td align="left" class="text"><select name="new_units" class="form" onchange="applyZoneUnits(document.zone_form)">
<?php
	foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
	{
?>
<option value="<?= $opt_units ?>"<?php if ( $opt_units == $zone['Units'] ) { ?> selected<?php } ?>><?= $opt_units ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Minimum X (left)</td><td align="left" class="text"><input type="text" name="new_lo_x" value="<?= $zone[LoX] ?>" size="4" class="form" onchange="checkWidth(this,'Minimum X')"></td></tr>
<tr><td align="left" class="text">Minimum Y (top)</td><td align="left" class="text"><input type="text" name="new_lo_y" value="<?= $zone[LoY] ?>" size="4" class="form" onchange="checkHeight(this,'Minimum Y')"></td></tr>
<tr><td align="left" class="text">Maximum X (right)</td><td align="left" class="text"><input type="text" name="new_hi_x" value="<?= $zone[HiX] ?>" size="4" class="form" onchange="checkWidth(this,'Maximum X')"></td></tr>
<tr><td align="left" class="text">Maximum Y (bottom)</td><td align="left" class="text"><input type="text" name="new_hi_y" value="<?= $zone[HiY] ?>" size="4" class="form" onchange="checkHeight(this,'Maximum Y')"></td></tr>
<tr><td align="left" class="text">Alarm Colour (RGB)</td><td align="left" class="text">R:<input type="text" name="new_alarm_rgb_r" value="<?= ($zone[AlarmRGB]>>16)&0xff ?>" size="3" class="form">&nbsp;G:<input type="text" name="new_alarm_rgb_g" value="<?= ($zone[AlarmRGB]>>8)&0xff ?>" size="3" class="form">&nbsp;B:<input type="text" name="new_alarm_rgb_b" value="<?= $zone[AlarmRGB]&0xff ?>" size="3" class="form"></td></tr>
<tr><td align="left" class="text">Alarm Threshold (0>=?<=255)</td><td align="left" class="text"><input type="text" name="new_alarm_threshold" value="<?= $zone[AlarmThreshold] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_min_alarm_pixels" value="<?= $zone[MinAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Maximum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_max_alarm_pixels" value="<?= $zone[MaxAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Maximum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Filter Width (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_x" value="<?= $zone[FilterX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Filter Height (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_y" value="<?= $zone[FilterY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Filtered Area</td><td align="left" class="text"><input type="text" name="new_min_filter_pixels" value="<?= $zone[MinFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text">Maximum Filtered Area</td><td align="left" class="text"><input type="text" name="new_max_filter_pixels" value="<?= $zone[MaxFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text">Minimum Blob Area</td><td align="left" class="text"><input type="text" name="new_min_blob_pixels" value="<?= $zone[MinBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blob Area</td><td align="left" class="text"><input type="text" name="new_max_blob_pixels" value="<?= $zone[MaxBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Blobs</td><td align="left" class="text"><input type="text" name="new_min_blobs" value="<?= $zone[MinBlobs] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blobs</td><td align="left" class="text"><input type="text" name="new_max_blobs" value="<?= $zone[MaxBlobs] ?>" size="4" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left">&nbsp;</td>
<td align="left"><input type="submit" value="Save" class="form"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
