<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Control.php');
require_once('Group.php');
require_once('Manufacturer.php');
require_once('Model.php');
require_once('Server.php');
require_once('Storage.php');
require_once('Zone.php');

class Monitor extends ZM_Object {
  private $shm_id = null;
  private $connected = false;

  private $shm_offsets = ['SharedData' => [
    'size'             => [ 'type'=>'uint32', 'offset'=>0, 'size'=>4 ],
    'last_write_index' => [ 'type'=>'int32', 'offset'=>4, 'size'=>4 ],
    'last_read_index'  => [ 'type'=>'int32', 'offset'=>8, 'size'=>4 ],
    'image_count'      => [ 'type'=>'int32', 'offset'=>12, 'size'=>4 ],
    'state'            => [ 'type'=>'uint32', 'offset'=>16, 'size'=>4 ],
    'capture_fps'      => [ 'type'=>'double', 'offset'=>20, 'size'=>8 ],
    'analysis_fps'     => [ 'type'=>'double', 'offset'=>28, 'size'=>8 ],
    'latitude'         => [ 'type'=>'double', 'offset'=>36, 'size'=>8 ],
    'longitude'        => [ 'type'=>'double', 'offset'=>44, 'size'=>8 ],
    'last_event'       => [ 'type'=>'uint64', 'offset'=>52, 'size'=>8 ],
    'action'           => [ 'type'=>'uint32', 'offset'=>60, 'size'=>4 ],
    'brightness'       => [ 'type'=>'int32', 'offset'=>64, 'size'=>4 ],
    'hue'              => [ 'type'=>'int32', 'offset'=>68, 'size'=>4 ],
    'colour'           => [ 'type'=>'int32', 'offset'=>72, 'size'=>4 ],
    'contrast'         => [ 'type'=>'int32', 'offset'=>76, 'size'=>4 ],
    'alarm_x'          => [ 'type'=>'int32', 'offset'=>80, 'size'=>4 ],
    'alarm_y'          => [ 'type'=>'int32', 'offset'=>84, 'size'=>4 ],
    'valid'            => [ 'type'=>'uint8', 'offset'=>88, 'size'=>1 ],
    'capturing'        => [ 'type'=>'uint8', 'offset'=>89, 'size'=>1 ],
    'analysing'        => [ 'type'=>'uint8', 'offset'=>90, 'size'=>1 ],
    'recording'        => [ 'type'=>'uint8', 'offset'=>91, 'size'=>1 ],
    'signal'           => [ 'type'=>'uint8', 'offset'=>92, 'size'=>1 ],
    'format'           => [ 'type'=>'uint8', 'offset'=>93, 'size'=>1 ],
    'reserved1'        => [ 'type'=>'uint8', 'offset'=>94, 'size'=>1 ],
    'reserved2'        => [ 'type'=>'uint8', 'offset'=>95, 'size'=>1 ],
    'imagesize'        => [ 'type'=>'uint32', 'offset'=>96, 'size'=>4 ],
    'last_frame_score' => [ 'type'=>'uint32', 'offset'=>100, 'size'=>4 ],
    'audio_frequency'  => [ 'type'=>'uint32', 'offset'=>104, 'size'=>4 ],
    'audio_channels'   => [ 'type'=>'uint32', 'offset'=>108, 'size'=>4 ],
    'startup_time'     => [ 'type'=>'time_t64', 'offset'=>112, 'size'=>8 ],
    'heartbeat_time'   => [ 'type'=>'time_t64', 'offset'=>120, 'size'=>8 ],
    'last_write_time'  => [ 'type'=>'time_t64', 'offset'=>128, 'size'=>8 ],
    'last_read_time'   => [ 'type'=>'time_t64', 'offset'=>136, 'size'=>8 ],
    'last_viewed_time' => [ 'type'=>'time_t64', 'offset'=>144, 'size'=>8 ],
    'control_state'    => [ 'type'=>'uint8[256]', 'offset'=>152, 'size'=>256 ],
    'alarm_cause'      => [ 'type'=>'int8[256]', 'offset'=>408, 'size'=>256 ],
    'video_fifo'       => [ 'type'=>'int8[64]', 'offset'=>664, 'size'=>64 ],
    'audio_fifo'       => [ 'type'=>'int8[64]', 'offset'=>728, 'size'=>64 ],
    'janus_pin'        => [ 'type'=>'int8[64]', 'offset'=>792, 'size'=>64 ],
  ], 
  'TriggerData' => [
    'size'     => [ 'type'=>'uint32', 'offset'=>864, 'size'=>4 ],
    'state'    => [ 'type'=>'uint32', 'offset'=>868, 'size'=>4 ],
    'score'    => [ 'type'=>'uint32', 'offset'=>872, 'size'=>4 ],
    'padding'  => [ 'type'=>'uint32', 'offset'=>876, 'size'=>4 ],
    'cause'    => [ 'type'=>'int8[32]', 'offset'=>880, 'size'=>32 ],
    'text'     => [ 'type'=>'int8[256]', 'offset'=>912, 'size'=>256 ],
    'showtext' => [ 'type'=>'int8[256]', 'offset'=>1268, 'size'=>256 ],
    // 1424
  ]
  ];


  protected static $FunctionTypes = null;

  public static function getFunctionTypes() {
    if (!isset($FunctionTypes)) {
      $FunctionTypes = array(
        'None'    => translate('FnNone'),
        'Monitor' => translate('FnMonitor'),
        'Modect'  => translate('FnModect'),
        'Record'  => translate('FnRecord'),
        'Mocord'  => translate('FnMocord'),
        'Nodect'  => translate('FnNodect')
      );
    }
    return $FunctionTypes;
  }

  protected static $CapturingOptions = null;
  public static function getCapturingOptions() {
    if (!isset($CapturingOptions)) {
      $CapturingOptions = array(
        'None'=>translate('None'),
        'Ondemand'  =>  translate('On Demand'),
        'Always'    =>  translate('Always'),
      );
    }
    return $CapturingOptions;
  }

  protected static $AnalysingOptions = null;
  public static function getAnalysingOptions() {
    if (!isset($AnalysingOptions)) {
      $AnalysingOptions = array(
        'None'   => translate('None'),
        'Always' => translate('Always'),
      );
    }
    return $AnalysingOptions;
  }
  public static function getAnalysingString($option) {
    $options = Monitor::getAnalysingOptions();
    return $options[$option];
  }

  protected static $AnalysisSourceOptions = null;
  public static function getAnalysisSourceOptions() {
    if (!isset($AnalysisSourceOptions)) {
      $AnalysisSourceOptions = array(
        'Primary'   => translate('Primary'),
        'Secondary' => translate('Secondary'),
      );
    }
    return $AnalysisSourceOptions;
  }

  protected static $AnalysisImageOptions = null;
  public static function getAnalysisImageOptions() {
    if (!isset($AnalysisImageOptions)) {
      $AnalysisImageOptions = array(
        'FullColour'   => translate('Full Colour'),
        'YChannel' => translate('Y-Channel (Greyscale)'),
      );
    }
    return $AnalysisImageOptions;
  }

