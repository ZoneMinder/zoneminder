
var currentScale=<?php echo $defaultScale?>;
var liveMode=<?php echo $initialModeIsLive?>;
console.log("Live mode?"+liveMode);
var fitMode=<?php echo $fitMode?>;
var currentSpeed=<?php echo $speeds[$speedIndex]?>;  // slider scale, which is only for replay and relative to real time
var speedIndex=<?php echo $speedIndex?>;
var currentDisplayInterval=<?php echo $initialDisplayInterval?>;  // will be set based on performance, this is the display interval in milliseconds for history, and fps for live, and dynamically determined (in ms)
var playSecsperInterval=1;         // How many seconds of recorded image we play per refresh determined by speed (replay rate) and display interval; (default=1 if coming from live)
var timerInterval;               // milliseconds between interrupts
var timerObj;               // object to hold timer interval;
var freeTimeLastIntervals=[];    // Percentage of current interval used in loading most recent image
var imageLoadTimesEvaluated=0;   // running count
var imageLoadTimesNeeded=15;     // and how many we need
var timeLabelsFractOfRow = 0.9;
var eMonId = [];
var eId = [];
var eStartSecs = [];
var eEndSecs = [];
var eventFrames = [];            // this is going to presume all frames equal durationlength
var groupStr=<?php if($group=="") echo '""'; else echo "\"&group=$group\""; ?>;

<?php

// Because we might not have time as the criteria, figure out the min/max time when we run the query

$minTimeSecs = strtotime('2036-01-01 01:01:01');
$maxTimeSecs = strtotime('1950-01-01 01:01:01');

// This builds the list of events that are eligible from this range

$index=0;
$anyAlarms=false;

$result = dbQuery( $eventsSql );
if ( ! $result ) {
  Fatal( "SQL-ERR");
  return;
}

while( $event = $result->fetch( PDO::FETCH_ASSOC ) ) {

  if ( $minTimeSecs > $event['StartTimeSecs'] )   $minTimeSecs = $event['StartTimeSecs'];
  if ( $maxTimeSecs < $event['CalcEndTimeSecs'] ) $maxTimeSecs = $event['CalcEndTimeSecs'];
    echo "
eMonId[$index]=" . $event['MonitorId'] . ";
eId[$index]=" . $event['Id'] . ";
eStartSecs[$index]=" . $event['StartTimeSecs'] . ";
eEndSecs[$index]=" . $event['CalcEndTimeSecs'] . ";
eventFrames[$index]=" . $event['Frames'] . ";

";

  $index = $index + 1;
  if ( $event['MaxScore'] > 0 )
    $anyAlarms = true;
}

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
  $maxTime = strftime($maxTimeSecs);
  $minTime = strftime($minTimeSecs);
} else {
  $minTimeSecs = strtotime($minTime);
  $maxTimeSecs = strtotime($maxTime);
}

// If we had any alarms in those events, this builds the list of all alarm frames, but consolidated down to (nearly) contiguous segments 
// comparison in else governs how aggressively it consolidates

echo "var fMonId = [];\n";
echo "var fTimeFromSecs = [];\n";
echo "var fTimeToSecs = [];\n";
echo "var fScore = [];\n";
$maxScore=0;
$index=0;
$mId=-1;
$fromSecs=-1;
$toSecs=-1;
$maxScore=-1;

if ( $anyAlarms && $result = dbQuery( $frameSql ) ) {

  while( $frame = $result->fetch( PDO::FETCH_ASSOC ) ) {
    if ( $mId < 0 ) {
      $mId = $frame['MonitorId'];
      $fromSecs = $frame['TimeStampSecs'];
      $toSecs = $frame['TimeStampSecs'];
      $maxScore = $frame['Score'];
    } else if ( $mId != $frame['MonitorId'] || $frame['TimeStampSecs'] - $toSecs > 10 ) {
      // dump this one start a new
      $index++;
      echo "
  fMonId[$index]= $mId;
  fTimeFromSecs[$index]= $fromSecs;
  fTimeToSecs[$index]= $toSecs;
  fScore[$index]= $maxScore;
";
      $mId = $frame['MonitorId'];
      $fromSecs = $frame['TimeStampSecs'];
      $toSecs = $frame['TimeStampSecs'];
      $maxScore = $frame['Score'];
    } else {
      // just add this one on
      $toSecs = $frame['TimeStampSecs'];
      if ( $maxScore < $frame['Score'] ) $maxScore = $frame['Score'];
    }
  }
}
if ( $mId > 0 ) {
  echo "
  fMonId[$index]= $mId;
  fTimeFromSecs[$index]= $fromSecs;
  fTimeToSecs[$index]= $toSecs;
  fScore[$index]= $maxScore;
";
}

