<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Event.php');
require_once('Tag.php');

class Event_Tag extends ZM_Object {
  protected static $table = 'Events_Tags';

  public $TagId;
  public $EventId;
  public $AssignedDate; 
  public $AssignedBy;
  public $Tag;
  public $Event;

	protected $defaults = array(
			'TagId'         => null,
			'EventId'       => null,
      'AssignedDate'  => null,
      'AssignedBy'    => 0,
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Event() {
    if (!isset($this->Event)) {
      $this->Event = Event::find_one(['Id'=>$this->EventId]);
    } else {
      Debug("Already have Event");
    }
    return $this->Event;
  }

  public function Tag() {
    if (!isset($this->Tag)) {
      $this->Tag = Tag::find_one(['Id'=>$this->TagId]);
    } else {
      Debug("Already have Tag");
    }
    return $this->Tag;
  }
} # end class Event_Tag
?>
