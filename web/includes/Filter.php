<?php

class Filter {
  public function __construct( $IdOrRow ) {
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
    } else {
      Error('No row for Filter ' . $IdOrRow );
    }
  } // end function __construct

  public function __call( $fn, array $args){
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
#array_unshift($args, $this);
#call_user_func_array( $this->{$fn}, $args);
    }
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

} # end class

?>
