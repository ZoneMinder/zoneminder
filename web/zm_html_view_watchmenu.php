<?php
//
// ZoneMinder web watch menu view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}

if ( empty($mode) )
{
	if ( canStream() )
		$mode = "stream";
	else
		$mode = "still";
}

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

if ( !isset( $scale ) )
	$scale = ZM_WEB_DEFAULT_SCALE;

?>
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<form name="view_form" method="get" action="<?= $PHP_SELF ?>" target="_parent">
<input type="hidden" name="view" value="watch">
<input type="hidden" name="mode" value="<?= $mode ?>">
<input type="hidden" name="mid" value="<?= $mid ?>">
<tr>
<td width="25%" align="left" class="text"><b><?= $monitor['Name'] ?></b></td>
<td align="center" valign="middle" class="text">
<?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "document.view_form.submit();" ); ?>
</td>
<?php if ( canView( 'Monitors' ) && $monitor['Type'] == "Local" ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=settings&mid=<?= $monitor['Id'] ?>', 'zmSettings<?= $monitor['Id'] ?>', <?= $jws['settings']['w'] ?>, <?= $jws['settings']['h'] ?> );"><?= $zmSlangSettings ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $php_self ?>?view=watch&mode=still&mid=<?= $mid ?>&scale=<?= $scale ?>" target="_parent"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $php_self ?>?view=watch&mode=stream&mid=<?= $mid ?>&scale=<?= $scale ?>" target="_parent"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
</form>
</table>
</body>
</html>
