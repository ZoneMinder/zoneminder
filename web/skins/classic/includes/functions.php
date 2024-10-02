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
  xhtmlHeadersStart($file, $title);
  xhtmlHeadersEnd();
}

function xhtmlHeadersStart($file, $title) {
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo validHtmlStr(ZM_WEB_TITLE_PREFIX) . ' - ' . validHtmlStr($title) ?></title>
<?php
if (defined('ZM_WEB_FAVICON')) {
  echo '
  <link rel="icon" type="image/ico" href="'.ZM_WEB_FAVICON.'"/>
  <link rel="shortcut icon" href="'.ZM_WEB_FAVICON.'"/>
';
} else if ( file_exists("skins/$skin/css/$css/graphics/favicon.ico") ) {
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
));

if ( $basename == 'montage' ) {
  echo output_link_if_exists(array('/assets/gridstack/dist/gridstack.css', '/assets/gridstack/dist/gridstack-extra.css'));
  echo output_link_if_exists(array('/assets/vis-timeline/styles/vis-timeline-graph2d.min.css'));
}
?>
  <link rel="stylesheet" href="skins/classic/js/jquery-ui-1.13.2/jquery-ui.theme.min.css" type="text/css"/>
  <?php #Chosen can't be cache-busted because it loads sprites by relative path ?>
  <link rel="stylesheet" href="skins/classic/js/chosen/chosen.min.css" type="text/css"/>
<?php
echo output_link_if_exists(array(
  'js/dateTimePicker/jquery-ui-timepicker-addon.css',
  'js/jquery-ui-1.13.2/jquery-ui.structure.min.css',
  'js/bootstrap-table-1.23.5/bootstrap-table.min.css',
  'js/bootstrap-table-1.23.5/extensions/page-jump-to/bootstrap-table-page-jump-to.min.css',
  'css/base/skin.css',
  'css/base/views/'.$basename.'.css',
), true);

if ( $css != 'base' )
  echo output_link_if_exists(array(
    'css/'.$css.'/skin.css',
    'css/'.$css.'/views/'.$basename.'.css',
    'css/'.$css.'/jquery-ui-theme.css',
  ));

  if ( $basename == 'watch' ) {
    echo output_link_if_exists(array('/css/base/views/control.css'));
    if ( $css != 'base' )
      echo output_link_if_exists(array('/css/'.$css.'/views/control.css'));
  }
?>
  <style>
<?php
  $baseCssPhpFile = getSkinFile('css/base/skin.css.php');
  if ($baseCssPhpFile) require_once($baseCssPhpFile);
  $skinCssPhpFile = getSkinFile('css/'.$css.'/skin.css.php');
  if ($skinCssPhpFile) require_once($baseCssPhpFile);

  $baseViewCssPhpFile = getSkinFile('/css/base/views/'.$basename.'.css.php');
  if ($baseViewCssPhpFile) require_once($baseViewCssPhpFile);
  $viewCssPhpFile = getSkinFile('/css/'.$css.'/views/'.$basename.'.css.php');
  if ($viewCssPhpFile) require_once($viewCssPhpFile);
?>
  </style>
<?php
  echo ob_get_clean();
} // end function xhtmlHeadersStart( $file, $title )

function xhtmlHeadersEnd() {
  echo '</head>';
} // end function xhtmlHeaders( $file, $title )

