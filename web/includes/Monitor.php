<?php
namespace ZM;
require_once('database.php');
require_once('Server.php');
require_once('Object.php');
require_once('Control.php');
require_once('Storage.php');

class Monitor extends ZM_Object {
  protected static $table = 'Monitors';

  protected $defaults = array(
    'Id' => null,
    'Name' => array('type'=>'text','filter_regexp'=>'/[^\w\-\.\(\)\:\/ ]/'),
    'Notes' => '',
    'ServerId' => 0,
    'StorageId' => 0,
    'Type'      => 'Ffmpeg',
    'Function'  => 'Mocord',
    'Enabled'   => array('type'=>'boolean','default'=>1),
    'LinkedMonitors' => array('type'=>'set', 'default'=>null),
    'Triggers'  =>  array('type'=>'set','default'=>''),
    'Device'  =>  '',
    'Channel' =>  0,
    'Format'  =>  '0',
    'V4LMultiBuffer'  =>  null,
    'V4LCapturesPerFrame' =>  1,
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
    'Colours' => 4,
    'Palette' =>  '0',
    'Orientation' => 'ROTATE_0',
    'Deinterlacing' =>  0,
    'DecoderHWAccelName'  =>  null,
    'DecoderHWAccelDevice'  =>  null,
    'SaveJPEGs' =>  3,
    'VideoWriter' =>  '0',
    'OutputCodec' =>  null,
    'OutputContainer' => null,
    'EncoderParameters' => "# Lines beginning with # are a comment \n# For changing quality, use the crf option\n# 1 is best, 51 is worst quality\n#crf=23\n",
    'RecordAudio' =>  array('type'=>'boolean', 'default'=>0),
    'RTSPDescribe'  =>  array('type'=>'boolean','default'=>0),
    'Brightness'  =>  -1,
    'Contrast'    =>  -1,
    'Hue'         =>  -1,
    'Colour'      =>  -1,
    'EventPrefix' =>  'Event-',
    'LabelFormat' => '%N - %d/%m/%y %H:%M:%S',
    'LabelX'      =>  0,
    'LabelY'      =>  0,
    'LabelSize'   =>  1,
    'ImageBufferCount'  =>  20,
    'WarmupCount' =>  0,
    'PreEventCount' =>  5,
    'PostEventCount'  =>  5,
    'StreamReplayBuffer'  => 0,
    'AlarmFrameCount'     =>  1,
    'SectionLength'       =>  600,
    'MinSectionLength'    =>  10,
    'FrameSkip'           =>  0,
    'MotionFrameSkip'     =>  0,
    'AnalysisFPSLimit'  =>  null,
    'AnalysisUpdateDelay'  =>  0,
    'MaxFPS' => null,
    'AlarmMaxFPS' => null,
    'FPSReportInterval'  =>  100,
    'RefBlendPerc'        =>  6,
    'AlarmRefBlendPerc'   =>  6,
    'Controllable'        =>  array('type'=>'boolean','default'=>0),
    'ControlId' =>  null,
    'ControlDevice' =>  null,
    'ControlAddress'  =>  null,
    'AutoStopTimeout' => null,
    'TrackMotion'     =>  array('type'=>'boolean','default'=>0),
    'TrackDelay'      =>  null,
    'ReturnLocation'  =>  -1,
    'ReturnDelay'     =>  null,
    'DefaultRate' =>  100,
    'DefaultScale'  =>  100,
    'SignalCheckPoints' =>  0,
    'SignalCheckColour' =>  '#0000BE',
    'WebColour'   =>  '#ff0000',
    'Exif'    =>  array('type'=>'boolean','default'=>0),
    'Sequence'  =>  null,
    'TotalEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'TotalEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'HourEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'HourEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'DayEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'DayEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'WeekEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'WeekEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'MonthEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'MonthEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'ArchivedEvents' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'ArchivedEventDiskSpace' =>  array('type'=>'integer', 'default'=>null, 'do_not_update'=>1),
    'ZoneCount' =>  0,
    'Refresh' => null,
    'DefaultCodec'  => 'auto',
    'GroupIds'    => array('default'=>array(), 'do_not_update'=>1),
  );
  private $status_fields = array(
    'Status'  =>  null,
    'AnalysisFPS' => null,
    'CaptureFPS' => null,
    'CaptureBandwidth' => null,
  );

  public function Control() {
    if ( !property_exists($this, 'Control') ) {
      if ( $this->ControlId() )
        $this->{'Control'} = Control::find_one(array('Id'=>$this->{'ControlId'}));

      if ( !(property_exists($this, 'Control') and $this->{'Control'}) )
        $this->{'Control'} = new Control();
    }
    return $this->{'Control'};
  }

