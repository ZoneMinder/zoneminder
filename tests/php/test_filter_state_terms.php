<?php
// The Events table records the per-event Videoed, Uploaded, Emailed, Messaged
// and Executed flags. The filter editor must offer them like Archived so a
// filter can select events that are not yet uploaded or already emailed.
//
// Run as: php tests/php/test_filter_state_terms.php

namespace ZM;

if (!defined('STRF_FMT_DATETIME_DB')) define('STRF_FMT_DATETIME_DB', 'Y-m-d H:i:s');
function dbEscape($s) { return "'".addslashes($s)."'"; }
function Warning($s) { /* noop */ }
function Error($s)   { /* noop */ }
function Debug($s)   { /* noop */ }
function translate($s) { return $s; }

require_once __DIR__.'/../../web/includes/FilterTerm.php';

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

function sql_for($attr, $op, $val) {
  $term = new FilterTerm(null, array('attr'=>$attr, 'op'=>$op, 'val'=>$val), 0);
  return $term->sql();
}

// The five tinyint(3) unsigned state columns on Events.
$state_attrs = array('Videoed', 'Uploaded', 'Emailed', 'Messaged', 'Executed');

foreach ($state_attrs as $attr) {
  check("$attr is a valid filter attribute", FilterTerm::is_valid_attr($attr), true);

  $term = new FilterTerm(null, array('attr'=>$attr, 'op'=>'=', 'val'=>'1'), 0);
  check("$attr maps to E.$attr", $term->sql_attr(), "E.$attr");

  $sql = sql_for($attr, '=', '1');
  check("$attr = 1 produces SQL on E.$attr",
    preg_match('/E\.'.$attr.'\s*=\s*\'1\'/', $sql), 1);
  check("$attr = 0 produces SQL on E.$attr",
    preg_match('/E\.'.$attr.'\s*=\s*\'0\'/', sql_for($attr, '=', '0')), 1);

  // The filter editor's boolean value widget submits IS / IS NOT.
  check("$attr IS 1 produces SQL on E.$attr",
    preg_match('/E\.'.$attr.'\s*=\s*\'1\'/', sql_for($attr, 'IS', '1')), 1);
  check("$attr IS NOT 1 produces SQL on E.$attr",
    preg_match('/E\.'.$attr.'\s*!=\s*\'1\'/', sql_for($attr, 'IS NOT', '1')), 1);
}

// Archived is the reference behaviour the state flags follow.
$archived = new FilterTerm(null, array('attr'=>'Archived', 'op'=>'=', 'val'=>'1'), 0);
check('Archived still maps to E.Archived', $archived->sql_attr(), 'E.Archived');
check('Archived still produces SQL on E.Archived',
  preg_match('/E\.Archived\s*=\s*\'1\'/', $archived->sql()), 1);

check('an unknown attribute is still rejected', FilterTerm::is_valid_attr('NotAnEventAttribute'), false);

echo "\n$passes passed, $failures failed.\n";
exit($failures ? 1 : 0);