// Outputs an opening body tag, and any additional content that should go at the very top, like warnings and error messages.
function getBodyTopHTML() {
  echo '
<body'.((defined('ZM_WEB_NAVBAR_STICKY') and ZM_WEB_NAVBAR_STICKY) ? ' class="sticky"' : '').'>
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

//
// The legacy navigation bar that collapses into a pulldown menu on small screens.
//
function getNormalNavBarHTML($running, $user, $bandwidth_options, $view, $skin) {
  $status = runtimeStatus($running);
?>
<div class="container-fluid" id="navbar-container">
  <div class="navbar-brand">
    <?php echo getNavBrandHTML() ?>
  </div>
  <div class="navbars">
    <nav class="navbar navbar-expand-md flex-row" id="navbar-one">
    <!-- the Navigation Bar Hamburger Button   -->
<!--
    <div class="nav justify-content-end flex-grow-1">
-->
<?php 
  if ((!ZM_OPT_USE_AUTH) or $user) {
?>
      <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="navbar-toggler-icon">
          <i class="material-icons md-20">menu</i>
        </span>
      </button>
      <button id="flipNarrow" type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar-two" aria-expanded="true">
        <span class="sr-only">Toggle guages</span>
        <span class="navbar-toggler-icon">
          <i class="material-icons md-20">monitor</i>
        </span>
      </button>
<!--
   </div>
-->
    <div class="collapse navbar-collapse" id="main-header-nav">
<?php

  // *** Build the navigation bar menu items ***
        echo '<ul class="nav navbar-nav align-self-start justify-content-center">';
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
          echo getReportsHTML($view);
          echo getRprtEvntAuditHTML($view);
          echo getMapHTML($view);
          echo getHeaderFlipHTML();
          echo '</ul></div><div id="accountstatus">
';

        echo '<ul class="nav navbar-nav justify-content-end align-self-start flex-grow-1">';
          echo getAccountCircleHTML($skin, $user);
          echo getStatusBtnHTML($status);
        echo '</ul>
    </div>
      ';
?>
  </nav><!-- End First Navbar -->

  <nav class="navbar navbar-expand-md justify-content-center" id="navbar-two"
<?php echo ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down' ) ? 'style="display:none;"' : '' ?>
>
    <div class="container-fluid" id="panel">
<?php

// *** Build the statistics shown on the navigation bar ***
?>
      <div id="reload" class="container-fluid">
        <ul id="Bandwidth" class="navbar-nav justify-content-start">
          <?php echo getBandwidthHTML($bandwidth_options, $user) ?>
        </ul>
        
        <ul class="navbar-nav list-inline justify-content-center">
          <?php
          echo getSysLoadHTML();
          echo getCpuUsageHTML();
          echo getDbConHTML();
          echo getStorageHTML();
          echo getRamHTML();
          ?>
        </ul>

        <ul id="Version" class="nav navbar-nav justify-content-end">
          <?php echo getZMVersionHTML() ?>
        </ul>
      </div>
<?php
?>
    </div><!-- End Collapsible Panel -->
  </nav><!-- End Second Navbar -->
  
<?php
  } // end if (!ZM_OPT_USE_AUTH) or $user )
  echo getConsoleBannerHTML();
?>
  </div><!--navbars-->
</div><!--navbar continaer-->
<?php
} // end function getNormalNavBarHTML()

//
// A new, slimmer navigation bar, permanently collapsed into a dropdown
//
function getCollapsedNavBarHTML($running, $user, $bandwidth_options, $view, $skin) {
  $status = runtimeStatus($running);
  ?>
  <div class="container-fluid" id="navbar-container">
    <nav class="navbar flex-nowrap">
      <div class="navbar-brand">
        <?php echo getNavBrandHTML() ?>
      </div>
      <nav class="navbar navbar-expand-md">
<?php
  // *** Build the statistics shown on the navigation bar ***
  if ( (!ZM_OPT_USE_AUTH) or $user ) {
?>
        <div id="reload" class="collapse navbar-collapse">
          <ul id="Version" class="navbar-nav">
            <?php echo getZMVersionHTML() ?>
          </ul>
          <ul id="Bandwidth" class="navbar-nav">
            <?php echo getBandwidthHTML($bandwidth_options, $user) ?>
          </ul>

          <ul class="nav navbar-nav list-group">
            <?php
            echo getSysLoadHTML();
            echo getCpuUsageHTML();
            echo getDbConHTML();
            echo getStorageHTML();
            echo getRamHTML();
            #echo getShmHTML();
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
        <span class="navbar-toggler-icon">
          <i class="material-icons md-20">menu</i>
        </span>
      </button>
    <?php } ?>

      <div style="background-color:#485460" class="dropdown-menu dropdown-menu-right px-3" id="main-header-nav">
      <?php
      if ( $user and $user->Username() ) {
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
            echo getReportsHTML($view);
            echo getRprtEvntAuditHTML($view);
            echo getMapHTML($view);
          echo '</ul>';
      }
      ?>
      </div>

    </nav><!-- End First Navbar -->
    <?php echo getConsoleBannerHTML() ?>
  </div>
  
  <?php
} // End function getCollapsedNavBarHTML

// Returns the html representing the current cpu Usage Percent
function getCpuUsageHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
  global $thisServer;
  if ($thisServer) {
    $thisServer->ReadStats();

    $result .= '<li id="getCpuUsageHTML" class="CpuUsage nav-item mx-2">'.PHP_EOL;
    $result .= '&nbsp;'.translate('Cpu').': '.number_format($thisServer->CpuUsagePercent, 1, '.', '').'%'.PHP_EOL;
    $result .= '</li>'.PHP_EOL;
  }
  return $result;
}

