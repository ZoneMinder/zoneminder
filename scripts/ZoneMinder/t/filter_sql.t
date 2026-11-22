use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Filter; 1 } or plan skip_all => "cannot load ZoneMinder::Filter: $@";
plan tests => 37;

{
  no warnings 'redefine', 'once';
  # sql_quote asks the database handle to quote; there is none here.
  *ZoneMinder::Filter::sql_quote = sub {
    my $v = shift;
    return q(NULL) if !defined $v;
    $v =~ s/'/''/g;
    return "'$v'";
  };
  *ZoneMinder::Filter::Debug = sub { };
  *ZoneMinder::Filter::Warning = sub { };
  *ZoneMinder::Filter::Error = sub { };
}

sub filter_sql {
  my (%attrs) = @_;
  my %query = (
    terms => exists $attrs{terms} ? $attrs{terms} : [{ attr => 'Archived', op => '=', val => '0' }],
    limit => exists $attrs{limit} ? $attrs{limit} : '100',
    skip_locked => $attrs{skip_locked} ? 1 : 0,
  );
  $query{sort_field} = $attrs{sort_field} if exists $attrs{sort_field};
  $query{sort_asc} = $attrs{sort_asc} ? 1 : 0 if exists $attrs{sort_asc};

  my $Filter = bless {
    Id => 1,
    Name => 'test',
    %attrs,
    Query_json => ZoneMinder::General::jsonEncode(\%query),
    LockRows => $attrs{LockRows} ? 1 : 0,
  }, 'ZoneMinder::Filter';
  return $Filter->Sql(undef);
}

