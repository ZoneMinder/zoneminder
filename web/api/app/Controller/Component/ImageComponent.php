<?php
App::uses('Component', 'Controller');

class ImageComponent extends Component {
	public function getImageSrc( $event, $frame, $scale, $config ) {
	        $eventPath = $this->getEventPath($event);
		
		$captImage = sprintf( "%0".$config['ZM_EVENT_IMAGE_DIGITS']."d-capture.jpg", $frame['Frame']['FrameId'] );
		$captPath = $eventPath.'/'.$captImage;

		$analImage = sprintf( "%0".$config['ZM_EVENT_IMAGE_DIGITS']."d-analyse.jpg", $frame['Frame']['FrameId'] );
		$analPath = $eventPath.'/'.$analImage;
		$analFile =  $config['ZM_DIR_EVENTS']."/".$analPath;

		$alarmFrame = $frame['Frame']['Type']=='Alarm';
		
		$hasAnalImage = $alarmFrame && file_exists( $analFile ) && filesize( $analFile );
		$isAnalImage = $hasAnalImage && !$captureOnly;


		if ( !$config['ZM_WEB_SCALE_THUMBS'] || $scale >= 100 || !function_exists( 'imagecreatefromjpeg' ) ) {
			$imagePath = $thumbPath = $isAnalImage?$analPath:$captPath;
			$imageFile = $config['ZM_DIR_EVENTS']."/".$imagePath;
			$thumbFile = $config['ZM_DIR_EVENTS']."/".$thumbPath;
		} else {
			if ( version_compare( phpversion(), "4.3.10", ">=") )
			    $fraction = sprintf( "%.3F", $scale/100 );
			else
			    $fraction = sprintf( "%.3f", $scale/100 );
			$scale = (int)round( $scale );
			
			$thumbCaptPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $captPath );
			$thumbAnalPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $analPath );
			
			if ( $isAnalImage )
			{
			    $imagePath = $analPath;
			    $thumbPath = $thumbAnalPath;
			}
			else
			{
			    $imagePath = $captPath;
			    $thumbPath = $thumbCaptPath;
			}
			
			$imageFile = $config['ZM_DIR_EVENTS']."/".$imagePath;
			//$thumbFile = ZM_DIR_EVENTS."/".$thumbPath;
			$thumbFile = $thumbPath;
			// This segment of code results in errors when trying to get Events API
			// This actually seems to be generating images for the angular UI web view
			// and should not be a part of the API anyway
			// I've commented it so events APIs continue to work
			// I did ask Kyle about this, but I don't have an answer from him
			// Either way, it does no harm to remove it -- as the UI of master 
			// does not use API code anyway
			/*
			if ( $overwrite || !file_exists( $thumbFile ) || !filesize( $thumbFile ) )
			{
			    // Get new dimensions
			    list( $imageWidth, $imageHeight ) = getimagesize( $imageFile );
			    $thumbWidth = $imageWidth * $fraction;
			    $thumbHeight = $imageHeight * $fraction;
			
			    // Resample
			    $thumbImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
			    $image = imagecreatefromjpeg( $imageFile );
			    imagecopyresampled( $thumbImage, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight );
			
			    if ( !imagejpeg( $thumbImage, $thumbFile ) )
			        Error( "Can't create thumbnail '$thumbPath'" );
			}
		      */
		}

		/*
		$imageData = array(
		    'eventPath' => $eventPath,
		    'imagePath' => $imagePath,
		    'thumbPath' => $thumbPath,
		    'imageFile' => $imageFile,
		    'thumbFile' => $thumbFile,
		    'imageClass' => $alarmFrame?"alarm":"normal",
		    'isAnalImage' => $isAnalImage,
		    'hasAnalImage' => $hasAnalImage,
		);

		return( $imageData );
	 */

	}

	// Take the StartTime of an Event and return
	// the path to its location on the filesystem
	public function getEventPath( $event ) {
    if ( $config['ZM_USE_DEEP_STORAGE'] == 1 )
      return $event['Event']['MonitorId'].'/'.strftime( "%y/%m/%d/%H/%M/%S", strtotime($event['Event']['StartTime']) );
    else
      return $event['Event']['MonitorId'].'/'.$event['Event']['Id'];
  }
}
?>
