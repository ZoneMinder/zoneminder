<?php
//
// ZoneMinder web watch pos view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

define( "ZM_POS_REFRESH", 3 );

if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: ".ZM_POS_REFRESH."; URL=$PHP_SELF?view=watchpos&mid=$mid" );
noCacheHeaders();

$result = mysql_query( "select * from Events where MonitorId = '$mid' order by Id desc limit 1" );
if ( !$result )
	die( mysql_error() );
$event = mysql_fetch_assoc( $result );
mysql_free_result( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangPosData ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function closeWindow()
{
	top.window.close();
}
<?php
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.replace( '<?= "$PHP_SELF?view=watchpos&mid=$mid" ?>' )", <?= ZM_POS_REFRESH*1000 ?> );
<?php
}
?>
</script>
</head>
<body>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td width="20%">&nbsp;</td>
<td><table width="96%" align="center" border="0" cellspacing="3" cellpadding="0">
<tr>
<td align="left" class="head"><b><?= $zmSlangPosData ?></td>
</tr>
<tr>
<td colspan="3" class="text">&nbsp;</td></tr>
</tr>
<tr>
<td align="left" class="text"><?= htmlentities($event['Cause']) ?></td>
</tr>
<tr>
<td align="left" class="text"><?= preg_replace( "/\n/", "<br>", htmlentities($event['Notes'])) ?></td>
</tr>
</table>
</td>
</tr>
</table></center>
</body>
</html>
