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
<wml>
<card id="zmError" title="ZM - <?= $zmSlangError ?>">
<table columns="1" align="C">
<tr><td>ZoneMinder <?= $zmSlangError ?></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td><strong><?= $zmSlangYouNoPerms ?></strong></td></tr>
<tr><td><strong><?= $zmSlangContactAdmin ?></strong></td></tr>
<tr><td>&nbsp;</td></tr>
</table>
</card>
</wml>
