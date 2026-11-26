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
# A Medium scheme event that is not in the database
# Two Deep day dirs; the first can be entered but not read
make_path("$dir/storage/1/26/11/01", "$dir/storage/1/26/11/02/10/00/00");
symlink('10/00/00', "$dir/storage/1/26/11/02/.5") or die "symlink: $!";
chmod(0100, "$dir/storage/1/26/11/01");

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
sub AUTOLOAD { return 1; }
sub DESTROY { }
1;
EOM
write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub new { return bless({}, 'ZoneMinder::Event'); }
sub find_one { return 0; }
sub recover_timestamps { $_[0]{StartDateTime} = '2026-11-01 00:00:00'; }
sub save { print "event saved\n"; }
sub to_string { return 'event'; }
sub AUTOLOAD { return 1; }
sub DESTROY { }
1;
EOM
write_file("$dir/lib/ZoneMinder/Storage.pm", <<EOM);
package ZoneMinder::Storage;
sub find { return bless({Id => 1, Path => '$dir/storage'}, 'ZoneMinder::Storage'); }
sub Path { return \$_[0]{Path}; }
sub Id { return 1; }
1;
EOM

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

my $out = qx($^X -T -I$dir/lib $dir/zmrecover.pl --interactive=0 2>&1 </dev/null);
chmod(0700, "$dir/storage/1/26/11/01");
my $saved = () = $out =~ /event saved/g;
check('day dir after an unreadable one is still recovered', $saved, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
