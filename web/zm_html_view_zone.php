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
$new_zone = $zone;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangZone ?> <?= $zone['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
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
	Form.elements['new_zone[AlarmRGB]'].value = (Form.new_alarm_rgb_r.value<<16)|(Form.new_alarm_rgb_g.value<<8)|Form.new_alarm_rgb_b.value;
	if ( (parseInt(Form.elements['new_zone[MinPixelThreshold]'].value) >= parseInt(Form.elements['new_zone[MaxPixelThreshold]'].value)) && (parseInt(Form.elements['new_zone[MaxPixelThreshold]'].value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinPixelThresLtMax ?>";
	}
	if ( parseInt(Form.elements['new_zone[MinAlarmPixels]'].value) < parseInt(Form.elements['new_zone[MinFilterPixels]'].value) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmGeMinFilter ?>";
	}
	if ( (parseInt(Form.elements['new_zone[MinAlarmPixels]'].value) >= parseInt(Form.elements['new_zone[MaxAlarmPixels]'].value)) && (parseInt(Form.elements['new_zone[MaxAlarmPixels]'].value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmPixelsLtMax ?>";
	}
	if ( parseInt(Form.elements['new_zone[MinFilterPixels]'].value) < parseInt(Form.elements['new_zone[MinBlobPixels]'].value) )
	{
		errors[errors.length] = "<?= $zmSlangMinAlarmGeMinBlob ?>";
	}
	if ( (parseInt(Form.elements['new_zone[MinFilterPixels]'].value) >= parseInt(Form.elements['new_zone[MaxFilterPixels]'].value)) && (parseInt(Form.elements['new_zone[MaxFilterPixels]'].value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinFilterPixelsLtMax ?>";
	}
	if ( (parseInt(Form.elements['new_zone[MinBlobPixels]'].value) >= parseInt(Form.elements['new_zone[MaxBlobPixels]'].value)) && (parseInt(Form.elements['new_zone[MaxBlobPixels]'].value) > 0) )
	{
		errors[errors.length] = "<?= $zmSlangMinBlobAreaLtMax ?>";
	}
	if ( (parseInt(Form.elements['new_zone[MinBlobs]'].value) >= parseInt(Form.elements['new_zone[MaxBlobs]'].value)) && (parseInt(Form.elements['new_zone[MaxBlobs]'].value) > 0) )
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
	if ( Form.elements['new_zone[Type]'].value == 'Inactive' )
	{
		Form.new_alarm_rgb_r.disabled = true;
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_b.disabled = true;
		Form.elements['new_zone[CheckMethod]'].disabled = true;
		Form.elements['new_zone[MinPixelThreshold]'].disabled = true;
		Form.elements['new_zone[MaxPixelThreshold]'].disabled = true;
		Form.elements['new_zone[MinAlarmPixels]'].disabled = true;
		Form.elements['new_zone[MaxAlarmPixels]'].disabled = true;
		Form.elements['new_zone[FilterX]'].disabled = true;
		Form.elements['new_zone[FilterY]'].disabled = true;
		Form.elements['new_zone[MinFilterPixels]'].disabled = true;
		Form.elements['new_zone[MaxFilterPixels]'].disabled = true;
		Form.elements['new_zone[MinBlobPixels]'].disabled = true;
		Form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		Form.elements['new_zone[MinBlobs]'].disabled = true;
		Form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else if ( Form.elements['new_zone[Type]'].value == 'Preclusive' )
	{
		Form.new_alarm_rgb_r.disabled = true;
		Form.new_alarm_rgb_g.disabled = true;
		Form.new_alarm_rgb_b.disabled = true;
		Form.elements['new_zone[CheckMethod]'].disabled = false;
		Form.elements['new_zone[MinPixelThreshold]'].disabled = false;
		Form.elements['new_zone[MaxPixelThreshold]'].disabled = false;
		Form.elements['new_zone[MinAlarmPixels]'].disabled = false;
		Form.elements['new_zone[MaxAlarmPixels]'].disabled = false;
		applyCheckMethod( Form );
	}
	else
	{
		Form.new_alarm_rgb_r.disabled = false;
		Form.new_alarm_rgb_g.disabled = false;
		Form.new_alarm_rgb_b.disabled = false;
		Form.elements['new_zone[CheckMethod]'].disabled = false;
		Form.elements['new_zone[MinPixelThreshold]'].disabled = false;
		Form.elements['new_zone[MaxPixelThreshold]'].disabled = false;
		Form.elements['new_zone[MinAlarmPixels]'].disabled = false;
		Form.elements['new_zone[MaxAlarmPixels]'].disabled = false;
		applyCheckMethod( Form );
	}
}

function applyCheckMethod( Form )
{
	if ( Form.elements['new_zone[CheckMethod]'].value == 'AlarmedPixels' )
	{
		Form.elements['new_zone[FilterX]'].disabled = true;
		Form.elements['new_zone[FilterY]'].disabled = true;
		Form.elements['new_zone[MinFilterPixels]'].disabled = true;
		Form.elements['new_zone[MaxFilterPixels]'].disabled = true;
		Form.elements['new_zone[MinBlobPixels]'].disabled = true;
		Form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		Form.elements['new_zone[MinBlobs]'].disabled = true;
		Form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else if ( Form.elements['new_zone[CheckMethod]'].value == 'FilteredPixels' )
	{
		Form.elements['new_zone[FilterX]'].disabled = false;
		Form.elements['new_zone[FilterY]'].disabled = false;
		Form.elements['new_zone[MinFilterPixels]'].disabled = false;
		Form.elements['new_zone[MaxFilterPixels]'].disabled = false;
		Form.elements['new_zone[MinBlobPixels]'].disabled = true;
		Form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		Form.elements['new_zone[MinBlobs]'].disabled = true;
		Form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else
	{
		Form.elements['new_zone[FilterX]'].disabled = false;
		Form.elements['new_zone[FilterY]'].disabled = false;
		Form.elements['new_zone[MinFilterPixels]'].disabled = false;
		Form.elements['new_zone[MaxFilterPixels]'].disabled = false;
		Form.elements['new_zone[MinBlobPixels]'].disabled = false;
		Form.elements['new_zone[MaxBlobPixels]'].disabled = false;
		Form.elements['new_zone[MinBlobs]'].disabled = false;
		Form.elements['new_zone[MaxBlobs]'].disabled = false;
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

	if ( Form.elements['new_zone[Units]'].value == 'Pixels' )
	{
		toPixels( Form.elements['new_zone[LoX]'], max_width );
		toPixels( Form.elements['new_zone[LoY]'], max_height );
		toPixels( Form.elements['new_zone[HiX]'], max_width );
		toPixels( Form.elements['new_zone[HiY]'], max_height );
		var area = ((parseInt(Form.elements['new_zone[HiX]'].value)-parseInt(Form.elements['new_zone[LoX]'].value))+1) * ((parseInt(Form.elements['new_zone[HiY]'].value)-parseInt(Form.elements['new_zone[LoY]'].value))+1);
		toPixels( Form.elements['new_zone[MinAlarmPixels]'], area );
		toPixels( Form.elements['new_zone[MaxAlarmPixels]'], area );
		toPixels( Form.elements['new_zone[MinFilterPixels]'], area );
		toPixels( Form.elements['new_zone[MaxFilterPixels]'], area );
		toPixels( Form.elements['new_zone[MinBlobPixels]'], area );
		toPixels( Form.elements['new_zone[MaxBlobPixels]'], area );
	}
	else
	{
		var area = ((parseInt(Form.elements['new_zone[HiX]'].value)-parseInt(Form.elements['new_zone[LoX]'].value))+1) * ((parseInt(Form.elements['new_zone[HiY]'].value)-parseInt(Form.elements['new_zone[LoY]'].value))+1);
		toPercent( Form.elements['new_zone[LoX]'], max_width );
		toPercent( Form.elements['new_zone[LoY]'], max_height );
		toPercent( Form.elements['new_zone[HiX]'], max_width );
		toPercent( Form.elements['new_zone[HiY]'], max_height );
		toPercent( Form.elements['new_zone[MinAlarmPixels]'], area );
		toPercent( Form.elements['new_zone[MaxAlarmPixels]'], area );
		toPercent( Form.elements['new_zone[MinFilterPixels]'], area );
		toPercent( Form.elements['new_zone[MaxFilterPixels]'], area );
		toPercent( Form.elements['new_zone[MinBlobPixels]'], area );
		toPercent( Form.elements['new_zone[MaxBlobPixels]'], area );
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
	if ( document.zone_form.elements['new_zone[Units]'].value == "Percent" )
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
<input type="hidden" name="new_zone[AlarmRGB]" value="">
<tr>
<td align="left" class="smallhead"><?= $zmSlangParameter ?></td><td align="left" class="smallhead"><?= $zmSlangValue ?></td>
</tr>
<tr><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_zone[Name]" value="<?= $new_zone['Name'] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangType ?></td><td align="left" class="text"><select name="new_zone[Type]" class="form" onchange="applyZoneType(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
{
?>
<option value="<?= $opt_type ?>"<?php if ( $opt_type == $new_zone['Type'] ) { ?> selected<?php } ?>><?= $opt_type ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangUnits ?></td><td align="left" class="text"><select name="new_zone[Units]" class="form" onchange="applyZoneUnits(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
{
?>
<option value="<?= $opt_units ?>"<?php if ( $opt_units == $new_zone['Units'] ) { ?> selected<?php } ?>><?= $opt_units ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinX ?></td><td align="left" class="text"><input type="text" name="new_zone[LoX]" value="<?= $new_zone['LoX'] ?>" size="4" class="form" onchange="checkWidth(this,'Minimum X')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinY ?></td><td align="left" class="text"><input type="text" name="new_zone[LoY]" value="<?= $new_zone['LoY'] ?>" size="4" class="form" onchange="checkHeight(this,'Minimum Y')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxX ?></td><td align="left" class="text"><input type="text" name="new_zone[HiX]" value="<?= $new_zone['HiX'] ?>" size="4" class="form" onchange="checkWidth(this,'Maximum X')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxY ?></td><td align="left" class="text"><input type="text" name="new_zone[HiY]" value="<?= $new_zone['HiY'] ?>" size="4" class="form" onchange="checkHeight(this,'Maximum Y')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneAlarmColour ?></td><td align="left" class="text">R:<input type="text" name="new_alarm_rgb_r" value="<?= ($new_zone['AlarmRGB']>>16)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;G:<input type="text" name="new_alarm_rgb_g" value="<?= ($new_zone['AlarmRGB']>>8)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;B:<input type="text" name="new_alarm_rgb_b" value="<?= $new_zone['AlarmRGB']&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCheckMethod ?></td><td align="left" class="text"><select name="new_zone[CheckMethod]" class="form" onchange="applyCheckMethod(document.zone_form)">
<?php
foreach ( getEnumValues( 'Zones', 'CheckMethod' ) as $opt_check_method )
{
?>
<option value="<?= $opt_check_method ?>"<?php if ( $opt_check_method == $new_zone['CheckMethod'] ) { ?> selected<?php } ?>><?= $opt_check_method ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinPixelThres ?></td><td align="left" class="text"><input type="text" name="new_zone[MinPixelThreshold]" value="<?= $new_zone['MinPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxPixelThres ?></td><td align="left" class="text"><input type="text" name="new_zone[MaxPixelThreshold]" value="<?= $new_zone['MaxPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinAlarmedArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinAlarmPixels]" value="<?= $new_zone['MinAlarmPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Alarmed Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxAlarmedArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MaxAlarmPixels]" value="<?= $new_zone['MaxAlarmPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Maximum Alarmed Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneFilterWidth ?></td><td align="left" class="text"><input type="text" name="new_zone[FilterX]" value="<?= $new_zone['FilterX'] ?>" size="4" class="form" onchange="limitFilter( this )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneFilterHeight ?></td><td align="left" class="text"><input type="text" name="new_zone[FilterY]" value="<?= $new_zone['FilterY'] ?>" size="4" class="form" onchange="limitFilter( this )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinFilteredArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinFilterPixels]" value="<?= $new_zone['MinFilterPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxFilteredArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MaxFilterPixels]" value="<?= $new_zone['MaxFilterPixels'] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinBlobArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinBlobPixels]" value="<?= $new_zone['MinBlobPixels'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxBlobArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MaxBlobPixels]" value="<?= $new_zone['MaxBlobPixels'] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinBlobs ?></td><td align="left" class="text"><input type="text" name="new_zone[MinBlobs]" value="<?= $new_zone['MinBlobs'] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMaxBlobs ?></td><td align="left" class="text"><input type="text" name="new_zone[MaxBlobs]" value="<?= $new_zone['MaxBlobs'] ?>" size="4" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left">&nbsp;</td>
<td align="left"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
<script type="text/javascript">
applyZoneType(document.zone_form);
applyCheckMethod(document.zone_form);
</script>
</body>
</html>
