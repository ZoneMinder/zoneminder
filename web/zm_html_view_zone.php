<?php
//
// ZoneMinder web zone view file, $Date$, $Revision$
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
	$zone['Name'] = $zmSlangNew;
	$zone['LoX'] = 0;
	$zone['LoY'] = 0;
	$zone['HiX'] = $monitor['Width']-1;
	$zone['HiY'] = $monitor['Height']-1;
}

?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangZone ?> <?= $zone['Name'] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
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
function validateForm( Form )
{
	var errors = new Array();
	Form.new_alarm_rgb.value = (Form.new_alarm_rgb_r.value<<16)|(Form.new_alarm_rgb_g.value<<8)|Form.new_alarm_rgb_b.value;
	if ( (parseInt(Form.new_min_pixel_threshold.value) >= parseInt(Form.new_max_pixel_threshold.value)) && (parseInt(Form.new_max_pixel_threshold.value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinPixelThresLtMax ?>";
	}
	if ( parseInt(Form.new_min_alarm_pixels.value) < parseInt(Form.new_min_filter_pixels.value) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmGeMinFilter ?>";
	}
	if ( (parseInt(Form.new_min_alarm_pixels.value) >= parseInt(Form.new_max_alarm_pixels.value)) && (parseInt(Form.new_max_alarm_pixels.value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmPixelsLtMax ?>";
	}
	if ( parseInt(Form.new_min_filter_pixels.value) < parseInt(Form.new_min_blob_pixels.value) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmGeMinBlob ?>";
	}
	if ( (parseInt(Form.new_min_filter_pixels.value) >= parseInt(Form.new_max_filter_pixels.value)) && (parseInt(Form.new_max_filter_pixels.value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinFilterPixelsLtMax ?>";
	}
	if ( (parseInt(Form.new_min_blob_pixels.value) >= parseInt(Form.new_max_blob_pixels.value)) && (parseInt(Form.new_max_blob_pixels.value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinBlobAreaLtMax ?>";
	}
	if ( (parseInt(Form.new_min_blobs.value) >= parseInt(Form.new_max_blobs.value)) && (parseInt(Form.new_max_blobs.value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinBlobsLtMax ?>";
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
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_b.disabled = true;
		Form.new_check_method.disabled = true;
		Form.new_min_pixel_threshold.disabled = true;
		Form.new_max_pixel_threshold.disabled = true;
		Form.new_min_alarm_pixels.disabled = true;
		Form.new_max_alarm_pixels.disabled = true;
		Form.new_filter_x.disabled = true;
		Form.new_filter_y.disabled = true;
		Form.new_min_filter_pixels.disabled = true;
		Form.new_max_filter_pixels.disabled = true;
		Form.new_min_blob_pixels.disabled = true;
		Form.new_max_blob_pixels.disabled = true;
		Form.new_min_blobs.disabled = true;
		Form.new_max_blobs.disabled = true;
	}
	else if ( Form.new_type.value == 'Preclusive' )
	{
		Form.new_alarm_rgb_r.disabled = true;
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_b.disabled = true;
		Form.new_check_method.disabled = false;
		Form.new_max_pixel_threshold.disabled = false;
		Form.new_min_pixel_threshold.disabled = false;
		Form.new_min_alarm_pixels.disabled = false;
		Form.new_max_alarm_pixels.disabled = false;
		Form.new_filter_x.disabled = false;
		Form.new_filter_y.disabled = false;
		Form.new_min_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.disabled = false;
		Form.new_min_blob_pixels.disabled = false;
		Form.new_max_blob_pixels.disabled = false;
		Form.new_min_blobs.disabled = false;
		Form.new_max_blobs.disabled = false;
	}
	else
	{
		Form.new_alarm_rgb_r.disabled = false;
		Form.new_alarm_rgb_g.disabled = false;
		Form.new_alarm_rgb_b.disabled = false;
		Form.new_check_method.disabled = false;
		Form.new_max_pixel_threshold.disabled = false;
		Form.new_min_pixel_threshold.disabled = false;
		Form.new_max_pixel_threshold.disabled = true;
		Form.new_min_pixel_threshold.disabled = true;
		Form.new_min_alarm_pixels.disabled = false;
		Form.new_max_alarm_pixels.disabled = false;
		Form.new_filter_x.disabled = false;
		Form.new_filter_y.disabled = false;
		Form.new_min_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.disabled = false;
		Form.new_min_blob_pixels.disabled = false;
		Form.new_max_blob_pixels.disabled = false;
		Form.new_min_blobs.disabled = false;
		Form.new_max_blobs.disabled = false;
	}
}

function applyCheckMethod( Form )
{
	if ( Form.new_check_method.value == 'AlarmedPixels' )
	{
		Form.new_filter_x.disabled = true;
		Form.new_filter_y.disabled = true;
		Form.new_min_filter_pixels.disabled = true;
		Form.new_max_filter_pixels.disabled = true;
		Form.new_min_blob_pixels.disabled = true;
		Form.new_max_blob_pixels.disabled = true;
		Form.new_min_blobs.disabled = true;
		Form.new_max_blobs.disabled = true;
	}
	else if ( Form.new_check_method.value == 'FilteredPixels' )
	{
		Form.new_filter_x.disabled = false;
		Form.new_filter_y.disabled = false;
		Form.new_min_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.disabled = false;
		Form.new_min_blob_pixels.disabled = true;
		Form.new_max_blob_pixels.disabled = true;
		Form.new_min_blobs.disabled = true;
		Form.new_max_blobs.disabled = true;
	}
	else
	{
		Form.new_filter_x.disabled = false;
		Form.new_filter_y.disabled = false;
		Form.new_min_filter_pixels.disabled = false;
		Form.new_max_filter_pixels.disabled = false;
		Form.new_min_blob_pixels.disabled = false;
		Form.new_max_blob_pixels.disabled = false;
		Form.new_min_blobs.disabled = false;
		Form.new_max_blobs.disabled = false;
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
	var max_width = <?= $monitor['Width']-1 ?>;
	var max_height = <?= $monitor['Height']-1 ?>;

	if ( Form.new_units.value == 'Pixels' )
	{
		toPixels( Form.new_lo_x, max_width );
		toPixels( Form.new_lo_y, max_height );
		toPixels( Form.new_hi_x, max_width );
		toPixels( Form.new_hi_y, max_height );
		var area = ((parseInt(Form.new_hi_x.value)-parseInt(Form.new_lo_x.value))+1) * ((parseInt(Form.new_hi_y.value)-parseInt(Form.new_lo_y.value))+1);
		toPixels( Form.new_min_alarm_pixels, area );
		toPixels( Form.new_max_alarm_pixels, area );
		toPixels( Form.new_min_filter_pixels, area );
		toPixels( Form.new_max_filter_pixels, area );
		toPixels( Form.new_min_blob_pixels, area );
		toPixels( Form.new_max_blob_pixels, area );
	}
	else
	{
		var area = ((parseInt(Form.new_hi_x.value)-parseInt(Form.new_lo_x.value))+1) * ((parseInt(Form.new_hi_y.value)-parseInt(Form.new_lo_y.value))+1);
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

function limitRange( Field, minValue, maxValue)
{
	if ( parseInt(Field.value) < parseInt(minValue) )
	{
		Field.value = minValue;
	}
	if ( parseInt(Field.value) > parseInt(maxValue) )
	{
		Field.value = maxValue;
	}
}

function limitFilter( Field )
{
	var minValue = 1;
	var maxValue = 15;

	Field.value = (Math.floor((Field.value-1)/2)*2) + 1;
	if ( parseInt(Field.value) < minValue )
	{
		Field.value = minValue;
	}
	if ( parseInt(Field.value) > maxValue )
	{
		Field.value = maxValue;
	}
}

function checkBounds( Field, fieldText, minValue, maxValue )
{
	if ( document.zone_form.new_units.value == "Percent" )
	{
		minValue = 0;
		maxValue = 100;
	}
	if ( parseInt(Field.value) < parseInt(minValue) )
	{
		alert( fieldText + " <?= $zmSlangMustBeGe ?> " + minValue );
		Field.value = minValue;
	}
	if ( parseInt(Field.value) > parseInt(maxValue) )
	{
		alert( fieldText + " <?= $zmSlangMustBeLe ?> " + maxValue );
		Field.value = maxValue;
	}
}

function checkWidth( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor['Width']-1 ?> ) );
}

function checkHeight( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor['Height']-1 ?> ) );
}

function checkArea( Field, fieldText )
{
	return( checkBounds( Field, fieldText, 0, <?= $monitor['Width']*$monitor['Height'] ?> ) );
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
<td colspan="2" align="left" class="head"><?= $zmSlangMonitor ?> <?= $monitor['Name'] ?> - <?= $zmSlangZone ?> <?= $zone['Name'] ?></td>
</tr>
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.zone_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="zone">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="zid" value="<?= $zid ?>">
<input type="hidden" name="new_alarm_rgb" value="">
<tr>
<td align="left" class="smallhead"><?= $zmSlangParameter ?></td><td align="left" class="smallhead"><?= $zmSlangValue ?></td>
</tr>
<tr><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_name" value="<?= $zone['Name'] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangType ?></td><td align="left" class="text"><select name="new_type" class="form" onchange="applyZoneType(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
{
?>
<option value="<?= $opt_type ?>"<?php if ( $opt_type == $zone['Type'] ) { ?> selected<?php } ?>><?= $opt_type ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangUnits ?></td><td align="left" class="text"><select name="new_units" class="form" onchange="applyZoneUnits(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
{
?>
<option value="<?= $opt_units ?>"<?php if ( $opt_units == $zone['Units'] ) { ?> selected<?php } ?>><?= $opt_units ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinX ?></td><td align="left" class="text"><input type="text" name="new_lo_x" value="<?= $zone['LoX'] ?>" size="4" class="form" onchange="checkWidth(this,'Minimum X')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinY ?></td><td align="left" class="text"><input type="text" name="new_lo_y" value="<?= $zone['LoY'] ?>" size="4" class="form" onchange="checkHeight(this,'Minimum Y')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxX ?></td><td align="left" class="text"><input type="text" name="new_hi_x" value="<?= $zone['HiX'] ?>" size="4" class="form" onchange="checkWidth(this,'Maximum X')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxY ?></td><td align="left" class="text"><input type="text" name="new_hi_y" value="<?= $zone['HiY'] ?>" size="4" class="form" onchange="checkHeight(this,'Maximum Y')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneAlarmColour ?></td><td align="left" class="text">R:<input type="text" name="new_alarm_rgb_r" value="<?= ($zone['AlarmRGB']>>16)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;G:<input type="text" name="new_alarm_rgb_g" value="<?= ($zone['AlarmRGB']>>8)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;B:<input type="text" name="new_alarm_rgb_b" value="<?= $zone['AlarmRGB']&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCheckMethod ?></td><td align="left" class="text"><select name="new_check_method" class="form" onchange="applyCheckMethod(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'CheckMethod' ) as $opt_check_method )
{
?>
<option value="<?= $opt_check_method ?>"<?php if ( $opt_check_method == $zone['CheckMethod'] ) { ?> selected<?php } ?>><?= $opt_check_method ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinPixelThres ?></td><td align="left" class="text"><input type="text" name="new_min_pixel_threshold" value="<?= $zone['MinPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxPixelThres ?></td><td align="left" class="text"><input type="text" name="new_max_pixel_threshold" value="<?= $zone['MaxPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinAlarmedArea ?></td><td align="left" class="text"><input type="text" name="new_min_alarm_pixels" value="<?= $zone['MinAlarmPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Alarmed Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxAlarmedArea ?></td><td align="left" class="text"><input type="text" name="new_max_alarm_pixels" value="<?= $zone['MaxAlarmPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Maximum Alarmed Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneFilterWidth ?></td><td align="left" class="text"><input type="text" name="new_filter_x" value="<?= $zone['FilterX'] ?>" size="4" class="form" onchange="limitFilter( this )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneFilterHeight ?></td><td align="left" class="text"><input type="text" name="new_filter_y" value="<?= $zone['FilterY'] ?>" size="4" class="form" onchange="limitFilter( this )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinFilteredArea ?></td><td align="left" class="text"><input type="text" name="new_min_filter_pixels" value="<?= $zone['MinFilterPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxFilteredArea ?></td><td align="left" class="text"><input type="text" name="new_max_filter_pixels" value="<?= $zone['MaxFilterPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinBlobArea ?></td><td align="left" class="text"><input type="text" name="new_min_blob_pixels" value="<?= $zone['MinBlobPixels'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxBlobArea ?></td><td align="left" class="text"><input type="text" name="new_max_blob_pixels" value="<?= $zone['MaxBlobPixels'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinBlobs ?></td><td align="left" class="text"><input type="text" name="new_min_blobs" value="<?= $zone['MinBlobs'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxBlobs ?></td><td align="left" class="text"><input type="text" name="new_max_blobs" value="<?= $zone['MaxBlobs'] ?>" size="4" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left">&nbsp;</td>
<td align="left"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
