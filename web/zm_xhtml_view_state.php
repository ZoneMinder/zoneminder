<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}
$running = daemonCheck();

$result = mysql_query( "select * from States" );
if ( !$result )
	die( mysql_error() );
while( $state = mysql_fetch_assoc( $result ) )
{
	$states[] = $state;
}

?>
<html>
<head>
<title>ZM - <?= $zmSlangState ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<form method="post" action="<?= $PHP_SELF ?>">
<div style="visibility: hidden">
<fieldset>
<input type="hidden" name="view" value="console"/>
<input type="hidden" name="action" value="state">
</fieldset>
</div>
<table>
<tr>
<td align="center" class="head">ZoneMinder - <?= $zmSlangRunState ?></td>
</tr>
<tr>
<td align="center"><select name="run_state" class="form">
<?php
	if ( $running )
	{
?>
<option value="stop" selected><?= $zmSlangStop ?></option>
<option value="restart"><?= $zmSlangRestart ?></option>
<?php
	}
	else
	{
?>
<option value="start" selected><?= $zmSlangStart ?></option>
<?php
	}
?>
<?php
	foreach ( $states as $state )
	{
?>
<option value="<?= $state['Name'] ?>"><?= $state['Name'] ?></option>
<?php
	}
?>
</select></td>
</tr>
<tr>
<td align="center"><input type="submit" value="<?= $zmSlangApply ?>" class="form"></td>
</tr>
</table>
</form>
</body>
</html>
