<?php
//
// ZoneMinder web option help view file, $Date$, $Revision$
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

$option_help_var = "zmOlangHelp".preg_replace( '/^ZM_/', '', $option );
$option_help_text = $$option_help_var?$$option_help_var:$config[$option]['Help'];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangOptionHelp ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="96%">
<tr><td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
<tr><td align="left" class="smallhead"><?= $option ?></td></tr>
<tr><td class="text"><p class="text" align="justify"><?= htmlentities($option_help_text) ?></p></td></tr>
</table>
</body>
</html>
