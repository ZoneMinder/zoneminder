<?php
//
// ZoneMinder WML interface file, $Date$, $Revision$
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
// Note: This is _really_ prototypical and not intended to be much
// use at present. However I'm working on a much nicer version with
// built in brower capability detection which should be much nicer.
//

ini_set( "session.name", "ZMSESSID" );
ini_set( "session.auto_start", "1" );
ini_set( "session.use_cookies", "0" );
ini_set( "session.use_trans_sid", "1" );
ini_set( "arg_separator.output", "&amp;" );
ini_set( "url_rewriter.tags", ini_get( "url_rewriter.tags" ).",card=ontimer" );

session_start();

$bandwidth = "mobile";

require_once( 'zm_config.php' );
require_once( 'zm_lang.php' );
require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

define( "WAP_COOKIES", false );

header("Content-type: text/vnd.wap.wml"); 
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
echo( '<?xml version="1.0"?>'."\n" );
echo( '<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">'."\n" );

if ( !$view )
{
	$view = "console";
}

switch( $view )
{
	case "console" :
	case "feed" :
	{
		require_once( "zm_wml_view_$view.php" );
		break;
	}
}
