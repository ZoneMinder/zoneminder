<?php
//
// ZoneMinder web montage view file, $Date$, $Revision$
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

if (!canView('Stream')) {
  $view = 'error';
  return;
}

require_once('includes/MontageLayout.php');
require_once('includes/Zone.php');

$showControl = false;
$showZones = false;
if (isset($_REQUEST['showZones'])) {
  if ($_REQUEST['showZones'] == 1) {
    $showZones = true;
  }
}
$widths = array( 
  'auto'  => 'auto',
  '160px' => '160px',
  '320px' => '320px',
  '352px' => '352px',
  '640px' => '640px',
  '1280px' => '1280px' );

$heights = array( 
  'auto'  => 'auto',
  '240px' => '240px',
  '270px' => '270px',
  '320px' => '320px',
  '480px' => '480px',
  '720px' => '720px',
  '1080px' => '1080px',
);

$monitorStatusPositon = array( 
  'insideImgBottom'  => translate('Inside bottom'),
  'outsideImgBottom' => translate('Outside bottom'),
  'hidden' => translate('Hidden'),
);

$monitorStatusPositonSelected = 'outsideImgBottom';

if (isset($_REQUEST['monitorStatusPositonSelected'])) {
  $monitorStatusPositonSelected = $_REQUEST['monitorStatusPositonSelected'];
} else if (isset($_COOKIE['zmMonitorStatusPositonSelected'])) {
  $monitorStatusPositonSelected = $_COOKIE['zmMonitorStatusPositonSelected'];
}

$layouts = ZM\MontageLayout::find(NULL, array('order'=>"lower('Name')"));
$layoutsById = array();
$FreeFormLayoutId = 0;
foreach ( $layouts as $l ) {
  if ( $l->Name() == 'Freeform' ) {
    $FreeFormLayoutId = $l->Id();
    $layoutsById[$l->Id()] = $l;
    break;
  }
}
foreach ( $layouts as $l ) {
  if ( $l->Name() != 'Freeform' )
    $layoutsById[$l->Id()] = $l;
}

zm_session_start();

$layout_id = '';
if ( isset($_COOKIE['zmMontageLayout']) ) {
  $layout_id = $_SESSION['zmMontageLayout'] = $_COOKIE['zmMontageLayout'];
} elseif ( isset($_SESSION['zmMontageLayout']) ) {
  $layout_id = $_SESSION['zmMontageLayout'];
}

$options = array();

if (isset($_REQUEST['zmMontageWidth'])) {
  $width = $_REQUEST['zmMontageWidth'];
  if (($width == 'auto') or preg_match('/^\d+px$/', $width))
    $_SESSION['zmMontageWidth'] = $options['width'] = $width;
} else if (isset($_COOKIE['zmMontageWidth'])) {
  $width = $_COOKIE['zmMontageWidth'];
  if (($width == 'auto') or preg_match('/^\d+px$/', $width))
    $_SESSION['zmMontageWidth'] = $options['width'] = $width;
} else if (isset($_SESSION['zmMontageWidth']) and $_SESSION['zmMontageWidth']) {
  $width = $_SESSION['zmMontageWidth'];
  if (($width == 'auto') or preg_match('/^\d+px$/', $width))
    $options['width'] = $width;
} else {
  $options['width'] = 0;
}

if (isset($_REQUEST['zmMontageHeight'])) {
  $height = $_REQUEST['zmMontageHeight'];
  if (($height == 'auto') or preg_match('/^\d+px$/', $height))
    $_SESSION['zmMontageHeight'] = $options['height'] = $height;
} else if (isset($_COOKIE['zmMontageHeight'])) {
  $height = $_COOKIE['zmMontageHeight'];
  if (($height == 'auto') or preg_match('/^\d+px$/', $height))
    $_SESSION['zmMontageHeight'] = $options['height'] = $height;
} else if (isset($_SESSION['zmMontageHeight']) and $_SESSION['zmMontageHeight']) {
  $height = $_SESSION['zmMontageHeight'];
  if (($height == 'auto') or preg_match('/^\d+px$/', $height))
    $options['height'] = $height;
} else {
  $options['height'] = 0;
}

