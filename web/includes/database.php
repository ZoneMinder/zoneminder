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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

define('DB_LOG_OFF', 0);
define('DB_LOG_ONLY', 1);
define('DB_LOG_DEBUG', 2);

$GLOBALS['dbLogLevel'] = DB_LOG_OFF;

$GLOBALS['dbConn'] = false;

function dbConnect() {
  global $dbConn;

  if ( strpos(ZM_DB_HOST, ':') ) {
    // Host variable may carry a port or socket.
    list($host, $portOrSocket) = explode(':', ZM_DB_HOST, 2);
    if ( ctype_digit($portOrSocket) ) {
      $socket = ':host='.$host . ';port='.$portOrSocket;
    } else {
      $socket = ':unix_socket='.$portOrSocket;
    }
  } else {
    $socket = ':host='.ZM_DB_HOST;
  }

  try {
    $dbOptions = null;
    if ( defined('ZM_DB_SSL_CA_CERT') and ZM_DB_SSL_CA_CERT ) {
      $dbOptions = array(
        PDO::MYSQL_ATTR_SSL_CA   => ZM_DB_SSL_CA_CERT,
        PDO::MYSQL_ATTR_SSL_KEY  => ZM_DB_SSL_CLIENT_KEY,
        PDO::MYSQL_ATTR_SSL_CERT => ZM_DB_SSL_CLIENT_CERT,
      );
      $dbConn = new PDO(ZM_DB_TYPE . $socket . ';dbname='.ZM_DB_NAME, ZM_DB_USER, ZM_DB_PASS, $dbOptions);
    } else {
      $dbConn = new PDO(ZM_DB_TYPE . $socket . ';dbname='.ZM_DB_NAME, ZM_DB_USER, ZM_DB_PASS);
    }

    $dbConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $ex) {
    echo 'Unable to connect to ZM db.' . $ex->getMessage();
    error_log('Unable to connect to ZM DB ' . $ex->getMessage());
    $dbConn = null;
  }
}

dbConnect();

function dbDisconnect() {
  global $dbConn;
  $dbConn = null;
}

function dbLogOff() {
  global $dbLogLevel;
  $dbLogLevel = DB_LOG_OFF;
}

function dbLogOn() {
  global $dbLogLevel;
  $dbLogLevel = DB_LOG_ONLY;
}

function dbLogDebug() {
  global $dbLogLevel;
  $dbLogLevel = DB_LOG_DEBUG;
}

function dbDebug() {
  dbLogDebug();
}

function dbLog($sql, $update=false) {
  global $dbLogLevel;
  $noExecute = $update && ($dbLogLevel >= DB_LOG_DEBUG);
  if ( $dbLogLevel > DB_LOG_OFF )
    ZM\Logger::Debug( "SQL-LOG: $sql".($noExecute?' (not executed)':'') );
  return( $noExecute );
}

function dbError($sql) {
  global $dbConn;
  $error = $dbConn->errorInfo();
  if ( ! $error[0] )
    return '';

  $message = "SQL-ERR '".implode("\n",$dbConn->errorInfo())."', statement was '".$sql."'";
  ZM\Error($message);
  return $message;
}

function dbEscape( $string ) {
  global $dbConn;
  if ( version_compare(phpversion(), '5.4', '<=') and get_magic_quotes_gpc() ) 
    return $dbConn->quote(stripslashes($string));
  else
    return $dbConn->quote($string);
}

function dbQuery($sql, $params=NULL) {
  global $dbConn;
  if ( dbLog($sql, true) )
    return;
  $result = NULL;
  try {
    if ( isset($params) ) {
      if ( ! $result = $dbConn->prepare($sql) ) {
        ZM\Error("SQL: Error preparing $sql: " . $pdo->errorInfo);
        return NULL;
      }

      if ( ! $result->execute($params) ) {
        ZM\Error("SQL: Error executing $sql: " . print_r($result->errorInfo(), true));
        return NULL;
      }
    } else {
      if ( defined('ZM_DB_DEBUG') ) {
				ZM\Logger::Debug("SQL: $sql values:" . ($params?implode(',',$params):''));
      }
      $result = $dbConn->query($sql);
      if ( ! $result ) {
        ZM\Error("SQL: Error preparing $sql: " . $pdo->errorInfo);
        return NULL;
      }
    }
    if ( defined('ZM_DB_DEBUG') ) {
      if ( $params )
        ZM\Logger::Debug("SQL: $sql " . implode(',',$params) . ' rows: '.$result->rowCount());
      else
        ZM\Logger::Debug("SQL: $sql: rows:" . $result->rowCount());
    }
  } catch(PDOException $e) {
    ZM\Error("SQL-ERR '".$e->getMessage()."', statement was '".$sql."' params:" . ($params?implode(',',$params):''));
    return NULL;
  }
  return $result;
}

function dbFetchOne($sql, $col=false, $params=NULL) {
  $result = dbQuery($sql, $params);
  if ( !$result ) {
    ZM\Error("SQL-ERR dbFetchOne no result, statement was '".$sql."'".($params ? 'params: ' . join(',',$params) : ''));
    return false;
  }
  if ( !$result->rowCount() ) {
    # No rows is not an error
    return false;
  }

  if ( $result && ($dbRow = $result->fetch(PDO::FETCH_ASSOC)) ) {
    if ( $col ) {
      if ( ! array_key_exists($col, $dbRow) ) {
        ZM\Warning("$col does not exist in the returned row " . print_r($dbRow, true));
        return false;
      }
      return $dbRow[$col];
    }
    return $dbRow;
  }
  return false;
}

