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

if ( !canView('Stream') ) {
  $view = 'error';
  return;
}

require_once('includes/MontageLayout.php');

$showControl = false;
$showZones = false;
if ( isset($_REQUEST['showZones']) ) {
  if ( $_REQUEST['showZones'] == 1 ) {
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
  '320px' => '320px',
  '480px' => '480px',
  '720px' => '720px',
  '1080px' => '1080px',
);

$scale = '100';   # actual

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmMontageScale']) ) {
  $scale = $_COOKIE['zmMontageScale'];
}

$layouts = ZM\MontageLayout::find(NULL, array('order'=>"lower('Name')"));
$layoutsById = array();
foreach ( $layouts as $l ) {
  if ( $l->Name() == 'Freeform' ) {
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
  ZM\Logger::Debug("Using layout $layout_id");
} elseif ( isset($_SESSION['zmMontageLayout']) ) {
  $layout_id = $_SESSION['zmMontageLayout'];
  ZM\Logger::Debug("Using layout $layout_id from session");
}

$options = array();
$Layout = '';
$Positions = '';
if ( $layout_id and is_numeric($layout_id) and isset($layoutsById[$layout_id]) ) {
  $Layout = $layoutsById[$layout_id];
  $Positions = json_decode($Layout->Positions(), true);
} else {
  ZM\Logger::Debug("Layout not found");
}
if ( $Layout and ( $Layout->Name() != 'Freeform' ) ) {
  // Use layout instead of other options
}

if ( isset($_COOKIE['zmMontageWidth']) and $_COOKIE['zmMontageWidth'] ) {
  $_SESSION['zmMontageWidth'] = $options['width'] = $_COOKIE['zmMontageWidth'];
#} elseif ( isset($_SESSION['zmMontageWidth']) and $_SESSION['zmMontageWidth'] ) {
  #$options['width'] = $_SESSION['zmMontageWidth'];
} else
  $options['width'] = '';

if ( isset($_COOKIE['zmMontageHeight']) and $_COOKIE['zmMontageHeight'] )
  $_SESSION['zmMontageHeight'] = $options['height'] = $_COOKIE['zmMontageHeight'];
#else if ( isset($_SESSION['zmMontageHeight']) and $_SESSION['zmMontageHeight'] )
  #$options['height'] = $_SESSION['zmMontageHeight'];
else
  $options['height'] = '';

#if ( $scale ) 
  $options['scale'] = $scale;

session_write_close();

ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$monitors = array();
foreach( $displayMonitors as &$row ) {
  if ( $row['Function'] == 'None' )
    continue;

  $row['Scale'] = $scale;
  $row['PopupScale'] = reScale(SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);

  if ( ZM_OPT_CONTROL && $row['ControlId'] && $row['Controllable'] )
    $showControl = true;
  $row['connKey'] = generateConnKey();
  if ( ! isset($widths[$row['Width']]) ) {
    $widths[$row['Width'].'px'] = $row['Width'].'px';
  }
  if ( ! isset($heights[$row['Height']]) ) {
    $heights[$row['Height'].'px'] = $row['Height'].'px';
  }
  $monitors[] = new ZM\Monitor($row);
} # end foreach Monitor

xhtmlHeaders(__FILE__, translate('Montage'));
?>
<body>
  <div id="page">
    <?php echo getNavBarHTML() ?>
    <div id="header">&nbsp;&nbsp;
      <a href="#"><span id="hdrbutton" class="glyphicon glyphicon-menu-up pull-right" title="Toggle Filters"></span></a>
      <div id="flipMontageHeader">
        <div id="headerButtons">
<?php
if ( $showControl ) {
  echo makePopupLink('?view=control', 'zmControl', 'control', translate('Control'));
}
if ( $showZones ) {
?>
        <a id="ShowZones" href="?view=montage&amp;showZones=0">Hide Zones</a>
<?php
} else {
?>
        <a id="ShowZones" href="?view=montage&amp;showZones=1">Show Zones</a>
<?php
}
?>
      </div>
      <form method="get">
        <input type="hidden" name="view" value="montage"/>
        <?php echo $filterbar ?>
      </form>
      <div id="sizeControl">
        <form action="index.php?view=montage" method="post">
          <input type="hidden" name="object" value="MontageLayout"/>
          <input type="hidden" name="action" value="Save"/>

          <span id="widthControl">
            <label><?php echo translate('Width') ?></label>
            <?php echo htmlSelect('width', $widths, $options['width'], array('data-on-change-this'=>'changeSize')); ?>
          </span>
          <span id="heightControl">
            <label><?php echo translate('Height') ?></label>
            <?php echo htmlSelect('height', $heights, $options['height'], array('data-on-change-this'=>'changeSize')); ?>
          </span>
          <span id="scaleControl">
            <label><?php echo translate('Scale') ?></label>
            <?php echo htmlSelect('scale', $scales, $scale, array('data-on-change-this'=>'changeScale')); ?>
          </span> 
          <span id="layoutControl">
            <label for="layout"><?php echo translate('Layout') ?></label>
            <?php echo htmlSelect('zmMontageLayout', $layoutsById, $layout_id, array('data-on-change-this'=>'selectLayout')); ?>
          </span>
          <input type="hidden" name="Positions"/>
          <button type="button" id="EditLayout" data-on-click-this="edit_layout"><?php echo translate('EditLayout') ?></button>
          <span id="SaveLayout" style="display:none;">
            <input type="text" name="Name" placeholder="Enter new name for layout if desired"/>
            <button type="button" value="Save" data-on-click-this="save_layout"><?php echo translate('Save') ?></button>
            <button type="button" value="Cancel" data-on-click-this="cancel_layout"><?php echo translate('Cancel') ?></button>
          </span>
        </form>
      </div>
    </div>
  </div>
  <div id="content">
    <div id="monitors">
<?php
foreach ( $monitors as $monitor ) {
  $connkey = $monitor->connKey(); // Minor hack
?>
        <div
          id="monitorFrame<?php echo $monitor->Id() ?>"
          class="monitorFrame"
          title="<?php echo $monitor->Id() . ' ' .$monitor->Name() ?>"
          style="<?php echo $options['width'] ? 'width:'.$options['width'].';':''?>"
        >
          <div id="monitor<?php echo $monitor->Id() ?>" class="monitor idle">
            <div
              id="imageFeed<?php echo $monitor->Id() ?>"
              class="imageFeed"
              data-monitor-id="<?php echo $monitor->Id() ?>"
              data-width="<?php echo reScale( $monitor->Width(), $monitor->PopupScale() ); ?>"
              data-height="<?php echo reScale( $monitor->Height(), $monitor->PopupScale() ); ?>">
            <?php
  $monitor_options = $options;
  if (0 and $Positions ) {
    $monitor_options['width'] = '100%';
    $monitor_options['height'] = '100%';
    if ( 0 ) {
    if ( isset($Positions[$monitor->Id()]) ) {
      $monitor_options = array();
      #$monitor_options = $Positions[$monitor->Id()];
    } else if ( isset($Positions['default']) ) {
      $monitor_options = array();
      #$monitor_options = $Positions['default'];
    }
    }
  }

  if ( $monitor->Type() == 'WebSite' ) {
    echo getWebSiteUrl(
      'liveStream'.$monitor->Id(),
      $monitor->Path(),
      (isset($options['width']) ? $options['width'] : reScale($monitor->Width(), $scale).'px' ),
      ( isset($options['height']) ? $options['height'] : reScale($monitor->Height(), $scale).'px' ),
      $monitor->Name()
    );
  } else {
    echo getStreamHTML($monitor, $monitor_options);
  }
  if ( $showZones ) { 
    $height = null;
    $width = null;
    if ( $options['width'] ) {
      $width = $options['width'];
      if ( !$options['height'] ) {
        $scale = (int)( 100 * $options['width'] / $monitor->Width() );
        $height = reScale($monitor->Height(), $scale).'px';
      }
    } else if ( $options['height'] ) {
      $height = $options['height'];
      if ( !$options['width'] ) {
        $scale = (int)( 100 * $options['height'] / $monitor->Height() );
        $width = reScale($monitor->Width(), $scale).'px';
      }
    } else if ( $scale ) {
      $width = reScale($monitor->Width(), $scale).'px';
      $height = reScale($monitor->Height(), $scale).'px';
    } 

    $zones = array();
    foreach( dbFetchAll('SELECT * FROM Zones WHERE MonitorId=? ORDER BY Area DESC', NULL, array($monitor->Id()) ) as $row ) {
      $row['Points'] = coordsToPoints($row['Coords']);

      if ( $scale ) {
        limitPoints($row['Points'], 0, 0, $monitor->Width(), $monitor->Height());
      } else {
        limitPoints($row['Points'], 0, 0, 
            ( $width ? $width-1 : $monitor->Width()-1 ),
            ( $height ? $height-1 : $monitor->Height()-1 )
            );
      }
      $row['Coords'] = pointsToCoords($row['Points']);
      $row['AreaCoords'] = preg_replace('/\s+/', ',', $row['Coords']);
      $zones[] = $row;
    } // end foreach Zone
?>

<svg class="zones" id="zones<?php echo $monitor->Id() ?>" style="position:absolute; top: 0; left: 0; background: none; width: 100%; height: 100%;" viewBox="0 0 <?php echo  $monitor->Width() ?> <?php echo  $monitor->Height() ?>" preserveAspectRatio="none">
<?php
foreach( array_reverse($zones) as $zone ) {
  echo '<polygon points="'. $zone['AreaCoords'] .'" class="'. $zone['Type'].'" />';
} // end foreach zone
?>
  Sorry, your browser does not support inline SVG
</svg>
<?php
  } # end if showZones
?>
            </div>
<?php
  if ( (!ZM_WEB_COMPACT_MONTAGE) && ($monitor->Type() != 'WebSite') ) {
?>
            <div id="monitorState<?php echo $monitor->Id() ?>" class="monitorState idle"><?php echo translate('State') ?>:&nbsp;<span id="stateValue<?php echo $monitor->Id() ?>"></span>&nbsp;-&nbsp;<span id="fpsValue<?php echo $monitor->Id() ?>"></span>&nbsp;fps</div>
<?php
  }
?>
          </div>
        </div>
<?php
} # end foreach monitor
?>
      </div>
    </div>
  </div>
<?php xhtmlFooter() ?>
