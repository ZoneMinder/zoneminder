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

$db_debug = false;
$db_log = false;

function dbConnect()
{
    global $db_conn;
    $db_conn = mysql_pconnect( ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS ) or die("Could not connect to database: ".mysql_error());
    mysql_select_db( ZM_DB_NAME, $db_conn) or die("Could not select database: ".mysql_error());
}

dbConnect();

function dbDebug( $sql )
{
    global $db_debug;

    if ( $db_debug )
        error_log( "SQL-DEBUG: $sql" );
    return( $db_debug );
}

function dbLog( $sql )
{
    global $db_log;

    if ( $db_log )
        error_log( "SQL-LOG:$sql" );
    return( $db_log );
}

function dbError( $sql )
{
    $err_ref = sprintf( "%X", rand( 0x100000, 0xffffff ) );
    error_log( "SQL-ERROR($err_ref): ".$sql );
    error_log( "SQL-ERROR($err_ref): ".mysql_error() );
    die( "An error has occurred and this operation cannot continue.<br>For full details check your web logs for the code '$err_ref'" );
}

function dbEscape( $string )
{
    if ( version_compare( phpversion(), "4.3.0", "<") )
        return( mysql_escape_string( $string ) );
    else
        return( mysql_real_escape_string( $string ) );
}

function dbQuery( $sql )
{
    if ( dbDebug( $sql ) )
        return;
    dbLog( $sql );
    if (!($result = mysql_query( $sql )))
        dbError( $sql );
    return( $result );
}

function dbFetchOne( $sql, $col=false )
{
    dbDebug( $sql );
    dbLog( $sql );

    if (!($result = mysql_query( $sql )))
        dbError( $sql );

    $db_row = mysql_fetch_assoc( $result );
    return( $col?$db_row[$col]:$db_row );
}

function dbFetchAll( $sql, $col=false )
{
    dbDebug( $sql );
    dbLog( $sql );

    if (!($result = mysql_query( $sql )))
        dbError( $sql );

    $db_rows = array();
    while( $db_row = mysql_fetch_assoc( $result ) )
        $db_rows[] = $col?$db_row[$col]:$db_row;
    return( $db_rows );
}

function dbFetch( $sql, $col=false )
{
    return( dbFetchAll( $sql, $col ) );
}

function dbFetchNext( $result, $col=false )
{
    $db_row = mysql_fetch_assoc( $result );
    return( $col?$db_row[$col]:$db_row );
}

function dbNumRows( $sql )
{
    dbDebug( $sql );
    dbLog( $sql );
    if (!($result = mysql_query( $sql )))
        dbError( $sql );
    return( mysql_num_rows( $result ) );
}

function dbInsertId()
{
    return( mysql_insert_id() );
}

function getEnumValues( $table, $column )
{
	$enum_values = array();
	$row = dbFetchOne( "DESCRIBE $table $column" );
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
	$sql =  "SELECT DISTINCT $column FROM $table WHERE (NOT ISNULL($column) AND $column != '') ORDER BY $column";
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( $as_string )
            $values[$row[0]] = $row[0];
        else
            $values = $row[0];
    }
	return( $values );  
}               

function getTableColumns( $table, $as_string=1 )
{
	$columns = array();
	$sql = "DESCRIBE $table";
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( $as_string )
            $columns[$row[Field]] = $row[Type];
        else
            $columns[] = $row[Type];
    }
	return( $columns );  
}               

function dbFetchMonitor( $mid )
{
    return( dbFetchOne( "select * from Monitors where Id = '$mid'" ) );
}

function dbFetchGroup( $gid )
{
    return( dbFetchOne( "select * from Groups where Id = '$gid'" ) );
}

?>
