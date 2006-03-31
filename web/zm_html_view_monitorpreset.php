<?php
//
// ZoneMinder web monitor preset view file, $Date$, $Revision$
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

if ( !canEdit( 'Monitors' ) )
{
	$view = "error";
	return;
}
$result = mysql_query( "select Id,Name from MonitorPresets" );
if ( !$result )
	die( mysql_error() );
$presets = array();
$presets[0] = $zmSlangChoosePreset;
while ( $preset = mysql_fetch_assoc( $result ) )
{
	$presets[$preset['Id']] = htmlentities( $preset['Name'] );
}
mysql_free_result( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangMonitorPreset ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function submitPreset()
{
	document.presetForm.target = opener.name;
	document.presetForm.view.value = 'monitor';
	document.presetForm.submit();
	window.setTimeout( 'window.close()', 250 );
}
function configureButtons()
{
	document.presetForm.saveBtn.disabled = (document.presetForm.preset.selectedIndex==0);
}
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<form name="presetForm" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="mid" value="<?= $mid ?>">
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td align="center" class="head"><?= $zmSlangMonitorPreset ?></td>
</tr>
<tr>
<td align="center" class="text"><?= $zmSlangMonitorPresetIntro ?></td>
</tr>
<tr>
<td align="center" class="text"><?= $zmSlangPreset ?>:&nbsp;<?= buildSelect( "preset", $presets, 'configureButtons()' ); ?></td>
</tr>
<tr>
<td align="right"><input type="submit" name="saveBtn" value="<?= $zmSlangSave ?>" class="form" onClick="submitPreset()" disabled>&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
</body>
</html>
