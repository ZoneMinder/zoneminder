<?php

//
// ZoneMinder database interface file, $Date$, $Revision$
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

$conn = mysql_pconnect( ZM_DB_SERVER, ZM_DB_USER, ZM_DB_PASS ) or die("Could not connect to database: ".mysql_error());
mysql_select_db( ZM_DB_NAME, $conn) or die("Could not select database: ".mysql_error());

function getEnumValues( $table, $column )
{
	$enum_values = array();
	$result = mysql_query( "DESCRIBE $table $column" );
	if ( $result )
	{
		$row = mysql_fetch_assoc($result);
		preg_match_all( "/'([^']+)'/", $row[Type], $enum_matches );
		$enum_values = $enum_matches[1];
	}
	else
	{
		echo mysql_error();
	}
	return $enum_values;
}
?>
