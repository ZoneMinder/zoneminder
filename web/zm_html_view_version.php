<?php
//
// ZoneMinder web version view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}
$options = array(
	"go" => $zmSlangGoToZoneMinder
);

if ( verNum( ZM_DYN_CURR_VERSION ) != verNum( ZM_DYN_LAST_VERSION ) )
{
	$options = array_merge( $options, array(
		"ignore" => $zmSlangVersionIgnore,
		"hour"   => $zmSlangVersionRemindHour,
		"day"    => $zmSlangVersionRemindDay,
		"week"   => $zmSlangVersionRemindWeek,
		"never"  => $zmSlangVersionRemindNever
	) );
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangVersion ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function zmWindow()
{
	var winName = window.open( 'http://www.zoneminder.com', 'ZoneMinder' );
	winName.focus();
}
function closeWindow()
{
	window.close();
}
function submitForm()
{
	with( document.version_form )
	{
		if ( option.selectedIndex == 0 )
		{
			view.value = '<?= $view ?>';
		}
		else
		{
			view.value = 'none';
		}
	}
}
<?php
if ( $action == "version" && $option == "go" )
{
?>
zmWindow();
<?php
}
?>
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<tr>
<td align="center" class="head">ZoneMinder - <?= $zmSlangVersion ?></td>
</tr>
</table>
<?php
if ( verNum( ZM_DYN_LAST_VERSION ) <= verNum( ZM_VERSION ) )
{
?>
<table border="0" cellspacing="0" cellpadding="6" width="100%">
<tr>
<td align="center" class="text"><?= sprintf( $zmClangRunningRecentVer, ZM_VERSION ) ?></td>
</tr>
<tr>
<td align="center" class="text"><?= $zmSlangUpdateNotNecessary ?></td>
</tr>
<tr>
<td align="center" class="text">&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td width="75%" align="center"><input type="button" value="<?= $zmSlangGoToZoneMinder ?>" class="form" onClick="zmWindow()"></td>
<td width="25%" align="center"><input type="button" value="<?= $zmSlangClose ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
<?php
}
else
{
?>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<form name="version_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="version">
<table border="0" cellspacing="0" cellpadding="6" width="100%">
<tr>
<td align="center" class="text"><?= $zmSlangUpdateAvailable ?></td>
</tr>
<tr>
<td align="center" class="text"><?= sprintf( $zmClangLatestRelease, ZM_DYN_LAST_VERSION, ZM_VERSION  ) ?></td>
</tr>
<tr>
<td align="center" class="text"><?= buildSelect( "option", $options ); ?></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td width="50%" align="center">&nbsp;</td>
<td width="25%" align="center"><input type="submit" value="<?= $zmSlangApply ?>" class="form" onClick="submitForm()"></td>
<td width="25%" align="center"><input type="button" value="<?= $zmSlangClose ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
<?php
}
?>
</body>
</html>
