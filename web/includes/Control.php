<?php
require_once( 'database.php' );
require_once( 'Server.php' );

class Control {

private $defaults = array(
    'CanMove' => 0,
    'CanMoveDiag' => 0,
    'CanMoveMap' => 0,
    'CanMoveAbs' => 0,
    'CanMoveRel' => 0,
    'CanMoveCon' => 0,
    'CanPan' => 0,
    'CanReset' => 0,
    'CanSleep' => 0,
    'CanWake' => 0,
    'MinPanRange' => NULL,
    'MaxPanRange' => NULL,
    'MinPanStep' => NULL,
    'MaxPanStep' => NULL,
    'HasPanSpeed' => 0,
    'MinPanSpeed' => NULL,
    'MaxPanSpeed' => NULL,
    'HasTurboPan' => 0,
    'TurboPanSpeed' => NULL,
    'CanTilt' => 0,
    'MinTiltRange' => NULL,
    'MaxTiltRange' => NULL,
    'MinTiltStep' => NULL,
    'MaxTiltStep' => NULL,
    'HasTiltSpeed' => 0,
    'MinTiltSpeed' => NULL,
    'MaxTiltSpeed' => NULL,
    'HasTurboTilt' => 0,
    'TurboTiltSpeed' => NULL,
    'CanZoom' => 0,
    'CanZoomAbs' => 0,
    'CanZoomRel' => 0,
    'CanZoomCon' => 0,
    'MinZoomRange' => NULL,
    'MaxZoomRange' => NULL,
    'MinZoomStep' => NULL,
    'MaxZoomStep' => NULL,
    'HasZoomSpeed' => 0,
    'MinZoomSpeed' => NULL,
    'MaxZoomSpeed' => NULL,
    'CanFocus' => 0,
    'CanAutoFocus' => 0,
    'CanFocusAbs' => 0,
    'CanFocusRel' => 0,
    'CanFocusCon' => 0,
    'MinFocusRange' => NULL,
    'MaxFocusRange' => NULL,
    'MinFocusStep' => NULL,
    'MaxFocusStep' => NULL,
    'HasFocusSpeed' => 0,
    'MinFocusSpeed' => NULL,
    'MaxFocusSpeed' => NULL,
    'CanIris' => 0,
    'CanAutoIris' => 0,
    'CanIrisAbs' => 0,
    'CanIrisRel' => 0,
    'CanIrisCon' => 0,
    'MinIrisRange' => NULL,
    'MaxIrisRange' => NULL,
    'MinIrisStep' => NULL,
    'MaxIrisStep' => NULL,
    'HasIrisSpeed' => 0,
    'MinIrisSpeed' => NULL,
    'MaxIrisSpeed' => NULL, 
    'CanGain' => 0,
    'CanAutoGain' => 0,
    'CanGainAbs' => 0,
    'CanGainRel' => 0,
    'CanGainCon' => 0,
    'MinGainRange' => NULL,
    'MaxGainRange' => NULL, 
    'MinGainStep' => NULL,
    'MaxGainStep' => NULL,
    'HasGainSpeed' => 0,
    'MinGainSpeed' => NULL,
    'MaxGainSpeed' => NULL,
    'CanWhite' => 0,
    'CanAutoWhite' => 0,
    'CanWhiteAbs' => 0,
    'CanWhiteRel' => 0,
    'CanWhiteCon' => 0, 
    'MinWhiteRange' => NULL,
    'MaxWhiteRange' => NULL,
    'MinWhiteStep' => NULL,
    'MaxWhiteStep' => NULL,
    'HasWhiteSpeed' => 0, 
    'MinWhiteSpeed' => NULL,
    'MaxWhiteSpeed' => NULL,
    'HasPresets' => 0,
    'NumPresets' => 0,
    'HasHomePreset' => 0,
    'CanSetPresets' => 0,
    'Name' => 'New',
    'Type' => 'Local',
    'Protocol' => NULL
    );

  public function __construct( $IdOrRow = NULL ) {
    if ( $IdOrRow ) {
      $row = NULL;
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM Controls WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Control record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        Error("Unknown argument passed to Control Constructor ($IdOrRow)");
        return;
      }

      if ( $row ) {
        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
      } else {
        Error('No row for Control ' . $IdOrRow );
      }
    } # end if isset($IdOrRow)
  } // end function __construct

  public function __call($fn, array $args){
    if ( count($args) ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists($fn, $this) ) {
      return $this->{$fn};
        #array_unshift($args, $this);
        #call_user_func_array( $this->{$fn}, $args);
		} else {
      if ( array_key_exists($fn, $this->control_fields) ) {
        return $this->control_fields{$fn};
      } else if ( array_key_exists( $fn, $this->defaults ) ) {
        return $this->defaults{$fn};
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Warning( "Unknown function call Control->$fn from $file:$line" );
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
    $sql = 'SELECT * FROM Controls ';
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
          Error("Invalid value for limit(".$options['limit'].") passed to Control::find from $file:$line");
          return;
        }
      }
    }
    $controls = array();
    $result = dbQuery($sql, $values);
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Control');
    foreach ( $results as $row => $obj ) {
      $controls[] = $obj;
    }
    return $controls;
  }

  public static function find_one( $parameters = array() ) {
    $results = Control::find( $parameters, array('limit'=>1) );
    if ( ! sizeof($results) ) {
      return;
    }
    return $results[0];
  }

  public function save( $new_values = null ) {

    if ( $new_values ) {
      foreach ( $new_values as $k=>$v ) {
        $this->{$k} = $v;
      }
    }
    // Set default values
    foreach ( $this->defaults as $k=>$v ) {
      if ( ( ! array_key_exists( $k, $this ) ) or ( $this->{$k} == '' ) ) {
        $this->{$k} = $v;
      }
    }
    
    $fields = array_keys( $this->defaults );

    if ( array_key_exists( 'Id', $this ) ) {
      $sql = 'UPDATE Controls SET '.implode(', ', array_map( function($field) {return $field.'=?';}, $fields ) ) . ' WHERE Id=?';
      $values = array_map( function($field){return $this->{$field};}, $fields );
      $values[] = $this->{'Id'};
      dbQuery( $sql, $values );
    } else {
      $sql = 'INSERT INTO Controls SET '.implode(', ', array_map( function($field) {return $field.'=?';}, $fields ) ) . '';
      $values = array_map( function($field){return $this->{$field};}, $fields );
      dbQuery( $sql, $values );
      $this->{'Id'} = dbInsertId();
    }
  } // end function save

} // end class Control
?>
