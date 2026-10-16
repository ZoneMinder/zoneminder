<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Control.php');
require_once('Monitor.php');

class MonitorAction extends ZM_Object {
  protected static $table = 'MonitorActions';

  protected $defaults = array(
    'Id'              => null,
    'MonitorId'       => 0,
    'TriggerOn'       => 'EventStart',
    'ActionType'      => 'AudioPlay',
    'TargetMonitorId' => 0,
    'AudioFile'       => null,
    'Label'           => '',
    'Enabled'         => 1,
    'Sequence'        => 0,
  );

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  // The zmcontrol command an action type maps onto. Mirrors
  // Monitor::ActionCommandName in src/zm_monitor.cpp; keep the two in step.
  public static function commandFor($action_type) {
    $commands = array(
      'LightOn'             => 'lightOn',
      'LightOff'            => 'lightOff',
      'IndicatorLightOn'    => 'indicatorLightOn',
      'IndicatorLightOff'   => 'indicatorLightOff',
      'AudioPlay'           => 'audioPlay',
      'AudioStop'           => 'audioStop',
    );
    return isset($commands[$action_type]) ? $commands[$action_type] : null;
  }

  // Which action types a given monitor can actually perform, decided by its
  // Controls row rather than assumed. A monitor with no control, or one whose
  // capabilities were measured as absent, offers nothing.
  public static function typesForMonitor($monitor) {
    $types = array();
    if (!$monitor or !$monitor->Controllable() or !$monitor->ControlId())
      return $types;

    $control = $monitor->Control();
    if (!$control or !$control->Id())
      return $types;

    if ($control->CanLight()) {
      $types[] = 'LightOn';
      $types[] = 'LightOff';
    }
    if ($control->CanIndicatorLight()) {
      $types[] = 'IndicatorLightOn';
      $types[] = 'IndicatorLightOff';
    }
    if ($control->CanAudioPlay()) {
      $types[] = 'AudioPlay';
      $types[] = 'AudioStop';
    }
    return $types;
  }

  // Monitors that can be the target of an action, with the capabilities the
  // editor needs to constrain its inputs.
  public static function targetCandidates() {
    $candidates = array();
    foreach (Monitor::find(array('Deleted' => 0)) as $monitor) {
      $types = self::typesForMonitor($monitor);
      if (!count($types))
        continue;
      $control = $monitor->Control();
      $candidates[] = array(
        'Id'          => $monitor->Id(),
        'Name'        => $monitor->Name(),
        'DeviceClass' => $monitor->DeviceClass(),
        'Types'       => $types,
        'MinAudioFile' => $control->MinAudioFile(),
        'MaxAudioFile' => $control->MaxAudioFile(),
      );
    }
    return $candidates;
  }

  public function Monitor() {
    return Monitor::find_one(array('Id' => $this->{'MonitorId'}));
  }

  public function TargetMonitor() {
    return Monitor::find_one(array('Id' => $this->{'TargetMonitorId'}));
  }

  // Build the zmcontrol option string this action sends, in the same form
  // buildControlCommand produces for the control panel.
  public function controlCommand() {
    $command = self::commandFor($this->{'ActionType'});
    if (!$command)
      return null;

    $options = '';
    if ($this->{'ActionType'} == 'AudioPlay' and $this->{'AudioFile'} !== null)
      $options .= ' --file='.validInt($this->{'AudioFile'});
    return $options.' --command='.$command;
  }
}
?>