  protected static $RecordingOptions = null;
  public static function getRecordingOptions() {
    if (!isset($RecordingOptions)) {
      $RecordingOptions = array(
        'None'     => translate('None'),
        'OnMotion' => translate('On Motion / Trigger / etc'),
        'Always'   => translate('Always'),
      );
    }
    return $RecordingOptions;
  }

  public static function getRecordingString($option) {
    $options = Monitor::getRecordingOptions();
    return $options[$option];
  }

  protected static $RecordingSourceOptions = null;
  public static function getRecordingSourceOptions() {
    if (!isset($RecordingSourceOptions)) {
      $RecordingSourceOptions = array(
        'Primary'   => translate('Primary'),
        'Secondary' => translate('Secondary'),
        'Both'      => translate('Both'),
      );
    }
    return $RecordingSourceOptions;
  }

  protected static $DecodingOptions = null;
  public static function getDecodingOptions() {
    if (!isset($DecodingOptions)) {
      $DecodingOptions = array(
        'None'      =>  translate('None'),
        'Ondemand'  =>  translate('On Demand'),
        'KeyFrames' =>  translate('KeyFrames Only'),
        'KeyFrames+Ondemand' => translate('Keyframes + Ondemand'),
        'Always'    =>  translate('Always'),
      );
    }
    return $DecodingOptions;
  }

  protected static $Statuses = null;
  public static function getStatuses() {
    if (!isset($Statuses)) {
      $Statuses = array(
        0 => 'Unknown',
        1 => 'Idle',
        2 => 'PreAlarm',
        3 => 'Alarm',
        4 => 'Alert',
      );
    }
    return $Statuses;
  }

  public static function getStateString($option) {
    $statuses = Monitor::getStatuses();
    return $statuses[$option];
  }

  protected static $table = 'Monitors';

  protected static $RTSP2WebStreamOptions = null;
  public static function getRTSP2WebStreamOptions() {
    if (!isset($RTSP2WebStreamOptions)) {
      $RTSP2WebStreamOptions = array(
        'Primary' => translate('Primary'),
        'Secondary' => translate('Secondary')
      );
    }
    return $RTSP2WebStreamOptions;
  }

  protected $defaults = array(
    'Id' => null,
    'Name' => array('type'=>'text','filter_regexp'=>'/[^\w\-\.\(\)\:\/ ]/', 'default'=>'Monitor'),
    'Deleted' => 0,
    'Notes' => '',
    'ServerId' => 0,
    'StorageId' => 0,
    'ManufacturerId'  => null,
    'ModelId'         => null,
    'Type'      => 'Ffmpeg',
    'Capturing' => 'Always',
    'Analysing' => 'Always',
    'Recording' => 'Always',
    'RecordingSource' => 'Primary',
    'AnalysisSource' => 'Primary',
    'AnalysisImage' => 'FullColour',
    'Enabled'   => array('type'=>'boolean','default'=>1),
    'Decoding'  => 'Always',
    'RTSP2WebEnabled'   => array('type'=>'integer','default'=>0),
    'DefaultPlayer' => '',
    'RTSP2WebStream'   => 'Primary',
    'Go2RTCEnabled'   => array('type'=>'integer','default'=>0),
    'JanusEnabled'   => array('type'=>'boolean','default'=>0),
    'JanusAudioEnabled'   => array('type'=>'boolean','default'=>0),
    'Janus_Profile_Override'   => '',
    'Janus_Use_RTSP_Restream'   => array('type'=>'boolean','default'=>0),
    'Janus_RTSP_User'           => null,
    'Janus_RTSP_Session_Timeout'  => array('type'=>'integer','default'=>0),
    'LinkedMonitors' => array('type'=>'set', 'default'=>null),
    'Triggers'  =>  array('type'=>'set','default'=>''),
    'EventStartCommand' => '',
    'EventEndCommand' => '',
    'ONVIF_URL' =>  '',
    'ONVIF_Events_Path' =>  '/Events',
    'ONVIF_Username'  =>  '',
    'ONVIF_Password'  =>  '',
    'ONVIF_Options'   =>  '',
    'ONVIF_Alarm_Text'   =>  'MotionAlarm',
    'ONVIF_Event_Listener'  =>  '0',
    'use_Amcrest_API'  =>  '0',
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
    'SecondPath'  =>  null,
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
    'Decoder'  =>  '',
    'DecoderHWAccelName'  =>  null,
    'DecoderHWAccelDevice'  =>  null,
    'SaveJPEGs' =>  0,
    'VideoWriter' =>  '2',
    'OutputCodecName' =>  'auto',
    'Encoder'     =>  'auto',
    'OutputContainer' => null,
    'EncoderParameters' => '',
    'WallClockTimestamps' => array('type'=>'boolean', 'default'=>0),
    'RecordAudio' =>  array('type'=>'boolean', 'default'=>0),
    #'OutputSourceStream'  => 'Primary',
    'RTSPDescribe'  =>  array('type'=>'boolean','default'=>0),
    'Brightness'  =>  -1,
    'Contrast'    =>  -1,
    'Hue'         =>  -1,
    'Colour'      =>  -1,
    'EventPrefix' =>  'Event-',
    'LabelFormat' => '',
    'LabelX'      =>  0,
    'LabelY'      =>  0,
    'LabelSize'   =>  2,
    'ImageBufferCount'  =>  3,
    'MaxImageBufferCount'  =>  121,
    'WarmupCount' =>  0,
    'PreEventCount' =>  5,
    'PostEventCount'  =>  5,
    'StreamReplayBuffer'  => 0,
    'AlarmFrameCount'     =>  1,
    'SectionLength'       =>  600,
    'SectionLengthWarn'   =>  true,
    'MinSectionLength'    =>  10,
    'EventCloseMode'    => 'system',
    'FrameSkip'           =>  0,
    'MotionFrameSkip'     =>  0,
    'AnalysisFPSLimit'  =>  [ 'default'=>null, 'initial_default'=>2, 'type'=>'float' ],
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
    'ModectDuringPTZ' =>  array('type'=>'boolean','default'=>0),
    'DefaultRate' =>  100,
    'DefaultScale'  =>  0,
    'SignalCheckPoints' =>  0,
    'SignalCheckColour' =>  '#0000BE',
    'WebColour'   =>  '#ff0000',
    'Exif'    =>  array('type'=>'boolean', 'default'=>0),
    'Sequence'  =>  null,
    'ZoneCount' =>  0,
    'Refresh' => null,
    'DefaultCodec'  => 'auto',
    'GroupIds'    => array('default'=>array(), 'do_not_update'=>1),
    'Latitude'  =>  null,
    'Longitude' =>  null,
    'RTSPServer' => array('type'=>'boolean', 'default'=>0),
    'RTSPStreamName'  => '',
    'SOAP_wsa_compl' => array('type'=>'boolean', 'default'=>0), 
    'Importance'      =>  'Normal',
    'MQTT_Enabled'   => array('type'=>'boolean','default'=>0),
    'MQTT_Subscriptions'  =>  '',
    'StartupDelay' => 0,
    'initial_scale' => array('default'=>100, 'do_not_update'=>1),
  );
  private $status_fields = array(
    'Status'  =>  null,
    'AnalysisFPS' => null,
    'CaptureFPS' => null,
    'CaptureBandwidth' => null,
    'Capturing' => 0,
    'Analysing' => 0,
    'State'     => 0,
    'LastEventId' =>  null,
    'EventId'     =>  null,
  );
  private $summary_fields = array(
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
  );

