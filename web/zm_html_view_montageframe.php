<?php
//
// ZoneMinder web montage frame view file, $Date$, $Revision$
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

?>
<html>
<?php
if ( ZM_WEB_COMPACT_MONTAGE )
{
?>
<frameset rows="<?= (ZM_WEB_REFRESH_METHOD=='javascript'&&ZM_WEB_DOUBLE_BUFFER)?'0,':'' ?>*" cols="100%" border="1" frameborder="no" framespacing="0">
<?php
	if ( ZM_WEB_REFRESH_METHOD == 'javascript' && ZM_WEB_DOUBLE_BUFFER )
	{
?>
<frame src="about:blank" name="MontageFetch<?= $mid ?>" scrolling="no">
	<?php
	}
?>
<frame src="<?= $PHP_SELF ?>?view=montagefeed&mid=<?= $mid ?>&mode=<?= $mode ?>" marginwidth="0" marginheight="0" name="MontageStream<?= $mid ?>" scrolling="no">
</frameset>
<?php
}
else
{
?>
<frameset rows="24,<?= (ZM_WEB_REFRESH_METHOD=='javascript'&&ZM_WEB_DOUBLE_BUFFER)?'0,':'' ?>*,16" cols="100%" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=montagemenu&mid=<?= $mid ?>&mode=<?= $mode ?>" marginwidth="0" marginheight="0" name="MontageMenu<?= $mid ?>" scrolling="no">
<?php
	if ( ZM_WEB_REFRESH_METHOD == 'javascript' && ZM_WEB_DOUBLE_BUFFER )
	{
?>
<frame src="about:blank" name="MontageFetch<?= $mid ?>" scrolling="no">
	<?php
	}
?>
<frame src="<?= $PHP_SELF ?>?view=montagefeed&mid=<?= $mid ?>&mode=<?= $mode ?>" marginwidth="0" marginheight="0" name="MontageStream<?= $mid ?>" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=montagestatus&mid=<?= $mid ?>" marginwidth="0" marginheight="0" name="MontageStatus<?= $mid ?>" scrolling="no">
</frameset>
<?php
}
?>
</html>
