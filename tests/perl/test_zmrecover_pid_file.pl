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

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use ZoneMinder::Base;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit Debug Info Warning Error);
  *{$caller.'::Fatal'} = sub { print STDERR "Fatal: $_[0]\n"; exit(1); };
  *{$caller.'::zmDbConnect'} = sub { 1 };
  *{$caller.'::ZM_PID'} = sub () { '/nonexistent/zm.pid' };
}
1;
EOM
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
write_file("$dir/lib/ZoneMinder/Base.pm", "package ZoneMinder::Base;\nuse constant ZM_VERSION => '0';\n1;\n");
write_file("$dir/lib/ZoneMinder/Monitor.pm", "package ZoneMinder::Monitor;\nsub find { die \"monitor lookup failed\\n\"; }\n1;\n");
write_file("$dir/lib/ZoneMinder/Event.pm", "package ZoneMinder::Event;\n1;\n");
write_file("$dir/lib/ZoneMinder/Storage.pm", "package ZoneMinder::Storage;\nsub find { return; }\n1;\n");

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

my $out = qx($^X -T -I$dir/lib $dir/zmrecover.pl 2>&1 </dev/null);
check('the run dies', $out =~ /monitor lookup failed/ ? 'died' : $out, 'died');
check('a run that died leaves no pid file', -e "$dir/run/zmrecover.pid" ? 'left behind' : 'removed', 'removed');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
