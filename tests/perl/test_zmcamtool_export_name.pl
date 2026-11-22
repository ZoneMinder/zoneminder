#!/usr/bin/perl
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

my $tool = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmcamtool.pl.in") or die "Can't open zmcamtool.pl.in: $!";
  local $/;
  <$fh>;
};

my ($branch) = $tool =~ /\} else \{\s*(\S[^\n]*Invalid characters in Name[^\n]*)/;
check('an invalid export name stops the export', $branch =~ /^die\b/ ? 'die' : $branch, 'die');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
