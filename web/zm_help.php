<?php
//
// ZoneMinder web help file, $Date$, $Revision$
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

//
// This file exists purely to allow language translators to override the
// default help text that accompanies configuration variables and which
// by default is in English.
//

// NOTE: This file is currently redundant and should not be shipped!!

$system_help_file = 'zm_help_'.ZM_LANG_DEFAULT.'.php';

if ( isset($user['Language']) )
{
	$user_help_file = 'zm_help_'.$user['Language'].'.php';
}

if ( isset($user_help_file) && file_exists( $user_help_file ) )
{
	$help_file = $user_help_file;
}
elseif ( file_exists( $system_help_file ) )
{
	$help_file = $system_help_file;
}

if ( $isset( $help_file ) )
{
	require_once( $help_file );
}
?>
