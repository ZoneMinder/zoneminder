<?php
	if ( !canEdit( 'System' ) )
	{
		$view = "error";
		return;
	}
	$running = daemonCheck();

	$result = mysql_query( "select * from States" );
	if ( !$result )
		die( mysql_error() );
	while( $state = mysql_fetch_assoc( $result ) )
	{
		$states[] = $state;
	}
?>
<html>
<head>
<title>ZM - State</title>
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
function refreshWindow()
{
	window.location.reload(true);
}
function closeWindow()
{
	window.close();
}
function checkState()
{
	with ( document.state_form )
	{
<?php
	if ( $running )
	{
?>
		if ( run_state.selectedIndex < 2 )
		{
			save_btn.disabled = true;
			delete_btn.disabled = true;
		}
<?php
	}
	else
	{
?>
		if ( run_state.selectedIndex < 1 )
		{
			save_btn.disabled = true;
			delete_btn.disabled = true;
		}
<?php
	}
?>
		else
		{
			save_btn.disabled = false;
			delete_btn.disabled = false;
		}
		if ( new_state.value != '' )
		{
			save_btn.disabled = false;
		}
	}
}
function saveState()
{
	with ( document.state_form )
	{
		view.value = '<?= $view ?>';
		action.value = 'save';
		submit();
	}
}
function deleteState()
{
	with ( document.state_form )
	{
		view.value = '<?= $view ?>';
		action.value = 'delete';
		submit();
	}
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="4" align="center" class="head">ZoneMinder Run State</td>
</tr>
<?php
if ( !$apply )
{
?>
<form name="state_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="apply" value="1">
<tr>
<td colspan="2" align="center"><select name="run_state" class="form" onChange="checkState();">
<?php
	if ( $running )
	{
?>
<option value="stop" selected>Stop</option>
<option value="restart">Restart</option>
<?php
	}
	else
	{
?>
<option value="start" selected>Start</option>
<?php
	}
?>
<?php
	foreach ( $states as $state )
	{
?>
<option value="<?= $state[Name] ?>"><?= $state[Name] ?></option>
<?php
	}
?>
</select></td>
</tr>
<tr>
<td align="right" class="text">New State:</td>
<td align="left"><input type="text" name="new_state" value="" size="16" class="form" onChange="checkState();"></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td width="25%" align="center"><input type="submit" value="Apply" class="form"></td>
<td width="25%" align="center"><input type="button" name="save_btn" value="Save" class="form" disabled onClick="saveState();"></td>
<td width="25%" align="center"><input type="button" name="delete_btn" value="Delete" class="form" disabled onClick="deleteState();"></td>
<td width="25%" align="center"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</form>
<?php
}
else
{
?>
<tr>
<td colspan="2" align="center" class="text">&nbsp;</td>
</tr>
<tr>
<td colspan="2" align="center" class="text">Applying ZoneMinder state change<br/>Please wait</td>
</tr>
<form name="state_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="state">
<input type="hidden" name="run_state" value="<?= $run_state ?>">
</form>
<script language="JavaScript">
window.setTimeout( "document.state_form.submit()", 500 );
</script>
<?php
}
?>
</table>
</body>
</html>
