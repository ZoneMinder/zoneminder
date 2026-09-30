# Per-Column ASC/DESC for Event Sort Specifications — Design

Date: 2026-06-08
Branch: `events-sort-expressions` (based on `master`)
Status: Approved design, pending implementation plan

## Problem

The events AJAX endpoint (`web/ajax/events.php`, `queryRequest()`) already parses a
sort spec as a comma-separated list of `<Column> [IS [NOT] NULL]` parts, but the
sort **direction** is a single global `$order` (ASC/DESC) applied to the whole
`ORDER BY`. There is no way to express per-column direction such as
`StartDateTime DESC, Id ASC`. We want the backend grammar + SQL builder to support
per-column direction. Scope is **backend only** — no frontend/UI changes; the
bootstrap-table events list keeps emitting single-column sorts as today.

## Goal

Extend the sort grammar to `<Column> [IS [NOT] NULL] [ASC|DESC]` per part and build
an `ORDER BY` where each part carries its own direction, while remaining fully
backward compatible with existing single-column UI sorts and saved filters.

## Non-goals

- No frontend changes (no bootstrap-table multipleSort extension).
- No saved-Filter persistence/editing of multi-column directional sorts.
- No consolidation of unrelated validation paths beyond what this feature needs.

## Key decisions (from brainstorming)

- **Scope:** backend grammar + SQL only.
- **Default direction:** a part without an explicit `ASC`/`DESC` inherits the
  request's global `$order`. So a bare `Id` behaves exactly as today.

## Architecture

### `Filter::buildSortSql($sort, $order, $resolve_column)` (new, static)

A pure, event-agnostic static helper on `Filter` that converts a sort spec into a
safe `ORDER BY` body string. It lives alongside the existing
`Filter::isValidSortExpression`; both use a shared grammar constant on `Filter`.

