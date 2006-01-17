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

?>
<wml>
<card id="zmLogin" title="<?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangLogin ?>">
<p align="center">ZoneMinder <?= $zmSlangLogin ?></p>
<p align="center">
<input title="<?= $zmSlangUsername ?>" type="text" name="Username" value="<?= isset($username)?$username:"" ?>" size="12"/><br/>
<input title="<?= $zmSlangPassword ?>" type="password" name="Password" value="" size="12"/><br/>
<do type="accept" name="Login" label="<?= $zmSlangLogin ?>">
<go href="<?= $PHP_SELF ?>" method="post">
<postfield name="action" value="login"/>
<postfield name="view" value="postlogin"/>
<postfield name="username" value="$(Username)"/>
<postfield name="password" value="$(Password)"/>
</go>
</do>
</p>
</card>
</wml>
