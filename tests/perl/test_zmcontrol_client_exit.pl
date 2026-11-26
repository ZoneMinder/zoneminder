#!/usr/bin/perl
#
# zmcontrol.pl run as a client (--id N --command X) when no control server can
# be reached. It logged the error and exited 0, so a cron entry for a scheduled
# monitor action never reported that the command was not delivered.
#
# Run as: perl tests/perl/test_zmcontrol_client_exit.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib", "$dir/socks");

# Just enough of ZoneMinder for the client path: no database, no zmdc.
open(my $fh, '>', "$dir/lib/ZoneMinder.pm") or die $!;
print $fh <<"EOS";
package ZoneMinder;
use strict;
use warnings;
use Exporter 'import';
use JSON::MaybeXS ();
BEGIN { *CORE::GLOBAL::sleep = sub { 1 }; }
our \@EXPORT = qw(\%Config logInit Debug Info Warning Error Fatal runCommand jsonEncode acquireExclusiveLock);
our \%Config = (ZM_PATH_SOCKS => '$dir/socks');
sub logInit {}
sub Debug {}
sub Info {}
sub Warning {}
sub Error { print STDERR "Error: \@_\\n"; }
sub Fatal { die \@_; }
sub runCommand { return ''; }
sub jsonEncode { return JSON::MaybeXS::encode_json(\$_[0]); }
sub acquireExclusiveLock { return; }
1;
EOS
close($fh);

my $src = "$FindBin::Bin/../../scripts/zmcontrol.pl.in";
open(my $in, '<', $src) or die "$src: $!";
my $script = do { local $/; <$in> };
close($in);
$script =~ s/\@PERL_EXECUTABLE\@/$^X/;
$script =~ s/\@EXTRA_PERL_LIB\@/use lib '$dir\/lib';/;
$script =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/;
open(my $out, '>', "$dir/zmcontrol.pl") or die $!;
print $out $script;
close($out);

my $libs = join(' ', map { "-I '$_'" } @INC);
my $output = `$^X -T $libs $dir/zmcontrol.pl --id 4 --command moveConLeft 2>&1`;
my $status = $? >> 8;
check('the client reports that no server could be reached', $output =~ /Unable to connect to zmcontrol server/ ? 1 : 0, 1);
check('the client exits non-zero when the command was not delivered', $status != 0 ? 1 : 0, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
