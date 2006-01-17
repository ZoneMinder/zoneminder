<?php
//
// ZoneMinder web export view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

if ( $generate )
{
	$_SESSION['export']['detail'] = $export_detail;
	$_SESSION['export']['frames'] = $export_frames;
	$_SESSION['export']['images'] = $export_images;
	$_SESSION['export']['video'] = $export_video;
	$_SESSION['export']['misc'] = $export_misc;
	$_SESSION['export']['format'] = $export_format;
}
else
{
	$export_detail = $_SESSION['export']['detail'];
	$export_frames = $_SESSION['export']['frames'];
	$export_images = $_SESSION['export']['images'];
	$export_video = $_SESSION['export']['video'];
	$export_misc = $_SESSION['export']['misc'];
	$export_format = $_SESSION['export']['format'];
}

if ( !(($export_detail || $export_frames || $export_images || $export_video || $export_misc) && $export_format) )
{
	$generate = 0;
}

ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangExport ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<form name="export_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="<?= $action ?>">
<?php
if ( isset($eid) )
{
?>
<input type="hidden" name="eid" value="<?= $eid ?>">
<?php
}
elseif ( isset($eids) )
{
	foreach ( $eids as $eid )
	{
?>
<input type="hidden" name="eids[]" value="<?= $eid ?>">
<?php
	}
	unset( $eid );
}
?>
<input type="hidden" name="generate" value="1">
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50">&nbsp;</td><td class="head" align="center"><?= $zmSlangExportOptions ?></td><td width="50" class="text" align="right"><a href="javascript: window.close();"><?= $zmSlangClose ?></a></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="60%">&nbsp;</td><td width="40%">&nbsp;</td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportDetails ?></td><td><input type="checkbox" class="form-noborder" name="export_detail" value="1"<?php if ( isset($export_detail) ) { ?> checked<?php } ?>></td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportFrames ?></td><td><input type="checkbox" class="form-noborder" name="export_frames" value="1"<?php if ( isset($export_frames) ) { ?> checked<?php } ?>></td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportImageFiles ?></td><td><input type="checkbox" class="form-noborder" name="export_images" value="1"<?php if ( isset($export_images) ) { ?> checked<?php } ?>></td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportVideoFiles ?></td><td><input type="checkbox" class="form-noborder" name="export_video" value="1"<?php if ( isset($export_video) ) { ?> checked<?php } ?>></td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportMiscFiles ?></td><td><input type="checkbox" class="form-noborder" name="export_misc" value="1"<?php if ( isset($export_misc) ) { ?> checked<?php } ?>></td></tr>
<tr><td class="text" align="right"><?= $zmSlangExportFormat ?></td><td class="text"><input type="radio" class="form-noborder" name="export_format" value="tar"<?php if ( $export_format == "tar" ) { ?> checked<?php } ?>>&nbsp;<?= $zmSlangExportFormatTar ?>&nbsp;&nbsp;<input type="radio" class="form-noborder" name="export_format" value="zip"<?php if ( $export_format == "zip" ) { ?> checked<?php } ?>>&nbsp;<?= $zmSlangExportFormatZip ?></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="form" value="<?= $zmSlangExport ?>">&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td></tr>
</table>
</form>
<?php
if ( !empty($generate) )
{
	require_once( 'zm_export_funcs.php' );

?>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td align="center" class="head"><?= $zmSlangExporting ?></td>
</tr>
</table>
</body>
</html>
<?php
	$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
	for ( $i = 0; $i < 4096/strlen($buffer_string); $i++ )
	{
		echo $buffer_string."\n";
	}
?>
<?php
	ob_end_flush();
	if ( $export_file = exportEvents( $eid?$eid:$eids ) )
	{
?>
<html>
<head>
<script type="text/javascript">
location.replace('<?= $export_file ?>');
</script>
</head>
</html>
<?php
	}
	else
	{
		ob_end_flush();
?>
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<p class="head" align="center"><font color="red"><br><?= $zmSlangExportFailed ?><br></font></p>
<?php
	}
}
else
{
	ob_end_flush();
}
?>
</body>
</html>
