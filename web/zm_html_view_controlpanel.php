<?php
//
// ZoneMinder web watch control view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

require_once( 'zm_control_funcs.php' );

$sql = "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );

$cmds = getControlCommands( $monitor );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
</script>
</head>
<body>
<form name="ctrl_form" method="get" action="<?= $PHP_SELF ?>" target="ControlSink<?= $menu?'':$mid ?>">
<input type="hidden" name="view" value="blank">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="action" value="control">
<input type="hidden" name="control" value="">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="middle" align="center"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td valign="top" align="center">&nbsp;</td>
<?php
if ( $monitor['CanFocus'] )
{
?>
<td valign="top" align="center"><?= controlFocus( $monitor ) ?></td>
<?php
}
if ( $monitor['CanZoom'] )
{
?>
<td valign="top" align="center"><?= controlZoom( $monitor ) ?></td>
<?php
}
if ( $monitor['CanMove'] || ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] ) )
{
?>
<td valign="top" align="center"><table border="0" cellspacing="0" cellpadding="0">
<?php
	if ( $monitor['CanMove'] )
	{
?>
<tr>
<td valign="top" align="center"><?= controlPanTilt( $monitor ) ?></td>
</tr>
<?php
	}
	if ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] )
	{
?>
<tr>
<td valign="top" align="center"><?= controlPower( $monitor ) ?></td>
</tr>
<?php
	}
?>
</table></td>
<?php
}
if ( $monitor['CanIris'] )
{
?>
<td valign="top" align="center"><?= controlIris( $monitor ) ?></td>
<?php
}
if ( $monitor['CanWhite'] )
{
?>
<td valign="top" align="center"><?= controlWhite( $monitor ) ?></td>
<?php
}
?>
</tr>
</table></td>
</tr>
<tr>
<?php if ( $monitor['HasPresets'] ) { ?><td valign="top" align="center"><?= controlPresets( $monitor ) ?></td><?php } ?>
</tr>
</table></center>
</form>
</body>
</html>
