var filterQuery = '<?php echo isset($filterQuery) ? validJsStr(htmlspecialchars_decode($filterQuery)) : '' ?>';
var sortQuery = '<?php echo isset($sortQuery) ? validJsStr(htmlspecialchars_decode($sortQuery)) : '' ?>';

var conjTypes = <?php echo isset($conjunctionTypes) ? json_encode($conjunctionTypes) : '' ?>;
var opTypes = <?php echo isset($opTypes) ? json_encode($opTypes) : '' ?>;

var archiveTypes = <?php echo isset($archiveTypes) ? json_encode($archiveTypes) : '' ?>;
var weekdays = <?php echo isset($weekdays) ? json_encode($weekdays) : '' ?>;
var states = <?php echo isset($states) ? json_encode($states) : '' ?>;
var servers = <?php echo isset($servers) ? json_encode($servers) : '' ?>;
var storageareas = <?php echo isset($storageareas) ? json_encode($storageareas) : '' ?>;
var monitors = <?php echo isset($monitors) ? json_encode($monitors) : '' ?>;

var errorBrackets = '<?php echo translate('ErrorBrackets') ?>';
var errorValue = '<?php echo translate('ErrorValidValue') ?>';

var deleteSavedFilterString = "<?php echo translate('DeleteSavedFilter') ?>";
