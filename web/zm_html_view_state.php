<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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
<title>ZM - <?= $zmSlangState ?></title>
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
<td colspan="4" align="center" class="head">ZoneMinder - <?= $zmSlangRunState ?></td>
</tr>
<?php
if ( empty($apply) )
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
<option value="stop" selected><?= $zmSlangStop ?></option>
<option value="restart"><?= $zmSlangRestart ?></option>
<?php
	}
	else
	{
?>
<option value="start" selected><?= $zmSlangStart ?></option>
<?php
	}
?>
<?php
	foreach ( $states as $state )
	{
?>
<option value="<?= $state['Name'] ?>"><?= $state['Name'] ?></option>
<?php
	}
?>
</select></td>
</tr>
<tr>
<td align="right" class="text"><?= $zmSlangNewState ?>:</td>
<td align="left"><input type="text" name="new_state" value="" size="16" class="form" onChange="checkState();"></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td width="25%" align="center"><input type="submit" value="<?= $zmSlangApply ?>" class="form"></td>
<td width="25%" align="center"><input type="button" name="save_btn" value="<?= $zmSlangSave ?>" class="form" disabled onClick="saveState();"></td>
<td width="25%" align="center"><input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled onClick="deleteState();"></td>
<td width="25%" align="center"><input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
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
<td colspan="2" align="center" class="text"><?= $zmSlangApplyingStateChange ?><br/><?= $zmSlangPleaseWait ?></td>
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
