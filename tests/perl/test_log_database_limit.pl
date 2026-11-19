#!/usr/bin/perl
#
# ZM_LOG_DATABASE_LIMIT: a number less than or equal to zero means no limit.
# zmstats.pl and zmaudit.pl took a negative number for a time interval and ran
# "DELETE ... now() - interval -1", which MySQL rejects: zmstats logged an
# error every pass and zmaudit died on it.
#
# Runs the Logs pruning block of each script against a stub database handle
# and records the statements it issues.
#
# Run as: perl tests/perl/test_log_database_limit.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

package ZoneMinder::Logger;
use constant AUDIT => 5;

package StubSth;
sub execute { 1 }
sub fetchrow_hashref { { Rows => 5000 } }
sub finish { 1 }
sub rows { 0 }
sub errstr { '' }

package StubDbh;
sub prepare_cached { my ($self, $sql) = @_; push @main::sql, $sql; return bless {}, 'StubSth'; }
sub errstr { '' }

package main;

our @sql;
our %Config;
our $dbh = bless {}, 'StubDbh';
our $res;
our $zm_terminate = 0;
sub Debug {} sub aud_print {}
sub Fatal { die "Fatal: @_\n" }
sub zmDbDo { push @sql, shift; return 0; }

foreach my $script (qw(zmstats.pl.in zmaudit.pl.in)) {
  my $file = "$FindBin::Bin/../../scripts/$script";
  open(my $fh, '<', $file) or die "Can't read $file: $!";
  my $src = do { local $/; <$fh> };
  close($fh);
  my ($block) = $src =~ /^(\s*if \( \$Config\{ZM_LOG_DATABASE_LIMIT\} .*?\} # end if ZM_LOG_DATABASE_LIMIT\n)/ms;
  check("$script: found the Logs pruning block", defined($block) ? 1 : 0, 1);
  next if !defined $block;
  my $prune = eval "sub { $block }" or die "Can't compile the block of $script: $@";

  foreach my $case (
    [ '0',       0 ],
    [ '-1',      0 ],
    [ '-100',    0 ],
    [ '1000',    1 ],
    [ '7 day',   1 ],
  ) {
    my ($limit, $prunes) = @$case;
    %Config = (ZM_LOG_DATABASE_LIMIT => $limit);
    @sql = ();
    eval { $prune->(); 1 } or push @sql, "died: $@";
    my @deletes = grep { /DELETE/ } @sql;
    my @bad = grep { /interval\s+-/i or /^died/ } @sql;
    check("$script: limit '$limit' issues no invalid statement", join(' | ', @bad), '');
    check("$script: limit '$limit' ".($prunes ? 'prunes' : 'leaves the Logs table alone'),
      (@deletes ? 1 : 0), $prunes);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
