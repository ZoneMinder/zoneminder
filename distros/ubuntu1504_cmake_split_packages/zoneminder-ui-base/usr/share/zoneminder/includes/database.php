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

	try {
		$dbConn = new PDO( ZM_DB_TYPE . ':host=' . ZM_DB_HOST . ';dbname='.ZM_DB_NAME, ZM_DB_USER, ZM_DB_PASS );
	} catch(PDOException $ex ) {
		echo "Unable to connect to ZM db." . $ex->getMessage();
		$dbConn = null;
	}
}

dbConnect();

function dbDisconnect()
{
    global $dbConn;
	$dbConn = null;
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
        Debug( "SQL-LOG: $sql".($noExecute?" (not executed)":"") );
    return( $noExecute );
}

function dbError( $sql )
{
    Fatal( "SQL-ERR '".mysql_error()."', statement was '".$sql."'" );
}

function dbEscape( $string )
{
	global $dbConn;
    if ( version_compare( phpversion(), "4.3.0", "<") )
        if ( get_magic_quotes_gpc() )
            return( $dbConn->quote( stripslashes( $string ) ) );
        else
            return( $dbConn->quote( $string ) );
    else
        if ( get_magic_quotes_gpc() )
            return( $dbConn->quote( stripslashes( $string ) ) );
        else
            return( $dbConn->quote( $string ) );
}

function dbQuery( $sql, $params=NULL ) {
    global $dbConn;
    if ( dbLog( $sql, true ) )
        return;
    $result = NULL;
    try {
        if ( isset($params) ) {
            $result = $dbConn->prepare( $sql );
            $result->execute( $params );
        } else {
            $result = $dbConn->query( $sql );
        }
    } catch(PDOException $e) {
		Fatal( "SQL-ERR '".$e.getMessage()."', statement was '".$sql."'" );
    }
    return( $result );
}

function dbFetchOne( $sql, $col=false, $params=NULL )
{
	$result = dbQuery( $sql, $params );
	if ( ! $result ) {
		Fatal( "SQL-ERR dbFetchOne no result, statement was '".$sql."'" . ( $params ? 'params: ' . join(',',$params) : '' ) );
		return false;
	}

    if ( $result && $dbRow = $result->fetch( PDO::FETCH_ASSOC ) )
        return( $col?$dbRow[$col]:$dbRow );
    return( false );
}

function dbFetchAll( $sql, $col=false, $params=NULL )
{
	$result = dbQuery( $sql, $params );
	if ( ! $result ) {
		Fatal( "SQL-ERR dbFetchAll no result, statement was '".$sql."'" . ( $params ? 'params: ' .join(',', $params) : '' ) );
		return false;
	}

    $dbRows = array();
    while( $dbRow = $result->fetch( PDO::FETCH_ASSOC ) )
        $dbRows[] = $col?$dbRow[$col]:$dbRow;
    return( $dbRows );
}

function dbFetchAssoc( $sql, $indexCol, $dataCol=false )
{
	$result = dbQuery( $sql );

    $dbRows = array();
    while( $dbRow = $result->fetch( PDO::FETCH_ASSOC ) )
        $dbRows[$dbRow[$indexCol]] = $dataCol?$dbRow[$dataCol]:$dbRow;
    return( $dbRows );
}

function dbFetch( $sql, $col=false )
{
    return( dbFetchAll( $sql, $col ) );
}

function dbFetchNext( $result, $col=false )
{
    if ( $dbRow = $result->fetch( PDO::FETCH_ASSOC ) )
        return( $col?$dbRow[$col]:$dbRow );
    return( false );
}

function dbNumRows( $sql )
{
	$result = dbQuery( $sql );
    return( $result->rowCount() );
}

function dbInsertId()
{
	global $dbConn;
    return( $dbConn->lastInsertId() );
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
    $row = dbFetchOne( "show table status where Name=?", NULL, array($table) );
    return( $row['Auto_increment'] );
}

