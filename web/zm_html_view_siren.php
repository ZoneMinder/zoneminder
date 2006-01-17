<?php
//
// ZoneMinder web siren view file, $Date$, $Revision$
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

$sound_src = ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
</head>
<body>
<?php
if ( ZM_WEB_USE_OBJECT_TAGS && isWindows() )
{
?>
<object id="MediaPlayer" width="0" height="0"
classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902"
<param name="FileName" value="<?= $sound_src ?>">
<param name="autoStart" value="1">
<param name="loop" value="1">
<param name=hidden value="1">
<param name="showControls" value="0">
<embed src="<?= $sound_src ?>"
autostart="true"
loop="true"
hidden="true">
</embed>
</object>
<?php
}
else
{
?>
<embed src="<?= $sound_src ?>"
autostart="true"
loop="true"
hidden="true">
</embed>
<?php
}
?>
</body>
</html>
