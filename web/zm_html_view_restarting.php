<?php
//
// ZoneMinder web restarting view file, $Date$, $Revision$
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

?>
<html>
<head>
<title>ZM - Restarting</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
window.setTimeout( "window.close();", <?= 10000 ?> );
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="96%">
<tr><td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
<tr><td align="center" class="smallhead">ZoneMinder - <?= $zmSlangRestarting ?></td></tr>
<tr><td align="center" class="text">Changes you have made to the configuration mean that ZoneMinder needs to restart. Please wait for a few seconds before applying any other changes.</td></tr>
</table>
</body>
</html>
