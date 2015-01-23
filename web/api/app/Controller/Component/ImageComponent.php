<?php
App::uses('Component', 'Controller');

class ImageComponent extends Component {
	public function getImageSrc( $event, $frame, $scale, $config ) {
	        $eventPath = $this->getEventPath($event);
		
		$captImage = sprintf( "%0".$config['ZM_EVENT_IMAGE_DIGITS']."d-capture.jpg", $frame['Frame']['FrameId'] );
		$captPath = $eventPath.'/'.$captImage;
		$thumbCaptPath = $config['ZM_DIR_IMAGES'].'/'.$event['Event']['Id'].'-'.$captImage;

		$analImage = sprintf( "%0".$config['ZM_EVENT_IMAGE_DIGITS']."d-analyse.jpg", $frame['Frame']['FrameId'] );
		$analPath = $eventPath.'/'.$analImage;
		$analFile =  $config['ZM_DIR_EVENTS']."/".$analPath;
		$thumbAnalPath = $config['ZM_DIR_IMAGES'].'/'.$event['Event']['Id'].'-'.$analImage;

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
			
			$thumbCaptPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbCaptPath );
			$thumbAnalPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbAnalPath );
			
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
		}

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

	}

	// Take the StartTime of an Event and return
	// the path to its location on the filesystem
	public function getEventPath( $event ) {
	        return $event['Event']['MonitorId'].'/'.strftime( "%y/%m/%d/%H/%M/%S", strtotime($event['Event']['StartTime']) );
	}
}
?>
