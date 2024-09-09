<?php
//
// ZoneMinder web video view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if (!canView('Events')) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');

$eid = validInt($_REQUEST['eid']);

$event = new ZM\Event($eid);

if (!canView('Monitors', $event->MonitorId())) {
  $view = 'error';
  return;
}

$monitor = $event->Monitor();
if ( isset($_REQUEST['rate']) )
  $rate = validInt($_REQUEST['rate']);
else
  $rate = reScale(RATE_BASE, $monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);

if ( isset($_REQUEST['scale']) )
  $scale = validInt($_REQUEST['scale']);
else
  $scale = reScale(SCALE_BASE, $monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE);

$event_path = $event->Path();

$videoFormats = array();
$ffmpegFormats = preg_split('/\s+/', ZM_FFMPEG_FORMATS);
foreach ( $ffmpegFormats as $ffmpegFormat ) {
  if ( preg_match('/^([^*]+)(\*\*?)$/', $ffmpegFormat, $matches) ) {
    $videoFormats[$matches[1]] = $matches[1];
    if ( !isset($videoFormat) && $matches[2] == '*' ) {
      $videoFormat = $matches[1];
    }
  } else {
    $videoFormats[$ffmpegFormat] = $ffmpegFormat;
  }
}

$videoFiles = array();
if ($dir = opendir($event_path)) {
  while (($file = readdir($dir)) !== false) {
    $file = $event_path.'/'.$file;
    if (is_file($file)) {
      if (preg_match('/\.(?:'.join('|', $videoFormats).')$/', $file)) {
        $videoFiles[] = $file;
      }
    }
  }
  closedir($dir);
}