$scale = '';   # auto
if (isset($_REQUEST['scale'])) {
  $scale = $_REQUEST['scale'];
} else if (isset($_COOKIE['zmMontageScale'])) {
  $scale = $_COOKIE['zmMontageScale'];
}
if ($scale != 'fixed' and $scale != 'auto') {
  $scale = validNum($scale);
/* So far so, otherwise when opening with scalex2, etc. The image is larger than the screen and everything slows down...
scaleControl is no longer used!
  $options['scale'] = $scale;
*/
}

session_write_close();

ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$need_hls = false;
$need_janus = false;
$monitors = array();
foreach ($displayMonitors as &$row) {
  if ($row['Capturing'] == 'None')
    continue;

  $row['Scale'] = $scale;
  $row['PopupScale'] = reScale(SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);

  if (ZM_OPT_CONTROL && $row['ControlId'] && $row['Controllable'])
    $showControl = true;
  if (!isset($widths[$row['Width'].'px'])) {
    $widths[$row['Width'].'px'] = $row['Width'].'px';
  }
  if (!isset($heights[$row['Height'].'px'])) {
    $heights[$row['Height'].'px'] = $row['Height'].'px';
  }
  $monitor = $monitors[] = new ZM\Monitor($row);

  if ( $monitor->RTSP2WebEnabled() and $monitor->RTSP2WebType == "HLS") {
    $need_hls = true;
  }
  if ($monitor->JanusEnabled()) {
    $need_janus = true;
  }
} # end foreach Monitor

if (!$layout_id) {
  $default_layout = '';
  if (!$default_layout) {
    if ((count($monitors) > 5) and (count($monitors)%5 == 0)) {
      $default_layout = '5 Wide';
    } else if ((count($monitors) > 4) and (count($monitors)%4 == 0)) {
      $default_layout = '4 Wide';
    } else if (count($monitors)%3 == 0) {
      $default_layout = '3 Wide';
    } else {
      $default_layout = '2 Wide';
    }
  }
  foreach ($layouts as $l) {
    if ($l->Name() == $default_layout) {
      $layout_id = $l->Id();
    }
  }
}
$Layout = '';
$Positions = '';
if ( $layout_id and is_numeric($layout_id) and isset($layoutsById[$layout_id]) ) {
  $Layout = $layoutsById[$layout_id];
  $Positions = json_decode($Layout->Positions(), true);
} else {
  ZM\Debug('Layout not found');
}

