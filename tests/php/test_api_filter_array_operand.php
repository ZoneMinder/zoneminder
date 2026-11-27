<?php
// REST API FilterComponent::buildFilter(): a named parameter given twice, or
// written Field[]:x, reaches it as an array. Only =, != and IN can take a list
// (CakePHP writes IN / NOT IN); any other operator got a TypeError for LIKE and
// a SQL syntax error for the rest, both a 500. They are a 400 now.

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

$list = array('a', 'b');
foreach (array('LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP', 'RLIKE', '>', '<=') as $op) {
  check("$op with a list is a bad request",
    attempt(array("Name $op" => $list)),
    array('BadRequestException' => "Invalid value for $op: array")
  );
}

check('LIKE with one value still gets wildcards',
  attempt(array('Name LIKE' => 'cam')),
  array('built' => array('Name LIKE' => '%cam%'))
);
check('no operator with a list is still IN',
  attempt(array('MonitorId' => array('1', '2'))),
  array('built' => array(array('MonitorId' => array('1', '2'))))
);
check('= with a list is still allowed',
  attempt(array('MonitorId =' => array('1', '2'))),
  array('built' => array(array('MonitorId =' => array('1', '2'))))
);
check('!= with a list is still allowed',
  attempt(array('MonitorId !=' => array('1', '2'))),
  array('built' => array(array('MonitorId !=' => array('1', '2'))))
);
check('IN with a list is still allowed',
  attempt(array('MonitorId IN' => array('1', '2'))),
  array('built' => array(array('MonitorId IN' => array('1', '2'))))
);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
