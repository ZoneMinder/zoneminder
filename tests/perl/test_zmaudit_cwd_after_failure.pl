#!/usr/bin/perl
#
# zmaudit reads each monitor directory relative to the storage path. A
# directory it can enter but not read must not leave it in that directory,
# or the monitors after it are not audited.
#
# Run as: perl tests/perl/test_zmaudit_cwd_after_failure.pl
#
use strict;
use warnings;
use FindBin;
use Cwd qw(getcwd);
use File::Temp qw(tempdir);
use File::Path qw(make_path);

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
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmaudit.pl.in") or die "Can't open zmaudit.pl.in: $!";
  local $/;
  <$fh>;
};
my ($loop) = $source =~ /^(    foreach my \$monitor \( glob\('\[0-9\]\*'\) \) \{\n.*?^    \} # end foreach monitor\n)/ms;
die 'Could not extract the monitor loop from zmaudit.pl.in' unless $loop;

{
  package ZoneMinder::Event;
  sub new { return bless({}, 'ZoneMinder::Event'); }
  sub find_one { return undef; }
  sub AUTOLOAD { return 1; }
  sub DESTROY { }
}
{
  package FakeStorage;
  sub Path { return $_[0]{Path}; }
  sub Id { return 1; }
}

our ($monitor_id, $report, $cleaned, %Config) = (0, 1, 0);
sub Debug { }
sub Error { }
sub Warning { }
sub aud_print { }
sub confirm { return 0; }
sub delete_empty_subdirs { }

# Monitor 1 has an unreadable directory, monitor 2 a Shallow event 5.
sub audit {
  my ($unreadable) = @_;
  my $dir = tempdir(CLEANUP => 1);
  make_path("$dir/1/26/11/01", "$dir/2/5");
  chmod(0100, "$dir/$unreadable");
  my $Storage = bless({Path => $dir}, 'FakeStorage');
  my $fs_monitors;
  my $cwd = getcwd();
  chdir($dir) or die "chdir $dir: $!";
  eval $loop;
  my $error = $@;
  chdir($cwd);
  chmod(0700, "$dir/$unreadable");
  die $error if $error;
  return join(',', sort keys %{$fs_monitors->{2} // {}});
}

check('monitor after an unreadable day directory is audited', audit('1/26/11/01'), '5');
check('monitor after an unreadable monitor directory is audited', audit('1'), '5');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
