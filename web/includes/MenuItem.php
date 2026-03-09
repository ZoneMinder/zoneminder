<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class MenuItem extends ZM_Object {
  protected static $table = 'Menu_Items';

  protected $defaults = array(
    'Id'        => null,
    'MenuKey'   => '',
    'Enabled'   => 1,
    'Label'     => null,
    'SortOrder' => 0,
    'Icon'      => null,
    'IconType'  => 'material',
  );

  // Default material icons for each menu key
  public static $defaultIcons = array(
    'Console'          => 'dashboard',
    'Montage'          => 'live_tv',
    'MontageReview'    => 'movie',
    'Events'           => 'event',
    'Options'          => 'settings',
    'Log'              => 'notification_important',
    'Devices'          => 'devices_other',
    'IntelGpu'         => 'memory',
    'Groups'           => 'group',
    'Filters'          => 'filter_alt',
    'Snapshots'        => 'preview',
    'Reports'          => 'report',
    'ReportEventAudit' => 'shield',
    'Map'              => 'language',
  );

  public function effectiveIcon() {
    if ($this->{'Icon'} !== null && $this->{'Icon'} !== '') {
      return $this->{'Icon'};
    }
    return isset(self::$defaultIcons[$this->{'MenuKey'}]) ? self::$defaultIcons[$this->{'MenuKey'}] : 'menu';
  }

  public function effectiveIconType() {
    if ($this->{'IconType'} == 'none') {
      return 'none';
    }
    if ($this->{'Icon'} !== null && $this->{'Icon'} !== '') {
      return $this->{'IconType'};
    }
    return 'material';
  }

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function displayLabel() {
    if ($this->{'Label'} !== null && $this->{'Label'} !== '') {
      return $this->{'Label'};
    }
    return translate($this->{'MenuKey'});
  }
}
