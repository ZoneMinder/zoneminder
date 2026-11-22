#!/usr/bin/perl
# Runs zmupdate.pl.in --freshen against stubbed ZoneMinder modules and a fake DBI
# handle, so no database or mysql binary is touched. Checks that a failed
# saveConfigToDB aborts the freshen instead of exiting 0, and that the MyISAM
# prompt values a closed stdin instead of treating it as "no".
#
# Set ZMUPDATE_SRC to test a different copy of the script.
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $scratch = '/tmp/hms-tests';
make_path($scratch);
my $dir = tempdir('hms-zmupdate-freshen-XXXXXX', DIR => $scratch, CLEANUP => 1);

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

sub read_file {
  my ($path) = @_;
  open(my $fh, '<', $path) or return '';
  local $/;
  my $c = <$fh>;
  close($fh);
  return $c;
}

make_path("$dir/lib/ZoneMinder", "$dir/lib/LWP", "$dir/lib/Crypt/Eksblowfish", "$dir/bin", "$dir/web", "$dir/db");
my $user = getpwuid($<);

write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
write_file("$dir/lib/LWP/UserAgent.pm", "package LWP::UserAgent;\n1;\n");
write_file("$dir/lib/Crypt/Eksblowfish/Bcrypt.pm", "package Crypt::Eksblowfish::Bcrypt;\n1;\n");
write_file("$dir/lib/ZoneMinder/Base.pm", <<'EOM');
package ZoneMinder::Base;
sub import {
  no strict 'refs';
  my $caller = caller();
  my $v = $ENV{TEST_ZM_VERSION};
  *{$caller.'::ZM_VERSION'} = sub () { $v };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Config.pm", <<EOM);
package ZoneMinder::Config;
our %Config = (
  ZM_WEB_USER => '$user', ZM_DB_USER => 'zmuser', ZM_DB_PASS => 'pw', ZM_DB_NAME => 'zm',
  ZM_DB_HOST => 'localhost', ZM_PATH_WEB => '$dir/web', ZM_PATH_DATA => '$dir', ZM_DIR_EVENTS => 'events',
  ZM_DIR_IMAGES => 'images', ZM_DIR_SOUNDS => 'sounds', ZM_PATH_MAP => '/dev/shm', ZM_PATH_ARP => '',
  ZM_DYN_DB_VERSION => \\\$ENV{TEST_DB_VERSION},
);
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::Config'} = \\%Config;
}
# -T taints everything that came from the environment, so launder it before use.
sub env_value {
  my \$v = shift;
  return undef if !defined(\$v);
  (\$v) = \$v =~ m{^(.*)\$}s;
  return \$v;
}
require ZoneMinder::Database;
ZoneMinder::Database::zmDbConnect();
sub syncConfigFlags { return 0; }
sub loadConfigFromDB { return 1; }
# TEST_SAVE_RESULT says what a failed write returns; the real one returns 0 and
# prints an error, and returns a true value on success.
sub saveConfigToDB {
  my \$result = env_value(\$ENV{TEST_SAVE_RESULT});
  return defined(\$result) ? \$result : 1;
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Logger.pm", <<'EOM');
package ZoneMinder::Logger;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit logSetSignal Debug Info Warning Error logDebugging);
  *{$caller.'::DEBUG'} = sub () { 1 };
  *{$caller.'::NOLOG'} = sub () { 0 };
  *{$caller.'::Fatal'} = sub { die "FATAL: @_\n" };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/General.pm", <<EOM);
package ZoneMinder::General;
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::systemStatus'} = sub { 'stopped' };
  *{\$caller.'::packageControl'} = sub { 1 };
  *{\$caller.'::makePath'} = sub { 1 };
  *{\$caller.'::jsonEncode'} = sub { '{}' };
  *{\$caller.'::findDbCommand'} = sub { '$dir/bin/mysql' };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Database.pm", <<'EOM');
