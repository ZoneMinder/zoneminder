<?php
//
// ZoneMinder web options view file, $Date$, $Revision$
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

if ( !canView( 'System' ) )
{
	$view = "error";
	return;
}

$tabs = array();
$tabs['system'] = $zmSlangSystem;
$tabs['config'] = $zmSlangConfig;
$tabs['paths'] = $zmSlangPaths;
$tabs['web'] = $zmSlangWeb;
$tabs['images'] = $zmSlangImages;
$tabs['debug'] = $zmSlangDebug;
$tabs['network'] = $zmSlangNetwork;
$tabs['mail'] = $zmSlangEmail;
$tabs['ftp'] = $zmSlangFTP;
$tabs['x10'] = $zmSlangX10;
$tabs['highband'] = $zmSlangHighBW;
$tabs['medband'] = $zmSlangMediumBW;
$tabs['lowband'] = $zmSlangLowBW;
$tabs['phoneband'] = $zmSlangPhoneBW;
if ( ZM_OPT_USE_AUTH )
	$tabs['users'] = $zmSlangUsers;

if ( !isset($tab) )
	$tab = "system";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangOptions ?></title>
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
		if ( 0 && $value['Type'] == "boolean" )
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
<td width="10" class="passivetab"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&tab=<?= $name ?>"><?= $value ?></a></td>
<?php
	}
}
?>
<td class="nontab">&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="1" cellpadding="1" width="100%">
<?php 
if ( $tab == "users" )
{
?>
<form name="user_form" method="post" action="<?= $PHP_SELF ?>" onSubmit="validateForm( this )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="delete">
<tr>
<td align="left" class="smallhead"><?= $zmSlangId ?></td>
<td align="left" class="smallhead"><?= $zmSlangUsername ?></td>
<td align="left" class="smallhead"><?= $zmSlangLanguage ?></td>
<td align="left" class="smallhead"><?= $zmSlangEnabled ?></td>
<td align="left" class="smallhead"><?= $zmSlangStream ?></td>
<td align="left" class="smallhead"><?= $zmSlangEvents ?></td>
<td align="left" class="smallhead"><?= $zmSlangControl ?></td>
<td align="left" class="smallhead"><?= $zmSlangMonitors ?></td>
<td align="left" class="smallhead"><?= $zmSlangSystem ?></td>
<td align="left" class="smallhead"><?= $zmSlangBandwidth ?></td>
<td align="left" class="smallhead"><?= $zmSlangMonitor ?></td>
<td align="left" class="smallhead"><?= $zmSlangMark ?></td>
</tr>
<?php
	$sql = "select * from Users";
    foreach( dbFetchAll( $sql ) as $row )
	{
?>
<tr onMouseOver="this.className='over'" onMouseOut="this.className='out'">
<td align="left" class="ruled"><?= $row['Id'] ?></td>
<td align="left" class="ruled"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=user&uid=".$row['Id']."', 'zmUser', ".$jws['user']['w'].", ".$jws['user']['h']." );", $row['Username'].($user['Username']==$row['Username']?"*":""), canEdit( 'System' ) ) ?></td>
<td align="left" class="ruled"><?= $row['Language']?$row['Language']:'default' ?></td>
<td align="left" class="ruled"><?= $row['Enabled']?$zmSlangYes:$zmSlangNo ?></td>
<td align="left" class="ruled"><?= $row['Stream'] ?></td>
<td align="left" class="ruled"><?= $row['Events'] ?></td>
<td align="left" class="ruled"><?= $row['Control'] ?></td>
<td align="left" class="ruled"><?= $row['Monitors'] ?></td>
<td align="left" class="ruled"><?= $row['System'] ?></td>
<td align="left" class="ruled"><?= $row['MaxBandwidth']?$bw_array[$row['MaxBandwidth']]:'&nbsp;' ?></td>
<td align="left" class="ruled"><?= $row['MonitorIds']?$row['MonitorIds']:"&nbsp;" ?></td>
<td align="center" class="ruled"><input type="checkbox" name="mark_uids[]" value="<?= $row['Id'] ?>" onClick="configureButton( document.user_form, 'mark_uids' );"<?php if ( !canEdit( 'System' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
<tr><td colspan="12" class="ruled">&nbsp;</td></tr>
<tr><td colspan="12" align="right"><input type="button" value="<?= $zmSlangAddNewUser ?>" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=user&uid=-1', 'zmUser', <?= $jws['user']['w'] ?>, <?= $jws['user']['h'] ?> );"<?php if ( !canEdit( 'System' ) ) { ?> disabled<?php } ?>>&nbsp;<input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled>&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td></tr>
</form>
<?php
}
else
{
	if ( $tab == "system" )
	{
		$config_cats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join( '|', getLanguages() );
	}
?>
<form name="options_form" method="post" action="<?= $PHP_SELF ?>" onSubmit="return( validateForm( document.options_form ) );">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="options">
<tr>
<td align="left" class="smallhead"><?= $zmSlangName ?></td>
<td align="left" class="smallhead"><?= $zmSlangDescription ?></td>
<td align="left" class="smallhead"><?= $zmSlangValue ?></td>
</tr>
<?php
	$config_cat = $config_cats[$tab];

	foreach ( $config_cat as $name=>$value )
	{
		$option_prompt_var = "zmOlangPrompt".preg_replace( '/^ZM_/', '', $value['Name'] );
		$option_prompt_text = isset($$option_prompt_var)?$$option_prompt_var:$value['Prompt'];
?>
<tr>
<td align="left" class="text"><?= $value['Name'] ?></td>
<td align="left" class="text"><?= htmlentities($option_prompt_text) ?> (<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=optionhelp&option=<?= $value['Name'] ?>', 'zmOptionHelp', <?= $jws['optionhelp']['w'] ?>, <?= $jws['optionhelp']['h'] ?>);">?</a>)</td>
<?php	
		if ( $value['Type'] == "boolean" )
		{
?>
<td align="left" class="text"><input type="checkbox" class="text" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="1"<?php if ( $value['Value'] ) { ?> checked<?php } ?>></td>
<?php
		}
		elseif ( preg_match( "/\|/", $value['Hint'] ) )
		{
?>
<td align="left" class="text"><nobr>
<?php
			$options = split( "\|", $value['Hint'] );
			if ( count( $options ) > 3 )
			{
?>
<select name="new_config[<?= $value['Name'] ?>] ?>" class="form">
<?php
				foreach ( $options as $option )
				{
?>
<option value="<?= $option ?>"<?php if ( $value['Value'] == $option ) { echo " selected"; } ?>><?= htmlentities($option) ?></option>
<?php
				}
?>
</select>
<?php
			}
			else
			{
				foreach ( $options as $option )
				{
?>
<input type="radio" class="text" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="<?= $option ?>"<?php if ( $value['Value'] == $option ) { ?> checked<?php } ?>>&nbsp;<?= $option ?>&nbsp;&nbsp;
<?php
				}
			}
?>
</nobr></td>
<?php
		}
		elseif ( $value['Type'] == "text" )
		{
?>
<td align="left" class="text"><textarea class="form" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" rows="5" cols="40"><?= htmlspecialchars($value['Value']) ?></textarea></td>
<?php
		}
		elseif ( $value['Type'] == "integer" )
		{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="<?= $value['Value'] ?>" size="8"></td>
<?php
		}
		elseif ( $value['Type'] == "hexadecimal" )
		{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="<?= $value['Value'] ?>" size="12"></td>
<?php
		}
		elseif ( $value['Type'] == "decimal" )
		{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="<?= $value['Value'] ?>" size="8"></td>
<?php
		}
		else
		{
?>
<td align="left" class="text"><input type="text" class="form" id="<?= $value['Name'] ?>" name="new_config[<?= $value['Name'] ?>]" value="<?= $value['Value'] ?>" size="32"></td>
<?php
		}
?>
</tr>
<?php
	}
?>
<tr><td colspan="3" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form">&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td></tr>
</form>
<?php
}
?>
</table>
<?php
if ( !empty($restart) )
{
	flush();
?>
<script type="text/javascript">
alert( "<?= $zmSlangOptionRestartWarning ?>" );
</script>
<?php
}
?>
</body>
</html>
