# Per-Column ASC/DESC Event Sort — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let event sort specs carry per-column direction (`StartDateTime DESC, Id ASC`), built safely into `ORDER BY`, with direction-less parts inheriting the request's global `$order`.

**Architecture:** Add a `buildSortSql` static method (and a shared grammar constant) to `ZM\Filter`, alongside the existing `isValidSortExpression`. `web/ajax/events.php` calls `Filter::buildSortSql` with a whitelist/alias resolver closure. No frontend changes, no new class.

**Tech Stack:** PHP. Test is a CLI **integration** test that bootstraps ZM's config/DB stack (run as `www-data`), because `Filter` extends the `ZM_Object` ORM base and loading it pulls in ZM's DB-stored config.

**Context:** Work happens on branch `events-sort-expressions` (already checked out, based on `master`). The branch currently has the prior "comma-separated sort parsing" work **uncommitted** in `web/ajax/events.php` and `web/includes/Filter.php`, plus the untracked design spec.

**Grammar (per comma-separated part):** `<Column> [IS [NOT] NULL] [ASC|DESC]`
Shared regex (used for both validation and parsing):
```
/^\s*([A-Za-z][A-Za-z0-9_]*)(\s+IS\s+(?:NOT\s+)?NULL)?(\s+(?:ASC|DESC))?\s*$/i
```

---

## File structure

- **Modify** `web/includes/Filter.php` — add `const SORT_PART_RE`; rewrite `isValidSortExpression` to use it (and accept direction); add static `buildSortSql($sort, $order, $resolve_column)`.
- **Modify** `web/ajax/events.php` — replace the inline parse/alias block in `queryRequest()` with a resolver closure + the `EndDateTime` nulls-last pre-rewrite + `Filter::buildSortSql()`; remove the trailing `$order` append in the `ORDER BY` splice.
- **Create** `tests/php/event_sort_test.php` — CLI integration test that bootstraps the repo's `config.php` then `Filter.php` and asserts `buildSortSql`/`isValidSortExpression` output.

---

## Task 1: Baseline commit (existing moved work + design docs)

**Files:** (commit only — no code changes)

- [ ] **Step 1: Confirm branch and the uncommitted baseline**

Run: `cd /home/iconnor/sandbox/ZoneMinder && git branch --show-current && git status --short`
Expected: branch `events-sort-expressions`; `web/ajax/events.php` and `web/includes/Filter.php` modified (` M`); the design spec/plan under `docs/superpowers/` untracked.

- [ ] **Step 2: Lint the baseline files**

Run: `php -l web/ajax/events.php && php -l web/includes/Filter.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 3: Commit the baseline feature**

```bash
cd /home/iconnor/sandbox/ZoneMinder
git add web/ajax/events.php web/includes/Filter.php
git commit -m "feat: parse comma-separated event sort expressions with NULLs-last idiom"
```

- [ ] **Step 4: Commit the design docs**

```bash
cd /home/iconnor/sandbox/ZoneMinder
git add docs/superpowers/specs/2026-06-08-per-column-event-sort-design.md docs/superpowers/plans/2026-06-08-per-column-event-sort.md
git commit -m "docs: add per-column event sort design and plan"
```

---

## Task 2: Add grammar const + `buildSortSql` to `Filter` (TDD)

**Files:**
- Create: `tests/php/event_sort_test.php`
- Modify: `web/includes/Filter.php`

- [ ] **Step 1: Write the failing test**

Create `tests/php/event_sort_test.php`:
```php
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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/iconnor/sandbox/ZoneMinder && sudo -u www-data php tests/php/event_sort_test.php 2>&1 | grep -v 'INIT block'`
Expected: FAIL — the `buildSortSql` cases error/fail because `ZM\Filter::buildSortSql` does not exist yet (PHP: `Call to undefined method ZM\Filter::buildSortSql()`). A benign `Warning, overriding installed zm.conf ...` line and `REMOTE_ADDR` notice may appear — ignore them.

- [ ] **Step 3: Add the grammar const and `buildSortSql`; update `isValidSortExpression`**

In `web/includes/Filter.php`, replace the existing `isValidSortExpression` method:
```php
  public static function isValidSortExpression($sf) {
    if ($sf === '' || $sf === null) return true;
    if (!is_string($sf)) return false;
    foreach (explode(',', $sf) as $part) {
      if (!preg_match('/^\s*[A-Za-z][A-Za-z0-9_]*(?:\s+IS\s+(?:NOT\s+)?NULL)?\s*$/i', $part)) {
        return false;
      }
    }
    return true;
  }
