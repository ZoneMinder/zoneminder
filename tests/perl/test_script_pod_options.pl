#!/usr/bin/perl
#
# Every long option a script's GetOptions accepts is listed in its POD
# (SYNOPSIS or OPTIONS), which is what --help and perldoc show, so an
# administrator can find it. zmupdate's --rename and --zone-fix, zmtelemetry's
# --verbose and --dry_run and others were accepted but not documented.
#
# Run as: perl tests/perl/test_script_pod_options.pl [scripts dir]
#
use strict;
use warnings;
use FindBin;

my $dir = $ARGV[0] || "$FindBin::Bin/../../scripts";
my $failures = 0;
my $checked = 0;

for my $file (sort glob("$dir/*.pl.in")) {
  open(my $fh, '<', $file) or die "Can't read $file: $!";
  my $src = do { local $/; <$fh> };
  close($fh);
  my ($spec) = $src =~ /GetOptions\s*\((.*?)\)\s*(?:or\b|\)|;|\|\|)/s;
  next if !$spec;
  my ($pod) = $src =~ /^(=head1 SYNOPSIS.*?)^=cut/ms;
  $pod //= '';
  (my $name = $file) =~ s{.*/}{};
  # Each entry is 'name|alias=type' => ... or a bareword name => ...
  while ($spec =~ /(?:^|[,(\s])['"]?([A-Za-z][\w-]*(?:\|[\w-]+)*)(?:[=:][^'"\s]*|[!+])?['"]?\s*=>/g) {
    my @names = split /\|/, $1;
    my @long = grep { length($_) > 1 } @names;
    next if !@long;
    $checked++;
    my $found = grep { $pod =~ /(?<![\w-])--?\Q$_\E(?![\w-])/ } @long;
    if (!$found) {
      $failures++;
      print "FAIL - $name: --$long[0] is accepted but not in its POD\n";
    }
  }
  if ($pod =~ /--verison/) {
    $failures++;
    print "FAIL - $name: POD misspells --version\n";
  }
}

print "checked $checked options\n";
print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
