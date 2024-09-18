//
// Import constants
//

const COMPACT_MONTAGE = <?php echo ZM_WEB_COMPACT_MONTAGE ?>;
const POPUP_ON_ALARM = <?php echo ZM_WEB_POPUP_ON_ALARM ?>;
const ZM_DIR_SOUNDS = '<?php echo ZM_DIR_SOUNDS ?>';

const statusRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;

const canStreamNative = <?php echo canStreamNative()?'true':'false' ?>;
const streamTimeout = <?php echo 1000*ZM_WEB_REFRESH_STATUS ?>;

var monitorData = new Array();

<?php
global $Montage;
global $speeds;
global $speedIndex;

$layouts = $Montage::$layoutsById;
$presetLayoutsNames = $Montage::$presetLayoutsNames; 

echo 'const ZM_PRESET_LAYOUT_NAMES = '.json_encode($presetLayoutsNames).';'.PHP_EOL;
#slider scale, which is only for replay and relative to real time
echo 'var currentSpeed='.$speeds[$speedIndex].';'.PHP_EOL;
echo 'var speedIndex=' . $speedIndex.';'.PHP_EOL;

$monitors = $Montage::$monitors;
foreach ( $monitors as $monitor ) {
?>
monitorData[monitorData.length] = {
  'id': <?php echo $monitor->Id() ?>,
  'name': '<?php echo $monitor->Name() ?>',
  'server_id': '<?php echo $monitor->ServerId() ?>',
  'connKey': '<?php echo $monitor->connKey() ?>',
  'width': <?php echo $monitor->ViewWidth() ?>,
  'height':<?php echo $monitor->ViewHeight() ?>,
  'RTSP2WebEnabled':<?php echo $monitor->RTSP2WebEnabled() ?>,
  'RTSP2WebType':'<?php echo $monitor->RTSP2WebType() ?>',
  'janusEnabled':<?php echo $monitor->JanusEnabled() ?>,
  'url': '<?php echo $monitor->UrlToIndex( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'url_to_zms': '<?php echo $monitor->UrlToZMS( ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '') ?>',
  'url_to_stream': '<?php echo $monitor->UrlToZMS(ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '').'&mode=jpeg&connkey='.$monitor->connKey() ?>',
  'url_to_snapshot': '<?php echo $monitor->UrlToZMS(ZM_MIN_STREAMING_PORT ? ($monitor->Id() + ZM_MIN_STREAMING_PORT) : '').'&mode=single' ?>',
  'onclick': function(){window.location.assign( '?view=watch&mid=<?php echo $monitor->Id() ?>' );},
  'type': '<?php echo $monitor->Type() ?>',
  'capturing': '<?php echo $monitor->Capturing() ?>',
  'refresh': '<?php echo $monitor->Refresh() ?>',
  'janus_pin': '<?php echo $monitor->Janus_Pin() ?>'
};
<?php
} // end foreach monitor
?>
layouts = new Array();
layouts[0] = {}; // reserved, should hold which fields to clear when transitioning
<?php

foreach ( $layouts as $layout ) {
?>
layouts[<?php echo $layout->Id() ?>] = {
  "Name":"<?php echo $layout->Name()?>",
  "UserId":"<?php echo $layout->UserId()?>",
  "Positions":<?php echo json_decode($layout->Positions())?$layout->Positions():'{}' ?>};
<?php
} // end foreach layout
echo 'const autoLayoutName="'.$Montage::$AutoLayoutName.'";'.PHP_EOL;
echo 'const request_montage='.json_encode($_REQUEST).';'.PHP_EOL;

$showZones = false;
if (isset($_REQUEST['showZones'])) {
  if ($_REQUEST['showZones'] == 1) {
    $showZones = true;
  }
}
echo 'const showZones="'.$showZones.'";'.PHP_EOL;

echo 'var speeds=[';
for ( $i=0; $i < count($speeds); $i++ )
  echo (($i>0)?', ':'') . $speeds[$i];
echo "];\n".PHP_EOL;

?>
var translate = {
  "events": "<?php echo translate('events') ?>",
  "TooManyEventsForTimeline": "<?php echo translate('TooManyEventsForTimeline') ?>",
  "Start Time": "<?php echo translate('Start Time') ?>",
};
