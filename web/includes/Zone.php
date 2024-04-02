<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Monitor.php');

class Zone extends ZM_Object {
  protected static $table = 'Zones';

	protected $defaults = array(
			'Id'                  => null,
      'MonitorId'           => null,
			'Name'                => '',
			'Type'                => 'Active',
			'Units'               => 'Pixels',
      'NumCoords'           => '4',
      'Coords'              => '',
      'Area'                => '0',
      'AlarmRGB'            => 0xff0000,
			'CheckMethod'         => 'Blobs',
			'MinPixelThreshold'   => 25,
			'MaxPixelThreshold'   => null,
			'MinAlarmPixels'      => null,
			'MaxAlarmPixels'      => null,
			'FilterX'             => 3,
			'FilterY'             => 3,
			'MinFilterPixels'     => null,
			'MaxFilterPixels'     => null,
			'MinBlobPixels'       => null,
			'MaxBlobPixels'       => null,
			'MinBlobs'            => 1,
			'MaxBlobs'            => null,
			'OverloadFrames'      => 0,
			'ExtendAlarmFrames'   => 0,
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Monitor() {
    if (isset($this->{'MonitorId'})) {
      $Monitor = Monitor::find_one(array('Id'=>$this->{'MonitorId'}));
      if ( $Monitor )
        return $Monitor;
    }
    return new Monitor();
  }

  public function Points() {
    return coordsToPoints($this->Coords());
  }

  public function AreaCoords() {
    return preg_replace('/\s+/', ',', $this->Coords());
  }

  public function svg_polygon() {
    return '<polygon points="'.$this->AreaCoords().'" class="'.$this->Type().'" data-mid="'.$this->MonitorId().'" data-zid="'.$this->Id().'"><title>'.$this->Name().'</title></polygon>';
  }
} # end class Zone
?>
