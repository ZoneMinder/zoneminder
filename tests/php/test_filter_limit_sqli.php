<?php
// Security regression test: the Filter `limit` value is user-controlled and is
// concatenated straight into SQL (web/ajax/modals/filterdebug.php and
// ZM\Filter::Events()). It must be coerced to an integer so a payload like
// "1 AND (SELECT ...)" cannot survive into a LIMIT clause.
// See GHSA-28mv-hqxw-qw84.
//
// Run as: php tests/php/test_filter_limit_sqli.php
//
// Filter.php require_once's database.php / FilterTerm.php / Monitor.php, but
// limit()/Query()/set() only use ZM_Object's magic accessors (no DB), so we
// stub the require-only dependencies and keep the real Object.php + Filter.php.

namespace ZM;

// ---- test harness ----------------------------------------------------------

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

// ---- stub the require-only dependencies of Filter.php ----------------------

$stubdir = sys_get_temp_dir().'/zm_filter_limit_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

require_once __DIR__.'/../../web/includes/Filter.php';

// ---- the reported payload --------------------------------------------------

$payload = '1 AND (SELECT 1 FROM (SELECT extractvalue(1,concat(0x7e,(SELECT Password FROM Users LIMIT 1))))x)';

// Attack entry point used by filterdebug.php: $filter->set($_REQUEST['filter'])
$f = new Filter();
$f->set(array('Query' => array('limit' => $payload)));
check('set() payload is coerced to int 1', $f->limit(), 1);
check('limit() returns an int type', is_int($f->limit()), true);

// The exact fragment the vulnerable sinks build must carry no SQL payload.
$fragment = $f->limit() ? ' LIMIT '.(int)$f->limit() : '';
check('built LIMIT fragment is safe', $fragment, ' LIMIT 1');
check('LIMIT fragment contains no injected keywords',
      (strpos($fragment, 'SELECT') === false) && (strpos($fragment, '(') === false),
      true);

// Setter accessor path (Filter::limit($value)).
$f2 = new Filter();
$f2->limit($payload);
check('limit(setter) payload coerced to int 1', $f2->limit(), 1);

// Benign integer values round-trip.
$f3 = new Filter();
$f3->limit('25');
check('numeric string limit round-trips as int', $f3->limit(), 25);

// A non-numeric junk value collapses to 0 (falsy -> no LIMIT clause emitted).
$f4 = new Filter();
$f4->limit('; DROP TABLE Users; --');
check('non-numeric limit collapses to 0', $f4->limit(), 0);

// Unset limit defaults to 0.
$f5 = new Filter();
check('unset limit defaults to 0', $f5->limit(), 0);

// ---- cleanup ---------------------------------------------------------------

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
