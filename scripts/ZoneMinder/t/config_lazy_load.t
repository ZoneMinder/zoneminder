use strict;
use warnings;
use Test::More;

# ZoneMinder::Config loads ZoneMinder::ConfigData, the definition and help text
# of every option, only in the functions that work on the Config table, rather
# than in every script that loads the config.

eval { require ZoneMinder::Config; 1 } or plan skip_all => "cannot load ZoneMinder::Config: $@";
plan tests => 9;

ok(!$INC{'ZoneMinder/ConfigData.pm'}, 'loading the config does not load ConfigData');

# Records what would go to the database.
my (@executed, @done);
{
  package FakeSth;
  sub new { my ($class, $rows) = @_; bless { rows => [ @{$rows || []} ] }, $class }
  sub execute { my $self = shift; push @executed, [ @_ ]; 1 }
  sub fetchrow_hashref { shift @{$_[0]{rows}} }
  sub finish { 1 }
  package FakeDbh;
  sub new { bless { AutoCommit => 1, rows => $_[1] }, $_[0] }
  sub prepare_cached { FakeSth->new($_[0]{rows}) }
  sub selectall_hashref {
    return { ZM_AUTH_HASH_SECRET => { Name => 'ZM_AUTH_HASH_SECRET', Private => 0, System => 0 } };
  }
  sub do { push @done, $_[1]; 1 }
  sub errstr { '' }
}
my $rows = [
  { Name => 'ZM_OPT_USE_AUTH', Value => '' },
  { Name => 'ZM_LANG_DEFAULT', Value => 'de_de' },
];
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Database::zmDbConnect = sub { FakeDbh->new($rows) };
}

local *STDOUT;
open(STDOUT, '>', '/dev/null');

is(ZoneMinder::Config::loadConfigFromDB(), 2, 'loadConfigFromDB reads the rows');
ok($INC{'ZoneMinder/ConfigData.pm'}, 'and loads ConfigData');
is($ZoneMinder::ConfigData::options_hash{ZM_OPT_USE_AUTH}{value}, '0', 'an empty boolean is read as 0');
is($ZoneMinder::ConfigData::options_hash{ZM_LANG_DEFAULT}{value}, 'de_de', 'a string is read as is');
ok(defined $ZoneMinder::ConfigData::options[0]{id}, 'the options were initialised');

@executed = ();
is(ZoneMinder::Config::syncConfigFlags(), 1, 'syncConfigFlags updates the flags that differ');

@executed = ();
ZoneMinder::Config::saveConfigToDB();
is(scalar @executed, scalar @ZoneMinder::ConfigData::options, 'saveConfigToDB writes every option');
my ($auth) = grep { $_->[1] eq 'ZM_OPT_USE_AUTH' } @executed;
is($auth->[2], '0', 'and writes the boolean through dbValue');
