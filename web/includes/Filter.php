<?php
namespace ZM;

class Filter {

public $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'Enabled'         =>  1,
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  0,
    'AutoEmail'       =>  0,
    'AutoDelete'      =>  0,
    'AutoArchive'     =>  0,
    'AutoVideo'       =>  0,
    'AutoUpload'      =>  0,
    'AutoMessage'     =>  0,
    'AutoMove'        =>  0,
    'AutoMoveTo'      =>  0,
    'UpdateDiskSpace' =>  0,
    'Background'      =>  0,
    'Concurrent'      =>  0,
    'limit'           =>  100,
    'Query'           =>  array(),
    'sort_field'      =>  ZM_WEB_EVENT_SORT_FIELD,
    'sort_asc'        =>  ZM_WEB_EVENT_SORT_ORDER,
);

  public function __construct( $IdOrRow=NULL ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer($IdOrRow) or is_numeric($IdOrRow) ) {
        $row = dbFetchOne('SELECT * FROM Filters WHERE Id=?', NULL, array($IdOrRow));
        if ( ! $row ) {
          Error('Unable to load Filter record for Id=' . $IdOrRow);
        }
      } elseif ( is_array($IdOrRow) ) {
        $row = $IdOrRow;
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Error("Unknown argument passed to Filter Constructor from $file:$line)");
        Error("Unknown argument passed to Filter Constructor ($IdOrRow)");
        return;
      }
    } # end if isset($IdOrRow)

    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
      if ( array_key_exists('Query', $this) and $this->{'Query'} ) {
        $this->{'Query'} = jsonDecode($this->{'Query'});
      } else {
        $this->{'Query'} = array();
      }
    }
  } // end function __construct

  public function __call( $fn, array $args ) {
    if ( count( $args )  ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
    } else if ( array_key_exists( $fn, $this->defaults ) ) {
      $this->{$fn} = $this->defaults{$fn};
      return $this->{$fn};
    } else {

      $backTrace = debug_backtrace();
      $file = $backTrace[1]['file'];
      $line = $backTrace[1]['line'];
      Warning( "Unknown function call Filter->$fn from $file:$line" );
    }
  }

  public function terms( ) {
    if ( func_num_args( ) ) {
      $this->Query()['terms'] = func_get_arg(0);
    }
    if ( isset( $this->Query()['terms'] ) ) {
      return $this->Query()['terms'];
    }
    return array();
  }

  // The following three fields are actually stored in the Query
  public function sort_field( ) {
    if ( func_num_args( ) ) {
      $this->Query()['sort_field'] = func_get_arg(0);
    }
    if ( isset( $this->Query()['sort_field'] ) ) {
      return $this->{'Query'}['sort_field'];
    }
    return $this->defaults{'sort_field'};
  }
  public function sort_asc( ) {
    if ( func_num_args( ) ) {
      $this->{'Query'}['sort_asc'] = func_get_arg(0);
    }
    if ( isset( $this->Query()['sort_asc'] ) ) {
      return $this->{'Query'}['sort_asc'];
    }
    return $this->defaults{'sort_asc'};
  }
  public function limit( ) {
    if ( func_num_args( ) ) {
      $this->{'Query'}['limit'] = func_get_arg(0);
    }
    if ( isset( $this->Query()['limit'] ) )
      return $this->{'Query'}['limit'];
    return $this->defaults{'limit'};
  }

  public static function find( $parameters = null, $options = null ) {
    $filters = array();
    $sql = 'SELECT * FROM Filters ';
    $values = array();

    if ( $parameters ) {
      $fields = array();
      $sql .= 'WHERE ';
      foreach ( $parameters as $field => $value ) {
        if ( $value == null ) {
          $fields[] = $field.' IS NULL';
        } else if ( is_array( $value ) ) {
          $func = function(){return '?';};
          $fields[] = $field.' IN ('.implode(',', array_map($func, $value)). ')';
          $values += $value;
        } else {
          $fields[] = $field.'=?';
          $values[] = $value;
        }
      }
      $sql .= implode(' AND ', $fields);
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
          Error("Invalid value for limit(".$options['limit'].") passed to Filter::find from $file:$line");
          return array();
        }
      }
    }
    $result = dbQuery($sql, $values);
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Filter');
    foreach ( $results as $row => $obj ) {
      $filters[] = $obj;
    }
    return $filters;
  } # end find()

  public static function find_one( $parameters = array() ) {
    $results = Filter::find($parameters, array('limit'=>1));
    if ( ! sizeof($results) ) {
      return;
    }
    return $results[0];
  } # end find_one()

  public function delete() {
    dbQuery('DELETE FROM Filters WHERE Id=?', array($this->{'Id'}));
  } # end function delete()

  public function set( $data ) {
    foreach ($data as $k => $v) {
      if ( is_array( $v ) ) {
        $this->{$k} = $v;
      } else if ( is_string( $v ) ) {
        $this->{$k} = trim( $v );
      } else if ( is_integer( $v ) ) {
        $this->{$k} = $v;
      } else if ( is_bool( $v ) ) {
        $this->{$k} = $v;
      } else {
        Error( "Unknown type $k => $v of var " . gettype( $v ) );
        $this->{$k} = $v;
      }
    }
  } # end function set

  public function control($command, $server_id=null) {
    $Servers = $server_id ? Server::find(array('Id'=>$server_id)) : Server::find();
    if ( !count($Servers) and !$server_id ) {
      # This will be the non-multi-server case
      $Servers = array(new Server());
    }
    foreach ( $Servers as $Server ) {

      if ( !defined('ZM_SERVER_ID') or !$Server->Id() or ZM_SERVER_ID==$Server->Id() ) {
        # Local
        Logger::Debug("Controlling filter locally $command for server ".$Server->Id());
        daemonControl($command, 'zmfilter.pl', '--filter_id='.$this->{'Id'});
      } else {
        # Remote case

        $url = $Server->UrlToIndex();
        if ( ZM_OPT_USE_AUTH ) {
          if ( ZM_AUTH_RELAY == 'hashed' ) {
            $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
          } else if ( ZM_AUTH_RELAY == 'plain' ) {
            $url = '?user='.$_SESSION['username'];
            $url = '?pass='.$_SESSION['password'];
          } else if ( ZM_AUTH_RELAY == 'none' ) {
            $url = '?user='.$_SESSION['username'];
          }
        }
        $url .= '&view=filter&action=control&command='.$command.'&Id='.$this->Id().'&ServerId='.$Server->Id();
        Logger::Debug("sending command to $url");
        $data = array();
        if ( defined('ZM_ENABLE_CSRF_MAGIC') ) {
          require_once( 'includes/csrf/csrf-magic.php' );
          $data['__csrf_magic'] = csrf_get_tokens();
        }

        // use key 'http' even if you send the request to https://...
        $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
          )
        );
        $context  = stream_context_create($options);
        try {
          $result = file_get_contents($url, false, $context);
          if ( $result === FALSE ) { /* Handle error */
            Error("Error restarting zmfilter.pl using $url");
          }
        } catch ( Exception $e ) {
          Error("Except $e thrown trying to restart zmfilter");
        }
      } # end if local or remote
    } # end foreach erver
  } # end function control

} # end class Filter

?>
