<?php

# Check process
$output = shell_exec("pgrep -f ffmpeg");
if (!empty($output)) die("ffmpeg already running");

require_once('config.php');
require_once('database.php');

// var_dump(get_defined_vars());

# Check parameters
if (empty($argv[1])) die('Parameters missing');
parse_str($argv[1], $params);
// var_dump($params);

if ( 
      empty($params['eids']) ||
      empty($params['generateEncoder']) ||
      empty($params['generateFramerate']) ||
      empty($params['generateSize']) 
    ) {
  die('Parameters missing');
}
  
# Validate encoder
if (!in_array($params['generateEncoder'], array('none', 'x264', 'mpeg2'))) {
  die("Invalid encoder");
}
  
# Validate framerate
if (!in_array($params['generateFramerate'], array('10000', '5000', '2500', '1000', '400', '200', '100', '50', '25'))) {
  die("Invalid framerate");
}
  
# Validate size
if (!in_array($params['generateSize'], array('1', '0.75', '0.5', '0.25', '0.125'))) {
  die("Invalid size");
}

# Store parameters ? not nessesery
$encoder = $params['generateEncoder'];
$framerate = $params['generateFramerate'];
$size = $params['generateSize'];

echo PHP_EOL . "Encoder " . $encoder . PHP_EOL;
echo "Framerate " . $framerate . PHP_EOL;
echo "Size " . $size . PHP_EOL . PHP_EOL;

set_time_limit(0);

foreach ($params['eids'] as $eid) {
  echo "Processing event " . $eid . PHP_EOL;

  # Validate eid
  if (!filter_var($eid, FILTER_VALIDATE_INT)) {
    echo "Invalid eventid, skipping" . PHP_EOL;
    continue;
  }

  # DB Query
  $sql = 'SELECT StartTime,MonitorId,Width,Height FROM Events WHERE Id = ?';
  $sql_values = array($eid);
  $event = dbFetchOne($sql, NULL, $sql_values);  

  # Calc date
  $dt = new DateTime($event['StartTime']);
  $date = $dt->format('Y-m-d');

  # Calc size
  $sizeW = $event['Width'] * $size;
  $sizeH = $event['Height'] * $size;
  $sizeStr = $sizeW . 'x' . $sizeH;

  # Calc encoder
  if ($encoder == 'none') $encoderStr = '-c copy';
  else if ($encoder == 'x264') $encoderStr = '-c:v libx264 -crf 23';
  else if ($encoder == 'mpeg2') $encoderStr = '-c:v mpeg2video -qscale:v 5';
  
  # Calc framerate
  $framerateStr = $framerate / RATE_BASE;
  # How to get actual framerate?
  $hardCodedFramerate = $framerateStr * 5;

  # Command lines
  $commandline1 = 'cd ' . ZM_DIR_EVENTS . '/' . $event['MonitorId'] . '/' . $date  .  '/' . $eid;
  $commandline2 = '/usr/bin/ffmpeg -y -framerate ' . $hardCodedFramerate . ' -i %05d-capture.jpg -s ' . $sizeStr . ' ' . 
    $encoderStr . ' "Event-_' . $eid . '-r' . $framerateStr  . '-s' . $size  . '.avi"';

  # Exec ffmpeg
  $output = null;
  
  // echo $commandline1 . PHP_EOL;
  // echo $commandline2 . PHP_EOL;
  echo $commandline1 . ' && ' . $commandline2 . ' > ffmpeg.log' . PHP_EOL;
  
  $output = exec( $commandline1 . ' && ' . $commandline2 . ' > ffmpeg.log' );
  echo $output . PHP_EOL . 'Event ' . $eid . ' done.' . PHP_EOL . PHP_EOL;
}


