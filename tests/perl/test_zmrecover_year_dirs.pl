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
# A Deep scheme event: the 26 year directory is numeric like a Shallow scheme event id
make_path("$dir/storage/1/26/11/01/10/00/00");
write_file("$dir/storage/1/26/11/01/10/00/00/5-video.mp4", '');

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
1;
EOM
write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub new { return bless({}, 'ZoneMinder::Event'); }
sub find_one { return 0; }
1;
EOM
write_file("$dir/lib/ZoneMinder/Storage.pm", <<EOM);
package ZoneMinder::Storage;
sub find { return bless({Id => 1, Path => '$dir/storage'}, 'ZoneMinder::Storage'); }
sub Path { return \$_[0]{Path}; }
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

my $out = qx(printf "n\\nn\\nn\\n" | $^X -T -I$dir/lib $dir/zmrecover.pl 2>&1);
my $prompts = () = $out =~ /resurrect/g;
check('only the Deep event is offered for recovery', $prompts, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
