use strict;
use warnings;
use Test::More;
use FindBin;
use File::Spec;

# The script needs a database, so this is a source-level regression test:
# the continuous main loop must sleep exactly once per pass, at its top.
# zmaudit once also slept again at the bottom of the loop, so passes ran
# every 2x ZM_AUDIT_CHECK_INTERVAL.

my $src = $ENV{ZMAUDIT_SRC};
if (!defined $src) {
  for my $candidate (
    'scripts/zmaudit.pl.in',
    File::Spec->catfile($FindBin::Bin, '..', '..', '..', 'scripts', 'zmaudit.pl.in'),
  ) {
    if (-f $candidate) {
      $src = $candidate;
      last;
    }
  }
  $src //= 'scripts/zmaudit.pl.in';
}

plan tests => 3;

open(my $fh, '<', $src) or die "$src: $!";
my $source = do { local $/; <$fh> };
close($fh);

# The main loop ends at the }; whose next statement is Term();
my ($loop) = $source =~ /(^MAIN: while\( \$loop \) \{\n.*?^\};)\s*^Term\(\);/ms;
ok($loop, 'found the zmaudit main loop');

# The reconnect retry deep in the loop is allowed to wait an interval
# between db reconnect attempts; it is not part of the per-pass cadence,
# so exclude that block before counting.
(my $per_pass = $loop // '') =~ s/^  while \( ! \( \$dbh and \$dbh->ping\(\) \) \) \{\n.*?^  \} # end while can't connect to the db\n//ms;

my $sleeps = () = $per_pass =~ /sleep\(\$Config\{ZM_AUDIT_CHECK_INTERVAL\}\)/g;
is($sleeps, 1, 'main loop sleeps exactly once per pass at the check interval');

my $first_sleep = index($loop // '', 'sleep($Config{ZM_AUDIT_CHECK_INTERVAL})');
my $first_redo  = index($loop // '', 'redo MAIN');
ok($first_sleep >= 0 && ($first_redo == -1 || $first_sleep < $first_redo),
   'the per-pass sleep is at the top of the loop, before any redo MAIN');