  public function Server() {
    if ( !property_exists($this, 'Server') ) {
      if ( $this->ServerId() ) 
        $this->{'Server'} = Server::find_one(array('Id'=>$this->{'ServerId'}));
      if ( !property_exists($this, 'Server') ) {
        $this->{'Server'} = new Server();
      }
    }
    return $this->{'Server'};
  }

  public function __call($fn, array $args){
    if ( count($args) ) {
      if ( is_array($this->defaults[$fn]) and $this->defaults[$fn]['type'] == 'set' ) {
        $this->{$fn} = is_array($args[0]) ? implode(',',$args[0]) : $args[0];
      } else {
        $this->{$fn} = $args[0];
      }
    }
    if ( property_exists($this, $fn) ) {
      return $this->{$fn};
    } else if ( array_key_exists($fn, $this->defaults) ) {
      if ( is_array($this->defaults[$fn]) ) {
        return $this->defaults[$fn]['default'];
      }
      return $this->defaults[$fn];
    } else if ( array_key_exists($fn, $this->status_fields) ) {
      $sql = 'SELECT `Status`,`CaptureFPS`,`AnalysisFPS`,`CaptureBandwidth`
        FROM `Monitor_Status` WHERE `MonitorId`=?';
      $row = dbFetchOne($sql, NULL, array($this->{'Id'}));
      if ( !$row ) {
        Error('Unable to load Monitor record for Id='.$this->{'Id'});
        foreach ( $this->status_fields as $k => $v ) {
          $this->{$k} = $v;
        }
      } else {
        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
      }
      return $this->{$fn};
    } else {
      $backTrace = debug_backtrace();
      $file = $backTrace[1]['file'];
      $line = $backTrace[1]['line'];
      Warning("Unknown function call Monitor->$fn from $file:$line");
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

    $streamSrc .= '?'.http_build_query($args, '', $querySep);

    return $streamSrc;
  } // end function getStreamSrc

	public function isPortrait() {
		return $this->ViewWidth() > $this->ViewHeight();
	}
	public function isLandscape() {
		return $this->ViewWidth() < $this->ViewHeight();
	}

  public function ViewWidth($new = null) {
    if ( $new )
      $this->{'Width'} = $new;

    $field = ( $this->Orientation() == 'ROTATE_90' or $this->Orientation() == 'ROTATE_270' ) ? 'Height' : 'Width';
    if ( property_exists($this, $field) )
      return $this->{$field};
    return $this->defaults[$field];
  } // end function Width

  public function ViewHeight($new=null) {
    if ( $new )
      $this->{'Height'} = $new;

    $field = ( $this->Orientation() == 'ROTATE_90' or $this->Orientation() == 'ROTATE_270' ) ?  'Width' : 'Height';
    if ( property_exists($this, $field) )
      return $this->{$field};
    return $this->defaults[$field];
  } // end function Height

  public function SignalCheckColour($new=null) {
    $field = 'SignalCheckColour';
    if ($new) {
      $this->{$field} = $new;
    }

    // Validate that it's a valid colour (we seem to allow color names, not just hex).
    // This also helps prevent XSS.
    if ( property_exists($this, $field) && preg_match('/^[#0-9a-zA-Z]+$/', $this->{$field})) {
      return $this->{$field};
    }
    return $this->defaults[$field];
  } // end function SignalCheckColour

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  function zmcControl( $mode=false ) {
    if ( ! $this->{'Id'} ) {
      Warning('Attempt to control a monitor with no Id');
      return;
    }
    if ( (!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) {
      if ( $this->Type() == 'Local' ) {
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

      $url = $Server->UrlToApi().'/monitors/daemonControl/'.$this->{'Id'}.'/'.$mode.'/zmc.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
        } else if ( ZM_AUTH_RELAY == 'plain' ) {
          $url .= '?user='.$_SESSION['username'];
          $url .= '?pass='.$_SESSION['password'];
        } else {
          Error('Multi-Server requires AUTH_RELAY be either HASH or PLAIN');
          return;
        }
      }
      Logger::Debug("sending command to $url");

      $context  = stream_context_create();
      try {
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ 
          Error("Error restarting zmc using $url");
        }
      } catch ( Exception $e ) {
        Error("Except $e thrown trying to restart zmc");
      }
    } else {
      Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
    }
  } // end function zmcControl

