#!/usr/bin/perl
#
# The zmcontrol server opened the camera once at startup. If that failed (camera
# offline at boot) or the module marked itself closed after an error, nothing
# reopened it, so every later command ran against a closed control until the
# server was restarted by hand.
#
# Run as: perl tests/perl/test_zmcontrol_reopen.pl
# ZMCONTROL_SRC may point at another copy of zmcontrol.pl.in to run it instead.
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use IO::Socket::UNIX;
use JSON::PP qw(encode_json);

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder", "$dir/socks");
my $log = "$dir/control.log";

open(my $fh, '>', "$dir/lib/ZoneMinder.pm") or die $!;
print $fh <<"EOS";
package ZoneMinder;
use strict;
use warnings;
use Exporter 'import';
use JSON::PP ();
our \@EXPORT = qw(\%Config logInit Debug Info Warning Error Fatal runCommand jsonEncode acquireExclusiveLock);
our \%Config = (ZM_PATH_SOCKS => '$dir/socks');
sub logInit {}
sub Debug {}
sub Info {}
sub Warning {}
sub Error {}
sub Fatal { die \@_; }
sub runCommand { return ''; }
sub jsonEncode { return JSON::PP::encode_json(\$_[0]); }
sub acquireExclusiveLock { open(my \$l, '>', '$dir/lock'); return (\$l, 'ok', \$\$); }
1;
EOS
close($fh);

# A control whose first open() fails, the way a camera that is down at boot does.
# JSON::MaybeXS is only a front for JSON::PP here, so do without it being installed.
make_path("$dir/lib/JSON");
open($fh, '>', "$dir/lib/JSON/MaybeXS.pm") or die $!;
print $fh <<'EOS';
package JSON::MaybeXS;
use strict;
use warnings;
use JSON::PP ();
sub import { no strict 'refs'; my $c = caller; *{"${c}::decode_json"} = \&JSON::PP::decode_json; *{"${c}::encode_json"} = \&JSON::PP::encode_json; }
1;
EOS
close($fh);

open($fh, '>', "$dir/lib/ZoneMinder/Monitor.pm") or die $!;
print $fh <<"EOS";
package ZoneMinder::Monitor;
use strict;
use warnings;
sub find_one { return bless {}, 'ZoneMinder::Monitor'; }
sub Control { return bless {Protocol => 'Fake', Id => 1}, 'ZoneMinder::Control::Fake'; }
package ZoneMinder::Control::Fake;
my \$opens = 0;
sub getKey { 1 }
sub loadMonitor {}
sub close {}
sub open {
  my \$self = shift;
  \$opens++;
  return undef if \$opens == 1;
  \$self->{state} = 'open';
  return 1;
}
sub moveConUp {
  my \$self = shift;
  open(my \$o, '>>', '$log') or die \$!;
  print \$o "moveConUp state=".(\$self->{state} // 'undef')." opens=\$opens\\n";
  close(\$o);
  return 1;
}
1;
EOS
close($fh);

my $src = $ENV{ZMCONTROL_SRC} // "$FindBin::Bin/../../scripts/zmcontrol.pl.in";
open(my $in, '<', $src) or die "$src: $!";
my $script = do { local $/; <$in> };
close($in);
$script =~ s/\@PERL_EXECUTABLE\@/$^X/;
$script =~ s/\@EXTRA_PERL_LIB\@/use lib '$dir\/lib';/;
$script =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/;
open(my $out, '>', "$dir/zmcontrol.pl") or die $!;
print $out $script;
close($out);

my @inc = map { ('-I', $_) } @INC;
my $pid = fork();
die "fork: $!" if !defined $pid;
if (!$pid) {
  open(STDOUT, '>', "$dir/server.out");
  open(STDERR, '>&', \*STDOUT);
  exec($^X, '-T', @inc, "$dir/zmcontrol.pl", '--id', '1') or die $!;
}

sub send_cmd {
  my $cmd = shift;
  my $sock;
  for (1 .. 50) {
    $sock = IO::Socket::UNIX->new(Peer => "$dir/socks/zmcontrol-1.sock");
    last if $sock;
    select(undef, undef, undef, 0.1);
  }
  die "server never listened: ".do { local (@ARGV, $/) = ("$dir/server.out"); <> // '' } if !$sock;
  print $sock encode_json({command => $cmd, wants_response => 1});
  $sock->shutdown(1);
  my $reply = do { local $/; <$sock> };
  close($sock);
  return $reply;
}

send_cmd('moveConUp');
send_cmd('quit');
waitpid($pid, 0);

my $got = -e $log ? do { local (@ARGV, $/) = ($log); <> } : '';
check('the control is reopened before the first command runs', $got, "moveConUp state=open opens=2\n");

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
