<?php
	if ( !canEdit( 'Monitors' ) )
	{
		$view = "error";
		return;
	}
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - Function - <?= $monitor[Name] ?></title>
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
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="center" class="head">Monitor '<?= $monitor[Name] ?>' Function</td>
</tr>
<tr>
<form method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="function">
<input type="hidden" name="mid" value="<?= $mid ?>">
<td colspan="2" align="center"><select name="new_function" class="form">
<?php
	foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
	{
		if ( !ZM_OPT_X10 && $opt_function == 'X10' )
			continue;
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
	}
?>
</select></td>
</tr>
<tr>
<td align="center"><input type="submit" value="Save" class="form"></td>
<td align="center"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
