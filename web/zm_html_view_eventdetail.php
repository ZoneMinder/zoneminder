<?php
//
// ZoneMinder web event detail view file, $Date$, $Revision$
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

if ( !canEdit( 'Events' ) )
{
	$view = "error";
	return;
}
if ( $eid )
{
	$sql = "select E.* from Events as E where E.Id = '$eid'";
	$new_event = dbFetchOne( $sql );
}
elseif ( $eids )
{
	$sql = "select E.* from Events as E where ";
	$sql_where = array();
	foreach ( $eids as $eid )
	{
		$sql_where[] = "E.Id = '$eid'";
	}
	unset( $eid );
	$sql .= join( " or ", $sql_where );
    foreach( dbFetchAll( $sql ) as $row )
	{
		if ( !isset($new_event) )
		{
			$new_event = $row;
		}
		else
		{
			if ( $new_event['Cause'] && $new_event['Cause'] != $row['Cause'] )
				$new_event['Cause'] = "";
			if ( $new_event['Notes'] && $new_event['Notes'] != $row['Notes'] )
				$new_event['Notes'] = "";
		}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangFrames ?> <?= $eid ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table width="96%" border="0">
<tr>
<td align="left" class="smallhead"><b><?= $zmSlangEvent ?> <?= $eid ?></b></td>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr><td colspan="2"><table width="100%" border="0" cellpadding="3" cellspacing="1">
<form name="event_form" method="post" action="<?= $PHP_SELF ?>">
<?php
if ( $eid )
{
?>
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="eventdetail">
<input type="hidden" name="eid" value="<?= $eid ?>">
<?php
}
elseif ( $eids )
{
?>
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="eventdetail">
<?php
	foreach ( $eids as $eid )
	{
?>
<input type="hidden" name="mark_eids[]" value="<?= $eid ?>">
<?php
	}
	unset( $eid );
}
?>
<tr valign="top"><td align="left" class="text"><?= $zmSlangCause ?></td><td align="left" class="text"><input type="text" name="new_event[Cause]" value="<?= $new_event['Cause'] ?>" size="32" class="form"></td></tr>
<tr valign="top"><td align="left" class="text"><?= $zmSlangNotes ?></td><td align="left" class="text"><textarea name="new_event[Notes]" rows="6" cols="50" class="form"><?= $new_event['Notes'] ?></textarea></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</form>
</table></td>
</tr>
</table>
</body>
</html>