package ZoneMinder::Database;
package FakeSth;
my $myisam_pending = 0;
sub execute {
  my $s = shift;
  open(my $fh, '>>', ($ENV{TEST_SQL_LOG} =~ m{^(.*)$})[0]);
  print $fh "EXEC $s->{sql} | @_\n";
  close $fh;
  $myisam_pending = defined($ENV{TEST_MYISAM}) ? 1 : 0;
  return 1;
}
sub fetchrow_hashref { return undef; }
# One MyISAM table row when TEST_MYISAM names one, otherwise the table is empty.
sub fetchrow {
  return undef unless $myisam_pending;
  $myisam_pending = 0;
  return ($ENV{TEST_MYISAM} =~ m{^(.*)$})[0];
}
sub finish { 1 }
sub errstr { '' }
package FakeDbh;
sub prepare_cached { my ($s, $sql) = @_; open(my $fh, '>>', ($ENV{TEST_SQL_LOG} =~ m{^(.*)$})[0]); print $fh "PREPARE $sql\n"; close $fh; return bless({sql => $sql}, 'FakeSth'); }
sub do { 1 }
sub errstr { '' }
package ZoneMinder::Database;
our $dbh;
sub zmDbConnect {
  my $force = shift;
  undef $dbh if $force;
  if (!$dbh) {
    $dbh = bless({}, 'FakeDbh');
  }
  return $dbh;
}
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::zmDbConnect'} = \&zmDbConnect;
  *{$caller.'::zmDbDisconnect'} = sub { 1 };
  *{$caller.'::zmDbDo'} = sub { 1 };
}
1;
EOM
write_file("$dir/bin/mysql", "#!/bin/sh\nexit 0\n");
chmod 0755, "$dir/bin/mysql";

my $src_path = $ENV{ZMUPDATE_SRC} || "$FindBin::Bin/../../scripts/zmupdate.pl.in";
my $source = read_file($src_path);
die "Can't read $src_path\n" if !$source;
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
$source =~ s/\@ZM_TMPDIR\@/$dir/g;
$source =~ s/\@ZM_CONFIG_SUBDIR\@/$dir/g;
$source =~ s/\@ZM_WEB_GROUP\@/root/g;
$source =~ s/\@ZM_[A-Z_]+\@/x/g;
write_file("$dir/zmupdate.pl", $source);

sub run_zmupdate {
  my ($args, $stdin, %env) = @_;
  unlink("$dir/sql.log");
  write_file("$dir/in.txt", $stdin);
  local $ENV{TEST_SQL_LOG} = "$dir/sql.log";
  local @ENV{keys %env} = values %env;
  my $out = qx(timeout 10 $^X -T -I$dir/lib $dir/zmupdate.pl $args <$dir/in.txt 2>&1);
  return ($out, $? >> 8);
}

my @base_env = (TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.37.5');

# -f must fail when saving the configuration fails.
my ($out, $status) = run_zmupdate('--nointeractive -f', '', @base_env, TEST_SAVE_RESULT => '0');
check('freshen exits non-zero when saveConfigToDB fails', $status == 0 ? 'exit 0' : 'non-zero exit', 'non-zero exit');
check('freshen reports the failed save',
  $out =~ /Failed to save the configuration to the database/ ? 'reported' : 'not reported', 'reported');

# -f must still succeed when saving the configuration works.
($out, $status) = run_zmupdate('--nointeractive -f', '', @base_env, TEST_SAVE_RESULT => '1');
check('freshen exits 0 when saveConfigToDB succeeds', $status, 0);
check('successful freshen does not report a failure',
  $out =~ /Failed to save the configuration/ ? 'reported' : 'not reported', 'not reported');

# With no answer available on stdin the MyISAM prompt must abort, not silently skip.
($out, $status) = run_zmupdate('--interactive -f', '', @base_env,
  TEST_SAVE_RESULT => '1', TEST_MYISAM => 'Users');
check('closed stdin at the MyISAM prompt aborts', $status == 0 ? 'exit 0' : 'non-zero exit', 'non-zero exit');
check('closed stdin at the MyISAM prompt is reported',
  $out =~ /No answer available on stdin/ ? 'reported' : 'not reported', 'reported');
check('closed stdin at the MyISAM prompt warns about no undefined value',
  $out =~ /Use of uninitialized value/ ? 'uninitialized warning' : 'no uninitialized warning', 'no uninitialized warning');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
