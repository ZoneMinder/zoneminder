<?php
//
// ZoneMinder web montage menu view file, $Date$, $Revision$
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

if ( $mid )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
}
?>
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<?php
if ( $mid )
{
?>
<td width="50%" align="center" class="text"><b><?= $monitor['Name'] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="50%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montageframe&mode=still&mid=<?= $mid ?>" target="_parent"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td width="50%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montageframe&mode=stream&mid=<?= $mid ?>" target="_parent"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td width="50%" align="center" class="text">&nbsp;</td>
<?php } ?>
<?php
}
else
{
?>
<td width="33%" align="left" class="text"><b><?= $zmSlangMontage ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montage&mode=still&mid=<?= $mid ?>" target="_parent"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montage&mode=stream&mid=<?= $mid ?>" target="_parent"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td width="34%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="33%" align="right" class="text"><a href="javascript: top.window.close()"><?= $zmSlangClose ?></a></td>
<?php
}
?>
</tr>
</table>
</body>
</html>