echo "var maxScore=$maxScore;\n";  // used to skip frame load if we find no alarms.
echo "var monitorName = [];\n";
echo "var monitorLoading = [];\n";
echo "var monitorImageObject = [];\n";
echo "var monitorImageURL = [];\n";
echo "var monitorLoadingStageURL = [];\n";
echo "var monitorLoadStartTimems = [];\n";
echo "var monitorLoadEndTimems = [];\n";
echo "var monitorColour = [];\n";
echo "var monitorWidth = [];\n";
echo "var monitorHeight = [];\n";
echo "var monitorIndex = [];\n";
echo "var monitorNormalizeScale = [];\n";
echo "var monitorZoomScale = [];\n";
echo "var monitorCanvasObj = [];\n"; // stash location of these here so we don't have to search
echo "var monitorCanvasCtx = [];\n";
echo "var monitorPtr = []; // monitorName[monitorPtr[0]] is first monitor\n";


$numMonitors=0;  // this array is indexed by the monitor ID for faster access later, so it may be sparse
$avgArea=floatval(0);  // Calculations the normalizing scale

foreach ( $monitors as $m ) {
  $avgArea = $avgArea + floatval($m->Width() * $m->Height());
  $numMonitors++;
}

if ( $numMonitors > 0 ) $avgArea = $avgArea / $numMonitors;

$numMonitors = 0;
foreach ( $monitors as $m ) {
    echo "  monitorLoading["         . $m->Id() . "]=false;\n";
    echo "  monitorImageURL["     . $m->Id() . "]='".$m->getStreamSrc( array('mode'=>'single','scale'=>$defaultScale*100), '&' )."';\n";
    echo "  monitorLoadingStageURL[" . $m->Id() . "] = '';\n";
    echo "  monitorColour["          . $m->Id() . "]=\"" . $m->WebColour() . "\";\n";
    echo "  monitorWidth["           . $m->Id() . "]=" . $m->Width() . ";\n";
    echo "  monitorHeight["          . $m->Id() . "]=" . $m->Height() . ";\n";
    echo "  monitorIndex["           . $m->Id() . "]=" . $numMonitors . ";\n";
    echo "  monitorName["            . $m->Id() . "]=\"" . $m->Name() . "\";\n";
    echo "  monitorLoadStartTimems[" . $m->Id() . "]=0;\n";
    echo "  monitorLoadEndTimems["   . $m->Id() . "]=0;\n";
    echo "  monitorNormalizeScale["  . $m->Id() . "]=" . sqrt($avgArea / ($m->Width() * $m->Height() )) . ";\n";
    $zoomScale=1.0;
    if(isset($_REQUEST[ 'z' . $m->Id() ]) )
        $zoomScale = floatval( validHtmlStr($_REQUEST[ 'z' . $m->Id() ]) );
    echo "  monitorZoomScale["       . $m->Id() . "]=" . $zoomScale . ";\n";
    echo "  monitorPtr["         . $numMonitors . "]=" . $m->Id() . ";\n";
    $numMonitors += 1;
}
echo "var numMonitors = $numMonitors;\n";
echo "var minTimeSecs="     . $minTimeSecs . ";\n";
echo "var maxTimeSecs="     . $maxTimeSecs . ";\n";
echo "var rangeTimeSecs="   . ( $maxTimeSecs - $minTimeSecs + 1) . ";\n";
if(isset($defaultCurrentTime))
  echo "var currentTimeSecs=" . strtotime($defaultCurrentTime) . ";\n";
else
  echo "var currentTimeSecs=" . ($minTimeSecs + $maxTimeSecs)/2 . ";\n";

echo 'var speeds=[';
for ($i=0; $i<count($speeds); $i++)
  echo (($i>0)?', ':'') . $speeds[$i];
echo "];\n";
?>

var scrubAsObject=$('scrub');
var cWidth;   // save canvas width
var cHeight;  // save canvas height
var canvas;   // global canvas definition so we don't have to keep looking it up
var ctx;
var underSlider;    // use this to hold what is hidden by the slider
var underSliderX;   // Where the above was taken from (left side, Y is zero)

