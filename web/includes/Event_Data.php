<?php
namespace ZM;
require_once('database.php');
require_once('Event.php');
require_once('Object.php');

class Event_Data extends ZM_Object {
  protected static $table = 'Event_Data';
  protected $defaults = array(
    'Id' => null,
    'EventId' => null,
    'FrameId' => null,
    'MonitorId' => null,
    'TimeStamp' => 0,
    'Data' => '',
  );
  private $Event = null;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Event() {
    if (!$this->Event) {
      if ($this->{'EventId'}) {
        $this->Event = Event::find_one(['Id'=> $this->{'EventId'}]);
      }
      if (!$this->Event) {
        $this->Event = new Event();
      }
    }
    return $this->Event;
  }
} # end class Event_Data
?>
