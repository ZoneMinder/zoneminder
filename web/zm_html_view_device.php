<?php
//
// ZoneMinder web device detail view file, $Date$, $Revision$
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

if ( !canEdit( 'Devices' ) )
{
	$view = "error";
	return;
}
if ( $did )
{
	$sql = "select * from Devices where Id = '$did'";
    $new_device = dbFetchOne( $sql );
}
else
{
	$new_device = array(
		"Id" => "",
		"Name" => "New Device",
		"KeyString" => ""
	);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangDevice ?> <?= $eid ?></title>
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
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<form name="device_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="device">
<input type="hidden" name="did" value="<?= $did ?>">
<table width="100%" border="0">
<tr><td class="head"><?= $zmSlangDevice ?></td></tr>
<tr><td><img src="graphics/spacer.gif" width="1" height="5"></td></tr>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr valign="top"><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_device[Name]" value="<?= $new_device['Name'] ?>" size="24" class="form"></td></tr>
<tr valign="top"><td align="left" class="text"><?= $zmSlangKeyString ?></td><td align="left" class="text"><input type="text" name="new_device[KeyString]" value="<?= $new_device['KeyString'] ?>" size="24" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Devices' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
</body>
</html>
