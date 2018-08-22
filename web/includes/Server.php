<?php
require_once('database.php');

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
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or ctype_digit( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Server record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      }
    } # end if isset($IdOrRow)
    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
    } else {
      $this->{'Name'} = '';
      $this->{'Hostname'} = '';
    }
  }
  public static function find_all( $parameters = null, $options = null ) {
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
    if ( $options and isset($options['order']) ) {
    $sql .= ' ORDER BY ' . $options['order'];
    }
    $result = dbQuery($sql, $values);
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server');
    foreach ( $results as $row => $obj ) {
      $filters[] = $obj;
    }
    return $filters;
  }

	public function Url() {
		if ( $this->Id() ) {
			return ZM_BASE_PROTOCOL . '://'. $this->Hostname();
		} else {
			return ZM_BASE_PROTOCOL . '://'. $_SERVER['SERVER_NAME'];
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

  public static function find( $parameters = array(), $limit = NULL ) {
    $sql = 'SELECT * FROM Servers';
    $values = array();
    if ( sizeof($parameters) ) {
      $sql .= ' WHERE ' . implode( ' AND ', array_map(
        function($v){ return $v.'=?'; },
        array_keys( $parameters )
        ) );
      $values = array_values( $parameters );
    }
		if ( is_integer( $limit ) or ctype_digit( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		} else {
			$backTrace = debug_backtrace();
			$file = $backTrace[1]['file'];
			$line = $backTrace[1]['line'];
			Error("Invalid value for limit($limit) passed to Server::find from $file:$line");
			return;
		}
    $results = dbFetchAll( $sql, NULL, $values );
    if ( $results ) {
      return array_map( function($id){ return new Server($id); }, $results );
    }
  }

  public static function find_one( $parameters = array() ) {
    $results = Server::find( $parameters, 1 );
    if ( ! sizeof( $results ) ) {
      return;
    }
    return $results[0];
  }

}
?>
