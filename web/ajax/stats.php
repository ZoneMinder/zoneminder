<?php
if (!canView('Events')) ajaxError('Insufficient permissions');
if (empty($_REQUEST['eid'])) ajaxError('Event Id Not Provided');
if (empty($_REQUEST['fid'])) ajaxError('Frame Id Not Provided');

# Both are ids and also go into the HTML of the stats table, so keep only digits.
$eid = is_scalar($_REQUEST['eid']) ? validCardinal($_REQUEST['eid']) : '';
$fid = is_scalar($_REQUEST['fid']) ? validCardinal($_REQUEST['fid']) : '';
if ($eid === '' or $fid === '') ajaxError('Invalid event or frame id');
# The row goes into an element id, so keep only characters an id can hold.
$row = (isset($_REQUEST['row']) and is_scalar($_REQUEST['row'])) ? preg_replace('/[^\w\-]/', '', $_REQUEST['row']) : '';
$raw = isset($_REQUEST['raw']);
$data = array();

if ($raw) {
  $data['raw'] = array();
  $sql = 'SELECT S.*,E.*,Z.Name AS ZoneName,Z.Units,Z.Area,M.Name AS MonitorName
    FROM Stats AS S LEFT JOIN Events AS E ON S.EventId = E.Id LEFT JOIN Zones AS Z ON S.ZoneId = Z.Id LEFT JOIN Monitors AS M ON E.MonitorId = M.Id
    WHERE S.EventId = ? AND S.FrameId = ? ORDER BY S.ZoneId';
  $stats = dbFetchAll($sql, NULL, array($eid, $fid));
  foreach ($stats as $stat) {
    // A zone deleted since the event has no name, label it by its id instead of leaving a blank cell
    $stat['ZoneName'] = validHtmlStr(isset($stat['ZoneName']) ? $stat['ZoneName'] : translate('Zone').' '.$stat['ZoneId']);
    $stat['PixelDiff'] = validHtmlStr($stat['PixelDiff']);
    // Zone Area is in the 0-10000 percentage coordinate space, the pixel counts are not.
    $area = floatval($stat['Area']) / 10000.0 * floatval($stat['Width']) * floatval($stat['Height']);
    $px = function($n) use ($area) {
      return $area > 0 ? sprintf('%d (%.1f%%)', $n, 100*$n/$area) : sprintf('%d', $n);
    };
    $stat['AlarmPixels'] = $px($stat['AlarmPixels']);
    $stat['FilterPixels'] = $px($stat['FilterPixels']);
    $stat['BlobPixels'] = $px($stat['BlobPixels']);
    $stat['Blobs'] = validHtmlStr($stat['Blobs']);
    if ($stat['Blobs'] > 1) {
      $stat['BlobSizes'] = $area > 0
        ? sprintf('%d-%d (%.1f%%-%.1f%%)', $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$area), (100*$stat['MaxBlobSize']/$area))
        : sprintf('%d-%d', $stat['MinBlobSize'], $stat['MaxBlobSize']);
    } else {
      $stat['BlobSizes'] = $px($stat['MinBlobSize']);
    }
    $stat['AlarmLimits'] = validHtmlStr($stat['MinX'].','.$stat['MinY'].'-'.$stat['MaxX'].','.$stat['MaxY']);
    $data['raw'][] = $stat;
  } # end foreach stat/zone
} else {
  $data['html'] = getStatsTableHTML($eid, $fid, $row);
  $data['id'] = '#contentStatsTable' .$row;
}

ajaxResponse($data);
return;
?>
