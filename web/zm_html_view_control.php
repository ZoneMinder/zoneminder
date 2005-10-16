<?php
//
// ZoneMinder web watch view file, $Date$, $Revision$
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

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangControl ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
</script>
</head>
<?php
if ( $menu )
{
?>
<frameset rows="24,*,0" border="0" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=controlmenu&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="ControlMenu" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=controlpanel&menu=1&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="ControlPanel" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=blank" marginwidth="0" marginheight="0" name="ControlSink" scrolling="no">
</frameset>
<?php
}
else
{
?>
<frameset rows="*,0" border="0" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=controlpanel&mid=<?= $monitor['Id'] ?>" marginwidth="0" marginheight="0" name="ControlPanel<?= $mid ?>" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=blank" marginwidth="0" marginheight="0" name="ControlSink<?= $mid ?>" scrolling="no">
</frameset>
<?php
}
?>
