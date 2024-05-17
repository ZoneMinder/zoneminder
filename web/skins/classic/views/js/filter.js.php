<?php
  global $filterQuery;
  global $sortQuery;
  global $conjunctionTypes;
  global $opTypes;
  global $tags_opTypes;
  global $archiveTypes;
  global $weekdays;
  global $states;
  global $servers;
  global $storageareas;
  global $monitors;
  global $availableTags;
  global $zones;
  global $booleanValues;
  global $filter;
?>
const filterid = '<?php echo $filter->Id() ?>';
const filter = <?php echo json_encode($filter) ?>;
const filterQuery = '<?php echo isset($filterQuery) ? validJsStr(htmlspecialchars_decode($filterQuery)) : '' ?>';
const sortQuery = '<?php echo isset($sortQuery) ? validJsStr(htmlspecialchars_decode($sortQuery)) : '' ?>';

const conjTypes = <?php echo isset($conjunctionTypes) ? json_encode($conjunctionTypes) : '' ?>;
const opTypes = <?php echo isset($opTypes) ? json_encode($opTypes) : '' ?>;

const archiveTypes = <?php echo isset($archiveTypes) ? json_encode($archiveTypes) : '' ?>;
const tags_opTypes = <?php echo isset($tags_opTypes) ? json_encode($tags_opTypes) : '' ?>;
const weekdays = <?php echo isset($weekdays) ? json_encode($weekdays) : '' ?>;
const states = <?php echo isset($states) ? json_encode($states) : '{}' ?>;
const servers = <?php echo isset($servers) ? json_encode($servers) : '{}' ?>;
const storageareas = <?php echo isset($storageareas) ? json_encode($storageareas) : '{}' ?>;
const monitors = <?php echo isset($monitors) ? json_encode($monitors) : '{}' ?>;
const availableTags = <?php echo isset($availableTags) ? json_encode($availableTags) : '[]' ?>;
const sorted_monitor_ids = <?php echo isset($monitors) ? json_encode(array_keys($monitors)) : '[]' ?>;
const zones = <?php echo isset($zones) ? json_encode($zones) : '{}' ?>;
const booleanValues = <?php echo json_encode($booleanValues) ?>;

const errorBrackets = '<?php echo translate('ErrorBrackets') ?>';
const errorValue = '<?php echo translate('ErrorValidValue') ?>';

const deleteSavedFilterString = "<?php echo translate('DeleteSavedFilter') ?>";
