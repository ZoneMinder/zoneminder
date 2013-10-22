<?php
  class Monitor extends AppModel {
    public $useTable = 'Monitors';
    public $primaryKey = 'Id';
    public $displayField = 'Name';
    public $hasMany = array(
	'Event' => array(
		'className' => 'Event',
		'foreignKey' => 'MonitorId',
		'fields' => 'Event.Id'
	),
	'Zone' => array(
		'className' => 'Zone',
		'foreignKey' => 'MonitorId'
	)
);

    public function getStreamSrc($id = null, $buffer, $function, $enabled, $name, $width, $scale = true) {
	$img['id'] = "livestream_$id";
	$img['width'] = $width;
      
        $ZM_MPEG_LIVE_FORMAT = Configure::read('ZM_MPEG_LIVE_FORMAT');
			  $ZM_WEB_STREAM_METHOD = ClassRegistry::init('Config')->getWebOption('ZM_WEB_STREAM_METHOD');
			  $ZM_WEB_VIDEO_BITRATE = ClassRegistry::init('Config')->getWebOption('ZM_WEB_VIDEO_BITRATE');
			  $ZM_WEB_VIDEO_MAXFPS = ClassRegistry::init('Config')->getWebOption('ZM_WEB_VIDEO_MAXFPS');
			  $ZM_MPEG_LIVE_FORMAT = $ZM_MPEG_LIVE_FORMAT;

	if (Configure::read('daemonStatus') && $function != "None" && $enabled) {
				$img['alt'] = "Live stream of $name";
			  if ($ZM_WEB_STREAM_METHOD == 'mpeg' && $ZM_MPEG_LIVE_FORMAT) {
			    $img['src'] = "/cgi-bin/nph-zms?mode=mpeg&maxfps=$ZM_WEB_VIDEO_MAXFPS&bitrate=$ZM_WEB_VIDEO_BITRATE&format=$ZM_MPEG_LIVE_FORMAT&monitor=$id";
			  } else {
			    $img['src'] = "/cgi-bin/nph-zms?mode=jpeg&maxfps=$ZM_WEB_VIDEO_MAXFPS&buffer=$buffer&monitor=$id";
			  }
			if ($scale) {
				$scale = (Configure::read('ZM_WEB_LIST_THUMB_WIDTH') / $width) * 100;
				$img['src'] .= '&scale=' . $scale;
			}
		} else {
			$img['src'] = "/img/no-image.png";
			$img['alt'] = "No live stream available for $name";
		}
		return $img;
    }
  }
?>
