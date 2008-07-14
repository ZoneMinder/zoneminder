<?php
function getDeviceScale( $width, $height, $divisor=1 )
{
    global $device;

    $deviceWidth = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
    $deviceHeight = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;

    // Allow for margins etc
    //$deviceWidth -= 4;
    //$deviceHeight -= 4;

    $widthScale = ($deviceWidth*SCALE_BASE)/$width;
    $heightScale = ($deviceHeight*SCALE_BASE)/$height;
    $scale = ($widthScale<$heightScale)?$widthScale:$heightScale;
    if ( $divisor != 1.0 )
        $scale = $scale/$divisor;
    error_log( "Div:$divisor" );
    error_log( "Scale:$scale" );
    return( intval($scale) );
}

function xhtmlHeaders( $file, $title )
{
    $skinCssFile = getSkinFile( 'css/skin.css' );
    $skinCssPhpFile = getSkinFile( 'css/skin.css.php' );

    $basename = basename( $file, '.php' );
    $viewCssFile = getSkinFile( 'views/css/'.$basename.'.css' );
    $viewCssPhpFile = getSkinFile( 'views/css/'.$basename.'.css.php' );

    extract( $GLOBALS, EXTR_OVERWRITE );

    noCacheHeaders();
    header("Content-type: application/xhtml+xml" );
    echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" );
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $title ?></title>
  <link rel="icon" type="image/ico" href="favicon.ico"/>
  <link rel="shortcut icon" href="favicon.ico"/>
  <link rel="stylesheet" href="css/reset.css" type="text/css" media="screen"/>
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
<?php
        require_once( $viewCssPhpFile );
?>
  </style>
<?php
    }
?>
</head>
<?php
}

?>
