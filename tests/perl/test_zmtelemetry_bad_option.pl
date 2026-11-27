#!/usr/bin/perl
#
# zmtelemetry.pl ignored the result of GetOptions, so a mistyped option such as
# --shwo only printed "Unknown option" and the script went on to run as the
# upload daemon. Every other script shows the usage and stops.
#
# Run as: perl tests/perl/test_zmtelemetry_bad_option.pl
#
use strict;
use warnings;
use FindBin;
use Getopt::Long;

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

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtelemetry.pl.in")
    or die "Can't open zmtelemetry.pl.in: $!";
  local $/;
  <$fh>;
};

my ($block) = $source =~ /^(my \$help = 0;.*?^GetOptions\(.*?^\)[^\n]*;)/ms;
die "Could not extract the option parsing from zmtelemetry.pl.in" unless $block;

sub parse {
  my @args = @_;
  my $usage = 0;
  my %seen;
  local @ARGV = @args;
  no warnings qw(redefine once);
  local *main::pod2usage = sub { $usage = 1 };
  my $code = "no strict; no warnings; $block; \%seen = (force => \$force, show => \$show);";
  local $SIG{__WARN__} = sub {};
  eval $code;
  die $@ if $@;
  return ($usage, \%seen);
}

my ($usage, $seen) = parse('--shwo');
check('an unknown option shows the usage', $usage, 1);

($usage, $seen) = parse('--force');
check('a known option does not show the usage', $usage, 0);
check('a known option is still parsed', $seen->{force}, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
