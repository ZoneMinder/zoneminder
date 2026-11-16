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

write_file("$dir/lib/ZoneMinder/Config.pm", <<'EOM');
package ZoneMinder::Config;
use ZoneMinder::Base;
our %Config = (ZM_WEB_USER => 'root', ZM_DB_USER => 'u', ZM_DB_PASS => 'p');
sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
1;
EOM
write_file("$dir/lib/ZoneMinder/Base.pm", "package ZoneMinder::Base;\nuse constant ZM_VERSION => '0';\n1;\n");
write_file("$dir/lib/ZoneMinder/Logger.pm", <<'EOM');
package ZoneMinder::Logger;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit logSetSignal Debug Info Warning Error Fatal DEBUG NOLOG);
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Database.pm", <<'EOM');
package ZoneMinder::Database;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(zmDbConnect zmDbDisconnect zmDbDo);
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub find_one { print "looked up event $_[2]\n"; return; }
1;
EOM
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmeventtool.pl.in") or die "Can't open zmeventtool.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmeventtool.pl", $source);

sub run {
  my $out = qx($^X -T -I$dir/lib $dir/zmeventtool.pl @_ 2>&1);
  return ($? >> 8, $out);
}

my ($status, $out) = run('deletejpegs', '1');
check('an unsupported command is refused', $status != 0 ? 'refused' : 'accepted', 'refused');
check('the refusal names the command', $out =~ /deletejpegs/ ? 'named' : 'silent', 'named');

($status, $out) = run('close', '7');
check('a supported command still runs', $out =~ /looked up event 7/ ? 'ran' : 'skipped', 'ran');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