```
with this (a shared const + the updated validator + the new builder):
```php
  // Single source of truth for the sort grammar, shared by validation and
  // building. Each comma-separated part: <Column> [IS [NOT] NULL] [ASC|DESC].
  const SORT_PART_RE = '/^\s*([A-Za-z][A-Za-z0-9_]*)(\s+IS\s+(?:NOT\s+)?NULL)?(\s+(?:ASC|DESC))?\s*$/i';

  public static function isValidSortExpression($sf) {
    if ($sf === '' || $sf === null) return true;
    if (!is_string($sf)) return false;
    foreach (explode(',', $sf) as $part) {
      if (!preg_match(self::SORT_PART_RE, $part)) return false;
    }
    return true;
  }

  // Build a safe ORDER BY body (without the "ORDER BY" keyword) from a sort
  // spec. $order is the global default direction ('ASC'|'DESC') used for parts
  // that omit an explicit ASC/DESC. $resolve_column is callable($col): ?string
  // returning the column's SQL (e.g. 'E.Id', 'M.Name') or null if not allowed.
  // Returns '' if the spec is empty or any part is invalid/not allowed.
  public static function buildSortSql($sort, $order, $resolve_column) {
    if ($sort === '' || $sort === null) return '';
    $out = array();
    foreach (explode(',', $sort) as $part) {
      if (!preg_match(self::SORT_PART_RE, $part, $m)) return '';
      $col_sql = call_user_func($resolve_column, $m[1]);
      if ($col_sql === null) return '';
      $null_expr = '';
      if (isset($m[2]) && trim($m[2]) !== '') {
        $null_expr = (stripos($m[2], 'NOT') !== false) ? ' IS NOT NULL' : ' IS NULL';
      }
      $dir = (isset($m[3]) && trim($m[3]) !== '') ? strtoupper(trim($m[3])) : $order;
      $out[] = $col_sql.$null_expr.' '.$dir;
    }
    return implode(', ', $out);
  }
```
(Leave the existing explanatory comment block above `isValidSortExpression` in place.)

- [ ] **Step 4: Run the test to verify it passes**

Run: `cd /home/iconnor/sandbox/ZoneMinder && sudo -u www-data php tests/php/event_sort_test.php 2>&1 | grep -v 'INIT block'`
Expected: every assertion `[PASS] ...` and final `ALL PASS` (exit 0). If any `[FAIL]`, fix `buildSortSql` until all pass.

- [ ] **Step 5: Lint and commit**

```bash
cd /home/iconnor/sandbox/ZoneMinder
php -l web/includes/Filter.php
git add web/includes/Filter.php tests/php/event_sort_test.php
git commit -m "feat: add Filter::buildSortSql for directional event sort specs with integration test"
```

---

## Task 3: Rewire `web/ajax/events.php` to use `Filter::buildSortSql`

**Files:**
- Modify: `web/ajax/events.php`

- [ ] **Step 1: Replace the inline sort-parsing block**

In `web/ajax/events.php`, inside `queryRequest()`, find this exact block:
```php
  if ( $sort != '' ) {
    // Parse as a comma-separated list of <Column> [IS [NOT] NULL] parts so
    // multi-expression sorts (e.g. the NULLs-last idiom) round-trip cleanly
    // instead of being rejected wholesale. Each part's column name still has
    // to be in the whitelist; once accepted we prefix with the appropriate
    // alias before splicing into ORDER BY.
    $whitelist = array_merge($columns, $col_alt);
    $sql_parts = array();
    $valid = ZM\Filter::isValidSortExpression($sort);
    if ($valid) {
      foreach (explode(',', $sort) as $part) {
        if (!preg_match('/^\s*([A-Za-z][A-Za-z0-9_]*)(\s+IS\s+(?:NOT\s+)?NULL)?\s*$/i', $part, $m)) {
          $valid = false;
          break;
        }
        $col = $m[1];
        if (!in_array($col, $whitelist)) {
          $valid = false;
          break;
        }
        if ($col == 'Tags') {
          $col_sql = 'Tags';
        } else if ($col == 'Monitor') {
          $col_sql = 'M.Name';
        } else {
          $col_sql = 'E.'.$col;
        }
        $sql_parts[] = $col_sql.(isset($m[2]) ? $m[2] : '');
      }
    }
    if (!$valid) {
      ZM\Warning('Invalid sort field, ignoring: '.$sort);
      $sort = '';
    } else if (count($sql_parts) == 1 && $sql_parts[0] == 'E.EndDateTime') {
      // Implicit NULLs-last rewrite when sorting only by EndDateTime, so
      // events without a recorded end (zmc crashed) don't bunch unpredictably.
      $sort = ($order == 'ASC')
        ? 'E.EndDateTime IS NULL, E.EndDateTime'
        : 'E.EndDateTime IS NOT NULL, E.EndDateTime';
    } else {
      $sort = implode(', ', $sql_parts);
    }
  }
