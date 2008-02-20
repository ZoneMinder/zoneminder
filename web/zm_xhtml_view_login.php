<?php
//
// ZoneMinder web login view file, $Date$, $Revision$
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

header("Content-type: application/xhtml+xml" );
echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" );
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangLogin ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<form method="post" action="<?= $PHP_SELF ?>">
<div style="visibility: hidden">
<fieldset>
<input type="hidden" name="action" value="login"/>
<input type="hidden" name="view" value="console"/>
</fieldset>
</div>
<table style="width: 100%">
<tr><td colspan="2" class="head" align="center">ZoneMinder <?= $zmSlangLogin ?>
</td></tr>
<tr><td align="right"><?= $zmSlangUsername ?></td><td align="left"><input type="text" name="username" value="<?= isset($username)?$username:"" ?>" size="12"/></td></tr>
<tr><td align="right"><?= $zmSlangPassword ?></td><td align="left"><input type="password" name="password" value="" size="12"/></td></tr>
<tr><td colspan="2" align="center"><input type="submit" value="<?= $zmSlangLogin ?>"/></td></tr>
</table>
</form>
</body>
</html>
