<?php
//
// ZoneMinder web user view file, $Date$, $Revision$
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
$result = mysql_query( "select * from Users where Id = '$uid'" );
if ( !$result )
	die( mysql_error() );
if ( !($row = mysql_fetch_assoc( $result )) )
{
	$row = array();
	$row['Username'] = $zmSlangNewUser;
	$row['Enabled'] = 1;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>ZM - <?= $zmSlangUser ?> - <?= $row['Username'] ?></title>
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
function validateForm(Form)
{
	var errors = new Array();
	if ( !Form.new_username.value )
	{
		errors[errors.length] = "You must supply a username";
	}
	if ( !Form.new_password.value )
	{
		errors[errors.length] = "You must supply a password";
	}
	else
	{
		if ( !Form.new_password2.value )
		{
			errors[errors.length] = "You must confirm the password";
		}
		else if ( Form.new_password.value != Form.new_password2.value )
		{
			errors[errors.length] = "The new and confirm passwords are different";
		}
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="left" class="head"><?= $zmSlangUser ?> - &quot;<?= $row['Username'] ?>&quot;</td>
</tr>
<form name="user_form" method="post" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.user_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="user">
<input type="hidden" name="uid" value="<?= $uid ?>">
<tr><td align="right" class="text"><?= $zmSlangUsername ?></td><td align="left" class="text"><input type="text" name="new_username" value="<?= $row['Username'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangNewPassword ?></td><td align="left" class="text"><input type="password" name="new_password" value="<?= $row['Password'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangConfirmPassword ?></td><td align="left" class="text"><input type="password" name="new_password2" value="<?= $row['Password'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangLanguage ?></td><td align="left" class="text"><input type="text" name="new_language" value="<?= $row['Language'] ?>" size="16" class="form"></td></tr>
<?php
$new_enabled = $row['Enabled'];
$yesno = array( 0=>$zmSlangNo, 1=>$zmSlangYes );
?>
<tr><td align="right" class="text"><?= $zmSlangEnabled ?></td><td align="left" class="text"><?= buildSelect( "new_enabled", $yesno ) ?></td></tr>
<?php
$new_stream = $row['Stream'];
$nv = array( 'None'=>$zmSlangNone, 'View'=>$zmSlangView );
?>
<tr><td align="right" class="text">Stream</td><td align="left" class="text"><?= buildSelect( "new_stream", $nv ) ?></td></tr>
<?php
$new_events = $row['Events'];
$new_monitors = $row['Monitors'];
$new_system = $row['System'];
$nve = array( 'None'=>$zmSlangNone, 'View'=>$zmSlangView, 'Edit'=>$zmSlangEdit );
?>
<tr><td align="right" class="text"><?= $zmSlangEvents ?></td><td align="left" class="text"><?= buildSelect( "new_events", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangMonitors ?></td><td align="left" class="text"><?= buildSelect( "new_monitors", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangSystem ?></td><td align="left" class="text"><?= buildSelect( "new_system", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangRestrictedCameraIds ?></td><td align="left" class="text"><input type="text" name="new_monitor_ids" value="<?= $row['MonitorIds'] ?>" size="16" class="form"></td></tr>
<tr>
<td align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"></td><td align="left"><input type="button" value="<?= $zmSlangClose ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
