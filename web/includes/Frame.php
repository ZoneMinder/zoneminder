<?php
namespace ZM;
require_once('database.php');
require_once('Event.php');
require_once('Object.php');

class Frame extends ZM_Object {
  protected static $table = 'Frames';
  protected $defaults = array(
    'Id' => null,
    'EventId' => 0,
    'FrameId' => 0,
    'Type' => 'Normal',
    'TimeStamp' => 0,
    'Delta' => 0.00,
    'Score' => 0,
  );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Storage() {
    return $this->Event()->Storage();
  }

  public function Event() {
    return new Event( $this->{'EventId'} );
  }

  public function getImageSrc( $show='capture' ) {
    return '?view=image&fid='.$this->{'FrameId'}.'&eid='.$this->{'EventId'}.'&show='.$show;
    #return '?view=image&fid='.$this->{'Id'}.'&show='.$show.'&filename='.$this->Event()->MonitorId().'_'.$this->{'EventId'}.'_'.$this->{'FrameId'}.'.jpg';
  } // end function getImageSrc

} # end class Frame
?>