Note on testability: `Filter.php` only fails to load standalone because of an
include-ordering quirk — loading it first runs `config.php`'s `loadConfig()`
(which reads ZM's settings from the DB `Config` table) before `database.php`
reaches `dbConnect()`. Bootstrapping `config.php` **first** (which the web flow
does, and the test will do) loads `Filter` cleanly. The test therefore runs as an
integration test against the real `Filter` class (see Testing).

- **Parameters:**
  - `$sort` — raw spec string (may be empty).
  - `$order` — global default direction, `'ASC'` or `'DESC'`.
  - `$resolve_column` — `callable($col): ?string`. Returns the SQL for a validated
    column name (e.g. `'E.Id'`, `'M.Name'`, `'Tags'`) or `null` if the column is
    not permitted. This keeps event-specific whitelist/aliasing out of `Filter`.
- **Returns:** the `ORDER BY` body without the `ORDER BY` keyword (e.g.
  `M.Name ASC, E.Id DESC`), or `''` if `$sort` is empty or any part is invalid.
- **Algorithm:**
  1. If `$sort === ''` return `''`.
  2. Split on `,`. For each part, match the grammar regex (the shared constant,
     see below). On any match failure, return `''`.
  3. Extract `column`, optional `IS [NOT] NULL`, optional `ASC|DESC`.
  4. `$col_sql = $resolve_column($column)`; if `null`, return `''`.
  5. `$dir` = the explicit direction if present, else `$order`.
  6. Emit `$col_sql . (null-expr ? ' '.null-expr : '') . ' ' . $dir`.
  7. Join parts with `', '` and return.

### Shared grammar constant

The grammar regex lives in **one** place — a private const/static on `Filter`
(e.g. `Filter::SORT_PART_RE`) — used by both `buildSortSql` and
`isValidSortExpression`, so the two cannot drift:

```
/^\s*([A-Za-z][A-Za-z0-9_]*)(\s+IS\s+(?:NOT\s+)?NULL)?(\s+(?:ASC|DESC))?\s*$/i
```

`isValidSortExpression` continues to be the structural-only gate used by
`Filter::sort_field()`; it applies the same per-part regex (capturing groups
ignored) and returns true/false.

### `events.php` integration (`queryRequest`)

Replace the current inline parse/validate/alias block with:

1. Build the resolver closure from the existing `$columns` and `$col_alt`:
   ```php
   $whitelist = array_merge($columns, $col_alt);
   $resolve = function($col) use ($whitelist) {
     if (!in_array($col, $whitelist)) return null;
     if ($col == 'Tags') return 'Tags';
     if ($col == 'Monitor') return 'M.Name';
     return 'E.'.$col;
   };
   ```
2. Apply the implicit `EndDateTime` nulls-last pre-rewrite (preserves today's
   behavior) when `$sort` is exactly the bare string `EndDateTime`:
   - `$order == 'ASC'`  → `$sort = 'EndDateTime IS NULL ASC, EndDateTime ASC'`
   - else               → `$sort = 'EndDateTime IS NOT NULL ASC, EndDateTime DESC'`
   The explicit directions ensure the IS-NULL key keeps ASC ordering when the
   global order is DESC, exactly reproducing the existing SQL.
3. `$order_by = ZM\Filter::buildSortSql($sort, $order, $resolve);`
4. If `$order_by === ''` and `$sort !== ''`, `ZM\Warning('Invalid sort field,
   ignoring: '.$sort);`.
5. Splice: `... GROUP BY E.Id' . ($order_by ? ' ORDER BY '.$order_by : '')`. The
   previous single trailing `$order` append is removed.

## Behavior / compatibility

| Input `$sort` | `$order` | Resulting ORDER BY body |
|---|---|---|
| `Id` | DESC | `E.Id DESC` |
| `Monitor` | ASC | `M.Name ASC` |
| `Tags` | ASC | `Tags ASC` |
| `StartDateTime DESC, Id` | ASC | `E.StartDateTime DESC, E.Id ASC` |
| `StartDateTime DESC, Id ASC` | DESC | `E.StartDateTime DESC, E.Id ASC` |
| `EndDateTime` (bare) | ASC | `E.EndDateTime IS NULL ASC, E.EndDateTime ASC` |
| `EndDateTime` (bare) | DESC | `E.EndDateTime IS NOT NULL ASC, E.EndDateTime DESC` |
| `` (empty) | any | `` (no ORDER BY) |
| `Bogus` / not whitelisted | any | `` (warn, no ORDER BY) |
| `Id; DROP TABLE` | any | `` (warn, no ORDER BY) |

Existing single-column UI sorts (`sort=<field>&order=asc|desc`) are unchanged: a
bare column inherits the global order, producing the same SQL as today.

## Error handling

- Any part failing the grammar regex, or any column the resolver rejects, makes
  the whole spec invalid → `buildSortSql` returns `''` → caller warns and omits
  `ORDER BY`. This matches the current fail-safe (drop the sort rather than risk
  injecting a fragment).
- `buildSortSql` never interpolates unresolved/unvalidated text into SQL; only
  resolver-returned column SQL, the fixed `IS [NOT] NULL` token, and `ASC`/`DESC`
  reach the output.

## Testing

ZM has no PHP unit harness and `Filter` requires the DB/config stack to load, so
add a CLI **integration** test `tests/php/event_sort_test.php` that bootstraps the
framework from the repo includes (sets `include_path` to `web/includes`,
`require`s `config.php` then `Filter.php`) and is run as
`sudo -u www-data php tests/php/event_sort_test.php` (needs a reachable DB; ZM CI
runs no PHP tests, so this is a local/dev check, consistent with ZM's existing
"manual PHP testing"). It defines a trivial resolver and asserts:

- bare column inherits `$order` (both ASC and DESC);
- `ColA ASC, ColB DESC` → exact body, independent of `$order`;
- the bare-`EndDateTime` rewrite output for both orders (asserted in `events.php`
  via the pre-rewrite string + builder, exercised by feeding the rewritten spec to
  `buildSortSql`);
- `IS NULL` / `IS NOT NULL` parts round-trip with direction;
- whitelist rejection → `''`;
- injection attempt (`Id; DROP TABLE Events`) → `''`;
- `isValidSortExpression` accepts the new directional grammar and rejects garbage.

The test asserts the exact `ORDER BY` body for each case and exits non-zero on any
failure. It is the failing-test-first artifact and the regression guard. Manual
verification: load the events list in the browser and confirm column-header sorts
still work and produce expected ordering (esp. EndDateTime nulls placement).

## Files touched

- `web/includes/Filter.php` — add the shared grammar const and `buildSortSql`;
  update `isValidSortExpression` to use the const (and accept direction).
- `web/ajax/events.php` — replace inline sort block with resolver + pre-rewrite +
  `Filter::buildSortSql` call; remove trailing `$order` append.
- `tests/php/event_sort_test.php` — new CLI integration test.
