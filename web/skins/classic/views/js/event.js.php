//
// Import constants
//
var CMD_NONE = <?php echo CMD_NONE ?>;
var CMD_PAUSE = <?php echo CMD_PAUSE ?>;
var CMD_PLAY = <?php echo CMD_PLAY ?>;
var CMD_STOP = <?php echo CMD_STOP ?>;
var CMD_FASTFWD = <?php echo CMD_FASTFWD ?>;
var CMD_SLOWFWD = <?php echo CMD_SLOWFWD ?>;
var CMD_SLOWREV = <?php echo CMD_SLOWREV ?>;
var CMD_FASTREV = <?php echo CMD_FASTREV ?>;
var CMD_ZOOMIN = <?php echo CMD_ZOOMIN ?>;
var CMD_ZOOMOUT = <?php echo CMD_ZOOMOUT ?>;
var CMD_PAN = <?php echo CMD_PAN ?>;
var CMD_SCALE = <?php echo CMD_SCALE ?>;
var CMD_PREV = <?php echo CMD_PREV ?>;
var CMD_NEXT = <?php echo CMD_NEXT ?>;
var CMD_SEEK = <?php echo CMD_SEEK ?>;
var CMD_QUERY = <?php echo CMD_QUERY ?>;

var SCALE_BASE = <?php echo SCALE_BASE ?>;

//
// PHP variables to JS
//
var connKey = '<?php echo $connkey ?>';

var eventData = {
    Id: '<?php echo $Event->Id() ?>',
    MonitorId: '<?php echo $Event->MonitorId() ?>',
    Width: '<?php echo $Event->Width() ?>',
    Height: '<?php echo $Event->Height() ?>',
    Length: '<?php echo $Event->Length() ?>'
};

var filterQuery = '<?php echo isset($filterQuery)?validJsStr(htmlspecialchars_decode($filterQuery)):'' ?>';
var sortQuery = '<?php echo isset($sortQuery)?validJsStr(htmlspecialchars_decode($sortQuery)):'' ?>';

var scale = <?php echo $scale ?>;
var canEditEvents = <?php echo canEdit( 'Events' )?'true':'false' ?>;
var streamTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;

var canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;

//
// Strings
//
var deleteString = "<?php echo translate('Delete') ?>";
var causeString = "<?php echo translate('AttrCause') ?>";

//
// AlarmCues
//
<?php
function renderAlarmCues () {
  global $Event, $scale;
  $sql = 'SELECT *, unix_timestamp(TimeStamp) AS UnixTimeStamp FROM Frames WHERE EventID = ? ORDER BY FrameId';
  $frames = dbFetchAll($sql, NULL, array($_REQUEST['eid']));
  if (count($frames)) {
    $width = reScale($Event->Width(), $scale);
    $lastFrame = end($frames);
    $lastTime = $lastFrame['Delta'] * 100;
    $ratio = $width / $lastTime;
    $minAlarm = ceil(1 / $ratio);
    $spanTimeStart = 0;
    $spanTimeEnd = 0;
    $alarmed = 0;
    $alarmHtml = "";
    $pixSkewNone = 0;
    $pixSkewAlarm = 0;
    $skip = 0;
    foreach ($frames as $key => $frame) {
      if ($frame['Type'] == "Alarm" && $alarmed == 0) { //From nothing to alarm.  End nothing and start alarm.
        if ($skip > $key) continue;
        $alarmed = 1;
        if ($frame['Delta'] == 0) continue; //If event starts with alarm
        $spanTimeEnd = $frame['Delta'] * 100;
        $spanTime = $spanTimeEnd - $spanTimeStart;
        $pix = $ratio * $spanTime;
        $pixSkewNone += $pix - round($pix);
        $pix = round($pix);
        if (($pixSkewNone > 1 || $pixSkewNone < -1) && $pix + round($pixSkewNone) > 0) { //add skew if it's a pixel and won't zero span
          $pix += round($pixSkewNone);
          $pixSkewNone = $pixSkewNone - round($pixSkewNone);
        }
        $alarmHtml .= "<span class=\"alarmCue noneCue\" style=\"width:" . $pix . "px;\"></span>";
        $spanTimeStart = $spanTimeEnd;
      } elseif ($frame['Type'] !== "Alarm" && $alarmed == 1) { //from Alarm to nothing.  End alarm and start nothing.
        if ($skip > $key) continue;
        $futNone = 0;
        $keyPlus = $key + 1;
        while ($futNone < $minAlarm) { //check ahead to see if there's enough for a none.
          if (!array_key_exists($keyPlus, $frames )) break; //check if end of event
          $futNone = ($frames[$keyPlus]['Delta'] * 100) - ($frame['Delta'] * 100);
          if ($frames[$keyPlus]['Type'] == "Alarm") {
            $skip = $keyPlus;
            continue 2;
          }
          $keyPlus +=1;
        }
        if ((($frame['Delta'] * 100) - $spanTimeStart) < $minAlarm && (array_key_exists($keyPlus, $frames))) continue; //alarm short and more event
        $spanTimeEnd = $frame['Delta'] * 100;
        $spanTime = $spanTimeEnd - $spanTimeStart;
        $alarmed = 0;
        $pix = $ratio * $spanTime;
        $pixSkewAlarm += $pix - round($pix);
        $pix = round($pix);
        if (($pixSkewAlarm > 1 || $pixSkewAlarm < -1) && $pix + round($pixSkewAlarm) > 0) {
          $pix += round($pixSkewAlarm);
          $pixSkewAlarm = $pixSkewAlarm - round($pixSkewAlarm);
        }
        $alarmHtml .= "<span class=\"alarmCue\" style=\"width:" . $pix . "px;\"></span>";
        $spanTimeStart = $spanTimeEnd;
      } elseif ($frame['Type'] == "Alarm" && $alarmed == 1 && !array_key_exists($key+1, $frames)) { // event ends on alarm
        $spanTimeEnd = $frame['Delta'] * 100;
        $spanTime = $spanTimeEnd - $spanTimeStart;
        $alarmed = 0;
        $pix = round($ratio * $spanTime);
        $alarmHtml .= "<span class=\"alarmCue\" style=\"width:" . $pix . "px;\"></span>";
      }
    }
    echo $alarmHtml;
  }
}
?>
var alarmHtml = '<?php renderAlarmCues (); ?>';
