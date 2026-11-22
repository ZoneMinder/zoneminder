#!/usr/bin/perl
#
# ZM_LOG_DATABASE_LIMIT: a number less than or equal to zero, with or without a
# unit such as "0 day" or "-1 day", means no limit.
# zmstats.pl and zmaudit.pl must leave the Logs table alone for any such value,
# with or without surrounding spaces, prune by row count for a value of digits
# only and by interval otherwise, and build no invalid statement and print no
# warning for the values below, including a missing setting.
# ZM_LOG_AUDIT_DATABASE_LIMIT follows the same rules.
#
# Runs the Logs and audit pruning blocks of each script against a stub
# database handle and records the statements they issue.
#
# Run as: perl tests/perl/test_log_database_limit.pl
#
use strict;
use warnings;
use FindBin;
use Scalar::Util qw(looks_like_number);

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

# logLimitInterval() lives in ZoneMinder::General, shared by both scripts.
{
  my $module = "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/General.pm";
  open(my $mfh, '<', $module) or die "Can't read $module: $!";
  my $msrc = do { local $/; <$mfh> };
  close($mfh);
  my ($sub) = $msrc =~ /^(sub logLimitInterval \{.*?\n\})$/ms or die "no logLimitInterval in $module";
  eval $sub; die $@ if $@;
}

foreach my $script (qw(zmstats.pl.in zmaudit.pl.in)) {
  my $file = "$FindBin::Bin/../../scripts/$script";
  open(my $fh, '<', $file) or die "Can't read $file: $!";
  my $src = do { local $/; <$fh> };
  close($fh);
  my ($block) = $src =~ /^(\s*# Prune the Logs table if required.*?\} # end if ZM_LOG_AUDIT_DATABASE_LIMIT\n)/ms;
  check("$script: found the Logs pruning block", defined($block) ? 1 : 0, 1);
  next if !defined $block;
  my $prune = eval "sub { $block }" or die "Can't compile the block of $script: $@";

  # prunes: 0 = none, 'count' = by row count, 'time' = by interval
  foreach my $case (
    [ 'ZM_LOG_DATABASE_LIMIT', '0',       0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-1',      0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-100',    0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '00',      0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '0.0',     0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-1.5',    0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', ' -5',     0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-5 ',     0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '1000',    'count' ],
    [ 'ZM_LOG_DATABASE_LIMIT', ' 1000',   'count' ],
    [ 'ZM_LOG_DATABASE_LIMIT', '7 day',   'time' ],
    [ 'ZM_LOG_DATABASE_LIMIT', '7 days',  'time' ],
    [ 'ZM_LOG_DATABASE_LIMIT', '0 day',   0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-1 day',  0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', '-7 days', 0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', ' 0 hour ', 0 ],
    [ 'ZM_LOG_DATABASE_LIMIT', undef,     0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '',       0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '1000',   'count' ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', ' 1000',  'count' ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '1000 ',  'count' ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '1 year', 'time' ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '0 year', 0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '-1 day', 0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '0',      0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', ' 0',     0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '0 ',     0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '00',     0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', '-1',     0 ],
    [ 'ZM_LOG_AUDIT_DATABASE_LIMIT', undef,    0 ],
  ) {
    my ($name, $limit, $prunes) = @$case;
    %Config = (ZM_LOG_DATABASE_LIMIT => '0', ZM_LOG_AUDIT_DATABASE_LIMIT => '', $name => $limit);
    delete $Config{$name} if !defined $limit;
    my $shown = defined $limit ? "'$limit'" : 'missing';
    @sql = ();
    my @warnings;
    {
      local $SIG{__WARN__} = sub { push @warnings, @_ };
      eval { $prune->(); 1 } or push @sql, "died: $@";
    }
    check("$script: $name $shown prints no warning", join('', @warnings), '');
    my @deletes = grep { /DELETE/ } @sql;
    my @bad = grep { (/interval/i and !/interval \d+ [a-z]+\)/i) or /^died/ } @sql;
    my $kind = !@deletes ? 0 : (grep { /interval/i } @deletes) ? 'time' : 'count';
    check("$script: $name $shown issues no invalid statement", join(' | ', @bad), '');
    check("$script: $name $shown ".($prunes ? "prunes by $prunes" : 'leaves the Logs table alone'),
      $kind, $prunes);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
