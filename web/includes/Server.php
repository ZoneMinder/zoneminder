<?php
require_once( 'database.php' );

class Server {
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
	public static function find_all() {
		$servers = array();
		$result = dbQuery( 'SELECT * FROM Servers ORDER BY Name');
		$results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server' );
		foreach ( $results as $row => $server_obj ) {
			$servers[] = $server_obj;
		}
		return $servers;
	}

	public function Url() {
		if ( $this->Id() ) {
			return ZM_BASE_PROTOCOL . '://'. $this->Hostname();
		} else {
			return '';
		}
	}
	public function Hostname() {
		if ( isset( $this->{'Hostname'} ) and ( $this->{'Hostname'} != '' ) ) {
			return $this->{'Hostname'};
		}
		return $this->{'Name'};
	}
	public function __call( $fn, array $args= NULL){
    if( array_key_exists( $fn, $this) ) {
      return $this->{$fn};
#array_unshift($args, $this);
#call_user_func_array( $this->{$fn}, $args);
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
