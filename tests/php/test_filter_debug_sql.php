<?php
// The filterdebug modal shows (and EXPLAINs) the SQL a filter runs. It has to
// match what zmfilter builds in ZoneMinder::Filter::Sql:
//
//   * the term expression is wrapped in parentheses, so an OR in it does not
//     swallow the AND clauses added after it
//   * the Auto* actions add their "not done yet" conditions
//   * a filter with no valid terms has no WHERE at all, instead of a dangling
//     "WHERE" that makes the EXPLAIN fail
//   * ORDER BY columns are qualified, since Id and Name exist in both Events
//     and Monitors and are rejected as ambiguous
//   * the modal does not read an absent fid from $_REQUEST
//
// Run as: php tests/php/test_filter_debug_sql.php

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
    echo "FAIL - $name\n   got:  ".var_export($got, true)."\n   want: ".var_export($want, true)."\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_filter_debug_sql_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'FilterTerm.php', 'Monitor.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}
define('ZM_WEB_EVENT_SORT_FIELD', 'StartDateTime');
define('ZM_WEB_EVENT_SORT_ORDER', 'asc');
function jsonEncode($v) { return json_encode($v); }
function jsonDecode($v) { return json_decode($v, true); }

require_once __DIR__.'/../../web/includes/Filter.php';

// Terms need the database, so inject the already built term expression.
function make_filter($terms_sql, $query = array(), $fields = array()) {
  $f = new Filter();
  $p = new \ReflectionProperty(Filter::class, '_sql');
  $p->setAccessible(true);
  if ($query) $f->Query($query);
  foreach ($fields as $k => $v) $f->$k($v);
  // Query() clears the built sql, so inject last.
  $p->setValue($f, $terms_sql);
  return $f;
}
function norm($s) { return trim(preg_replace('/\s+/', ' ', $s)); }

$base = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId)';

$has = method_exists(Filter::class, 'debugSql');
check('Filter::debugSql exists', $has, true);
if ($has) {
  $f = make_filter("E.Id > 1 OR E.Id < 5\n", array('sort_field'=>'StartDateTime', 'sort_asc'=>0));
  check('terms are parenthesised',
    norm($f->debugSql()), $base.' WHERE ( E.Id > 1 OR E.Id < 5 ) ORDER BY E.StartDateTime DESC');

  $f = make_filter("E.Id > 1\n", array('sort_field'=>'', 'skip_locked'=>1), array('LockRows'=>1));
  check('skip locked is ANDed to the whole expression',
    norm($f->debugSql()), $base.' WHERE ( E.Id > 1 ) AND NOT EXISTS (SELECT 1 FROM Events_Lock AS EL WHERE EL.EventId=E.Id AND EL.ExpiresAt>NOW())');

  $f = make_filter("E.Id > 1\n", array('sort_field'=>'Id', 'sort_asc'=>1, 'limit'=>5),
    array('AutoArchive'=>1, 'AutoVideo'=>1, 'AutoEmail'=>1));
  check('auto actions add their conditions, Id is qualified, limit kept',
    norm($f->debugSql()), $base.' WHERE ( E.Id > 1 ) AND ( E.Archived = 0 or E.Videoed = 0 or E.Emailed = 0 ) ORDER BY E.Id ASC LIMIT 5');

  $f = make_filter('', array('sort_field'=>'Name', 'sort_asc'=>1));
  check('no terms means no WHERE, Name is qualified', norm($f->debugSql()), $base.' ORDER BY E.Name ASC');

  $f = make_filter('', array('sort_field'=>'', 'sort_asc'=>1), array('AutoUnarchive'=>1));
  check('no terms but an auto action still gets a WHERE',
    norm($f->debugSql()), $base.' WHERE ( E.Archived = 1 )');

  $f = make_filter("E.Id > 1\n", array('sort_field'=>'', 'sort_asc'=>1), array('AutoTag'=>1, 'AutoTagId'=>7));
  check('tagging skips events that already have the tag',
    norm($f->debugSql()), $base.' WHERE ( E.Id > 1 ) AND ( NOT EXISTS (SELECT 1 FROM Events_Tags AS ET WHERE ET.EventId=E.Id AND ET.TagId=7) )');

  $f = make_filter("E.Id > 1\n", array('sort_field'=>'', 'sort_asc'=>1), array('AutoTag'=>1, 'AutoTagId'=>0));
  check('tagging with no tag adds no condition', norm($f->debugSql()), $base.' WHERE ( E.Id > 1 )');

  $f = make_filter("E.Id > 1\n", array('sort_field'=>'MonitorName', 'sort_asc'=>1));
  check('MonitorName sorts on the monitor', norm($f->debugSql()), $base.' WHERE ( E.Id > 1 ) ORDER BY M.Name ASC');
}

$modal = file_get_contents(__DIR__.'/../../web/ajax/modals/filterdebug.php');
check('modal does not read an absent fid',
  (bool)preg_match('/\$fid\s*=\s*isset\(\$_REQUEST\[.fid.\]\)/', $modal), true);

@unlink($stubdir.'/database.php');
@unlink($stubdir.'/FilterTerm.php');
@unlink($stubdir.'/Monitor.php');
@rmdir($stubdir);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
