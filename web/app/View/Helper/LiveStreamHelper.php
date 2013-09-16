<?php
App::uses('AppHelper', 'View/Helper');

class LiveStreamHelper extends AppHelper {
	public function makeLiveStream($name, $src, $id, $width=0) {
		$liveStream = "<img id=\"liveStream_$id\" alt=\"Live Stream of $name\" src=\"$src&monitor=$id\" width=\"$width\">";
		return $liveStream;
	}
	
	public function showNoImage($name, $src, $id, $width=0) {
		$liveStream = "<img id=\"liveStream_$id\" alt=\"Live Stream of $name\" src=\"/img/no-image.jpg".width?"width=\"$width">";
		return $liveStream;
	}
}
?>
