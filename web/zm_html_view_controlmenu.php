<?php
//
// ZoneMinder web control menu view file, $Date$, $Revision$
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

if ( !canEdit( 'Monitors' ) )
{
	$view = "error";
	return;
}

if ( $group )
{
	$sql = "select * from Groups where Id = '$group'";
	$result = mysql_query( $sql );
	if ( !$result )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );
	$group_sql = "and find_in_set( Id, '".$row['MonitorIds']."' )";
}
$sql = "select * from Monitors where Function != 'None' and Controllable = 1 $group_sql order by Id";
$result = mysql_query( $sql ); 
if ( !$result )
	die( mysql_error() );
$mids = array();
while( $row = mysql_fetch_assoc( $result ) )
{
	if ( !visibleMonitor( $row['Id'] ) )
	{
		continue;
	}
	$mids[$row['Id']] = $row['Name'];
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
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
<form name="menu_form" method="get" action="<?= $PHP_SELF ?>" target="ControlPanel">
<input type="hidden" name="view" value="controlpanel">
<input type="hidden" name="menu" value="1">
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="25%" align="center" class="text">&nbsp;</td>
<td width="50%" align="center" valign="middle" class="text"><?= buildSelect( "mid", $mids, "document.menu_form.submit();" ); ?></td>
<td width="25%" align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
</table>
</form>
</body>
</html>
