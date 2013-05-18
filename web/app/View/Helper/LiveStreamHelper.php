<?php
App::uses('AppHelper', 'View/Helper');

class LiveStreamHelper extends AppHelper {
	public function makeLiveStream($name, $src, $id) {
		$liveStream = "<img id=\"liveStream_$id\" alt=\"Live Stream of $name\" src=\"$src&monitor=$id\">";
		return $liveStream;
	}
}
?>
