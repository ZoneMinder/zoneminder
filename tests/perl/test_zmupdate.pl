#!/usr/bin/perl
# Runs scripts/zmupdate.pl.in against stubbed ZoneMinder modules, a fake DBI handle
# that only records SQL, and a fake mysql that only logs what it is given. No real
# database or mysql binary is touched.
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
  *{\$caller.'::findDbCommand'} = sub { '$dir/bin/mysql' };
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
  return undef if $ENV{TEST_CONNECT_FAIL};
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

my $source = read_file("$FindBin::Bin/../../scripts/zmupdate.pl.in");
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

# No answer on stdin (closed or at EOF) used to loop forever re-asking the backup question.
my ($out, $status) = run_zmupdate("--interactive -v 1.36.0 -d $dir/db", '',
  TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.0');
check('EOF at the backup prompt does not hang', $status == 124 ? 'timed out' : 'terminated', 'terminated');
check('EOF at the backup prompt aborts the upgrade', $status == 0 ? 'exit 0' : 'non-zero exit', 'non-zero exit');
check('EOF at the backup prompt applies no patch', -e "$dir/mysql.log" ? 'mysql ran' : 'mysql not run', 'mysql not run');

# --migrate-events must select the column the Events table has.
($out, $status) = run_zmupdate('--nointeractive --migrate-events', "y\n",
  TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.0');
my $log = read_file("$dir/sql.log");
my ($events_sql) = $log =~ /PREPARE (SELECT .*FROM Events)/;
check('migrate-events queries an Events column that exists', ($events_sql // '') =~ /\bStartDateTime\b/ ? 'StartDateTime' : ($events_sql // 'no query'), 'StartDateTime');

# Patches are applied oldest first, and one newer than ZM_VERSION is left alone.
for my $v ('1.36.1', '1.36.10', '1.36.9', '1.37.5', '1.37.6') {
  write_file("$dir/db/zm_update-$v.sql", "-- patch $v\n");
}
($out, $status) = run_zmupdate("--nointeractive -v 1.36.2 -d $dir/db", '',
  TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.2');
my @applied = (read_file("$dir/mysql.log") =~ /^-- patch (\S+)/mg);
check('patches apply in version order and skip newer ones', join(' ', @applied), '1.36.9 1.36.10 1.37.5');
check('upgrade exits 0', $status, 0);

# --user and --pass must be in effect for the connection zmupdate makes.
my $connect_log = "$dir/connect.log";
unlink($connect_log);
($out, $status) = run_zmupdate('--nointeractive --user=root --pass=rootpw --migrate-events', "y\n",
  TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.0', TEST_CONNECT_LOG => $connect_log);
check('--user and --pass are used for the db connection', (split /\n/, read_file($connect_log))[-1] // 'no connect', 'CONNECT root/rootpw');

# A failed connection says where the credentials come from and how to override them.
($out, $status) = run_zmupdate('--nointeractive --check', '',
  TEST_ZM_VERSION => '1.37.5', TEST_DB_VERSION => '1.36.0', TEST_CONNECT_FAIL => 1);
check('a failed connection exits non-zero', $status ? 'non-zero exit' : 'exit 0', 'non-zero exit');
check('a failed connection points at zm.conf', $out =~ /zm\.conf/ ? 'mentions zm.conf' : $out, 'mentions zm.conf');
check('a failed connection mentions --user and --pass', ($out =~ /--user/ && $out =~ /--pass/) ? 'mentions them' : $out, 'mentions them');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
