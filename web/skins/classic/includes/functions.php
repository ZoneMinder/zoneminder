<?php
//
// ZoneMinder web function library
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


function xhtmlHeaders($file, $title) {
  global $css;
  global $skin;
  global $view;

  # This idea is that we always include the classic css files, 
  # and then any different skin only needs to contain things that are different.
  $baseCssPhpFile = getSkinFile('css/base/skin.css.php');

  $skinCssPhpFile = getSkinFile('css/'.$css.'/skin.css.php');

  $skinJsPhpFile = getSkinFile('js/skin.js.php');
  $cssJsFile = getSkinFile('js/'.$css.'.js');

  $basename = basename($file, '.php');

  $baseViewCssPhpFile = getSkinFile('/css/base/views/'.$basename.'.css.php');
  $viewCssPhpFile = getSkinFile('/css/'.$css.'/views/'.$basename.'.css.php');
  $viewJsFile = getSkinFile('views/js/'.$basename.'.js');
  $viewJsPhpFile = getSkinFile('views/js/'.$basename.'.js.php');

  extract($GLOBALS, EXTR_OVERWRITE);

  function output_link_if_exists($files) {
    global $skin;
    $html = array();
    foreach ( $files as $file ) {
      if ( getSkinFile($file) ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust('skins/'.$skin.'/'.$file).'" type="text/css"/>';
      }
    }
    $html[] = ''; // So we ge a trailing \n
    return implode("\n", $html);
  }
  
  function output_cache_busted_stylesheet_links($files) {
    $html = array();
    foreach ( $files as $file ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust($file).'" type="text/css"/>';
    }
    return implode("\n", $html);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo validHtmlStr(ZM_WEB_TITLE_PREFIX); ?> - <?php echo validHtmlStr($title) ?></title>
<?php
if ( file_exists("skins/$skin/css/$css/graphics/favicon.ico") ) {
  echo "
  <link rel=\"icon\" type=\"image/ico\" href=\"skins/$skin/css/$css/graphics/favicon.ico\"/>
  <link rel=\"shortcut icon\" href=\"skins/$skin/css/$css/graphics/favicon.ico\"/>
";
} else {
  echo '
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
';
}
echo output_cache_busted_stylesheet_links(array(
  'css/reset.css',
  'css/overlay.css',
  'css/bootstrap.min.css',
));

echo output_link_if_exists( array(
  'css/base/skin.css',
  'css/base/views/'.$basename.'.css',
  'js/dateTimePicker/jquery-ui-timepicker-addon.css',
  'js/jquery-ui-1.12.1/jquery-ui.structure.min.css',
)
);
if ( $css != 'base' )
  echo output_link_if_exists( array(
    'css/'.$css.'/skin.css',
    'css/'.$css.'/views/'.$basename.'.css',
    'css/'.$css.'/jquery-ui-theme.css',
  )
);
?>

<link rel="stylesheet" href="skins/classic/js/jquery-ui-1.12.1/jquery-ui.theme.min.css" type="text/css"/>
<!--Chosen can't be cache-busted because it loads sprites by relative path-->
<link rel="stylesheet" href="skins/classic/js/chosen/chosen.min.css" type="text/css"/>
<?php
  if ( $basename == 'watch' ) {
    echo output_link_if_exists(array('/css/base/views/control.css'));
    if ( $css != 'base' )
      echo output_link_if_exists(array('/css/'.$css.'/views/control.css'));
  }
?>
  <style type="text/css">
<?php
  if ( $baseViewCssPhpFile ) {
    require_once($baseViewCssPhpFile);
  }
  if ( $viewCssPhpFile ) {
    require_once($viewCssPhpFile);
  }
?>
  </style>
<?php
?>

<?php if ( $basename != 'login' ) { ?>
  <script src="tools/mootools/mootools-core.js"></script>
  <script src="tools/mootools/mootools-more.js"></script>
  <script src="js/mootools.ext.js"></script>
<?php } ?>
  <script src="skins/<?php echo $skin; ?>/js/jquery.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/jquery-ui-1.12.1/jquery-ui.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/bootstrap.min.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/chosen/chosen.jquery.min.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/dateTimePicker/jquery-ui-timepicker-addon.js"></script>

  <script src="<?php echo cache_bust('js/Server.js'); ?>"></script>
  <script nonce="<?php echo $cspNonce; ?>">
  jQuery(document).ready(function() {
    jQuery("#flip").click(function() {
      jQuery("#panel").slideToggle("slow");
      var flip = jQuery("#flip");
      if ( flip.html() == 'keyboard_arrow_up' ) {
        flip.html('keyboard_arrow_down');
      Cookie.write('zmHeaderFlip', 'down', {duration: 10*365} );
      } else {
        flip.html('keyboard_arrow_up');
        Cookie.write('zmHeaderFlip', 'up', {duration: 10*365} );
      }
    });
  });
  var $j = jQuery.noConflict();
  // $j is now an alias to the jQuery function; creating the new alias is optional.
  </script>
  <script src="<?php echo cache_bust('skins/'.$skin.'/views/js/state.js') ?>"></script>
<?php
  if ( $view == 'event' ) {
?>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
<?php
  }
?>
  <script src="skins/<?php echo $skin ?>/js/moment.min.js"></script>
<?php
  if ( $skinJsPhpFile ) {
?>
  <script nonce="<?php echo $cspNonce; ?>">
<?php
    require_once( $skinJsPhpFile );
?>
  </script>
<?php
  }
  if ( $viewJsPhpFile ) {
?>
  <script nonce="<?php echo $cspNonce; ?>">
<?php
    require_once( $viewJsPhpFile );
?>
  </script>
<?php
  }
	if ( $cssJsFile ) {
?>
  <script src="<?php echo cache_bust($cssJsFile) ?>"></script>
<?php
} else {
?>
  <script src="<?php echo cache_bust('skins/classic/js/base.js') ?>"></script>
<?php }
  $skinJsFile = getSkinFile('js/skin.js');
?>
  <script src="<?php echo cache_bust($skinJsFile) ?>"></script>
  <script src="<?php echo cache_bust('js/logger.js')?>"></script>
<?php 
  if ($basename == 'watch' or $basename == 'log' ) {
  // This is used in the log popup for the export function. Not sure if it's used anywhere else
?>
    <script src="<?php echo cache_bust('js/overlay.js') ?>"></script>
<?php } ?>
<?php
  if ( $viewJsFile ) {
?>
  <script src="<?php echo cache_bust($viewJsFile) ?>"></script>
<?php
  }
?>
</head>
<?php
} // end function xhtmlHeaders( $file, $title )

// Outputs an opening body tag, and any additional content that should go at the very top, like warnings and error messages.
function getBodyTopHTML() {
  echo '
<body>
<noscript>
<div style="background-color:red;color:white;font-size:x-large;">
'. validHtmlStr(ZM_WEB_TITLE) .' requires Javascript. Please enable Javascript in your browser for this site.

</div>
</noscript>
';
  global $error_message;
  if ( $error_message ) {
   echo '<div class="error">'.$error_message.'</div>';
  }
} // end function getBodyTopHTML

function getNavBarHTML($reload = null) {
  # Provide a facility to turn off the headers if you put navbar=0 into the url
  if ( isset($_REQUEST['navbar']) and $_REQUEST['navbar']=='0' )
    return '';

  $versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';
  global $running;
  global $user;
  global $bandwidth_options;
  global $view;
  global $filterQuery;
  global $sortQuery;
  global $limitQuery;

  if (!$sortQuery) {
    parseSort();
  }
  if ( (!$filterQuery) and isset($_REQUEST['filter']) ) {
    parseFilter($_REQUEST['filter']);
    $filterQuery = $_REQUEST['filter']['query'];
  }
  if ( $reload === null ) {
    ob_start();
    if ( $running == null )
      $running = daemonCheck();
    if ( $running ) {
      $state = dbFetchOne('SELECT Name FROM States WHERE isActive=1', 'Name');
      if ( $state == 'default' )
        $state = '';
    }
    $status = $running ? ($state ? $state : translate('Running')) : translate('Stopped');

?>
<div class="fixed-top container-fluid p-0 p-0">
  <nav class="navbar navbar-expand-md navbar-dark bg-dark justify-content-center flex-row">

    <div class="navbar-brand justify-content-start">
      <?php echo getNavBrandHTML() ?>
    </div>

    <!-- the Navigation Bar Hamburger Button   -->
    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
      <span class="sr-only">Toggle navigation</span>
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="main-header-nav">
<?php

  // *** Build the navigation bar menu items ***
  if ( $user and $user['Username'] ) {
        echo '<ul class="navbar-nav justify-content-center">';
          echo getConsoleHTML();
          echo getOptionsHTML();
          echo getLogHTML();
          echo getDevicesHTML();
          echo getGroupsHTML();
          echo getFilterHTML();
          echo getCycleHTML();
          echo getMontageHTML();
          echo getMontageReviewHTML();
          echo getRprtEvntAuditHTML();
          echo getHeaderFlipHTML();
        echo '</ul>';

        echo '<ul class="nav navbar-nav justify-content-end">';
          echo getAcctCircleHTML($user);
          echo getStatusBtnHTML($status);
        echo '</ul>';
  }
?>
    </div>
  </nav><!-- End First Navbar -->

  <nav class="navbar navbar-expand-md bg-dark justify-content-center p-0">
    <div class="container-fluid" id="panel"<?php echo ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down' ) ? 'style="display:none;"' : '' ?>>
<?php

} //end reload null.  Runs on full page load

if ( (!ZM_OPT_USE_AUTH) or $user ) {
  if ($reload == 'reload') ob_start();

// *** Build the statistics shown on the navigation bar ***
?>
  <div id="reload" class="container-fluid">

    <ul id="Bandwidth" class="navbar-nav justify-content-start">
      <?php echo getBandwidthHTML($bandwidth_options,$user) ?>
    </ul>
    
    <ul class="navbar-nav justify-content-center">
      <?php
      echo getSysLoadHTML();
      echo getDbConHTML();
      echo getStorageHTML();
      echo getShmHTML();
      ?>
    </ul>

    <ul id="Version" class="nav navbar-nav justify-content-end">
      <?php echo getZMVersionHTML($versionClass) ?>
    </ul>

  </div>

    <?php echo getConsoleBannerHTML() ?>

    </div>
  </nav><!-- End Second Navbar -->
</div>

<?php
  if ($reload == 'reload') return ob_get_clean();

} // end if (!ZM_OPT_USE_AUTH) or $user )

  return ob_get_clean();
} // end function getNavBarHTML()

// Returns the html representing the current unix style system load
function getSysLoadHTML() {

  echo '<li class="Load nav-item mx-2">'.PHP_EOL;
  echo '<i class="material-icons md-18">trending_up</i>'.PHP_EOL;
  echo '&nbsp;'.translate('Load').': '.getLoad().PHP_EOL;
  echo '</li>'.PHP_EOL;
}

// Returns the html representing the current number of connections made to the database
function getDbConHTML() {
  $connections = dbFetchOne('SHOW status WHERE variable_name=\'threads_connected\'', 'Value');
  $max_connections = dbFetchOne('SHOW variables WHERE variable_name=\'max_connections\'', 'Value');
  $percent_used = $max_connections ? 100 * $connections / $max_connections : 100;
  $class = $percent_used > 90 ? 'text-warning' : '';

  echo '<li class="'. $class .' nav-item mx-2">'.PHP_EOL;
  echo '<i class="material-icons md-18 mr-1">storage</i>'.PHP_EOL;
  echo translate('DB').': '.$connections.'/'.$max_connections.PHP_EOL;   
  echo '</li>'.PHP_EOL;
}

// Returns the html representing up to 4 storage areas and their current capacity
function getStorageHTML() {

  $func = function($S) {
    $class = '';
    if ( $S->disk_usage_percent() > 98 ) {
      $class = 'text-danger';
    } else if ( $S->disk_usage_percent() > 90 ) {
      $class = 'text-warning';
    }
    $title = human_filesize($S->disk_used_space()) . ' of ' . human_filesize($S->disk_total_space()). 
      ( ( $S->disk_used_space() != $S->event_disk_space() ) ? ' ' .human_filesize($S->event_disk_space()) . ' used by events' : '' );
    return '<span class="ml-1'.$class.'" title="'.$title.'">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</span>';
  };

  $storage_areas = ZM\Storage::find(array('Enabled'=>true));
  $num_storage_areas = count($storage_areas);
  $storage_paths = null;
  $storage_areas_with_no_server_id = array();
  foreach ( $storage_areas as $area ) {
    $storage_paths[$area->Path()] = $area;
    if ( ! $area->ServerId() ) {
      $storage_areas_with_no_server_id[] = $area;
    }
  }

  echo '<li class="nav-item mx-2">'.translate('Storage').':';

  if ( $num_storage_areas > 4 ) {
    $storage_areas = $storage_areas_with_no_server_id;
  } else {
    echo implode(', ', array_map($func, $storage_areas));
  }
  echo '</li>'.PHP_EOL;
}

// Returns the html representing the current capacity of mapped memory filesystem (usually /dev/shm)
function getShmHTML() {
  $shm_percent = getDiskPercent(ZM_PATH_MAP);
  $shm_total_space = disk_total_space(ZM_PATH_MAP);
  $shm_used = $shm_total_space - disk_free_space(ZM_PATH_MAP);

  $class = '';
  if ( $shm_percent > 98 ) {
    $class = 'text-danger';
  } else if ( $shm_percent > 90 ) {
    $class = 'text-warning';
  }
  echo ' <li class="'.$class.' nav-item" title="' . human_filesize($shm_used).' of '.human_filesize($shm_total_space).'">'.ZM_PATH_MAP.': '.$shm_percent.'%</li>'.PHP_EOL;
}

// Returns the html representing the optional web console banner text
function getConsoleBannerHTML() {

  if ( defined('ZM_WEB_CONSOLE_BANNER') and ZM_WEB_CONSOLE_BANNER != '' ) {
    echo '<h3 id="development">'.validHtmlStr(ZM_WEB_CONSOLE_BANNER).'</h3>';
  }
}

// Returns the html representing the current high,medium,low bandwidth setting
function getBandwidthHTML($bandwidth_options,$user) {
  echo '<li class="nav-item">'.makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', "<i class='material-icons md-18'>network_check</i>&nbsp;".$bandwidth_options[$_COOKIE['zmBandwidth']] . ' ', ($user && $user['MaxBandwidth'] != 'low' )).'</li>'.PHP_EOL;
}

// Returns the html representing the version of ZoneMinder
function getZMVersionHTML($versionClass) {
  echo '<li class="nav-item">'.makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="version '.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit('System') ).'</li>'.PHP_EOL;
}

// Returns the html representing the ZoneMinder logo
function getNavBrandHTML() {
  echo '<a href="' .validHtmlStr(ZM_HOME_URL). '" target="' .validHtmlStr(ZM_WEB_TITLE). '">' .ZM_HOME_CONTENT. '</a>'.PHP_EOL;
}

// Returns the html representing the Console menu item
function getConsoleHTML() {
  if ( canView('Monitors') ) {
    echo '<li class="nav-item"><a class="nav-link" href="?view=console">'.translate('Console').'</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the Options menu item
function getOptionsHTML() {
  if ( canView('System') ) {
    echo '<li class="nav-item"><a class="nav-link" href="?view=options">'.translate('Options').'</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the Log menu item
function getLogHTML() {
  if ( canView('System') ) {
    if ( ZM\logToDatabase() > ZM\Logger::NOLOG ) { 
      if ( ! ZM_RUN_AUDIT ) {
       # zmaudit can clean the logs, but if we aren't running it, then we should clean them regularly
        if ( preg_match('/^\d+$/', ZM_LOG_DATABASE_LIMIT) ) {
          # Number of lines, instead of an interval
          $rows = dbFetchOne('SELECT Count(*) AS `Rows` FROM `Logs`', 'Rows');
          if ( $rows > ZM_LOG_DATABASE_LIMIT ) {
            dbQuery('DELETE low_priority FROM `Logs` ORDER BY `TimeKey` ASC LIMIT ?', array($rows - ZM_LOG_DATABASE_LIMIT));
          }
        } else if ( preg_match('/^\d\s*(hour|minute|day|week|month|year)$/', ZM_LOG_DATABASE_LIMIT, $matches) ) {
          dbQuery('DELETE FROM `Logs` WHERE `TimeKey` < unix_timestamp( NOW() - interval '.ZM_LOG_DATABASE_LIMIT.') LIMIT 100');
        } else {
          ZM\Error('Potentially invalid value for ZM_LOG_DATABASE_LIMIT: ' . ZM_LOG_DATABASE_LIMIT);
        }
      }
      $logstate = logState();
      $class = $logstate == 'ok' ? 'text-succss' : $logstate == 'alert' ? 'text-warning' : $logstate == 'alarm' ? 'text-danger' : '';
      echo '<li class="nav-item">'.makePopupLink('?view=log', 'zmLog', 'log', '<span class="nav-link '.$class.'">'.translate('Log').'</span></li>').PHP_EOL;
    }
  }
}

// Returns the html representing the X10 Devices menu item
function getDevicesHTML() {
  if ( ZM_OPT_X10 && canView('Devices') ) {
    echo '<li class="nav-item"><a class="nav-link" href="?view=devices">Devices</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the Groups menu item
function getGroupsHTML() {
  global $view;
  $class = $view == 'groups' ? 'selected' : '';
  echo '<li class="nav-item"><a class="nav-link" href="?view=groups" class="' .$class. '">'. translate('Groups') .'</a></li>'.PHP_EOL;
}

// Returns the html representing the Filter menu item
function getFilterHTML() {
  global $view;
  global $filterQuery;
  global $sortQuery;
  global $limitQuery;

  $class = $view == 'filter' ? 'selected' : '';
  echo '<li class="nav-item"><a class="nav-link" href="?view=filter'.$filterQuery.$sortQuery.$limitQuery.'" class="'.$class.'">'.translate('Filters').'</a></li>'.PHP_EOL;
}

// Returns the html representing the Cycle menu item
function getCycleHTML() {
  global $view;
  if ( canView('Stream') ) {
    $class = $view == 'cycle' ? 'selected' : '';
    echo '<li class="nav-item"><a class="nav-link" href="?view=cycle" class="' .$class. '">' .translate('Cycle'). '</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the Montage menu item
function getMontageHTML() {
  global $view;
  if ( canView('Stream') ) {
    $class = $view == 'cycle' ? 'selected' : '';
    echo '<li class="nav-item"><a class="nav-link" href="?view=montage" class="' .$class. '">' .translate('Montage'). '</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the MontageReview menu item
function getMontageReviewHTML() {
  global $view;
  if ( canView('Events') ) {
    if ( isset($_REQUEST['filter']['Query']['terms']['attr']) ) {
      $terms = $_REQUEST['filter']['Query']['terms'];
      $count = 0;
      foreach ($terms as $term) {
        if ( $term['attr'] == 'StartDateTime' ) {
          $count += 1;
          if ($term['op'] == '>=') $minTime = $term['val'];
          if ($term['op'] == '<=') $maxTime = $term['val'];
        }
      }
      if ( $count == 2 ) {
        $montageReviewQuery = '&minTime='.$minTime.'&maxTime='.$maxTime;
      }
    }
    $live = isset($montageReviewQuery) ? '&fit=1'.$montageReviewQuery.'&live=0' : '';
    $class = $view == 'montagereview' ? 'selected' : '';
    echo '<li class="nav-item"><a class="nav-link" href="?view=montagereview' .$live. '" class="' .$class. '">' .translate('MontageReview'). '</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the Audit Events Report menu item
function getRprtEvntAuditHTML() {
  global $view;
  if ( canView('Events') ) {
    $class = $view == 'report_event_audit' ? 'selected' : '';
    echo '<li class="nav-item"><a class="nav-link" href="?view=report_event_audit" class="' .$class. '">' .translate('ReportEventAudit'). '</a></li>'.PHP_EOL;
  }
}

// Returns the html representing the header collapse toggle menu item
function getHeaderFlipHTML() {
  $header = ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down') ? 'down' : 'up';
  echo '<li class="nav-item"><a class="nav-link" href="#"><i id="flip" class="material-icons md-18">keyboard_arrow_' .$header. '</i></a></li>'.PHP_EOL;
}

// Returns the html representing the logged in user name and avatar
function getAcctCircleHTML($user=null) {
  if ( ZM_OPT_USE_AUTH and $user ) {
    echo '<p class="navbar-text">'.PHP_EOL;
      echo '<i class="material-icons">account_circle</i>';
      echo makePopupLink('?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == 'builtin') ).PHP_EOL;
    echo '</p>'.PHP_EOL;
  }
}

// Returns the html representing the runtime status button
function getStatusBtnHTML($status) {
  if ( canEdit('System') ) {
    echo '<li class="nav-item">'.PHP_EOL;
    echo '<form class="form-inline">'.PHP_EOL;
    echo '<button type="button" class="mx-3 btn btn-default navbar-btn" data-toggle="modal" data-target="#modalState">' .$status. '</button>'.PHP_EOL;
    echo '</form>'.PHP_EOL;
    echo '</li>'.PHP_EOL;

    if ( ZM_SYSTEM_SHUTDOWN ) {
      echo '<p class="navbar-text">'.PHP_EOL;
      echo makePopupLink('?view=shutdown', 'zmShutdown', 'shutdown', '<i class="material-icons md-18">power_settings_new</i>' ).PHP_EOL;
      echo '</p>'.PHP_EOL;
     } 

  } else if ( canView('System') ) {
    echo '<p class="navbar-text">'.PHP_EOL;
    echo $status.PHP_EOL;
    echo '</p>'.PHP_EOL;
  }
}

function xhtmlFooter() {
  global $cspNonce;
  global $view;
  global $skin;
  global $running;
  if ( canEdit('System') ) {
    include("skins/$skin/views/state.php");
  }
?>
  <script nonce="<?php echo $cspNonce; ?>">$j('.chosen').chosen();</script>
  </body>
</html>
<?php
} // end xhtmlFooter
?>
