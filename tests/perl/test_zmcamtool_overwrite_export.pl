#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  $got = '(undef)' if !defined $got;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmcamtool.pl.in") or die "Can't open zmcamtool.pl.in: $!";
  local $/;
  <$fh>;
};

# Globals the script's subs expect
my %Config = (ZM_DB_HOST => 'localhost', ZM_DB_NAME => 'zm', ZM_PATH_DATA => '/nonexistent');
my ($dbUser, $dbPass, $overwrite, $noregex, $sqlfile) = ('zmuser', '', 1, 0, '');
my $dumpcmd = 'echo';
sub findDbCommand { return $dumpcmd; }
sub logDebugging { return 0; }

# A database that remembers what it was asked, with Controls 'Foo' at Id 7
my @log;
my $dbh;
sub zmDbConnect { return $dbh; }
{
  package FakeSth;
  sub new { my ($c, $sql) = @_; return bless {sql => $sql}, $c; }
  sub execute { my $s = shift; push @log, [$s->{sql}, @_]; return 1; }
  sub fetchrow_arrayref { return $_[0]{sql} =~ /count\(\*\)/ ? [1] : undef; }
  sub fetchrow_array { return $_[0]{sql} =~ /MIN\(`Id`\)/ ? (7) : (); }
  sub finish { return 1; }
  sub errstr { return ''; }
  package FakeDbh;
  sub prepare_cached { return FakeSth->new($_[1]); }
  sub last_insert_id { return 99; }
  sub errstr { return ''; }
}
$dbh = bless {}, 'FakeDbh';

my ($subs) = $source =~ /(sub selectQuery.*?)\n1;\n__END__/s;
eval $subs.';1' or die "eval failed: $@";

my $dir = tempdir(CLEANUP => 1);
open(my $fh, '>', "$dir/c.sql") or die;
print $fh "INSERT INTO `Controls` (`Id`,`Name`,`Protocol`) VALUES (3,'Foo','FooProto');\n";
close $fh;

# 1. Overwriting a control must leave it under the Id Monitors.ControlId holds
@ARGV = ("$dir/c.sql");
{
  open(my $out, '>', \my $buf);
  my $old = select($out);
  importsql();
  select($old);
}
my ($upd) = grep { $_->[0] =~ /^UPDATE `Controls` SET `Id`/ } @log;
check('overwrite restores the control Id', $upd ? "$upd->[1]<-$upd->[2]" : 'no UPDATE', '7<-99');

# 2. A socket host is not a port
for my $case (['localhost:/run/mysqld/mysqld.sock', 'zm'], ['dbhost:3307', 'zm']) {
  $Config{ZM_DB_HOST} = $case->[0];
  $dumpcmd = 'echo';
  @ARGV = ();
  open(my $out, '>', \my $buf);
  my $old = select($out);
  exportsql();
  select($old);
  if ($case->[0] =~ /sock/) {
    check('socket host uses -S', ($buf =~ /-S'?\/run\/mysqld\/mysqld\.sock/ ? 'S' : $buf), 'S');
    check('socket host is not passed as -P', ($buf =~ /-P/ ? 'P' : 'noP'), 'noP');
  } else {
    check('host:port still uses -P', ($buf =~ /-P3307/ ? 'P' : $buf), 'P');
  }
}

# 3. topreset argument must be all digits
my ($cond) = $source =~ /if \( \(\$topreset\)&&(\(.*?\)) \) \{\n  print\( STDERR qq\/Parameter "topreset"/s;
my $topreset = 1;
for my $c (['12', 0], ['12abc', 1], ['abc', 1]) {
  @ARGV = ($c->[0]);
  my $bad = eval $cond;
  check("topreset arg '$c->[0]' rejected=$c->[1]", $bad ? 1 : 0, $c->[1]);
}
@ARGV = ();
check('missing topreset arg is rejected', eval($cond) ? 1 : 0, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
