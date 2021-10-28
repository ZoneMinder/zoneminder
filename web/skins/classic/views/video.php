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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');

$eid = validInt($_REQUEST['eid']);

$event = new ZM\Event($eid);

if ( ! canView('Monitors', $event->MonitorId() ) ) {
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
if ( $dir = opendir($event_path) ) {
  while ( ($file = readdir($dir)) !== false ) {
    $file = $event_path.'/'.$file;
    if ( is_file($file) ) {
      if ( preg_match('/\.(?:'.join('|', $videoFormats).')$/', $file) ) {
        $videoFiles[] = $file;
      }
    }
  }
  closedir($dir);
}

if ( isset($_REQUEST['deleteIndex']) ) {
  $deleteIndex = validInt($_REQUEST['deleteIndex']);
  unlink($videoFiles[$deleteIndex]);
  unset($videoFiles[$deleteIndex]);
}

if ( isset($_REQUEST['downloadIndex']) ) {
  // can't be output buffering, as this file might be large
  ob_end_clean();
  $downloadIndex = validInt($_REQUEST['downloadIndex']);
  ZM\Error("Download $downloadIndex, file: " . $videoFiles[$downloadIndex]);
  header('Pragma: public');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Cache-Control: private', false); // required by certain browsers
  header('Content-Description: File Transfer');
  header('Content-disposition: attachment; filename="'.basename($videoFiles[$downloadIndex]).'"'); // basename is required because the video index contains the path and firefox doesn't strip the path but simply replaces the slashes with an underscore.
  header('Content-Transfer-Encoding: binary');
  header('Content-Type: application/force-download');
  header('Content-Length: '.filesize($videoFiles[$downloadIndex])); 
  readfile($videoFiles[$downloadIndex]);
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
if ( isset($_REQUEST['showIndex']) ) {
  $showIndex = validInt($_REQUEST['showIndex']);
  preg_match('/([^\/]+)\.([^.]+)$/', $videoFiles[$showIndex], $matches);
  $name = $matches[1];
  $videoFormat = $matches[2];
?>
      <h3 id="videoFile"><?php echo substr($videoFiles[$showIndex], strlen(ZM_DIR_EVENTS)+1) ?></h3>
      <div id="imageFeed"><?php outputVideoStream('videoStream', $videoFiles[$showIndex], validInt($_REQUEST['width']), validInt($_REQUEST['height']), $videoFormat, $name) ?></div>
<?php
} else {
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
            <th scope="row"><?php echo translate('Format') ?></th>
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
        preg_match('/^(.+)-((?:r[_\d]+)|(?:F[_\d]+))-((?:s[_\d]+)|(?:S[0-9a-z]+))\.([^.]+)$/', $file, $matches);
        if ( preg_match('/^r(.+)$/', $matches[2], $temp_matches) ) {
          $rate = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
          $rateText = isset($rates[$rate])?$rates[$rate]:($rate."x");
        } elseif ( preg_match('/^F(.+)$/', $matches[2], $temp_matches) ) {
          $rateText = $temp_matches[1].'fps';
        }
        if ( preg_match('/^s(.+)$/', $matches[3], $temp_matches) ) {
          $scale = (int)(100 * preg_replace('/_/', '.', $temp_matches[1]) );
          $scaleText = isset($scales[$scale])?$scales[$scale]:($scale.'x');
        } elseif ( preg_match('/^S(.+)$/', $matches[3], $temp_matches) ) {
          $scaleText = $temp_matches[1];
        }
        $width = $scale?reScale($event->Width(), $scale):$event->Width();
        $height = $scale?reScale($event->Height(), $scale):$event->Height();
?>
        <tr>
          <td><?php echo $matches[4] ?></td>
          <td><?php echo filesize($file) ?></td>
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
<?php
}
?>
    </div>
  </div>
<?php xhtmlFooter() ?>
