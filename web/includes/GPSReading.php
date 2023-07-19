<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Object_Type.php');

class GPSReading extends ZM_Object {
  protected static $table = 'GPSReading';
  protected $defaults = array(
    'Id' => null,
    'ObjectId'          => null,
    'ObjectTypeId'      => null,
    'TimeStamp'         => 0,
    'Latitude'          => '',
    'Longitude'         => '',
    'Accuracy'          => null,
    'Altitude'          => null,
    'AltitudeAccuracy'  => null,
    'Heading'           => null,
    'Speed'             => null,
  );
  private $Event = null;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function ZMObject($new=null) {
    if ($new) {
      $this->ZMObject = $new;
      $this->ObjectId = $new->Id();
      $this->Object_Type = $new->Object_Type();
      $this->ObjectTypeId = $this->Object_Type->Id();
    }
    if (!$this->ZMObject) {
      if ($this->{'ObjectId'}) {
        $this->ZMObject = ZM_Object::_find_one($this->Object_Type->Name(), ['Id'=> $this->{'ObjectId'}]);
      }
    }
    return $this->ZMObject;
  }
} # end class GPSReading
?>
