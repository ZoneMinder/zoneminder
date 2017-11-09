<?php

require_once('database.php');

class MontageLayout {

  private $defaults = array(
    'Id' => null,
    'Name' => '',
    'Positions' => 0,
  );

  public function __construct( $IdOrRow = NULL ) {
    if ( $IdOrRow ) {
      $row = NULL;
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM MontageLayouts WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load MontageLayout record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        Error("Unknown argument passed to MontageLayout Constructor ($IdOrRow)");
        return;
      }

      if ( $row ) {
        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
      } else {
        Error('No row for MontageLayout ' . $IdOrRow );
      }
    } # end if isset($IdOrRow)
  } // end function __construct

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
        Warning( "Unknown function call MontageLayout->$fn from $file:$line" );
      }
    }
  }

  public function set( $data ) {
    foreach ($data as $k => $v) {
      if ( is_array( $v ) ) {
        # perhaps should turn into a comma-separated string
        $this->{$k} = implode(',',$v);
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
  public static function find( $parameters = null, $options = null ) {
    $filters = array();
    $sql = 'SELECT * FROM MontageLayouts ';
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
    if ( $result ) {
      $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'MontageLayout');
      foreach ( $results as $row => $obj ) {
        $filters[] = $obj;
      }
    }
    return $filters;
  }
  public function save( $new_values = null ) {
    if ( $new_values ) {
      foreach ( $new_values as $k=>$v ) {
        $this->{$k} = $v;
      }
    }

    $fields = array_values( array_filter( array_keys($this->defaults), function($field){return $field != 'Id';} ) );
    $values = null; 
    if ( isset($this->{'Id'}) ) {
      $sql = 'UPDATE MontageLayouts SET '.implode(', ', array_map( function($field) {return $field.'=?';}, $fields ) ) . ' WHERE Id=?';
      $values = array_map( function($field){return $this->{$field};}, $fields );
      $values[] = $this->{'Id'};
    dbQuery( $sql, $values );
    } else {
      $sql = 'INSERT INTO MontageLayouts ('.implode( ',', $fields ).') VALUES ('.implode(',',array_map( function(){return '?';}, $fields ) ).')';
      $values = array_map( function($field){return $this->{$field};}, $fields );
      dbQuery( $sql, $values );
      global $dbConn;
      $this->{Id} = $dbConn->lastInsertId();
    }
  } // end function save

} // end class MontageLayout
?>
