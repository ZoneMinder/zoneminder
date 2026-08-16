<?php
// Integration test for ZM\Filter sort helpers. Filter extends the ZM_Object ORM
// base, so loading it pulls in ZM's DB-stored config; we therefore bootstrap
// config.php (DB connect) BEFORE Filter.php. The tested methods are pure.
// Run: sudo -u www-data php tests/php/event_sort_test.php   (from the repo root)
set_include_path(__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());
require_once('config.php');   // connects to DB + loads config; must precede Filter
require_once('Filter.php');

$failures = 0;
function check($label, $got, $want) {
  global $failures;
  $ok = ($got === $want);
  if (!$ok) $failures++;
  printf("[%s] %s\n        got:  %s\n        want: %s\n",
    $ok ? 'PASS' : 'FAIL', $label, var_export($got, true), var_export($want, true));
}

// Resolver mirrors web/ajax/events.php: whitelist + alias, null if not allowed.
$resolve = function($col) {
  $cols = array('Id', 'Name', 'StartDateTime', 'EndDateTime', 'Monitor', 'Tags');
  if (!in_array($col, $cols)) return null;
  if ($col == 'Tags') return 'Tags';
  if ($col == 'Monitor') return 'M.Name';
  return 'E.'.$col;
};
$B = function($sort, $order) use ($resolve) { return \ZM\Filter::buildSortSql($sort, $order, $resolve); };

// buildSortSql
check('empty -> empty',              $B('', 'ASC'),                              '');
check('bare inherits DESC',          $B('Id', 'DESC'),                           'E.Id DESC');
check('bare inherits ASC',           $B('Id', 'ASC'),                            'E.Id ASC');
check('monitor alias',               $B('Monitor', 'ASC'),                       'M.Name ASC');
check('tags alias',                  $B('Tags', 'ASC'),                          'Tags ASC');
check('mixed explicit+default',      $B('StartDateTime DESC, Id', 'ASC'),        'E.StartDateTime DESC, E.Id ASC');
check('all explicit ignores global', $B('StartDateTime DESC, Id ASC', 'DESC'),   'E.StartDateTime DESC, E.Id ASC');
check('endDateTime ASC rewrite form',$B('EndDateTime IS NULL ASC, EndDateTime ASC', 'ASC'),
                                                                                 'E.EndDateTime IS NULL ASC, E.EndDateTime ASC');
check('endDateTime DESC rewrite form',$B('EndDateTime IS NOT NULL ASC, EndDateTime DESC', 'DESC'),
                                                                                 'E.EndDateTime IS NOT NULL ASC, E.EndDateTime DESC');
check('is null inherits order',      $B('EndDateTime IS NULL', 'ASC'),           'E.EndDateTime IS NULL ASC');
check('keyword case normalized',     $B('EndDateTime is null desc', 'ASC'),      'E.EndDateTime IS NULL DESC');
check('not whitelisted -> empty',    $B('Bogus', 'ASC'),                         '');
check('injection -> empty',          $B('Id; DROP TABLE Events', 'ASC'),         '');
check('one bad part fails whole',    $B('Id, Bogus', 'ASC'),                     '');
check('bad order -> empty',          $B('Id', 'SLEEP(5)--'),                     '');
check('lowercase order normalized',  $B('Id', 'asc'),                            'E.Id ASC');

// isValidSortExpression (structural only — does NOT whitelist)
check('valid empty',      \ZM\Filter::isValidSortExpression(''),                              true);
check('valid bare',       \ZM\Filter::isValidSortExpression('Id'),                            true);
check('valid directional',\ZM\Filter::isValidSortExpression('StartDateTime DESC, Id ASC'),    true);
check('valid is null',    \ZM\Filter::isValidSortExpression('EndDateTime IS NOT NULL, EndDateTime'), true);
check('invalid inject',   \ZM\Filter::isValidSortExpression('Id; DROP TABLE'),                false);
check('invalid two dirs', \ZM\Filter::isValidSortExpression('Id ASC DESC'),                   false);
check('invalid nonstring',\ZM\Filter::isValidSortExpression(123),                             false);

echo $failures ? "\n$failures FAILURE(S)\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
