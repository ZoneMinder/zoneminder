var presets = new Object();
<?php
foreach ( $presets as $preset )
{
?>
presets[<?php echo $preset['Id'] ?>] = {
    'UnitsIndex': <?php echo $preset['UnitsIndex'] ?>,
    'CheckMethodIndex': <?php echo $preset['CheckMethodIndex'] ?>,
    'MinPixelThreshold': '<?php echo $preset['MinPixelThreshold'] ?>',
    'MaxPixelThreshold': '<?php echo $preset['MaxPixelThreshold'] ?>',
    'FilterX': '<?php echo $preset['FilterX'] ?>',
    'FilterY': '<?php echo $preset['FilterY'] ?>',
    'MinAlarmPixels': '<?php echo $preset['MinAlarmPixels'] ?>',
    'MaxAlarmPixels': '<?php echo $preset['MaxAlarmPixels'] ?>',
    'MinFilterPixels': '<?php echo $preset['MinFilterPixels'] ?>',
    'MaxFilterPixels': '<?php echo $preset['MaxFilterPixels'] ?>',
    'MinBlobPixels': '<?php echo $preset['MinBlobPixels'] ?>',
    'MaxBlobPixels': '<?php echo $preset['MaxBlobPixels'] ?>',
    'MinBlobs': '<?php echo $preset['MinBlobs'] ?>',
    'MaxBlobs': '<?php echo $preset['MaxBlobs'] ?>',
    'OverloadFrames': '<?php echo $preset['OverloadFrames'] ?>',
    'ExtendAlarmFrames': '<?php echo $preset['ExtendAlarmFrames'] ?>'
};
<?php
}
?>

var zone = {
    'Name': '<?php echo validJsStr($zone['Name']) ?>',
    'Id': <?php echo validJsStr($zone['Id']) ?>,
    'MonitorId': <?php echo validJsStr($zone['MonitorId']) ?>,
    'CheckMethod': '<?php echo $zone['CheckMethod'] ?>',
    'AlarmRGB': '<?php echo $zone['AlarmRGB'] ?>',
    'NumCoords': <?php echo $zone['NumCoords'] ?>,
    'Coords': '<?php echo $zone['Coords'] ?>',
    'Area': <?php echo $zone['Area'] ?>
};

zone['Points'] = new Array();
<?php
for ( $i = 0; $i < count($newZone['Points']); $i++ )
{
?>
zone['Points'][<?php echo $i ?>] = { 'x': <?php echo $newZone['Points'][$i]['x'] ?>, 'y': <?php echo $newZone['Points'][$i]['y'] ?> };
<?php
}
?>

var maxX = <?php echo $monitor['Width']-1 ?>;
var maxY = <?php echo $monitor['Height']-1 ?>;
var selfIntersecting = <?php echo $selfIntersecting?'true':'false' ?>;

var selfIntersectingString = '<?php echo addslashes(translate('SelfIntersecting')) ?>';
var alarmRGBUnsetString = '<?php echo addslashes(translate('AlarmRGBUnset')) ?>';
var minPixelThresUnsetString = '<?php echo addslashes(translate('MinPixelThresUnset')) ?>';
var minPixelThresLtMaxString = '<?php echo addslashes(translate('MinPixelThresLtMax')) ?>';
var filterUnsetString = '<?php echo addslashes(translate('FilterUnset')) ?>';
var minAlarmAreaUnsetString = '<?php echo addslashes(translate('MinAlarmAreaUnset')) ?>';
var minAlarmAreaLtMaxString = '<?php echo addslashes(translate('MinAlarmAreaLtMax')) ?>';
var minFilterAreaUnsetString = '<?php echo addslashes(translate('MinFilterAreaUnset')) ?>';
var minFilterAreaLtMaxString = '<?php echo addslashes(translate('MinFilterAreaLtMax')) ?>';
var minFilterLtMinAlarmString = '<?php echo addslashes(translate('MinFilterLtMinAlarm')) ?>';
var minBlobAreaUnsetString = '<?php echo addslashes(translate('MinBlobAreaUnset')) ?>';
var minBlobAreaLtMaxString = '<?php echo addslashes(translate('MinBlobAreaLtMax')) ?>';
var minBlobLtMinFilterString = '<?php echo addslashes(translate('MinBlobLtMinFilter')) ?>';
var minBlobsUnsetString = '<?php echo addslashes(translate('MinBlobsUnset')) ?>';
var minBlobsLtMaxString = '<?php echo addslashes(translate('MinBlobsLtMax')) ?>';
