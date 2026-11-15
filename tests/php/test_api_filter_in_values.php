<?php
// Test for REST API FilterComponent::buildFilter() handling of the IN operator
// and preservation of `=` values.

class App {
  public static function uses($class, $package) {}
}
class Component {}
class BadRequestException extends Exception {}

require_once __DIR__.'/../../web/api/app/Controller/Component/FilterComponent.php';

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

function attempt($named) {
  try {
    $conditions = (new FilterComponent())->buildFilter($named);
  } catch (Exception $e) {
    return array('rejected' => $e->getMessage());
  }
  return array('built' => $conditions);
}

// 1. IN:(1,2) strips surrounding parentheses, parses elements
check('IN:(1,2) parses into array of values',
  attempt(array('MonitorId IN' => '(1,2)')),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);

// 2. IN:1,2 without parentheses keeps working
check('IN:1,2 keeps working without parentheses',
  attempt(array('MonitorId IN' => '1,2')),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);

// 3. IN:( 1 , 2 ) trims whitespace around elements
check('IN:( 1 , 2 ) trims whitespace around elements',
  attempt(array('MonitorId IN' => '( 1 , 2 )')),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);

// 4. IN:() with no elements throws BadRequestException
$res = attempt(array('MonitorId IN' => '()'));
check('IN:() throws exception', isset($res['rejected']), true);
check('IN:() rejection message', isset($res['rejected']) ? $res['rejected'] : '', 'Invalid value for IN: ()');

// Check other empty variants throw exception
$resEmpty = attempt(array('MonitorId IN' => '( , )'));
check('IN:( , ) throws exception', isset($resEmpty['rejected']), true);

// 5. = values are unchanged
check('= value without operator is unchanged',
  attempt(array('MonitorId' => '5')),
  array('built' => array('MonitorId' => '5'))
);

check('= value with explicit operator is unchanged',
  attempt(array('MonitorId =' => '5')),
  array('built' => array('MonitorId =' => '5'))
);

check('= value containing parentheses is unchanged',
  attempt(array('Name' => '(foo)')),
  array('built' => array('Name' => '(foo)'))
);

check('= value with explicit operator and parentheses is unchanged',
  attempt(array('Name =' => '(foo)')),
  array('built' => array('Name =' => '(foo)'))
);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
