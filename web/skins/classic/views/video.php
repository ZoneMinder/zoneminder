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

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?';
$sql_values = array($eid);

if ( $user['MonitorIds'] ) {
  $monitor_ids = explode(',', $user['MonitorIds']);
  $sql .= ' AND MonitorId IN ('.implode(',', array_fill(0,count($monitor_ids),'?')).')';
  $sql_values = array_merge($sql_values, $monitor_ids);
}
$event = dbFetchOne($sql, NULL, $sql_values);

if ( isset($_REQUEST['rate']) )
  $rate = validInt($_REQUEST['rate']);
else
  $rate = reScale(RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE);
if ( isset($_REQUEST['scale']) )
  $scale = validInt($_REQUEST['scale']);
else
  $scale = reScale(SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE);

$Event = new Event($event['Id']);
$eventPath = $Event->Path();

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
if ( $dir = opendir($eventPath) ) {
  while ( ($file = readdir($dir)) !== false ) {
    $file = $eventPath.'/'.$file;
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
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Video') ?></h2>
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
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="id" value="<?php echo $event['Id'] ?>"/>
        <table id="contentTable" class="minor">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('VideoFormat') ?></th>
              <td><?php echo buildSelect('videoFormat', $videoFormats) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('FrameRate') ?></th>
              <td><?php echo buildSelect('rate', $rates) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('VideoSize') ?></th>
              <td><?php echo buildSelect('scale', $scales) ?></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('OverwriteExisting') ?></th>
              <td><input type="checkbox" name="overwrite" value="1"<?php if ( !empty($_REQUEST['overwrite']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
          </tbody>
        </table>
        <input type="button" value="<?php echo translate('GenerateVideo') ?>" onclick="generateVideo(this.form);"<?php if ( !ZM_OPT_FFMPEG ) { ?> disabled="disabled"<?php } ?>/>
      </form>
<?php
  if ( isset($_REQUEST['generated']) ) {
?>
      <h2 id="videoProgress" class="<?php echo $_REQUEST['generated']?'infoText':'errorText' ?>">
        <span id="videoProgressText"><?php echo $_REQUEST['generated']?translate('VideoGenSucceeded'):translate('VideoGenFailed') ?></span>
        <span id="videoProgressTicker"></span>
      </h2>
<?php
  } else {
?>
      <h2 id="videoProgress" class="hidden warnText">
        <span id="videoProgressText"><?php echo translate('GeneratingVideo') ?></span>
        <span id="videoProgressTicker"></span>
      </h2>
<?php
  }
?>
      <h2 id="videoFilesHeader"><?php echo translate('VideoGenFiles') ?></h2>
<?php
  if ( count($videoFiles) == 0 ) {
?>
      <h3 id="videoNoFiles"><?php echo translate('VideoGenNoFiles') ?></h3>
<?php
  } else {
?>
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
        $width = $scale?reScale($event['Width'], $scale):$event['Width'];
        $height = $scale?reScale($event['Height'], $scale):$event['Height'];
?>
        <tr>
          <td><?php echo $matches[4] ?></td>
          <td><?php echo filesize($file) ?></td>
          <td><?php echo $rateText ?></td>
          <td><?php echo $scaleText ?></td>
          <td><?php echo makePopupLink('?view='.$view.'&amp;eid='.$event['Id'].'&amp;width='.$width.'&amp;height='.$height.'&amp;showIndex='.$index, 'zmVideo'.$event['Id'].'-'.$scale, array( 'videoview', $width, $height ), translate('View') ); ?>&nbsp;/&nbsp;<a href="<?php echo substr( $file, strlen(ZM_DIR_EVENTS)+1 ) ?>" onclick="downloadVideo( <?php echo $index ?> ); return( false );"><?php echo translate('Download') ?></a>&nbsp;/&nbsp;<a href="#" onclick="deleteVideo( <?php echo $index ?> ); return( false );"><?php echo translate('Delete') ?></a></td>
        </tr>
<?php
        $index++;
      }
    }
?>
        </tbody>
      </table>
<?php
  }
}
?>
    </div>
  </div>
</body>
</html>
