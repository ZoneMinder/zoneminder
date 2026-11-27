<?php
// The Reports list ignored the search box: the query had no WHERE clause and
// the reported total was the size of the current page, so server-side
// pagination broke as soon as text was typed into the search field.
// Run: php tests/php/test_reports_search_total.php   (from the repo root)

$stub = sys_get_temp_dir().'/zm_reports_stub_'.getmypid();
mkdir($stub.'/includes', 0777, true);
file_put_contents($stub.'/includes/Filter.php', '<?php');
file_put_contents($stub.'/includes/Report.php', '<?php namespace ZM; function Debug($m){} function Error($m){} function Warning($m){} class Report { function __construct($r=null){} function Id(){return 1;} function remove_from_cache(){} }');
set_include_path($stub.PATH_SEPARATOR.get_include_path());

$queries = array();
$counts = array();
$rows = array(array('Id'=>1, 'Name'=>'Alpha', 'FilterId'=>1, 'StartDateTime'=>null, 'EndDateTime'=>null, 'Interval'=>'86400'));

function dbQuery($sql, $params = null) { global $queries; $queries[] = array($sql, $params); return true; }
function dbFetchNext($q) { global $rows; return array_shift($rows); }
function dbFetchOne($sql, $col = null, $params = null) { global $counts; $counts[] = array($sql, $params); return 7; }
function canView($a) { return true; }
function canEdit($a) { return true; }
function validHtmlStr($s) { return $s; }
function ajaxError($m) { echo "ajaxError: $m\n"; }
function ajaxResponse($d) { global $response; $response = $d; }
class FakeFmt { function format($t) { return 'x'; } }
$dateTimeFormatter = new FakeFmt();
$user = null;
$_REQUEST = array('task'=>'query', 'search'=>'Alp');

include __DIR__.'/../../web/ajax/reports.php';

$fail = array();
list($sql, $params) = $queries[0];
if (strpos($sql, 'LIKE') === false) $fail[] = "search not applied to query: $sql";
if ($params !== array('%Alp%')) $fail[] = 'search value not bound: '.json_encode($params);
if ($response['total'] !== 7 || strpos($counts[count($counts)-1][0], 'LIKE') === false) $fail[] = 'total is not the filtered count: '.json_encode($response['total']);

system('rm -rf '.escapeshellarg($stub));
if ($fail) { echo "FAIL\n".implode("\n", $fail)."\n"; exit(1); }
echo "PASS\n";