  function zmaControl($mode=false) {
    if ( ! $this->{'Id'} ) {
      Warning('Attempt to control a monitor with no Id');
      return;
    }

    if ( (!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) {
      if ( $this->{'Function'} == 'None' || $this->{'Function'} == 'Monitor' || $mode == 'stop' ) {
        if ( ZM_OPT_CONTROL ) {
          daemonControl('stop', 'zmtrack.pl', '-m '.$this->{'Id'});
        }
        daemonControl('stop', 'zma', '-m '.$this->{'Id'});
      } else {
        if ( $mode == 'restart' ) {
          if ( ZM_OPT_CONTROL ) {
            daemonControl('stop', 'zmtrack.pl', '-m '.$this->{'Id'});
          }
          daemonControl('stop', 'zma', '-m '.$this->{'Id'});
        }
        daemonControl('start', 'zma', '-m '.$this->{'Id'});
        if ( ZM_OPT_CONTROL && $this->Controllable() && $this->TrackMotion() && 
          ( $this->{'Function'} == 'Modect' || $this->{'Function'} == 'Mocord' ) ) {
          daemonControl('start', 'zmtrack.pl', '-m '.$this->{'Id'});
        }
        if ( $mode == 'reload' ) {
          daemonControl('reload', 'zma', '-m '.$this->{'Id'});
        }
      }
    } else if ( $this->ServerId() ) {
      $Server = $this->Server();

      $url = ZM_BASE_PROTOCOL . '://'.$Server->Hostname().'/zm/api/monitors/daemonControl/'.$this->{'Id'}.'/'.$mode.'/zma.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
        } else if ( ZM_AUTH_RELAY == 'plain' ) {
          $url .= '?user='.$_SESSION['username'];
          $url .= '?pass='.$_SESSION['password'];
        } else {
          Error('Multi-Server requires AUTH_RELAY be either HASH or PLAIN');
          return;
        }
      }
      Logger::Debug("sending command to $url");

      $context  = stream_context_create();
      try {
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */
          Error("Error restarting zma using $url");
        }
      } catch ( Exception $e ) {
        Error("Except $e thrown trying to restart zma");
      }
    } else {
      Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
    } // end if we are on the recording server
  } // end public function zmaControl

  public function GroupIds( $new='' ) {
    if ( $new != '' ) {
      if ( !is_array($new) ) {
        $this->{'GroupIds'} = array($new);
      } else {
        $this->{'GroupIds'} = $new;
      }
    }

    if ( !property_exists($this, 'GroupIds') ) {
      if ( property_exists($this, 'Id') and $this->{'Id'} ) {
        $this->{'GroupIds'} = dbFetchAll('SELECT `GroupId` FROM `Groups_Monitors` WHERE `MonitorId`=?', 'GroupId', array($this->{'Id'}) );
        if ( ! $this->{'GroupIds'} )
          $this->{'GroupIds'} = array();
      } else {
        $this->{'GroupIds'} = array();
      }
    }
    return $this->{'GroupIds'};
  }

  public function delete() {
    if ( ! $this->{'Id'} ) {
      Warning("Attempt to delete a monitor without id.");
      return;
    }
    $this->zmaControl('stop');
    $this->zmcControl('stop');

    // If fast deletes are on, then zmaudit will clean everything else up later
    // If fast deletes are off and there are lots of events then this step may
    // well time out before completing, in which case zmaudit will still tidy up
    if ( !ZM_OPT_FAST_DELETE ) {
      $markEids = dbFetchAll('SELECT Id FROM Events WHERE MonitorId=?', 'Id', array($this->{'Id'}));
      foreach ($markEids as $markEid)
        deleteEvent($markEid);

      if ( $this->{'Name'} )
        deletePath(ZM_DIR_EVENTS.'/'.basename($this->{'Name'}));
      deletePath(ZM_DIR_EVENTS.'/'.$this->{'Id'});
      $Storage = $this->Storage();
      if ( $Storage->Path() != ZM_DIR_EVENTS ) {
        if ( $this->{'Name'} )
          deletePath($Storage->Path().'/'.basename($this->{'Name'}));
        deletePath($Storage->Path().'/'.$this->{'Id'});
      }
    } // end if !ZM_OPT_FAST_DELETE

    // This is the important stuff
    dbQuery('DELETE FROM Zones WHERE MonitorId = ?', array($this->{'Id'}));
    if ( ZM_OPT_X10 )
      dbQuery('DELETE FROM TriggersX10 WHERE MonitorId=?', array($this->{'Id'}));
    dbQuery('DELETE FROM Monitors WHERE Id = ?', array($this->{'Id'}));
  } // end function delete

  public function Storage($new = null) {
    if ( $new ) {
      $this->{'Storage'} = $new;
    }
    if ( ! ( property_exists($this, 'Storage') and $this->{'Storage'} ) ) {
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
    } else if ( $this->{'Type'} == 'Remote' ) {
      $source = preg_replace( '/^.*@/', '', $this->{'Host'} );
      if ( $this->{'Port'} != '80' and $this->{'Port'} != '554' ) {
        $source .= ':'.$this->{'Port'};
      }
    } else if ( $this->{'Type'} == 'VNC' ) {
      $source = preg_replace( '/^.*@/', '', $this->{'Host'} );
      if ( $this->{'Port'} != '5900' ) {
        $source .= ':'.$this->{'Port'};
      }
    } else if ( $this->{'Type'} == 'Ffmpeg' || $this->{'Type'} == 'Libvlc' || $this->{'Type'} == 'WebSite' ) {
      $url_parts = parse_url( $this->{'Path'} );
      if ( ZM_WEB_FILTER_SOURCE == 'Hostname' ) {
        # Filter out everything but the hostname
        if ( isset($url_parts['host']) ) {
          $source = $url_parts['host'];
        } else {
          $source = $this->{'Path'};
        }
      } else if ( ZM_WEB_FILTER_SOURCE == 'NoCredentials' ) {
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

  public function UrlToIndex($port=null) {
    return $this->Server()->UrlToIndex($port);
    //ZM_MIN_STREAMING_PORT ? (ZM_MIN_STREAMING_PORT+$this->Id()) : null);
  }

  public function sendControlCommand($command) {
    // command is generally a command option list like --command=blah but might be just the word quit

    $options = array();
    # Convert from a command line params to an option array
    foreach ( explode(' ', $command) as $option ) {
      if ( preg_match('/--([^=]+)(?:=(.+))?/', $option, $matches) ) {
        $options[$matches[1]] = $matches[2]?$matches[2]:1;
      } else if ( $option != '' and $option != 'quit' ) {
        Warning("Ignored command for zmcontrol $option in $command");
      }
    }
    if ( !count($options) ) {

      if ( $command == 'quit' or $command == 'start' or $command == 'stop' ) {
        # These are special as we now run zmcontrol as a daemon through zmdc.
        $status = daemonStatus('zmcontrol.pl', array('--id', $this->{'Id'}));
        Logger::Debug("Current status $status");
        if ( $status or ( (!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) ) {
          daemonControl($command, 'zmcontrol.pl', '--id '.$this->{'Id'});
          return;
        }
        $options['command'] = $command;
      } else {
        Warning("No commands to send to zmcontrol from $command");
        return false;
      }
    }

    if ( (!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) ) ) {
      # Local
      Logger::Debug('Trying to send options ' . print_r($options, true));

      $optionString = jsonEncode($options);
      Logger::Debug("Trying to send options $optionString");
      // Either connects to running zmcontrol.pl or runs zmcontrol.pl to send the command.
      $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
      if ( $socket < 0 ) {
        Error('socket_create() failed: '.socket_strerror($socket));
        return false;
      }
      $sockFile = ZM_PATH_SOCKS.'/zmcontrol-'.$this->{'Id'}.'.sock';
      if ( @socket_connect($socket, $sockFile) ) {
        if ( !socket_write($socket, $optionString) ) {
          Error('Can\'t write to control socket: '.socket_strerror(socket_last_error($socket)));
          return false;
        }
      } else if ( $command != 'quit' ) {
        $command = ZM_PATH_BIN.'/zmcontrol.pl '.$command.' --id='.$this->{'Id'};

        // Can't connect so use script
        $ctrlOutput = exec(escapeshellcmd($command));
      }
      socket_close($socket);
    } else if ( $this->ServerId() ) {
      $Server = $this->Server();

      $url = ZM_BASE_PROTOCOL . '://'.$Server->Hostname().'/zm/api/monitors/daemonControl/'.$this->{'Id'}.'/'.$command.'/zmcontrol.pl.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
        } else if ( ZM_AUTH_RELAY == 'plain' ) {
          $url .= '?user='.$_SESSION['username'];
          $url .= '?pass='.$_SESSION['password'];
        } else if ( ZM_AUTH_RELAY == 'none' ) {
          $url .= '?user='.$_SESSION['username'];
        }
      }
      Logger::Debug("sending command to $url");

      $context = stream_context_create();
      try {
        $result = file_get_contents($url, false, $context);
        if ( $result === FALSE ) { /* Handle error */
          Error("Error sending command using $url");
          return false;
        }
      } catch ( Exception $e ) {
        Error("Exception $e thrown trying to send command to $url");
        return false;
      }
    } else {
      Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
      return false;
    } // end if we are on the recording server
    return true;
  } // end function sendControlCommand($mid, $command)

} // end class Monitor
?>
