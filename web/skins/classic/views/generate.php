<?php
//
// ZoneMinder web export view file, $Date$, $Revision$
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
// BUT WHY?
$rate = 100;
$scale = 100;

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

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Generate') );

?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Generate') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<?php
if ( !empty($_REQUEST['eids']) ) {
    foreach ( $_REQUEST['eids'] as $eid ) {
?>
        <input type="hidden" name="eids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
    echo "Generating " . count($_REQUEST['eids']) . ' event(s).';
} else {
    echo '<div class="warning">There are no events found.</div>';
}
?>
        
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
      <br>
      <textarea id="result" rows="4" cols="80"></textarea>
    </div>
  </div>
</body>
</html>
