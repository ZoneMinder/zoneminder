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
			'Units'               => 'Percent',
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

  // Built-in zone presets shipped with ZoneMinder (database Name => translation key prefix).
  // Add new entries here only when new default presets are added to the DB schema (zm_create.sql),
  // along with corresponding '<Key>' and '<Key>Desc' strings in the language files.
  // Entries listed here are localized in the UI and protected from modification or deletion.
  public const BUILT_IN_PRESETS = array(
    'Default'                  => 'ZonePresetDefault',
    'Fast, low sensitivity'    => 'ZonePresetFastLow',
    'Fast, medium sensitivity' => 'ZonePresetFastMedium',
    'Fast, high sensitivity'   => 'ZonePresetFastHigh',
    'Best, low sensitivity'    => 'ZonePresetBestLow',
    'Best, medium sensitivity' => 'ZonePresetBestMedium',
    'Best, high sensitivity'   => 'ZonePresetBestHigh',
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

  public function svg_polygon($width=0, $height=0) {
    $areaCoords = $this->AreaCoords();
    if ($width && $height) {
      $points = coordsToPoints($this->Coords());
      $isPixel = false;
      foreach ($points as $point) {
        if ($point['x'] > 100 || $point['y'] > 100) {
          $isPixel = true;
          break;
        }
      }
      if ($isPixel) {
        foreach ($points as &$point) {
          $point['x'] = round($point['x'] / $width * 100, 2);
          $point['y'] = round($point['y'] / $height * 100, 2);
        }
        unset($point);
        $areaCoords = preg_replace('/\s+/', ',', pointsToCoords($points));
      }
    }
    return '<polygon points="'.$areaCoords.'" class="'.$this->Type().'" data-mid="'.$this->MonitorId().'" data-zid="'.$this->Id().'"><title>'.validHtmlStr($this->Name()).'</title></polygon>';
  }
} # end class Zone
?>
