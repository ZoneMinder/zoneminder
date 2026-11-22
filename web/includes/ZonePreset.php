<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class ZonePreset extends ZM_Object {
  protected static $table = 'ZonePresets';

  protected $defaults = array(
    'Id'                  => null,
    'Name'                => '',
    'Type'                => 'Active',
    'Units'               => 'Percent',
    'CheckMethod'         => 'Blobs',
    'MinPixelThreshold'   => null,
    'MaxPixelThreshold'   => null,
    'MinAlarmPixels'      => null,
    'MaxAlarmPixels'      => null,
    'FilterX'             => null,
    'FilterY'             => null,
    'MinFilterPixels'     => null,
    'MaxFilterPixels'     => null,
    'MinBlobPixels'       => null,
    'MaxBlobPixels'       => null,
    'MinBlobs'            => null,
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

  const CHECK_METHOD_INDEXES = array(
    'AlarmedPixels'  => 0,
    'FilteredPixels' => 1,
    'Blobs'          => 2,
  );

  // ZonePresets columns excluded when saving or applying preset parameters
  const EXCLUDED_FIELDS = array(
    'Id',
    'Name',
    'Type',
    'Units',
  );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  // Returns the list of configurable ZonePresets column names
  public static function getConfigurableFields() {
    $instance = new self();
    return array_values(array_diff(array_keys($instance->defaults), self::EXCLUDED_FIELDS));
  }

  public static function isBuiltInName($name) {
    $trimmed = trim((string)$name);
    if ( $trimmed === '' ) {
      return false;
    }
    foreach ( self::BUILT_IN_PRESETS as $sysName => $langKey ) {
      if ( strcasecmp($trimmed, $sysName) === 0 || strcasecmp($trimmed, translate($langKey)) === 0 ) {
        return true;
      }
    }
    return false;
  }

  public function isBuiltIn() {
    $name = trim((string)$this->Name());
    return isset(self::BUILT_IN_PRESETS[$name]) || (int)$this->Id() <= count(self::BUILT_IN_PRESETS);
  }

  public function CheckMethodIndex() {
    $method = $this->CheckMethod();
    return isset(self::CHECK_METHOD_INDEXES[$method]) ? self::CHECK_METHOD_INDEXES[$method] : 2;
  }

  public function toArray() {
    $data = array();
    foreach ( array_keys($this->defaults) as $field ) {
      $data[$field] = $this->$field();
    }
    $data['CheckMethodIndex'] = $this->CheckMethodIndex();
    $data['isBuiltIn'] = $this->isBuiltIn();
    return $data;
  }
} # end class ZonePreset
?>
