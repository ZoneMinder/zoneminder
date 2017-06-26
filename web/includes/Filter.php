<?php

class Filter {

public $defaults = array(
    'Id'              => null,
    'Name'            => '',
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  0,
    'AutoEmail'       =>  0,
    'AutoDelete'      =>  0,
    'AutoArchive'     =>  0,
    'AutoVideo'       =>  0,
    'AutoMessage'     =>  0,
    'Background'      =>  0,
    'Concurrent'      =>  0,
    'limit'           =>  100,
    'terms'           =>  array(),
    'sort_field'      =>  ZM_WEB_EVENT_SORT_FIELD,
    'sort_asc'        =>  (ZM_WEB_EVENT_SORT_ORDER == 'asc'),
);

  public function __construct( $IdOrRow=NULL ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM Filters WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error('Unable to load Filter record for Id=' . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
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
    }
  } // end function __construct

  public function __call( $fn, array $args ) {
    if ( count( $args )  ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
    } else if ( array_key_exists( $fn, $this->defaults ) ) {
      return $this->defaults{$fn};
    } else {

      $backTrace = debug_backtrace();
      $file = $backTrace[1]['file'];
      $line = $backTrace[1]['line'];
      Warning( "Unknown function call Filter->$fn from $file:$line" );
    }

  }

  public function terms( ) {
    if ( func_num_args( ) ) {
      $this->{'terms'} = func_get_arg(0);
    }
    if ( ! isset( $this->{'terms'} ) ) {
      if ( array_key_exists( 'Query', $this ) and $this->{'Query'} ) {
        $this->{'terms'} = jsonDecode( $this->{'Query'} );
        if ( isset( $this->{'terms'}['terms'] ) )
          $this->{'terms'} = $this->{'terms'}['terms'];
        
      } else {
        $this->{'terms'} = array();
      }
    }
    return $this->{'terms'};
  }

  public static function find_all() {
    $filters = array();
    $result = dbQuery( 'SELECT * FROM Filters ORDER BY Name');
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Filter' );
    foreach ( $results as $row => $obj ) {
      $filters[] = $obj;
    }
    return $filters;
  }

  public function delete() {
    dbQuery( 'DELETE FROM Filters WHERE Id = ?', array($this->{'Id'}) );
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
  }


} # end class

?>
