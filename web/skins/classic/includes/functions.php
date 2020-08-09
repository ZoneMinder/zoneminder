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
  ob_start();

  global $css;
  global $skin;
  global $view;
  global $cspNonce;

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

  function output_link_if_exists($files) {
    global $skin;
    $html = array();
    foreach ( $files as $file ) {
      if ( getSkinFile($file) ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust('skins/'.$skin.'/'.$file).'" type="text/css"/>';
      }
    }
    $html[] = ''; // So we ge a trailing \n
    return implode(PHP_EOL, $html);
  }
  
  function output_cache_busted_stylesheet_links($files) {
    $html = array();
    foreach ( $files as $file ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust($file).'" type="text/css"/>';
    }
    $html[] = ''; // So we ge a trailing \n
    return implode(PHP_EOL, $html);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo validHtmlStr(ZM_WEB_TITLE_PREFIX) . ' - ' . validHtmlStr($title) ?></title>
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

echo output_link_if_exists(array(
  'css/base/skin.css',
  'css/base/views/'.$basename.'.css',
  'js/dateTimePicker/jquery-ui-timepicker-addon.css',
  'js/jquery-ui-1.12.1/jquery-ui.structure.min.css',
));
if ( $css != 'base' )
  echo output_link_if_exists(array(
    'css/'.$css.'/skin.css',
    'css/'.$css.'/views/'.$basename.'.css',
    'css/'.$css.'/jquery-ui-theme.css',
  ));
?>
  <link rel="stylesheet" href="skins/classic/js/jquery-ui-1.12.1/jquery-ui.theme.min.css" type="text/css"/>
  <?php #Chosen can't be cache-busted because it loads sprites by relative path ?>
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

<?php if ( $basename != 'login' and $basename != 'postlogin' ) { ?>
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
  <script nonce="<?php echo $cspNonce; ?>">var $j = jQuery.noConflict();</script>
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
  echo ob_get_clean();
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

function getNavBarHTML() {
  # Provide a facility to turn off the headers if you put navbar=0 into the url
  if ( isset($_REQUEST['navbar']) and $_REQUEST['navbar'] == '0' )
    return '';

  global $running;
  global $user;
  global $bandwidth_options;
  global $view;
  global $filterQuery;
  global $sortQuery;
  global $limitQuery;

  if ( !$sortQuery ) {
    parseSort();
  }
  if ( (!$filterQuery) and isset($_REQUEST['filter']) ) {
    parseFilter($_REQUEST['filter']);
    $filterQuery = $_REQUEST['filter']['query'];
  }

  ob_start();
  
  if ( ZM_WEB_NAVBAR_TYPE == "normal" ) {
    echo getNormalNavBarHTML($running, $user, $bandwidth_options, $view, $filterQuery, $sortQuery, $limitQuery);
  } else {
    echo getCollapsedNavBarHTML($running, $user, $bandwidth_options, $view, $filterQuery, $sortQuery, $limitQuery);
  }

  return ob_get_clean();
}

//
// The legacy navigation bar that collapses into a pulldown menu on small screens.
//
function getNormalNavBarHTML($running, $user, $bandwidth_options, $view, $filterQuery, $sortQuery, $limitQuery) {

  $status = runtimeStatus($running);

?>
<div class="fixed-top container-fluid p-0">
  <nav class="navbar navbar-expand-md navbar-dark bg-dark justify-content-center flex-row">

    <div class="navbar-brand justify-content-start align-self-start">
      <?php echo getNavBrandHTML() ?>
    </div>

    <!-- the Navigation Bar Hamburger Button   -->
    <div class="nav justify-content-end flex-grow-1">
      <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="navbar-toggler-icon"></span>
      </button>
   </div>

    <div class="collapse navbar-collapse" id="main-header-nav">
<?php

  // *** Build the navigation bar menu items ***
  if ( $user and $user['Username'] ) {
        echo '<ul class="navbar-nav align-self-start justify-content-center">';
          echo getConsoleHTML();
          echo getOptionsHTML();
          echo getLogHTML();
          echo getDevicesHTML();
          echo getGroupsHTML($view);
          echo getFilterHTML($view, $filterQuery, $sortQuery, $limitQuery);
          echo getCycleHTML($view);
          echo getMontageHTML($view);
          echo getMontageReviewHTML($view);
          echo getRprtEvntAuditHTML($view);
          echo getHeaderFlipHTML();
        echo '</ul>';

        echo '<ul class="nav navbar-nav justify-content-end align-self-start flex-grow-1">';
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

  if ( (!ZM_OPT_USE_AUTH) or $user ) {

// *** Build the statistics shown on the navigation bar ***
?>
      <div id="reload" class="container-fluid">
        <ul id="Bandwidth" class="navbar-nav justify-content-start">
          <?php echo getBandwidthHTML($bandwidth_options, $user) ?>
        </ul>
        
        <ul class="navbar-nav list-inline justify-content-center">
          <?php
          echo getSysLoadHTML();
          echo getDbConHTML();
          echo getStorageHTML();
          echo getShmHTML();
          echo getLogIconHTML();
          ?>
        </ul>

        <ul id="Version" class="nav navbar-nav justify-content-end">
          <?php echo getZMVersionHTML() ?>
        </ul>
      </div>
<?php
  } // end if (!ZM_OPT_USE_AUTH) or $user )
?>
    </div><!-- End Collapsible Panel -->
  </nav><!-- End Second Navbar -->
  
  <nav class="navbar navbar-expand-md bg-dark justify-content-center p-0">
    <?php echo getConsoleBannerHTML() ?>
  </nav><!-- End Third Navbar -->
</div>
<?php
} // end function getNormalNavBarHTML()

//
// A new, slimmer navigation bar, permanently collapsed into a dropdown
//
function getCollapsedNavBarHTML($running, $user, $bandwidth_options, $view, $filterQuery, $sortQuery, $limitQuery) {

  $status = runtimeStatus($running);

  ?>
  <div class="fixed-top container-fluid p-0">
  <nav class="navbar navbar-dark bg-dark px-1 flex-nowrap">

    <div class="navbar-brand align-self-start px-0">
      <?php echo getNavBrandHTML() ?>
    </div>

    <nav class="navbar navbar-expand-md align-self-start px-0">
    <ul class="nav navbar-nav list-group px-0">
    <?php

  // *** Build the statistics shown on the navigation bar ***
  if ( (!ZM_OPT_USE_AUTH) or $user ) {
    ?>
    <div id="reload" class="collapse navbar-collapse px-0">

      <ul id="Version" class="pr-2">
        <?php echo getZMVersionHTML() ?>
      </ul>

      <ul id="Bandwidth" class="px-2">
        <?php echo getBandwidthHTML($bandwidth_options, $user) ?>
      </ul>

        <?php
        echo getSysLoadHTML();
        echo getDbConHTML();
        echo getStorageHTML();
        echo getShmHTML();
        echo getLogIconHTML();
        ?>

    </div>

<?php 
  } // end if (!ZM_OPT_USE_AUTH) or $user )
?> 
    </nav>

        <ul class="list-group list-group-horizontal ml-auto">
        <?php
        echo getAcctCircleHTML($user);
        echo getStatusBtnHTML($status);
        ?>
        </ul>
      </ul>

    <!-- the Navigation Bar Hamburger Button   -->
    <?php if ( (!ZM_OPT_USE_AUTH) or $user ) { ?>
      <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#main-header-nav" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="navbar-toggler-icon"></span>
      </button>
    <?php } ?>

  <div style="background-color:#485460" class="dropdown-menu dropdown-menu-right px-3" id="main-header-nav">
    <?php
    if ( $user and $user['Username'] ) {
        echo '<ul class="navbar-nav">';
          echo getConsoleHTML();
          echo getOptionsHTML();
          echo getLogHTML();
          echo getDevicesHTML();
          echo getGroupsHTML($view);
          echo getFilterHTML($view,$filterQuery,$sortQuery,$limitQuery);
          echo getCycleHTML($view);
          echo getMontageHTML($view);
          echo getMontageReviewHTML($view);
          echo getRprtEvntAuditHTML($view);
        echo '</ul>';
    }
    ?>
  </div>

  </nav><!-- End First Navbar -->

  <nav class="navbar navbar-expand-md bg-dark justify-content-center p-0">
    <?php echo getConsoleBannerHTML() ?>
  </nav><!-- End Second Navbar -->
  </div>
  
  <?php
} // End function getCollapsedNavBarHTML

// Returns the html representing the current unix style system load
function getSysLoadHTML() {
  $result = '';

  $result .= '<li id="getSysLoadHTML" class="Load nav-item mx-2">'.PHP_EOL;
  $result .= '<i class="material-icons md-18">trending_up</i>'.PHP_EOL;
  $result .= '&nbsp;'.translate('Load').': '.getLoad().PHP_EOL;
  $result .= '</li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the current number of connections made to the database
function getDbConHTML() {
  $result = '';
  
  $connections = dbFetchOne('SHOW status WHERE variable_name=\'threads_connected\'', 'Value');
  $max_connections = dbFetchOne('SHOW variables WHERE variable_name=\'max_connections\'', 'Value');
  $percent_used = $max_connections ? 100 * $connections / $max_connections : 100;
  $class = ( $percent_used > 90 ) ? ' text-warning' : '';

  $result .= '<li id="getDbConHTML" class="nav-item dropdown mx-2' .$class. '">'.PHP_EOL;
  $result .= '<i class="material-icons md-18 mr-1">storage</i>'.PHP_EOL;
  $result .= translate('DB'). ': ' .$connections. '/' .$max_connections.PHP_EOL;   
  $result .= '</li>'.PHP_EOL;
  
  return $result;
}

// Returns an html dropdown showing capacity of all storage areas
function getStorageHTML() {
  $result='';

  $func = function($S) {
    $class = '';
    if ( $S->disk_usage_percent() > 98 ) {
      $class = 'text-danger';
    } else if ( $S->disk_usage_percent() > 90 ) {
      $class = 'text-warning';
    }
    $title = human_filesize($S->disk_used_space()) . ' of ' . human_filesize($S->disk_total_space()). 
      ( ( $S->disk_used_space() != $S->event_disk_space() ) ? ' ' .human_filesize($S->event_disk_space()) . ' used by events' : '' );
    return '<a class="dropdown-item '.$class.'" title="'.$title.'" href="?view=options&amp;tab=storage">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</a>';
  };

  $storage_areas = ZM\Storage::find(array('Enabled'=>true));
  $num_storage_areas = count($storage_areas);  
  
  $full_warning = 0;
  $full_error = 0;
  foreach ( $storage_areas as $area ) {  
    if ( $area->disk_usage_percent() > 98 ) { $full_error++; continue; }
    if ( $area->disk_usage_percent() > 90 ) $full_warning++;    
  } 
  
  $class = '';
  if ( $full_error ) {
    $class = 'text-danger'; 
  } else if ( $full_warning ) {
    $class = 'text-warning'; 
  }
  
  $result .= '<li id="getStorageHTML" class="nav-item dropdown mx-2">'.PHP_EOL;
  $result .= '<a class="dropdown-toggle mr-2 '.$class.'" href="#" id="dropdown_storage" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="material-icons md-18 mr-1">folder_shared</i>Storage</a>'.PHP_EOL;
  $result .= '<div class="dropdown-menu" id="dropdown_storage" aria-labelledby="dropdown_storage">'.PHP_EOL;
  
  foreach ( $storage_areas as $area ) {  
    $result .= $func($area).PHP_EOL;
  } 
  $result .= '</div>'.PHP_EOL;
  $result .= '</li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the current capacity of mapped memory filesystem (usually /dev/shm)
function getShmHTML() {
  $result = '';
  
  $shm_percent = getDiskPercent(ZM_PATH_MAP);
  $shm_total_space = disk_total_space(ZM_PATH_MAP);
  $shm_used = $shm_total_space - disk_free_space(ZM_PATH_MAP);

  $class = '';
  if ( $shm_percent > 98 ) {
    $class = 'text-danger';
  } else if ( $shm_percent > 90 ) {
    $class = 'text-warning';
  }
  $result .= ' <li id="getShmHTML" class="nav-item dropdown mx-2' .$class. '" title="' .human_filesize($shm_used). ' of ' .human_filesize($shm_total_space). '">' .ZM_PATH_MAP. ': '.$shm_percent.'%</li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the optional web console banner text
function getConsoleBannerHTML() {
  $result = '';

  if ( defined('ZM_WEB_CONSOLE_BANNER') and ZM_WEB_CONSOLE_BANNER != '' ) {
    $result .= '<h2 id="getConsoleBannerHTML">'.validHtmlStr(ZM_WEB_CONSOLE_BANNER).'</h2>';
  }
  return $result;
}

// Returns the html representing the current high,medium,low bandwidth setting
function getBandwidthHTML($bandwidth_options, $user) {
  $result = '';

  # Limit available options to what are available in user
  if ( $user && !empty($user['MaxBandwidth']) ) {
    if ( $user['MaxBandwidth'] == 'low' ) {
      unset($bandwidth_options['high']);
      unset($bandwidth_options['medium']);
    } else if ( $user['MaxBandwidth'] == 'medium' ) {
      unset($bandwidth_options['high']);
    }
  }

  $result .= '<li id="getBandwidthHTML" class="nav-item dropdown mx-2">'.PHP_EOL;
  $result .= '<a class="dropdown-toggle mr-2" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="material-icons md-18 mr-1">network_check</i>'.translate($bandwidth_options[$_COOKIE['zmBandwidth']]).'</a>'.PHP_EOL;

  $result .= '<div class="dropdown-menu" id="dropdown_bandwidth" aria-labelledby="dropdown_bandwidth">'.PHP_EOL;  
    $result .= '<a data-pdsa-dropdown-val="high" class="dropdown-item" href="#">' .translate('High'). '</a>'.PHP_EOL;
    $result .= '<a data-pdsa-dropdown-val="medium" class="dropdown-item" href="#">' .translate('Medium'). '</a>'.PHP_EOL;
    $result .= '<a data-pdsa-dropdown-val="low" class="dropdown-item" href="#">' .translate('Low'). '</a>'.PHP_EOL;    
  $result .= '</div>'.PHP_EOL;

  $result .= '</li>'.PHP_EOL;
    
  return $result;
}

// Returns the html representing the version of ZoneMinder
function getZMVersionHTML() {
  $result = '';
  $content = '';
  
  if ( ZM_DYN_DB_VERSION && (ZM_DYN_DB_VERSION != ZM_VERSION) ) {  // Must upgrade before proceeding
    $class = 'text-danger';
    $tt_text = translate('RunLocalUpdate');
    $content = 'v'.ZM_VERSION.PHP_EOL;
  } else if ( false ) { // No update needed
    $class = ''; // Don't change the text color under normal conditions
    $tt_text = translate('UpdateNotNecessary');
    $content = 'v'.ZM_VERSION.PHP_EOL;
  } else if ( canEdit('System') ) { // An update is available and the user is an administrator
    $class = 'text-warning';
    $tt_text = translate('UpdateAvailable');
    $content = '<a class="dropdown ' .$class. '" data-toggle="dropdown" href="#">v' .ZM_VERSION. '</a>'.PHP_EOL;
    $content .= '<div class="dropdown-menu" id="dropdown_reminder" aria-labelledby="dropdown_reminder">'.PHP_EOL;  
      $content .= '<h6 class="dropdown-header">' .translate('UpdateAvailable'). '</h6>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="ignore" href="#">' .translate('VersionIgnore'). '</a>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="hour" href="#">' .translate('VersionRemindHour'). '</a>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="day" href="#">' .translate('VersionRemindDay'). '</a>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="week" href="#">' .translate('VersionRemindWeek'). '</a>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="month" href="#">' .translate('VersionRemindMonth'). '</a>'.PHP_EOL;
      $content .= '<a class="dropdown-item" data-pdsa-dropdown-val="never" href="#">' .translate('VersionRemindNever'). '</a>'.PHP_EOL;  
    $content .= '</div>'.PHP_EOL;
  } else { // An update is available and the user is NOT an administrator
    $class = 'text-warning';
    $tt_text = translate('UpdateAvailable');
    $content = 'v'.ZM_VERSION.PHP_EOL;
  }

  $result .= '<li id="getZMVersionHTML" class="nav-item dropdown ' .$class. '" data-placement="bottom" data-placement="bottom" title="' .$tt_text. '">'.PHP_EOL; 
  $result .= $content;
  $result .= '</li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the ZoneMinder logo and about menu
function getNavBrandHTML() {
  $result = '';

  if ( ZM_HOME_ABOUT ) {
  $result .= '<a id="getNavBrandHTML" class="dropdown" data-toggle="dropdown" href="#">ZoneMinder</a>'.PHP_EOL;
    $result .= '<ul style="background-color:#485460" class="dropdown-menu">'.PHP_EOL;
      $result .= '<li><a class="dropdown-item" href="https://zoneminder.com/" target="_blank">ZoneMinder</a></li>'.PHP_EOL;
      $result .= '<li><a class="dropdown-item" href="https://zoneminder.readthedocs.io/en/stable/" target="_blank">Documentation</a></li>'.PHP_EOL;
      $result .= '<li><a class="dropdown-item" href="https://forums.zoneminder.com/" target="_blank">Support</a></li>'.PHP_EOL;
    $result .= '</ul>'.PHP_EOL;
  } else {
  $result .= '<a id="getNavBrandHTML" href="' .validHtmlStr(ZM_HOME_URL). '" target="' .validHtmlStr(ZM_WEB_TITLE). '">' .ZM_HOME_CONTENT. '</a>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Console menu item
function getConsoleHTML() {
  $result = '';
  
  if ( canView('Monitors') ) {
    $result .= '<li id="getConsoleHTML" class="nav-item dropdown"><a class="nav-link" href="?view=console">'.translate('Console').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Options menu item
function getOptionsHTML() {
  $result = '';
  
  if ( canView('System') ) {
    $result .= '<li id="getOptionsHTML" class="nav-item dropdown"><a class="nav-link" href="?view=options">'.translate('Options').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Log menu item
function getLogHTML() {
  $result = '';
  
  if ( canView('System') ) {
    if ( ZM\logToDatabase() > ZM\Logger::NOLOG ) { 
      $logstate = logState();
      $class = ($logstate == 'ok') ? 'text-success' : ($logstate == 'alert' ? 'text-warning' : (($logstate == 'alarm' ? 'text-danger' : '')));
      $result .= '<li id="getLogHTML" class="nav-item dropdown mx-2">'.makePopupLink('?view=log', 'zmLog', 'log', '<span class="nav-link '.$class.'">'.translate('Log').'</span>').'</li>'.PHP_EOL;
    }
  }
  
  return $result;
}

// Returns the html representing the log icon
function getLogIconHTML() {
  $result = '';
  
  if ( canView('System') ) {
    if ( ZM\logToDatabase() > ZM\Logger::NOLOG ) { 
      $logstate = logState();
      $class = ( $logstate == 'alert' ) ? 'text-warning' : (( $logstate == 'alarm' ) ? 'text-danger' : '');
      $result .= '<li id="getLogIconHTML" class="nav-item dropdown">'.
        makePopupLink('?view=log', 'zmLog', 'log', '<span class="mx-1 ' .$class. '"><i class="material-icons md-18">report</i>'.translate('Log').'</span>').
        '</li>'.PHP_EOL;
    }
  }
  
  return $result;
}

// Returns the html representing the X10 Devices menu item
function getDevicesHTML() {
  $result = '';
  
  if ( ZM_OPT_X10 && canView('Devices') ) {
    $result .= '<li id="getDevicesHTML" class="nav-item dropdown"><a class="nav-link" href="?view=devices">Devices</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Groups menu item
function getGroupsHTML($view) {
  $result = '';
  
  $class = $view == 'groups' ? ' selected' : '';
  $result .= '<li id="getGroupsHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=groups">'. translate('Groups') .'</a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the Filter menu item
function getFilterHTML($view, $filterQuery, $sortQuery, $limitQuery) {
  $result = '';
  
  $class = $view == 'filter' ? ' selected' : '';
  $result .= '<li id="getFilterHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=filter'.$filterQuery.$sortQuery.$limitQuery.'">'.translate('Filters').'</a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the Cycle menu item
function getCycleHTML($view) {
  $result = '';
  
  if ( canView('Stream') ) {
    $class = $view == 'cycle' ? ' selected' : '';
    $result .= '<li id="getCycleHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=cycle">' .translate('Cycle'). '</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Montage menu item
function getMontageHTML($view) {
  $result = '';
  
  if ( canView('Stream') ) {
    $class = $view == 'cycle' ? ' selected' : '';
    $result .= '<li id="getMontageHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=montage">' .translate('Montage'). '</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the MontageReview menu item
function getMontageReviewHTML($view) {
  $result = '';
  
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
    $class = $view == 'montagereview' ? ' selected' : '';
    $result .= '<li id="getMontageReviewHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=montagereview' .$live. '">'.translate('MontageReview').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Audit Events Report menu item
function getRprtEvntAuditHTML($view) {
  $result = '';
  
  if ( canView('Events') ) {
    $class = $view == 'report_event_audit' ? ' selected' : '';
    $result .= '<li id="getRprtEvntAuditHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=report_event_audit">'.translate('ReportEventAudit').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the header collapse toggle menu item
function getHeaderFlipHTML() {
  $result = '';
  
  $header = ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down') ? 'down' : 'up';
  $result .= '<li id="getHeaderFlipHTML" class="nav-item dropdown"><a class="nav-link" href="#"><i id="flip" class="material-icons md-18">keyboard_arrow_' .$header. '</i></a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the logged in user name and avatar
function getAcctCircleHTML($user=null) {
  $result = '';
  
  if ( ZM_OPT_USE_AUTH and $user ) {
    $result .= '<p id="getAcctCircleHTML" class="navbar-text mr-2">'.PHP_EOL;
    $result .= makePopupLink('?view=logout', 'zmLogout', 'logout',
      '<i class="material-icons">account_circle</i> '.  $user['Username'],
      (ZM_AUTH_TYPE == 'builtin') ).PHP_EOL;
    $result .= '</p>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the runtime status button
function getStatusBtnHTML($status) {
  $result = '';
  
  if ( canEdit('System') ) {
    //$result .= '<li class="nav-item dropdown">'.PHP_EOL;
    $result .= '<form id="getStatusBtnHTML" class="form-inline">'.PHP_EOL;
    $result .= '<button type="button" class="btn btn-default navbar-btn" data-toggle="modal" data-target="#modalState">' .$status. '</button>'.PHP_EOL;
    $result .= '</form>'.PHP_EOL;
    //$result .= '</li>'.PHP_EOL;

    if ( ZM_SYSTEM_SHUTDOWN ) {
      $result .= '<p class="navbar-text">'.PHP_EOL;
      $result .= makePopupLink('?view=shutdown', 'zmShutdown', 'shutdown', '<i class="material-icons md-18">power_settings_new</i>' ).PHP_EOL;
      $result .= '</p>'.PHP_EOL;
     } 

  } else if ( canView('System') ) {
    $result .= '<p id="getStatusBtnHTML" class="navbar-text">'.PHP_EOL;
    $result .= $status.PHP_EOL;
    $result .= '</p>'.PHP_EOL;
  }
  
  return $result;
}

function runtimeStatus($running=null) {
  if ( $running == null )
    $running = daemonCheck();
  if ( $running ) {
    $state = dbFetchOne('SELECT Name FROM States WHERE isActive=1', 'Name');
    if ( $state == 'default' )
      $state = '';
  }

  return $running ? ($state ? $state : translate('Running')) : translate('Stopped');
}

function xhtmlFooter() {
  global $cspNonce;
  global $view;
  global $skin;
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