if (isset($_REQUEST['deleteIndex'])) {
  $deleteIndex = validCardinal($_REQUEST['deleteIndex']);
  unlink($videoFiles[$deleteIndex]);
  unset($videoFiles[$deleteIndex]);
} else if (isset($_REQUEST['downloadIndex'])) {
  if (!count($videoFiles)) {
    ZM\Warning("No video files found for $eid. Downloading not possible.");
  } else {
    $downloadIndex = validInt($_REQUEST['downloadIndex']);
    ZM\Debug("Download $downloadIndex, file: " . $videoFiles[$downloadIndex]);
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required by certain browsers
    header('Content-Description: File Transfer');
    header('Content-disposition: attachment; filename="'.basename($videoFiles[$downloadIndex]).'"'); // basename is required because the video index contains the path and firefox doesn't strip the path but simply replaces the slashes with an underscore.
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: application/force-download');
    header('Content-Length: '.filesize($videoFiles[$downloadIndex])); 
    // can't be output buffering, as this file might be large
    while (ob_get_level()) {
      ob_end_clean();
    }
    set_time_limit(0);
    readfile($videoFiles[$downloadIndex]);
  }
  exit;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Video'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <div class="w-100 py-1">
      <div class="float-left pl-3">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
        <button type="button" id="videoBtn" class="btn btn-normal" data-on-click="generateVideo" data-toggle="tooltip" data-placement="top" title="<?php echo translate('GenerateVideo') ?>" disabled><i class="fa fa-file-video-o"></i></button>
      </div>
      <div class="w-100 pt-2">
        <h2><?php echo translate('Video') ?></h2>
      </div>
    </div>
    <div id="content">
<?php
if (isset($_REQUEST['showIndex'])) {
  $showIndex = validInt($_REQUEST['showIndex']);
  $path_parts = pathinfo($videoFiles[$showIndex]);

  $width = $event->Width();
  $height = $event->Height();

  foreach (explode('-', $path_parts['filename']) as $option) {
    if ( preg_match('/^s(.+)$/', $option, $temp_matches) ) {
      $scale = (int)(preg_replace('/_/', '.', $temp_matches[1]) );
      $width = $width * $scale;
      $height = $height * $scale;
    } elseif ( preg_match('/^S(\d+)x(\d+)$/', $option, $temp_matches) ) {
      $width = $temp_matches[1];
      $height = $temp_matches[2];
    }
  }
?>
      <h3 id="videoFile"><?php echo $path_parts['basename'] ?></h3>
      <div id="imageFeed"><?php outputVideoStream('videoStream',
        '?view=view_video&event_id='.$eid.'&file='.urlencode($path_parts['basename']),
      (isset($_REQUEST['width']) ? validInt($_REQUEST['width']) : $width),
      (isset($_REQUEST['height']) ? validInt($_REQUEST['height']) : $height),
      $path_parts['extension'], $path_parts['filename']) ?></div>
<?php
}
?>
      <form name="contentForm" id="videoForm" method="post" action="?">
        <input type="hidden" name="id" value="<?php echo $event->Id() ?>"/>
        <table id="contentTable" class="minor">
          <tbody>
            <tr>
              <th class="text-nowrap text-right pr-3" scope="row"><?php echo translate('VideoFormat') ?></th>
              <td><?php echo buildSelect('videoFormat', $videoFormats) ?></td>
            </tr>
            <tr>
              <th class="text-nowrap text-right pr-3" scope="row"><?php echo translate('FrameRate') ?></th>
              <td><?php echo buildSelect('rate', $rates) ?></td>
            </tr>
            <tr>
              <th class="text-nowrap text-right pr-3" scope="row"><?php echo translate('VideoSize') ?></th>
              <td><?php echo buildSelect('scale', $scales) ?></td>
            </tr>
            <tr>
              <th class="text-nowrap text-right pr-3" scope="row"><?php echo translate('Transform') ?></th>
              <td>
<?php 
  $transforms = array(
    ''=>translate('None'),
    'hue=s=0'=>translate('Grayscale'),
  );
  echo buildSelect('transform', $transforms);
?>
              </td>
            </tr>
            <tr>
              <th class="text-nowrap text-right pr-3" scope="row"><?php echo translate('OverwriteExisting') ?></th>
              <td><input type="checkbox" name="overwrite" value="1"<?php if ( !empty($_REQUEST['overwrite']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
          </tbody>
        </table>
      </form>
      <h2 id="videoProgress" class="text-warning invisible"> 
        <span class="spinner-grow" role="status" aria-hidden="true"></span> 
        <?php echo translate('GeneratingVideo') ?>
      </h2>
      <h2 id="videoFilesHeader"><?php echo translate('VideoGenFiles') ?></h2>

      <table id="videoTable" class="major">
        <thead>
          <tr>
            <th scope="row"><?php echo translate('Filename') ?></th>
            <th scope="row"><?php echo translate('Size') ?></th>
            <th scope="row"><?php echo translate('Rate') ?></th>
            <th scope="row"><?php echo translate('Scale') ?></th>
            <th scope="row"><?php echo translate('Action') ?></th>
          </tr>
        </thead>
        <tbody>
<?php

  if ( count($videoFiles) == 0 ) {
       ?>
      <td>No Video Files Found</td>
      <?php
  } else {
    $index = 0;
    foreach ( $videoFiles as $file ) {
      if ( filesize($file) > 0 ) {
        $path_parts = pathinfo($file);

        foreach (explode('-', $path_parts['filename']) as $option) {
          if ( preg_match('/^r(.+)$/', $option, $temp_matches) ) {
            $rate = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
            $rateText = isset($rates[$rate])?$rates[$rate]:($rate."x");
          } elseif ( preg_match('/^F(.+)$/', $option, $temp_matches) ) {
            $rateText = $temp_matches[1].'fps';
          } else if ( preg_match('/^s(.+)$/', $option, $temp_matches) ) {
            $scale = (int)(100 * preg_replace('/_/', '.', $temp_matches[1]) );
            $scaleText = isset($scales[$scale])?$scales[$scale]:($scale.'x');
          } elseif ( preg_match('/^S(.+)$/', $option, $temp_matches) ) {
            $scaleText = $temp_matches[1];
          }
        } # end foreach option in filename
?>
        <tr>
          <td>
            <a href="?view=video&eid=<?php echo $event->Id() ?>&showIndex=<?php echo $index ?>"><?php echo $path_parts['basename'] ?></a></td>
          <td><?php echo human_filesize(filesize($file)) ?></td>
          <td><?php echo $rateText ?></td>
          <td><?php echo $scaleText ?></td>
          <td>
            <a href="?view=video&eid=<?php echo $event->Id() ?>&downloadIndex=<?php echo $index ?>"><?php echo translate('Download') ?></a>
               &nbsp;/&nbsp;
            <a href="?view=video&eid=<?php echo $event->Id() ?>&deleteIndex=<?php echo $index ?>"><?php echo translate('Delete') ?></a>
          </td>
        </tr>
<?php
        $index++;
      } # end if filesize
    } # end foreach videoFile
  }
?>
        </tbody>
      </table>
    </div>
  </div>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
<?php xhtmlFooter() ?>
