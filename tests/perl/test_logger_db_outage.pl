#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "NOT OK - $name: got '".(defined $got ? $got : '<undef>')."' want '".(defined $want ? $want : '<undef>')."'\n";
  }
}

{
  package FakeSth;
  sub execute {
    my $self = shift;
    push(@main::inserted, [@_]);
    return 1;
  }
}
{
  package FakeDbh;
  sub ping { return $main::dbUp; }
  sub prepare_cached { return bless({}, 'FakeSth'); }
  sub errstr { return 'fake'; }
}

our $dbUp = 1;
our @inserted;
{
  no warnings 'redefine';
  *ZoneMinder::Database::zmDbConnect = sub {
    return unless $main::dbUp;
    $ZoneMinder::Database::dbh = bless({}, 'FakeDbh');
    return $ZoneMinder::Database::dbh;
  };
}

logInit(id=>'zm_test_dboutage', termLevel=>NOLOG, fileLevel=>NOLOG, syslogLevel=>NOLOG, databaseLevel=>NOLOG);
my $logger = ZoneMinder::Logger::fetch();
$logger->{databaseLevel} = INFO;
$logger->level(INFO);
$ZoneMinder::Database::dbh = bless({}, 'FakeDbh');

Info('before the outage');
check('a row is written while the database is up', scalar(@inserted), 1);

$dbUp = 0;
Info('during the outage');
check('nothing is written while the database is down', scalar(@inserted), 1);
check('the database level is kept', logDatabaseLevel(), INFO);

$dbUp = 1;
Info('after the outage');
check('logging to the database resumes', scalar(@inserted), 2);

logTerm();

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