function getTableDescription( $table, $asString=1 )
{
    $columns = array();
    foreach( dbFetchAll( "describe $table" ) as $row )
    {
        $desc = array(
            'name' => $row['Field'],
            'required' => ($row['Null']=='NO')?true:false,
            'default' => $row['Default'],
            'db' => $row,
        );
        if ( preg_match( "/^varchar\((\d+)\)$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'text';
            $desc['typeAttrib'] = 'varchar';
            $desc['maxLength'] = $matches[1];
        }
        elseif ( preg_match( "/^(\w+)?text$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'text';
            if (!empty($matches[1]) )
                $desc['typeAttrib'] = $matches[1];
            switch ( $matches[1] )
            {
                case 'tiny' :
                    $desc['maxLength'] = 255;
                    break;
                case 'medium' :
                    $desc['maxLength'] = 32768;
                    break;
                case '' :
                case 'big' :
                    //$desc['minLength'] = -128;
                    break;
                default :
                    Error( "Unexpected text qualifier '".$matches[1]."' found for field '".$row['Field']."' in table '".$table."'" );
                    break;
            }
        }
        elseif ( preg_match( "/^(enum|set)\((.*)\)$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'text';
            $desc['typeAttrib'] = $matches[1];
            preg_match_all( "/'([^']+)'/", $matches[2], $matches );
            $desc['values'] = $matches[1];
        }
        elseif ( preg_match( "/^(\w+)?int\(\d+\)(?:\s+(unsigned))?$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'integer';
            switch ( $matches[1] )
            {
                case 'tiny' :
                    $desc['minValue'] = -128;
                    $desc['maxValue'] = 127;
                    break;
                case 'small' :
                    $desc['minValue'] = -32768;
                    $desc['maxValue'] = 32767;
                    break;
                case 'medium' :
                    $desc['minValue'] = -8388608;
                    $desc['maxValue'] = 8388607;
                    break;
                case '' :
                    $desc['minValue'] = -2147483648;
                    $desc['maxValue'] = 2147483647;
                    break;
                case 'big' :
                    //$desc['minValue'] = -128;
                    //$desc['maxValue'] = 127;
                    break;
                default :
                    Error( "Unexpected integer qualifier '".$matches[1]."' found for field '".$row['Field']."' in table '".$table."'" );
                    break;
            }
            if ( !empty($matches[1]) )
                $desc['typeAttrib'] = $matches[1];
            if ( $desc['unsigned'] = ( isset($matches[2]) && $matches[2] == 'unsigned' ) )
            {
                $desc['maxValue'] += (-$desc['minValue']);
                $desc['minValue'] = 0;
            }
        }
        elseif ( preg_match( "/^(?:decimal|numeric)\((\d+)(?:,(\d+))?\)(?:\s+(unsigned))?$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'fixed';
            $desc['range'] = $matches[1];
            if ( isset($matches[2]) )
                $desc['precision'] = $matches[2];
            else
                $desc['precision'] = 0;
            $desc['unsigned'] = ( isset($matches[3]) && $matches[3] == 'unsigned' );
        }
        elseif ( preg_match( "/^(datetime|timestamp|date|time)$/", $row['Type'], $matches ) )
        {
            $desc['type'] = 'datetime';
            switch ( $desc['typeAttrib'] = $matches[1] )
            {
                case 'datetime' :
                case 'timestamp' :
                    $desc['hasDate'] = true;
                    $desc['hasTime'] = true;
                    break;
                case 'date' :
                    $desc['hasDate'] = true;
                    $desc['hasTime'] = false;
                    break;
                case 'time' :
                    $desc['hasDate'] = false;
                    $desc['hasTime'] = true;
                    break;
            }
        }
        else
        {
            Error( "Can't parse database type '".$row['Type']."' found for field '".$row['Field']."' in table '".$table."'" );
        }

        if ( $asString )
            $columns[$row['Field']] = $desc;
        else
            $columns[] = $desc;
    }
    return( $columns );  
}               

function dbFetchMonitor( $mid )
{
    return( dbFetchOne( "select * from Monitors where Id = ?", NULL, array($mid) ) );
}

function dbFetchGroup( $gid )
{
    return( dbFetchOne( "select * from Groups where Id = ?", NULL, array($gid) ) );
}

?>