xhtmlHeaders(__FILE__, translate('Montage'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="header">
<?php
    $html = '<a class="flip" href="#" 
             data-flip-сontrol-object="#mfbpanel" 
             data-flip-сontrol-run-after-func="applyChosen" 
             data-flip-сontrol-run-after-complet-func="changeScale">
               <i id="mfbflip" class="material-icons md-18" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i>
             </a>'.PHP_EOL;
    $html .= '<div id="mfbpanel" class="hidden-shift container-fluid">'.PHP_EOL;
    echo $html;
?>
      <div id="headerButtons">
<?php
if ($showControl) {
  echo makeLink('?view=control', translate('Control'));
}
if (canView('System')) {
  if ($showZones) {
  ?>
    <a id="HideZones" href="?view=montage&amp;showZones=0"><?php echo translate('Hide Zones')?></a>
  <?php
  } else {
  ?>
    <a id="ShowZones" href="?view=montage&amp;showZones=1"><?php echo translate('Show Zones')?></a>
  <?php
  }
}
?>
      </div>
      <form method="get">
        <input type="hidden" name="view" value="montage"/>
        <?php echo $filterbar ?>
      </form>
      <div id="sizeControl">
        <form action="?view=montage" method="post">
          <input type="hidden" name="object" value="MontageLayout"/>
          <input type="hidden" name="action" value="Save"/>

          <span id="monitorStatusPositonControl">
            <label><?php echo translate('Monitor status position') ?></label>
            <?php echo htmlSelect('monitorStatusPositon', $monitorStatusPositon, $monitorStatusPositonSelected, array('id'=>'monitorStatusPositon', 'data-on-change'=>'changeMonitorStatusPositon', 'class'=>'chosen')); ?>
          </span>
          <span id="widthControl" class="hidden">
            <label><?php echo translate('Width') ?></label>
            <?php echo htmlSelect('width', $widths, 'auto'/*$options['width']*/, array('id'=>'width', 'data-on-change'=>'changeWidth', 'class'=>'chosen')); ?>
          </span>
          <span id="heightControl" class="hidden">
            <label><?php echo translate('Height') ?></label>
            <?php echo htmlSelect('height', $heights, 'auto'/*$options['height']*/, array('id'=>'height', 'data-on-change'=>'changeHeight', 'class'=>'chosen')); ?>
          </span>
          <span id="scaleControl" class="hidden">
            <label><?php echo translate('Scale') ?></label>
            <?php echo htmlSelect('scale', $scales, '0'/*$scale*/, array('id'=>'scale', 'data-on-change-this'=>'changeScale', 'class'=>'chosen')); ?>
          </span> 
          <span id="layoutControl">
            <label for="layout"><?php echo translate('Layout') ?></label>
            <?php echo htmlSelect('zmMontageLayout', $layoutsById, $layout_id, array('id'=>'zmMontageLayout', 'data-on-change'=>'selectLayout', 'class'=>'chosen')); ?>
          </span>
          <input type="hidden" name="Positions"/>
          <button type="button" id="EditLayout" data-on-click-this="edit_layout"><?php echo translate('EditLayout') ?></button>
          <span id="SaveLayout" style="display:none;">
            <input type="text" name="Name" placeholder="Enter new name for layout if desired" autocomplete="off"/>
            <button type="button" value="Save" data-on-click-this="save_layout"><?php echo translate('Save') ?></button>
            <button type="button" value="Cancel" data-on-click-this="cancel_layout"><?php echo translate('Cancel') ?></button>
          </span>

<?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
          <button type="button" name="snapshotBtn" data-on-click-this="takeSnapshot">
            <i class="material-icons md-18">camera_enhance</i>
            &nbsp;<?php echo translate('Snapshot') ?>
          </button>
<?php } ?>
          <button type="button" id="fullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="avail" data-on-click="watchFullscreen">
          <i class="material-icons md-18">fullscreen</i>
          </button>
        </form>
      </div>
    </div>
  </div>
  <div id="content">
    <div id="monitors" class="grid-stack hidden-shift">
<?php
foreach ($monitors as $monitor) {
  $monitor_options = $options;
  #ZM\Debug('Options: ' . print_r($monitor_options,true));

  if ($monitor->Type() == 'WebSite') {
    echo getWebSiteUrl(
      'liveStream'.$monitor->Id(),
      $monitor->Path(),
      (isset($options['width']) ? $options['width'] : reScale($monitor->ViewWidth(), $scale).'px' ),
      (isset($options['height']) ? $options['height'] : reScale($monitor->ViewHeight(), $scale).'px' ),
      $monitor->Name()
    );
  } else {
    $monitor_options['state'] = !ZM_WEB_COMPACT_MONTAGE;
    $monitor_options['zones'] = $showZones;
    $monitor_options['mode'] = 'single';
    echo $monitor->getStreamHTML($monitor_options);
  }
} # end foreach monitor
?>
      </div>
    </div>
  </div>
  <script src="<?php echo cache_bust('js/adapter.min.js') ?>"></script>
<?php if ($need_janus) { ?>
  <script src="/javascript/janus/janus.js"></script>
<?php } ?>
<?php if ($need_hls) { ?>
  <script src="<?php echo cache_bust('js/hls.js') ?>"></script>
<?php } ?>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>

<!-- In May 2024, IgorA100 globally changed grid layout -->
<div id="messageModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Error reading Layout')?></h5>
      </div>
      <div class="modal-body">
        <span id="message-error"></span>
        <span><?php echo translate('This Layout was saved in previous version of ZoneMinder!')?></span>
        <br>
        <span><?php echo translate('It is necessary to place monitors again and resave the Layout.')?></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Close') ?></button>
      </div>
    </div>
  </div>
</div>
