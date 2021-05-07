<?php
  global $event;
?>
var eventId = '<?php echo $event->Id() ?>';

var videoGenSuccessString = '<?php echo addslashes(translate('VideoGenSucceeded')) ?>';
var videoGenFailedString = '<?php echo addslashes(translate('VideoGenFailed')) ?>';
var videoGenProgressString = '<?php echo addslashes(translate('GeneratingVideo')) ?>';
var opt_ffmpeg = <?php echo ZM_OPT_FFMPEG ? 'true' : 'false' ?>;
