<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');


class Zone extends ZM_Object {
  protected static $table = 'Zones';

	protected $defaults = array(
			'Id'                  => null,
      'MonitorId'           => null,
			'Name'                => '',
			'Type' => 'Active',
			'Units' => 'Pixels',
      'NumCoords' => '0',
      'Coords' => 0,
      'Area' => '0',
      'AlarmRGB' => '0',
			'CheckMethod' => 'Blobs',
			'MinPixelThreshold' => null,
			'MaxPixelThreshold' => null,
			'MinAlarmPixels' => null,
			'MaxAlarmPixels' => null,
			'FilterX' => null,
			'FilterY' => null,
			'MinFilterPixels' => null,
			'MaxFilterPixels' => null,
			'MinBlobPixels' => null,
			'MaxBlobPixels' => null,
			'MinBlobs' => null,
			'MaxBlobs' => null,
			'OverloadFrames' => 0,
			'ExtendAlarmFrames' => 0,
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }
  public function Monitor() {
    if ( isset($this->{'MonitorId'}) ) {
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
    return '<polygon points="'.$this->AreaCoords().'" class="'.$this->Type().'" />';
  }

} # end class Zone
?>
