<?php
//
// ZoneMinder web database interface file, $Date$, $Revision$
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

$conn = mysql_pconnect( ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS ) or die("Could not connect to database: ".mysql_error());
mysql_select_db( ZM_DB_NAME, $conn) or die("Could not select database: ".mysql_error());

function simpleQuery( $sql )
{
	global $debug;

	if ( $debug )
	{
		echo "SQL:$sql<br>\n";
	}
	$result = mysql_query( $sql );
	if ( !$result )
		die( mysql_error() );
	return( $result );
}

function getEnumValues( $table, $column )
{
	$enum_values = array();
	$result = mysql_query( "DESCRIBE $table $column" );
	if ( !$result )
		die( mysql_error() );
	$row = mysql_fetch_assoc($result);
	mysql_free_result( $result );
	preg_match_all( "/'([^']+)'/", $row['Type'], $enum_matches );
	$enum_values = $enum_matches[1];
	return( $enum_values );
}

function getSetValues( $table, $column )
{
	return( getEnumValues( $table, $column ) );
}

function getUniqueValues( $table, $column, $as_string=1 )
{
	$values = array();
	$result = mysql_query( "SELECT DISTINCT $column FROM $table WHERE (NOT ISNULL($column) AND $column != '') ORDER BY $column" );
	if ( $result )
	{
		while ( $row = mysql_fetch_array($result) )
		{
			if ( $as_string )
				$values[$row[0]] = $row[0];
			else
				$values = $row[0];
		}
		mysql_free_result( $result );
	}     
	else          
	{             
		echo mysql_error();
	}                     
	return( $values );  
}               

function getTableColumns( $table, $as_string=1 )
{
	$columns = array();
	$result = mysql_query( "DESCRIBE $table" );
	if ( $result )
	{
		while( $row = mysql_fetch_assoc($result) )
		{
			if ( $as_string )
				$columns[$row[Field]] = $row[Type];
			else
				$columns[] = $row[Type];
		}
		mysql_free_result( $result );
	}     
	else          
	{             
		echo mysql_error();
	}                     
	return( $columns );  
}               
?>
