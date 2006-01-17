<?php
//
// ZoneMinder web zone view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

//phpinfo( INFO_VARIABLES );
//error_reporting( E_ALL );
$scale = SCALE_SCALE;

$hicolor = "0x00ff00"; // Green

$marker = array(
	"src"=>"graphics/point-g.gif",
	"hisrc"=>"graphics/point-o.gif",
	"actsrc"=>"graphics/point-r.gif",
	"width"=>7,
	"height"=>7,
);

$result = mysql_query( "select *, Units-1 as UnitsIndex, CheckMethod-1 as CheckMethodIndex from ZonePresets order by Id asc" );
if ( !$result )
	die( mysql_error() );
$presets = array();
$preset_names = array();
$preset_names[0] = $zmSlangChoosePreset;
while ( $preset = mysql_fetch_assoc( $result ) )
{
	$preset_names[$preset['Id']] = $preset['Name'];
	$presets[] = $preset;
}
mysql_free_result( $result );

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );

$min_x = 0;
$max_x = $monitor['Width']-1;
$min_y = 0;
$max_y = $monitor['Height']-1;

if ( !isset($new_zone) )
{
	if ( $zid > 0 )
	{
		$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
		if ( !$result )
			die( mysql_error() );
		$zone = mysql_fetch_assoc( $result );
		mysql_free_result( $result );
	}
	else
	{
		$zone = array();
		$zone['Name'] = $zmSlangNew;
		$zone['CheckMethod'] = 'Blobs';
		$zone['AlarmRGB'] = 0xff0000;
		$zone['NumCoords'] = 4;
		$zone['Coords'] = sprintf( "%d,%d %d,%d, %d,%d %d,%d", $min_x, $min_y, $max_x, $min_y, $max_x, $max_y, $min_x, $max_y );
		$zone['Area'] = $monitor['Width'] * $monitor['Height'];
	}
	$zone['Points'] = coordsToPoints( $zone['Coords'] );

	$new_zone = $zone;
}

//if ( !$points )
//{
	//$points = $zone['Points'];
//}

ksort( $new_zone['Points'], SORT_NUMERIC );

if ( isset($action) )
{
	if ( $action == "loc_addpoint" )
	{
		if ( $subaction < (count($new_zone['Points'])-1) )
		{
			$new_x = intval(round(($new_zone['Points'][$subaction]['x']+$new_zone['Points'][$subaction+1]['x'])/2));
			$new_y = intval(round(($new_zone['Points'][$subaction]['y']+$new_zone['Points'][$subaction+1]['y'])/2));
		}
		else
		{
			$new_x = intval(round(($new_zone['Points'][$subaction]['x']+$new_zone['Points'][0]['x'])/2));
			$new_y = intval(round(($new_zone['Points'][$subaction]['y']+$new_zone['Points'][0]['y'])/2));
		}
		array_splice( $new_zone['Points'], $subaction+1, 0, array( array( 'x'=>$new_x, 'y'=>$new_y ) ) );
	}
	elseif ( $action == "loc_delpoint" )
	{
		array_splice( $new_zone['Points'], $subaction, 1 );
	}
}

$new_zone['Coords'] = pointsToCoords( $new_zone['Points'] );
$new_zone['Area'] = getPolyArea( $new_zone['Points'] );
$self_intersecting = isSelfIntersecting( $new_zone['Points'] );

chdir( ZM_DIR_IMAGES );
$command = getZmuCommand( " -m $mid -z" );
if ( !$zid )
	$zid = 0;
$command .= "\"$zid $hicolor ".$new_zone['Coords']."\"";
$status = exec( escapeshellcmd( $command ) );
chdir( '..' );

$zone_image = ZM_DIR_IMAGES.'/'.$monitor['Name']."-Zones.jpg?".time();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangZone ?> <?= $new_zone['Name'] ?></title>
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

function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}

function closeWindow()
{
	window.close();
}

var active = -1;
var self_intersecting = <?= $self_intersecting?'true':'false' ?>;