my $LOCK_CLAUSE = qr/NOT EXISTS \(SELECT 1 FROM Events_Lock/;

# The batch lock is gone: locking the whole result set held every row lock the
# per-event work went on to take until the run committed.
for my $case (
  ['plain',                  {}],
  ['LockRows',               {LockRows => 1}],
  ['LockRows + skip_locked', {LockRows => 1, skip_locked => 1}],
) {
  my ($name, $attrs) = @$case;
  unlike(filter_sql(%$attrs), qr/FOR UPDATE|SKIP LOCKED/i, "$name: no FOR UPDATE / SKIP LOCKED");
}

# skip_locked leaves out events another filter is working on, so that a LIMIT
# fills with events this filter can actually act on.
like(filter_sql(LockRows => 1, skip_locked => 1), $LOCK_CLAUSE,
  'LockRows with skip_locked excludes events other filters hold');
like(filter_sql(LockRows => 1, skip_locked => 1), qr/EL\.ExpiresAt>NOW\(\)/,
  'and only while their lock is unexpired');

unlike(filter_sql(LockRows => 1), $LOCK_CLAUSE,
  'without skip_locked the events are still returned, just claimed one by one');
unlike(filter_sql(skip_locked => 1), $LOCK_CLAUSE,
  'skip_locked alone does nothing: nothing is taking locks to skip');
unlike(filter_sql(), $LOCK_CLAUSE, 'a plain filter is unaffected');

# The exclusion has to be part of the WHERE clause, not tacked on the end.
my $sql = filter_sql(LockRows => 1, skip_locked => 1);
cmp_ok(index($sql, 'NOT EXISTS'), '<', index($sql, 'LIMIT'),
  'the exclusion goes in the WHERE clause, before ORDER BY / LIMIT');

# --- DateTime window terms -------------------------------------------------
# "Overlaps this window" is a lower bound on when the event ended and an upper
# bound on when it started. The lower bound has three parts, each carrying its
# weight: a StartDateTime floor, an EndDateTime conjunct that is logically
# redundant but indexable, and the effective end that gets the semantics right.
sub datetime_sql {
  my ($op, $val) = @_;
  my $Filter = bless {
    Id => 1, Name => 'test', LockRows => 0,
    Query_json => sprintf('{"terms":[{"attr":"DateTime","op":"%s","val":"%s"}]}', $op, $val),
  }, 'ZoneMinder::Filter';
  return $Filter->Sql(undef);
}

my $lower = datetime_sql('>=', '2026-08-17 18:57:38');
unlike($lower, qr/9999/, 'no stand-in date for "never ends"');
unlike($lower, qr/COALESCE\s*\(\s*E\.EndDateTime\s*,/i,
  'EndDateTime is not coalesced with a sentinel, which no index could range over');
like($lower, qr/E\.StartDateTime >= DATE_SUB\('2026-08-17 18:57:38', INTERVAL 1 DAY\)/,
  'a floor on StartDateTime bounds the scan, and drops events abandoned long ago');
like($lower, qr/\(E\.EndDateTime IS NULL OR E\.EndDateTime >= '2026-08-17 18:57:38'\)/,
  'the bare EndDateTime conjunct gives the optimiser a second indexable handle');
like($lower, qr/WHEN E\.Length > 0 THEN DATE_ADD\(E\.StartDateTime, INTERVAL FLOOR\(E\.Length\) SECOND\)/,
  'a missing end falls back to StartDateTime + Length, as the SELECT list does');

my $upper = datetime_sql('<=', '2026-08-17 19:57:38');
like($upper, qr/E\.StartDateTime <= '2026-08-17 19:57:38'/,
  'an upper bound compares against StartDateTime');
unlike($upper, qr/EndDateTime/, 'and leaves EndDateTime out of it entirely');

# --- CurrentWeekday --------------------------------------------------------
# A term on the current weekday compares weekday(NOW()), not an Events column.
# Monday is 0 and has to be kept as a value. refs #5418
sub term_sql {
  my ($attr, $op, $val) = @_;
  my $Filter = bless {
    Id => 1, Name => 'test', LockRows => 0,
    Query_json => sprintf('{"terms":[{"attr":"%s","op":"%s","val":"%s"}]}', $attr, $op, $val),
  }, 'ZoneMinder::Filter';
  return $Filter->Sql(undef);
}

my $sunday = term_sql('CurrentWeekday', '=', '6');
like($sunday, qr/weekday\(NOW\(\)\)\s*=\s*6/, 'CurrentWeekday compares weekday(NOW())');
unlike($sunday, qr/E\.CurrentWeekday/, 'and not a CurrentWeekday column, which Events does not have');
like(term_sql('CurrentWeekday', '=', '0'), qr/weekday\(NOW\(\)\)\s*=\s*0/, 'Monday (0) is kept as a value');

# --- MonitorName sorting ---------------------------------------------------
# Sorting by MonitorName must join Monitors and sort by M.Name without dropping
# skip_locked or adding duplicate joins.
my $mon_sort_locked = filter_sql(LockRows => 1, skip_locked => 1, sort_field => 'MonitorName', sort_asc => 1);
like($mon_sort_locked, qr/M\.Name as MonitorName/, 'MonitorName sort adds MonitorName to fields');
like($mon_sort_locked, qr/INNER JOIN Monitors as M on M\.Id = E\.MonitorId/, 'MonitorName sort joins Monitors table');
like($mon_sort_locked, $LOCK_CLAUSE, 'MonitorName sort preserves skip_locked clause');
like($mon_sort_locked, qr/ORDER BY M\.Name ASC/, 'MonitorName sort orders by M.Name ASC');

my $mon_sort_desc = filter_sql(sort_field => 'MonitorName', sort_asc => 0);
like($mon_sort_desc, qr/ORDER BY M\.Name DESC/, 'MonitorName sort orders by M.Name DESC');

my $mon_dup_join = filter_sql(
  terms => [{ attr => 'MonitorName', op => '=', val => 'Front' }],
  sort_field => 'MonitorName',
);
my @joins = ($mon_dup_join =~ /INNER JOIN Monitors as M/g);
is(scalar(@joins), 1, 'Monitors table is only joined once when already in query');

my $mon_no_where = filter_sql(terms => [], sort_field => 'MonitorName');
unlike($mon_no_where, qr/\bWHERE\b/, 'MonitorName sort with no terms has no WHERE clause');
unlike($mon_no_where, qr/\bAND\b/, 'MonitorName sort with no terms has no dangling AND clause');

# --- Odd / Even parity -----------------------------------------------------
# IS / IS NOT parity operators map to modulo expressions.
like(filter_sql(terms => [{ attr => 'Id', op => 'IS', val => 'Odd' }]),
  qr/E\.Id % 2 = 1/, 'IS Odd maps to % 2 = 1');
like(filter_sql(terms => [{ attr => 'Id', op => 'IS', val => 'Even' }]),
  qr/E\.Id % 2 = 0/, 'IS Even maps to % 2 = 0');
like(filter_sql(terms => [{ attr => 'Id', op => 'IS NOT', val => 'Odd' }]),
  qr/E\.Id % 2 = 0/, 'IS NOT Odd maps to % 2 = 0');
unlike(filter_sql(terms => [{ attr => 'Id', op => 'IS NOT', val => 'Odd' }]),
  qr/!= 'Odd'/, 'IS NOT Odd does not fall through to !=');
like(filter_sql(terms => [{ attr => 'Id', op => 'IS NOT', val => 'Even' }]),
  qr/E\.Id % 2 = 1/, 'IS NOT Even maps to % 2 = 1');
unlike(filter_sql(terms => [{ attr => 'Id', op => 'IS NOT', val => 'Even' }]),
  qr/!= 'Even'/, 'IS NOT Even does not fall through to !=');

# --- NULL comparisons ------------------------------------------------------
# An empty StorageId value represents NULL; = and != must become IS / IS NOT.
my $storage_null = filter_sql(terms => [{ attr => 'StorageId', op => '=', val => '' }]);
like($storage_null, qr/E\.StorageId IS NULL/, "StorageId = '' produces IS NULL");
unlike($storage_null, qr/=\s*NULL/, "StorageId = '' does not produce = NULL");

my $storage_not_null = filter_sql(terms => [{ attr => 'StorageId', op => '!=', val => '' }]);
like($storage_not_null, qr/E\.StorageId IS NOT NULL/, "StorageId != '' produces IS NOT NULL");
unlike($storage_not_null, qr/!=\s*NULL/, "StorageId != '' does not produce != NULL");
