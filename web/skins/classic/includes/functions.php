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

// Only load new js & css in these views
$new_views = array('login');

function xhtmlHeaders( $file, $title ) {
  global  $css;
  global  $skin;
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

  <script type="text/javascript" src="js/logger.js"></script>
  <script type="text/javascript" src="js/overlay.js"></script>
<?php if ( $title == 'Login' && (defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA) ) { ?>
  <script src='https://www.google.com/recaptcha/api.js'></script>
<?php } else if ( $title == 'Event' ) {
?>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
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
<?php
  if ( $viewJsFile ) {
?>
  <script type="text/javascript" src="<?php echo $viewJsFile ?>"></script>
<?php
  }
?>
</head>
<?php
}
?>
