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
}
?>
