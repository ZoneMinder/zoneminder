<?php
//
// ZoneMinder web function view file, $Date$, $Revision$
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
<title>ZM - <?= $zmSlangFunction ?> - <?= $monitor['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<form method="post" action="<?= $PHP_SELF ?>">
<div style="visibility: hidden">
<fieldset>
<input type="hidden" name="view" value="console"/>
<input type="hidden" name="action" value="function"/>
<input type="hidden" name="mid" value="<?= $mid ?>"/>
</fieldset>
</div>
<table>
<tr>
<td align="center" class="smallhead"><?= sprintf( $zmClangMonitorFunction, $monitor['Name'] ) ?></td>
</tr>
<tr>
<td align="center"><select name="new_function" class="form">
<?php
foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
{
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
}
?>
</select></td>
</tr>
<tr>
<td align="center"><input type="submit" value="<?= $zmSlangSave ?>" class="form"></td>
</tr>
</table>
</form>
</body>
</html>
