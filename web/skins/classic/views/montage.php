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

$monitorStatusPosition = array( 
  'insideImgBottom'  => translate('Inside bottom'),
  'outsideImgBottom' => translate('Outside bottom'),
  'hidden' => translate('Hidden'),
  'showOnHover' => translate('Show on hover'),
);

$monitorStatusPositionSelected = 'outsideImgBottom';
if (isset($_REQUEST['monitorStatusPositionSelected'])) {
  $monitorStatusPositionSelected = $_REQUEST['monitorStatusPositionSelected'];
} else if (isset($_COOKIE['zmMonitorStatusPositionSelected'])) {
  $monitorStatusPositionSelected = $_COOKIE['zmMonitorStatusPositionSelected'];
}

$layouts = ZM\MontageLayout::find(NULL, array('order'=>"lower('Name')"));
// layoutsById is used in the dropdown, so needs to be sorted
$layoutsById = array();
$presetLayoutsNames = array( //Order matters!
  'Auto',
  '1 Wide',
  '2 Wide',
  '3 Wide',
  '4 Wide',
  '6 Wide',
  '8 Wide',
  '12 Wide',
  '16 Wide',
  '24 Wide',
  '48 Wide'
);

/* Create an array "Name"=>layouts to make it easier to find IDs by name */
$layoutsByName = array();
foreach ($layouts as $l) {
  /* IMPORTANT! 
  * Before GridStack integration, instead of the layer name "Auto", we used "Freeform" in the DB.
  * Before deleting this check, you need to replace "Freeform" with "Auto" in the DB of already installed ZM !!!
  */
  if ($l->Name() == 'Freeform') $l->Name('Auto');
  $layoutsByName[$l->Name()] = $l;
}

/* Fill with preinstalled Layouts. They should always come first.
 * Also sorting 1 Wide and 11 Wide fails... so need a smarter sort
 */
foreach ($presetLayoutsNames as $name) {
  if (array_key_exists($name, $layoutsByName)) // Layout may be missing in DB (rare case during update process)
    $layoutsById[$layoutsByName[$name]->Id()] = $layoutsByName[$name];
}

/* Add custom Layouts & assign objects instead of names for preset Layouts */
foreach ($layouts as $l) {
  $layoutsById[$l->Id()] = $l;
}

zm_session_start();

$layout_id = 0;
if (isset($_REQUEST['zmMontageLayout'])) {
  $layout_id = $_SESSION['zmMontageLayout'] = validCardinal($_REQUEST['zmMontageLayout']);
} else if ( isset($_COOKIE['zmMontageLayout']) ) {
  $layout_id = $_SESSION['zmMontageLayout'] = validCardinal($_COOKIE['zmMontageLayout']);
} else if ( isset($_SESSION['zmMontageLayout']) ) {
  $layout_id = validCardinal($_SESSION['zmMontageLayout']);
}
if (!$layout_id || !isset($layoutsById[$layout_id])) {
  $layout_id = $layoutsByName['Auto']->Id();
}
$layout = $layoutsById[$layout_id];
$layout_is_preset = array_search($layout->Name(), $presetLayoutsNames) === false ? false : true;

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

$streamQualitySelected = '0';
if (isset($_REQUEST['streamQuality'])) {
  $streamQualitySelected = $_REQUEST['streamQuality'];
} else if (isset($_COOKIE['zmStreamQuality'])) {
  $streamQualitySelected = $_COOKIE['zmStreamQuality'];
} else if (isset($_SESSION['zmStreamQuality']) ) {
  $streamQualitySelected = $_SESSION['zmStreamQuality'];
}

if (!empty($_REQUEST['maxfps']) and validFloat($_REQUEST['maxfps']) and ($_REQUEST['maxfps']>0)) {
  $options['maxfps'] = validHtmlStr($_REQUEST['maxfps']);
} else if (isset($_COOKIE['zmMontageRate'])) {
  $options['maxfps'] = validHtmlStr($_COOKIE['zmMontageRate']);
} else {
  $options['maxfps'] = ''; // unlimited
}

session_write_close();

include('_monitor_filters.php');
$resultMonitorFilters = buildMonitorsFilters();
$filterbar = $resultMonitorFilters['filterBar'];
$displayMonitors = $resultMonitorFilters['displayMonitors'];