```
Replace the entire block with:
```php
  if ( $sort != '' ) {
    // Resolve a whitelisted event column name to its SQL (alias), or null.
    $whitelist = array_merge($columns, $col_alt);
    $resolve = function($col) use ($whitelist) {
      if (!in_array($col, $whitelist)) return null;
      if ($col == 'Tags') return 'Tags';
      if ($col == 'Monitor') return 'M.Name';
      return 'E.'.$col;
    };
    // Implicit NULLs-last rewrite when sorting solely by EndDateTime, so events
    // without a recorded end (zmc crashed) don't bunch unpredictably. Emitted
    // with explicit directions so the IS NULL key keeps ASC ordering even when
    // the global order is DESC, reproducing the historical SQL.
    if (trim($sort) == 'EndDateTime') {
      $sort = ($order == 'ASC')
        ? 'EndDateTime IS NULL ASC, EndDateTime ASC'
        : 'EndDateTime IS NOT NULL ASC, EndDateTime DESC';
    }
    // Build the per-part directional ORDER BY body. Parts without an explicit
    // ASC/DESC inherit $order. An invalid/non-whitelisted spec yields '' and is
    // dropped (no ORDER BY) rather than risking an injected fragment.
    $sort = ZM\Filter::buildSortSql($sort, $order, $resolve);
    if ($sort === '') {
      ZM\Warning('Invalid sort field, ignoring');
    }
  }
```

- [ ] **Step 2: Fix the ORDER BY splice (direction now lives per-part)**

In the same file, find:
```php
    WHERE '.$search_filter->sql().'
    GROUP BY E.Id'
    .($sort ? ' ORDER BY '.$sort.' '.$order : '');
```
Replace with:
```php
    WHERE '.$search_filter->sql().'
    GROUP BY E.Id'
    .($sort ? ' ORDER BY '.$sort : '');
```

- [ ] **Step 3: Lint**

Run: `cd /home/iconnor/sandbox/ZoneMinder && php -l web/ajax/events.php`
Expected: `No syntax errors detected in web/ajax/events.php`.

- [ ] **Step 4: Commit**

```bash
cd /home/iconnor/sandbox/ZoneMinder
git add web/ajax/events.php
git commit -m "feat: build per-column directional ORDER BY for event list via Filter::buildSortSql"
```

---

## Task 4: Manual browser verification

**Files:** none (verification)

- [ ] **Step 1: Deploy the changed PHP to the running install**

The live site serves from `/usr/share/zoneminder/www` (real copies, not symlinks). Deploy the two changed files:
```bash
cd /home/iconnor/sandbox/ZoneMinder
sudo install -m 644 web/includes/Filter.php /usr/share/zoneminder/www/includes/Filter.php
sudo install -m 644 web/ajax/events.php      /usr/share/zoneminder/www/ajax/events.php
```

- [ ] **Step 2: Verify in the browser (pseudo.connortechnology.com, already logged in)**

Open the Events list. Confirm:
- Clicking the **Id** column header sorts ascending/descending (toggles) — unchanged single-column behavior.
- Clicking the **Monitor** column sorts by monitor name (alias works).
- Clicking the **End** (EndDateTime) column: events with no end time group at the expected end (nulls-last on ASC) and the toggle still works.

- [ ] **Step 3: Confirm the generated SQL via the debug log**

`queryRequest` logs the SQL at Debug. With debug logging on, trigger a sort and check:
```bash
sudo grep -E "matching search filter|ORDER BY" /var/log/zm/web_php.log 2>/dev/null | tail -3
```
Expected: an `ORDER BY` clause with per-part directions (e.g. `ORDER BY E.Id DESC`), and for an EndDateTime sort, `ORDER BY E.EndDateTime IS NULL ASC, E.EndDateTime ASC` (ASC) — no trailing bare `$order`, no SQL errors.

- [ ] **Step 4: Re-run the integration test as a final regression check**

Run: `cd /home/iconnor/sandbox/ZoneMinder && sudo -u www-data php tests/php/event_sort_test.php 2>&1 | grep -v 'INIT block'`
Expected: `ALL PASS`.

---

## Self-review notes (spec coverage)

- Grammar/builder (`buildSortSql`) + shared regex const: Task 2 (`Filter`). Direction resolution (explicit-or-global default): Task 2 `buildSortSql` + tests. `EndDateTime` nulls-last pre-rewrite: Task 3. Validation grammar update (shared const, accepts direction): Task 2. `events.php` integration + splice fix: Task 3. Testing: Task 2 (integration) + Task 4 (manual/browser). Whitelist/alias resolver: Task 3. Injection rejection: Task 2 tests.
- Name consistency: `Filter::SORT_PART_RE`, `Filter::isValidSortExpression`, `Filter::buildSortSql($sort,$order,$resolve_column)`, and the `$resolve` closure are used consistently across Tasks 2–3 and the test.
- Test loads the **repo** `Filter.php` (via `include_path` = `web/includes`), so it exercises the modified code, not the deployed copy. It needs a reachable DB and `www-data` (consistent with ZM's manual-PHP-testing reality; ZM CI runs no PHP tests).
