<?php
require_once('database.php');
require_once('Server.php');

$monitor_cache = array();

class Monitor {

private $defaults = array(
  'Id' => null,
  'Name' => '',
  'ServerId' => 0,
  'StorageId' => 0,
  'Type'      =>  'Ffmpeg',
  'Function' => 'None',
  'Enabled' => 1,
  'LinkedMonitors' => null,
  'Triggers'  =>  null,
  'Device'  =>  '',
  'Channel' =>  0,
  'Format'  =>  '0',
  'V4LMultiBuffer'  =>  null,
  'V4LCapturesPerFrame' =>  null,
  'Protocol'  =>  null,
  'Method'  =>  '',
  'Host'  =>  null,
  'Port'  =>  '',
  'SubPath' =>  '',
  'Path'  =>  null,
  'Options' =>  null,
  'User'  =>  null,
  'Pass'  =>  null,
  // These are NOT NULL default 0 in the db, but 0 is not a valid value. FIXME
  'Width' => null,
  'Height' => null,
  'Colours' => 1,
  'Palette' =>  '0',
  'Orientation' => null,
  'Deinterlacing' =>  0,
  'SaveJPEGs' =>  3,
  'VideoWriter' =>  '0',
  'OutputCodec' =>  null,
  'OutputContainer' => null,
  'EncoderParameters' => null,
  'RecordAudio' =>  0,
  'RTSPDescribe'  =>  null,
  'Brightness'  =>  -1,
  'Contrast'    =>  -1,
  'Hue'         =>  -1,
  'Colour'      =>  -1,
  'EventPrefix' =>  'Event-',
  'LabelFormat' =>  null,
  'LabelX'      =>  0,
  'LabelY'      =>  0,
  'LabelSize'   =>  1,
  'ImageBufferCount'  =>  100,
  'WarmupCount' =>  0,
  'PreEventCount' =>  0,
  'PostEventCount'  =>  0,
  'StreamReplayBuffer'  => 0,
  'AlarmFrameCount'     =>  1,
  'SectionLength'       =>  600,
  'FrameSkip'           =>  0,
  'AnalysisFPSLimit'  =>  null,
  'AnalysisUpdateDelete'  =>  0,
  'MaxFPS' => null,
  'AlarmMaxFPS' => null,
  'FPSReportIneterval'  =>  100,
  'RefBlencPerc'        =>  6,
  'AlarmRefBlendPerc'   =>  6,
  'Controllable'        =>  0,
  'ControlId' =>  null,
  'ControlDevice' =>  null,
  'ControlAddress'  =>  null,
  'AutoStopTimeout' => null,
  'TrackMotion'     =>  0,
  'TrackDelay'      =>  null,
  'ReturnLocation'  =>  -1,
  'ReturnDelay'     =>  null,
  'DefaultView' =>  'Events',
  'DefaultRate' =>  100,
  'DefaultScale'  =>  100,
  'SignalCheckPoints' =>  0,
  'SignalCheckColour' =>  '#0000BE',
  'WebColour'   =>  'red',
  'Exif'    =>  0,
  'Sequence'  =>  null,
  'TotalEvents' =>  null,
  'TotalEventDiskSpace' =>  null,
  'HourEvents' =>  null,
  'HourEventDiskSpace' =>  null,
  'DayEvents' =>  null,
  'DayEventDiskSpace' =>  null,
  'WeekEvents' =>  null,
  'WeekEventDiskSpace' =>  null,
  'MonthEvents' =>  null,
  'MonthEventDiskSpace' =>  null,
  'ArchivedEvents' =>  null,
  'ArchivedEventDiskSpace' =>  null,
  'ZoneCount' =>  0,
  'Refresh' => null,
);
private $status_fields = array(
  'AnalysisFPS' => null,
  'CaptureFPS' => null,
  'CaptureBandwidth' => null,
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
      if ( is_integer($IdOrRow) or is_numeric($IdOrRow) ) {
        $row = dbFetchOne('SELECT * FROM Monitors WHERE Id=?', NULL, array($IdOrRow));
        if ( ! $row ) {
          Error("Unable to load Monitor record for Id=" . $IdOrRow);
        }
      } elseif ( is_array($IdOrRow) ) {
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
          $s = dbFetchOne('SELECT * FROM Controls WHERE Id=?', NULL, array($this->{'ControlId'}) );
          if ( $s ) {
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
          } else {
            Warning('No Controls found for monitor '.$this->{'Id'} . ' ' . $this->{'Name'}.' althrough it is marked as controllable');
          }
        }
        global $monitor_cache;
        $monitor_cache[$row['Id']] = $this;

      } else {
        Error('No row for Monitor ' . $IdOrRow);
      }
    } # end if isset($IdOrRow)
  } // end function __construct

  public function Server() {
    return new Server($this->{'ServerId'});
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

  public function getStreamSrc($args, $querySep='&amp;') {

    $streamSrc = $this->Server()->UrlToZMS(
      ZM_MIN_STREAMING_PORT ?
      ZM_MIN_STREAMING_PORT+$this->{'Id'} :
      null);

    $args['monitor'] = $this->{'Id'};

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $args['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
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

    $streamSrc .= '?'.http_build_query($args,'', $querySep);

    return $streamSrc;
  } // end function getStreamSrc

  public function Width($new = null) {
    if ( $new )
      $this->{'Width'} = $new;

    $field = ( $this->Orientation() == '90' or $this->Orientation() == '270' ) ? 'Height' : 'Width';
    if ( array_key_exists($field, $this) )
      return $this->{$field};
    return $this->defaults{$field};
  } // end function Width

  public function Height($new=null) {
    if ( $new )
      $this->{'Height'} = $new;

    $field = ( $this->Orientation() == '90' or $this->Orientation() == '270' ) ?  'Width' : 'Height';
    if ( array_key_exists($field, $this) )
      return $this->{$field};
    return $this->defaults{$field};
  } // end function Height

  public function set($data) {
    foreach ($data as $k => $v) {
      if ( method_exists($this, $k) ) {
        $this->{$k}($v);
      } else {
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
      } # end if method_exists
    } # end foreach $data as $k=>$v
  }
  public static function find( $parameters = null, $options = null ) {
    $sql = 'SELECT * FROM Monitors ';
    $values = array();

    if ( $parameters ) {
      $fields = array();
      $sql .= 'WHERE ';
      foreach ( $parameters as $field => $value ) {
        if ( $value == null ) {
          $fields[] = $field.' IS NULL';
        } else if ( is_array($value) ) {
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
          Error("Invalid value for limit(".$options['limit'].") passed to Control::find from $file:$line");
          return array();
        }
      }
    }
    $monitors = array();
    $result = dbQuery($sql, $values);
    $results = $result->fetchALL();
    foreach ( $results as $row ) {
      $monitors[] = new Monitor($row);
    }
    return $monitors;
  } # end find

  public static function find_one( $parameters = array() ) {
    global $monitor_cache;
    if ( 
        ( count($parameters) == 1 ) and
        isset($parameters['Id']) and
        isset($monitor_cache[$parameters['Id']]) ) {
      return $monitor_cache[$parameters['Id']];
    }
    $results = Monitor::find( $parameters, array('limit'=>1) );
    if ( ! sizeof($results) ) {
      return;
    }
    return $results[0];
  } # end find_one

  public function save($new_values = null) {

    if ( $new_values ) {
      foreach ( $new_values as $k=>$v ) {
        $this->{$k} = $v;
      }
    }
    
    $fields = array_keys($this->defaults);

    $sql = 'UPDATE Monitors SET '.implode(', ', array_map(function($field) {return $field.'=?';}, $fields )) . ' WHERE Id=?';
    $values = array_map(function($field){return $this->{$field};}, $fields);
    $values[] = $this->{'Id'};
    dbQuery($sql, $values);
  } // end function save

  function zmcControl( $mode=false ) {
    if ( (!defined('ZM_SERVER_ID')) or ( array_key_exists('ServerId', $this) and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) {
      if ( $this->{'Type'} == 'Local' ) {
        $zmcArgs = '-d '.$this->{'Device'};
      } else {
        $zmcArgs = '-m '.$this->{'Id'};
      }

      if ( $mode == 'stop' ) {
        daemonControl('stop', 'zmc', $zmcArgs);
      } else {
        if ( $mode == 'restart' ) {
          daemonControl('stop', 'zmc', $zmcArgs);
        }
        if ( $this->{'Function'} != 'None' ) {
          daemonControl('start', 'zmc', $zmcArgs);
        }
      }
    } else if ( $this->ServerId() ) {
      $Server = $this->Server();

      $url = $Server->UrlToApi().'/monitors/'.$this->{'Id'}.'.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash( ZM_AUTH_HASH_IPS );
        } elseif ( ZM_AUTH_RELAY == 'plain' ) {
          $url = '?user='.$_SESSION['username'];
          $url = '?pass='.$_SESSION['password'];
        } elseif ( ZM_AUTH_RELAY == 'none' ) {
          $url = '?user='.$_SESSION['username'];
        }
      }
      Logger::Debug("sending command to $url");
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
      try {
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ 
          Error("Error restarting zmc using $url");
        }
      } catch ( Exception $e ) {
        Error("Except $e thrown trying to restart zmc");
      }
    } else {
      Error("Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.");
    }
  } // end function zmcControl

  function zmaControl( $mode=false ) {
    if ( (!defined('ZM_SERVER_ID')) or ( array_key_exists('ServerId', $this) and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) {
      if ( $this->{'Function'} == 'None' || $this->{'Function'} == 'Monitor' || $mode == 'stop' ) {
        if ( ZM_OPT_CONTROL ) {
          daemonControl('stop', 'zmtrack.pl', '-m '.$this->{'Id'});
        }
        daemonControl('stop', 'zma', '-m '.$this->{'Id'});
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
  } // end public function zmaControl

  public function GroupIds( $new='') {
    if ( $new != '' ) {
      if(!is_array($new)) {
        $this->{'GroupIds'} = array($new);
      } else {
        $this->{'GroupIds'} = $new;
      }
    }

    if ( !array_key_exists('GroupIds', $this) ) {
      if ( array_key_exists('Id', $this) and $this->{'Id'} ) {
        $this->{'GroupIds'} = dbFetchAll('SELECT GroupId FROM Groups_Monitors WHERE MonitorId=?', 'GroupId', array($this->{'Id'}) );
        if ( ! $this->{'GroupIds'} )
          $this->{'GroupIds'} = array();
      } else {
        $this->{'GroupIds'} = array();
      }
    }
    return $this->{'GroupIds'};
  }
  public function delete() {
    $this->zmaControl('stop');
    $this->zmcControl('stop');

    // If fast deletes are on, then zmaudit will clean everything else up later
    // If fast deletes are off and there are lots of events then this step may
    // well time out before completing, in which case zmaudit will still tidy up
    if ( !ZM_OPT_FAST_DELETE ) {
      $markEids = dbFetchAll('SELECT Id FROM Events WHERE MonitorId=?', 'Id', array($this->{'Id'}));
      foreach($markEids as $markEid)
        deleteEvent($markEid);

      deletePath(ZM_DIR_EVENTS.'/'.basename($this->{'Name'}));
      deletePath(ZM_DIR_EVENTS.'/'.$this->{'Id'});
      $Storage = $this->Storage();
      if ( $Storage->Path() != ZM_DIR_EVENTS ) {
        deletePath($Storage->Path().'/'.basename($this->{'Name'}));
        deletePath($Storage->Path().'/'.$this->{'Id'});
      }
    } // end if ZM_OPT_FAST_DELETE

    // This is the important stuff
    dbQuery('DELETE FROM Zones WHERE MonitorId = ?', array($this->{'Id'}));
    if ( ZM_OPT_X10 )
      dbQuery('DELETE FROM TriggersX10 WHERE MonitorId=?', array($this->{'Id'}));
    dbQuery('DELETE FROM Monitors WHERE Id = ?', array($this->{'Id'}));

    // Deleting a Monitor does not affect the order, just creates a gap in the sequence.  Who cares?
    // fixSequences();

  } // end function delete

  public function Storage( $new = null ) {
    if ( $new ) {
      $this->{'Storage'} = $new;
    }
    if ( ! ( array_key_exists('Storage', $this) and $this->{'Storage'} ) ) {
      $this->{'Storage'} = isset($this->{'StorageId'}) ? 
        Storage::find_one(array('Id'=>$this->{'StorageId'})) : 
          new Storage(NULL);
      if ( ! $this->{'Storage'} )
        $this->{'Storage'} = new Storage(NULL);
    }
    return $this->{'Storage'};
  }

  public function Source( ) {
    $source = '';
    if ( $this->{'Type'} == 'Local' ) {
      $source = $this->{'Device'}.' ('.$this->{'Channel'}.')';
    } elseif ( $this->{'Type'} == 'Remote' ) {
      $source = preg_replace( '/^.*@/', '', $this->{'Host'} );
      if ( $this->{'Port'} != '80' and $this->{'Port'} != '554' ) {
        $source .= ':'.$this->{'Port'};
      }
    } elseif ( $this->{'Type'} == 'File' || $this->{'Type'} == 'cURL' ) {
      $source = preg_replace( '/^.*\//', '', $this->{'Path'} );
    } elseif ( $this->{'Type'} == 'Ffmpeg' || $this->{'Type'} == 'Libvlc' || $this->{'Type'} == 'WebSite' ) {
      $url_parts = parse_url( $this->{'Path'} );
      if ( ZM_WEB_FILTER_SOURCE == 'Hostname' ) {
        # Filter out everything but the hostname
        if ( isset($url_parts['host']) ) {
          $source = $url_parts['host'];
        } else {
          $source = $this->{'Path'};
        }
      } elseif ( ZM_WEB_FILTER_SOURCE == "NoCredentials" ) {
        # Filter out sensitive and common items
        unset($url_parts['user']);
        unset($url_parts['pass']);
        #unset($url_parts['scheme']);
        unset($url_parts['query']);
        #unset($url_parts['path']);
        if ( isset($url_parts['port']) and ( $url_parts['port'] == '80' or $url_parts['port'] == '554' ) )
          unset($url_parts['port']);
        $source = unparse_url($url_parts);
      } else { # Don't filter anything 
        $source = $this->{'Path'};
      }
    }
    if ( $source == '' ) {
      $source = 'Monitor ' . $this->{'Id'};
    }
    return $source;
  } // end function Source

  public function UrlToIndex() {
    return $this->Server()->UrlToIndex(ZM_MIN_STREAMING_PORT ? (ZM_MIN_STREAMING_PORT+$this->Id()) : null);
  }

} // end class Monitor
?>
