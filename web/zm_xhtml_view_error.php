<?php
//
// ZoneMinder web error view file, $Date$, $Revision$
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
<title>ZM - <?= $zmSlangError ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css">
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr><td colspan="2" class="smallhead" align="center">ZoneMinder <?= $zmSlangError ?></td></tr>
<tr><td colspan="2" class="text" align="center">&nbsp;</td></tr>
<tr><td colspan="2" class="text" align="center"><strong><?= $zmSlangYouNoPerms ?><br/><?= $zmSlangContactAdmin ?></strong></td></tr>
</table>
</body>
</html>
