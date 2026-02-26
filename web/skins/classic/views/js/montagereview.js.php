const server_utc_offset = <?php
$tz = ini_get('date.timezone');
if (!$tz) {
  $tz = 'UTC';
  ZM\Warning('Timezone has not been set. Either select it in Options->System->Timezone or in php.ini');
}

$TimeZone = new DateTimeZone($tz);
$now = new DateTime('now', $TimeZone);
$offset = $TimeZone->getOffset($now);
echo $offset.'; // '.floor($offset / 3600).' hours ';

global $defaultScale;
global $liveMode;
global $fitMode;
global $speeds;
global $speedIndex;
global $initialDisplayInterval;
global $minTimeSecs;
global $maxTimeSecs;
global $minTime;
global $maxTime;
global $monitors;
global $eventsSql;
?>

var currentScale=<?php echo $defaultScale?>;
var liveMode=<?php echo $liveMode?>;
var fitMode=<?php echo $fitMode?>;

// slider scale, which is only for replay and relative to real time
var currentSpeed=<?php echo $speeds[$speedIndex]?>;  
var speedIndex=<?php echo $speedIndex?>;
var lastSpeedIndex=0;

// will be set based on performance, this is the display interval in milliseconds 
// for history, and fps for live, and dynamically determined (in ms)

var currentDisplayInterval=<?php echo $initialDisplayInterval?>;
var playSecsPerInterval=1;       // How many seconds of recorded image we play per refresh determined by speed (replay rate) and display interval; (default=1 if coming from live)
var timerInterval;               // milliseconds between interrupts
var timerObj;                    // object to hold timer interval;
var freeTimeLastIntervals=[];    // Percentage of current interval used in loading most recent image
var imageLoadTimesEvaluated=0;   // running count
var imageLoadTimesNeeded=15;     // and how many we need
var timeLabelsFractOfRow = 0.9;

const events = {};
const events_for_monitor = {};

<?php

// Because we might not have time as the criteria, figure out the min/max time when we run the query

// This builds the list of events that are eligible from this range

$index = 0;
$anyAlarms = false;
$maxScore = 0;

if (!$liveMode) {
  // if there is no data set the min/max to the passed in values
  if ( $index == 0 ) {
    if ( isset($minTime) && isset($maxTime) ) {
      $minTimeSecs = strtotime($minTime);
      $maxTimeSecs = strtotime($maxTime);
    } else {
      // this is the case of no passed in times AND no data -- just set something arbitrary
      $minTimeSecs = strtotime('1950-06-01 01:01:01');  // random time so there's something to display
      $maxTimeSecs = time() + 86400;
    }
  }

  // We only reset the calling time if there was no calling time
  if ( !isset($minTime) || !isset($maxTime) ) {
    $maxTime = date('c', $maxTimeSecs);
    $minTime = date('c', $minTimeSecs);
  } else {
    $minTimeSecs = strtotime($minTime);
    $maxTimeSecs = strtotime($maxTime);
  }

  echo "var maxScore=$maxScore;\n";  // used to skip frame load if we find no alarms.
} // end if initialmodeislive

