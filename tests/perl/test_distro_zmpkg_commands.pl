#!/usr/bin/perl
#
# The service units, logrotate files and init scripts shipped under distros/
# and misc/ call zmpkg.pl with a command. zmpkg.pl only accepts the commands in
# its own check, and anything else is looked up as a run state name, so a unit
# that asks for one it does not have fails every time it is used.
#
# Run as: perl tests/perl/test_distro_zmpkg_commands.pl
#
use strict;
use warnings;
use FindBin;
use File::Find;

my $root = "$FindBin::Bin/../..";
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
  open(my $fh, '<', "$root/scripts/zmpkg.pl.in") or die "Can't open zmpkg.pl.in: $!";
  local $/;
  <$fh>;
};
my ($list) = $source =~ /\$command !~ \/\^\(\?:([a-z|]+)\)\$\//
  or die 'Could not find the accepted command list in zmpkg.pl.in';
my %accepted = map { $_ => 1 } split(/\|/, $list);

my @files;
find({ no_chdir => 1, wanted => sub {
  push @files, $File::Find::name
    if -f $_ and m{/[^/]*(?:\.service(?:\.in)?|logrotate[^/]*|\.init)$};
} }, "$root/distros", "$root/misc");

check('found unit, logrotate and init files', @files > 5 ? 'yes' : 'no', 'yes');

my $calls = 0;
foreach my $file (sort @files) {
  open(my $fh, '<', $file) or die "Can't open $file: $!";
  while (my $line = <$fh>) {
    next if $line =~ /^\s*#/;
    while ($line =~ /zmpkg\.pl\s+([A-Za-z-]+)/g) {
      my $command = $1;
      $calls++;
      (my $name = $file) =~ s/^\Q$root\E\///;
      check("$name:$. runs zmpkg.pl $command", $accepted{$command} ? 'accepted' : 'rejected', 'accepted');
    }
  }
  close($fh);
}
check('found zmpkg.pl calls', $calls > 5 ? 'yes' : 'no', 'yes');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
