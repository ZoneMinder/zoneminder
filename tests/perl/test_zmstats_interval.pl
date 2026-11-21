#!/usr/bin/perl
# zmstats.pl must never sleep for less than a second between passes, whatever
# ZM_STATS_UPDATE_INTERVAL holds (0 is allowed by the integer option type).
# Set ZMSTATS_SOURCE to test another copy of zmstats.pl.in.
use strict;
use warnings;
use FindBin;
use Scalar::Util qw(looks_like_number);

my $path = $ENV{ZMSTATS_SOURCE} // "$FindBin::Bin/../../scripts/zmstats.pl.in";
open(my $fh, '<', $path) or die "Can't open $path: $!";
my $source = do { local $/; <$fh> };
close($fh);

my ($pass, $fail) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $pass++; print "ok - $name\n"; }
  else { $fail++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

our %Config;

# Every sleep() call in the main loop, with the sub that computes its argument.
my @calls = $source =~ /^\s*sleep\(([^;]*)\);/mg;
@calls = grep { $_ !~ /START_DELAY/ } @calls;
my ($sub) = $source =~ /^(sub statsInterval \{.*?^\})/ms;
my $code = defined $sub ? $sub : '';
if ($code) { eval "$code; 1" or die $@; }

for my $value (0, -3, '', 'abc', undef, 15) {
  my $label = defined $value ? "'$value'" : 'undef';
  $Config{ZM_STATS_UPDATE_INTERVAL} = $value;
  my @got;
  for my $expr (@calls) {
    my $v = $expr eq '$Config{ZM_STATS_UPDATE_INTERVAL}'
      ? $Config{ZM_STATS_UPDATE_INTERVAL}
      : eval $expr;
    push @got, (defined $v and looks_like_number($v)) ? $v : 0;
  }
  my $want = (defined $value and looks_like_number($value) and $value > 0) ? $value : 60;
  check("interval $label gives $want between passes", join(',', @got), join(',', ($want) x scalar(@calls)));
}
check('two sleeps found in the loop', scalar(@calls), 2);

print "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