// Returns the html representing the current unix style system load
function getSysLoadHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
  global $thisServer;
  if ($thisServer) {
    $thisServer->ReadStats();

    $result .= '<li id="getSysLoadHTML" class="Load nav-item mx-2">';
    $result .= '<i class="material-icons md-18" style="display: inline-block;">trending_up</i>';
    $result .= '&nbsp;'.translate('Load').': '.number_format($thisServer->CpuLoad, 2, '.', '');
    $result .= '</li>'.PHP_EOL;
  } 
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
  $result .= '<i class="material-icons md-18 mr-1" style="display: inline-block;">storage</i>'.PHP_EOL;
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
      $class .= 'text-danger';
    } else if ( $S->disk_usage_percent() > 95 ) {
      $class .= 'text-warning';
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
    if ( $area->disk_usage_percent() > 95 ) $full_warning++;
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
    $result .= '<a class="dropdown-toggle mr-2 '.$class.'" href="#" id="dropdown_storage" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="material-icons md-18 mr-1" style="display: inline-block;">folder_shared</i>Storage</a>'.PHP_EOL;
    $result .= '<div class="dropdown-menu" aria-labelledby="dropdown_storage">'.PHP_EOL;
    
    foreach ( $storage_areas as $area ) {  
      $result .= $func($area, 'dropdown-item ').PHP_EOL;
    } 
    $result .= '</div>'.PHP_EOL;
    $result .= '</li>'.PHP_EOL;
  }
  
  return $result;
}

function getRamHTML() {
  $result = '';
  if ( !canView('System') ) return $result;
  if (file_exists('/proc')) {
    $contents = file_get_contents('/proc/meminfo');
    preg_match_all('/(\w+):\s+(\d+)\s/', $contents, $matches);
    $meminfo = array_combine($matches[1], array_map(function($v){return 1024*$v;}, $matches[2]));
    $mem_used = $meminfo['MemTotal'] - $meminfo['MemFree'] - $meminfo['Buffers'] - $meminfo['Cached'];
    $mem_used_percent = (int)(100*$mem_used/$meminfo['MemTotal']);
    $used_class = '';
    if ($mem_used_percent > 95) {
      $used_class = 'text-danger';
    } else if ($mem_used_percent > 90) {
      $used_class = 'text-warning';
    }
    $result .= ' <li id="getRamHTML" class="nav-item dropdown mx-2">'.
      '<span class="'.$used_class.'" title="' .human_filesize($mem_used). ' of ' .human_filesize($meminfo['MemTotal']). '">'.translate('Memory').': '.$mem_used_percent.'%</span> ';

    if ($meminfo['SwapTotal']) {
      $swap_used = $meminfo['SwapTotal'] - $meminfo['SwapFree'];
      $swap_used_percent = (int)(100*$swap_used/$meminfo['SwapTotal']);
      $swap_class = '';
      if ($swap_used_percent > 95) {
        $swap_class = 'text-danger';
      } else if ($swap_used_percent > 90) {
        $swap_class = 'text-warning';
      }
      $result .= '<span class="'.$swap_class.'" title="' .human_filesize($swap_used). ' of ' .human_filesize($meminfo['SwapTotal']). '">'.translate('Swap').': '.$swap_used_percent.'%</span> ';
    } # end if SwapTotal
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
    $result .= '<nav class="navbar navbar-expand-md justify-content-center" id="navbar-three">';
    $result .= '<h2 id="getConsoleBannerHTML">'.validHtmlStr(ZM_WEB_CONSOLE_BANNER).'</h2>';
    $result .= '</nav>';
  }
  return $result;
}

