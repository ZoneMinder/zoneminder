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


// Don't load in additional JS to these views
$bad_views = array('monitor', 'log');

function xhtmlHeaders( $file, $title ) {
  global $css;
  global $skin;
  $skinCssFile = getSkinFile( 'css/'.$css.'/skin.css' );
  $skinCssPhpFile = getSkinFile( 'css/'.$css.'/skin.css.php' );

  $skinJsFile = getSkinFile( 'js/skin.js' );
  $skinJsPhpFile = getSkinFile( 'js/skin.js.php' );
  $cssJsFile = getSkinFile( 'js/'.$css.'.js' );

  $basename = basename( $file, '.php' );
  $viewCssFile = getSkinFile( '/css/'.$css.'/views/'.$basename.'.css' );
  $viewCssPhpFile = getSkinFile( '/css/'.$css.'/views/'.$basename.'.css.php' );
  $viewJsFile = getSkinFile( 'views/js/'.$basename.'.js' );
  $viewJsPhpFile = getSkinFile( 'views/js/'.$basename.'.js.php' );

  extract( $GLOBALS, EXTR_OVERWRITE );
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo ZM_WEB_TITLE_PREFIX ?> - <?php echo validHtmlStr($title) ?></title>
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
  <link rel="stylesheet" href="css/reset.css" type="text/css"/>
  <link rel="stylesheet" href="css/overlay.css" type="text/css"/>
  <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css"/>
  <link rel="stylesheet" href="<?php echo $skinCssFile ?>" type="text/css" media="screen"/>
<?php
  if ( $viewCssFile ) {
?>
  <link rel="stylesheet" href="<?php echo $viewCssFile ?>" type="text/css" media="screen"/>
<?php
  }
  if ( $viewCssPhpFile ) {
?>
  <style type="text/css">
  /*<![CDATA[*/
<?php
    require_once( $viewCssPhpFile );
?>
  /*]]>*/
  </style>
<?php
  }
?>
  <script type="text/javascript" src="tools/mootools/mootools-core.js"></script>
  <script type="text/javascript" src="tools/mootools/mootools-more.js"></script>
  <script type="text/javascript" src="js/mootools.ext.js"></script>
  <script type="text/javascript" src="skins/<?php echo $skin; ?>/js/jquery.js"></script>
  <script type="text/javascript" src="skins/<?php echo $skin; ?>/js/jquery-ui.js"></script>
  <script type="text/javascript" src="skins/<?php echo $skin; ?>/js/bootstrap.min.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  <!--
  var $j = jQuery.noConflict();
  // $j is now an alias to the jQuery function; creating the new alias is optional.

  //-->
  //]]>
  </script>
  <script type="text/javascript" src="skins/<?php echo $skin; ?>/views/js/state.js"></script>
<?php
  if ( $title == 'Login' && (defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA) ) {
?>
  <script src='https://www.google.com/recaptcha/api.js'></script>
<?php
  } else if ( $title == 'Event' ) {
?>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
  <script src="skins/<?php echo $skin ?>/js/moment.min.js"></script>
<?php
  }
  if ( $skinJsPhpFile ) {
?>
  <script type="text/javascript">
  //<![CDATA[
  <!--
<?php
    require_once( $skinJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
  }
  if ( $viewJsPhpFile ) {
?>
  <script type="text/javascript">
  //<![CDATA[
  <!--
<?php
    require_once( $viewJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
  }
	if ( $cssJsFile ) {
?>
  <script type="text/javascript" src="<?php echo $cssJsFile ?>"></script>
<?php } ?>
  <script type="text/javascript" src="<?php echo $skinJsFile ?>"></script>
  <script type="text/javascript" src="js/logger.js"></script>
<?php
  if ( $viewJsFile ) {
?>
  <script type="text/javascript" src="<?php echo $viewJsFile ?>"></script>
<?php
  }
?>
</head>
<?php
} // end function xhtmlHeaders( $file, $title )

function getNavBarHTML() {

  $group = NULL;
  if ( ! empty($_COOKIE['zmGroup']) ) {
	  if ( $group = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_COOKIE['zmGroup'])) )
		  $groupIds = array_flip(explode( ',', $group['MonitorIds'] ));
  }

  $maxWidth = 0;
  $maxHeight = 0;
  # Used to determine if the Cycle button should be made available
  $cycleCount = 0;
  $monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
  global $displayMonitors;
  $displayMonitors = array();
  for ( $i = 0; $i < count($monitors); $i++ ) {
    if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
      continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) ) {
      continue;
    }
    if ( $monitors[$i]['Function'] != 'None' ) {
      $cycleCount++;
      $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
      $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
      if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
      if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
    }
    $displayMonitors[] = $monitors[$i];
  }

  $cycleWidth = $maxWidth;
  $cycleHeight = $maxHeight;

  $versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';

  ob_start();
  global $CLANG;
  global $VLANG;
  global $CLANG;
  global $VLANG;
  global $running;
  if ( $running == null )
    $running = daemonCheck();
  $status = $running?translate('Running'):translate('Stopped');
  global $user;
  global $bwArray;
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
			<a class="navbar-brand" href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a>
		</div>

		<div class="collapse navbar-collapse" id="main-header-nav">
		<ul class="nav navbar-nav">
			<li><a href="?view=console"><?php echo translate('Console') ?></a></li>
