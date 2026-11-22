#!/usr/bin/perl
# zmfilter.pl.in must log the exit code, not the raw wait status ($? holds
# exit<<8 | signal), in its Debug lines. Structural test on the template.
use strict;
use warnings;
use FindBin;

my $file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

my @bad = grep { /Debug\(.*\$status\b/ and !/>>\s*8/ } split /\n/, $src;
check('no Debug line prints the raw wait status', !@bad);
check('command Debug line prints exit code', $src =~ /Debug\("Status: .*\(\$status >> 8\)/ || $src =~ /Debug\([^\n]*\$status >> 8/);
check('command Debug line mentions the signal', $src =~ /Debug\([^\n]*\$status & 127/);
print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
