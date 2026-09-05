use strict;
use warnings;
use POSIX qw(tzset);
use Test::More tests => 33;

require_ok('ZoneMinder::Control::FoscamHD');

# ---------------------------------------------------------------------------
# _parse_cgi_result
# ---------------------------------------------------------------------------

my $answer = ZoneMinder::Control::FoscamHD::_parse_cgi_result(<<'XML');
<CGI_Result>
    <result>0</result>
    <timeSource>0</timeSource>
    <ntpServer></ntpServer>
    <timeZone>14400</timeZone>
    <pkgTime>2015-08-11_14%3A17%3A32</pkgTime>
</CGI_Result>
XML

is($answer->{result}, 0, 'parses result');
is($answer->{timeZone}, 14400, 'parses a value');
is($answer->{ntpServer}, '', 'parses an empty element as empty string');
is($answer->{pkgTime}, '2015-08-11_14:17:32', 'url-unescapes values the camera encoded');

is(ZoneMinder::Control::FoscamHD::_parse_cgi_result('not xml at all'),
  undef, 'returns undef when there is no CGI_Result');
is(ZoneMinder::Control::FoscamHD::_parse_cgi_result(undef),
  undef, 'returns undef for undef content');

# ---------------------------------------------------------------------------
# local_timezone_offset - seconds WEST of UTC, the sign the camera wants
# ---------------------------------------------------------------------------

my $original_tz = $ENV{TZ};
sub with_tz {
  my ($tz, $epoch) = @_;
  $ENV{TZ} = $tz;
  tzset();
  my $offset = ZoneMinder::Control::FoscamHD::local_timezone_offset($epoch);
  return $offset;
}

# 2026-09-05T17:38:24Z, which is EDT (UTC-4) in Toronto
is(with_tz('America/Toronto', 1788622704), 14400, 'EDT is 14400 seconds west');
# 2026-01-15T12:00:00Z, which is EST (UTC-5)
is(with_tz('America/Toronto', 1768478400), 18000, 'EST is 18000 seconds west');
is(with_tz('UTC', 1788622704), 0, 'UTC is 0');
# East of UTC is negative
is(with_tz('Asia/Tokyo', 1788622704), -32400, 'JST is -32400, east of UTC');
# 2026-01-01T02:00:00Z crosses back over new year in Toronto
is(with_tz('America/Toronto', 1767232800), 18000, 'handles a year boundary');
# 2026-01-01T20:00:00Z crosses forward over new year in Tokyo
is(with_tz('Asia/Tokyo', 1767297600), -32400, 'handles a forward year boundary');
# A half hour zone, to prove minutes are carried
is(with_tz('Asia/Kolkata', 1788622704), -19800, 'IST is -19800');

# It is reached as $control->local_timezone_offset() from cameratool.pl, so a
# leading invocant must not be mistaken for the instant to convert.
$ENV{TZ} = 'America/Toronto';
tzset();
my $as_method = ZoneMinder::Control::FoscamHD->local_timezone_offset(1788622704);
is($as_method, 14400, 'ignores a leading invocant when called as a method');
my $blessed = bless {}, 'ZoneMinder::Control::FoscamHD';
is($blessed->local_timezone_offset(1788622704), 14400,
  'ignores an object invocant too');

if (defined $original_tz) { $ENV{TZ} = $original_tz } else { delete $ENV{TZ} }
tzset();

# ---------------------------------------------------------------------------
# set_config - the merge that keeps a partial write from zeroing the rest
# ---------------------------------------------------------------------------

my @sent;
my %reads = (
  getSystemTime => {
    timeSource => 0, ntpServer => '', dateFormat => 0, timeFormat => 1,
    timeZone => 0, isDst => 0, dst => 0,
    year => 2026, mon => 8, day => 31, hour => 2, minute => 40, sec => 46,
  },
  getOSDSetting => {
    isEnableTimeStamp => 1, isEnableTempAndHumid => 0,
    isEnableDevName => 0, dispPos => 0, isEnableOSDMask => 0,
  },
  getDDNSConfig => {
    isEnable => 0, hostName => '', ddnsServer => 0,
    user => '', password => '', factoryDDNS => 'ji2844.myfoscam.org',
  },
);

my $stub = bless {}, 'ZoneMinder::Control::FoscamHD';

no warnings 'redefine', 'once';
local *ZoneMinder::Control::FoscamHD::cgi = sub {
  my ($self, $cmd, $params) = @_;
  push @sent, [$cmd, $params];
  return $reads{$cmd} ? {%{$reads{$cmd}}} : {};
};
use warnings 'redefine', 'once';

# Setting only timeSource/ntpServer must still send the rest of the section.
@sent = ();
ok($stub->set_config({SystemTime => {timeSource => 0, ntpServer => '192.168.2.66'}}),
  'set_config succeeds for SystemTime');
is(scalar @sent, 2, 'reads the section back before writing it');
is($sent[0][0], 'getSystemTime', 'reads first');
is($sent[1][0], 'setSystemTime', 'writes second');
my $written = $sent[1][1];
is($written->{ntpServer}, '192.168.2.66', 'writes the wanted value');
is($written->{timeFormat}, 1, 'carries over a field that was not in the diff');
ok(!exists $written->{year},
  'drops the manual clock fields when the camera is on NTP');

# timeSource 1 is manual, so the clock fields have to survive.
@sent = ();
$stub->set_config({SystemTime => {timeSource => 1, year => 2026, mon => 9, day => 5}});
is($sent[1][1]{year}, 2026, 'keeps the clock fields when setting the time manually');

# factoryDDNS is reported by the reader and refused by the writer.
@sent = ();
$stub->set_config({DDNSConfig => {isEnable => 1}});
ok(!exists $sent[1][1]{factoryDDNS}, 'drops factoryDDNS before writing DDNSConfig');

# A read-only section must not be written.
@sent = ();
ok($stub->set_config({VideoStreamParam => {frameRate0 => 10}}),
  'set_config does not fail on a read-only section');
is(scalar @sent, 0, 'read-only section issues no command at all');

# Per-field sections use one command per field and do not read back.
@sent = ();
$stub->set_config({ImageSetting => {brightness => 60, contrast => 40}});
is(scalar @sent, 2, 'ImageSetting sends one command per field');
is($sent[0][0], 'setBrightness', 'brightness has its own command');
is_deeply($sent[0][1], {brightness => 60}, 'and carries only its own field');

# denoiseLevel is readable but has no setter on this firmware.
@sent = ();
$stub->set_config({ImageSetting => {denoiseLevel => 10}});
is(scalar @sent, 0, 'a field with no setter issues no command');

# ---------------------------------------------------------------------------
# set_time
# ---------------------------------------------------------------------------

@sent = ();
is($stub->set_time(), undef, 'set_time refuses to run without an ntp server');

@sent = ();
$stub->set_time(ntp_server => '192.168.2.66', timezone => 14400);
is_deeply(
  {map { $_ => $sent[1][1]{$_} } qw(timeSource ntpServer timeZone isDst dst)},
  {timeSource => 0, ntpServer => '192.168.2.66', timeZone => 14400, isDst => 0, dst => 0},
  'set_time sends NTP mode with dst folded into timeZone');
