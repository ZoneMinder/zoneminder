<?php

class Filter {

public $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
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
    dbQuery('DELETE FROM Filters WHERE Id = ?', array($this->{'Id'}));
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
} # end class Filter

?>
