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

if ( empty($_REQUEST['mode']) ) {
  if ( canStream() )
    $mode = 'stream';
  else
    $mode = 'still';
} else {
  $mode = validHtmlStr($_REQUEST['mode']);
}
ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$monIdx = 0;
$monitors = array();
foreach( $displayMonitors as &$row ) {
  if ( $row['Function'] == 'None' )
    continue;
  if ( isset($_REQUEST['mid']) && $row['Id'] == $_REQUEST['mid'] )
    $monIdx = count($monitors);

  $row['ScaledWidth'] = reScale($row['Width'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
  $row['ScaledHeight'] = reScale($row['Height'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
  $row['PopupScale'] = reScale(SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);

  $row['connKey'] = generateConnKey();
  $monitors[] = new Monitor($row);
} # end foreach Monitor

if ( $monitors ) {
  $monitor = $monitors[$monIdx];
  $nextMid = $monIdx==(count($monitors)-1)?$monitors[0]->Id():$monitors[$monIdx+1]->Id();
  $montageWidth = $monitor->ScaledWidth();
  $montageHeight = $monitor->ScaledHeight();
  $widthScale = ($montageWidth*SCALE_BASE)/$monitor->Width();
  $heightScale = ($montageHeight*SCALE_BASE)/$monitor->Height();
  $scale = (int)(($widthScale<$heightScale)?$widthScale:$heightScale);
}

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
    </div>
    <div id="content">
      <div id="imageFeed">
      <?php 
        if ( $monitor ) {
          echo getStreamHTML($monitor, array('scale'=>$scale, 'mode'=>$mode, 'width'=>'100%'));
        } else {
          echo "There are no monitors to view.";
        }
      ?>
      </div>
    </div>
  </div>
<?php xhtmlFooter() ?>
