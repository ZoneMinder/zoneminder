#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my $path = "$FindBin::Bin/../../scripts/zmpkg.pl.in";
open(my $fh, '<', $path) or die "Cannot read $path: $!";
my $src = do { local $/; <$fh> };
close($fh);
$src =~ /^(sub legacyFunctionModes \{.*?^\})/ms or die "legacyFunctionModes not found in $path";
eval $1;
die $@ if $@;

my %want = (
  None    => 'None/None/None',
  Monitor => 'Always/None/None',
  Modect  => 'Always/Always/OnMotion',
  Mocord  => 'Always/Always/Always',
  Record  => 'Always/None/Always',
  Nodect  => 'Always/None/OnMotion',
);
foreach my $function (sort keys %want) {
  check($function, join('/', legacyFunctionModes($function)), $want{$function});
}
check('unknown function gives an empty list', scalar(my @none = legacyFunctionModes('Bogus')), 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
