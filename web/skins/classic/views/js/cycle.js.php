var monIdx = '<?php echo $monIdx; ?>';
var nextMid = "<?php echo isset($nextMid)?$nextMid:'' ?>";
var mode = "<?php echo $options['mode'] ?>";

var cycleRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_CYCLE ?>;
var monitorData = new Array();
<?php
foreach ( $monitors as $monitor ) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'connKey': <?php echo $monitor->connKey() ?>,
  'width': <?php echo $monitor->Width() ?>,
  'height':<?php echo $monitor->Height() ?>,
  'url': '<?php echo $monitor->UrlToIndex() ?>',
  'onclick': function(){createPopup( '?view=watch&mid=<?php echo $monitor->Id() ?>', 'zmWatch<?php echo $monitor->Id() ?>', 'watch', <?php echo reScale( $monitor->Width(), $monitor->PopupScale() ); ?>, <?php echo reScale( $monitor->Height(), $monitor->PopupScale() ); ?> );},
  'type': '<?php echo $monitor->Type() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>'
};
<?php
} // end foreach monitor
?>

var SCALE_BASE = <?php echo SCALE_BASE ?>;
