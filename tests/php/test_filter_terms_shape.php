<?php
// ZM\Filter::terms() used to return Query['terms'] as-is. A filter built from
// the request (?view=filter&filter[Query][terms]=x or terms[]=x) then made
// callers fatal under PHP 8: count($filter->terms()) on a string
// (views/filter.php:81, views/event.php:180), and has_term()/widget() indexing
// a string term. terms() must return only the array terms as a packed list,
// and array() for a non-array.
//
// Run as: php tests/php/test_filter_terms_shape.php [repo-root]
//
// terms()/has_term() only read Query(), so stub the require-only dependencies
// and drive the real ZM\Filter (as test_filter_canview.php does).

namespace ZM;

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

function fail_exception($label, $e) {
  global $failures;
  $failures++;
  echo "FAIL - $label threw ".get_class($e).': '.$e->getMessage()."\n";
}

function run_case($label, $fn) {
  try {
    $fn();
  } catch (\Throwable $e) {
    fail_exception($label, $e);
  }
}

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

$stubdir = '/tmp/numes-tests/zm_filter_terms_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.$root.'/web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function Info($s)    { /* noop */ }
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

require_once $root.'/web/includes/Filter.php';

// a) Query terms = 'x' (a string from filter[Query][terms]=x).
run_case('a', function() {
  $f = new Filter();
  $f->Query(array('terms' => 'x'));
  $terms = $f->terms();
  check('a: string terms() returns array()', $terms, array());
  check('a: count(string terms()) is 0', count($terms), 0);
  check('a: has_term(Id) on a string term is false', $f->has_term('Id'), false);
});

// b) Query terms = array('x') (filter[Query][terms][]=x).
run_case('b', function() {
  $f = new Filter();
  $f->Query(array('terms' => array('x')));
  $terms = $f->terms();
  check('b: array of a string terms() returns array()', $terms, array());
  check('b: count(array of a string terms()) is 0', count($terms), 0);
  check('b: has_term(Id) on a string term is false', $f->has_term('Id'), false);
});

// c) Mixed string and array terms: only the array term survives, at index 0.
run_case('c', function() {
  $term = array('attr' => 'Id', 'op' => '=', 'val' => '5');
  $f = new Filter();
  $f->Query(array('terms' => array('x', $term)));
  $terms = $f->terms();
  check('c: only the array term is returned', $terms, array($term));
  check('c: has_term(Id) finds the array term', $f->has_term('Id') !== false, true);
});

// d) A normal terms array of two terms is returned unchanged.
run_case('d', function() {
  $wanted = array(
    array('attr' => 'MonitorId', 'op' => '=', 'val' => '64'),
    array('cnj' => 'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '2026-08-09 00:00:00'),
  );
  $f = new Filter();
  $f->Query(array('terms' => $wanted));
  check('d: two array terms are returned unchanged in order', $f->terms(), $wanted);
});

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