echo "\nvar Storage = [];\n";
$have_storage_zero = 0;
foreach ( ZM\Storage::find() as $Storage ) {
  echo 'Storage[' . $Storage->Id() . '] = ' . $Storage->to_json(). ";\n";
  if ( $Storage->Id() == 0 )
    $have_storage_zero = true;
}
if ( !$have_storage_zero ) {
  $Storage = new ZM\Storage();
  echo 'Storage[0] = ' . $Storage->to_json(). ";\n";
}
echo "\nconst monitorData = [];\n";
foreach ( $monitors as $monitor ) {
  if ($monitor->Deleted() or !$monitor->canView()) continue;
?>

monitorData[monitorData.length] = {
  'Id': <?php echo $monitor->Id() ?>,
  'Name': '<?php echo $monitor->Name() ?>',
  'connKey': '<?php echo $monitor->connKey() ?>',
  'Width': <?php echo $monitor->ViewWidth() ?>,
  'Height':<?php echo $monitor->ViewHeight() ?>,
  'JanusEnabled':<?php echo $monitor->JanusEnabled() ?>,
  'Url': '<?php echo $monitor->UrlToIndex( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'UrlToZms': '<?php echo $monitor->UrlToZMS( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $monitor->Id() ?>' );},
  'Type': '<?php echo $monitor->Type() ?>',
  'Refresh': '<?php echo $monitor->Refresh() ?>',
  'Janus_Pin': '<?php echo $monitor->Janus_Pin() ?>',
  'WebColour': '<?php echo $monitor->WebColour() ?>'
};
<?php
} // end foreach monitor

echo '
var monitorName = [];
var monitorLoading = [];
var monitorServerId = [];
var monitorImageObject = [];
var monitorImageURL = [];
var monitorLoadingStageURL = [];
var monitorLoadStartTimems = [];
var monitorLoadEndTimems = [];
var monitorColour = [];
var monitorWidth = [];
var monitorHeight = [];
var monitorIndex = [];
var monitorNormalizeScale = [];
var monitorZoomScale = [];
var monitorCanvasObj = [];
var monitorCanvasCtx = [];
var monitorPtr = []; // monitorName[monitorPtr[0]] is first monitor
';

$numMonitors = 0;  // this array is indexed by the monitor ID for faster access later, so it may be sparse
$avgArea = floatval(0);  // Calculations the normalizing scale

foreach ( $monitors as $m ) {
  $avgArea = $avgArea + floatval($m->Width() * $m->Height());
  $numMonitors++;
}

if ( $numMonitors > 0 ) $avgArea = $avgArea / $numMonitors;

$numMonitors = 0;
foreach ( $monitors as $m ) {
  echo "  monitorLoading["         . $m->Id() . "]=false;\n";
  echo "  monitorImageURL["        . $m->Id() . "]='".$m->getStreamSrc( array('mode'=>'single','scale'=>$defaultScale*100), '&' )."';\n";
  echo "  monitorLoadingStageURL[" . $m->Id() . "] = '';\n";
  echo "  monitorColour["          . $m->Id() . "]=\"" . validHtmlStr($m->WebColour()) . "\";\n";
  echo "  monitorWidth["           . $m->Id() . "]=" . validHtmlStr($m->ViewWidth()) . ";\n";
  echo "  monitorHeight["          . $m->Id() . "]=" . validHtmlStr($m->ViewHeight()) . ";\n";
  echo "  monitorIndex["           . $m->Id() . "]=" . $numMonitors . ";\n";
  echo "  monitorServerId["        . $m->Id() . "]='" .($m->ServerId() ?  $m->ServerId() : '0'). "';\n";
  echo "  monitorName["            . $m->Id() . "]=\"" . validHtmlStr($m->Name()) . "\";\n";
  echo "  monitorLoadStartTimems[" . $m->Id() . "]=0;\n";
  echo "  monitorLoadEndTimems["   . $m->Id() . "]=0;\n";
  echo "  monitorNormalizeScale["  . $m->Id() . "]=" . sqrt($avgArea / ($m->Width() * $m->Height() )) . ";\n";
  $zoomScale=1.0;
  if ( isset($_REQUEST['z'.$m->Id()]) )
      $zoomScale = floatval(validHtmlStr($_REQUEST['z'.$m->Id()]));
  echo "  monitorZoomScale["       . $m->Id() . "]=" . $zoomScale . ";\n";
  echo "  monitorPtr["         . $numMonitors . "]=" . $m->Id() . ";\n";
  $numMonitors += 1;
}
echo "
var numMonitors = $numMonitors;
var minTimeSecs =parseInt($minTimeSecs);
var maxTimeSecs =parseInt($maxTimeSecs);
var minTime='$minTime';
var maxTime='$maxTime';
";
echo 'var rangeTimeSecs='.($maxTimeSecs - $minTimeSecs + 1).";\n";
if ( isset($defaultCurrentTimeSecs) ) {
  echo 'var currentTimeSecs=parseFloat('.$defaultCurrentTimeSecs.");\n";
} else {
  echo 'var currentTimeSecs=parseFloat('.$minTimeSecs.");\n";
}

echo 'var speeds=[';
for ( $i=0; $i < count($speeds); $i++ )
  echo (($i>0)?', ':'') . $speeds[$i];
echo "];\n";
?>

var cWidth;   // save canvas width
var cHeight;  // save canvas height
var rowHeight = 0;
var canvas;   // global canvas definition so we don't have to keep looking it up
var ctx = null;
var underSlider;    // use this to hold what is hidden by the slider
