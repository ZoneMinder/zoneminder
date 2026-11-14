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

write_file("$dir/lib/ZoneMinder/Base.pm", "package ZoneMinder::Base;\nuse constant ZM_VERSION => '0';\n1;\n");
write_file("$dir/lib/ZoneMinder/Config.pm", <<'EOM');
package ZoneMinder::Config;
use ZoneMinder::Base;
our %Config = (ZM_WEB_USER => 'root', ZM_DB_USER => 'zmuser', ZM_DB_PASS => 'zmpass',
  ZM_DB_HOST => 'localhost', ZM_DB_NAME => 'zm', ZM_PATH_DATA => '/nonexistent');
sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
1;
EOM
write_file("$dir/lib/ZoneMinder/Logger.pm", <<'EOM');
package ZoneMinder::Logger;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit logSetSignal DEBUG NOLOG);
  *{$caller.'::logDebugging'} = sub { 0 };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/General.pm", <<'EOM');
package ZoneMinder::General;
sub import {
  no strict 'refs';
  *{caller().'::findDbCommand'} = sub { 'echo' };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Database.pm", <<'EOM');
package ZoneMinder::Database;
use ZoneMinder::Config;
sub import {
  no strict 'refs';
  *{caller().'::zmDbConnect'} = sub {
    print "connect force=".($_[0] ? 1 : 0)." user=$ZoneMinder::Config::Config{ZM_DB_USER}\n";
    return bless {}, 'FakeDbh';
  };
}
package FakeDbh;
sub prepare_cached { return; }
1;
EOM
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmcamtool.pl.in") or die "Can't open zmcamtool.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmcamtool.pl", $source);

sub run {
  open(my $ph, '-|', "$^X -T -I$dir/lib $dir/zmcamtool.pl ".join(' ', map { "'".s/'/'\\''/gr."'" } @_)." 2>&1")
    or die "Can't run zmcamtool.pl: $!";
  local $/;
  my $out = <$ph>;
  close($ph);
  return $out;
}

my $out = run('--import', '/nonexistent', '--user', 'alt', '--pass', 'secret');
check('credentials from the command line are used to connect', $out =~ /connect force=1 user=alt/ ? 'reconnected' : $out, 'reconnected');

$out = run('--import', '/nonexistent');
check('the configured credentials are not reconnected', $out =~ /connect force=1/ ? 'reconnected' : 'kept', 'kept');

$out = run('--export', '--user', 'alt', '--pass', "a'b");
check('--export accepts credentials from the command line', $out =~ /Insecure dependency/ ? 'insecure' : 'accepted', 'accepted');
check('a quote in the password reaches the command intact', index($out, "-pa'b zm") >= 0 ? 'intact' : $out, 'intact');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
