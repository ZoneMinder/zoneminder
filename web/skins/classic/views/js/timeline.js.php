<?php
  global $filterQuery;
  global $monitors;
  global $minTime;
  global $midTime;
  global $maxTime;
  global $range;
  global $majXScale;
  global $monEventSlots;
  global $monFrameSlots;
?>
var filterQuery = '<?php echo validJsStr($filterQuery) ?>';
var events = {};

<?php
  //ZM\Debug(print_r($monEventSlots, true));
  //ZM\Debug(print_r($monFrameSlots, true));
$jsMonitors = array();

$fields = array('Name', 'LabelFormat', 'SaveJPEGs', 'VideoWriter');
foreach ($monitors as $monitor) {
  $jsMonitor = array();
  foreach ($fields as $field) {
    $jsMonitor[$field] = $monitor->$field();
  }
  $firstEvent = reset($monEventSlots[$monitor->Id()])['event'];

  $jsMonitor['FirstEventId'] = $firstEvent['Id'];
  echo 'events['.$firstEvent['Id'].']='.json_encode($firstEvent).';'.PHP_EOL;

  $jsMonitors[$monitor->Id()] = $jsMonitor;
}
?>
var monitors = <?php echo json_encode($jsMonitors) ?>;

var archivedString = "<?php echo translate('Archived') ?>";

var minTime = '<?php echo $minTime?>';
var midTime = '<?php echo $midTime?>';
var maxTime = '<?php echo $maxTime?>';
var range = '<?php echo $range?>';
var zoomout_range = '<?php (int)($range*$majXScale['zoomout']) ?>';

