<?php
//
// ZoneMinder web function library, $Date: 2008-07-08 16:06:45 +0100 (Tue, 08 Jul 2008) $, $Revision: 2484 $
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
if ( file_exists( "skins/$skin/css/$css/graphics/favicon.ico" ) ) {
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
?>
  <link rel="stylesheet" href="css/reset.css" type="text/css"/>
  <link rel="stylesheet" href="css/overlay.css" type="text/css"/>
  <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css"/>
  
<?php 
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

  <script src="tools/mootools/mootools-core.js"></script>
  <script src="tools/mootools/mootools-more.js"></script>
  <script src="js/mootools.ext.js"></script>
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
  if ( $title == 'Login' && (defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA) ) {
?>
  <script src='https://www.google.com/recaptcha/api.js'></script>
<?php
  } else if ( $view == 'event' ) {
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
<div class="navbar navbar-inverse navbar-static-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
			</button>
      <div class="navbar-brand">
        <a href="<?php echo validHtmlStr(ZM_HOME_URL); ?>" target="<?php echo validHtmlStr(ZM_WEB_TITLE); ?>"><?php echo ZM_HOME_CONTENT ?></a>
      </div>
		</div>

		<div class="collapse navbar-collapse" id="main-header-nav">
		<ul class="nav navbar-nav">
<?php
if ( $user and $user['Username'] ) {
  if ( canView('Monitors') ) {
?>
			<li><a href="?view=console"><?php echo translate('Console') ?></a></li>
<?php
  } // end if canView('Monitors')
  if ( canView('System') ) {
?>
			<li><a href="?view=options"><?php echo translate('Options') ?></a></li>
			<li>
<?php
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
      echo makePopupLink('?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.translate('Log').'</span>');
    }
?>
      </li>
<?php
  } // end if canview(System)
  if ( ZM_OPT_X10 && canView('Devices') ) { ?>
			<li><a href="?view=devices">Devices</a></li>
<?php
  }
?>
      <li><a href="?view=groups"<?php echo $view=='groups'?' class="selected"':''?>><?php echo translate('Groups') ?></a></li>
      <li><a href="?view=filter<?php echo $filterQuery.$sortQuery.$limitQuery ?>"<?php echo $view=='filter'?' class="selected"':''?>><?php echo translate('Filters') ?></a></li>

<?php 
  if ( canView('Stream') ) {
?>
      <li><a href="?view=cycle"<?php echo $view=='cycle'?' class="selected"':''?>><?php echo translate('Cycle') ?></a></li>
      <li><a href="?view=montage"<?php echo $view=='montage'?' class="selected"':''?>><?php echo translate('Montage') ?></a></li>
<?php
   }
  
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
 ?>
      <li><a href="?view=montagereview<?php echo isset($montageReviewQuery)?'&fit=1'.$montageReviewQuery.'&live=0':'' ?>"<?php echo $view=='montagereview'?' class="selected"':''?>><?php echo translate('MontageReview')?></a></li>
      <li><a href="?view=report_event_audit"<?php echo $view=='report_event_audit'?' class="selected"':''?>><?php echo translate('ReportEventAudit') ?></a></li>
<?php
  }
?>
      <li><a href="#"><i id="flip" class="material-icons md-18 pull-right">keyboard_arrow_<?php echo ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down') ? 'down' : 'up' ?></i></a></li>
		</ul>

<div class="navbar-right">
<?php
if ( ZM_OPT_USE_AUTH and $user ) {
?>
  <p class="navbar-text">
    <i class="material-icons">account_circle</i>
    <?php echo makePopupLink('?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == 'builtin')) ?>
  </p>
<?php
}
if ( canEdit('System') ) {
?>
		<button type="button" class="btn btn-default navbar-btn" data-toggle="modal" data-target="#modalState"><?php echo $status ?></button>
  <?php if ( ZM_SYSTEM_SHUTDOWN ) { ?>
  <p class="navbar-text">
  <?php echo makePopupLink('?view=shutdown', 'zmShutdown', 'shutdown', '<i class="material-icons md-18">power_settings_new</i>' ) ?>
  </p>
  <?php } ?>
<?php } else if ( canView('System') ) { ?>
		<p class="navbar-text"><?php echo $status ?></p>
<?php } ?>
</div>
<?php } # end if !$user or $user['Id'] meaning logged in ?>
		</div><!-- End .navbar-collapse -->
	</div> <!-- End .container-fluid -->
  <div id="panel"<?php echo ( isset($_COOKIE['zmHeaderFlip']) and $_COOKIE['zmHeaderFlip'] == 'down' ) ? 'style="display:none;"' : '' ?>>
<?php
} //end reload null.  Runs on full page load

if ( (!ZM_OPT_USE_AUTH) or $user ) {
  if ($reload == 'reload') ob_start();
?>
	<div id="reload" class="container-fluid reduced-text">
    <div id="Bandwidth" class="pull-left">
      <?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', "<i class='material-icons md-18'>network_check</i>&nbsp;".$bandwidth_options[$_COOKIE['zmBandwidth']] . ' ', ($user && $user['MaxBandwidth'] != 'low' ) ) ?>
    </div>
    <div id="Version" class="pull-right">
      <?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="version '.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit('System') ) ?>
    </div>
    <ul class="list-inline">
      <li class="Load"><i class="material-icons md-18">trending_up</i>&nbsp;<?php echo translate('Load') ?>: <?php echo getLoad() ?></li>
      <i class="material-icons md-18">storage</i>
<?php 
  $connections = dbFetchOne('SHOW status WHERE variable_name=\'threads_connected\'', 'Value');
  $max_connections = dbFetchOne('SHOW variables WHERE variable_name=\'max_connections\'', 'Value');
  $percent_used = $max_connections ? 100 * $connections / $max_connections : 100;
  echo '<li'. ( $percent_used > 90 ? ' class="warning"' : '' ).'>'.translate('DB').':'.$connections.'/'.$max_connections.'</li>';
?>
	  <li><?php echo translate('Storage') ?>:
<?php
  $storage_areas = ZM\Storage::find(array('Enabled'=>true));
  $storage_paths = null;
	$storage_areas_with_no_server_id = array();
  foreach ( $storage_areas as $area ) {
    $storage_paths[$area->Path()] = $area;
		if ( ! $area->ServerId() ) {
			$storage_areas_with_no_server_id[] = $area;
		}
  }
  $func = function($S){
    $class = '';
    if ( $S->disk_usage_percent() > 98 ) {
      $class = 'error';
    } else if ( $S->disk_usage_percent() > 90 ) {
      $class = 'warning';
    }
    $title = human_filesize($S->disk_used_space()) . ' of ' . human_filesize($S->disk_total_space()). 
      ( ( $S->disk_used_space() != $S->event_disk_space() ) ? ' ' .human_filesize($S->event_disk_space()) . ' used by events' : '' );

    return '<span class="'.$class.'" title="'.$title.'">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</span>
'; };
  #$func =  function($S){ return '<span title="">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</span>'; };
  if ( count($storage_areas) > 4 ) 
    $storage_areas = $storage_areas_with_no_server_id;
  if ( count($storage_areas) <= 4 )
    echo implode(', ', array_map($func, $storage_areas));
  $shm_percent = getDiskPercent(ZM_PATH_MAP);
  $class = '';
  if ( $shm_percent > 98 ) {
    $class = 'error';
  } else if ( $shm_percent > 90 ) {
    $class = 'warning';
  }
  echo ' <span class="'.$class.'">'.ZM_PATH_MAP.': '.$shm_percent.'%</span>';
?></li>
  </ul>
    <?php if ( defined('ZM_WEB_CONSOLE_BANNER') and ZM_WEB_CONSOLE_BANNER != '' ) { ?>
        <h3 id="development"><?php echo validHtmlStr(ZM_WEB_CONSOLE_BANNER); ?></h3>
    <?php } ?>	
<!-- End .footer/reload --></div>
<?php
  if ($reload == 'reload') return ob_get_clean();
} // end if (!ZM_OPT_USE_AUTH) or $user )
?>
  </div>
</div><!-- End .navbar .navbar-default -->
<?php
  return ob_get_clean();
} // end function getNavBarHTML()

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