$need_hls = false;
$need_janus = false;
$monitors = array();
foreach ($displayMonitors as &$row) {
  if ($row['Capturing'] == 'None')
    continue;

  $row['Scale'] = $scale;

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

$default_layout = '';

$monitorCount = count($monitors);
if ($monitorCount <= 3) {
  $default_layout = $monitorCount . ' Wide';
} else if ($monitorCount <= 4) {
  $default_layout = '2 Wide';
} else if ($monitorCount <= 6) {
  $default_layout = '3 Wide';
} else if ($monitorCount%4 == 0) {
  $default_layout = '4 Wide';
} else if ($monitorCount%6 == 0) {
  $default_layout = '6 Wide';
} else {
  $default_layout = '4 Wide';
}

$AutoLayoutName = $default_layout;

xhtmlHeadersStart(__FILE__, translate('Montage'));
echo output_link(array('/assets/gridstack-12.3.3/dist/gridstack.css', '/assets/gridstack-12.3.3/dist/gridstack-extra.css'));
xhtmlHeadersEnd(__FILE__, translate('Montage'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
  <div id="header"<?php echo (isset($_REQUEST['header']) and ($_REQUEST['header']=='0' or $_REQUEST['header']=='hidden')) ? ' style="display:none;"' : '' ?>>
<?php
    $html = '<a class="flip" href="#" 
             data-flip-control-object="#mfbpanel" 
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
      <form method="get" name="monitorFiltersForm" id="monitorFiltersForm">
        <input type="hidden" name="view" value="montage"/>
        <?php echo $filterbar ?>
      </form>
      <div id="sizeControl">
        <form action="?view=montage" method="post">
          <input type="hidden" name="object" value="MontageLayout"/>
          <input id="action" type="hidden" name="action" value=""/> <?php // "value" is generated in montage.js depending on the action "Save" or "Delete"?>

          <span id="monitorStatusPositionControl">
            <label><?php echo translate('Monitor status position') ?></label>
            <?php echo htmlSelect('monitorStatusPosition', $monitorStatusPosition, $monitorStatusPositionSelected, array('id'=>'monitorStatusPosition', 'data-on-change'=>'changeMonitorStatusPosition', 'class'=>'chosen')); ?>
          </span>
          <span id="rateControl">
            <label for="changeRate"><?php echo translate('Rate') ?>:</label>
            <?php
$maxfps_options = array(''=>translate('Unlimited'),
  '0.10' => '1/10' .translate('FPS'),
  '0.50' => '1/2' .translate('FPS'),
  '1' => '1 '.translate('FPS'),
  '2' => '2 '.translate('FPS'),
  '5' => '5 '.translate('FPS'),
  '10' => '10 '.translate('FPS'),
  '20' => '20 '.translate('FPS'),
);
echo htmlSelect('changeRate', $maxfps_options, $options['maxfps'], array('id'=>'changeRate', 'data-on-change'=>'changeMonitorRate', 'class'=>'chosen'));
?>
          </span>
          <span id="ratioControl">
            <label><?php echo translate('Ratio') ?></label>
            <?php echo htmlSelect('ratio', [], '', array('id'=>'ratio', 'data-on-change'=>'changeRatioForAll', 'class'=>'chosen')); ?>
          </span>
          <span id="streamQualityControl">
            <label for="streamQuality"><?php echo translate('Stream quality') ?></label>
            <?php echo htmlSelect('streamQuality', $streamQuality, $streamQualitySelected, array('data-on-change'=>'changeStreamQuality','id'=>'streamQuality', 'class'=>'chosen')); ?>
          </span>
          <span id="layoutControl">
            <label for="zmMontageLayout"><?php echo translate('Layout') ?></label>
            <?php echo htmlSelect('zmMontageLayout', $layoutsById, $layout_id, array('id'=>'zmMontageLayout', 'data-on-change'=>'selectLayout', 'class'=>'chosen')); ?>
          </span>
          <input type="hidden" name="Positions"/>
          <button type="button" id="EditLayout" data-on-click-this="edit_layout"><?php echo translate('EditLayout') ?></button>
          <button type="button" id="btnDeleteLayout" class="btn btn-danger" value="Delete" data-on-click-this="delete_layout" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete layout') ?>" disabled><i class="material-icons md-18">delete</i></button>
          <span id="SaveLayout" style="display:none;">
            <input type="text" name="Name" placeholder="<?php echo translate('Enter new name for layout if desired') ?>" autocomplete="off"/>
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
    </div><!--header-->
  </div>
  <div id="content">
    <div id="monitors" class="grid-stack hidden-shift">
<?php
foreach ($monitors as $monitor) {
  $monitor_options = $options;
  #ZM\Debug('Options: ' . print_r($monitor_options,true));
  $monitor_options['scale'] = 50; # ensure defined value, but not 100 because this is montage... we assume at least 2 streams

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
    # If we start up in a streaming mode, even paused, the content-type=mixed etc makes Chrome queue the requests for 15s.  We are stuck with just getting a single image to start, then switching to streaming in js.
    $monitor_options['mode'] = 'single';
    $monitor_options['connkey'] = $monitor->connKey();
    $browser_width = 1920;
    if (isset($_COOKIE['zmBrowserSizes'])) {
      $zmBrowserSizes =  jsonDecode($_COOKIE['zmBrowserSizes']);
      $browser_width = validInt($zmBrowserSizes['innerWidth']);
      if (!$browser_width) $browser_width = 1920;
    }
    if (!$scale and ($layout->Name() != 'Auto')) {
      if ($layout_is_preset) {
        # We know the # of columns so can figure out a proper scale
        if (preg_match('/^(\d+) Wide$/', $layout->Name(), $matches)) {
          if ($matches[1]) {
            $monitor_options['scale'] = intval(100*(($browser_width/$matches[1])/$monitor->Width()));
            if ($monitor_options['scale'] < 10) $monitor_options['scale'] = 10;
            else if ($monitor_options['scale'] > 100) $monitor_options['scale'] = 100;
          }
        }
      }
    } else {
      $divisor = count($monitors)/2;
      if ($divisor < 2) $divisor = 2;

      # Custom, default to 25% of 1920 for now, because 25% of a 4k is very different from 25% of 640px
      $monitor_options['scale'] = intval(100*(($browser_width/$divisor)/$monitor->Width()));
      if ($monitor_options['scale'] > 100) $monitor_options['scale'] = 100;
      else if ($monitor_options['scale'] < 10) $monitor_options['scale'] = 10;
    }
    $monitor->initial_scale($monitor_options['scale']);
    echo $monitor->getStreamHTML($monitor_options);
  } # end if monitor type == Website
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
  <script src="<?php echo cache_bust('js/hls-1.6.13/hls.min.js') ?>"></script>
<?php } ?>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>

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
<?php
  echo '<script src="skins/'.$skin.'/assets/gridstack-12.3.3/dist/gridstack-all.js"></script>';
  echo output_script_if_exists(array('assets/jquery.panzoom/dist/jquery.panzoom.js'));
  echo output_script_if_exists(array('js/panzoom.js'));
  echo '<script type="module" src="js/video-stream.js"></script>'.PHP_EOL;
  xhtmlFooter() ?>
