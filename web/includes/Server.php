<?php
require_once('database.php');

$server_cache = array();

class Server {
  private $defaults = array(
    'Id'        =>  null,
    'Name'      =>  '',
    'Hostname'  =>  '',
    'zmaudit'   =>  1,
    'zmstats'   =>  1,
    'zmtrigger' =>  0,
  );


  public function __construct( $IdOrRow = NULL ) {
  global $server_cache;
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer($IdOrRow) or ctype_digit($IdOrRow) ) {
        $row = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, array($IdOrRow));
        if ( !$row ) {
          Error("Unable to load Server record for Id=" . $IdOrRow);
        }
      } elseif ( is_array($IdOrRow) ) {
        $row = $IdOrRow;
      }
    } # end if isset($IdOrRow)
    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
      $server_cache[$row['Id']] = $this;
    } else {
      $this->{'Name'} = '';
      $this->{'Hostname'} = '';
    }
  }

	public function Url() {
		if ( $this->Id() ) {
			return ZM_BASE_PROTOCOL . '://'. $this->Hostname().$_SERVER['PHP_SELF'];
		} else {
			return ZM_BASE_PROTOCOL . '://'. $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
			return '';
		}
	}
	public function Hostname() {
		if ( isset( $this->{'Hostname'} ) and ( $this->{'Hostname'} != '' ) ) {
			return $this->{'Hostname'};
		}
		return $this->{'Name'};
	}
  public function __call($fn, array $args){
    if ( count($args) ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists($fn, $this) ) {
      return $this->{$fn};
    } else {
      if ( array_key_exists( $fn, $this->defaults ) ) {
        return $this->defaults{$fn};
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Warning( "Unknown function call Server->$fn from $file:$line" );
      }
    }
  }
  public static function find( $parameters = null, $options = null ) {
    $filters = array();
    $sql = 'SELECT * FROM Servers ';
    $values = array();

    if ( $parameters ) {
      $fields = array();
      $sql .= 'WHERE ';
      foreach ( $parameters as $field => $value ) {
        if ( $value == null ) {
          $fields[] = $field.' IS NULL';
        } else if ( is_array( $value ) ) {
          $func = function(){return '?';};
          $fields[] = $field.' IN ('.implode(',', array_map( $func, $value ) ). ')';
          $values += $value;

        } else {
          $fields[] = $field.'=?';
          $values[] = $value;
        }
      }
      $sql .= implode(' AND ', $fields );
    }
    if ( $options ) {
      if ( isset($options['order']) ) {
        $sql .= ' ORDER BY ' . $options['order'];
      }
      if ( isset($options['limit']) ) {
        if ( is_integer($options['limit']) or ctype_digit($options['limit']) ) {
          $sql .= ' LIMIT ' . $options['limit'];
        } else {
          $backTrace = debug_backtrace();
          $file = $backTrace[1]['file'];
          $line = $backTrace[1]['line'];
          Error("Invalid value for limit(".$options['limit'].") passed to Server::find from $file:$line");
          return array();
        }
      }
    }
    $results = dbFetchAll( $sql, NULL, $values );
    if ( $results ) {
      return array_map( function($id){ return new Server($id); }, $results );
    }
    return array();
  }

  public static function find_one( $parameters = array() ) {
    global $server_cache;
    if ( 
        ( count($parameters) == 1 ) and
        isset($parameters['Id']) and
        isset($server_cache[$parameters['Id']]) ) {
      return $server_cache[$parameters['Id']];
    }
    $results = Server::find( $parameters, array('limit'=>1) );
    if ( ! sizeof($results) ) {
      return;
    }
    return $results[0];
  }

}
?>
