<?php
class Frame extends AppModel {
  public $useTable = 'Frames';
  public $primaryKey = 'FrameId';
  public $belongsTo = array(
    'Event' => array(
      'className' => 'Event',
      'foreignKey' => 'EventId'
    )
  );

  public function createListThumbnail( $event, $overwrite=false) {
    $frame = $this->find('first', array(
      'conditions' => array(
        'EventId' => $event['Id'],
        'Score' => $event['MaxScore']
      ),
      'order' => 'FrameId'
    ));

    if (!($frame)) {
      return ("Whoa now!  Could not locate a frame for this event.");
    }

    $frameId = $frame['Frame']['FrameId'];
    $thumbWidth = Configure::read('ZM_WEB_LIST_THUMB_WIDTH');
    $thumbHeight = Configure::read('ZM_WEB_LIST_THUMB_HEIGHT');
    $scale = Configure::read('SCALE_BASE');

    // Should we scale the thumbnail based on the width or height of the image?
    // By default, ZM_WEB_LIST_THUMB_WIDTH is set, ZM_WEB_LIST_THUMB_HEIGHT is not.
    if ($thumbWidth) {
      $scale = ($scale*$thumbWidth)/$event['Width'];
      $thumbHeight = $this->reScale($event['Height'], $scale);
    } elseif ($thumbHeight) {
      $scale = ($scale*$thumbHeight)/$event['Height'];
      $thumbWidth = $this->reScale($event['Width'], $scale);
    } else {
      return ("No thumbnail width or height specified, please check in Options->Web");
    }

    // Get the path to the image on the filesystem
    $imageData = $this->getImageSrc( $event, $frame['Frame'], $scale, false, $overwrite );

    $thumbData = $frame;
    $thumbData['Path'] = $imageData['thumbPath'];
    $thumbData['Width'] = (int)$thumbWidth;
    $thumbData['Height'] = (int)$thumbHeight;

    return ($thumbData);
  }

}
?>
