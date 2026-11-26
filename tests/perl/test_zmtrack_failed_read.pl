#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
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

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder");

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use strict;
use warnings;

our %Config = (ZM_PATH_BIN => '/bin');
our $iter = 0;

sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::Config'} = \%Config;
  *{$caller.'::logInit'} = sub { 1 };
  *{$caller.'::logSetSignal'} = sub { 1 };
  *{$caller.'::logDebugging'} = sub { 0 };
  *{$caller.'::Debug'} = sub { 1 };
  *{$caller.'::Info'} = sub { 1 };
  *{$caller.'::Warning'} = sub { 1 };
  *{$caller.'::Fatal'} = sub { die "FATAL: $_[0]\n"; };
  *{$caller.'::zmDbConnect'} = sub {
    return bless { sth => bless({}, 'MockSTH') }, 'MockDBH';
  };
  *{$caller.'::zmMemVerify'} = sub { 1 };
  *{$caller.'::zmMonitorSuspend'} = sub { 1 };
  *{$caller.'::zmMonitorResume'} = sub { 1 };
  *{$caller.'::zmIsAlarmed'} = sub {
    exit(0) if ++$iter > 1;
    return 1;
  };
  # A failed shared memory read yields a single undef, as zmMemRead does.
  *{$caller.'::zmGetAlarmLocation'} = sub {
    return $ENV{TEST_MODE} ? (undef) : (10, 20);
  };
  *{$caller.'::executeShellCommand'} = sub { print "CMD: $_[0]\n"; 1 };
}

package MockDBH;
sub prepare_cached { return $_[0]->{sth}; }

package MockSTH;
sub execute { 1 }
sub fetchrow_hashref {
  return {
    Id => 1, Controllable => 1, TrackMotion => 1, CanMoveMap => 1,
    TrackDelay => 0, ReturnLocation => -1, Orientation => 'ROTATE_0',
    Width => 640, Height => 480,
  };
}
1;
EOM

write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtrack.pl.in") or die "Can't open zmtrack.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmtrack.pl", $source);

sub run {
  local $ENV{TEST_MODE} = shift;
  return qx($^X -T -I$dir/lib $dir/zmtrack.pl --monitor=1 2>&1);
}

my $out = run(0);
check('valid alarm location issues a moveMap',
      $out =~ m{CMD: /bin/zmcontrol.pl -i 1 --command=moveMap --xcoord=10 --ycoord=20} ? 'moved' : 'none', 'moved');

$out = run(1);
check('failed alarm location read does not move the camera',
      $out =~ /CMD:/ ? "moved: $out" : 'none', 'none');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
