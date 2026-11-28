<?php
// An API config edit stored any text in a numeric option, such as "fast" for the integer option
// ZM_WATCH_CHECK_INTERVAL, which the Options page refuses. The Cake model is stubbed over an in-memory Config
// table and its Value rule is run the way Cake runs it, so this works from a source checkout:
//   php tests/php/test_api_config_value_valid.php [repo root]

class App { public static function uses($class, $package) {} }
class Model {
  public $alias = 'Config';
  public $id = null;
  public $validate = array();
  public static $rows = array();
  public function find($type, $options) {
    $name = $options['conditions']['Config.Name'];
    return isset(self::$rows[$name]) ? array('Config' => self::$rows[$name] + array('Name' => $name)) : array();
  }
}
$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
require_once $root.'/web/api/app/Model/AppModel.php';
require_once $root.'/web/api/app/Model/Config.php';

$passes = 0;
$failures = 0;
function check($name, $got, $expected) {
  global $passes, $failures;
  if ($got === $expected) { $passes++; echo "ok - $name\n"; }
  else { $failures++; echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n"; }
}

// Whether the Value rules accept $value for the option $name, as Cake's validation does.
function accepted($name, $value) {
  $config = new Config();
  $config->id = $name;
  $rules = isset($config->validate['Value']) ? $config->validate['Value'] : array();
  foreach ($rules as $rule) {
    $method = $rule['rule'][0];
    if ($config->$method(array('Value' => $value)) !== true) return false;
  }
  return true;
}

Model::$rows = array(
  'ZM_LOG_DATABASE_LIMIT' => array('Value' => '7 day', 'Type' => 'string', 'DefaultValue' => '7 day'),
  'ZM_WEB_REFRESH_MAIN' => array('Value' => '60', 'Type' => 'integer', 'DefaultValue' => '60'),
  'ZM_WATCH_CHECK_INTERVAL' => array('Value' => '10', 'Type' => 'integer', 'DefaultValue' => '10'),
  'ZM_WATCH_MAX_DELAY' => array('Value' => '5', 'Type' => 'decimal', 'DefaultValue' => '5'),
  'ZM_OPT_FFMPEG' => array('Value' => '1', 'Type' => 'boolean', 'DefaultValue' => '1'),
  'ZM_BROKEN' => array('Value' => 'oops', 'Type' => 'integer', 'DefaultValue' => '3'),
);

check('a number is accepted for an integer option', accepted('ZM_WEB_REFRESH_MAIN', '120'), true);
check('text is refused for an integer option', accepted('ZM_WEB_REFRESH_MAIN', 'abc'), false);
check('a decimal is refused for an integer option', accepted('ZM_WEB_REFRESH_MAIN', '1.5'), false);
check('text is accepted for a string option', accepted('ZM_LOG_DATABASE_LIMIT', '30 day'), true);
check('a boolean option takes 0', accepted('ZM_OPT_FFMPEG', '0'), true);
check('text is refused for ZM_WATCH_CHECK_INTERVAL', accepted('ZM_WATCH_CHECK_INTERVAL', 'fast'), false);
check('a decimal is accepted for a decimal option', accepted('ZM_WATCH_MAX_DELAY', '0.5'), true);
check('text is refused for a decimal option', accepted('ZM_WATCH_MAX_DELAY', 'x'), false);
check('the stored value is accepted again', accepted('ZM_BROKEN', 'oops'), true);
check('a number is accepted where the stored value was bad', accepted('ZM_BROKEN', '4'), true);
check('an unknown option is left to the save', accepted('ZM_NO_SUCH_OPTION', 'x'), true);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
