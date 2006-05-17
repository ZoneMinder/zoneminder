<?php
//
// ZoneMinder web status view file, $Date$, $Revision$
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

$zmu_command = getZmuCommand( " --list" );
$result = exec( escapeshellcmd( $zmu_command ), $output );

$refresh = ZM_WEB_REFRESH_STATUS;
$url = "$PHP_SELF?view=$view";
if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: $refresh; URL=$url" );
noCacheHeaders();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangStatus ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.replace( '<?= $url ?>' )", <?= $refresh*1000 ?> );
<?php
}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellpadding="0" cellspacing="0">
<?php
foreach ( $output as $row )
{
?>
  <tr>
<?php
    foreach ( preg_split( "/\s+/", $row ) as $col )
    {
?>
    <td><?= $col ?></td>
<?php
    }
?>
  </tr>
<?php
}
?>
</table>
</body>
</html>