<?php if ( canView( 'System' ) ) { ?>
			<li><a href="?view=options"><?php echo translate('Options') ?></a></li>
			<li><?php if ( logToDatabase() > Logger::NOLOG ) { ?> <?php echo makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.translate('Log').'</span>' ) ?><?php } ?></li>
<?php } ?>
<?php if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
			<li><a href="/?view=devices">Devices</a></li>
<?php } ?>
			<li><?php echo makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'Groups' ) ); ?></li>
			<li><a href="/?view=filter">Filters</a></li>

<?php 
  $cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
  if ( canView( 'Stream' ) && $cycleCount > 1 ) {
?>
					<li><?php echo makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), translate('Cycle'), $running ) ?></li>
					<li><?php echo makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', translate('Montage'), $running ) ?></li>
<?php
   }
  if ( canView('Events') ) {
 ?>
					<li><?php echo makePopupLink( '?view=montagereview&amp;group='.$cycleGroup, 'zmMontageReview'.$cycleGroup, 'montagereview', translate('MontageReview') ) ?></li>
<?php
  }
?>
		</ul>

<div class="navbar-right">
<?php if ( ZM_OPT_USE_AUTH ) { ?>
	<p class="navbar-text"><?php echo translate('LoggedInAs') ?> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?> </p>
<?php } ?>

<?php if ( canEdit( 'System' ) ) { ?>
		<button type="button" class="btn btn-default navbar-btn" data-toggle="modal" data-target="#modalState"><?php echo $status ?></button>

<?php } else if ( canView( 'System' ) ) { ?>
		<p class="navbar-text"> <?php echo $status ?> </p>
<?php } ?>
</div>
		</div><!-- End .navbar-collapse -->
	</div> <!-- End .container-fluid -->
	<div class="container-fluid">
  <div class="pull-left">
    <?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?php echo translate('BandwidthHead') ?>
  </div>
  <div class="pull-right">
	  <?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="'.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?>
  <?php if ( defined('ZM_WEB_CONSOLE_BANNER') and ZM_WEB_CONSOLE_BANNER != '' ) { ?>
      <h3 id="development"><?php echo ZM_WEB_CONSOLE_BANNER ?></h3>
  <?php } ?>
  </div>
  <ul class="list-inline">
	  <li><?php echo translate('Load') ?>: <?php echo getLoad() ?></li>
<?php 
  $connections = dbFetchOne( "SHOW status WHERE variable_name='threads_connected'", 'Value' );
  $max_connections = dbFetchOne( "SHOW variables WHERE variable_name='max_connections'", 'Value' );
  $percent_used = 100 * $connections / $max_connections;
  echo '<li'. ( $percent_used > 90 ? ' class="warning"' : '' ).'>'.translate('DB').':'.$connections.'/'.$max_connections.'</li>';
?>
	  <li><?php echo translate('Storage') ?>:
<?php
  $storage_areas = Storage::find_all();
  $storage_paths = null;
  foreach ( $storage_areas as $area ) {
    $storage_paths[$area->Path()] = $area;
  }
  if ( ! isset($storage_paths[ZM_DIR_EVENTS]) ) {
    array_push( $storage_areas, new Storage() );
  }
  $func =  function($S){ return $S->Name() . ': ' . $S->disk_usage_percent().'%'; };
  echo implode( ', ', array_map ( $func, $storage_areas ) );
  echo ' ' . ZM_PATH_MAP .': '. getDiskPercent(ZM_PATH_MAP).'%';
?></li>
  </ul>
</div> <!-- End .footer -->

</div> <!-- End .navbar .navbar-default -->
<?php
  return( ob_get_clean() );
} // end function getNavBarHTML()
?>
