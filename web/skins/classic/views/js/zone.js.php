var presets = new Hash();
<?php
foreach ( $presets as $preset )
{
?>
presets[<?= $preset['Id'] ?>] = {
    'UnitsIndex': <?= $preset['UnitsIndex'] ?>,
    'CheckMethodIndex': <?= $preset['CheckMethodIndex'] ?>,
    'MinPixelThreshold': '<?= $preset['MinPixelThreshold'] ?>',
    'MaxPixelThreshold': '<?= $preset['MaxPixelThreshold'] ?>',
    'FilterX': '<?= $preset['FilterX'] ?>',
    'FilterY': '<?= $preset['FilterY'] ?>',
    'MinAlarmPixels': '<?= $preset['MinAlarmPixels'] ?>',
    'MaxAlarmPixels': '<?= $preset['MaxAlarmPixels'] ?>',
    'MinFilterPixels': '<?= $preset['MinFilterPixels'] ?>',
    'MaxFilterPixels': '<?= $preset['MaxFilterPixels'] ?>',
    'MinBlobPixels': '<?= $preset['MinBlobPixels'] ?>',
    'MaxBlobPixels': '<?= $preset['MaxBlobPixels'] ?>',
    'MinBlobs': '<?= $preset['MinBlobs'] ?>',
    'MaxBlobs': '<?= $preset['MaxBlobs'] ?>',
    'OverloadFrames': '<?= $preset['OverloadFrames'] ?>'
};
<?php
}
?>

var zone = {
    'Name': '<?= validJsStr($zone['Name']) ?>',
    'Id': <?= validJsStr($zone['Id']) ?>,
    'MonitorId': <?= validJsStr($zone['MonitorId']) ?>,
    'CheckMethod': '<?= $zone['CheckMethod'] ?>',
    'AlarmRGB': '<?= $zone['AlarmRGB'] ?>',
    'NumCoords': <?= $zone['NumCoords'] ?>,
    'Coords': '<?= $zone['Coords'] ?>',
    'Area': <?= $zone['Area'] ?>
};

zone['Points'] = new Array();
<?php
for ( $i = 0; $i < count($newZone['Points']); $i++ )
{
?>
zone['Points'][<?= $i ?>] = { 'x': <?= $newZone['Points'][$i]['x'] ?>, 'y': <?= $newZone['Points'][$i]['y'] ?> };
<?php
}
?>

var maxX = <?= $monitor['Width']-1 ?>;
var maxY = <?= $monitor['Height']-1 ?>;
var selfIntersecting = <?= $selfIntersecting?'true':'false' ?>;

var selfIntersectingString = '<?= addslashes($SLANG['SelfIntersecting']) ?>';
var alarmRGBUnsetString = '<?= addslashes($SLANG['AlarmRGBUnset']) ?>';
var minPixelThresUnsetString = '<?= addslashes($SLANG['MinPixelThresUnset']) ?>';
var minPixelThresLtMaxString = '<?= addslashes($SLANG['MinPixelThresLtMax']) ?>';
var filterUnsetString = '<?= addslashes($SLANG['FilterUnset']) ?>';
var minAlarmAreaUnsetString = '<?= addslashes($SLANG['MinAlarmAreaUnset']) ?>';
var minAlarmAreaLtMaxString = '<?= addslashes($SLANG['MinAlarmAreaLtMax']) ?>';
var minFilterAreaUnsetString = '<?= addslashes($SLANG['MinFilterAreaUnset']) ?>';
var minFilterAreaLtMaxString = '<?= addslashes($SLANG['MinFilterAreaLtMax']) ?>';
var minFilterLtMinAlarmString = '<?= addslashes($SLANG['MinFilterLtMinAlarm']) ?>';
var minBlobAreaUnsetString = '<?= addslashes($SLANG['MinBlobAreaUnset']) ?>';
var minBlobAreaLtMaxString = '<?= addslashes($SLANG['MinBlobAreaLtMax']) ?>';
var minBlobLtMinFilterString = '<?= addslashes($SLANG['MinBlobLtMinFilter']) ?>';
var minBlobsUnsetString = '<?= addslashes($SLANG['MinBlobsUnset']) ?>';
var minBlobsLtMaxString = '<?= addslashes($SLANG['MinBlobsLtMax']) ?>';
