<?php
  global $filterQuery;
  global $sortQuery;
  global $conjunctionTypes;
  global $opTypes;
  global $archiveTypes;
  global $weekdays;
  global $states;
  global $servers;
  global $storageareas;
  global $monitors;
  global $zones;
  global $booleanValues;
  global $filter;
?>
  var filterid = '<?php echo $filter->Id() ?>';
var filterQuery = '<?php echo isset($filterQuery) ? validJsStr(htmlspecialchars_decode($filterQuery)) : '' ?>';
var sortQuery = '<?php echo isset($sortQuery) ? validJsStr(htmlspecialchars_decode($sortQuery)) : '' ?>';

var conjTypes = <?php echo isset($conjunctionTypes) ? json_encode($conjunctionTypes) : '' ?>;
var opTypes = <?php echo isset($opTypes) ? json_encode($opTypes) : '' ?>;

var archiveTypes = <?php echo isset($archiveTypes) ? json_encode($archiveTypes) : '' ?>;
var weekdays = <?php echo isset($weekdays) ? json_encode($weekdays) : '' ?>;
var states = <?php echo isset($states) ? json_encode($states) : '{}' ?>;
var servers = <?php echo isset($servers) ? json_encode($servers) : '{}' ?>;
var storageareas = <?php echo isset($storageareas) ? json_encode($storageareas) : '{}' ?>;
var monitors = <?php echo isset($monitors) ? json_encode($monitors) : '{}' ?>;
var sorted_monitor_ids = <?php echo isset($monitors) ? json_encode(array_keys($monitors)) : '[]' ?>;
var zones = <?php echo isset($zones) ? json_encode($zones) : '{}' ?>;
var booleanValues = <?php echo json_encode($booleanValues) ?>;

var errorBrackets = '<?php echo translate('ErrorBrackets') ?>';
var errorValue = '<?php echo translate('ErrorValidValue') ?>';

var deleteSavedFilterString = "<?php echo translate('DeleteSavedFilter') ?>";
