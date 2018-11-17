<?php

# Check process
$output = shell_exec("pgrep -f ffmpeg");
if (!empty($output)) die("Ffmpeg already running");

chdir('/usr/share/zoneminder/www/');
require_once('includes/config.php');
require_once('includes/database.php');

# Check parameters
if (empty($argv[1])) die('Parameters missing');
parse_str($argv[1], $params);
// var_dump($params);

if ( empty($params['eids']) || empty($params['generateEncoder']) || empty($params['generateFramerate'])
  || empty($params['generateSize']) ) {
  die('Parameters missing');
}

set_time_limit(0);
  
# Validate encoder
if (!in_array($params['generateEncoder'], array('none', 'x264', 'mpeg2'))) {
  echo "Invalid encoder, skipping";
  continue;
}
  
# Validate framerate
if (!in_array($params['generateFramerate'], array('4', '8', '16', '32'))) {
  echo "Invalid framerate, skipping";
  continue;
}
  
# Validate size
if (!in_array($params['generateSize'], array('1', '0.75', '0.5', '0.25', '0.125'))) {
  echo "Invalid size, skipping";
  continue;
}

# Store parameters
$encoder = $params['generateEncoder'];
$framerate = $params['generateFramerate'];
$size = $params['generateSize'];

echo PHP_EOL . "Encoder " . $encoder . PHP_EOL;
echo "Framerate " . $framerate . PHP_EOL;
echo "Size " . $size . PHP_EOL . PHP_EOL;

foreach ($params['eids'] as $eid) {
  echo "Processing event " . $eid . PHP_EOL;

  # Validate eid
  if (!is_numeric($eid)) {
    echo "Invalid eventid, skipping";
    continue;
  }

  # DB Query
  $sql = 'SELECT StartTime,MonitorId,Width,Height FROM Events WHERE Id = ?';
  $sql_values = array($eid);
  $event = dbFetchOne($sql, NULL, $sql_values);  

  # Calc date
  $dt = new DateTime($event["StartTime"]);
  $date = $dt->format('Y-m-d');

  # Calc size
  $sizeW = $event['Width'] * $size;
  $sizeH = $event['Height'] * $size;
  $sizeStr = $sizeW . 'x' . $sizeH;

  # Calc encoder
  if ($encoder == 'none') $encoderStr = "-c copy";
  else if ($encoder == 'x264') $encoderStr = "-c:v libx264 -crf 23";
  else if ($encoder == 'mpeg2') $encoderStr = "-c:v mpeg2video -qscale:v 5";
  
  # Calc framerate
  $framerateStr = $framerate / 4;

  # Command lines
  $commandline1 = "cd /mnt/rec/events/" . $event["MonitorId"] . "/" . $date  .  "/" . $eid;
  $commandline2 = '/usr/bin/ffmpeg -y -framerate ' . $framerate . ' -i %05d-capture.jpg -s ' . $sizeStr . ' ' . 
    $encoderStr . ' -pix_fmt yuvj420p "Event-_' . $eid . '-r' . $framerateStr  . '-s' . $size  . '.avi"';

  # Exec ffmpeg
  $output = null;
  
  // echo $commandline1 . PHP_EOL;
  // echo $commandline2 . PHP_EOL;
  // echo $commandline1 . " && " . $commandline2 . ' > ffmpeg.log' . PHP_EOL;
  // $output = exec( 'echo HELLO ' . $eid );
  // echo $output . PHP_EOL;
  // echo "Done." . PHP_EOL . PHP_EOL;
  // continue;
  
  $output = exec( $commandline1 . " && " . $commandline2 . ' > ffmpeg.log' );
  echo $output . PHP_EOL . "Done." . PHP_EOL . PHP_EOL;
}


