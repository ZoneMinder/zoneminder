<?php
require_once('database.php');

$server_cache = array();

class Server {
  private $defaults = array(
    'Id'          => null,
    'Name'        => '',
    'Protocol'    => '',
    'Hostname'    => '',
    'Port'        =>  null,
    'PathPrefix'  => '/zm',
    'zmaudit'     => 1,
    'zmstats'     => 1,
    'zmtrigger'   => 0,
  );

  public function __construct($IdOrRow = NULL) {
    global $server_cache;
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer($IdOrRow) or ctype_digit($IdOrRow) ) {
        $row = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, array($IdOrRow));
        if ( !$row ) {
          Error('Unable to load Server record for Id='.$IdOrRow);
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
      # Set defaults
      foreach ( $this->defaults as $k => $v ) $this->{$k} = $v;
    }
  }

  public function Hostname( $new = null ) {
    if ( $new != null )
      $this->{'Hostname'} = $new;

    if ( isset( $this->{'Hostname'}) and ( $this->{'Hostname'} != '' ) ) {
      return $this->{'Hostname'};
    } else if ( $this->Id() ) {
      return $this->{'Name'};
    }
    return $_SERVER['SERVER_NAME'];
  }

  public function Protocol( $new = null ) {
    if ( $new != null )
      $this->{'Protocol'} = $new;

    if ( isset($this->{'Protocol'}) and ( $this->{'Protocol'} != '' ) ) {
      return $this->{'Protocol'};
    }
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
  }

  public function Port( $new = '' ) {
    if ( $new != '' )
      $this->{'Port'} = $new;

    if ( isset($this->{'Port'}) and $this->{'Port'} ) {
      return $this->{'Port'};
    }
    return $_SERVER['SERVER_PORT'];
  }

  public function PathToZMS( $new = null ) {
    if ( $new != null )
      $this{'PathToZMS'} = $new;
    if ( $this->Id() and $this->{'PathToZMS'} ) {
      return $this->{'PathToZMS'};
    } else {
      return ZM_PATH_ZMS;
    }
  }
  public function UrlToZMS( ) {
    return $this->Url().$this->PathToZMS();
  }

	public function Url( $port = null ) {
    $url = $this->Protocol().'://';
		if ( $this->Id() ) {
			$url .= $this->Hostname();
		} else {
			$url .= $_SERVER['SERVER_NAME'];
		}
    if ( $port ) {
      $url .= ':'.$port;
    } else {
      $url .= ':'.$this->Port();
    }
    return $url;
	}

  public function PathToIndex( $new = null ) {
    if ( $new != null )
      $this->{'PathToIndex'} = $new;

    if ( isset($this->{'PathToIndex'}) and $this->{'PathToIndex'} ) {
      return $this->{'PathToIndex'};
    }
    return $_SERVER['PHP_SELF'];
  }

  public function UrlToIndex( ) {
    return $this->Url().$this->PathToIndex();
  }

  public function __call($fn, array $args){
    if ( count($args) ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists($fn, $this) ) {
      return $this->{$fn};
    } else {
      if ( array_key_exists($fn, $this->defaults) ) {
        return $this->defaults{$fn};
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Warning("Unknown function call Server->$fn from $file:$line");
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
      return array_map(function($id){ return new Server($id); }, $results);
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

} # end class Server
?>
