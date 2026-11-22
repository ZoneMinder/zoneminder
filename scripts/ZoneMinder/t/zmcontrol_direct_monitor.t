use strict;
use warnings;
use Test::More;
use File::Basename qw(dirname);
use HTTP::Response;

# zmcontrol.pl --protocol builds a plain hash as the Monitor. Modules that
# suspend motion detection during a move call methods on that Monitor unless
# ModectDuringPTZ is set, so the stand-in must set it or every move dies.

my $script = $ENV{ZMCONTROL_SRC} // dirname(__FILE__).'/../../zmcontrol.pl.in';
open(my $fh, '<', $script) or die "open $script: $!";
my $src = do { local $/; <$fh> };
close $fh;

my ($stub) = $src =~ /my \$control = (bless \{\n.*?\n  \}, \$module);/s;
ok($stub, 'found the direct mode Monitor stand-in in zmcontrol.pl.in');

require_ok('ZoneMinder::Control::Trendnet');
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Trendnet::get = sub { HTTP::Response->new(200, 'OK', [], 'ok') };
}

my ($device, $address) = ('', 'user:pass@camera');
my %options = (autostop => 0);
my $module = 'ZoneMinder::Control::Trendnet';
my $control = eval $stub;
ok($control, 'the stand-in evaluates') or diag $@;

my $ok = eval { $control->sendCmd('x'); 1 };
ok($ok, 'a move through the direct mode stand-in does not die') or diag $@;

done_testing();
