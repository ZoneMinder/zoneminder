#!/usr/bin/perl
#
# checkFilter's "returned N events" summary is only useful to someone who
# pressed Execute if it reaches the log at Info; a background daemon pass must
# keep it at Debug so it does not fill the log on every interval.
#
# The sub is extracted from the template and evaluated with stub filters and
# logger, as the other tests/perl/test_zmfilter*.pl tests do.
#
# Run as: perl tests/perl/test_zmfilter_execute_summary.pl
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print "  want: $want\n";
  }
}

my $file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

package FakeFilter;
sub new { bless { Name => 'Test filter', AutoEmail => 0, EmailFormat => 'Individual' }, shift }
sub Execute { return (); }

package main;
our %Config = (ZM_OPT_EMAIL => 0);
our $zm_terminate = 0;
our $dbh;
our @info;
our @debug;

sub Info { push @info, join('', map { defined $_ ? $_ : '' } @_) }
sub Debug { push @debug, join('', map { defined $_ ? $_ : '' } @_) }
sub Warning {}
sub Error {}
sub Fatal { die @_ }
sub sendSummaryEmail {}
sub checkFilterEvent {}
sub zmDbConnect { return $dbh }

my ($sub) = $src =~ /^(sub checkFilter \{.*?^\}[^\n]*\n)/ms;
die "sub checkFilter not found in $file\n" if !$sub;
eval "$sub; 1" or die "Can't compile checkFilter: $@";

my ($on_demand) = $src =~ /^(my \$on_demand = .*?;)/ms;
check('a one-pass run without --daemon is on demand',
  (defined $on_demand and $on_demand =~ /\$daemon/ and $on_demand =~ /\$filter_id/) ? 1 : 0, 1);
check('a concurrent filter child is marked background',
  $src =~ /local \$ENV\{ZM_FILTER_BACKGROUND\} = 1/ ? 1 : 0, 1);
check('the on-demand check excludes a background child',
  (defined $on_demand and $on_demand =~ /\$ENV\{ZM_FILTER_BACKGROUND\}/) ? 1 : 0, 1);
check('the filter loop tells checkFilter whether the run is on demand',
  $src =~ /checkFilter\(\$filter, \$on_demand\)/ ? 1 : 0, 1);

sub run_check {
  my ($on_demand) = @_;
  @info = ();
  @debug = ();
  checkFilter(FakeFilter->new(), $on_demand);
  return scalar grep { /returned 0 events/ } @info;
}

check('on demand (--filter_id without --daemon) logs the summary at Info', run_check(1), 1);
check('a daemon pass does not log the summary at Info', run_check(0), 0);
check('a daemon pass still logs the summary at Debug',
  scalar(grep { /returned 0 events/ } @debug), 1);

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
