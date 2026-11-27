#!/usr/bin/perl
#
# zmonvif-probe's POD listed seven options copied from zmaudit (-c, -f, -i, -m,
# -r, -s and -v as --version) that it does not accept, misspelt its own name in
# the SYNOPSIS, and its usage left out the "other" command it does handle.
#
# Run as: perl tests/perl/test_zmonvif_probe_help.pl [path/to/zmonvif-probe.pl.in]
#
use strict;
use warnings;
use FindBin;

my $file = $ARGV[0] || "$FindBin::Bin/../../scripts/zmonvif-probe.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my $failures = 0;
sub check {
  my ($name, $ok) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  $failures++ if !$ok;
}

my ($opts) = $src =~ /^my \$OPTIONS = '([^']*)';/m;
my ($pod_options) = $src =~ /^=head1 OPTIONS\n(.*?)^=cut/ms;
my ($synopsis) = $src =~ /^=head1 SYNOPSIS\n(.*?)^=head1/ms;
my ($help) = $src =~ /^sub HELP_MESSAGE \{(.*?)^\}/ms;

check('the getopts option string is found', defined $opts);
my %accepted = map { $_ => 1 } grep { $_ ne ':' } split //, ($opts // '');
# Getopt::Std's STANDARD_HELP_VERSION also handles --help and --version.
my @documented = ($pod_options // '') =~ /^\s*-(\w)\b/mg;
check('the POD lists options', scalar(@documented) > 0);
check('every short option the POD lists is accepted: '.join(',', @documented),
  !grep { !$accepted{$_} } @documented);
check('the POD lists no zmaudit options',
  ($pod_options // '') !~ /--(continuous|force|interactive|monitor_id|report|storage_id)/);
check('the SYNOPSIS names the script', ($synopsis // '') =~ /zmonvif-probe\.pl/ && ($synopsis // '') !~ /zmonfig/);
check('the SYNOPSIS has no stray code', ($synopsis // '') !~ /\\n";/);
check('the usage lists the other command, which the script handles',
  ($help // '') =~ /^\s*other\s+-/m && $src =~ /\$media->\$action\(/);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
