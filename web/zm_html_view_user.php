<?php
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
		$row[Username] = "NewUser";
		$row[Enabled] = 1;
	}
?>
<html>
<head>
<title>ZM - User - <?= $row[Username] ?></title>
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
<td colspan="2" align="left" class="head">User - &quot;<?= $row[Username] ?>&quot;</td>
</tr>
<form name="user_form" method="post" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.user_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="user">
<input type="hidden" name="uid" value="<?= $uid ?>">
<tr><td align="right" class="text">Username</td><td align="left" class="text"><input type="text" name="new_username" value="<?= $row[Username] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text">New Password</td><td align="left" class="text"><input type="password" name="new_password" value="<?= $row[Password] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text">Confirm Password</td><td align="left" class="text"><input type="password" name="new_password2" value="<?= $row[Password] ?>" size="16" class="form"></td></tr>
<?php
	$new_enabled = $row[Enabled];
	$yesno = array( 0=>'No', 1=>'Yes' );
?>
<tr><td align="right" class="text">Enabled</td><td align="left" class="text"><?= buildSelect( "new_enabled", $yesno ) ?></td></tr>
<?php
	$new_stream = $row[Stream];
	$nv = array( 'None'=>'None', 'View'=>'View' );
?>
<tr><td align="right" class="text">Stream</td><td align="left" class="text"><?= buildSelect( "new_stream", $nv ) ?></td></tr>
<?php
	$new_events = $row[Events];
	$new_monitors = $row[Monitors];
	$new_system = $row[System];
	$nve = array( 'None'=>'None', 'View'=>'View', 'Edit'=>'Edit' );
?>
<tr><td align="right" class="text">Events</td><td align="left" class="text"><?= buildSelect( "new_events", $nve ) ?></td></tr>
<tr><td align="right" class="text">Monitors</td><td align="left" class="text"><?= buildSelect( "new_monitors", $nve ) ?></td></tr>
<tr><td align="right" class="text">System</td><td align="left" class="text"><?= buildSelect( "new_system", $nve ) ?></td></tr>
<tr><td align="right" class="text">Restricted Camera Ids</td><td align="left" class="text"><input type="text" name="new_monitor_ids" value="<?= $row[MonitorIds] ?>" size="16" class="form"></td></tr>
<tr>
<td align="right"><input type="submit" value="Save" class="form"></td><td align="left"><input type="button" value="Close" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
