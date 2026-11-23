#!/usr/bin/perl
# Runs scripts/zmupdate.pl.in against stubbed ZoneMinder modules, a fake DBI handle
# and fake mysql and mysqldump binaries. No real database is touched.
# The interactive backup must write an owner-only dump, must not write through a
# symlink planted at the dump name, and --dir must not reach the shell.
# Set ZMUPDATE_IN to another zmupdate.pl.in to test it.
#
# Run as: perl tests/perl/test_zmupdate_backup.pl
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

sub read_file {
  my ($path) = @_;
  open(my $fh, '<', $path) or return '';
  local $/;
  my $c = <$fh>;
  close($fh);
  return $c;
}

my $dir = tempdir(CLEANUP => 1);
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
  ZM_DYN_DB_VERSION => \$ENV{TEST_DB_VERSION},
);
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::Config'} = \\%Config;
}
# Like the real module, connect while loading, with the zm.conf credentials
require ZoneMinder::Database;
ZoneMinder::Database::zmDbConnect();
sub syncConfigFlags { return 0; }
sub loadConfigFromDB { return 1; }
sub saveConfigToDB { return 1; }
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
  *{\$caller.'::findDbCommand'} = sub { '$dir/bin/'.\$_[0] };
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Database.pm", <<'EOM');
package ZoneMinder::Database;
package FakeSth;
sub execute { my $s = shift; open(my $fh, '>>', ($ENV{TEST_SQL_LOG} =~ m{^(.*)$})[0]); print $fh "EXEC $s->{sql} | @_\n"; close $fh; return 1; }
sub fetchrow_hashref { return undef; }
sub fetchrow { return undef; }
sub finish { 1 }
sub errstr { '' }
package FakeDbh;
sub prepare_cached { my ($s, $sql) = @_; open(my $fh, '>>', ($ENV{TEST_SQL_LOG} =~ m{^(.*)$})[0]); print $fh "PREPARE $sql\n"; close $fh; return bless({sql => $sql}, 'FakeSth'); }
sub do { 1 }
sub errstr { '' }
package ZoneMinder::Database;
# Like the real one, keep the connection and only make a new one when forced
our $dbh;
sub zmDbConnect {
  my $force = shift;
  undef $dbh if $force;
  if (!$dbh) {
    if ($ENV{TEST_CONNECT_LOG}) {
      open(my $fh, '>>', ($ENV{TEST_CONNECT_LOG} =~ m{^(.*)$})[0]);
      print $fh "CONNECT $ZoneMinder::Config::Config{ZM_DB_USER}/$ZoneMinder::Config::Config{ZM_DB_PASS}\n";
      close $fh;
    }
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
write_file("$dir/bin/mysql", "#!/bin/sh\necho \"MYSQL \$*\" >> $dir/mysql.log\ncat >> $dir/mysql.log\n");
chmod 0755, "$dir/bin/mysql";
write_file("$dir/bin/mysqldump", "#!/bin/sh\necho '-- dump'\n");
chmod 0755, "$dir/bin/mysqldump";

my $source = read_file($ENV{ZMUPDATE_IN} || "$FindBin::Bin/../../scripts/zmupdate.pl.in");
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
  unlink("$dir/sql.log", "$dir/mysql.log");
  write_file("$dir/in.txt", $stdin);
  local $ENV{TEST_SQL_LOG} = "$dir/sql.log";
  local @ENV{keys %env} = values %env;
  my $out = qx(timeout 5 $^X -T -I$dir/lib $dir/zmupdate.pl $args <$dir/in.txt 2>&1);
  return ($out, $? >> 8);
}


$ENV{PATH} = '/usr/bin:/bin';
my %env = (TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.0');
my $dump = "$dir/zm-1.36.0.dump";

# Interactive backup: the dump is private.
my ($out, $status) = run_zmupdate("--interactive -v 1.36.0 -d $dir/db", "y\ny\n", %env);
check('backup is written', -s $dump ? 'written' : "missing: $out", 'written');
my $mode = -e $dump ? (stat($dump))[2] & 07777 : 0;
check('backup is readable by the owner only', sprintf('%04o', $mode & 077), '0000');

# A symlink planted at the dump name must not be written through.
my $victim = "$dir/victim.txt";
write_file($victim, "keep me\n");
unlink($dump);
symlink($victim, $dump);
($out, $status) = run_zmupdate("--interactive -v 1.36.0 -d $dir/db", "y\ny\n", %env);
check('a planted symlink target is not overwritten', read_file($victim), "keep me\n");
check('backup replaces the symlink with a regular file', (-l $dump || !-f $dump) ? 'not a file' : 'file', 'file');

# --dir with shell syntax must be a path, not a command.
my $evil = 'd$(touch pwned)';
make_path("$dir/$evil");
write_file("$dir/$evil/zm_update-1.37.5.sql", "-- patch\n");
unlink("$dir/pwned");
chdir($dir);
($out, $status) = run_zmupdate("--nointeractive -v 1.37.4 -d '$evil'", '', TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.37.4');
check('--dir is not run by the shell', -e "$dir/pwned" ? 'command ran' : 'not run', 'not run');
check('--dir patch is still applied', read_file("$dir/mysql.log") =~ /-- patch/ ? 'applied' : "not applied: $out", 'applied');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
