<?php
//
// ZoneMinder web database interface file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

define( "DB_LOG_OFF", 0 );
define( "DB_LOG_ONLY", 1 );
define( "DB_LOG_DEBUG", 2 );

$GLOBALS['dbLogLevel'] = DB_LOG_OFF;

$GLOBALS['dbConn'] = false;

function dbConnect()
{
    global $dbConn;
    $dbConn = mysql_pconnect( ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS ) or die( "Could not connect to database: ".mysql_error() );
    mysql_select_db( ZM_DB_NAME, $dbConn ) or die( "Could not select database: ".mysql_error() );
}

dbConnect();

function dbDisconnect()
{
    global $dbConn;
    mysql_close( $dbConn ) or die( "Could not disconnect from database" );
}

function dbLogOff()
{
    global $dbLogLevel;
    $dbLogLevel = DB_LOG_OFF;
}

function dbLogOn()
{
    global $dbLogLevel;
    $dbLogLevel = DB_LOG_ONLY;
}

function dbLogDebug()
{
    global $dbLogLevel;
    $dbLogLevel = DB_LOG_DEBUG;
}

function dbDebug()
{
    dbLogDebug();
}

function dbLog( $sql, $update=false )
{
    global $dbLogLevel;
    $noExecute = $update && ($dbLogLevel >= DB_LOG_DEBUG);
    if ( $dbLogLevel > DB_LOG_OFF )
        error_log( "SQL-LOG: $sql".($noExecute?" (not executed)":"") );
    return( $noExecute );
}

function dbError( $sql )
{
    $err_ref = sprintf( "%X", rand( 0x100000, 0xffffff ) );
    error_log( "SQL-ERROR($err_ref): ".$sql );
    error_log( "SQL-ERROR($err_ref): ".mysql_error() );
    die( "An error has occurred and this operation cannot continue.<br/>For full details check your web logs for the code '$err_ref'" );
}

function dbEscape( $string )
{
    if ( version_compare( phpversion(), "4.3.0", "<") )
        if ( get_magic_quotes_gpc() )
            return( mysql_escape_string( stripslashes( $string ) ) );
        else
            return( mysql_escape_string( $string ) );
    else
        if ( get_magic_quotes_gpc() )
            return( mysql_real_escape_string( stripslashes( $string ) ) );
        else
            return( mysql_real_escape_string( $string ) );
}

function dbQuery( $sql )
{
    if ( dbLog( $sql, true ) )
        return;
    if (!($result = mysql_query( $sql )))
        dbError( $sql );
    return( $result );
}

function dbFetchOne( $sql, $col=false )
{
    dbLog( $sql );
    if (!($result = mysql_query( $sql )))
        dbError( $sql );

    if ( $dbRow = mysql_fetch_assoc( $result ) )
        return( $col?$dbRow[$col]:$dbRow );
    return( false );
}

function dbFetchAll( $sql, $col=false )
{
    dbLog( $sql );
    if (!($result = mysql_query( $sql )))
        dbError( $sql );

    $dbRows = array();
    while( $dbRow = mysql_fetch_assoc( $result ) )
        $dbRows[] = $col?$dbRow[$col]:$dbRow;
    return( $dbRows );
}

function dbFetchAssoc( $sql, $indexCol, $dataCol=false )
{
    dbLog( $sql );
    if (!($result = mysql_query( $sql )))
        dbError( $sql );

    $dbRows = array();
    while( $dbRow = mysql_fetch_assoc( $result ) )
        $dbRows[$dbRow[$indexCol]] = $dataCol?$dbRow[$dataCol]:$dbRow;
    return( $dbRows );
}

function dbFetch( $sql, $col=false )
{
    return( dbFetchAll( $sql, $col ) );
}

function dbFetchNext( $result, $col=false )
{
    if ( $dbRow = mysql_fetch_assoc( $result ) )
        return( $col?$dbRow[$col]:$dbRow );
    return( false );
}

function dbNumRows( $sql )
{
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
    $row = dbFetchOne( "describe $table $column" );
    preg_match_all( "/'([^']+)'/", $row['Type'], $matches );
    return( $matches[1] );
}

function getSetValues( $table, $column )
{
    return( getEnumValues( $table, $column ) );
}

function getUniqueValues( $table, $column, $asString=1 )
{
    $values = array();
    $sql =  "select distinct $column from $table where (not isnull($column) and $column != '') order by $column";
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( $asString )
            $values[$row[$column]] = $row[$column];
        else
            $values[] = $row[$column];
    }
    return( $values );  
}               

function getTableColumns( $table, $asString=1 )
{
    $columns = array();
    $table = dbEscape($table);
    $sql = "describe $table";
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( $asString )
            $columns[$row['Field']] = $row['Type'];
        else
            $columns[] = $row['Type'];
    }
    return( $columns );  
}               

function getTableAutoInc( $table )
{
    $sql = "show table status where Name = '".dbEscape($table)."'";
    $row = dbFetchOne( $sql );
    return( $row['Auto_increment'] );
}

function dbFetchMonitor( $mid )
{
    return( dbFetchOne( "select * from Monitors where Id = '".dbEscape($mid)."'" ) );
}

function dbFetchGroup( $gid )
{
    return( dbFetchOne( "select * from Groups where Id = '".dbEscape($gid)."'" ) );
}

?>
