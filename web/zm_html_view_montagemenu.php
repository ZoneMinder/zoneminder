<?php
//
// ZoneMinder web montage menu view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) )
{
	$view = "error";
	return;
}
if ( empty($mode) )
{
	if ( ZM_WEB_USE_STREAMS && canStream() )
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
	mysql_free_result( $result );
}
elseif ( ZM_OPT_CONTROL )
{
	if ( $group )
	{
		$sql = "select * from Groups where Id = '$group'";
		$result = mysql_query( $sql );
		if ( !$result )
			die( mysql_error() );
		$row = mysql_fetch_assoc( $result );
		mysql_free_result( $result );
		$group_sql = "and find_in_set( Id, '".$row['MonitorIds']."' )";
	}
	$sql = "select * from Monitors where Function != 'None' and Controllable = 1 $group_sql order by Sequence";
	$result = mysql_query( $sql ); 
	if ( !$result )
		die( mysql_error() );
	$control_mid = 0;
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( !visibleMonitor( $row['Id'] ) )
		{
			continue;
		}
		if ( !$control_mid )
		{
			$control_mid = $row['Id'];
		}
	}
	mysql_free_result( $result );
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
</script>
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
<td width="25%" align="left" class="text"><b><?= $zmSlangMontage ?></b></td>
<?php if ( ZM_OPT_CONTROL && $control_mid && canView( 'Control' ) ) { ?>
<td width="25%" align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=control&menu=1&mid=<?= $control_mid ?>', 'Control', <?= $jws['control']['w'] ?>, <?= $jws['control']['h'] ?> )"><?= $zmSlangControl ?></a></td>
<?php } else { ?>
<td width="25%" align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td width="25%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montage&mode=still&group=<?= $group ?>" target="_parent"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td width="25%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montage&mode=stream&group=<?= $group ?>" target="_parent"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td width="25%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="25%" align="right" class="text"><a href="javascript: top.window.close()"><?= $zmSlangClose ?></a></td>
<?php
}
?>
</tr>
</table>
</body>
</html>
