<?php
// Exports hold event footage, and the export path keeps its directories to the
// owning user: ZM_DIR_EXPORTS and the per-export directory are both chmod 0700,
// as are all three directories in download_functions.php. The per-event
// directory was created with a bare mkdir(), so its mode came from the umask
// instead, leaving it 0755 under the usual 0022. The footage was world readable
// and only the 0700 parent above it was keeping anyone out. Issue #5074.
//
// Note on the umask: parent permissions do not propagate to a child directory,
// only a POSIX default ACL would do that and ZM sets none, so the umask alone
// decides the mode.
//
// The source checks below read the file with token_get_all() rather than
// matching text. Regex versions of these passed on the very regressions they
// name: one matched any chmod near a mkdir regardless of which directory it
// applied to, one skipped any mkdir that carried a mode argument, and one never
// looked at the mode at all, so reverting 0700 to 0755 went unnoticed.
// Run: php tests/php/test_export_dir_permissions.php   (from the repo root)

$root = __DIR__.'/../..';
$failures = 0;
function check($label, $ok, $detail = '') {
  global $failures;
  if (!$ok) $failures++;
  printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, ($ok || $detail === '') ? '' : "\n        $detail");
}

// ---- source model -------------------------------------------------------
// Significant tokens only, so positions are stable against comments and layout.
function zm_tokens($src) {
  $out = array();
  foreach (token_get_all($src) as $t) {
    if (is_array($t)) {
      if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
      $out[] = array('text' => $t[1], 'line' => $t[2], 'id' => $t[0]);
    } else {
      $out[] = array('text' => $t, 'line' => null, 'id' => null);
    }
  }
  // carry the last known line onto punctuation
  $line = 0;
  foreach ($out as $i => $t) {
    if ($t['line'] !== null) $line = $t['line'];
    else $out[$i]['line'] = $line;
  }
  return $out;
}

// Every call to $name, with its arguments split at top level.
function zm_calls($tokens, $name) {
  $calls = array();
  for ($i = 0; $i < count($tokens); $i++) {
    if ($tokens[$i]['id'] !== T_STRING || $tokens[$i]['text'] !== $name) continue;
    if (!isset($tokens[$i + 1]) || $tokens[$i + 1]['text'] !== '(') continue;
    // a method call is not what we are looking for
    if ($i > 0 && in_array($tokens[$i - 1]['text'], array('->', '::'), true)) continue;
    $depth = 0;
    $args = array();
    $cur = '';
    for ($j = $i + 1; $j < count($tokens); $j++) {
      $tx = $tokens[$j]['text'];
      if ($tx === '(') {
        $depth++;
        if ($depth === 1) continue;
      } elseif ($tx === ')') {
        $depth--;
        if ($depth === 0) { $args[] = trim($cur); break; }
      } elseif ($tx === ',' && $depth === 1) {
        $args[] = trim($cur);
        $cur = '';
        continue;
      }
      $cur .= $tx;
    }
    $calls[] = array('line' => $tokens[$i]['line'], 'args' => $args);
  }
  return $calls;
}

// Line range of the block guarded by if ($exportStructure != 'flat').
function zm_non_flat_block($tokens) {
  for ($i = 0; $i < count($tokens); $i++) {
    if ($tokens[$i]['id'] !== T_IF) continue;
    // scan the condition
    $depth = 0; $sawVar = false; $sawNe = false; $sawFlat = false; $j = $i + 1;
    for (; $j < count($tokens); $j++) {
      $tx = $tokens[$j]['text'];
      if ($tx === '(') { $depth++; continue; }
      if ($tx === ')') { $depth--; if ($depth === 0) { $j++; break; } continue; }
      if ($tokens[$j]['id'] === T_VARIABLE && $tx === '$exportStructure') $sawVar = true;
      if ($tokens[$j]['id'] === T_IS_NOT_EQUAL || $tokens[$j]['id'] === T_IS_NOT_IDENTICAL) $sawNe = true;
      if ($tokens[$j]['id'] === T_CONSTANT_ENCAPSED_STRING && trim($tx, "'\"") === 'flat') $sawFlat = true;
    }
    if (!($sawVar && $sawNe && $sawFlat)) continue;
    if (!isset($tokens[$j]) || $tokens[$j]['text'] !== '{') continue;
    $start = $tokens[$j]['line'];
    $bd = 0;
    for ($k = $j; $k < count($tokens); $k++) {
      if ($tokens[$k]['text'] === '{') $bd++;
      elseif ($tokens[$k]['text'] === '}') {
        $bd--;
        if ($bd === 0) return array($start, $tokens[$k]['line']);
      }
    }
  }
  return null;
}

