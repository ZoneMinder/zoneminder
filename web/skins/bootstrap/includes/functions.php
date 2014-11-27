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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

function xhtmlHeaders( $file, $title )
{
    $skinCssFile = getSkinFile( 'css/skin.css' );
    $skinCssPhpFile = getSkinFile( 'css/skin.css.php' );
    $skinJsFile = getSkinFile( 'js/skin.js' );
    $skinJsPhpFile = getSkinFile( 'js/skin.js.php' );
		$jqueryJsFile = getSkinFile( 'js/jquery-2.1.1.min.js');
    $statePhpFile = getSkinFile( 'views/js/state.js.php' );
		$stateJsFile = getSkinFile( 'views/js/state.js');
		$bootstrapCssFile = getSkinFile( 'css/bootstrap.css' );
		$bootstrapJsFile = getSkinFile( 'js/bootstrap.min.js' );
		$ChartJsFile = getSkinFile( 'js/Chart.min.js' );
		$tcangularchartjsFile = getSkinFile( 'js/tc-angular-chartjs.min.js' );
		$uibootstrapJsFile = getSkinFile( 'js/ui-bootstrap-tpls-0.12.0.min.js' );
    $basename = basename( $file, '.php' );
    $viewCssFile = getSkinFile( 'views/css/'.$basename.'.css' );
    $viewCssPhpFile = getSkinFile( 'views/css/'.$basename.'.css.php' );
    $viewJsFile = getSkinFile( 'views/js/'.$basename.'.js' );
    $viewJsPhpFile = getSkinFile( 'views/js/'.$basename.'.js.php' );

    extract( $GLOBALS, EXTR_OVERWRITE );
?>
<!DOCTYPE html>
<html lang="en" ng-app="ZoneMinder">
<head>
  <title><?= ZM_WEB_TITLE_PREFIX ?> - <?= validHtmlStr($title) ?></title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<base href="/">
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
  <link rel="stylesheet" href="css/reset.css" type="text/css"/>
  <link rel="stylesheet" href="css/overlay.css" type="text/css"/>
  <link rel="stylesheet" href="<?= $bootstrapCssFile ?>" type="text/css" media="screen"/>
  <link rel="stylesheet" href="<?= $skinCssFile ?>" type="text/css" media="screen"/>
<?php
    if ( $viewCssFile )
    {
?>
  <link rel="stylesheet" href="<?= $viewCssFile ?>" type="text/css" media="screen"/>
<?php
    }
    if ( $viewCssPhpFile )
    {
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
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.2/angular.min.js"></script>
  <script type="text/javascript" src="<?= $ChartJsFile ?>"></script>
	<script src="<?= getSkinFile('js/app.js'); ?>"></script>
	<script src="<?= getSkinFile('js/controllers.js'); ?>"></script>
  <script type="text/javascript" src="<?= $tcangularchartjsFile ?>"></script>
  <script type="text/javascript" src="<?= $uibootstrapJsFile ?>"></script>
  <script type="text/javascript" src="<?= $jqueryJsFile ?>"></script>
  <script type="text/javascript" src="<?= $bootstrapJsFile ?>"></script>
  <script type="text/javascript" src="tools/mootools/mootools-core.js"></script>
  <script type="text/javascript" src="tools/mootools/mootools-more.js"></script>
  <script type="text/javascript" src="js/mootools.ext.js"></script>
  <script type="text/javascript" src="js/logger.js"></script>
  <script type="text/javascript" src="js/overlay.js"></script>
<?php
    if ( $skinJsPhpFile )
    {
?>
  <script type="text/javascript">
  //<![CDATA[
  <!--
<?php
    require_once( $skinJsPhpFile );
    require_once( $statePhpFile );
?>
  //-->
  //]]>
  </script>
<?php
    }
    if ( $viewJsPhpFile )
    {
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
?>
  <script type="text/javascript" src="<?= $skinJsFile ?>"></script>
<?php
    if ( $viewJsFile )
    {
?>
  <script type="text/javascript" src="<?= $viewJsFile ?>"></script>
<?php
    }
?>
  <script type="text/javascript" src="<?= $stateJsFile ?>"></script>
</head>
<?php
}
?>
