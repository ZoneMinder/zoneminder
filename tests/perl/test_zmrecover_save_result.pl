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

my $scratch = '/tmp/hms-tests';
make_path($scratch) if ! -d $scratch;

my $src_path = $ENV{ZMRECOVER_SRC} || "$FindBin::Bin/../../scripts/zmrecover.pl.in";

# Scratch dirs are created under /tmp/hms-tests only
my $dir = tempdir(DIR => $scratch, CLEANUP => 1);
make_path("$dir/lib/ZoneMinder", "$dir/run");
# A Shallow scheme event: storage/<monitor>/<event id>
make_path("$dir/storage/1/7");
write_file("$dir/storage/1/7/00001-capture.jpg", '');

# The stub Event::save() returns whatever is in this file, so the test can
# make a save fail ('Duplicate entry') or succeed ('') without a database.
write_file("$dir/save_result", '');

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use ZoneMinder::Base;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { } for qw(logInit Debug Warning Fatal);
  *{$caller.'::Info'} = sub { print STDERR "Info: @_\n" };
  *{$caller.'::Error'} = sub { print STDERR "Error: @_\n" };
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
sub to_string { return 'event ' . (defined $_[0]{Id} ? $_[0]{Id} : ''); }
sub recover_timestamps { $_[0]{StartDateTime} = '2026-01-01 00:00:00'; }
sub save {
  my ($self) = @_;
  open(my $fh, '<', 'ZMR_SAVE_PATH') or return 'stub save result unavailable';
  local $/;
  my $result = <$fh>;
  close($fh);
  $result = '' if !defined $result;
  chomp($result);
  return $result;
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
  open(my $in, '<', $f) or die "Can't read $f: $!";
  local $/;
  my $c = <$in>;
  close($in);
  $c =~ s/ZMR_SAVE_PATH/$dir\/save_result/;
  write_file($f, $c);
}

my $source = do {
  open(my $fh, '<', $src_path) or die "Can't open $src_path: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_RUNDIR\@/$dir\/run/g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmrecover.pl", $source);

# Case 1: save() fails, so the recovery must be reported as a failure
write_file("$dir/save_result", "Duplicate entry\n");
my $out_fail = qx($^X -T -I$dir/lib $dir/zmrecover.pl --interactive=0 2>&1);
check('failed save reports an error', $out_fail =~ /Error: Failed to save recovered event 7: Duplicate entry/ ? 'yes' : 'no', 'yes');
check('failed save is not reported as resurrected', $out_fail =~ /Event resurrected/ ? 'yes' : 'no', 'no');

# Case 2: save() succeeds, so the existing behaviour is unchanged
write_file("$dir/save_result", "");
my $out_ok = qx($^X -T -I$dir/lib $dir/zmrecover.pl --interactive=0 2>&1);
check('successful save is reported as resurrected', $out_ok =~ /Info: Event resurrected as event 7/ ? 'yes' : 'no', 'yes');
check('successful save reports no save error', $out_ok =~ /Failed to save/ ? 'yes' : 'no', 'no');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