// Returns the html representing the current high,medium,low bandwidth setting
function getBandwidthHTML($bandwidth_options, $user) {

  # Limit available options to what are available in user
  if ( $user && !empty($user->MaxBandwidth()) ) {
    if ( $user->MaxBandwidth() == 'low' ) {
      unset($bandwidth_options['high']);
      unset($bandwidth_options['medium']);
    } else if ( $user->MaxBandwidth() == 'medium' ) {
      unset($bandwidth_options['high']);
    }
  }

  $result = '';
  if (count($bandwidth_options) > 1) {
    $result .= '<li id="getBandwidthHTML" class="nav-item dropdown mx-2">'.PHP_EOL;
    $result .= '<a class="dropdown-toggle mr-2" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dropdown_bandwidth"><i class="material-icons md-18 mr-1" style="display: inline-block;">network_check</i>'.translate($bandwidth_options[$_COOKIE['zmBandwidth']]).'</a>'.PHP_EOL;

    $result .= '<div class="dropdown-menu" aria-labelledby="dropdown_bandwidth">'.PHP_EOL;  
    if ( isset($bandwidth_options['high']) )
      $result .= '<a data-pdsa-dropdown-val="high" class="dropdown-item bwselect" href="#">' .translate('High'). '</a>'.PHP_EOL;
    if ( isset($bandwidth_options['medium']) )
      $result .= '<a data-pdsa-dropdown-val="medium" class="dropdown-item bwselect" href="#">' .translate('Medium'). '</a>'.PHP_EOL;
    # low is theoretically always available
    $result .= '<a data-pdsa-dropdown-val="low" class="dropdown-item bwselect" href="#">' .translate('Low'). '</a>'.PHP_EOL;    
    $result .= '</div>'.PHP_EOL;

    $result .= '</li>'.PHP_EOL;
  }
    
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
  global $user;
  $result = '';
  
  if (count($user->viewableMonitorIds()) or !ZM\Monitor::find_one()) {
    $result .= '<li id="getConsoleHTML" class="nav-item"><a class="nav-link" href="?view=console">'.translate('Console').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Options menu item
function getOptionsHTML() {
  $result = '';
  
  if ( canView('System') ) {
    $result .= '<li id="getOptionsHTML" class="nav-item"><a class="nav-link" href="?view=options">'.translate('Options').'</a></li>'.PHP_EOL;
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
      $result .= '<li id="getLogHTML" class="nav-item"><a class="nav-link '.$class.'" href="?view=log">'.translate('Log').'</a></li>'.PHP_EOL;
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
      $class = ($logstate == 'ok') ? 'text-success' : ($logstate == 'alert' ? 'text-warning' : (($logstate == 'alarm' ? 'text-danger' : '')));
      $result .= '<li id="getLogIconHTML" class="nav-item">'.
        makeLink('?view=log', '<span class="mx-1 ' .$class. '"><i class="material-icons md-18" style="display: inline-block;">report</i>'.translate('Log').'</span>').
        '</li>'.PHP_EOL;
    }
  }
  
  return $result;
}

// Returns the html representing the X10 Devices menu item
function getDevicesHTML() {
  $result = '';
  
  if ( ZM_OPT_X10 && canView('Devices') ) {
    $result .= '<li id="getDevicesHTML" class="nav-item"><a class="nav-link" href="?view=devices">Devices</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Groups menu item
function getGroupsHTML($view) {
  $result = '';
  if ( !canView('Groups') ) return $result;

  $class = $view == 'groups' ? ' selected' : '';
  $result .= '<li id="getGroupsHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=groups">'. translate('Groups') .'</a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the Filter menu item
function getFilterHTML($view) {
  $result = '';
  if ( !canView('Events') ) return $result;
  
  $class = $view == 'filter' ? ' selected' : '';
  $result .= '<li id="getFilterHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=filter">'.translate('Filters').'</a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the Cycle menu item
function getCycleHTML($view) {
  $result = '';
  
  if ( canView('Stream') ) {
    $class = $view == 'cycle' ? ' selected' : '';
    $result .= '<li id="getCycleHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=watch&amp;cycle=true">' .translate('Cycle'). '</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Montage menu item
function getMontageHTML($view) {
  global $user;
  $result = '';
  
  if (canView('Stream') and count($user->viewableMonitorIds())) {
    $class = $view == 'montage' ? ' selected' : '';
    $result .= '<li id="getMontageHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=montage">' .translate('Montage'). '</a></li>'.PHP_EOL;
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
    $result .= '<li id="getMontageReviewHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=montagereview' .$live. '">'.translate('MontageReview').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Montage menu item
function getSnapshotsHTML($view) {
  $result = '';
  
  if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS and canView('Snapshots')) {
    $class = $view == 'snapshots' ? ' selected' : '';
    $result .= '<li id="getSnapshotsHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=snapshots">' .translate('Snapshots'). '</a></li>'.PHP_EOL;
  }
  
  return $result;
}

function getReportsHTML($view) {
  $result = '';
  
  if (canView('Events')) {
    $class = ($view == 'reports' or $view == 'report') ? ' selected' : '';
    $result .= '<li id="getReportsHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=reports">'.translate('Reports').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Audit Events Report menu item
function getRprtEvntAuditHTML($view) {
  $result = '';
  
  if ( canView('Events') ) {
    $class = $view == 'report_event_audit' ? ' selected' : '';
    $result .= '<li id="getRprtEvntAuditHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=report_event_audit">'.translate('ReportEventAudit').'</a></li>'.PHP_EOL;
  }
  
  return $result;
}

// Returns the html representing the Audit Events Report menu item
function getMapHTML($view) {
  $result = '';

  if (defined('ZM_OPT_USE_GEOLOCATION') and ZM_OPT_USE_GEOLOCATION) {
    $class = $view == 'map' ? ' selected' : '';
    $result .= '<li id="getMapHTML" class="nav-item"><a class="nav-link'.$class.'" href="?view=map">'.translate('Map').'</a></li>'.PHP_EOL;
  }

  return $result;
}

// Returns the html representing the header collapse toggle menu item
function getHeaderFlipHTML() {
  $result = '';
  
  $header = ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down') ? 'down' : 'up';
  $result .= '<li id="getHeaderFlipHTML" class="nav-item dropdown"><a class="nav-link" href="#"><i id="flip" class="material-icons md-18" style="display: inline-block;">keyboard_arrow_' .$header. '</i></a></li>'.PHP_EOL;
  
  return $result;
}

// Returns the html representing the logged in user name and avatar
function getAccountCircleHTML($skin, $user=null) {
  $result = '';
  
  if ( ZM_OPT_USE_AUTH and $user ) {
    $result .= '<li id="getAccountCircleHTML" class="navbar-text navbar-nav mr-2">'.PHP_EOL;
    $result .= makeLink('#', '<i class="material-icons">account_circle</i> '. validHtmlStr($user->Username()),
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
      $result .= '<li class="shutdown">'.PHP_EOL;
      $result .= '<button id="shutdownButton" class="btn btn-default navbar-btn" data-on-click="getShutdownModal" data-toggle="tooltip" data-placement="top" title="' .translate('Shutdown'). '"><i class="material-icons md-18" style="display: inline-block;">power_settings_new</i></button>'.PHP_EOL;
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
  <script src="skins/<?php echo $skin; ?>/js/bootstrap-4.5.0.min.js"></script>
<?php 
  if ( $basename == 'montage' ) {
    echo output_script_if_exists(array('assets/gridstack/dist/gridstack-all.js'));
    echo output_script_if_exists(array('assets/jquery.panzoom/dist/jquery.panzoom.js'));
    echo output_script_if_exists(array('js/panzoom.js'));
    echo output_script_if_exists(array('assets/vis-timeline/standalone/umd/vis-timeline-graph2d.min.js'));
  } else if ( $basename == 'watch' || $basename == 'event') {
    echo output_script_if_exists(array('assets/jquery.panzoom/dist/jquery.panzoom.js'));
    echo output_script_if_exists(array('js/panzoom.js'));
  }

  echo output_script_if_exists(array(
  'js/fontfaceobserver.standalone.js',
  'js/tableExport.min.js',
  'js/bootstrap-table-1.23.5/bootstrap-table.min.js',
  'js/bootstrap-table-1.23.5/extensions/locale/bootstrap-table-locale-all.min.js',
  'js/bootstrap-table-1.23.5/extensions/export/bootstrap-table-export.min.js',
  'js/bootstrap-table-1.23.5/extensions/page-jump-to/bootstrap-table-page-jump-to.min.js',
  'js/bootstrap-table-1.23.5/extensions/cookie/bootstrap-table-cookie.js',
  'js/bootstrap-table-1.23.5/extensions/toolbar/bootstrap-table-toolbar.min.js',
  'js/bootstrap-table-1.23.5/extensions/auto-refresh/bootstrap-table-auto-refresh.min.js',
  'js/bootstrap-table-1.23.5/extensions/mobile/bootstrap-table-mobile.js',
  'js/chosen/chosen.jquery.js',
  'js/dateTimePicker/jquery-ui-timepicker-addon.js',
  'js/Server.js',
), true );
?>
  <script src="skins/<?php echo $skin ?>/js/moment.min.js"></script>
  <script src="skins/<?php echo $skin ?>/js/luxon-3.4.4.min.js"></script>
<?php
?>
  <script nonce="<?php echo $cspNonce; ?>">
    var $j = jQuery.noConflict();
    var DateTime = luxon.DateTime;
<?php
  if ( $skinJsPhpFile ) {
    require_once( $skinJsPhpFile );
  }
  if ( $viewJsPhpFile ) {
    require_once( $viewJsPhpFile );
  }
?>
  </script>
  <script src="<?php echo cache_bust('js/logger.js')?>"></script>
<?php
  if ( $viewJsFile ) {
?>
  <script src="<?php echo cache_bust($viewJsFile) ?>"></script>
<?php
  }
  $skinJsFile = getSkinFile('js/skin.js');
?>
  <script nonce="<?php echo $cspNonce; ?>" src="<?php echo cache_bust($skinJsFile) ?>"></script>
  </body>
</html>
<?php
} // end xhtmlFooter
?>
