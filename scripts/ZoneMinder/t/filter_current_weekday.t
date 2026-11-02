use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Filter; 1 } or plan skip_all => "cannot load ZoneMinder::Filter: $@";
plan tests => 3;

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Filter::Debug = sub { };
  *ZoneMinder::Filter::Warning = sub { };
  *ZoneMinder::Filter::Error = sub { };
}

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
