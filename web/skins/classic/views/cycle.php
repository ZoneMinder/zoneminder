<?php
//
// ZoneMinder web cycle view file, $Date$, $Revision$
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

if ( !canView('Stream') ) {
  $view = 'error';
  return;
}

ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$options = array();
$options['mode'] = 'single';

if ( empty($_REQUEST['mode']) ) {
  if ( canStream() )
    $mode = 'stream';
  else
    $mode = 'still';
} else {
  $mode = validHtmlStr($_REQUEST['mode']);
}

$widths = array(
  'auto'  => translate('auto'),
  '100%'  => '100%',
  '160px' => '160px',
  '320px' => '320px',
  '352px' => '352px',
  '640px' => '640px',
  '1280px' => '1280px',
  '1920px'  =>  '1920px'
);

$heights = array(
  'auto'  => translate('auto'),
  '240px' => '240px',
  '480px' => '480px',
  '720px' => '720px',
  '1080px'  =>  '1080px',
);


$monIdx = 0;
$monitors = array();
$monitor = NULL;
foreach( $displayMonitors as &$row ) {
  if ( $row['Function'] == 'None' )
    continue;
  if ( isset($_REQUEST['mid']) && ($row['Id'] == $_REQUEST['mid']) )
    $monIdx = count($monitors);

  $row['ScaledWidth'] = reScale($row['Width'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
  $row['ScaledHeight'] = reScale($row['Height'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
  $row['PopupScale'] = reScale(SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
   if ( !isset($widths[$row['Width'].'px']) ) {
    $widths[$row['Width'].'px'] = $row['Width'].'px';
  }
  if ( ! isset($heights[$row['Height'].'px']) ) {
    $heights[$row['Height'].'px'] = $row['Height'].'px';
  }

  $monitors[] = new ZM\Monitor($row);
  unset($row);
} # end foreach Monitor

if ( $monitors ) {
  $monitor = $monitors[$monIdx];
  $nextMid = $monIdx==(count($monitors)-1)?$monitors[0]->Id():$monitors[$monIdx+1]->Id();
}
if ( !$monitor ) {
  ZM\Error('There was no monitor to display.');
}

zm_session_start();

if ( isset($_REQUEST['scale']) ) {
  $options['scale'] = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmCycleScale']) ) {
  $options['scale'] = $_COOKIE['zmCycleScale'];
} else if ( $monitor ) {
  $options['scale'] = $monitor->DefaultScale();
}

if ( !isset($options['scale']) )
  $options['scale'] = 100;

if ( isset($_COOKIE['zmCycleWidth']) and $_COOKIE['zmCycleWidth'] ) {
  $_SESSION['zmCycleWidth'] = $options['width'] = $_COOKIE['zmCycleWidth'];
#} elseif ( isset($_SESSION['zmCycleWidth']) and $_SESSION['zmCycleWidth'] ) {
  #$options['width'] = $_SESSION['zmCycleWidth'];
} else {
  $options['width'] = '';
}

if ( isset($_COOKIE['zmCycleHeight']) and $_COOKIE['zmCycleHeight'] ) {
  $_SESSION['zmCycleHeight'] = $options['height'] = $_COOKIE['zmCycleHeight'];
#else if ( isset($_SESSION['zmCycleHeight']) and $_SESSION['zmCycleHeight'] )
  #$options['height'] = $_SESSION['zmCycleHeight'];
} else {
  $options['height'] = '';
}

session_write_close();

noCacheHeaders();
xhtmlHeaders(__FILE__, translate('CycleWatch'));
?>
<body>
  <div id="page">
    <?php echo $navbar = getNavBarHTML(); ?>
    <div id="header">
      <div id="headerButtons">
<?php if ( $mode == 'stream' ) { ?>
        <a href="?view=<?php echo $view ?>&amp;mode=still&amp;mid=<?php echo $monitor ? $monitor->Id() : '' ?>"><?php echo translate('Stills') ?></a>
<?php } else { ?>
        <a href="?view=<?php echo $view ?>&amp;mode=stream&amp;mid=<?php echo $monitor ? $monitor->Id() : '' ?>"><?php echo translate('Stream') ?></a>
<?php } ?>
      </div>
      <div class="controlHeader">
        <form method="get">
          <input type="hidden" name="view" value="cycle"/>
          <?php echo $filterbar ?>
        </form>
      </div>
      <div id="sizeControl">
        <span id="widthControl">
          <label><?php echo translate('Width') ?></label>
          <?php echo htmlSelect('width', $widths, $options['width'], array('id'=>'width', 'data-on-change-this'=>'changeSize') ); ?>
        </span>
        <span id="heightControl">
          <label><?php echo translate('Height') ?></label>
          <?php echo htmlSelect('height', $heights, $options['height'], array('id'=>'height', 'data-on-change-this'=>'changeSize') ); ?>
        </span>
        <span id="scaleControl">
          <label><?php echo translate('Scale') ?></label>
          <?php echo htmlSelect('scale', $scales, $options['scale'], array('id'=>'scale', 'data-on-change-this'=>'changeScale') ); ?>
        </span>
      </div>
    </div>
    <div class="container-fluid h-100">
      <div class="row flex-nowrap h-100" id="content">
        <nav id="sidebar" class="h-100">
          <ul class="nav nav-pills flex-column h-100">
  <?php
  foreach ( $monitors as $m ) {
            echo '<li class="nav-item"><a class="nav-link'.( $m->Id() == $monitor->Id() ? ' active' : '' ).'" href="?view=cycle&amp;mid='.$m->Id().'">'.$m->Name().'</a></li>';
  }
  ?>
          </ul>
        </nav>
        <div class="container-fluid col-sm-offset-2 h-100 pr-0">
          <div class="monitor" id="monitor<?php echo $monitor->Id() ?>">
            <div id="imageFeed<?php echo $monitor->Id() ?>" class="imageFeed">
          <?php 
            if ( $monitor ) {
              echo getStreamHTML($monitor, $options);
            } else {
              echo 'There are no monitors to view.';
            }
          ?>
            </div>
          </div>

          <div id="buttons" class="buttons">
            <button type="button" value="&lt;" id="prevBtn" title="<?php echo translate('PreviousMonitor') ?>" class="active" data-on-click-true="cyclePrev">&lt;&lt;</button>
            <button type="button" value="||" id="pauseBtn" title="<?php echo translate('PauseCycle') ?>" class="active" data-on-click-true="cyclePause">||</button>
            <button type="button" value="|&gt;" id="playBtn" title="<?php echo translate('PlayCycle') ?>" class="inactive" disabled="disabled" data-on-click-true="cycleStart">|&gt;</button>
            <button type="button" value="&gt;" id="nextBtn" title="<?php echo translate('NextMonitor') ?>" class="active" data-on-click-true="cycleNext">&gt;&gt;</button>
          </div>

        </div>
      </div>
    </div>
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>