  protected $Id;

  public function save($data = null) {
    if ($data) $this->set($data);
    if ($this->Manufacturer() and $this->Manufacturer()->Name() and ! $this->Manufacturer->Id()) {
      if ($this->Manufacturer()->save()) {
        $this->ManufacturerId = $this->Manufacturer()->Id();
      }
    }
    if ($this->Model() and $this->Model()->Name() and ! $this->Model->Id()) {
      if ($this->Model()->save(['ManufacturerId'=>$this->ManufacturerId])) {
        $this->ModelId = $this->Model()->Id();
      }
    }
    return parent::save();
  }

  public function Janus_Pin() {
    if (!$this->{'JanusEnabled'}) return '';

    if ((!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) )) {
      $cmd = getZmuCommand(' --janus-pin -m '.$this->{'Id'});
      $output = shell_exec($cmd);
      Debug("Running $cmd output: $output");
      return $output ? trim($output) : $output;
    } else if ($this->ServerId()) {
      $result = $this->Server()->SendToApi('/monitors/'.$this->{'Id'}.'.json');
      $json = json_decode($result, true);
      return ((isset($json['monitor']) and isset($json['monitor']['Monitor']) and isset($json['monitor']['Monitor']['Janus_Pin'])) ? $json['monitor']['Monitor']['Janus_Pin'] : '');
    } else {
      Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
    }
  }

  public function Control() {
    if (!property_exists($this, 'Control')) {
      if ($this->ControlId())
        $this->{'Control'} = Control::find_one(array('Id'=>$this->{'ControlId'}));

      if (!(property_exists($this, 'Control') and $this->{'Control'}))
        $this->{'Control'} = new Control();
    }
    return $this->{'Control'};
  }

  public function Server() {
    if (!property_exists($this, 'Server')) {
      if ($this->ServerId()) {
        $this->{'Server'} = Server::find_one(array('Id'=>$this->{'ServerId'}));
        if (!$this->{'Server'}) {
          $this->{'Server'} = new Server();
        }
      }
      if (!property_exists($this, 'Server')) {
        $this->{'Server'} = new Server();
      }
    }
    return $this->{'Server'};
  }

  public function Path($new=null) {
    // set the new value if requested
    if ($new !== null) {
      $this->{'Path'} = $new;
    }
    // empty value or old auth values terminate
    if (!isset($this->{'Path'}) or ($this->{'Path'}==''))
      return '';

    // extract the authentication part from the path given
    $values = extract_auth_values_from_url($this->{'Path'});

    // If no values for User and Pass fields are present then terminate
    if (count($values) !== 2) {
      return $this->{'Path'};
    }

    $old_us = isset($this->{'User'}) ? $this->{'User'} : '';
    $old_ps = isset($this->{'Pass'}) ? $this->{'Pass'} : '';
    $us = $values[0];
    $ps = $values[1];

    // Update the auth fields if they were empty and remove them from the path
    // or if they are equal between the path and field
    if ( (!$old_us && !$old_ps) || ($us == $old_us && $ps == $old_ps) ) {
      $this->{'Path'} = str_replace("$us:$ps@", '', $this->{'Path'});
      $this->{'User'} = $us;
      $this->{'Pass'} = $ps;
    }
    return $this->{'Path'};
  }

  public function User($new=null) {
    if ($new !== null) {
      // no url check if the update has different value
      $this->{'User'} = $new;
    }

    if (isset($this->{'User'}) and $this->{'User'} != '')
      return $this->{'User'};

    // Only try to update from path if the field is empty
    $values = extract_auth_values_from_url($this->Path());
    $this->{'User'} = count($values) == 2 ? $values[0] : '';
    return $this->{'User'};
  }

  public function Pass($new=null) {
    if ($new !== null) {
      // no url check if the update has different value
      $this->{'Pass'} = $new;
    }

    if (isset($this->{'Pass'}) and $this->{'Pass'} != '')
      return $this->{'Pass'};

    // Only try to update from path if the field is empty
    $values = extract_auth_values_from_url($this->Path());
    $this->{'Pass'} = count($values) == 2 ? $values[1] : '';
    return $this->{'Pass'};
  }

  public function __call($fn, array $args) {
    if (count($args)) {
      if (is_array($this->defaults[$fn]) and $this->defaults[$fn]['type'] == 'set') {
        $this->{$fn} = is_array($args[0]) ? implode(',', $args[0]) : $args[0];
      } else {
        $this->{$fn} = $args[0];
      }
    }
    if (property_exists($this, $fn)) {
      return $this->{$fn};
    } else if (array_key_exists($fn, $this->defaults)) {
      if ( is_array($this->defaults[$fn]) ) {
        return $this->defaults[$fn]['default'];
      }
      return $this->defaults[$fn];
    } else if (array_key_exists($fn, $this->status_fields)) {
      if ($this->Id()) {
        $row = $this->Monitor_Status();
        if ($row) return $row[$fn];
      } # end if this->Id
      return null;
    } else if (array_key_exists($fn, $this->summary_fields)) {
      $sql = 'SELECT * FROM `Event_Summaries` WHERE `MonitorId`=?';
      $row = dbFetchOne($sql, NULL, array($this->{'Id'}));
      if (!$row) {
        Warning('Unable to load Event Summary record for Id='.$this->{'Id'}.' using '.$sql);
        return null;
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

  public function Monitor_Status() {
    if (!property_exists($this, 'Monitor_Status')) {
      $sql = 'SELECT * FROM `Monitor_Status` WHERE `MonitorId`=?';
      $row = $this->{'Monitor_Status'} = dbFetchOne($sql, NULL, array($this->{'Id'}));
      if (!$row) Debug('Unable to load Monitor status record for Id='.$this->{'Id'}.' using '.$sql);
    }
    return $this->{'Monitor_Status'};
  }

  public function getStreamSrc($args, $querySep='&amp;') {
    $streamSrc = $this->Server()->UrlToZMS(ZM_MIN_STREAMING_PORT ? ZM_MIN_STREAMING_PORT+(int)($this->{'Id'}/5)+1 : null);

    $args['monitor'] = $this->{'Id'};

    if (ZM_OPT_USE_AUTH) {
      if (ZM_AUTH_RELAY == 'hashed') {
        $args['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
        # Include user so that db lookups can be more efficient
        $args['user'] = isset($_SESSION['username']) ? $_SESSION['username'] : '';
      } elseif ( ZM_AUTH_RELAY == 'plain' ) {
        $args['user'] = isset($_SESSION['username']) ? $_SESSION['username'] : '';
        $args['pass'] = isset($_SESSION['password']) ? $_SESSION['password'] : '';
      } elseif ( ZM_AUTH_RELAY == 'none' ) {
        $args['user'] = $_SESSION['username'];
      }
    }
    if (ZM_RAND_STREAM) {
      $args['rand'] = time();
    }

    # zms doesn't support width & height, so if no scale is set, default it
    if (!isset($args['scale'])) {
      if (isset($args['width']) and intval($args['width'])) {
        $args['scale'] = intval((100*intval($args['width']))/$this->ViewWidth());
      } else if (isset($args['height']) and intval($args['height'])) {
        $args['scale'] = intval((100*intval($args['height']))/$this->ViewHeight());
      }
    }
    if ($args['scale'] <= 0) {
      $args['scale'] = 100;
    }
    if (isset($args['width']))
      unset($args['width']);
    if (isset($args['height']))
      unset($args['height']);

    unset($args['state']);
    unset($args['zones']);

    $streamSrc .= '?'.http_build_query($args, '', $querySep);
    $this->streamSrc = $streamSrc;
    return $streamSrc;
  } // end function getStreamSrc

	public function isPortrait() {
		return $this->ViewWidth() > $this->ViewHeight();
	}
	public function isLandscape() {
		return $this->ViewWidth() < $this->ViewHeight();
	}

  public function ViewWidth($new = null) {
    if ($new)
      $this->{'Width'} = $new;

    $field = ( $this->Orientation() == 'ROTATE_90' or $this->Orientation() == 'ROTATE_270' ) ? 'Height' : 'Width';
    if (property_exists($this, $field))
      return $this->{$field};
    return $this->defaults[$field];
  } // end function Width

  public function ViewHeight($new=null) {
    if ($new)
      $this->{'Height'} = $new;

    $field = ( $this->Orientation() == 'ROTATE_90' or $this->Orientation() == 'ROTATE_270' ) ?  'Width' : 'Height';
    if (property_exists($this, $field))
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
    if (property_exists($this, $field) && preg_match('/^[#0-9a-zA-Z]+$/', $this->{$field})) {
      return $this->{$field};
    }
    return $this->defaults[$field];
  } // end function SignalCheckColour

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  function zmcControl($mode=false) {
    if (!(property_exists($this,'Id') and $this->{'Id'})) {
      Warning('Attempt to control a monitor with no Id');
      return;
    }
    if ((!defined('ZM_SERVER_ID')) or ( property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}) )) {
      if ($this->Type() == 'Local') {
        $zmcArgs = '-d '.$this->{'Device'};
      } else {
        $zmcArgs = '-m '.$this->{'Id'};
      }

      if ($mode == 'stop') {
        daemonControl('stop', 'zmc', $zmcArgs);
      } else if ($mode == 'reload') {
        daemonControl('reload', 'zmc', $zmcArgs);
      } else {
        if ($mode == 'restart') {
          daemonControl('stop', 'zmc', $zmcArgs);
        }
        if ($this->Capturing() != 'None') {
          daemonControl('start', 'zmc', $zmcArgs);
        }
      }
    } else if ($this->ServerId()) {
      $result = $this->Server()->SendToApi('/monitors/daemonControl/'.$this->{'Id'}.'/'.$mode.'/zmc.json');
    } else {
      Error('Server not assigned to Monitor '.$this->{'Id'}.' in a multi-server setup. Please assign a server to the Monitor.');
    }
  } // end function zmcControl

  public function GroupIds($new='') {
    if ($new != '') {
      if (!is_array($new)) {
        $this->{'GroupIds'} = array($new);
      } else {
        $this->{'GroupIds'} = $new;
      }
    }

    if (!property_exists($this, 'GroupIds')) {
      if ($this->Id()) {
        $this->{'GroupIds'} = dbFetchAll('SELECT `GroupId` FROM `Groups_Monitors` WHERE `MonitorId`=?', 'GroupId', [$this->Id()]);
        if (!$this->{'GroupIds'})
          $this->{'GroupIds'} = array();
      } else {
        $this->{'GroupIds'} = array();
      }
    }
    return $this->{'GroupIds'};
  }

  public function delete() {
    if ($this->Type() != 'WebSite') {
      $this->zmcControl('stop');
      if ($this->Controllable()) {
        $this->sendControlCommand('stop');
      }
    }
    $this->save(['Deleted'=>true]);
  }
  public function destroy() {
    if (!$this->{'Id'}) {
      Warning('Attempt to destroy a monitor without id.');
      return;
    }
    $this->zmcControl('stop');
    if ($this->Controllable()) {
      $this->sendControlCommand('stop');
    }

    // If fast deletes are on, then zmaudit will clean everything else up later
    // If fast deletes are off and there are lots of events then this step may
    // well time out before completing, in which case zmaudit will still tidy up
    if (!ZM_OPT_FAST_DELETE) {
      $markEids = dbFetchAll('SELECT Id FROM Events WHERE MonitorId=?', 'Id', array($this->{'Id'}));
      foreach ($markEids as $markEid)
        deleteEvent($markEid);

      if ($this->{'Name'})
        deletePath(ZM_DIR_EVENTS.'/'.basename($this->{'Name'}));
      deletePath(ZM_DIR_EVENTS.'/'.$this->{'Id'});
      $Storage = $this->Storage();
      if ($Storage->Path() != ZM_DIR_EVENTS) {
        if ($this->{'Name'})
          deletePath($Storage->Path().'/'.basename($this->{'Name'}));
        deletePath($Storage->Path().'/'.$this->{'Id'});
      }
    } // end if !ZM_OPT_FAST_DELETE

    // This is the important stuff
    dbQuery('DELETE FROM Zones WHERE MonitorId = ?', array($this->{'Id'}));
    if ( ZM_OPT_X10 )
      dbQuery('DELETE FROM TriggersX10 WHERE MonitorId=?', array($this->{'Id'}));
    dbQuery('DELETE FROM Monitor_Status WHERE MonitorId = ?', array($this->{'Id'}));
    dbQuery('DELETE FROM Event_Summaries WHERE MonitorId = ?', array($this->{'Id'}));
    dbQuery('DELETE FROM Monitors WHERE Id = ?', array($this->{'Id'}));
  } // end function destroy

  public function Storage($new = null) {
    if ($new) {
      $this->{'Storage'} = $new;
    }
    if (!(property_exists($this, 'Storage') and $this->{'Storage'})) {
      $this->{'Storage'} = isset($this->{'StorageId'}) ?
        Storage::find_one(array('Id'=>$this->{'StorageId'})) :
          new Storage(NULL);
      if (!$this->{'Storage'})
        $this->{'Storage'} = new Storage(NULL);
    }
    return $this->{'Storage'};
  }

  public function Source( ) {
    $source = '';
    if ($this->{'Type'} == 'Local') {
      $source = $this->{'Device'}.' ('.$this->{'Channel'}.')';
    } else if ($this->{'Type'} == 'Remote') {
      $source = preg_replace('/^.*@/', '', $this->{'Host'});
      if ($this->{'Port'} != '80' and $this->{'Port'} != '554') {
        $source .= ':'.$this->{'Port'};
      }
    } else if ($this->{'Type'} == 'VNC') {
      $source = preg_replace( '/^.*@/', '', $this->{'Host'} );
      if ($this->{'Port'} != '5900') {
        $source .= ':'.$this->{'Port'};
      }
    } else if ($this->{'Type'} == 'Ffmpeg' || $this->{'Type'} == 'Libvlc' || $this->{'Type'} == 'WebSite') {
      if ($this->{'Path'}) {
        $url_parts = parse_url($this->{'Path'});
        if (ZM_WEB_FILTER_SOURCE == 'Hostname') {
          # Filter out everything but the hostname
          if (isset($url_parts['host'])) {
            $source = $url_parts['host'];
          } else {
            $source = $this->{'Path'};
          }
        } else if (ZM_WEB_FILTER_SOURCE == 'NoCredentials') {
          # Filter out sensitive and common items
          unset($url_parts['user']);
          unset($url_parts['pass']);
          #unset($url_parts['scheme']);
          unset($url_parts['query']);
          #unset($url_parts['path']);
          if (isset($url_parts['port']) and ($url_parts['port'] == '80' or $url_parts['port'] == '554'))
            unset($url_parts['port']);
          $source = unparse_url($url_parts);
        } else { # Don't filter anything
          $source = $this->{'Path'};
        }
      } # end if Path
    }
    if ($source == '') {
      $source = 'Monitor ' . $this->{'Id'};
    }
    return $source;
  } // end function Source

  public function UrlToZMS($port=null) {
    return $this->Server()->UrlToZMS($port).'?monitor='.$this->Id();
  }

  public function UrlToIndex($port=null) {
    return $this->Server()->UrlToIndex($port);
  }

  public function sendControlCommand($command) {
    // command is generally a command option list like --command=blah but might be just the word quit

    $options = array();
    # Convert from a command line params to an option array
    foreach (explode(' ', $command) as $option) {
      if (preg_match('/--([^=]+)(?:=(.+))?/', $option, $matches)) {
        $options[$matches[1]] = isset($matches[2]) ? $matches[2] : 1;
      } else if ($option != '' and $option != 'quit' and $option != 'start' and $option != 'stop') {
        Warning("Ignored command for zmcontrol $option in $command");
      }
    }
    if (!count($options)) {
      if ($command == 'quit' or $command == 'start' or $command == 'stop') {
        # These are special as we now run zmcontrol as a daemon through zmdc.
        $status = daemonStatus('zmcontrol.pl', array('--id', $this->{'Id'}));
        Debug("Current status $status");
        if ($status or ((!defined('ZM_SERVER_ID')) or (property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'})))) {
          daemonControl($command, 'zmcontrol.pl', '--id '.$this->{'Id'});
          return;
        }
        $options['command'] = $command;
      } else {
        Warning('No commands to send to zmcontrol from '.$command);
        return false;
      }
    }

    if ((!defined('ZM_SERVER_ID')) or (property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}))) {
      # Local
      Debug('Trying to send options ' . print_r($options, true));

      $optionString = jsonEncode($options);
      Debug('Trying to send options '.$optionString);
      // Either connects to running zmcontrol.pl or runs zmcontrol.pl to send the command.
      $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
      if ($socket < 0) {
        Error('socket_create() failed: '.socket_strerror($socket));
        return false;
      }
      $sockFile = ZM_PATH_SOCKS.'/zmcontrol-'.$this->{'Id'}.'.sock';
      if (@socket_connect($socket, $sockFile)) {
        if (!socket_write($socket, $optionString)) {
          Error('Can\'t write to control socket: '.socket_strerror(socket_last_error($socket)));
          return false;
        }
      } else if ($command != 'quit') {
        $command = ZM_PATH_BIN.'/zmcontrol.pl '.$command.' --id '.$this->{'Id'};

        // Can't connect so use script
        $ctrlOutput = exec(escapeshellcmd($command));
      }
      socket_close($socket);
    } else if ($this->ServerId()) {
      $result = $this->Server()->SendToApi('/monitors/daemonControl/'.$this->{'Id'}.'/'.$command.'/zmcontrol.pl.json');
      return $result;
    } else {
      Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
      return false;
    } // end if we are on the recording server
    return true;
  } // end function sendControlCommand($mid, $command)

  function Groups($new='') {
    if ($new != '')
      $this->Groups = $new;
    if (!property_exists($this, 'Groups')) {
      $this->Groups = Group::find(array('Id'=>$this->GroupIds()));
    }
    return $this->Groups;
  }

  function connKey($new='') {
    if ($new)
      $this->connKey = $new;
    if (!isset($this->connKey)) {
      if (!empty($GLOBALS['connkey'])) {
        $this->connKey = $GLOBALS['connkey'];
      } else {
        $this->connKey = generateConnKey();
      }
    }
    return $this->connKey;
  }

  function canEdit($u=null) {
    global $user;
    if ($u===null or $u->Id() == $user->Id())
      return editableMonitor($this->{'Id'});

    $monitor_permission = $u->Monitor_Permission($this->{'Id'});
    if ($monitor_permission and
      ($monitor_permission->Permission() == 'None' or $monitor_permission->Permission() == 'View')) {
      Debug("Can't edit monitor ".$this->{'Id'}." because of monitor permission ".$monitor_permission->Permission());
      return false;
    }

    $group_permissions = $u->Group_Permissions();

    # If denied view in any group, then can't view it.
    foreach ($group_permissions as $permission) {
      if (!$permission->canEditMonitor($this->{'Id'})) {
        Debug("Can't edit monitor ".$this->{'Id'}." because of group ".$permision->Group()->Name().' '.$permision->Permission());
        return false;
      }
    }
    return ($u['Monitors'] == 'Edit');
  }

  function canView($u=null) {
    global $user;
    if (($u === null) or ($u->Id() == $user->Id()))
      return visibleMonitor($this->Id());

    $monitor_permission = $u->Monitor_Permission($this->{'Id'});
    if ($monitor_permission and ($monitor_permission->Permission() == 'None')) {
      Debug('Can\'t view monitor '.$this->{'Id'}.' because of monitor permission '.$monitor_permission->Permission());
      return false;
    }

    $group_permissions = $u->Group_Permissions();

    # If denied view in any group, then can't view it.
    $group_permission_value = 'Inherit';
    foreach ($group_permissions as $permission) {
      $value = $permission->MonitorPermission($this->Id());
      if ($value == 'None') {
        Debug('Can\'t view monitor '.$this->{'Id'}.' because of group '.$permission->Group()->Name().' '.$permission->Permission());
        return false;
      }
      if ($value == 'Edit' or $value == 'View') {
        $group_permission_value = $value;
      }
    }
  if ($group_permission_value != 'Inherit') return true;
    return ($u->Monitors() != 'None');
  } # end function canView

  function AlarmCommand($cmd) {
    if ((!defined('ZM_SERVER_ID')) or (property_exists($this, 'ServerId') and (ZM_SERVER_ID==$this->{'ServerId'}))) {
      switch ($cmd) {
      case 'on' : $cmd = ' -a'; break;
      case 'off': $cmd = ' -c'; break;
      case 'disable': $cmd = ' -n'; break;
      case 'status': $cmd = ' -s'; break;
      default:
        Warning("Invalid command $cmd in AlarmCommand");
        return false;
      }

      $cmd = getZmuCommand($cmd.' -m '.validCardinal($this->{'Id'}));
      $output = shell_exec($cmd);
      Debug("Running $cmd output: $output");
      return $output;
    }
    
    if ($this->ServerId()) {
      $result = $this->Server()->SendToApi('/monitors/alarm/id:'.$this->{'Id'}.'/command:'.$cmd.'.json');

      if ($result === FALSE) { /* Handle error */
        Error('Error sending command using '.$url);
        return false;
      }
      $json = json_decode($result, true);
      return $json['status'];
    } // end if we are on the recording server
    Error('Server not assigned to Monitor in a multi-server setup. Please assign a server to the Monitor.');
    return false;
  }

  function TriggerOn() {
    $output = $this->AlarmCommand('on');
    if ($output and preg_match('/Alarmed event id: (\d+)$/', $output, $matches)) {
      return $matches[1];
    }
    Warning('No event returned from TriggerOn');
  }
  function TriggerOff() {
    $output = $this->AlarmCommand('off');
  }
  function DisableAlarms() {
    $output = $this->AlarmCommand('disable');
  }
  function Model($new=-1) {
    if ($new != -1) {
      Debug("New model $new");
      $model = Model::find_one(['Name'=>$new]);
      if (!$model) {
        $model = new Model();
        $model->set(['Name'=>$new, 'ManufacturerId'=>$this->ManufacturerId()]);
        $this->Model = $model;
        if ($this->ModelId) $this->ModelId = null;
        Debug("model: " . $model->Name() . ' ' . $model->Id() . ' ' . $this->ModelId());
      } else {
        $this->ModelId = $model->Id();
        Debug("Foud model: " . $model->Name() . ' ' . $model->Id() . ' ' . $this->ModelId());
      }
    }
    if (!property_exists($this, 'Model')) {
      if (property_exists($this, 'ModelId') and $this->{'ModelId'}) {
        $this->{'Model'} = Model::find_one(array('Id'=>$this->ModelId()));
        if (!$this->{'Model'})
          $this->{'Model'} = new Model();
      } else {
        $this->{'Model'} = new Model();
      }
    }
    return $this->{'Model'};
  }
  function Manufacturer($new=-1) {
    if ($new != -1) {
      $manufacturer = Manufacturer::find_one(array('Name'=>$new));
      if (!$manufacturer) {
        $manufacturer = new Manufacturer();
        $manufacturer->set(['Name'=>$new]);
        $this->Manufacturer = $manufacturer;
        if ($this->ManufacturerId)
          $this->ManufacturerId = null;
      } else {
        $this->ManufacturerId = $manufacturer->Id();
      }
    }

    if (!property_exists($this, 'Manufacturer')) {
      if (property_exists($this, 'ManufacturerId') and $this->{'ManufacturerId'}) {
        $this->{'Manufacturer'} = Manufacturer::find_one(array('Id'=>$this->ManufacturerId()));
        if (!$this->{'Manufacturer'})
          $this->{'Manufacturer'} = new Manufacturer();
      } else {
          $this->{'Manufacturer'} = new Manufacturer();
      }
    }
    return $this->{'Manufacturer'};
  }

  function getMonitorStateHTML() {
    $html = '
<div id="monitorStatus'.$this->Id().'" class="monitorStatus">
<span class="MonitorName">'.$this->Name().' (id='.$this->Id().')</span>
  <div id="monitorState'.$this->Id().'" class="monitorState">
    <span>'.translate('State').':<span id="stateValue'.$this->Id().'">'.$this->Status().'</span></span>
    <span class="viewingFPS" id="viewingFPS'.$this->Id().'" title="'.translate('Viewing FPS').'"><span id="viewingFPSValue'.$this->Id().'"></span> fps</span>
    <span class="captureFPS" id="captureFPS'.$this->Id().'" title="'.translate('Capturing FPS').'"><span id="captureFPSValue'.$this->Id().'"></span> fps</span>
';
    if ($this->Analysing() != 'None') {
      $html .= '<span class="analysisFPS" id="analysisFPS'.$this->Id().'" title="'.translate('Analysis FPS').'"><span id="analysisFPSValue'.$this->Id().'"></span> fps</span>
      ';
    }
    $html .= '
    <span id="rate'.$this->Id().'" class="rate hidden">'.translate('Rate').': <span id="rateValue'.$this->Id().'"></span>x</span>
    <span id="delay'.$this->Id().'" class="delay hidden">'.translate('Delay').': <span id="delayValue'.$this->Id().'"></span>s</span>
    <span id="level'.$this->Id().'" class="buffer hidden">'.translate('Buffer').': <span id="levelValue'.$this->Id().'"></span>%</span>
    <span class="zoom hidden" id="zoom'.$this->Id().'">'. translate('Zoom').': <span id="zoomValue'.$this->Id().'"></span>x</span>
  </div>
</div>
';
    return $html;
  }

/* options['width'] is the desired view width not necessarily the image width requested.
 * It can be % in which case we us it to set the scale
 * It can be px in which case we can use it to calculate the scale
 * Same width height.  If both are set we should calculate the smaller resulting scale
 */
  function getStreamHTML($options) {
    global $basename;

    if (isset($options['scale']) and $options['scale'] != '' and $options['scale'] != 'fixed') {
      if ($options['scale'] != 'auto' && $options['scale'] != '0') {
        $options['width'] = reScale($this->ViewWidth(), $options['scale']).'px';
        $options['height'] = reScale($this->ViewHeight(), $options['scale']).'px';
      } else if (!(isset($options['width']) or isset($options['height']))) {
        $options['width'] = '100%';
        $options['height'] = 'auto';
      }
    } else {
      $options['scale'] = 100;
      # scale is empty or 100
      # There may be a fixed width applied though, in which case we need to leave the height empty
      if (!(isset($options['width']) and $options['width']) or ($options['width']=='auto')) {
        # Havn't specified width.  If we specified height, then we should
        # use a width that keeps the aspect ratio, otherwise no scaling, 
        # no dimensions, so assume the dimensions of the Monitor

        if (!(isset($options['height']) and $options['height'])) {
          # If we havn't specified any scale or dimensions, then we must be using CSS to scale it in a dynamic way. Can't make any assumptions.
        }
      } else {
        if (preg_match('/^(\d+)px$/', $options['width'], $matches)) {
          $scale = intval(100*$matches[1]/$this->ViewWidth());
          if ($scale < $options['scale'])
            $options['scale'] = $scale;
        } else if (preg_match('/^(\d+)%$/', $options['width'], $matches)) {
          $scale = intval($matches[1]);
          if ($scale < $options['scale'])
            $options['scale'] = $scale;
        } else {
          $backTrace = debug_backtrace();
          Warning('Invalid value for width: '.$options['width']. ' from '.print_r($backTrace, true));
        }
      }
    }
    if (!isset($options['mode'])) {
      $options['mode'] = 'stream';
    }
    if (!isset($options['width']) or $options['width'] == 'auto')
      $options['width'] = 0;
    if (!isset($options['height']) or $options['height'] == 'auto')
      $options['height'] = 0;

    if (!isset($options['maxfps'])) {
      $options['maxfps'] = ZM_WEB_VIDEO_MAXFPS;
    }
    if ($this->StreamReplayBuffer())
      $options['buffer'] = $this->StreamReplayBuffer();
    //Warning("width: " . $options['width'] . ' height: ' . $options['height']. ' scale: ' . $options['scale'] );
    $blockRatioControl = ($basename == "montage") ? '<div id="ratioControl'.$this->Id().'" class="ratioControl hidden"><select name="ratio'.$this->Id().'" id="ratio'.$this->Id().'" class="select-ratio chosen" data-on-change="changeRatio">
</select></div>' : '';
    $html = '
      <div id="m'. $this->Id() . '" class="grid-monitor grid-stack-item" gs-id="'. $this->Id() . '" gs-w="12" gs-auto-position="true">
        ' . $blockRatioControl . '
        <div class="grid-stack-item-content">
          <div id="monitor'. $this->Id() . '" data-id="'.$this->Id().'" class="monitor"
            title="Shift+Click to Zoom, Click+Drag to Pan &#013;Ctrl+Click to Zoom out, Ctrl+Shift+Click to Zoom out completely"
            >
            <div
              id="imageFeed'. $this->Id() .'"
              class="monitorStream imageFeed"
              data-monitor-id="'. $this->Id() .'"
              data-width="'. $this->ViewWidth() .'"
              data-height="'.$this->ViewHeight() .'" style="'.
#(($options['width'] and ($options['width'] != '0px')) ? 'width: '.$options['width'].';' : '').
#(($options['height'] and ($options['height'] != '0px')) ? 'height: '.$options['height'].';' : '').
            '">';
              $html .= '
                <div id="button_zoom'.$this->Id().'" class="button_zoom hidden">
                  <button id="btn-zoom-in'.$this->Id().'" class="btn btn-zoom-in hidden" data-on-click="panZoomIn" title="'.translate('Zoom IN').'"><span class="material-icons md-36">add</span></button>
                  <button id="btn-zoom-out'.$this->Id().'" class="btn btn-zoom-out hidden" data-on-click="panZoomOut" title="'.translate('Zoom OUT').'"><span class="material-icons md-36">remove</span></button>
                  <div class="block-button-center">
                    <button id="btn-fullscreen'.$this->Id().'" class="btn btn-fullscreen" title="'.translate('Open full screen').'"><span class="material-icons md-30">fullscreen</span></button>
                    <button id="btn-view-watch'.$this->Id().'" class="btn btn-view-watch" title="'.translate('Open watch page').'"><span class="material-icons md-30">open_in_new</span></button>
                    <button id="btn-edit-monitor'.$this->Id().'" class="btn btn-edit-monitor" title="'.translate('Edit monitor').'"><span class="material-icons md-30">edit</span></button>
                  </div>
                </div>
                <div class="zoompan">';

    $player = isset($options['player']) ? $options['player'] : $this->DefaultPlayer();

    if ($this->Type() == 'WebSite') {
      $html .= getWebSiteUrl(
        'liveStream'.$this->Id(), $this->Path(),
        ( isset($options['width']) ? $options['width'] : NULL ),
        ( isset($options['height']) ? $options['height'] : NULL ),
        $this->Name()
      );
      //FIXME, the width and height of the image need to be scaled.
    } else if ((ZM_WEB_STREAM_METHOD == 'mpeg') && ZM_MPEG_LIVE_FORMAT) {
      $streamSrc = $this->getStreamSrc( array(
        'mode'   => 'mpeg',
        'scale'  => (isset($options['scale'])?$options['scale']:100),
        'bitrate'=> ZM_WEB_VIDEO_BITRATE,
        'maxfps' => ZM_WEB_VIDEO_MAXFPS,
        'format' => ZM_MPEG_LIVE_FORMAT
      ) );
      $html .= getVideoStreamHTML( 'liveStream'.$this->Id(), $streamSrc, $options['width'], $options['height'], ZM_MPEG_LIVE_FORMAT, $this->Name() );
    } else if ($player == 'zms') {
      if ( $options['mode'] == 'stream' and canStream() ) {
        $options['mode'] = 'jpeg';
      }
      $streamSrc = $this->getStreamSrc($options);
      $html .= getImageStreamHTML('liveStream'.$this->Id(), $streamSrc, $options['width'], $options['height'], $this->Name());
    } else if ($this->JanusEnabled() or ($this->RTSP2WebEnabled() and ZM_RTSP2WEB_PATH) or ($this->Go2RTCEnabled() and ZM_GO2RTC_PATH)) {
      $html .= '<video id="liveStream'.$this->Id().'" '.
        ((isset($options['width']) and $options['width'] and $options['width'] != '0')?'width="'.$options['width'].'"':'').
        ' autoplay muted controls playsinline=""></video>';
    } else if ( $options['mode'] == 'stream' and canStream() ) {
      $options['mode'] = 'jpeg';
      $streamSrc = $this->getStreamSrc($options);
      $html .= getImageStreamHTML('liveStream'.$this->Id(), $streamSrc, $options['width'], $options['height'], $this->Name());
    } else if ( $options['mode'] == 'single' and canStream() ) {
      $streamSrc = $this->getStreamSrc($options);
      $html .= getImageStreamHTML('liveStream'.$this->Id(), $streamSrc, $options['width'], $options['height'], $this->Name());
    } else {
      if ($options['mode'] == 'stream') {
        Info('The system has fallen back to single jpeg mode for streaming. Consider enabling Cambozola or upgrading the client browser.');
      }
      $options['mode'] = 'single';
      $streamSrc = $this->getStreamSrc($options);
      $html .= getImageStill('liveStream'.$this->Id(), $streamSrc,
        (isset($options['width']) ? $options['width'] : null),
        (isset($options['height']) ? $options['height'] : null),
        $this->Name());
    }

    if (isset($options['zones']) and $options['zones']) {
      $html .= '<svg class="zones" id="zones'.$this->Id().'" viewBox="0 0 '.$this->ViewWidth().' '.$this->ViewHeight() .'" preserveAspectRatio="none">'.PHP_EOL;
      foreach (Zone::find(array('MonitorId'=>$this->Id()), array('order'=>'Area DESC')) as $zone) {
        $html .= $zone->svg_polygon();
      } // end foreach zone
      $html .= '
  Sorry, your browser does not support inline SVG
</svg>
';
    } # end if showZones
    $html .= PHP_EOL.'</div><!--.zoompan--></div><!--monitorStream-->'.PHP_EOL;
    if (isset($options['state']) and $options['state']) {
    $html .= '<div class="status">';
    $html .= '
      <div class="stream-info">
          <div class="stream-info-status"></div>
          <div class="stream-info-mode"></div>
      </div>
      '.PHP_EOL;
    //if ((!ZM_WEB_COMPACT_MONTAGE) && ($this->Type() != 'WebSite')) {
      $html .= $this->getMonitorStateHTML();
      $html .= '</div><!--state-->';
    }
    $html .= PHP_EOL.'</div></div><!--.grid-stack-item-content--></div><!--.grid-stack-item-->'.PHP_EOL;
    return $html;
  } // end getStreamHTML
 
  public function effectivePermission($u=null) {
    if ($u === null) {
      global $user;
      $u = $user;
    }
    $monitor_permission = $u->Monitor_Permission($this->Id());
    if ($monitor_permission->Permission() != 'Inherit') {
      return $monitor_permission->Permission();
    }
    $gp_permissions = array();
    foreach ($u->Group_Permissions() as $gp) {
      if (false === array_search($this->Id(), $gp->Group()->MonitorIds())) {
        continue;
      }
      if ($gp->Permission() == 'None') {
        return $gp->Permission();
      }
      $gp_permissions[$gp->Permission()] = 1;
    }
    if (isset($gp_permissions['View'])) return 'View';
    if (isset($gp_permissions['Edit'])) return 'Edit';
    return $u->Monitors();
  }

  public function link_to($text='') {
    return '<a href="?view=monitor&mid='.$this->Id().'">'.($text ? $text : $this->Name()).'</a>';
  }

  public function connect() {
    if ($this->connected()) return true;
    $mmap_file = ZM_PATH_MAP.'/zm.mmap.'.$this->Id;
    if (!file_exists($mmap_file)) {
      Debug( "Memory map file '$mmap_file' does not exist.  zmc might not be running.");
      return false;
    }
    $mmap_file_size = filesize($mmap_file);
    if (!$mmap_file_size) {
      Debug( "Memory map file '$mmap_file' has no size.  zmc might not be running.");
      return false;
    }

    $valid = false;
    $this->shm_id = fopen($mmap_file, 'r+');
    if ($this->shm_id) {
      #$this->mmap = mmap($this->shm_id);
      $valid = $this->shared_read('SharedData', 'valid');
      if (!$valid) {
        Error('Shared data not valid for monitor '.$this->Id);
      }
      $size = $this->shared_read('SharedData', 'size');
      Debug("Shm is $valid $size");
    }

    return $this->connected = $this->shm_id && $valid;
  }
  public function disconnect() {
    if ($this->shm_id) {
      fclose($this->shm_id);
      $this->connected = false;
      $this->shm_id = false;
    }
  }

  public function shared_read($section, $var) {
    if (!isset($this->shm_offsets[$section])) {
      Error("Invalid shared mem section $section");
      return false;
    }
    if (!isset($this->shm_offsets[$section][$var])) {
      Error("Invalid shared mem variable $var");
      return false;
    }

    fseek($this->shm_id, $this->shm_offsets[$section][$var]['offset']);
    $value = fread($this->shm_id, $this->shm_offsets[$section][$var]['size']);
    $unpacked_value = $value;
    if ($this->shm_offsets[$section][$var]['type'] == 'int8') {
      $unpacked_value = unpack('c', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint8') {
      $unpacked_value = unpack('C', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'int32') {
      $unpacked_value = unpack('l', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint32') {
      $unpacked_value = unpack('L', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'int64') {
      $unpacked_value = unpack('q', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint64') {
      $unpacked_value = unpack('Q', $value);
    }
    $unpacked_value = is_array($unpacked_value) ? $unpacked_value[1] : $unpacked_value;
    Debug("Value for $section::$var is $value=>$unpacked_value from ".$this->shm_offsets[$section][$var]['offset']);
    return $unpacked_value;

    #return substr($this->mmap,
      #$this->shm_offsets[$section][$var]['offset'],
      #$this->shm_offsets[$section][$var]['size'],
    #);

    return shmop_read($this->shm_id,
      $this->shm_offsets[$section][$var]['offset'], 
      $this->shm_offsets[$section][$var]['size']
    );
  }

  public function shared_write($section, $var, $value) {
    if (!isset($this->shm_offsets[$section])) {
      Error("Invalid shared mem section $section");
      return false;
    }
    if (!isset($this->shm_offsets[$section][$var])) {
      Error("Invalid shared mem variable $var");
      return false;
    }

    $packed_value = $value;
    if ($this->shm_offsets[$section][$var]['type'] == 'int8') {
      $packed_value = pack('c', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint8') {
      $packed_value = pack('C', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'int32') {
      $packed_value = pack('l', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint32') {
      $packed_value = pack('L', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'int64') {
      $packed_value = pack('q', $value);
    } else if ($this->shm_offsets[$section][$var]['type'] == 'uint64') {
      $packed_value = pack('Q', $value);
    }
    Debug("Writing $value=>$packed_value at ".$this->shm_offsets[$section][$var]['offset']);
    fseek($this->shm_id, $this->shm_offsets[$section][$var]['offset']);
    return fwrite($this->shm_id, $packed_value, $this->shm_offsets[$section][$var]['size']);
  }
  
  public function enable() {
    if (!$this->connect()) return false;
    $action = $this->shared_read('TriggerData', 'action');
    $action |= TRIGGER_ACTION_SUSPEND;
    $this->shared_write('TriggerData', 'action', $action);
    return true;
  }

  public function disable() {
    if (!$this->connect()) return false;
    $action = $this->shared_read('TriggerData', 'action');
    $action |= TRIGGER_ACTION_RESUME;
    $this->shared_write('TriggerData', 'action', $action);
    return true;
  }

  public function TriggerEventOn($score, $cause, $text, $showtext='') {
    if (!$this->connect()) return false;
    Debug("Trigger size is ".$this->shared_read('TriggerData','size'));
    $this->shared_write('TriggerData', 'score', $score);
    $this->shared_write('TriggerData', 'cause', $cause);
    if ($text) $this->shared_write('TriggerData', 'text', $text);
    if ($showtext) $this->shared_write('TriggerData', 'showtext', $showtext);
    $this->shared_write('TriggerData', 'state', TRIGGER_ON);
    Debug("Trigger cause is ".$this->shared_read('TriggerData','cause'));
  }

  public function  TriggerEventOff () {
    if (!$this->connect()) return false;
    $this->shared_write('TriggerData', 'score', 0);
    $this->shared_write('TriggerData', 'cause', '');
    if ($text) $this->shared_write('TriggerData', 'text', '');
    if ($showtext) $this->shared_write('TriggerData', 'showtext', '');
    $this->shared_write('TriggerData', 'state', TRIGGER_OFF);
  }

  public function TriggerEventCancel() {
    if (!$this->connect()) return false;
    $this->shared_write('TriggerData', 'score', 0);
    $this->shared_write('TriggerData', 'cause', '');
    if ($text) $this->shared_write('TriggerData', 'text', '');
    if ($showtext) $this->shared_write('TriggerData', 'showtext', '');
    $this->shared_write('TriggerData', 'state', TRIGGER_CANCEL);
  }
  public function TriggerShowtext($showtext) {
    $this->shared_write('TriggerData', 'showtext', $showtext);
  }

  public function GetState() {
    if (!$this->connect()) return false;
    return $this->shared_read('SharedData', 'state');
  }

  public function InAlarm() {
    if (!$this->connect()) return false;
    $state = $this->GetState();
    return( $state == STATE_ALARM || $state == STATE_ALERT );
  }

  public function GetLastEventId() {
    if (!$this->connect()) return false;
    return $this->shared_read('SharedData', 'last_event');
  }

  public function getStreamMode() {
    $streamMode = getStreamMode(); # from includs/functions.php
    return $streamMode;
  }

} // end class Monitor
?>
