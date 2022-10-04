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
  global $basename;

  # This idea is that we always include the classic css files, 
  # and then any different skin only needs to contain things that are different.
  $baseCssPhpFile = getSkinFile('css/base/skin.css.php');

  $skinCssPhpFile = getSkinFile('css/'.$css.'/skin.css.php');


  $basename = basename($file, '.php');

  $baseViewCssPhpFile = getSkinFile('/css/base/views/'.$basename.'.css.php');
  $viewCssPhpFile = getSkinFile('/css/'.$css.'/views/'.$basename.'.css.php');

  function output_link_if_exists($files, $cache_bust=true) {
    global $skin;
    $html = array();
    foreach ( $files as $file ) {
      if ( getSkinFile($file) ) {
        if ( $cache_bust ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust('skins/'.$skin.'/'.$file).'" type="text/css"/>';
        } else  {
        $html[] = '<link rel="stylesheet" href="skins/'.$skin.'/'.$file.'" type="text/css"/>';
        }
      }
    }
    $html[] = ''; // So we ge a trailing \n
    return implode(PHP_EOL, $html);
  }
  function output_script_if_exists($files, $cache_bust=true) {
    global $skin;
    $html = array();
    foreach ( $files as $file ) {
      if ( file_exists('skins/'.$skin.'/'.$file) ) {
        if ( $cache_bust ) {
          $html[] = '<script src="'.cache_bust('skins/'.$skin.'/'.$file).'"></script>';
        } else {
          $html[] = '<script src="skins/'.$skin.'/'.$file.'"></script>';
        }
      } else if ( file_exists($file) ) {
        if ( $cache_bust ) {
          $html[] = '<script src="'.cache_bust($file).'"></script>';
        } else {
          $html[] = '<script src="'.$file.'"></script>';
        }
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
    if ( ! count($html) ) {
      ZM\Warning("No files found for $files");
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
  'css/font-awesome.min.css',
  'css/bootstrap.min.css',
  'css/bootstrap-table.min.css',
  'css/bootstrap-table-page-jump-to.min.css',
));

echo output_link_if_exists(array(
  'css/base/skin.css',
  'css/base/views/'.$basename.'.css',
  'js/dateTimePicker/jquery-ui-timepicker-addon.css',
  'js/jquery-ui-1.13.2/jquery-ui.structure.min.css',
));
if ( $css != 'base' )
  echo output_link_if_exists(array(
    'css/'.$css.'/skin.css',
    'css/'.$css.'/views/'.$basename.'.css',
    'css/'.$css.'/jquery-ui-theme.css',
  ));
?>
  <link rel="stylesheet" href="skins/classic/js/jquery-ui-1.13.2/jquery-ui.theme.min.css" type="text/css"/>
  <?php #Chosen can't be cache-busted because it loads sprites by relative path ?>
  <link rel="stylesheet" href="skins/classic/js/chosen/chosen.min.css" type="text/css"/>
<?php
  if ( $basename == 'watch' ) {
    echo output_link_if_exists(array('/css/base/views/control.css'));
    if ( $css != 'base' )
      echo output_link_if_exists(array('/css/'.$css.'/views/control.css'));
  } else if ( $basename == 'monitor' ) {
      echo output_link_if_exists(array('js/leaflet/leaflet.css'), false);
  }
?>
  <style>
<?php
  if ( $baseViewCssPhpFile ) {
    require_once($baseViewCssPhpFile);
  }
  if ( $viewCssPhpFile ) {
    require_once($viewCssPhpFile);
  }
?>
  </style>

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
   echo '<div id="error">'.$error_message.'</div>';
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
  global $skin;

  ob_start();
  
  if ( ZM_WEB_NAVBAR_TYPE == "normal" ) {
    echo getNormalNavBarHTML($running, $user, $bandwidth_options, $view, $skin);
  } else {
    echo getCollapsedNavBarHTML($running, $user, $bandwidth_options, $view, $skin);
  }

  return ob_get_clean();
}

//
// The legacy navigation bar that collapses into a pulldown menu on small screens.
//
function getNormalNavBarHTML($running, $user, $bandwidth_options, $view, $skin) {

  $status = runtimeStatus($running);

?>
<div class="container-fluid p-0">
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
          echo getFilterHTML($view);
          echo getCycleHTML($view);
          echo getMontageHTML($view);
          echo getMontageReviewHTML($view);
          echo getSnapshotsHTML($view);
          echo getRprtEvntAuditHTML($view);
          echo getHeaderFlipHTML();
        echo '</ul>';

        echo '<ul class="nav navbar-nav justify-content-end align-self-start flex-grow-1">';
          echo getAccountCircleHTML($skin, $user);
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
          #echo getLogIconHTML();
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
function getCollapsedNavBarHTML($running, $user, $bandwidth_options, $view, $skin) {

  $status = runtimeStatus($running);

  ?>
  <div class="fixed-top container-fluid p-0">
    <nav class="navbar navbar-dark bg-dark px-1 flex-nowrap">

      <div class="navbar-brand align-self-start px-0">
        <?php echo getNavBrandHTML() ?>
      </div>

      <nav class="navbar navbar-expand-md align-self-start px-0">
<?php

  // *** Build the statistics shown on the navigation bar ***
  if ( (!ZM_OPT_USE_AUTH) or $user ) {
?>
        <div id="reload" class="collapse navbar-collapse px-0">

          <ul id="Version" class="pr-2 navbar-nav">
            <?php echo getZMVersionHTML() ?>
          </ul>

          <ul id="Bandwidth" class="px-2 navbar-nav">
            <?php echo getBandwidthHTML($bandwidth_options, $user) ?>
          </ul>

          <ul class="nav navbar-nav list-group px-0">
            <?php
            echo getSysLoadHTML();
            echo getDbConHTML();
            echo getStorageHTML();
            echo getShmHTML();
            echo getLogIconHTML();
            ?>
          </ul>

        </div>
<?php 
  } // end if (!ZM_OPT_USE_AUTH) or $user )
?> 
      </nav>

      <ul class="list-group list-group-horizontal ml-auto">
        <?php
        echo getAccountCircleHTML($skin, $user);
        echo getStatusBtnHTML($status);
        ?>
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
            echo getFilterHTML($view);
            echo getCycleHTML($view);
            echo getMontageHTML($view);
            echo getMontageReviewHTML($view);
            echo getSnapshotsHTML($view);
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
  if ( !canView('System') ) return $result;

  $result .= '<li id="getSysLoadHTML" class="Load nav-item mx-2">'.PHP_EOL;
  $result .= '<i class="material-icons md-18">trending_up</i>'.PHP_EOL;
  $result .= '&nbsp;'.translate('Load').': '.number_format(getLoad(), 2, '.', '').PHP_EOL;
  $result .= '</li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the current number of connections made to the database
function getDbConHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
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
  $result = '';
  if ( !canView('System') ) return $result;

  $func = function($S, $class='') {
    if ( $S->disk_usage_percent() > 98 ) {
      $class = 'text-danger';
    } else if ( $S->disk_usage_percent() > 90 ) {
      $class = 'text-warning';
    }
    $title = human_filesize($S->disk_used_space()) . ' of ' . human_filesize($S->disk_total_space()). 
      ( ( $S->disk_used_space() != $S->event_disk_space() ) ? ' ' .human_filesize($S->event_disk_space()) . ' used by events' : '' );
    return '<a class="'.$class.'" title="'.$title.'" href="?view=options&amp;tab=storage">'.validHtmlStr($S->Name()) . ': ' . $S->disk_usage_percent().'%' . '</a>';
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
  
  if (count($storage_areas) <= 2) {
    $result .= '<li id="getStorageHTML" class="nav-item mx-2">'.PHP_EOL;
    foreach ( $storage_areas as $area ) {  
      $result .= $func($area).PHP_EOL;
    } 
    $result .= '</li>'.PHP_EOL;
  } else {
    $result .= '<li id="getStorageHTML" class="nav-item dropdown mx-2">'.PHP_EOL;
    $result .= '<a class="dropdown-toggle mr-2 '.$class.'" href="#" id="dropdown_storage" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="material-icons md-18 mr-1">folder_shared</i>Storage</a>'.PHP_EOL;
    $result .= '<div class="dropdown-menu" aria-labelledby="dropdown_storage">'.PHP_EOL;
    
    foreach ( $storage_areas as $area ) {  
      $result .= $func($area, 'dropdown-item ').PHP_EOL;
    } 
    $result .= '</div>'.PHP_EOL;
    $result .= '</li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the current capacity of mapped memory filesystem (usually /dev/shm)
function getShmHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
  
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

  # Limit available options to what are available in user
  if ( $user && !empty($user['MaxBandwidth']) ) {
    if ( $user['MaxBandwidth'] == 'low' ) {
      unset($bandwidth_options['high']);
      unset($bandwidth_options['medium']);
    } else if ( $user['MaxBandwidth'] == 'medium' ) {
      unset($bandwidth_options['high']);
    }
  }

  $result = '<li id="getBandwidthHTML" class="nav-item dropdown mx-2">'.PHP_EOL;
  $result .= '<a class="dropdown-toggle mr-2" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dropdown_bandwidth"><i class="material-icons md-18 mr-1">network_check</i>'.translate($bandwidth_options[$_COOKIE['zmBandwidth']]).'</a>'.PHP_EOL;

  $result .= '<div class="dropdown-menu" aria-labelledby="dropdown_bandwidth">'.PHP_EOL;  
  if ( count($bandwidth_options) > 1 ) {
    if ( isset($bandwidth_options['high']) )
      $result .= '<a data-pdsa-dropdown-val="high" class="dropdown-item bwselect" href="#">' .translate('High'). '</a>'.PHP_EOL;
    if ( isset($bandwidth_options['medium']) )
      $result .= '<a data-pdsa-dropdown-val="medium" class="dropdown-item bwselect" href="#">' .translate('Medium'). '</a>'.PHP_EOL;
    # low is theoretically always available
    $result .= '<a data-pdsa-dropdown-val="low" class="dropdown-item bwselect" href="#">' .translate('Low'). '</a>'.PHP_EOL;    
  }
  $result .= '</div>'.PHP_EOL;

  $result .= '</li>'.PHP_EOL;
    
  return $result;
}

// Returns the html representing the version of ZoneMinder
function getZMVersionHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
  $content = '';
  
  if ( ZM_DYN_DB_VERSION && (ZM_DYN_DB_VERSION != ZM_VERSION) ) {  // Must upgrade before proceeding
    $class = 'text-danger';
    $tt_text = translate('RunLocalUpdate');
    $content = 'v'.ZM_VERSION.PHP_EOL;
  } else if ( verNum( ZM_DYN_LAST_VERSION ) <= verNum( ZM_VERSION ) || !ZM_CHECK_FOR_UPDATES || ZM_DYN_NEXT_REMINDER > time() ) { // No update needed
    $class = ''; // Don't change the text color under normal conditions
    $tt_text = translate('UpdateNotNecessary');
    $content = 'v'.ZM_VERSION.PHP_EOL;
  } else if ( canEdit('System') ) { // An update is available and the user is an administrator
    $class = 'text-warning';
    $tt_text = translate('UpdateAvailable');
    $content = '<a class="dropdown ' .$class. '" data-toggle="dropdown" href="#" id="dropdown_reminder">v' .ZM_VERSION. '</a>'.PHP_EOL;
    $content .= '<div class="dropdown-menu" aria-labelledby="dropdown_reminder">'.PHP_EOL;  
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

  $result .= '<li id="getZMVersionHTML" class="nav-item dropdown ' .$class. '" data-placement="bottom" title="' .$tt_text. '">'.PHP_EOL; 
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
      $result .= '<li id="getLogHTML" class="nav-item dropdown mx-2">'.makeLink('?view=log', '<span class="nav-link '.$class.'">'.translate('Log').'</span>').'</li>'.PHP_EOL;
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
        makeLink('?view=log', '<span class="mx-1 ' .$class. '"><i class="material-icons md-18">report</i>'.translate('Log').'</span>').
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
  if ( !canView('Groups') ) return $result;

  $class = $view == 'groups' ? ' selected' : '';
  $result .= '<li id="getGroupsHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=groups">'. translate('Groups') .'</a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the Filter menu item
function getFilterHTML($view) {
  $result = '';
  if ( !canView('Events') ) return $result;
  
  $class = $view == 'filter' ? ' selected' : '';
  $result .= '<li id="getFilterHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=filter">'.translate('Filters').'</a></li>'.PHP_EOL;
  
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
    $class = $view == 'montage' ? ' selected' : '';
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

// Returns the html representing the Montage menu item
function getSnapshotsHTML($view) {
  $result = '';
  
  if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS and canView('Snapshots')) {
    $class = $view == 'snapshots' ? ' selected' : '';
    $result .= '<li id="getSnapshotsHTML" class="nav-item dropdown"><a class="nav-link'.$class.'" href="?view=snapshots">' .translate('Snapshots'). '</a></li>'.PHP_EOL;
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
function getAccountCircleHTML($skin, $user=null) {
  $result = '';
  
  if ( ZM_OPT_USE_AUTH and $user ) {
    $result .= '<li id="getAccountCircleHTML" class="navbar-text navbar-nav mr-2">'.PHP_EOL;
    $result .= makeLink('#', '<i class="material-icons">account_circle</i> '.  validHtmlStr($user['Username']),
      (ZM_AUTH_TYPE == 'builtin'), 'id="logoutButton" data-toggle="modal" data-target="#modalLogout" data-backdrop="false"' ).PHP_EOL;
    $result .= '</li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the runtime status button
function getStatusBtnHTML($status) {
  $result = '';
  
  if (canEdit('System')) {
    $result .= '<li id="getStatusBtnHTML">'.PHP_EOL;
    $result .= '<button type="button" class="btn btn-default navbar-btn" id="stateModalBtn">' .$status. '</button>'.PHP_EOL;
    $result .= '</li>'.PHP_EOL;

    if (ZM_SYSTEM_SHUTDOWN) {
      $result .= '<li class="pr-2">'.PHP_EOL;
      $result .= '<button id="shutdownButton" class="btn btn-default navbar-btn" data-on-click="getShutdownModal" data-toggle="tooltip" data-placement="top" title="' .translate('Shutdown'). '"><i class="material-icons md-18">power_settings_new</i></button>'.PHP_EOL;
      $result .= '</li>'.PHP_EOL;
     } 

  } else if (canView('System')) {
    $result .= '<li id="getStatusBtnHTML" class="navbar-text">'.PHP_EOL;
    $result .= $status.PHP_EOL;
    $result .= '</li>'.PHP_EOL;
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

function getStatsTableHTML($eid, $fid, $row='') {
  if ( !canView('Events') ) return;
  $result = '';
  
  $sql = 'SELECT S.*,E.*,Z.Name AS ZoneName,Z.Units,Z.Area,M.Name AS MonitorName FROM Stats AS S LEFT JOIN Events AS E ON S.EventId = E.Id LEFT JOIN Zones AS Z ON S.ZoneId = Z.Id LEFT JOIN Monitors AS M ON E.MonitorId = M.Id WHERE S.EventId = ? AND S.FrameId = ? ORDER BY S.ZoneId';
  $stats = dbFetchAll( $sql, NULL, array( $eid, $fid ) );
  
  $result .= '<table id="contentStatsTable' .$row. '"'.PHP_EOL;
    $result .= 'data-locale="' .i18n(). '"'.PHP_EOL;
    $result .= 'data-toggle="table"'.PHP_EOL;
    $result .= 'data-toolbar="#toolbar"'.PHP_EOL;
    $result .= 'class="table-sm table-borderless contentStatsTable"'.PHP_EOL;
    $result .= 'cellspacing="0">'.PHP_EOL;
    
    $result .= '<caption>' .translate('Stats'). ' - ' .$eid. ' - ' .$fid. '</caption>'.PHP_EOL;
    $result .= '<thead>'.PHP_EOL;
      $result .= '<tr>'.PHP_EOL;
        $result .= '<th class="colZone font-weight-bold" data-align="center">' .translate('Zone'). '</th>'.PHP_EOL;
        $result .= '<th class="colPixelDiff font-weight-bold" data-align="center">' .translate('PixelDiff'). '</th>'.PHP_EOL;
        $result .= '<th class="colAlarmPx font-weight-bold" data-align="center">' .translate('AlarmPx'). '</th>'.PHP_EOL;
        $result .= '<th class="colFilterPx font-weight-bold" data-align="center">' .translate('FilterPx'). '</th>'.PHP_EOL;
        $result .= '<th class="colBlobPx font-weight-bold" data-align="center">' .translate('BlobPx'). '</th>'.PHP_EOL;
        $result .= '<th class="colBlobs font-weight-bold" data-align="center">' .translate('Blobs'). '</th>'.PHP_EOL;
        $result .= '<th class="colBlobSizes font-weight-bold" data-align="center">' .translate('BlobSizes'). '</th>'.PHP_EOL;
        $result .= '<th class="colAlarmLimits font-weight-bold" data-align="center">' .translate('AlarmLimits'). '</th>'.PHP_EOL;
        $result .= '<th class="colScore font-weight-bold" data-align="center">' .translate('Score'). '</th>'.PHP_EOL;
      $result .= '</tr>'.PHP_EOL;
    $result .= '</thead>'.PHP_EOL;

    $result .= '<tbody>'.PHP_EOL;
    
    if ( count($stats) ) {
      foreach ( $stats as $stat ) {
        $result .= '<tr>'.PHP_EOL;
          $result .= '<td class="colZone">' .validHtmlStr($stat['ZoneName']). '</td>'.PHP_EOL;
          $result .= '<td class="colPixelDiff">' .validHtmlStr($stat['PixelDiff']). '</td>'.PHP_EOL;
          $result .= '<td class="colAlarmPx">' .sprintf( "%d (%d%%)", $stat['AlarmPixels'], (100*$stat['AlarmPixels']/$stat['Area']) ). '</td>'.PHP_EOL;
          $result .= '<td class="colFilterPx">' .sprintf( "%d (%d%%)", $stat['FilterPixels'], (100*$stat['FilterPixels']/$stat['Area']) ).'</td>'.PHP_EOL;
          $result .= '<td class="colBlobPx">' .sprintf( "%d (%d%%)", $stat['BlobPixels'], (100*$stat['BlobPixels']/$stat['Area']) ). '</td>'.PHP_EOL;
          $result .= '<td class="colBlobs">' .validHtmlStr($stat['Blobs']). '</td>'.PHP_EOL;
          
          if ( $stat['Blobs'] > 1 ) {
            $result .= '<td class="colBlobSizes">' .sprintf( "%d-%d (%d%%-%d%%)", $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$stat['Area']), (100*$stat['MaxBlobSize']/$stat['Area']) ). '</td>'.PHP_EOL;
          } else {
            $result .= '<td class="colBlobSizes">' .sprintf( "%d (%d%%)", $stat['MinBlobSize'], 100*$stat['MinBlobSize']/$stat['Area'] ). '</td>'.PHP_EOL;
          }
          
          $result .= '<td class="colAlarmLimits">' .validHtmlStr($stat['MinX'].",".$stat['MinY']."-".$stat['MaxX'].",".$stat['MaxY']). '</td>'.PHP_EOL;
          $result .= '<td class="colScore">' .$stat['Score']. '</td>'.PHP_EOL;
      }
    } else {
      $result .= '<tr>'.PHP_EOL;
        $result .= '<td class="rowNoStats" colspan="9">' .translate('NoStatisticsRecorded'). '</td>'.PHP_EOL;
      $result .= '</tr>'.PHP_EOL;
    }

    $result .= '</tbody>'.PHP_EOL;
  $result .= '</table>'.PHP_EOL;
  
  return $result;
}

// Use this function to manually insert the csrf key into the form when using a modal generated via ajax call
function getCSRFinputHTML() {
  if ( isset($GLOBALS['csrf']['key']) ) {
    $result = '<input type="hidden" name="__csrf_magic" value="key:' .csrf_hash($GLOBALS['csrf']['key']). '" />'.PHP_EOL;
  } else {
    $result = '';
  }
  
  return $result;
}

function xhtmlFooter() {
  global $css;
  global $cspNonce;
  global $view;
  global $skin;
  global $basename;
  $skinJsPhpFile = getSkinFile('js/skin.js.php');
  $viewJsFile = getSkinFile('views/js/'.$basename.'.js');
  $viewJsPhpFile = getSkinFile('views/js/'.$basename.'.js.php');
?>
  <script src="<?php echo cache_bust('skins/'.$skin.'/js/jquery.min.js'); ?>"></script>
  <script src="skins/<?php echo $skin; ?>/js/jquery-ui-1.13.2/jquery-ui.min.js"></script>
  <script src="<?php echo cache_bust('js/ajaxQueue.js') ?>"></script>
  <script src="<?php echo 'skins/'.$skin.'/js/bootstrap.min.js' ?>"></script>
<?php echo output_script_if_exists(array(
  'js/tableExport.min.js',
  'js/bootstrap-table.min.js',
  'js/bootstrap-table-locale-all.min.js',
  'js/bootstrap-table-export.min.js',
  'js/bootstrap-table-page-jump-to.min.js',
  'js/bootstrap-table-cookie.min.js',
  'js/bootstrap-table-toolbar.min.js',
  'js/bootstrap-table-auto-refresh.min.js',
  'js/chosen/chosen.jquery.min.js',
  'js/dateTimePicker/jquery-ui-timepicker-addon.js',
  'js/Server.js',
), true );
?>
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
?>
  <script nonce="<?php echo $cspNonce; ?>">var $j = jQuery.noConflict();
<?php
  if ( $skinJsPhpFile ) {
    require_once( $skinJsPhpFile );
  }
  if ( $viewJsPhpFile ) {
    require_once( $viewJsPhpFile );
  }
?>
  </script>
<?php
  if ( $viewJsFile ) {
?>
  <script src="<?php echo cache_bust($viewJsFile) ?>"></script>
<?php
  }
  $skinJsFile = getSkinFile('js/skin.js');
?>
  <script src="<?php echo cache_bust($skinJsFile) ?>"></script>
  <script src="<?php echo cache_bust('js/logger.js')?>"></script>
<?php
  if ( $basename == 'monitor' ) {
    echo output_script_if_exists(array('js/leaflet/leaflet.js'), false);
  } ?>
  <script nonce="<?php echo $cspNonce; ?>">$j('.chosen').chosen();</script>
  </body>
</html>
<?php
} // end xhtmlFooter
?>
