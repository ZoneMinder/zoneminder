<html>
<head>
<title>ZM - <?= $zmSlangBandwidth ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table align="center" border="0" cellspacing="4" cellpadding="2" width="96%">
<form name="logout_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="action" value="bandwidth">
<input type="hidden" name="view" value="">
<tr><td colspan="2" class="smallhead" align="center"><?= $zmSlangSetNewBandwidth ?></td></tr>
<?php
		$bw_array = array( "high"=>$zmSlangHigh, "medium"=>$zmSlangMedium, "low"=>$zmSlangLow );
		$new_bandwidth = $bandwidth;
?>
<tr><td colspan="2" class="text" align="center"><?= buildSelect( "new_bandwidth", $bw_array ) ?></td></tr>
<tr><td align="center"><input type="submit" value="<?= $zmSlangSave ?>" class="form"></td>
<td align="center"><input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td></tr>
</form>
</table>
</body>
</html>
