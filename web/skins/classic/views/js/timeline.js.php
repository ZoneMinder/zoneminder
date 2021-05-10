var filterQuery = '<?php echo validJsStr($filterQuery) ?>';

<?php
$jsMonitors = array();

$fields = array('Name', 'LabelFormat', 'SaveJPEGs', 'VideoWriter');
foreach ( $monitors as $monitor ) {
  $jsMonitor = array();
  foreach ($fields as $field) {
    $jsMonitor[$field] = $monitor->$field();
  }
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