function dbFetchAll($sql, $col=false, $params=NULL) {
  $result = dbQuery($sql, $params);
  if ( ! $result ) {
    ZM\Error("SQL-ERR dbFetchAll no result, statement was '".$sql."'".($params ? 'params: '.join(',', $params) : ''));
    return false;
  }

  $dbRows = array();
  while ( $dbRow = $result->fetch(PDO::FETCH_ASSOC) )
    $dbRows[] = $col ? $dbRow[$col] : $dbRow;
  return $dbRows;
}

function dbFetchAssoc($sql, $indexCol, $dataCol=false) {
  $result = dbQuery($sql);

  $dbRows = array();
  while( $dbRow = $result->fetch(PDO::FETCH_ASSOC) )
    $dbRows[$dbRow[$indexCol]] = $dataCol ? $dbRow[$dataCol] : $dbRow;
  return $dbRows;
}

function dbFetch($sql, $col=false) {
  return dbFetchAll($sql, $col);
}

function dbFetchNext($result, $col=false) {
	if ( !$result ) {
		ZM\Error("dbFetchNext called on null result.");
		return false;
	}
  if ( $dbRow = $result->fetch(PDO::FETCH_ASSOC) )
    return $col ? $dbRow[$col] : $dbRow;
  return false;
}

function dbNumRows( $sql ) {
  $result = dbQuery($sql);
  return $result->rowCount();
}

function dbInsertId() {
  global $dbConn;
  return $dbConn->lastInsertId();
}

function getEnumValues($table, $column) {
  $row = dbFetchOne("DESCRIBE `$table` `$column`");
  preg_match_all("/'([^']+)'/", $row['Type'], $matches);
  return $matches[1];
}

function getSetValues($table, $column) {
  return getEnumValues($table, $column);
}

function getUniqueValues($table, $column, $asString=1) {
  $values = array();
  $sql =  "SELECT DISTINCT `$column` FROM `$table` WHERE (NOT isnull(`$column`) AND `$column` != '') ORDER BY `$column`";
  foreach ( dbFetchAll($sql) as $row ) {
    if ( $asString )
      $values[$row[$column]] = $row[$column];
    else
      $values[] = $row[$column];
  }
  return $values;
}

function getTableColumns( $table, $asString=1 ) {
  $columns = array();
  $sql = "DESCRIBE `$table`";
  foreach ( dbFetchAll($sql) as $row ) {
    if ( $asString )
      $columns[$row['Field']] = $row['Type'];
    else
      $columns[] = $row['Type'];
  }
  return $columns;
}

function getTableAutoInc( $table ) {
  $row = dbFetchOne('SHOW TABLE status WHERE Name=?', NULL, array($table));
  return $row['Auto_increment'];
}

function getTableDescription( $table, $asString=1 ) {
  $columns = array();
  foreach( dbFetchAll("DESCRIBE `$table`") as $row ) {
    $desc = array(
        'name' => $row['Field'],
        'required' => ($row['Null']=='NO')?true:false,
        'default' => $row['Default'],
        'db' => $row,
        );
    if ( preg_match('/^varchar\((\d+)\)$/', $row['Type'], $matches) ) {
      $desc['type'] = 'text';
      $desc['typeAttrib'] = 'varchar';
      $desc['maxLength'] = $matches[1];
    } elseif ( preg_match('/^(\w+)?text$/', $row['Type'], $matches) ) {
      $desc['type'] = 'text';
      if ( !empty($matches[1]) )
        $desc['typeAttrib'] = $matches[1];
      switch ( $matches[1] ) {
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
          ZM\Error("Unexpected text qualifier '".$matches[1]."' found for field '".$row['Field']."' in table '".$table."'");
          break;
      }
    } elseif ( preg_match('/^(enum|set)\((.*)\)$/', $row['Type'], $matches) ) {
      $desc['type'] = 'text';
      $desc['typeAttrib'] = $matches[1];
      preg_match_all("/'([^']+)'/", $matches[2], $matches);
      $desc['values'] = $matches[1];
    } elseif ( preg_match('/^(\w+)?int\(\d+\)(?:\s+(unsigned))?$/', $row['Type'], $matches) ) {
      $desc['type'] = 'integer';
      switch ( $matches[1] ) {
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
          ZM\Error("Unexpected integer qualifier '".$matches[1]."' found for field '".$row['Field']."' in table '".$table."'");
          break;
      }
      if ( !empty($matches[1]) )
        $desc['typeAttrib'] = $matches[1];
      if ( $desc['unsigned'] = ( isset($matches[2]) && $matches[2] == 'unsigned' ) ) {
        $desc['maxValue'] += (-$desc['minValue']);
        $desc['minValue'] = 0;
      }
    } elseif ( preg_match('/^(?:decimal|numeric)\((\d+)(?:,(\d+))?\)(?:\s+(unsigned))?$/', $row['Type'], $matches) ) {
      $desc['type'] = 'fixed';
      $desc['range'] = $matches[1];
      if ( isset($matches[2]) )
        $desc['precision'] = $matches[2];
      else
        $desc['precision'] = 0;
      $desc['unsigned'] = ( isset($matches[3]) && $matches[3] == 'unsigned' );
    } elseif ( preg_match('/^(datetime|timestamp|date|time)$/', $row['Type'], $matches) ) {
      $desc['type'] = 'datetime';
      switch ( $desc['typeAttrib'] = $matches[1] ) {
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
    } else {
      ZM\Error("Can't parse database type '".$row['Type']."' found for field '".$row['Field']."' in table '".$table."'");
    }

    if ( $asString )
      $columns[$row['Field']] = $desc;
    else
      $columns[] = $desc;
  }
  return $columns;
}
?>
