#!/usr/bin/perl
# zmpkg.pl restart (and a state change, which ends in restart) must exit 0 when
# the system was stopped and is then started; only a plain stop of a system that
# is not running is a failure. Structural test on the template.
use strict;
use warnings;
use FindBin;

my $path = "$FindBin::Bin/../../scripts/zmpkg.pl.in";
open(my $fh, '<', $path) or die "Cannot read $path: $!";
my $src = do { local $/; <$fh> };
close($fh);

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

$src =~ /(if \( \$command =~ \/\^\(\?:stop\|restart\)\$\/ \) \{.*?\n\}\n)/s or die 'stop block not found';
my $block = $1;
check('stop block only fails for a plain stop', $block =~ /\$retval\s*=\s*1\s+if\s+\$command\s+eq\s+'stop'/
  || $block =~ /if\s*\(\s*\$command\s+eq\s+'stop'\s*\)\s*\{\s*\$retval\s*=\s*1/);
print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
