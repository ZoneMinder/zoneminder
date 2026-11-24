#!/usr/bin/perl
#
# zmonvif-trigger's loadMonitors must not load soft-deleted monitors.
# A deleted monitor keeps Capturing and ONVIF_URL, so the query needs an
# explicit Deleted filter; without one the daemon subscribes to cameras the
# web interface has removed.
#
# The loadMonitors sub is extracted from the script and run against a mock
# DBI handle that emulates the database honouring (or ignoring) the WHERE
# clause, so the test fails if the filter is dropped.
#
# Run as: perl tests/perl/test_onvif_trigger_load_monitors.pl [file]
#
use strict;
use warnings;
use FindBin;

my $file = $ARGV[0] // "$FindBin::Bin/../../scripts/zmonvif-trigger.pl.in";
open(my $fh, '<', $file) or die "Can't open $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

$src =~ /^(  sub loadMonitors \{.*?^  \} # end foreach db monitor)/ms
  or die 'sub loadMonitors not found';
my $load_monitors_sub = $1;

my @rows = (
  { Id => 1, Deleted => 0, Capturing => 'Monitor', ONVIF_URL => 'http://a',
    ONVIF_Options => '', ONVIF_Username => '', ONVIF_Password => '' },
  { Id => 2, Deleted => 1, Capturing => 'Monitor', ONVIF_URL => 'http://b',
    ONVIF_Options => '', ONVIF_Username => '', ONVIF_Password => '' },
);

package _MockSTH;
sub new {
  my ($class, $dbh) = @_;
  return bless { dbh => $dbh, kept => [] }, $class;
}
sub execute {
  my ($self) = @_;
  my $sql = $self->{dbh}->{sql};
  my $honours = ($sql =~ /\bDeleted\s*=\s*0\b/
    || $sql =~ /\bDeleted\s*!=\s*1\b/
    || $sql =~ /\bNOT\s+Deleted\b/) ? 1 : 0;
  $self->{kept} = [ $honours
    ? grep { !$_->{Deleted} } @{ $self->{dbh}->{rows} }
    : @{ $self->{dbh}->{rows} } ];
  return 1;
}
sub fetchrow_hashref {
  my ($self) = @_;
  return shift @{ $self->{kept} };
}
sub errstr { return ''; }

package _MockDBH;
sub new {
  my ($class, $rows) = @_;
  return bless { rows => $rows, sql => '' }, $class;
}
sub prepare_cached {
  my ($self, $sql) = @_;
  $self->{sql} = $sql;
  return _MockSTH->new($self);
}
sub errstr { return ''; }

package main;

our $DBH = _MockDBH->new(\@rows);

{
  package _Consumer;

  my $monitor_reload_time;
  my $script;
  my $daemon_pid = 1;
  my $dbh = $main::DBH;

  sub Info {}
  sub Debug {}
  sub Fatal { die join('', @_) }
  sub zmMemVerify { return 1 }
  sub zmMemInvalidate {}
  sub set_monitors {
    my ($self, %m) = @_;
    $self->{m} = \%m;
  }

  eval $load_monitors_sub . "\n1;" or die $@;
}

{
  package ONVIF::Client;
  sub new { return bless {}, $_[0] }
  sub set_credentials {}
  sub create_services {}
}

package main;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name\n  got:  $got\n  want: $want\n";
  }
}

my $consumer = bless {}, '_Consumer';
$consumer->loadMonitors();

my $ids = join(',', sort { $a <=> $b } keys %{ $consumer->{m} || {} });
check('soft-deleted monitor is not loaded', $ids, '1');

my $sql = $DBH->{sql};
check('query still filters on Capturing', $sql =~ /Capturing/ ? 'yes' : 'no', 'yes');
check('query still filters on ONVIF_URL', $sql =~ /ONVIF_URL/ ? 'yes' : 'no', 'yes');

print "1.." . ($passes + $failures) . "\n";
print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
