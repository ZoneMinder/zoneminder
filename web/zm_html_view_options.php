<?php
	if ( !canView( 'System' ) )
	{
		$view = "error";
		return;
	}

	$tabs = array();
	$tabs["system"] = "System";
	$tabs["paths"] = "Paths";
	$tabs["video"] = "Video";
	$tabs["network"] = "Network";
	$tabs["web"] = "Web";
	$tabs["mail"] = "Email";
	$tabs["ftp"] = "FTP";
	$tabs["x10"] = "X10";
	$tabs["tools"] = "Tools";
	$tabs["highband"] = "High&nbsp;B/W";
	$tabs["medband"] = "Medium&nbsp;B/W";
	$tabs["lowband"] = "Low&nbsp;B/W";
	$tabs["phoneband"] = "Phone&nbsp;B/W";
	if ( ZM_OPT_USE_AUTH )
		$tabs["users"] = "Users";

	if ( !$tab )
		$tab = "system";
?>
<html>
<head>
<title>ZM - Options</title>
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

function configureButton(form,name)
{
	var checked = false;
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				checked = true;
				break;
			}
		}
	}
	form.delete_btn.disabled = !checked;
}

function newWindow(Url,Name,Width,Height)
{
	window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}

function closeWindow()
{
	window.close();
}

<?php
	if ( $tab == 'users' )
	{
?>
function validateForm( form )
{
	return( true );
}
<?php
	}
	else
	{
?>

function validateForm( form )
{
	var errors = Array();
<?php
		$config_cat = $config_cats[$tab];

		foreach ( $config_cat as $name=>$value )
		{
			if ( 0 && $value[Type] == "boolean" )
			{
?>
	if ( !form.<?= $name ?>.value )
	{
		form.<?= $name ?>.value = 0;
	}
<?php
			}
		}
?>
	return( true );
}
<?php
	}
?>

</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<?php
	foreach ( $tabs as $name=>$value )
	{
		if ( $tab == $name )
		{
?>
<td width="10" class="activetab"><?= $value ?></td>
<?php
		}
		else
		{
?>
<td width="10" class="passivetab"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&tab=<?= $name ?>"?><?= $value ?></a></td>
<?php
		}
	}
