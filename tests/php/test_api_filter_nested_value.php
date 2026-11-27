<?php
// REST API FilterComponent::buildFilter(): a named parameter written Field[a][b]:x, or a list holding a list,
// reaches it as a nested array. CakePHP writes the inner array into the SQL as the word Array and MySQL
// answers "Unknown column 'Array'", a 500. It is a 400 now; a flat list and a plain value are unchanged.
//   php tests/php/test_api_filter_nested_value.php

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
  } catch (Throwable $e) {
    return array(get_class($e) => $e->getMessage());
  }
  return array('built' => $conditions);
}

check('a nested array is a bad request',
  attempt(array('Name' => array('a' => array('b' => '1')))),
  array('BadRequestException' => 'Invalid value for Name: nested array')
);
check('a list holding a list is a bad request',
  attempt(array('MonitorId' => array('1', array('2')))),
  array('BadRequestException' => 'Invalid value for MonitorId: nested array')
);
check('IN with a nested array is a bad request',
  attempt(array('MonitorId IN' => array(array('1')))),
  array('BadRequestException' => 'Invalid value for MonitorId: nested array')
);
check('= with a nested array is a bad request',
  attempt(array('MonitorId =' => array('1', array('x' => '2')))),
  array('BadRequestException' => 'Invalid value for MonitorId: nested array')
);

check('a plain value is unchanged',
  attempt(array('Name' => 'cam')),
  array('built' => array('Name' => 'cam'))
);
check('a flat list is still IN',
  attempt(array('MonitorId' => array('1', '2'))),
  array('built' => array(array('MonitorId' => array('1', '2'))))
);
check('IN with a flat list is unchanged',
  attempt(array('MonitorId IN' => array('1', '2'))),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);
check('IN with a comma list is unchanged',
  attempt(array('MonitorId IN' => '1,2')),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);
check('LIKE with one value still gets wildcards',
  attempt(array('Name LIKE' => 'cam')),
  array('built' => array('Name LIKE' => '%cam%'))
);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
