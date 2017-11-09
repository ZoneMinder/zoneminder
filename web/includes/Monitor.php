<?php
require_once( 'database.php' );
require_once( 'Server.php' );

class Monitor {

private $defaults = array(
'Id' => null,
'Name' => '',
'StorageId' => 0,
'ServerId' => 0,
'Function' => 'None',
'Enabled' => 1,
'Width' => null,
'Height' => null,
'Orientation' => null,
'AnalysisFPSLimit'  =>  null,
);
private $control_fields = array(
  'Name' => '',
  'Type' => 'Local',
  'Protocol' => NULL,
  'CanWake' => '0',
  'CanSleep' => '0',
  'CanReset' => '0',
  'CanZoom' => '0',
  'CanAutoZoom' => '0',
  'CanZoomAbs' => '0',
  'CanZoomRel' => '0',
  'CanZoomCon' => '0',
  'MinZoomRange' => NULL,
  'MaxZoomRange' => NULL,
  'MinZoomStep' => NULL,
  'MaxZoomStep' => NULL,
  'HasZoomSpeed' => '0',
  'MinZoomSpeed' => NULL,
  'MaxZoomSpeed' => NULL,
  'CanFocus' => '0',
  'CanAutoFocus' => '0',
  'CanFocusAbs' => '0',
  'CanFocusRel' => '0',
  'CanFocusCon' => '0',
  'MinFocusRange' => NULL,
  'MaxFocusRange' => NULL,
  'MinFocusStep' => NULL,
  'MaxFocusStep' => NULL,
  'HasFocusSpeed' => '0',
  'MinFocusSpeed' => NULL,
  'MaxFocusSpeed' => NULL,
  'CanIris' => '0',
  'CanAutoIris' => '0',
  'CanIrisAbs' => '0',
  'CanIrisRel' => '0',
  'CanIrisCon' => '0',
  'MinIrisRange' => NULL,
  'MaxIrisRange' => NULL,
  'MinIrisStep' => NULL,
  'MaxIrisStep' => NULL,
  'HasIrisSpeed' => '0',
  'MinIrisSpeed' => NULL,
  'MaxIrisSpeed' => NULL,
  'CanGain' => '0',
  'CanAutoGain' => '0',
  'CanGainAbs' => '0',
  'CanGainRel' => '0',
  'CanGainCon' => '0',
  'MinGainRange' => NULL,
  'MaxGainRange' => NULL,
  'MinGainStep' => NULL,
  'MaxGainStep' => NULL,
  'HasGainSpeed' => '0',
  'MinGainSpeed' => NULL,
  'MaxGainSpeed' => NULL,
  'CanWhite' => '0',
  'CanAutoWhite' => '0',
  'CanWhiteAbs' => '0',
  'CanWhiteRel' => '0',
  'CanWhiteCon' => '0',
  'MinWhiteRange' => NULL,
  'MaxWhiteRange' => NULL,
  'MinWhiteStep' => NULL,
  'MaxWhiteStep' => NULL,
  'HasWhiteSpeed' => '0',
  'MinWhiteSpeed' => NULL,
  'MaxWhiteSpeed' => NULL,
  'HasPresets' => '0',
  'NumPresets' => '0',
  'HasHomePreset' => '0',
  'CanSetPresets' => '0',
  'CanMove' => '0',
  'CanMoveDiag' => '0',
  'CanMoveMap' => '0',
  'CanMoveAbs' => '0',
  'CanMoveRel' => '0',
  'CanMoveCon' => '0',
  'CanPan' => '0',
  'MinPanRange' => NULL,
  'MaxPanRange' => NULL,
  'MinPanStep' => NULL,
  'MaxPanStep' => NULL,
  'HasPanSpeed' => '0',
  'MinPanSpeed' => NULL,
  'MaxPanSpeed' => NULL,
  'HasTurboPan' => '0',
  'TurboPanSpeed' => NULL,
  'CanTilt' => '0',
  'MinTiltRange' => NULL,
  'MaxTiltRange' => NULL,
  'MinTiltStep'  => NULL,
  'MaxTiltStep'  => NULL,
  'HasTiltSpeed' => '0',
  'MinTiltSpeed' => NULL,
  'MaxTiltSpeed' => NULL,
  'HasTurboTilt' => '0',
  'TurboTiltSpeed' => NULL,
  'CanAutoScan' => '0',
  'NumScanPaths' => '0',
);

