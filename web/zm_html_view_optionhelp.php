<?php

// Help Text can be either the default from the DB or overridden in the language files
include_once( 'zm_help.php' );

$option_help_var = "zmHlangOption$option";
$option_help_text = $$option_help_var?$$option_help_var:$config[$option]['Help'];

?>
<html>
<head>
<title>ZM - <?= $zmSlangOptionHelp ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="96%">
<tr><td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
<tr><td align="left" class="smallhead"><?= $option ?></td></tr>
<tr><td class="text"><p class="text" align="justify"><?= $option_help_text ?></p></td></tr>
</table>
</body>
</html>