function validateForm()
{
	var form = document.zone_form;
	var errors = new Array();

	if ( self_intersecting )
	{
		errors[errors.length] = "<?= $zmSlangSelfIntersecting ?>";
	}
	if ( form.elements['new_zone[Type]'].value != 'Inactive' )
	{
		if ( !form.new_alarm_rgb_r.value || !form.new_alarm_rgb_g.value || !form.new_alarm_rgb_b.value )
		{
			errors[errors.length] = "<?= $zmSlangAlarmRGBUnset ?>";
		}
		form.elements['new_zone[AlarmRGB]'].value = (form.new_alarm_rgb_r.value<<16)|(form.new_alarm_rgb_g.value<<8)|form.new_alarm_rgb_b.value;
		if ( !form.elements['new_zone[MinPixelThreshold]'].value || (parseInt(form.elements['new_zone[MinPixelThreshold]'].value) <= 0 ) )
		{
			errors[errors.length] = "<?= $zmSlangMinPixelThresUnset ?>";
		}
		else if ( (parseInt(form.elements['new_zone[MinPixelThreshold]'].value) >= parseInt(form.elements['new_zone[MaxPixelThreshold]'].value)) && (parseInt(form.elements['new_zone[MaxPixelThreshold]'].value) > 0) )
		{
			errors[errors.length] = "<?= $zmSlangMinPixelThresLtMax ?>";
		}
		if ( form.elements['new_zone[CheckMethod]'].value == 'FilteredPixels' || form.elements['new_zone[CheckMethod]'].value == 'Blobs' )
		{
			if ( !form.elements['new_zone[FilterX]'].value || !form.elements['new_zone[FilterY]'].value )
			{
				errors[errors.length] = "<?= $zmSlangFilterUnset ?>";
			}
		}
		if ( !form.elements['new_zone[MinAlarmPixels]'].value || (parseFloat(form.elements['new_zone[MinAlarmPixels]'].value) <= 0 ) )
		{
			errors[errors.length] = "<?= $zmSlangMinAlarmAreaUnset ?>";
		}
		else if ( (parseFloat(form.elements['new_zone[MinAlarmPixels]'].value) >= parseFloat(form.elements['new_zone[MaxAlarmPixels]'].value)) && (parseFloat(form.elements['new_zone[MaxAlarmPixels]'].value) > 0) )
		{
			errors[errors.length] = "<?= $zmSlangMinAlarmAreaLtMax ?>";
		}
		if ( form.elements['new_zone[CheckMethod]'].value == 'FilteredPixels' || form.elements['new_zone[CheckMethod]'].value == 'Blobs' )
		{
			if ( !form.elements['new_zone[MinFilterPixels]'].value || (parseFloat(form.elements['new_zone[MinFilterPixels]'].value) <= 0 ) )
			{
				errors[errors.length] = "<?= $zmSlangMinFilterAreaUnset ?>";
			}
			else if ( (parseFloat(form.elements['new_zone[MinFilterPixels]'].value) >= parseFloat(form.elements['new_zone[MaxFilterPixels]'].value)) && (parseFloat(form.elements['new_zone[MaxFilterPixels]'].value) > 0) )
			{
				errors[errors.length] = "<?= $zmSlangMinFilterAreaLtMax ?>";
			}
			else if ( parseFloat(form.elements['new_zone[MinAlarmPixels]'].value) < parseFloat(form.elements['new_zone[MinFilterPixels]'].value) )
			{
				errors[errors.length] = "<?= $zmSlangMinFilterLtMinAlarm ?>";
			}
			if ( form.elements['new_zone[CheckMethod]'].value == 'Blobs' )
			{
				if ( !form.elements['new_zone[MinBlobPixels]'].value || (parseFloat(form.elements['new_zone[MinBlobPixels]'].value) <= 0 ) )
				{
					errors[errors.length] = "<?= $zmSlangMinBlobAreaUnset ?>";
				}
				else if ( (parseFloat(form.elements['new_zone[MinBlobPixels]'].value) >= parseFloat(form.elements['new_zone[MaxBlobPixels]'].value)) && (parseFloat(form.elements['new_zone[MaxBlobPixels]'].value) > 0) )
				{
					errors[errors.length] = "<?= $zmSlangMinBlobAreaLtMax ?>";
				}
				else if ( parseFloat(form.elements['new_zone[MinFilterPixels]'].value) < parseFloat(form.elements['new_zone[MinBlobPixels]'].value) )
				{
					errors[errors.length] = "<?= $zmSlangMinBlobLtMinFilter ?>";
				}
				if ( !form.elements['new_zone[MinBlobs]'].value || (parseInt(form.elements['new_zone[MinBlobs]'].value) <= 0 ) )
				{
					errors[errors.length] = "<?= $zmSlangMinBlobsUnset ?>";
				}
				else if ( (parseInt(form.elements['new_zone[MinBlobs]'].value) >= parseInt(form.elements['new_zone[MaxBlobs]'].value)) && (parseInt(form.elements['new_zone[MaxBlobs]'].value) > 0) )
				{
					errors[errors.length] = "<?= $zmSlangMinBlobsLtMax ?>";
				}
			}
		}
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function submitForm()
{
	var form = document.zone_form;

	form.elements['new_zone[AlarmRGB]'].value = (form.new_alarm_rgb_r.value<<16)|(form.new_alarm_rgb_g.value<<8)|form.new_alarm_rgb_b.value;

	form.submit();
}

function applyZoneType()
{
	var form = document.zone_form;
	if ( form.elements['new_zone[Type]'].value == 'Inactive' )
	{
		form.presetSelector.disabled = true;
		form.new_alarm_rgb_r.disabled = true;
		form.new_alarm_rgb_g.disabled = true;
		form.new_alarm_rgb_b.disabled = true;
		form.elements['new_zone[CheckMethod]'].disabled = true;
		form.elements['new_zone[MinPixelThreshold]'].disabled = true;
		form.elements['new_zone[MaxPixelThreshold]'].disabled = true;
		form.elements['new_zone[MinAlarmPixels]'].disabled = true;
		form.elements['new_zone[MaxAlarmPixels]'].disabled = true;
		form.elements['new_zone[FilterX]'].disabled = true;
		form.elements['new_zone[FilterY]'].disabled = true;
		form.elements['new_zone[MinFilterPixels]'].disabled = true;
		form.elements['new_zone[MaxFilterPixels]'].disabled = true;
		form.elements['new_zone[MinBlobPixels]'].disabled = true;
		form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		form.elements['new_zone[MinBlobs]'].disabled = true;
		form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else if ( form.elements['new_zone[Type]'].value == 'Preclusive' )
	{
		form.presetSelector.disabled = false;
		form.new_alarm_rgb_r.disabled = true;
		form.new_alarm_rgb_g.disabled = true;
		form.new_alarm_rgb_b.disabled = true;
		form.elements['new_zone[CheckMethod]'].disabled = false;
		form.elements['new_zone[MinPixelThreshold]'].disabled = false;
		form.elements['new_zone[MaxPixelThreshold]'].disabled = false;
		form.elements['new_zone[MinAlarmPixels]'].disabled = false;
		form.elements['new_zone[MaxAlarmPixels]'].disabled = false;
		applyCheckMethod();
	}
	else
	{
		form.presetSelector.disabled = false;
		form.new_alarm_rgb_r.disabled = false;
		form.new_alarm_rgb_g.disabled = false;
		form.new_alarm_rgb_b.disabled = false;
		form.elements['new_zone[CheckMethod]'].disabled = false;
		form.elements['new_zone[MinPixelThreshold]'].disabled = false;
		form.elements['new_zone[MaxPixelThreshold]'].disabled = false;
		form.elements['new_zone[MinAlarmPixels]'].disabled = false;
		form.elements['new_zone[MaxAlarmPixels]'].disabled = false;
		applyCheckMethod(); 
	}
}

function applyCheckMethod()
{
	var form = document.zone_form;
	if ( form.elements['new_zone[CheckMethod]'].value == 'AlarmedPixels' )
	{
		form.elements['new_zone[FilterX]'].disabled = true;
		form.elements['new_zone[FilterY]'].disabled = true;
		form.elements['new_zone[MinFilterPixels]'].disabled = true;
		form.elements['new_zone[MaxFilterPixels]'].disabled = true;
		form.elements['new_zone[MinBlobPixels]'].disabled = true;
		form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		form.elements['new_zone[MinBlobs]'].disabled = true;
		form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else if ( form.elements['new_zone[CheckMethod]'].value == 'FilteredPixels' )
	{
		form.elements['new_zone[FilterX]'].disabled = false;
		form.elements['new_zone[FilterY]'].disabled = false;
		form.elements['new_zone[MinFilterPixels]'].disabled = false;
		form.elements['new_zone[MaxFilterPixels]'].disabled = false;
		form.elements['new_zone[MinBlobPixels]'].disabled = true;
		form.elements['new_zone[MaxBlobPixels]'].disabled = true;
		form.elements['new_zone[MinBlobs]'].disabled = true;
		form.elements['new_zone[MaxBlobs]'].disabled = true;
	}
	else
	{
		form.elements['new_zone[FilterX]'].disabled = false;
		form.elements['new_zone[FilterY]'].disabled = false;
		form.elements['new_zone[MinFilterPixels]'].disabled = false;
		form.elements['new_zone[MaxFilterPixels]'].disabled = false;
		form.elements['new_zone[MinBlobPixels]'].disabled = false;
		form.elements['new_zone[MaxBlobPixels]'].disabled = false;
		form.elements['new_zone[MinBlobs]'].disabled = false;
		form.elements['new_zone[MaxBlobs]'].disabled = false;
	}
}

function applyPreset()
{
	var form = document.zone_form;
	var preset = form.elements['presetSelector'].options[form.elements['presetSelector'].selectedIndex].value;

	switch( preset )
	{
<?php
foreach ( $presets as $preset )
{
?>
		case '<?= $preset['Id'] ?>':
		{
			form.elements['new_zone[Units]'].selectedIndex = <?= $preset['UnitsIndex'] ?>;
			form.elements['new_zone[CheckMethod]'].selectedIndex = <?= $preset['CheckMethodIndex'] ?>;
			form.elements['new_zone[MinPixelThreshold]'].value = '<?= $preset['MinPixelThreshold'] ?>';
			form.elements['new_zone[MaxPixelThreshold]'].value = '<?= $preset['MaxPixelThreshold'] ?>';
			form.elements['new_zone[FilterX]'].value = '<?= $preset['FilterX'] ?>';
			form.elements['new_zone[FilterY]'].value = '<?= $preset['FilterY'] ?>';
			form.elements['new_zone[MinAlarmPixels]'].value = '<?= $preset['MinAlarmPixels'] ?>';
			form.elements['new_zone[MaxAlarmPixels]'].value = '<?= $preset['MaxAlarmPixels'] ?>';
			form.elements['new_zone[MinFilterPixels]'].value = '<?= $preset['MinFilterPixels'] ?>';
			form.elements['new_zone[MaxFilterPixels]'].value = '<?= $preset['MaxFilterPixels'] ?>';
			form.elements['new_zone[MinBlobPixels]'].value = '<?= $preset['MinBlobPixels'] ?>';
			form.elements['new_zone[MaxBlobPixels]'].value = '<?= $preset['MaxBlobPixels'] ?>';
			form.elements['new_zone[MinBlobs]'].value = '<?= $preset['MinBlobs'] ?>';
			form.elements['new_zone[MaxBlobs]'].value = '<?= $preset['MaxBlobs'] ?>';
			break;
		}
<?php
}
?>
	}
	applyCheckMethod();
	form.elements['new_zone[TempArea]'].value = 100;
}

function toPixels( field, maxValue )
{
	if ( field.value != '' )
		field.value = Math.round((field.value*maxValue)/100);
}

function toPercent( field, maxValue )
{
	if ( field.value != '' )
		field.value = Math.round((100*100*field.value)/maxValue)/100;
}

function applyZoneUnits()
{
	var max_width = <?= $monitor['Width']-1 ?>;
	var max_height = <?= $monitor['Height']-1 ?>;
	var area = <?= $new_zone['Area'] ?>;

	var form = document.zone_form;
	if ( form.elements['new_zone[Units]'].value == 'Pixels' )
	{
		form.elements['new_zone[TempArea]'].value = area;
		toPixels( form.elements['new_zone[MinAlarmPixels]'], area );
		toPixels( form.elements['new_zone[MaxAlarmPixels]'], area );
		toPixels( form.elements['new_zone[MinFilterPixels]'], area );
		toPixels( form.elements['new_zone[MaxFilterPixels]'], area );
		toPixels( form.elements['new_zone[MinBlobPixels]'], area );
		toPixels( form.elements['new_zone[MaxBlobPixels]'], area );
	}
	else
	{
		form.elements['new_zone[TempArea]'].value = 100;
		toPercent( form.elements['new_zone[MinAlarmPixels]'], area );
		toPercent( form.elements['new_zone[MaxAlarmPixels]'], area );
		toPercent( form.elements['new_zone[MinFilterPixels]'], area );
		toPercent( form.elements['new_zone[MaxFilterPixels]'], area );
		toPercent( form.elements['new_zone[MinBlobPixels]'], area );
		toPercent( form.elements['new_zone[MaxBlobPixels]'], area );
	}
}

function limitRange( field, minValue, maxValue )
{
	if ( parseInt(field.value) < parseInt(minValue) )
	{
		field.value = minValue;
	}
	else if ( parseInt(field.value) > parseInt(maxValue) )
	{
		field.value = maxValue;
	}
}

function limitFilter( field )
{
	var minValue = 3;
	var maxValue = 15;

	field.value = (Math.floor((field.value-1)/2)*2) + 1;
	if ( parseInt(field.value) < minValue )
	{
		field.value = minValue;
	}
	if ( parseInt(field.value) > maxValue )
	{
		field.value = maxValue;
	}
}

function limitArea( field )
{
	var minValue = 0;
	var maxValue = <?= $new_zone['Area'] ?>;
	if ( document.zone_form.elements['new_zone[Units]'].value == "Percent" )
	{
		maxValue = 100;
	}
	limitRange( field, minValue, maxValue );
}

function setBgColor( id, color )
{
	var element = document.getElementById( id );
	element.setAttribute( "bgcolor", color );
}

function swapImage( id, src )
{
	var element = document.getElementById( id );
	element.src = src;
}

function highlightOn( index )
{
	if ( active >= 0 )
		return;
	setBgColor( 'row'+index, '#F0E68C' );
	swapImage( 'point'+index, '<?= $marker['hisrc'] ?>' );
}

function highlightOff( index )
{
	if ( active >= 0 )
		return;
	setBgColor( 'row'+index, '#ffffff' );
	swapImage( 'point'+index, '<?= $marker['src'] ?>' );
}

function setActivePoint( index )
{
	if ( active >= 0 )
	{
		var last_active = active;
		active = -1;
		highlightOff( last_active );
		if ( document.getElementById( 'delete'+last_active ) )
			document.getElementById( 'delete'+last_active ).innerHTML="&ndash;";
		document.getElementById( 'cancel'+last_active ).innerHTML="";
	}
	setBgColor( 'row'+index, '#FFA07A' );
	swapImage( 'point'+index, '<?= $marker['actsrc'] ?>' );
	document.getElementById( 'new_zone[Points]['+index+'][x]' ).disabled = false;
	document.getElementById( 'new_zone[Points]['+index+'][y]' ).disabled = false;
	if ( document.getElementById( 'delete'+index ) )
		document.getElementById( 'delete'+index ).innerHTML="&ndash;";
	document.getElementById( 'cancel'+index ).innerHTML="X";
	active = index;
	document.getElementById( 'zoneImage' ).onclick = fixActivePoint;
	document.getElementById( 'zoneImage' ).onmousemove = updateActivePoint;
}

function unsetActivePoint( index )
{
	if ( active >= 0 )
	{
		var last_active = active;
		active = -1;
		highlightOff( last_active );
		if ( document.getElementById( 'delete'+index ) )
			document.getElementById( 'delete'+index ).innerHTML="";
		document.getElementById( 'cancel'+index ).innerHTML="";
		document.getElementById( 'zoneImage' ).ondblclick = '';
		document.getElementById( 'zoneImage' ).onmousemove = '';
		document.zone_form.reset();
		for ( var i = 0; i < <?= count($new_zone['Points']) ?>; i++ )
		{
			//document.getElementById( 'new_zone[Points]['+i+'][x]' ).disabled = true;
			//document.getElementById( 'new_zone[Points]['+i+'][y]' ).disabled = true;
		}
	}
}

function fixActivePoint( event )
{
	if ( active < 0 )
		return;
	
	if ( !event )
	{
		event = window.event;
	}
	updateActivePoint( event );
	submitForm();
}

function updateActivePoint( event )
{
	var x, y;
	var x_point, y_point;
	if ( event )
	{
		x = event.layerX;
		y = event.layerY;
	}
	else
	{
		event = window.event;
		x = event.offsetX;
		y = event.offsetY;
	}

	x_point = document.getElementById( 'new_zone[Points]['+active+'][x]' );
	y_point = document.getElementById( 'new_zone[Points]['+active+'][y]' );
	x_point.value = x;
	y_point.value = y;
}

function addPoint( index )
{
	document.zone_form.action.value = "loc_addpoint";
	document.zone_form.subaction.value = index;
	submitForm();
}

function delPoint( index )
{
	document.zone_form.action.value = "loc_delpoint";
	document.zone_form.subaction.value = index;
	submitForm();
}

function updatePoint( point, lo_val, hi_val )
{
	if ( point.value < lo_val )
		point.value = 0;
	else if ( point.value > hi_val )
		point.value = hi_val;
	updateValues();
	//document.zone_form.update_btn.disabled = false;
}

function updateX( index )
{
	updatePoint( document.getElementById( 'new_zone[Points]['+index+'][x]' ), 0, <?= $monitor['Width']-1 ?> );
}

function updateY( index )
{
	updatePoint( document.getElementById( 'new_zone[Points]['+index+'][y]' ), 0, <?= $monitor['Height']-1 ?> );
}

function updateValues()
{
	document.zone_form.action.value = '';
	submitForm();
}

function saveChanges()
{
	if ( validateForm() )
	{
		document.zone_form.action.value = 'zone';
		submitForm();
		return( true );
	}
	return( false );
}
</script>
</head>
<body>
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="subaction" value="">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="zid" value="<?= $zid ?>">
<input type="hidden" name="new_zone[NumCoords]" value="<?= count($new_zone['Points']) ?>">
<input type="hidden" name="new_zone[Coords]" value="<?= $new_zone['Coords'] ?>">
<input type="hidden" name="new_zone[Area]" value="<?= $new_zone['Area'] ?>">
<input type="hidden" name="new_zone[AlarmRGB]" value=""
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td align="left" class="head"><?= $zmSlangMonitor ?> <?= $monitor['Name'] ?> - <?= $zmSlangZone ?> <?= $new_zone['Name'] ?></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td width="50%" valign="top">
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td align="left" class="smallhead"><?= $zmSlangParameter ?></td><td colspan="2" align="left" class="smallhead"><?= $zmSlangValue ?></td>
</tr>
<tr><td align="left" class="text"><?= $zmSlangName ?></td><td colspan="2" align="left" class="text"><input type="text" name="new_zone[Name]" value="<?= $new_zone['Name'] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangType ?></td><td colspan="2" align="left" class="text"><select name="new_zone[Type]" class="form" onchange="applyZoneType()">
<?php
foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
{
?>
<option value="<?= $opt_type ?>"<?php if ( $opt_type == $new_zone['Type'] ) { ?> selected<?php } ?>><?= $opt_type ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangPreset ?></td><td colspan="2" align="left" class="text"><?= buildSelect( "presetSelector", $preset_names, array( "onChange"=>"applyPreset()", "onBlur"=>"this.selectedIndex=0" ) ) ?></td></tr>
<tr><td colspan="3"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangUnits ?></td><td colspan="2" align="left" class="text"><select name="new_zone[Units]" class="form" onchange="applyZoneUnits()">
<?php
foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
{
?>
<option value="<?= $opt_units ?>"<?php if ( $opt_units == $new_zone['Units'] ) { ?> selected<?php } ?>><?= $opt_units ?></option>
<?php
}
?>
</select></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneAlarmColour ?></td><td colspan="2" align="left" class="text"><input type="text" name="new_alarm_rgb_r" value="<?= ($new_zone['AlarmRGB']>>16)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;/&nbsp;<input type="text" name="new_alarm_rgb_g" value="<?= ($new_zone['AlarmRGB']>>8)&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )">&nbsp;/&nbsp;<input type="text" name="new_alarm_rgb_b" value="<?= $new_zone['AlarmRGB']&0xff ?>" size="3" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCheckMethod ?></td><td colspan="2" align="left" class="text"><select name="new_zone[CheckMethod]" class="form" onchange="applyCheckMethod()">
<?php
foreach ( getEnumValues( 'Zones', 'CheckMethod' ) as $opt_check_method )
{
?>
<option value="<?= $opt_check_method ?>"<?php if ( $opt_check_method == $new_zone['CheckMethod'] ) { ?> selected<?php } ?>><?= $opt_check_method ?></option>
<?php
}
?>
</select></td></tr>
<tr><td colspan="3"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinMaxPixelThres ?></td><td align="left" class="text"><input type="text" name="new_zone[MinPixelThreshold]" value="<?= $new_zone['MinPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td><td align="left" class="text"><input type="text" name="new_zone[MaxPixelThreshold]" value="<?= $new_zone['MaxPixelThreshold'] ?>" size="4" class="form" onchange="limitRange( this, 0, 255 )"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneFilterSize ?></td><td align="left" class="text"><input type="text" name="new_zone[FilterX]" value="<?= $new_zone['FilterX'] ?>" size="4" class="form" onchange="limitFilter( this )"></td><td align="left" class="text"><input type="text" name="new_zone[FilterY]" value="<?= $new_zone['FilterY'] ?>" size="4" class="form" onchange="limitFilter( this )"></td></tr>
<tr><td colspan="3"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneArea ?></td><td colspan="2" align="left" class="text"><input type="text" name="new_zone[TempArea]" value="<?= $new_zone['Area'] ?>" size="7" class="form" disabled></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinMaxAlarmArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinAlarmPixels]" value="<?= $new_zone['MinAlarmPixels'] ?>" size="6" class="form" onchange="limitArea(this)"></td><td align="left" class="text"><input type="text" name="new_zone[MaxAlarmPixels]" value="<?= $new_zone['MaxAlarmPixels'] ?>" size="6" class="form" onchange="limitArea(this)"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinMaxFiltArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinFilterPixels]" value="<?= $new_zone['MinFilterPixels'] ?>" size="6" class="form" onchange="limitArea(this)"></td><td align="left" class="text"><input type="text" name="new_zone[MaxFilterPixels]" value="<?= $new_zone['MaxFilterPixels'] ?>" size="6" class="form" onchange="limitArea(this)"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinMaxBlobArea ?></td><td align="left" class="text"><input type="text" name="new_zone[MinBlobPixels]" value="<?= $new_zone['MinBlobPixels'] ?>" size="6" class="form"></td><td align="left" class="text"><input type="text" name="new_zone[MaxBlobPixels]" value="<?= $new_zone['MaxBlobPixels'] ?>" size="6" class="form"></td></tr>
<tr><td colspan="3"><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangZoneMinMaxBlobs ?></td><td align="left" class="text"><input type="text" name="new_zone[MinBlobs]" value="<?= $new_zone['MinBlobs'] ?>" size="4" class="form"></td><td align="left" class="text"><input type="text" name="new_zone[MaxBlobs]" value="<?= $new_zone['MaxBlobs'] ?>" size="4" class="form"></td></tr>
<tr><td colspan="3" align="left" class="text">&nbsp;</td></tr>
</table>
</td>
<td width="50%">
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<tr><td align="center">
<div id="canvas" style="position:relative; width:<?= reScale( $monitor['Width'], $scale ) ?>px; height:<?= reScale( $monitor['Height'], $scale ) ?>px;">
<img name="zoneImage" id="zoneImage" src="<?= $zone_image ?>" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>" border="0">
<?php
for ( $i = 0; $i < count($new_zone['Points']); $i++ )
{
?>
<div style="position:absolute; width:<?= $marker['width'] ?>px; height:<?= $marker['height'] ?>px; left: <?= $new_zone['Points'][$i]['x']-intval($marker['width']/2) ?>px; top: <?= $new_zone['Points'][$i]['y']-intval($marker['height']/2) ?>px"><img id="point<?= $i ?>" src="<?= $marker['src'] ?>" width="<?= $marker['width'] ?>" height="<?= $marker['height'] ?>" border="0" onMouseOver="highlightOn( <?= $i ?> )" onMouseOut="highlightOff( <?= $i ?> )" onClick="setActivePoint( <?= $i ?> )"></div>
<?php
}
?>
</div>
</td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="1">
<tr>
<?php
$point_cols = 2;
for ( $i = 0; $i < $point_cols; $i++ )
{
?>
<td width="48%" valign="top"><table align="center" border="0" cellspacing="0" cellpadding="1">
<tr>
<td align="center" class="smallhead"><?= $zmSlangPoint ?></td>
<td align="center" class="smallhead"><?= $zmSlangX ?></td>
<td align="center" class="smallhead"><?= $zmSlangY ?></td>
<td align="center" class="smallhead"><?= $zmSlangAction ?></td>
</tr>
<?php
	for ( $j = $i; $j < count($new_zone['Points']); $j += 2 )
	{
?>
<tr id="row<?= $j ?>" onMouseOver="highlightOn( <?= $j ?> )" onMouseOut="highlightOff( <?= $j ?> )" onClick="setActivePoint( <?= $j ?> )">
<td align="center" class="text"><?= $j+1 ?></td>
<td align="center" class="text"><input name="new_zone[Points][<?= $j ?>][x]" id="new_zone[Points][<?= $j ?>][x]" size="5" value="<?= $new_zone['Points'][$j]['x'] ?>" onChange="updateX( <?= $j ?> )" class="form"></td>
<td align="center" class="text"><input name="new_zone[Points][<?= $j ?>][y]" id="new_zone[Points][<?= $j ?>][y]" size="5" value="<?= $new_zone['Points'][$j]['y'] ?>" onChange="updateY( <?= $j ?> )" class="form"></td>
<td align="center" class="text"><a href="javascript: addPoint( <?= $j ?> );">+</a><?php if ( count($new_zone['Points']) > 3 ) { ?>&nbsp;<a id="delete<?= $j ?>" href="javascript: delPoint( <?= $j ?> )"></a><?php } ?>&nbsp;<a id="cancel<?= $j ?>" href="javascript: unsetActivePoint( <?= $j ?> )"></a></td>
</tr>
<?php
	}
?>
</table></td>
<?php
	if ( $i < ($point_cols-1) )
	{
?>
<td>&nbsp;</td>
<?php
	}
}
?>
</tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<tr>
<td colspan="3" align="center" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left" width="40%">&nbsp;</td>
<td align="center" width="20%"><!--<input type="button" name="update_btn" value="<?= $zmSlangUpdate ?>" class="form" onClick="updateValues()" disabled>--></td>
<td align="right" width="40%"><input type="submit" value="<?= $zmSlangSave ?>" onClick="return saveChanges()" class="form"<?= false && $self_intersecting?" disabled":"" ?>>&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
</td>
</tr>
</table>
</form>
<script type="text/javascript">
applyZoneType();
<?php
if ( isset($zone) )
{
?>
if ( document.zone_form.elements['new_zone[Units]'].value == 'Percent' )
{
	applyZoneUnits();
}
<?php
}
?>
applyCheckMethod();
</script>
</body>
</html>