  public function __construct( $IdOrRow = NULL ) {
    if ( $IdOrRow ) {
      $row = NULL;
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM Monitors WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Server record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        Error("Unknown argument passed to Monitor Constructor ($IdOrRow)");
        return;
      }

      if ( $row ) {
        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
        if ( $this->{'Controllable'} ) {
          $s = dbFetchOne( 'SELECT * FROM Controls WHERE Id=?', NULL, array( $this->{'ControlId'} ) );
          foreach ($s as $k => $v) {
            if ( $k == 'Id' ) {
              continue;
# The reason for these is that the name overlaps Monitor fields.
            } else if ( $k == 'Protocol' ) {
              $this->{'ControlProtocol'} = $v;
            } else if ( $k == 'Name' ) {
              $this->{'ControlName'} = $v;
            } else if ( $k == 'Type' ) {
              $this->{'ControlType'} = $v;
            } else {
              $this->{$k} = $v;
            }
          }
        }

      } else {
        Error('No row for Monitor ' . $IdOrRow );
      }
    } # end if isset($IdOrRow)
  } // end function __construct
  public function Server() {
    return new Server( $this->{'ServerId'} );
  }
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
        Warning( "Unknown function call Monitor->$fn from $file:$line" );
      }
    }
  }

  public function getStreamSrc( $args, $querySep='&amp;' ) {
    if ( isset($this->{'ServerId'}) and $this->{'ServerId'} ) {
      $Server = new Server( $this->{'ServerId'} );
      $streamSrc = ZM_BASE_PROTOCOL.'://'.$Server->Hostname();
    } else {
      $streamSrc = ZM_BASE_URL;
    }
    if ( ZM_MIN_STREAMING_PORT )
      $streamSrc .= ':'. (ZM_MIN_STREAMING_PORT+$this->{'Id'});
    $streamSrc .= ZM_PATH_ZMS;

    $args['monitor'] = $this->{'Id'};

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $args['auth'] = generateAuthHash( ZM_AUTH_HASH_IPS );
      } elseif ( ZM_AUTH_RELAY == 'plain' ) {
        $args['user'] = $_SESSION['username'];
        $args['pass'] = $_SESSION['password'];
      } elseif ( ZM_AUTH_RELAY == 'none' ) {
        $args['user'] = $_SESSION['username'];
      }
    }
    if ( ( (!isset($args['mode'])) or ( $args['mode'] != 'single' ) ) && !empty($GLOBALS['connkey']) ) {
      $args['connkey'] = $GLOBALS['connkey'];
    }
    if ( ZM_RAND_STREAM ) {
      $args['rand'] = time();
    }

    $streamSrc .= '?'.http_build_query( $args,'', $querySep );

    return( $streamSrc );
  } // end function getStreamSrc

  public function Width( $new = null ) {
    if ( $new )
      $this->{'Width'} = $new;

    if ( $this->Orientation() == '90' or $this->Orientation() == '270' ) {
      return $this->{'Height'};
    }
    return $this->{'Width'};
  }

  public function Height( $new=null ) {
    if ( $new )
      $this->{'Height'} = $new;
    if ( $this->Orientation() == '90' or $this->Orientation() == '270' ) {
      return $this->{'Width'};
    }
    return $this->{'Height'};
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
  public static function find_all( $parameters = null, $options = null ) {
    $filters = array();
    $sql = 'SELECT * FROM Monitors ';
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
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Monitor');
    foreach ( $results as $row => $obj ) {
      $filters[] = $obj;
    }
    return $filters;
  }
  public function save( $new_values = null ) {


    if ( $new_values ) {
      foreach ( $new_values as $k=>$v ) {
        $this->{$k} = $v;
      }
    }
    
    $sql = 'UPDATE Monitors SET '.implode(', ', array_map( function($field) {return $field.'=?';}, array_keys( $this->defaults ) ) ) . ' WHERE Id=?';
    $values = array_map( function($field){return $this->{$field};}, $this->fields );
    $values[] = $this->{'Id'};
    dbQuery( $sql, $values );
  } // end function save

  function zmcControl( $mode=false ) {
    if ( (!defined('ZM_SERVER_ID')) or ( ZM_SERVER_ID==$this->{'ServerId'} ) ) {
      if ( $this->{'Type'} == 'Local' ) {
        $zmcArgs = '-d '.$this->{'Device'};
      } else {
        $zmcArgs = '-m '.$this->{'Id'};
      }

      if ( $mode == 'stop' ) {
        daemonControl( 'stop', 'zmc', $zmcArgs );
      } else {
        if ( $mode == 'restart' ) {
          daemonControl( 'stop', 'zmc', $zmcArgs );
        }
        if ( $this->{'Function'} != 'None' )
          daemonControl( 'start', 'zmc', $zmcArgs );
      }
    } else {
      $Server = $this->Server();

      $url = $Server->Url() . '/zm/api/monitors/'.$this->{'Id'}.'.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '&auth='.generateAuthHash( ZM_AUTH_HASH_IPS );
        } elseif ( ZM_AUTH_RELAY == 'plain' ) {
          $url = '&user='.$_SESSION['username'];
          $url = '&pass='.$_SESSION['password'];
        } elseif ( ZM_AUTH_RELAY == 'none' ) {
          $url = '&user='.$_SESSION['username'];
        }
      }
      $data = array('Monitor[Function]' => $this->{'Function'} );

      // use key 'http' even if you send the request to https://...
      $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
            )
          );
      $context  = stream_context_create($options);
      $result = file_get_contents($url, false, $context);
      if ($result === FALSE) { /* Handle error */ }
    }
  } // end function zmcControl
  function zmaControl( $mode=false ) {
    if ( (!defined('ZM_SERVER_ID')) or ( ZM_SERVER_ID==$this->{'ServerId'} ) ) {
      if ( $this->{'Function'} == 'None' || $this->{'Function'} == 'Monitor' || $mode == 'stop' ) {
        if ( ZM_OPT_CONTROL ) {
          daemonControl( 'stop', 'zmtrack.pl', '-m '.$this->{'Id'} );
        }
        daemonControl( 'stop', 'zma', '-m '.$this->{'Id'} );
      } else {
        if ( $mode == 'restart' ) {
          if ( ZM_OPT_CONTROL ) {
            daemonControl( 'stop', 'zmtrack.pl', '-m '.$this->{'Id'} );
          }
          daemonControl( 'stop', 'zma', '-m '.$this->{'Id'} );
        }
        daemonControl( 'start', 'zma', '-m '.$this->{'Id'} );
        if ( ZM_OPT_CONTROL && $this->{'Controllable'} && $this->{'TrackMotion'} && ( $this->{'Function'} == 'Modect' || $this->{'Function'} == 'Mocord' ) ) {
          daemonControl( 'start', 'zmtrack.pl', '-m '.$this->{'Id'} );
        }
        if ( $mode == 'reload' ) {
          daemonControl( 'reload', 'zma', '-m '.$this->{'Id'} );
        }
      }
    } // end if we are on the recording server
  }
} // end class Monitor
?>