?>
<td class="nontab">&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<?php 
	if ( $tab == "users" )
	{
?>
<form name="user_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="validateForm( this )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="delete">
<tr>
<td align="left" class="smallhead">Id</td>
<td align="left" class="smallhead">Username</td>
<td align="left" class="smallhead">Password</td>
<td align="left" class="smallhead">Enabled</td>
<td align="left" class="smallhead">Stream</td>
<td align="left" class="smallhead">Events</td>
<td align="left" class="smallhead">Monitors</td>
<td align="left" class="smallhead">System</td>
<td align="left" class="smallhead">Monitor&nbsp;Ids</td>
<td align="left" class="smallhead">Mark</td>
</tr>
<?php
		$result = mysql_query( "select * from Users" );
		if ( !$result )
			die( mysql_error() );
		while( $row = mysql_fetch_assoc( $result ) )
		{
?>
<tr onMouseOver="this.className='over'" onMouseOut="this.className='out'">
<td align="left" class="ruled"><?= $row[Id] ?></td>
<td align="left" class="ruled"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=user&uid=$row[Id]', 'zmUser', ".$jws['user']['w'].", ".$jws['user']['h']." );", $row[Username].($user[Username]==$row[Username]?"*":""), canEdit( 'System' ) ) ?></td>
<td align="left" class="ruled">********</td>
<td align="left" class="ruled"><?= $row[Enabled]?'Yes':'No' ?></td>
<td align="left" class="ruled"><?= $row[Stream] ?></td>
<td align="left" class="ruled"><?= $row[Events] ?></td>
<td align="left" class="ruled"><?= $row[Monitors] ?></td>
<td align="left" class="ruled"><?= $row[System] ?></td>
<td align="left" class="ruled"><?= $row[MonitorIds]?$row[MonitorIds]:"&nbsp;" ?></td>
<td align="center" class="ruled"><input type="checkbox" name="mark_uids[]" value="<?= $row[Id] ?>" onClick="configureButton( document.user_form, 'mark_uids' );"<?php if ( !canEdit( 'System' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
		}
?>
<tr><td colspan="10" class="ruled">&nbsp;</td></tr>
<tr><td colspan="10" align="right"><input type="button" value="Add New User" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=user&uid=-1', 'zmUser', <?= $jws['user']['w'] ?>, <?= $jws['user']['h'] ?> );"<?php if ( !canEdit( 'System' ) ) { ?> disabled<?php } ?>>&nbsp;<input type="submit" name="delete_btn" value="Delete" class="form" disabled>&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow();"></td></tr>
</form>
<?php
	}
	else
	{
?>
<form name="options_form" method="post" action="<?= $PHP_SELF ?>" onSubmit="return( validateForm( document.options_form ) );">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="options">
<tr>
<td align="left" class="smallhead">Name</td>
<td align="left" class="smallhead">Description</td>
<td align="left" class="smallhead">Value</td>
</tr>
<?php
		$config_cat = $config_cats[$tab];

		foreach ( $config_cat as $name=>$value )
		{
?>
<tr>
<td align="left" class="text"><?= $value[Name] ?></td>
<td align="left" class="text"><?= $value[Prompt] ?> (<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=optionhelp&option=<?= $value[Name] ?>', 'zmOptionHelp', <?= $jws['optionhelp']['w'] ?>, <?= $jws['optionhelp']['h'] ?>);">?</a>)</td>
<?php	
			if ( $value[Type] == "boolean" )
			{
?>
<td align="left" class="text"><input type="checkbox" class="text" id="<?= $value[Name] ?>" name="new_config[<?= $value[Name] ?>]" value="1"<?php if ( $value[Value] ) { ?> checked<?php } ?>></td>
<?php
			}
			elseif ( preg_match( "/\|/", $value[Hint] ) )
			{
?>
<td align="left" class="text">
<?php
				foreach ( split( "\|", $value[Hint] ) as $option )
				{
?>
<input type="radio" class="text" id="<?= $value[Name] ?>" name="new_config[<?= $value[Name] ?>]" value="<?= $option ?>"<?php if ( $value[Value] == $option ) { ?> checked<?php } ?>>&nbsp;<?= $option ?>&nbsp;&nbsp;
<?php
				}
?>
</td>
<?php
			}
			elseif ( $value[Type] == "text" )
			{
?>
<td align="left" class="text"><textarea class="form" id="<?= $value[Name] ?>" name="new_config[<?= $value[Name] ?>]" rows="5" cols="40"><?= htmlspecialchars($value[Value]) ?></textarea></td>
<?php
			}
			elseif ( $value[Type] == "integer" )
			{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value[Name] ?>" name="new_config[<?= $value[Name] ?>]" value="<?= $value[Value] ?>" size="8"></td>
<?php
			}
			else
			{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value[Name] ?>" name="new_config[<?= $value[Name] ?>]" value="<?= $value[Value] ?>" size="40"></td>
<?php
			}
?>
</tr>
<?php
		}
?>
<tr><td colspan="3" align="right"><input type="submit" value="Save" class="form">&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow();"></td></tr>
</form>
<?php
	}
?>
</table>
<?php
	if ( $restart )
	{
		flush();
?>
<script language="JavaScript">
alert( "These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder." );
//var restartWindow = window.open( '<?= $PHP_SELF ?>?view=restarting', 'zmRestarting', 'resizable,width=<?= $jws['restarting']['w'] ?>,height=<?= $jws['restarting']['h'] ?>' );
</script>
<?php
	}
?>
</body>
</html>
