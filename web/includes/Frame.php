<?php
require_once( 'database.php' );
require_once( 'Event.php' );

class Frame {
  public function __construct( $IdOrRow=null ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or ctype_digit($IdOrRow) ) {
        $row = dbFetchOne( 'SELECT * FROM Frames WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Frame record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        Error("Unknown argument passed to Frame Constructor ($IdOrRow)");
        return;
      }

      if ( $row ) {
        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
      } else {
        Error("No row for Frame " . $IdOrRow );
      }
    } # end if isset($IdOrRow)
  } // end function __construct

  public function Storage() {
    return $this->Event()->Storage();
  }

  public function Event() {
    return new Event( $this->{'EventId'} );
  }
  public function __call( $fn, array $args){
    if ( count( $args )  ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};

        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Warning( "Unknown function call Frame->$fn from $file:$line" );
    }
  }

  public function getImageSrc( $show='capture' ) {
    
    return $_SERVER['PHP_SELF'].'?view=image&fid='.$this->{'FrameId'}.'&eid='.$this->{'EventId'}.'&show='.$show;
    #return $_SERVER['PHP_SELF'].'?view=image&fid='.$this->{'Id'}.'&show='.$show.'&filename='.$this->Event()->MonitorId().'_'.$this->{'EventId'}.'_'.$this->{'FrameId'}.'.jpg';
  } // end function getImageSrc

	public static function find( $parameters = array(), $options = NULL ) {
		$sql = 'SELECT * FROM Frames';
		$values = array();
		if ( sizeof($parameters) ) {
			$sql .= ' WHERE ' . implode( ' AND ', array_map( 
				function($v){ return $v.'=?'; }, 
				array_keys( $parameters ) 
				) );
			$values = array_values( $parameters );
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
          Error("Invalid value for limit(".$options['limit'].") passed to Frame::find from $file:$line");
          return array();
        }
      }
    }

		$results = dbFetchAll($sql, NULL, $values);
		if ( $results ) {
		  return array_map( function($id){ return new Frame($id); }, $results );
		}
    return array();
	}

	public static function find_one( $parameters = array(), $options = null ) {
    $options['limit'] = 1;
	  $results = Frame::find($parameters, $options);
	  if ( ! sizeof($results) ) {
		  return;
	  }
	  return $results[0];
	}
} # end class
?>
