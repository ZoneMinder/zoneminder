use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Filter; 1 } or plan skip_all => "cannot load ZoneMinder::Filter: $@";
plan tests => 16;

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Filter::Debug = sub { };
  *ZoneMinder::Filter::Warning = sub { };
  *ZoneMinder::Filter::Error = sub { };
}

my $QUERY = '{"terms":[{"attr":"Archived","op":"=","val":"0"}],"limit":"100","skip_locked":%s}';

sub filter_sql {
  my (%attrs) = @_;
  my $Filter = bless {
    Id => 1,
    Name => 'test',
    Query_json => sprintf($QUERY, $attrs{skip_locked} ? 1 : 0),
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
