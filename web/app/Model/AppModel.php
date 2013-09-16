<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

	public function daemonStatus() {
		$zm_path_bin = Configure::read('ZM_PATH_BIN');
		$string = $zm_path_bin."/zmdc.pl status";
		$daemon_status = shell_exec ( $string );
		return !strstr($daemon_status, "Unable to connect to server");
	}

	function daemonControl( $command ) {
		$string = Configure::read('ZM_PATH_BIN')."/zmdc.pl $command";
		$string .= " 2>/dev/null >&- <&- >/dev/null";
		$return = exec( $string );
		return $return;
	}

function getSystemLoad()
{
    $uptime = shell_exec( 'uptime' );
    $load = '';
    if ( preg_match( '/load average: ([\d.]+)/', $uptime, $matches ) )
        $load = $matches[1];
    return( $load );
}

function getDiskSpace()
{
    $df = shell_exec( 'df ' . Configure::read('ZM_PATH_WEB') . '/' . Configure::read('ZM_DIR_EVENTS') );
    $space = -1;
    if ( preg_match( '/\s(\d+)%/ms', $df, $matches ) )
        $space = $matches[1];
    return( $space );
}
	 


  public function reScale( $dimension, $dummy) {
    $scale_base = Configure::read('SCALE_BASE');
    for ( $i = 1; $i < func_num_args(); $i++ ) {
      $scale = func_get_arg( $i );
      if ( !empty($scale) && $scale != $scale_base ) {
        $dimension = (int)(($dimension*$scale)/$scale_base);
      }
    }
    return( $dimension );
  }

  public function getEventPath( $event ){
    if (Configure::read('ZM_USE_DEEP_STORAGE')) {
      $eventPath = $event['MonitorId'].'/'.strftime("%y/%m/%d/%H/%M/%S", strtotime($event['StartTime']));
    } else {
      $eventPath = $event['MonitorId'].'/'.$event['Id'];
    }
    return($eventPath);
  }

  public function getImageSrc( $event, $frame, $captureOnly=false, $overwrite=false) {
    $scale = Configure::read('SCALE_BASE');
    $eventPath = $this->getEventPath($event);
    $zm_event_image_digits = Configure::read('ZM_EVENT_IMAGE_DIGITS');
    $zm_dir_images = Configure::read('ZM_DIR_IMAGES');
    $zm_dir_events = Configure::read('ZM_DIR_EVENTS');
    $zm_web_scale_thumbs = Configure::read('ZM_WEB_SCALE_THUMBS');

    if (!is_array($frame)) {
      $frame = array('FrameId' => $frame, 'Type' => '');
    }

    // This is the path to the capture image
    $captImage = sprintf("%0".$zm_event_image_digits."d-capture.jpg", $frame['FrameId']);
    $captPath = $eventPath.'/'.$captImage;
    $thumbCaptPath = $zm_dir_images.'/'.$event['Id'].'-'.$captImage;

    // This is the path to the analysis image
    $analImage = sprintf("%0".$zm_event_image_digits."d-analyse.jpg", $frame['FrameId']);
    $analPath = $eventPath.'/'.$analImage;
    $analFile = $zm_dir_events.'/'.$analPath;
    $thumbAnalPath = $zm_dir_images.'/'.$event['Id'].'-'.$captImage;

    $alarmFrame = $frame['Type'] == 'Alarm';

    $hasAnalImage = $alarmFrame && file_exists( $analFile ) && filesize( $analFile );
    $isAnalImage = $hasAnalImage && !$captureOnly;

    if (!$zm_web_scale_thumbs || $scale >= $scale_base || !function_exists('imagecreatefromjpeg')) {
      $imagePath = $thumbPath = $isAnalImage ? $analPath : $captPath;
      $imageFile = $zm_dir_events.'/'.$imagePath;
      $thumbFile = $zm_dir_events.'/'.$thumbPath;
    } else {
        if (version_compare(phpversion(), "4.3.10", ">=") ) {
            $fraction = sprintf( "%.3F", $scale/$scale_base );
        } else {
            $fraction = sprintf( "%.3f", $scale/$scale_base );
        }
    $scale = (int)round( $scale );

      $thumbCaptPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbCaptPath );
      $thumbAnalPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbAnalPath );
      
      if ( $isAnalImage ) {
        $imagePath = $analPath; 
        $thumbPath = $thumbAnalPath;
      } else {
        $imagePath = $captPath;
        $thumbPath = $thumbCaptPath;
      }
      
      $imageFile = $zm_dir_events."/".$imagePath;
      $thumbFile = $thumbPath;
      if ( $overwrite || !file_exists( $thumbFile ) || !filesize( $thumbFile ) ) {

          // Get new dimentions
          list( $imageWidth, $imageHeight ) = getimagesize( $imageFile );
          $thumbWidth = $imageWidth * $fraction;
          $thumbHeight = $imageHeight * $fraction;

          // Resample
          $thumbImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
          $image = imagecreatefromjpeg( $imageFile );
          imagecopyresampled( $thumbImage, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight );

          if ( !imagejpeg( $thumbImage, $thumbFile ) ) {
              Error( "Can't create thumbnail '$thumbPath'" );
          }
      }
    }

    $imageData = array(
        'eventPath' => $eventPath,
        'imagePath' => $imagePath,
        'thumbPath' => $thumbPath,
        'imageFile' => $imageFile,
        'thumbFile' => $thumbFile,
        'imageClass' => $alarmFrame?"alarm":"normal",
        'isAnalImage' => $isAnalImage,
        'hasAnalImage' => $hasAnalImage
    );

    return($imageData);
  }

}
