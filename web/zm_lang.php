<?php
//
// ZoneMinder web language file, $Date$, $Revision$
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

$fallback_lang_file = 'zm_lang_en_gb.php';
$system_lang_file = 'zm_lang_'.ZM_LANG_DEFAULT.'.php';

if ( isset($user['Language']) )
{
	$user_lang_file = 'zm_lang_'.$user['Language'].'.php';
}

if ( isset($user_lang_file) && file_exists( $user_lang_file ) )
{
	$lang_file = $user_lang_file;
}
elseif ( file_exists( $system_lang_file ) )
{
	$lang_file = $system_lang_file;
}
else
{
	$lang_file = $fallback_lang_file;
}

require_once( $lang_file );

?>
