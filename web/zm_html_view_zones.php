<?php
	if ( !canView( 'Monitors' ) )
	{
		$view = "error";
		break;
	}
	chdir( ZM_DIR_IMAGES );
	$status = exec( escapeshellcmd( ZMU_COMMAND." -m $mid -z" ) );
	chdir( '..' );

	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );

	$result = mysql_query( "select * from Zones where MonitorId = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$zones = array();
	while( $row = mysql_fetch_assoc( $result ) )
	{
		$zones[] = $row;
	}

	$image = $monitor[Name]."-Zones.jpg";
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Zones</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
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
</script>
</head>
<body>
<map name="zonemap">
<?php
	foreach( $zones as $zone )
	{
		if ( $zone[Units] == 'Percent' )
		{
?>
<area shape="rect" coords="<?= sprintf( "%d,%d,%d,%d", ($zone[LoX]*$monitor[Width])/100, ($zone[LoY]*$monitor[Height])/100, ($zone[HiX]*$monitor[Width])/100, ($zone[HiY]*$monitor[Height])/100 ) ?>" href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );">
<?php
		}
		else
		{
?>
<area shape="rect" coords="<?= "$zone[LoX],$zone[LoY],$zone[HiX],$zone[HiY]" ?>" href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );">
<?php
		}
	}
?>
<area shape="default" nohref>
</map>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td width="33%" align="left" class="text">&nbsp;</td>
<td width="34%" align="center" class="head"><strong><?= $monitor[Name] ?> Zones</strong></td>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="3" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$image ?>" usemap="#zonemap" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>" border="0"></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="mid" value="<?= $mid ?>">
<tr><td align="center" class="smallhead">Id</td>
<td align="center" class="smallhead">Name</td>
<td align="center" class="smallhead">Type</td>
<td align="center" class="smallhead">Units</td>
<td align="center" class="smallhead">Dimensions</td>
<td align="center" class="smallhead">Mark</td>
</tr>
<?php
	foreach( $zones as $zone )
	{
?>
<tr>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"><?= $zone[Id] ?>.</a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"><?= $zone[Name] ?></a></td>
<td align="center" class="text"><?= $zone['Type'] ?></td>
<td align="center" class="text"><?= $zone[Units] ?></td>
<td align="center" class="text"><?= $zone[LoX] ?>,<?= $zone[LoY] ?>-<?= $zone[HiX] ?>,<?= $zone[HiY]?></td>
<td align="center" class="text"><input type="checkbox" name="mark_zids[]" value="<?= $zone[Id] ?>" onClick="configureButton( document.zone_form, 'mark_zids' );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
<tr>
<td align="center" class="text">&nbsp;</td>
<td colspan="4" align="center"><input type="button" value="Add New Zone" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=-1', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled<?php } ?>></td>
<td align="center"><input type="submit" name="delete_btn" value="Delete" class="form" disabled></td>
</tr>
</form>
</table>
</body>
</html>