// ---- what a bare mkdir actually leaves behind ---------------------------
$base = sys_get_temp_dir().'/zm_export_perm_'.getmypid();
@mkdir($base);
$old_umask = umask(0022);
$bare = $base.'/bare';
@mkdir($bare);
$bare_mode = fileperms($bare) & 0777;
check('a bare mkdir under the usual umask is world readable',
  $bare_mode === 0755, sprintf('got mode %04o, want 0755', $bare_mode));
// This half exercises PHP's own chmod, not ZoneMinder: exportEvents() needs a
// database, config constants and exec(), so it cannot be called from here. It
// documents the intended mode, it is not behavioural coverage of the export.
$fixed = $base.'/fixed';
@mkdir($fixed);
chmod($fixed, 0700);
$fixed_mode = fileperms($fixed) & 0777;
check('an explicit chmod keeps it to the owner',
  $fixed_mode === 0700, sprintf('got mode %04o, want 0700', $fixed_mode));
umask($old_umask);
@rmdir($bare); @rmdir($fixed); @rmdir($base);

// ---- source guards ------------------------------------------------------
$path = $root.'/web/skins/classic/includes/export_functions.php';
$src = file_get_contents($path);
$tokens = zm_tokens($src);
$mkdirs = zm_calls($tokens, 'mkdir');
$chmods = zm_calls($tokens, 'chmod');

check('the export path still creates directories', count($mkdirs) > 0);

// Pair by the actual first argument, whatever form the mkdir takes. An
// unreadable argument counts as unpaired rather than being skipped.
$unpaired = array();
$wrong_mode = array();
foreach ($mkdirs as $mk) {
  $target = isset($mk['args'][0]) ? $mk['args'][0] : '';
  if ($target === '') { $unpaired[] = $mk['line'].': (unreadable argument)'; continue; }
  $paired = null;
  foreach ($chmods as $ch) {
    if (isset($ch['args'][0]) && $ch['args'][0] === $target) { $paired = $ch; break; }
  }
  if ($paired === null) { $unpaired[] = $mk['line'].': '.$target; continue; }
  $mode = isset($paired['args'][1]) ? $paired['args'][1] : '';
  if ($mode !== '0700') $wrong_mode[] = $paired['line'].': '.$target.' set to '.($mode === '' ? '(none)' : $mode);
}
check('every mkdir in the export path chmods that same directory',
  count($unpaired) === 0, 'unpaired: '.implode('; ', $unpaired));
check('and sets it to 0700, not something the world can read',
  count($wrong_mode) === 0, implode('; ', $wrong_mode));

// The per-event directory belongs to the non-flat structure. A flat export
// copies into $export_dir directly and never touches it, so creating it there,
// and skipping the event when that fails, would drop events that export fine.
$block = zm_non_flat_block($tokens);
check('the export still branches on a non-flat structure', $block !== null);
if ($block !== null) {
  $inside = false;
  foreach ($mkdirs as $mk) {
    if (isset($mk['args'][0]) && $mk['args'][0] === '$event_dir'
        && $mk['line'] > $block[0] && $mk['line'] < $block[1]) {
      $inside = true;
    }
  }
  check('the per-event directory is only created inside that branch', $inside,
    sprintf('non-flat block spans lines %d-%d; mkdir($event_dir) at %s',
      $block[0], $block[1],
      implode(',', array_map(function($m) { return $m['line']; },
        array_filter($mkdirs, function($m) { return ($m['args'][0] ?? '') === '$event_dir'; })))));
}

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
