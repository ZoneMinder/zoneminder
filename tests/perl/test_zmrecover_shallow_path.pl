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
make_path("$dir/lib/ZoneMinder", "$dir/run");
# A Shallow scheme event: storage/<monitor>/<event id>
make_path("$dir/storage/1/7");
write_file("$dir/storage/1/7/00001-capture.jpg", '');

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use ZoneMinder::Base;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit Debug Info Warning Error Fatal);
  *{$caller.'::zmDbConnect'} = sub { 1 };
  *{$caller.'::ZM_PID'} = sub () { '/nonexistent/zm.pid' };
}
1;
EOM
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
write_file("$dir/lib/ZoneMinder/Base.pm", "package ZoneMinder::Base;\nuse constant ZM_VERSION => '0';\n1;\n");
write_file("$dir/lib/ZoneMinder/Monitor.pm", <<'EOM');
package ZoneMinder::Monitor;
sub find { return bless({Id => 1}, 'ZoneMinder::Monitor'); }
sub to_string { return 'monitor'; }
sub Width { 1 } sub Height { 1 } sub Orientation { 1 }
1;
EOM
write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub new { return bless({}, 'ZoneMinder::Event'); }
sub find_one { return 0; }
sub MonitorId { $_[0]{MonitorId} = $_[1] if @_ > 1; $_[0]{MonitorId} }
sub StorageId { $_[0]{StorageId} = $_[1] if @_ > 1; $_[0]{StorageId} }
sub Width { 1 } sub Height { 1 } sub Orientation { 1 } sub DiskSpace { 1 }
sub to_string { 'event' }
sub save { 1 }
sub recover_timestamps {
  my $e = shift;
  # like the real one: nothing can be derived when the directory is missing
  open(my $fh, '>>', 'ZMR_OUT_PATH') or die;
  print $fh "path=$$e{Path} exists=" . (-d $$e{Path} ? 1 : 0) . "\n";
  close $fh;
  $$e{StartDateTime} = '2026-01-01 00:00:00' if -d $$e{Path};
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Storage.pm", <<EOM);
package ZoneMinder::Storage;
sub find { return bless({Id => 1, Path => '$dir/storage'}, 'ZoneMinder::Storage'); }
sub Path { return \$_[0]{Path}; }
sub Id { return 1; }
1;
EOM

{
  my $f = "$dir/lib/ZoneMinder/Event.pm";
  open(my $in, '<', $f) or die; local $/; my $c = <$in>; close $in;
  $c =~ s/ZMR_OUT_PATH/$dir\/out/;
  write_file($f, $c);
}

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmrecover.pl.in") or die "Can't open zmrecover.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_RUNDIR\@/$dir\/run/g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmrecover.pl", $source);

my $out = qx($^X -T -I$dir/lib $dir/zmrecover.pl --interactive=0 2>&1);
my $rec = do { local $/; open(my $fh, '<', "$dir/out") or die "no recover_timestamps call: $out"; <$fh> };
check('Shallow event path includes the monitor directory', $rec, "path=$dir/storage/1/7 exists=1\n");

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
