<?php
//
// ZoneMinder web controls file, $Date$, $Revision$
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

if ( !canView( 'Control' ) )
{
    $view = "error";
    return;
}

$sql = "select * from Controls order by Id";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
$controls = array();
while( $row = mysql_fetch_assoc( $result ) )
{
	$controls[] = $row;
}
mysql_free_result( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangControlCaps ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
//window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h']+(25*(count($controls)>6?count($controls):6)) ?> );
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
    window.close();
}
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
function confirmDelete()
{
	return( confirm( 'Warning, deleting a control will reset all monitors that use it to be uncontrollable.\nAre you sure you wish to delete?' ) );
}
</script>
</head>
<body scroll="auto">
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td align="left" class="head"><?= $zmSlangControlCaps ?></td>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
</table>
<form name="control_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="return(confirmDelete());">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr><td align="center" class="smallhead"><?= $zmSlangId ?></td>
<td align="center" class="smallhead"><?= $zmSlangName ?></td>
<td align="center" class="smallhead"><?= $zmSlangType ?></td>
<td align="center" class="smallhead"><?= $zmSlangCanMove ?></td>
<td align="center" class="smallhead"><?= $zmSlangCanZoom ?></td>
<td align="center" class="smallhead"><?= $zmSlangCanFocus ?></td>
<td align="center" class="smallhead"><?= $zmSlangCanIris ?></td>
<td align="center" class="smallhead"><?= $zmSlangCanWhiteBal ?></td>
<td align="center" class="smallhead"><?= $zmSlangHasPresets ?></td>
<td align="center" class="smallhead"><?= $zmSlangMark ?></td>
</tr>
<?php
foreach( $controls as $control )
{
?>
<tr>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=controlcap&cid=".$control['Id']."', 'zmControlCap', ".$jws['controlcap']['w'].", ".$jws['controlcap']['h']." );", $control['Id'].'.', canView( 'Control' ) ) ?></td>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=controlcap&cid=".$control['Id']."', 'zmControlCap', ".$jws['controlcap']['w'].", ".$jws['controlcap']['h']." );", $control['Name'], canView( 'Control' ) ) ?></td>
<td align="center" class="text"><?= $control['Type'] ?></td>
<td align="center" class="text"><?= $control['CanMove']?$zmSlangYes:$zmSlangNo ?></td>
<td align="center" class="text"><?= $control['CanZoom']?$zmSlangYes:$zmSlangNo ?></td>
<td align="center" class="text"><?= $control['CanFocus']?$zmSlangYes:$zmSlangNo ?></td>
<td align="center" class="text"><?= $control['CanIris']?$zmSlangYes:$zmSlangNo ?></td>
<td align="center" class="text"><?= $control['CanWhite']?$zmSlangYes:$zmSlangNo ?></td>
<td align="center" class="text"><?= $control['HasHomePreset']?'H':'' ?><?= $control['HasPresets']?$control['NumPresets']:'0' ?></td>
<td align="center" class="text"><input type="checkbox" name="mark_cids[]" value="<?= $control['Id'] ?>" onClick="configureButton( document.control_form, 'mark_cids' );"<?php if ( !canEdit( 'Control' ) ) {?> disabled<?php } ?>></td>
</tr>
<?php
}
?>
<tr>
<td colspan="2" align="center">&nbsp;
<input type="button" value="<?= $zmSlangRefresh ?>" class="form" onClick="javascript: location.reload(true);">
</td>
<td colspan="2" align="center">
<input type="button" value="<?= $zmSlangAddNewControl ?>" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=controlcap', 'zmControlCap', <?= $jws['controlcap']['w'] ?>, <?= $jws['controlcap']['h'] ?>);"<?php if ( !canEdit( 'Control' ) ) {?> disabled<?php } ?>>
</td>
<td colspan="5" align="center">&nbsp;</td>
<td align="center"><input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled></td>
</tr>
</form>
</table>
</body>
</html>
